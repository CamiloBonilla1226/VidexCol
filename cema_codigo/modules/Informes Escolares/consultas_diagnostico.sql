-- Consultas de diagnóstico del módulo Informes Escolares (solo lectura, seguras de correr).
-- Reemplaza los valores entre <...> por los reales.

-- ============ 1. ASISTENCIA (reportAttendances.php) ============
-- 1.1 ¿Hay asistencia por grupo registrada? (tabla que lee el reporte)
SELECT COUNT(*) AS total, MIN(date) AS primera, MAX(date) AS ultima FROM gibbonAttendanceLogFormGroup;

-- 1.2 Asistencia por fecha (últimos 30 registros)
SELECT l.date, fg.nameShort AS grupo, CONCAT(p.surname,' ',p.preferredName) AS quien_la_tomo, l.timestampTaken
FROM gibbonAttendanceLogFormGroup l
JOIN gibbonFormGroup fg ON fg.gibbonFormGroupID = l.gibbonFormGroupID
JOIN gibbonPerson p ON p.gibbonPersonID = l.gibbonPersonIDTaker
ORDER BY l.date DESC LIMIT 30;

-- 1.3 Cómo se llaman los grupos y quién es su director (el reporte agrupa por el número con que empieza nameShort)
SELECT fg.gibbonFormGroupID, fg.nameShort, fg.name, sy.name AS anio, sy.status,
       t1.surname AS tutor1, t2.surname AS tutor2, t3.surname AS tutor3
FROM gibbonFormGroup fg
JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = fg.gibbonSchoolYearID
LEFT JOIN gibbonPerson t1 ON t1.gibbonPersonID = fg.gibbonPersonIDTutor
LEFT JOIN gibbonPerson t2 ON t2.gibbonPersonID = fg.gibbonPersonIDTutor2
LEFT JOIN gibbonPerson t3 ON t3.gibbonPersonID = fg.gibbonPersonIDTutor3
ORDER BY sy.sequenceNumber DESC, fg.nameShort;

-- 1.4 Misma lógica del reporte para un rango (cambiar fechas)
SELECT p.surname, p.preferredName, fg.nameShort AS grupo, COUNT(l.gibbonAttendanceLogFormGroupID) AS veces
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
JOIN gibbonFormGroup fg ON p.gibbonPersonID IN (fg.gibbonPersonIDTutor, fg.gibbonPersonIDTutor2, fg.gibbonPersonIDTutor3)
LEFT JOIN gibbonAttendanceLogFormGroup l ON l.gibbonFormGroupID = fg.gibbonFormGroupID AND l.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID, fg.gibbonFormGroupID
ORDER BY p.surname;

-- ============ 2. INGRESO DE NOTAS (reportEntryNotes.php) ============
-- 2.1 Períodos del año actual (los del desplegable)
SELECT t.gibbonSchoolYearTermID, t.name, t.firstDay, t.lastDay, sy.name AS anio
FROM gibbonSchoolYearTerm t JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = t.gibbonSchoolYearID
WHERE sy.status = 'Current';

-- 2.2 Columnas del markbook por período (si es 0 en un período, todos salen "Pendiente")
SELECT t.name AS periodo, COUNT(c.gibbonMarkbookColumnID) AS columnas
FROM gibbonSchoolYearTerm t
LEFT JOIN gibbonMarkbookColumn c ON c.gibbonSchoolYearTermID = t.gibbonSchoolYearTermID
GROUP BY t.gibbonSchoolYearTermID;

