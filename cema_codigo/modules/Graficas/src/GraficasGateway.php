<?php
/*
 * Gateway del módulo "Graficas": datos del dashboard de estudiantes.
 * Vive dentro del módulo (no en src/ del core) para sobrevivir a las
 * actualizaciones del núcleo de Gibbon.
 */

namespace Modules\Graficas;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

class GraficasGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStudentEnrolment';
    private static $primaryKey = 'gibbonStudentEnrolmentID';

    private static $searchableColumns = [];

    /**
     * Base del dashboard "Gráficas de estudiantes": una fila por estudiante y año escolar
     * (años Past y Current). No devuelve nombres ni identificadores, solo campos agregables.
     * - grado sale de gibbonYearGroup (los nombres de grupo cambian de formato entre años).
     * - edad = años cumplidos al primer día del año escolar (NULL si dob vacío o 0000-00-00).
     * - antiguedad = años escolares distintos del estudiante hasta el año de la fila.
     * - En el año actual solo cuentan personas con status 'Full'.
     * $columnaNacionalidad es configurable porque gibbonPerson.nationality no está confirmada
     * en el esquema del repositorio; si la columna no existe se devuelve 'Sin dato'.
     */
    public function queryGraficasEstudiantes($columnaNacionalidad = 'nationality')
    {
        $columnaNacionalidad = preg_match('/^[A-Za-z0-9_]+$/', (string) $columnaNacionalidad) ? $columnaNacionalidad : '';
        $existe = $columnaNacionalidad !== '' && $this->db()->selectOne(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'gibbonPerson' AND column_name = :columna",
            ['columna' => $columnaNacionalidad]
        ) > 0;
        $nacionalidad = $existe
            ? "COALESCE(NULLIF(TRIM(gibbonPerson.`{$columnaNacionalidad}`), ''), 'Sin dato')"
            : "'Sin dato'";

        return $this->db()->select(
            "SELECT gibbonSchoolYear.name AS anio,
                    gibbonSchoolYear.status AS estadoAnio,
                    gibbonYearGroup.name AS grado,
                    gibbonYearGroup.sequenceNumber AS ordenGrado,
                    TRIM(gibbonFormGroup.nameShort) AS grupo,
                    CASE gibbonPerson.gender WHEN 'F' THEN 'Niñas' WHEN 'M' THEN 'Niños' ELSE 'Sin dato' END AS genero,
                    CASE WHEN gibbonPerson.dob IS NULL OR gibbonPerson.dob = '0000-00-00' THEN NULL
                         ELSE TIMESTAMPDIFF(YEAR, gibbonPerson.dob, COALESCE(gibbonSchoolYear.firstDay, CURDATE())) END AS edad,
                    COALESCE(NULLIF(TRIM(gibbonPerson.address1District), ''), 'Sin dato') AS localidad,
                    {$nacionalidad} AS nacionalidad,
                    (SELECT COUNT(DISTINCT e2.gibbonSchoolYearID)
                     FROM gibbonStudentEnrolment AS e2
                     INNER JOIN gibbonSchoolYear AS y2 ON y2.gibbonSchoolYearID = e2.gibbonSchoolYearID
                     WHERE e2.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
                     AND y2.sequenceNumber <= gibbonSchoolYear.sequenceNumber) AS antiguedad
             FROM gibbonStudentEnrolment
             INNER JOIN gibbonPerson ON gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
             INNER JOIN gibbonYearGroup ON gibbonYearGroup.gibbonYearGroupID = gibbonStudentEnrolment.gibbonYearGroupID
             INNER JOIN gibbonFormGroup ON gibbonFormGroup.gibbonFormGroupID = gibbonStudentEnrolment.gibbonFormGroupID
             INNER JOIN gibbonSchoolYear ON gibbonSchoolYear.gibbonSchoolYearID = gibbonStudentEnrolment.gibbonSchoolYearID
             WHERE gibbonSchoolYear.status IN ('Past', 'Current')
             AND (gibbonSchoolYear.status = 'Past' OR gibbonPerson.status = 'Full')
             ORDER BY gibbonSchoolYear.sequenceNumber, gibbonYearGroup.sequenceNumber, grupo"
        )->fetchAll();
    }

    /**
     * Base del dashboard "Gráficas de docentes": una fila por evento (planeación u observación).
     * Campos: tipo ('P' planeación / 'O' observación), idEvento (para contar sin duplicados),
     * fecha, docente ("Apellido Nombre") y materia. No devuelve IDs de persona ni contacto.
     * - Planeaciones: se atribuyen a quien las creó (gibbonPlannerEntry.gibbonPersonIDCreator),
     *   y solo si esa persona es o fue docente de alguna clase (gibbonCourseClassPerson.role
     *   'Teacher' / 'Teacher - Left'); así no aparecen directivos u otro personal que crearon una planeación.
     *   Respaldo: si el creador ya no existe en gibbonPerson, se atribuyen a cada docente
     *   (role 'Teacher') de la clase; ese caso repite el idEvento y por eso se cuenta distinto.
     * - Observaciones: obsObservaciones.gibbonPersonID_create y fechaIncidente; no tienen materia.
     * $fechaMinima descarta fechas mal digitadas (años 26, 206…); $fechaMaxima (o null) descarta
     * eventos futuros. Con $incluirObservaciones = false solo se traen planeaciones (más liviano).
     */
    public function queryGraficasDocentes($fechaMinima, $fechaMaxima = null, $incluirObservaciones = true)
    {
        $params = [];
        $limites = function ($columna, $sufijo) use (&$params, $fechaMinima, $fechaMaxima) {
            $params['min' . $sufijo] = $fechaMinima;
            $sql = " AND {$columna} >= :min{$sufijo}";
            if ($fechaMaxima !== null) {
                $params['max' . $sufijo] = $fechaMaxima;
                $sql .= " AND {$columna} <= :max{$sufijo}";
            }
            return $sql;
        };

        $sql = "SELECT 'P' AS tipo, pe.gibbonPlannerEntryID AS idEvento, pe.date AS fecha,
                       CONCAT(TRIM(per.surname), ' ', TRIM(per.preferredName)) AS docente, gibbonCourse.name AS materia
                FROM gibbonPlannerEntry AS pe
                INNER JOIN gibbonCourseClass ON gibbonCourseClass.gibbonCourseClassID = pe.gibbonCourseClassID
                INNER JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                INNER JOIN gibbonPerson AS per ON per.gibbonPersonID = pe.gibbonPersonIDCreator
                WHERE pe.date IS NOT NULL" . $limites('pe.date', 'A') . "
                AND EXISTS (SELECT 1 FROM gibbonCourseClassPerson AS doc
                            WHERE doc.gibbonPersonID = pe.gibbonPersonIDCreator AND doc.role IN ('Teacher', 'Teacher - Left'))
                UNION ALL
                SELECT 'P', pe.gibbonPlannerEntryID, pe.date,
                       CONCAT(TRIM(per.surname), ' ', TRIM(per.preferredName)), gibbonCourse.name
                FROM gibbonPlannerEntry AS pe
                INNER JOIN gibbonCourseClass ON gibbonCourseClass.gibbonCourseClassID = pe.gibbonCourseClassID
                INNER JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                INNER JOIN gibbonCourseClassPerson AS ccp ON ccp.gibbonCourseClassID = pe.gibbonCourseClassID AND ccp.role = 'Teacher'
                INNER JOIN gibbonPerson AS per ON per.gibbonPersonID = ccp.gibbonPersonID
                WHERE pe.date IS NOT NULL" . $limites('pe.date', 'B') . "
                AND NOT EXISTS (SELECT 1 FROM gibbonPerson AS creador WHERE creador.gibbonPersonID = pe.gibbonPersonIDCreator)
                " . ($incluirObservaciones ? "UNION ALL
                SELECT 'O', o.id, o.fechaIncidente,
                       CONCAT(TRIM(per.surname), ' ', TRIM(per.preferredName)), 'Sin materia'
                FROM obsObservaciones AS o
                INNER JOIN gibbonPerson AS per ON per.gibbonPersonID = o.gibbonPersonID_create
                WHERE o.fechaIncidente IS NOT NULL" . $limites('o.fechaIncidente', 'C') : '') . "
                ORDER BY fecha";

        return $this->db()->select($sql, $params)->fetchAll();
    }

    /**
     * Docentes activos: personas con estado 'Full' que son docentes (role 'Teacher') en alguna clase
     * del año escolar actual. No basta con estar en gibbonStaff: ahí también está el personal
     * administrativo y de apoyo. Solo "Apellido Nombre". Es el denominador del indicador de cumplimiento.
     */
    public function queryDocentesActivos()
    {
        return $this->db()->select(
            "SELECT DISTINCT CONCAT(TRIM(gibbonPerson.surname), ' ', TRIM(gibbonPerson.preferredName)) AS docente
             FROM gibbonPerson
             INNER JOIN gibbonCourseClassPerson ON gibbonCourseClassPerson.gibbonPersonID = gibbonPerson.gibbonPersonID
                 AND gibbonCourseClassPerson.role = 'Teacher'
             INNER JOIN gibbonCourseClass ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
             INNER JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
             INNER JOIN gibbonSchoolYear ON gibbonSchoolYear.gibbonSchoolYearID = gibbonCourse.gibbonSchoolYearID
                 AND gibbonSchoolYear.status = 'Current'
             WHERE gibbonPerson.status = 'Full'
             ORDER BY docente"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Base del dashboard "Gráficas de familias": una fila por vínculo estudiante-acudiente del año
     * escolar (años Past y Current; en el actual solo estudiantes 'Full'). Los estudiantes sin
     * acudiente salen con los campos del acudiente vacíos (LEFT JOIN).
     * Devuelve IDs reales (idEstudiante, idFamilia, idAcudiente) SOLO para que la página los
     * convierta en claves anónimas secuenciales; nunca deben enviarse al navegador. No devuelve
     * nombres, correos, teléfonos ni direcciones: solo banderas 0/1 de si el dato existe.
     * - hijos: estudiantes de esa familia matriculados en ese año escolar.
     * - religion: la del estudiante (gibbonPerson.religion).
     * - parentesco: gibbonFamilyRelationship.relationship (persona 1 = acudiente, persona 2 = estudiante).
     */
    public function queryGraficasFamilias()
    {
        return $this->db()->select(
            "SELECT gibbonSchoolYear.name AS anio, gibbonSchoolYear.status AS estadoAnio,
                    gibbonYearGroup.name AS grado, gibbonYearGroup.sequenceNumber AS ordenGrado,
                    CASE student.gender WHEN 'F' THEN 'Niñas' WHEN 'M' THEN 'Niños' ELSE 'Sin dato' END AS genero,
                    COALESCE(NULLIF(TRIM(student.religion), ''), 'Sin dato') AS religion,
                    student.gibbonPersonID AS idEstudiante,
                    gibbonFamilyChild.gibbonFamilyID AS idFamilia,
                    adult.gibbonPersonID AS idAcudiente,
                    (SELECT COUNT(DISTINCT hermano.gibbonPersonID)
                     FROM gibbonFamilyChild AS hijo
                     INNER JOIN gibbonStudentEnrolment AS hermano ON hermano.gibbonPersonID = hijo.gibbonPersonID
                         AND hermano.gibbonSchoolYearID = gibbonStudentEnrolment.gibbonSchoolYearID
                     INNER JOIN gibbonPerson AS personaHermano ON personaHermano.gibbonPersonID = hermano.gibbonPersonID
                     WHERE hijo.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID
                     AND (gibbonSchoolYear.status = 'Past' OR personaHermano.status = 'Full')) AS hijos,
                    COALESCE(NULLIF(TRIM(gibbonFamilyRelationship.relationship), ''), 'Sin dato') AS parentesco,
                    COALESCE(NULLIF(UPPER(TRIM(adult.profession)), ''), 'SIN DATO') AS profesion,
                    CASE WHEN adult.gibbonPersonID IS NULL THEN NULL
                         WHEN TRIM(COALESCE(adult.email, '')) <> '' OR TRIM(COALESCE(adult.emailAlternate, '')) <> '' THEN 1 ELSE 0 END AS tieneCorreo,
                    CASE WHEN adult.gibbonPersonID IS NULL THEN NULL
                         WHEN TRIM(COALESCE(adult.phone1, '')) <> '' THEN 1 ELSE 0 END AS tieneTelefono,
                    CASE WHEN adult.gibbonPersonID IS NULL THEN NULL
                         WHEN TRIM(COALESCE(adult.address1, '')) <> '' THEN 1 ELSE 0 END AS tieneDireccion
             FROM gibbonStudentEnrolment
             INNER JOIN gibbonPerson AS student ON student.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
             INNER JOIN gibbonYearGroup ON gibbonYearGroup.gibbonYearGroupID = gibbonStudentEnrolment.gibbonYearGroupID
             INNER JOIN gibbonSchoolYear ON gibbonSchoolYear.gibbonSchoolYearID = gibbonStudentEnrolment.gibbonSchoolYearID
             LEFT JOIN gibbonFamilyChild ON gibbonFamilyChild.gibbonPersonID = student.gibbonPersonID
             LEFT JOIN gibbonFamilyAdult ON gibbonFamilyAdult.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID
             LEFT JOIN gibbonPerson AS adult ON adult.gibbonPersonID = gibbonFamilyAdult.gibbonPersonID AND adult.status = 'Full'
             LEFT JOIN gibbonFamilyRelationship ON gibbonFamilyRelationship.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID
                 AND gibbonFamilyRelationship.gibbonPersonID1 = adult.gibbonPersonID
                 AND gibbonFamilyRelationship.gibbonPersonID2 = student.gibbonPersonID
             WHERE gibbonSchoolYear.status IN ('Past', 'Current')
             AND (gibbonSchoolYear.status = 'Past' OR student.status = 'Full')
             ORDER BY gibbonSchoolYear.sequenceNumber, gibbonYearGroup.sequenceNumber"
        )->fetchAll();
    }
}
