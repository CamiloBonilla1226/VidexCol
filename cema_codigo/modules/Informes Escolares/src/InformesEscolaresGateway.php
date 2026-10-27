<?php
/*
 * Gateway propio del módulo "Informes Escolares".
 *
 * Reconstruye 3 métodos (queryAttendanceTakeClass, queryStudentObservations,
 * queryPlanning) que antes vivían directamente en el StaffGateway del core
 * de Gibbon y fueron eliminados en una versión anterior a v30 (ya no existían
 * ni en la copia de producción actual antes de esta actualización). No se
 * encontró la implementación original en ningún respaldo disponible, así que
 * esta es una reconstrucción de mejor esfuerzo basada en el esquema de datos
 * y en las columnas que el propio reporte espera mostrar — VALIDAR los
 * resultados contra lo que el colegio espera ver antes de confiar en ellos
 * para decisiones de cumplimiento.
 *
 * Vive dentro del módulo (no en src/ del core) a propósito: así sobrevive a
 * futuras actualizaciones del núcleo de Gibbon.
 */

namespace Modules\InformesEscolares;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class InformesEscolaresGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaff';
    private static $primaryKey = 'gibbonStaffID';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];

    /**
     * Asistencia tomada por director(a) de grupo, pivotada por grado (1ro..11vo).
     * Supuesto: cada columna de grado = cuántas veces ese profesor(a), como
     * tutor de un form group de ese grado, registró asistencia en el rango de fechas.
     */
    public function queryAttendanceTakeClass(QueryCriteria $criteria, $fechaInicial, $fechaFinal)
    {
        // Columna => número de grado. El grupo cuenta para una columna si su nameShort
        // empieza por ese número (ej. "1", "1A", "1ro", "10-2"), no solo si es igual a "1ro".
        $gradeColumns = ['1ro' => 1, '2do' => 2, '3ro' => 3, '4to' => 4, '5to' => 5, '6to' => 6, '7mo' => 7, '8vo' => 8, '9no' => 9, '10mo' => 10, '11vo' => 11];

        $query = $this
            ->newQuery()
            ->from('gibbonStaff')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.image_240', 'gibbonPerson.username',
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->leftJoin('gibbonFormGroup', '(gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)')
            ->leftJoin('gibbonAttendanceLogFormGroup', 'gibbonAttendanceLogFormGroup.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND gibbonAttendanceLogFormGroup.date BETWEEN :fechaInicial AND :fechaFinal')
            ->where('gibbonPerson.status = "Full"')
            ->bindValue('fechaInicial', $fechaInicial)
            ->bindValue('fechaFinal', $fechaFinal)
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        foreach ($gradeColumns as $grade => $number) {
            $number = (int) $number;
            $query->cols(["SUM(CASE WHEN gibbonFormGroup.nameShort REGEXP '^ *0*{$number}([^0-9]|$)' AND gibbonAttendanceLogFormGroup.gibbonAttendanceLogFormGroupID IS NOT NULL THEN 1 ELSE 0 END) AS `{$grade}`"]);
        }

        return $this->runQuery($query, $criteria);
    }

    /**
     * Número de observaciones (obsObservaciones) creadas por cada profesor(a)
     * en el rango de fechas, usando gibbonPersonID_create como autor.
     */
    public function queryStudentObservations(QueryCriteria $criteria, $fechaInicial, $fechaFinal)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonStaff')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.image_240', 'gibbonPerson.username',
                'COUNT(obsObservaciones.id) AS numero_obs',
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->leftJoin('obsObservaciones', 'obsObservaciones.gibbonPersonID_create=gibbonPerson.gibbonPersonID AND obsObservaciones.fechaIncidente BETWEEN :fechaInicial AND :fechaFinal')
            ->where('gibbonPerson.status = "Full"')
            ->bindValue('fechaInicial', $fechaInicial)
            ->bindValue('fechaFinal', $fechaFinal)
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Número de entradas del planeador de lecciones (gibbonPlannerEntry) por
     * profesor(a) en el rango de fechas, vía las clases que dicta.
     */
    public function queryPlanning(QueryCriteria $criteria, $fechaInicial, $fechaFinal)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonStaff')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.image_240', 'gibbonPerson.username',
                'COUNT(DISTINCT gibbonPlannerEntry.gibbonPlannerEntryID) AS numero_planning',
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.role="Teacher"')
            ->leftJoin('gibbonPlannerEntry', 'gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonPlannerEntry.date BETWEEN :fechaInicial AND :fechaFinal')
            ->where('gibbonPerson.status = "Full"')
            ->bindValue('fechaInicial', $fechaInicial)
            ->bindValue('fechaFinal', $fechaFinal)
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Ingreso de notas por profesor(a) y clase en un período (reportEntryNotes.php).
     * Una fila por profesor(a) + clase del año del período. Campos propios:
     *  - columnas: columnas del markbook creadas para esa clase en el período.
     *  - sinNota:  casillas sin nota (estudiante x columna) de esas columnas.
     * Antes vivía en StaffGateway del core (ya no existe).
     */
    public function queryReportEntryNotesData(QueryCriteria $criteria, $gibbonSchoolYearTermID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonStaff')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.image_240', 'gibbonPerson.username',
                'gibbonCourseClass.gibbonCourseClassID',
                "CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS nameShort",
                '(SELECT COUNT(*) FROM gibbonMarkbookColumn mc
                    WHERE mc.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
                    AND mc.gibbonSchoolYearTermID = :termColumnas) AS columnas',
                "(SELECT COUNT(*) FROM gibbonMarkbookColumn mc
                    INNER JOIN gibbonCourseClassPerson st ON st.gibbonCourseClassID = mc.gibbonCourseClassID AND st.role = 'Student'
                    LEFT JOIN gibbonMarkbookEntry me ON me.gibbonMarkbookColumnID = mc.gibbonMarkbookColumnID AND me.gibbonPersonIDStudent = st.gibbonPersonID
                    WHERE mc.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
                    AND mc.gibbonSchoolYearTermID = :termSinNota
                    AND (me.attainmentValue IS NULL OR me.attainmentValue = '')) AS sinNota",
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->innerJoin('gibbonCourseClassPerson', "gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.role='Teacher'")
            ->innerJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID')
            ->where('gibbonPerson.status = "Full"')
            ->where('gibbonCourse.gibbonSchoolYearID = (SELECT gibbonSchoolYearID FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID = :termAnio)')
            ->bindValue('termColumnas', $gibbonSchoolYearTermID)
            ->bindValue('termSinNota', $gibbonSchoolYearTermID)
            ->bindValue('termAnio', $gibbonSchoolYearTermID)
            ->groupBy(['gibbonPerson.gibbonPersonID', 'gibbonCourseClass.gibbonCourseClassID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Notas de los estudiantes de un grupo: una fila por estudiante con una clave
     * por curso (nameShort del curso, ej. "Mat 5") con el promedio de sus notas
     * (gibbonMarkbookEntry.attainmentValue) en columnas del markbook cuya fecha
     * esté en el rango. Devuelve un arreglo (reportGradesByCourse.php).
     * Antes vivía en StudentGateway del core (ya no existe).
     */
    public function queryGradesByCourse($gibbonFormGroupID, $fechaInicial, $fechaFinal)
    {
        $alumnos = $this->db()->select(
            "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240,
                    gibbonPerson.status, gibbonPerson.dateStart, gibbonPerson.dateEnd, gibbonFormGroup.nameShort AS grupo
             FROM gibbonStudentEnrolment
             INNER JOIN gibbonPerson ON gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
             INNER JOIN gibbonFormGroup ON gibbonFormGroup.gibbonFormGroupID = gibbonStudentEnrolment.gibbonFormGroupID
             WHERE gibbonStudentEnrolment.gibbonFormGroupID = :gibbonFormGroupID
             AND gibbonPerson.status = 'Full'
             ORDER BY gibbonPerson.surname, gibbonPerson.preferredName",
            ['gibbonFormGroupID' => $gibbonFormGroupID]
        )->fetchAll();

        $promedios = $this->db()->select(
            "SELECT gibbonMarkbookEntry.gibbonPersonIDStudent AS gibbonPersonID, gibbonCourse.nameShort AS curso,
                    ROUND(AVG(CAST(REPLACE(gibbonMarkbookEntry.attainmentValue, ',', '.') AS DECIMAL(6,2))), 2) AS nota
             FROM gibbonStudentEnrolment
             INNER JOIN gibbonMarkbookEntry ON gibbonMarkbookEntry.gibbonPersonIDStudent = gibbonStudentEnrolment.gibbonPersonID
             INNER JOIN gibbonMarkbookColumn ON gibbonMarkbookColumn.gibbonMarkbookColumnID = gibbonMarkbookEntry.gibbonMarkbookColumnID
             INNER JOIN gibbonCourseClass ON gibbonCourseClass.gibbonCourseClassID = gibbonMarkbookColumn.gibbonCourseClassID
             INNER JOIN gibbonCourse ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                 AND gibbonCourse.gibbonSchoolYearID = gibbonStudentEnrolment.gibbonSchoolYearID
             WHERE gibbonStudentEnrolment.gibbonFormGroupID = :gibbonFormGroupID
             AND gibbonMarkbookColumn.date BETWEEN :fechaInicial AND :fechaFinal
             AND gibbonMarkbookEntry.attainmentValue REGEXP '^[0-9]+([.,][0-9]+)?$'
             GROUP BY gibbonMarkbookEntry.gibbonPersonIDStudent, gibbonCourse.nameShort",
            ['gibbonFormGroupID' => $gibbonFormGroupID, 'fechaInicial' => $fechaInicial, 'fechaFinal' => $fechaFinal]
        )->fetchAll();

        $notas = [];
        foreach ($promedios as $fila) {
            $notas[$fila['gibbonPersonID']][$fila['curso']] = $fila['nota'];
        }

        // Todos los cursos del año del grupo arrancan vacíos, así el reporte nunca
        // encuentra una clave inexistente cuando un estudiante no tiene nota.
        $cursos = $this->db()->select(
            "SELECT DISTINCT gibbonCourse.nameShort
             FROM gibbonCourse
             INNER JOIN gibbonFormGroup ON gibbonFormGroup.gibbonSchoolYearID = gibbonCourse.gibbonSchoolYearID
             WHERE gibbonFormGroup.gibbonFormGroupID = :gibbonFormGroupID",
            ['gibbonFormGroupID' => $gibbonFormGroupID]
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($alumnos as &$alumno) {
            foreach ($cursos as $curso) {
                $alumno[$curso] = '';
            }
            $alumno['roleCategory'] = 'Student';
            $alumno['yearGroup'] = $gibbonFormGroupID;
            foreach ($notas[$alumno['gibbonPersonID']] ?? [] as $curso => $nota) {
                $alumno[$curso] = $nota;
            }
        }
        unset($alumno);

        return $alumnos;
    }

    /**
     * Estudiantes de un grupo (form group) con datos de contacto de la familia.
     * Usado por reportStudents.php. Los correos del papá/mamá se toman de los
     * adultos de la familia del estudiante según su género (M = papá, F = mamá),
     * priorizando contactPriority. Se usan subconsultas para no duplicar filas.
     */
    public function queryStudentsData(QueryCriteria $criteria, $gibbonFormGroupID)
    {
        $adultEmail = function ($gender) {
            return "(SELECT adult.email FROM gibbonFamilyChild
                INNER JOIN gibbonFamilyAdult ON gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID
                INNER JOIN gibbonPerson AS adult ON adult.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID
                WHERE gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID
                AND adult.gender='{$gender}' AND adult.email IS NOT NULL AND adult.email<>''
                ORDER BY gibbonFamilyAdult.contactPriority ASC LIMIT 1)";
        };

        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.image_240', 'gibbonPerson.username', 'gibbonPerson.canLogin',
                'gibbonPerson.phone1CountryCode', 'gibbonPerson.phone1', 'gibbonPerson.studentID',
                'gibbonPerson.status', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd',
                $adultEmail('M').' AS email_father',
                $adultEmail('F').' AS email_mother',
                "(SELECT gibbonFamily.homeAddress FROM gibbonFamilyChild
                    INNER JOIN gibbonFamily ON gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID
                    WHERE gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID
                    ORDER BY gibbonFamily.gibbonFamilyID ASC LIMIT 1) AS homeAddress",
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
            ->bindValue('gibbonFormGroupID', $gibbonFormGroupID)
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Estudiantes activos de un grupo, con datos médicos, religión y contactos de emergencia.
     * Reemplaza queryStudentsData_3(), que ya no existe en el StudentGateway del núcleo.
     * Se trae gibbonPersonMedical.* (así, si el colegio tiene columnas propias como
     * vacunas10Years, aparecen sin tener que nombrarlas) y luego las columnas de gibbonPerson,
     * que en caso de repetirse (gibbonPersonID) prevalecen.
     */
    public function queryStudentsData_3(QueryCriteria $criteria, $gibbonFormGroupID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPersonMedical.*',
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName',
                'gibbonPerson.officialName', 'gibbonPerson.image_240', 'gibbonPerson.username',
                'gibbonPerson.status', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.religion',
                'gibbonPerson.emergency1Name', 'gibbonPerson.emergency1Number1', 'gibbonPerson.emergency1Number2', 'gibbonPerson.emergency1Relationship',
                'gibbonPerson.emergency2Name', 'gibbonPerson.emergency2Number1', 'gibbonPerson.emergency2Number2', 'gibbonPerson.emergency2Relationship',
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPersonMedical', 'gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
            ->bindValue('gibbonFormGroupID', $gibbonFormGroupID)
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'));

        return $this->runQuery($query, $criteria);
    }

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
}
