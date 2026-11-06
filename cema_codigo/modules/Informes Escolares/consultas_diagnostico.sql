-- Consultas de diagnóstico del módulo Informes Escolares (solo lectura, seguras de correr).
-- Reemplaza los valores entre <...> por los reales.

-- ============ 1. ASISTENCIA (reportAttendances.php) ============
-- 1.1 ¿Hay asistencia por grupo registrada? (tabla que lee el reporte)
SELECT COUNT(*) AS total, MIN(date) AS primera, MAX(date) AS ultima FROM gibbonAttendanceLogFormGroup;
rta 

total
primera
ultima
622
2020-01-31
2026-02-24

-- 1.2 Asistencia por fecha (últimos 30 registros)
SELECT l.date, fg.nameShort AS grupo, CONCAT(p.surname,' ',p.preferredName) AS quien_la_tomo, l.timestampTaken
FROM gibbonAttendanceLogFormGroup l
JOIN gibbonFormGroup fg ON fg.gibbonFormGroupID = l.gibbonFormGroupID
JOIN gibbonPerson p ON p.gibbonPersonID = l.gibbonPersonIDTaker
ORDER BY l.date DESC LIMIT 30;


date Descendente 1
grupo
quien_la_tomo
timestampTaken
2026-02-24
 8°
León Alexander
2026-02-24 07:02:21
2026-02-12
 8°
León Alexander
2026-02-12 06:57:43
2026-02-10
 1°
Cuervo Diana Nataly
2026-02-10 07:00:26
2026-02-10
 2°
González Daniela
2026-02-10 07:02:01
2026-02-10
 4°
León Alexander
2026-02-10 07:05:18
2026-02-10
 9°
Romero Rincon Edgar
2026-02-10 07:12:13
2026-02-10
 5°
Romero Rincon Edgar
2026-02-10 07:52:22
2026-02-10
 7°
López Diego Alejandro
2026-02-10 07:57:17
2026-02-10
 11°
Romero Rincon Edgar
2026-02-10 08:54:35
2026-02-10
 6°
Romero Rincon Edgar
2026-02-10 10:24:50
2026-02-09
10°
Cuervo Diana Nataly
2026-02-09 06:54:55
2026-02-09
 7°
Cruz Guerrero Ilany Kiara
2026-02-09 06:58:25
2026-02-09
 4°
Cuervo Diana Nataly
2026-02-09 08:01:31
2026-02-09
 8°
Cuervo Diana Nataly
2026-02-09 08:54:02
2024-11-19
 10mo
Súarez García Sergio Stiven
2024-11-19 06:58:50
2024-10-03
 6to
Sanabria Gutierrez Angel
2024-10-03 06:50:34
2024-10-01
 6to
Sanabria Gutierrez Angel
2024-10-01 08:31:42
2024-09-25
 6to
Sanabria Gutierrez Angel
2024-09-25 06:50:29
2024-09-03
 10mo
Súarez García Sergio Stiven
2024-09-03 07:00:40
2024-08-23
 10mo
Súarez García Sergio Stiven
2024-08-23 10:02:30
2024-08-21
 1ro
Cuervo Jenny Patricia
2024-08-21 19:40:13
2024-08-13
 10mo
Súarez García Sergio Stiven
2024-08-13 08:03:29
2024-07-30
 10mo
Súarez García Sergio Stiven
2024-07-30 07:58:31
2024-06-14
 1ro
Cuervo Jenny Patricia
2024-06-15 19:02:08
2024-05-20
 1ro
Cuervo Jenny Patricia
2024-05-20 16:06:37
2024-03-07
 1ro
Cuervo Jenny Patricia
2024-03-07 13:26:42
2023-10-27
 3ro
Amaya Saenz Angie Daniela
2023-10-27 08:26:15
2023-10-17
 9no
Moreno Claudia Patricia
2023-10-17 13:31:27
2023-10-17
 1ro
Cuervo Jenny Patricia
2023-10-17 17:33:42
2023-10-06
 1ro
Cuervo Jenny Patricia
2023-10-17 17:34:26


-- 1.3 Cómo se llaman los grupos y quién es su director (el reporte agrupa por el número con que empieza nameShort)
SELECT fg.gibbonFormGroupID, fg.nameShort, fg.name, sy.name AS anio, sy.status,
       t1.surname AS tutor1, t2.surname AS tutor2, t3.surname AS tutor3