-- 2.3 Mismo cálculo del reporte para un período (<ID_PERIODO>)
SELECT p.surname, p.preferredName, CONCAT(co.nameShort,'.',cl.nameShort) AS clase,
  (SELECT COUNT(*) FROM gibbonMarkbookColumn mc WHERE mc.gibbonCourseClassID = cl.gibbonCourseClassID AND mc.gibbonSchoolYearTermID = <ID_PERIODO>) AS columnas,
  (SELECT COUNT(*) FROM gibbonMarkbookColumn mc
     JOIN gibbonCourseClassPerson st ON st.gibbonCourseClassID = mc.gibbonCourseClassID AND st.role = 'Student'
     LEFT JOIN gibbonMarkbookEntry me ON me.gibbonMarkbookColumnID = mc.gibbonMarkbookColumnID AND me.gibbonPersonIDStudent = st.gibbonPersonID
     WHERE mc.gibbonCourseClassID = cl.gibbonCourseClassID AND mc.gibbonSchoolYearTermID = <ID_PERIODO>
       AND (me.attainmentValue IS NULL OR me.attainmentValue = '')) AS sin_nota
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
JOIN gibbonCourseClassPerson ccp ON ccp.gibbonPersonID = p.gibbonPersonID AND ccp.role = 'Teacher'
JOIN gibbonCourseClass cl ON cl.gibbonCourseClassID = ccp.gibbonCourseClassID
JOIN gibbonCourse co ON co.gibbonCourseID = cl.gibbonCourseID
WHERE co.gibbonSchoolYearID = (SELECT gibbonSchoolYearID FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID = <ID_PERIODO>)
ORDER BY p.surname, clase;

-- ============ 3. NOTAS POR CURSO (reportGradesByCourse.php) ============
-- 3.1 Cursos del año actual (el reporte busca claves como "Mat 5", "5 PILEO": deben coincidir EXACTO con nameShort)
SELECT co.gibbonCourseID, co.nameShort, co.name FROM gibbonCourse co
JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = co.gibbonSchoolYearID AND sy.status = 'Current'
ORDER BY co.nameShort;

-- 3.2 Notas de un grupo (<ID_GRUPO>) en un rango: promedio por estudiante y curso
SELECT p.surname, p.preferredName, co.nameShort AS curso,
       ROUND(AVG(CAST(REPLACE(me.attainmentValue, ',', '.') AS DECIMAL(6,2))), 2) AS nota
FROM gibbonStudentEnrolment se
JOIN gibbonPerson p ON p.gibbonPersonID = se.gibbonPersonID
JOIN gibbonMarkbookEntry me ON me.gibbonPersonIDStudent = se.gibbonPersonID
JOIN gibbonMarkbookColumn mc ON mc.gibbonMarkbookColumnID = me.gibbonMarkbookColumnID
JOIN gibbonCourseClass cl ON cl.gibbonCourseClassID = mc.gibbonCourseClassID
JOIN gibbonCourse co ON co.gibbonCourseID = cl.gibbonCourseID AND co.gibbonSchoolYearID = se.gibbonSchoolYearID
WHERE se.gibbonFormGroupID = <ID_GRUPO>
  AND mc.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
  AND me.attainmentValue REGEXP '^[0-9]+([.,][0-9]+)?$'
GROUP BY p.gibbonPersonID, co.nameShort
ORDER BY p.surname, co.nameShort;

-- 3.3 ¿Las notas están en attainmentValue o en effortValue? (si attainment sale vacío, el reporte saldrá vacío)
SELECT COUNT(*) AS total, SUM(attainmentValue IS NOT NULL AND attainmentValue <> '') AS con_attainment,
       SUM(effortValue IS NOT NULL AND effortValue <> '') AS con_effort
FROM gibbonMarkbookEntry;

-- ============ 4. OBSERVACIONES Y PLANEACIÓN ============
-- 4.1 Observaciones por profesor (reportStudentObservations.php)
SELECT p.surname, p.preferredName, COUNT(o.id) AS observaciones
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
LEFT JOIN obsObservaciones o ON o.gibbonPersonID_create = p.gibbonPersonID AND o.fechaIncidente BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID ORDER BY observaciones DESC;

-- 4.2 Planeaciones por profesor (reportPlanning.php)
SELECT p.surname, p.preferredName, COUNT(DISTINCT pe.gibbonPlannerEntryID) AS planeaciones
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
LEFT JOIN gibbonCourseClassPerson ccp ON ccp.gibbonPersonID = p.gibbonPersonID AND ccp.role = 'Teacher'
LEFT JOIN gibbonPlannerEntry pe ON pe.gibbonCourseClassID = ccp.gibbonCourseClassID AND pe.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID ORDER BY planeaciones DESC;

