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
}