FROM gibbonFormGroup fg
JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = fg.gibbonSchoolYearID
LEFT JOIN gibbonPerson t1 ON t1.gibbonPersonID = fg.gibbonPersonIDTutor
LEFT JOIN gibbonPerson t2 ON t2.gibbonPersonID = fg.gibbonPersonIDTutor2
LEFT JOIN gibbonPerson t3 ON t3.gibbonPersonID = fg.gibbonPersonIDTutor3
ORDER BY sy.sequenceNumber DESC, fg.nameShort;

gibbonFormGroupID
nameShort
name
anio
status
tutor1
tutor2
tutor3
00118
 11°
11° Once
2026
Current
González
NULL
NULL
00109
 1°
 1° Primero
2026
Current
Cuervo
NULL
NULL
00110
 2°
 2° Segundo
2026
Current
González
NULL
NULL
00111
 3°
 3° Tercero
2026
Current
Cubides Moyano
NULL
NULL
00112
 4°
 4° Cuarto
2026
Current
Cuervo
NULL
NULL
00114
 5°
 5° Quinto
2026
Current
López
NULL
NULL
00113
 6°
 6° Sexto
2026
Current
Vargas
NULL
NULL
00115
 7°
 7° Séptimo
2026
Current
Cruz Guerrero
NULL
NULL
00116
 8°
 8° Octavo
2026
Current
León
NULL
NULL
00117
 9°
 9° Noveno
2026
Current
Pedraza
NULL
NULL
00119
10°
10° Décimo
2026
Current
Romero Rincon
NULL
NULL
00085
 11°
11° Once
2025
Past
López
NULL
NULL
00065
 1°
 1° Primero
2025
Past
Cuervo
NULL
NULL
00066
 2°
 2° Segundo
2025
Past
González
NULL
NULL
00067
 3°
 3° Tercero
2025
Past
Cubides Moyano
NULL
NULL
00068
 4°
 4° Cuarto
2025
Past
Domínguez Súarez
NULL
NULL
00080
 5°
 5° Quinto
2025
Past
Galindo Espitia
NULL
NULL
00070
 6°
 6° Sexto
2025
Past
Cruz Guerrero
NULL
NULL
00082
 7°
 7° Séptimo
2025
Past
Osita
NULL
NULL
00083
 8°
 8° Octavo
2025
Past
Arevalo Cáceres
NULL
NULL
00084
 9°
 9° Noveno
2025
Past
Romero Rincon
NULL
NULL
00086
10°
10° Décimo
2025
Past
Sanabria
NULL
NULL
00064
 10mo
 10 Décimo
2024
Past
Súarez García
NULL
NULL
00063
 11vo
 11 Once
2024
Past
López
NULL
NULL
00054
 1ro
 1 Primero
2024
Past
Cuervo
NULL
NULL


-- 1.4 Misma lógica del reporte para un rango (cambiar fechas)
SELECT p.surname, p.preferredName, fg.nameShort AS grupo, COUNT(l.gibbonAttendanceLogFormGroupID) AS veces
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
JOIN gibbonFormGroup fg ON p.gibbonPersonID IN (fg.gibbonPersonIDTutor, fg.gibbonPersonIDTutor2, fg.gibbonPersonIDTutor3)
LEFT JOIN gibbonAttendanceLogFormGroup l ON l.gibbonFormGroupID = fg.gibbonFormGroupID AND l.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID, fg.gibbonFormGroupID
ORDER BY p.surname;

surname Ascendente 1
preferredName
grupo
veces
Cruz Guerrero
Ilany Kiara
 7°
0
Cruz Guerrero
Ilany Kiara
 6°
0
Cubides Moyano
Verónica
 3°
0
Cubides Moyano
Verónica
 3°
0
Cuervo
Jenny Patricia
 1ro
0
Cuervo
Diana Nataly
 4°
0
Cuervo
Jenny Patricia
 1°
0
Cuervo
Jenny Patricia
3ro
0
Cuervo
Jenny Patricia
3ro
0
Cuervo
Jenny Patricia
 1ro
0
Cuervo
Jenny Patricia
 1°
0
Cuervo
Jenny Patricia
2do
0
González
Daniela
 2°
0
González
Daniela
1ro
0
González
Daniela
1ro
0
González
Daniela
 2do
0
González
Daniela
 2°
0
González
Daniela
1ro
0
González
Daniela
 2do
0
González
Luis Ernesto
 11°
0
Guerrero
Nathali
11vo
0
Guerrero
Nathali
 11vo
0
Guerrero
Nathali
7mo
0
Guerrero
Nathali
11vo
0
León
Alexander
 8°
0