-- ============ 5. EXPLORAR LA BASE DE DATOS ============
-- 5.1 Tablas más grandes (filas aprox.)
SELECT table_name, table_rows FROM information_schema.tables
WHERE table_schema = DATABASE() ORDER BY table_rows DESC LIMIT 30;

-- 5.2 Tablas por prefijo (gibbon* = núcleo; obs* = Observador; etc.)
SELECT table_name FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name NOT LIKE 'gibbon%' ORDER BY table_name;

-- 5.3 Columnas de una tabla (cambiar nombre)
SELECT column_name, column_type, is_nullable, column_key FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'gibbonMarkbookColumn' ORDER BY ordinal_position;

-- 5.4 Qué tablas referencian a gibbonPerson (cómo se conectan las personas)
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name LIKE '%gibbonPersonID%' ORDER BY table_name;

-- 5.5 Dónde aparece una columna en cualquier tabla (ej. gibbonFormGroupID)
SELECT table_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'gibbonFormGroupID' ORDER BY table_name;

-- 5.6 Llaves foráneas reales (si la BD las define)
SELECT table_name, column_name, referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE() AND referenced_table_name IS NOT NULL ORDER BY table_name;

-- 5.7 Cadena principal: estudiante -> año -> grupo -> año escolar
SELECT p.surname, p.preferredName, sy.name AS anio, yg.name AS grado, fg.nameShort AS grupo
FROM gibbonStudentEnrolment se
JOIN gibbonPerson p ON p.gibbonPersonID = se.gibbonPersonID
JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = se.gibbonSchoolYearID
JOIN gibbonYearGroup yg ON yg.gibbonYearGroupID = se.gibbonYearGroupID
JOIN gibbonFormGroup fg ON fg.gibbonFormGroupID = se.gibbonFormGroupID
WHERE sy.status = 'Current' LIMIT 20;

-- 5.8 Cadena académica: profesor -> clase -> curso -> columna de notas -> nota del estudiante
SELECT t.surname AS profesor, co.nameShort AS curso, cl.nameShort AS clase, mc.name AS columna,
       s.surname AS estudiante, me.attainmentValue AS nota
FROM gibbonCourseClassPerson ccp
JOIN gibbonPerson t ON t.gibbonPersonID = ccp.gibbonPersonID AND ccp.role = 'Teacher'
JOIN gibbonCourseClass cl ON cl.gibbonCourseClassID = ccp.gibbonCourseClassID
JOIN gibbonCourse co ON co.gibbonCourseID = cl.gibbonCourseID
JOIN gibbonMarkbookColumn mc ON mc.gibbonCourseClassID = cl.gibbonCourseClassID
JOIN gibbonMarkbookEntry me ON me.gibbonMarkbookColumnID = mc.gibbonMarkbookColumnID
JOIN gibbonPerson s ON s.gibbonPersonID = me.gibbonPersonIDStudent
LIMIT 20;

-- 5.9 Conteos rápidos de salud de datos
SELECT 'personas' AS tabla, COUNT(*) AS n FROM gibbonPerson
UNION ALL SELECT 'staff', COUNT(*) FROM gibbonStaff
UNION ALL SELECT 'estudiantes matriculados (año actual)', COUNT(*) FROM gibbonStudentEnrolment se JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = se.gibbonSchoolYearID AND sy.status = 'Current'
UNION ALL SELECT 'grupos', COUNT(*) FROM gibbonFormGroup
UNION ALL SELECT 'cursos', COUNT(*) FROM gibbonCourse
UNION ALL SELECT 'clases', COUNT(*) FROM gibbonCourseClass
UNION ALL SELECT 'columnas de notas', COUNT(*) FROM gibbonMarkbookColumn
UNION ALL SELECT 'notas', COUNT(*) FROM gibbonMarkbookEntry
UNION ALL SELECT 'planeaciones', COUNT(*) FROM gibbonPlannerEntry
UNION ALL SELECT 'observaciones', COUNT(*) FROM obsObservaciones;
