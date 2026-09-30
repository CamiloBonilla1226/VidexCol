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
        $gradeColumns = ['1ro', '2do', '3ro', '4to', '5to', '6to', '7mo', '8vo', '9no', '10mo', '11vo'];

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

        foreach ($gradeColumns as $grade) {
            $query->cols(["SUM(CASE WHEN gibbonFormGroup.nameShort = '{$grade}' AND gibbonAttendanceLogFormGroup.gibbonAttendanceLogFormGroupID IS NOT NULL THEN 1 ELSE 0 END) AS `{$grade}`"]);
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
}