-- ============ 2. INGRESO DE NOTAS (reportEntryNotes.php) ============
-- 2.1 Períodos del año actual (los del desplegable)
SELECT t.gibbonSchoolYearTermID, t.name, t.firstDay, t.lastDay, sy.name AS anio
FROM gibbonSchoolYearTerm t JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = t.gibbonSchoolYearID
WHERE sy.status = 'Current';

gibbonSchoolYearTermID
name
firstDay
lastDay
anio
00054
Período 1
2026-02-03
2026-04-11
2026
00055
Período 2
2026-04-12
2026-06-19
2026
00056
Período 3
2026-07-14
2026-09-15
2026
00057
Período 4
2026-09-16
2026-11-17
2026


-- 2.2 Columnas del markbook por período (si es 0 en un período, todos salen "Pendiente")
SELECT t.name AS periodo, COUNT(c.gibbonMarkbookColumnID) AS columnas
FROM gibbonSchoolYearTerm t
LEFT JOIN gibbonMarkbookColumn c ON c.gibbonSchoolYearTermID = t.gibbonSchoolYearTermID
GROUP BY t.gibbonSchoolYearTermID;

periodo
columnas
Term 1
0
Term 2
0
Term 3
0
Período 1
0
Período 2
0
Período 3
0
Período 4
0
Período 1
0
Período 2
0
Período 3
0
Período 4
0
Período 1
0
Período 2
0
Período 3
0
Período 4
0
Período 1
0
Período 2
0
Período 3
0
Período 4
0
Período 1
0
Período 2
0
Período 3
0
Período 4
0
Período 1
0
Período 2
0

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

gibbonCourseID Ascendente 1
nameShort
name
00001049
4°Art
4°Artes
00001050
4°ECOL
4°ECOLOGIA
00001051
4°Edu F
4°Educación Física
00001052
4°Esp.
4°Español
00001053
4°ETC.
4°Ética y Valores
00001054
4°Ge.E
4°Geoestadística
00001055
4°Infor
4°Informática
00001056
4°Ing
4°Inglés
00001057
4°Mat
4°Matemáticas
00001058
4°PRO
4°PROCUR
00001059
4°Rel
4°Religión
00001060
4°Soc
4°Sociales
00001061
10°Art
10°Artes
00001062
10°CESP
10°CESP
00001063
10°Edu F
10°Educación Física
00001064
10°Esp.
10°Español
00001065
10°ETC.
10°Ética y Valores
00001066
10°Filo
10°Filosofía
00001067
10°Ge.E
10°Geoestadística
00001068
10°Infor
10°Informática
00001069
10°Ing
10°Inglés
00001070
10°Mat
10°Matemáticas
00001071
10°PRO
10°PROCUR
00001072
10°Quim
10°Química
00001073
10°Rel
10°Religión
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

RTA 
--
--SELECT p.surname, p.preferredName, co.nameShort AS curso,
--       ROUND(AVG(CAST(REPLACE(me.attainmentValue, ',', '.') AS DECIMAL(6,2))), 2) AS nota
--FROM gibbonStudentEnrolment se
--JOIN gibbonPerson p ON p.gibbonPersonID = se.gibbonPersonID
--JOIN gibbonMarkbookEntry me ON me.gibbonPersonIDStudent = se.gibbonPersonID
--JOIN gibbonMarkbookColumn mc ON mc.gibbonMarkbookColumnID = me.gibbonMarkbookColumnID
--JOIN gibbonCourseClass cl ON cl.gibbonCourseClassID = mc.gibbonCourseClassID
--JOIN gibbonCourse co ON co.gibbonCourseID = cl.gibbonCourseID AND co.gibbonSchoolYearID = se.gibbonSchoolYearID
--WHERE se.gibbonFormGroupID = <ID_GRUPO>
--  AND mc.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
--  AND me.attainmentValue REGEXP '^[0-9]+([.,][0-9]+)?$'
--GROUP BY p.gibbonPersonID, co.nameShort
--ORDER BY p.surname, co.nameShort LIMIT 0, 25
--MySQL ha dicho: Documentación
--
--#1064 - Existe un error en su sintaxis SQL; revise el manual que se corresponde con su versión del servidor MariaDB para averiguar la sintaxis correcta a utilizar cerca de '<ID_GRUPO>
--  AND mc.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
--  AND me...' en la línea 9


-- 3.3 ¿Las notas están en attainmentValue o en effortValue? (si attainment sale vacío, el reporte saldrá vacío)
SELECT COUNT(*) AS total, SUM(attainmentValue IS NOT NULL AND attainmentValue <> '') AS con_attainment,
       SUM(effortValue IS NOT NULL AND effortValue <> '') AS con_effort
FROM gibbonMarkbookEntry;

total
con_attainment
con_effort
0
NULL
NULL

-- ============ 4. OBSERVACIONES Y PLANEACIÓN ============
-- 4.1 Observaciones por profesor (reportStudentObservations.php)
SELECT p.surname, p.preferredName, COUNT(o.id) AS observaciones
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
LEFT JOIN obsObservaciones o ON o.gibbonPersonID_create = p.gibbonPersonID AND o.fechaIncidente BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID ORDER BY observaciones DESC;


surname
preferredName
observaciones Descendente 1
Cuervo
Diana Nataly
0
Guerrero
Nathali
0
Castillo
Juan
0
González
Daniela
0
Cubides Moyano
Verónica
0
Camacho González
Felipe
0
Muñoz
Juan Carlos
0
Amaya Saenz
Angie Daniela
0
López
Diego Alejandro
0
Bonilla
Camilo
0
Vaca
Alba Luz
0
González
Luis Ernesto
0
Pineda
Jorge Enrique
0
León
Alexander
0
Muñoz
José Guillermo
0
Garrido
Alex
0
Cuervo
Jenny Patricia
0
González
MAFE
0
Obando
Manuel José
0
Cruz Guerrero
Ilany Kiara
0
Romero Rincon
Edgar
0
Piraquive
AIDEE
0
Vargas
David
0
Vargas
Kenberly
0
Pinzón
Luis
0


-- 4.2 Planeaciones por profesor (reportPlanning.php)
SELECT p.surname, p.preferredName, COUNT(DISTINCT pe.gibbonPlannerEntryID) AS planeaciones
FROM gibbonStaff s
JOIN gibbonPerson p ON p.gibbonPersonID = s.gibbonPersonID AND p.status = 'Full'
LEFT JOIN gibbonCourseClassPerson ccp ON ccp.gibbonPersonID = p.gibbonPersonID AND ccp.role = 'Teacher'
LEFT JOIN gibbonPlannerEntry pe ON pe.gibbonCourseClassID = ccp.gibbonCourseClassID AND pe.date BETWEEN '<2026-01-01>' AND '<2026-12-31>'
GROUP BY p.gibbonPersonID ORDER BY planeaciones DESC;


surname
preferredName
planeaciones Descendente 1
Cuervo
Diana Nataly
0
Guerrero
Nathali
0
Castillo
Juan
0
González
Daniela
0
Cubides Moyano
Verónica
0
Camacho González
Felipe
0
Muñoz
Juan Carlos
0
Amaya Saenz
Angie Daniela
0
López
Diego Alejandro
0
Bonilla
Camilo
0
Vaca
Alba Luz
0
González
Luis Ernesto
0
Pineda
Jorge Enrique
0
León
Alexander
0
Muñoz
José Guillermo
0
Garrido
Alex
0
Cuervo
Jenny Patricia
0
González
MAFE
0
Obando
Manuel José
0
Cruz Guerrero
Ilany Kiara
0
Romero Rincon
Edgar
0
Piraquive
AIDEE
0
Vargas
David
0
Vargas
Kenberly
0
Pinzón
Luis
0


-- ============ 5. EXPLORAR LA BASE DE DATOS ============
-- 5.1 Tablas más grandes (filas aprox.)
SELECT table_name, table_rows FROM information_schema.tables
WHERE table_schema = DATABASE() ORDER BY table_rows DESC LIMIT 30;


table_name
table_rows Descendente 1
SPATIAL_REF_SYS
NULL
THREAD_POOL_GROUPS
NULL
INNODB_TABLESPACES_ENCRYPTION
NULL
GLOBAL_STATUS
NULL
SESSION_VARIABLES
NULL
THREAD_POOL_QUEUES
NULL
INNODB_SYS_TABLES
NULL
COLLATION_CHARACTER_SET_APPLICABILITY
NULL
PROCESSLIST
NULL
USER_PRIVILEGES
NULL
INNODB_TRX
NULL
INNODB_SYS_FOREIGN_COLS
NULL
THREAD_POOL_STATS
NULL
KEYWORDS
NULL
SQL_FUNCTIONS
NULL
INNODB_SYS_FIELDS
NULL
INNODB_SYS_TABLESPACES
NULL
COLUMN_PRIVILEGES
NULL
REFERENTIAL_CONSTRAINTS
NULL
CLIENT_STATISTICS
NULL
INNODB_METRICS
NULL
INNODB_BUFFER_POOL_STATS
NULL
ALL_PLUGINS
NULL
KEY_COLUMN_USAGE
NULL
TABLES
NULL
INNODB_LOCKS
NULL
INNODB_BUFFER_PAGE
NULL
ENGINES
NULL
SCHEMATA
NULL
INNODB_FT_CONFIG
NULL


-- 5.2 Tablas por prefijo (gibbon* = núcleo; obs* = Observador; etc.)
SELECT table_name FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name NOT LIKE 'gibbon%' ORDER BY table_name;


table_name Ascendente 1
ALL_PLUGINS
APPLICABLE_ROLES
CHARACTER_SETS
CHECK_CONSTRAINTS
CLIENT_STATISTICS
COLLATIONS
COLLATION_CHARACTER_SET_APPLICABILITY
COLUMNS
COLUMN_PRIVILEGES
ENABLED_ROLES
ENGINES
EVENTS
FILES
GEOMETRY_COLUMNS
GLOBAL_STATUS
GLOBAL_VARIABLES
INDEX_STATISTICS
INNODB_BUFFER_PAGE
INNODB_BUFFER_PAGE_LRU
INNODB_BUFFER_POOL_STATS
INNODB_CMP
INNODB_CMPMEM
INNODB_CMPMEM_RESET
INNODB_CMP_PER_INDEX
INNODB_CMP_PER_INDEX_RESET

-- 5.3 Columnas de una tabla (cambiar nombre)
SELECT column_name, column_type, is_nullable, column_key FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'gibbonMarkbookColumn' ORDER BY ordinal_position;
column_name	column_type	is_nullable	column_key	

-- 5.4 Qué tablas referencian a gibbonPerson (cómo se conectan las personas)
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name LIKE '%gibbonPersonID%' ORDER BY table_name;
table_name	column_name	


-- 5.5 Dónde aparece una columna en cualquier tabla (ej. gibbonFormGroupID)
SELECT table_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'gibbonFormGroupID' ORDER BY table_name;
TABLE_NAME 

-- 5.6 Llaves foráneas reales (si la BD las define)
SELECT table_name, column_name, referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE() AND referenced_table_name IS NOT NULL ORDER BY table_name;

table_name	column_name	referenced_table_name	referenced_column_name	



-- 5.7 Cadena principal: estudiante -> año -> grupo -> año escolar
SELECT p.surname, p.preferredName, sy.name AS anio, yg.name AS grado, fg.nameShort AS grupo
FROM gibbonStudentEnrolment se
JOIN gibbonPerson p ON p.gibbonPersonID = se.gibbonPersonID
JOIN gibbonSchoolYear sy ON sy.gibbonSchoolYearID = se.gibbonSchoolYearID
JOIN gibbonYearGroup yg ON yg.gibbonYearGroupID = se.gibbonYearGroupID
JOIN gibbonFormGroup fg ON fg.gibbonFormGroupID = se.gibbonFormGroupID
WHERE sy.status = 'Current' LIMIT 20;


surname
preferredName
anio
grado
grupo
Cala
Mariana
2026
Cuarto
 4°
Cuesta
Joseph David
2026
Cuarto
 4°
González
María Fernanda
2026
Cuarto
 4°
González
Juan José
2026
Cuarto
 4°
Herrera
José Gabriel
2026
Cuarto
 4°
LOPEZ
ALEJANDRA
2026
Cuarto
 4°
Medina
Dylan Mathias
2026
Cuarto
 4°
Mestra
Ethan José
2026
Cuarto
 4°
Moreno
Mariana
2026
Cuarto
 4°
Moreno
Stephany
2026
Cuarto
 4°
Ovalle
Julián Mauricio
2026
Cuarto
 4°
Piedrahita
Juanita
2026
Cuarto
 4°
Walles
Fiorella
2026
Cuarto
 4°
Acosta
Juan Diego
2026
Cuarto
 4°
Naranjo
Emily Sofía
2026
Cuarto
 4°
Poveda
Luis  Alejandro
2026
Cuarto
 4°
Arenales
Jwsline  Alexandra
2026
Décimo
10°
Bareño
Santiago Alexander
2026
Décimo
10°
Barragán
Carol Natalia
2026
Décimo
10°
Beltran
Nicolas
2026
Décimo
10°


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

profesor	curso	clase	columna	estudiante	nota	



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


tabla
n
personas
2344
staff
90
estudiantes matriculados (año actual)
294
grupos
77
cursos
1191
clases
1256
columnas de notas
0
notas
0
planeaciones
68883
observaciones
22738
