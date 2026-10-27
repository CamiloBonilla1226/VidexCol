# Registro de cambios

Cada entrada indica qué se cambió, por qué y **qué archivos hay que subir al servidor**.

---

## 2026-10-27 — Gráficas de familias (módulo Graficas)

**Qué se agregó:** dashboard "Gráficas de familias" en `modules/Graficas/familias.php` (la opción "Graficas familias" que ya estaba en el menú del módulo Graficas; no hizo falta script SQL ni tocar el manifest). Mismo estilo que estudiantes y docentes. Filtros: año escolar (por defecto el actual), grado, género del estudiante y religión. Cifras: estudiantes, familias y acudientes. El parentesco se muestra en español (Mamá, Papá, Abuela…; Gibbon lo guarda en inglés). 5 gráficas con pregunta, frase corta e ícono (i): hijos por familia, religión (dona), parentesco de los acudientes, ocupación de los acudientes (columnas verticales, Top 10 + "Otras") y datos de contacto por grado.

**Datos:** `queryGraficasFamilias()` en `modules/Graficas/src/GraficasGateway.php` (una fila por vínculo estudiante-acudiente del año; los estudiantes sin acudiente salen con LEFT JOIN). **Privacidad:** el servidor reemplaza los IDs por claves anónimas secuenciales (estudiante, familia, acudiente) y el navegador no recibe nombres, correos, teléfonos, direcciones ni IDs reales, solo banderas 0/1 de si el dato existe. Religión, parentesco y ocupación con menos de 5 personas se agrupan en "Otras" (constante `$minimoCategoria`). Se cuentan claves distintas, no filas.

**Archivos (subir al servidor):**
- `modules/Graficas/familias.php`
- `modules/Graficas/src/GraficasGateway.php`

**Diferencias con lo pedido:** se hizo en el módulo Graficas (no en Informes Escolares) y con los filtros propios del dashboard (no ControlWrapper), igual que estudiantes y docentes. No se incluye el filtro de grupo (se había quitado en estudiantes por poco útil). La gráfica de contacto es de columnas agrupadas (no apiladas al 100 %), porque correo, teléfono y dirección no son partes de un total.

**Supuestos / no verificado (sin acceso a la BD):**
- Columnas confirmadas en `gibbon.sql`: `gibbonPerson.religion`, `profession`, `email`, `emailAlternate`, `phone1`, `address1`; `gibbonFamilyChild`, `gibbonFamilyAdult`, `gibbonFamilyRelationship` (persona 1 = acudiente, persona 2 = estudiante, como lo usan Finance y Admisiones).
- La religión es la del estudiante (`gibbonPerson.religion` del estudiante), no la del acudiente.
- Solo se cuentan acudientes con estado `Full`. "Hijos en el colegio" = estudiantes de la familia matriculados ese año.
- Si un estudiante está en varias familias (padres separados) cuenta en cada una.
- Parentesco "Sin dato" = no hay fila en `gibbonFamilyRelationship` para ese acudiente y estudiante.
- Acceso: limitar la acción "Graficas familias" a directivos y pastoral.

**Consultas para validar:**
```sql
SELECT COUNT(*) AS estudiantes_sin_familia FROM gibbonStudentEnrolment e
 INNER JOIN gibbonSchoolYear y ON y.gibbonSchoolYearID = e.gibbonSchoolYearID AND y.status = 'Current'
 WHERE NOT EXISTS (SELECT 1 FROM gibbonFamilyChild fc WHERE fc.gibbonPersonID = e.gibbonPersonID);
SELECT relationship, COUNT(*) FROM gibbonFamilyRelationship GROUP BY relationship ORDER BY 2 DESC;
SELECT religion, COUNT(*) FROM gibbonPerson WHERE status = 'Full' GROUP BY religion ORDER BY 2 DESC;
SELECT UPPER(TRIM(profession)) AS ocupacion, COUNT(*) FROM gibbonPerson WHERE profession <> '' GROUP BY 1 ORDER BY 2 DESC LIMIT 20;
```

**Notas:** sin prueba contra la BD; se probó la página con datos inventados (sintaxis PHP y JS, escritorio).

---

## 2026-10-27 — Gráficas de docentes (módulo Graficas)

**Qué se agregó:** dashboard "Gráficas de docentes" en `modules/Graficas/docentes.php` (la opción "Graficas docentes" que ya estaba en el menú del módulo Graficas; no hizo falta script SQL ni tocar el manifest). Mismo estilo que Gráficas de estudiantes. Solo planeaciones (las observaciones ya no se muestran). Filtros: año, rango de fechas y docente con búsqueda. Sin tarjetas de cifras ni texto introductorio. 3 gráficas con pregunta, frase corta e ícono (i): planeaciones por docente (todos los activos, los que no tienen ninguna en gris), cuántos docentes están al día (porcentaje con barra de progreso y quiénes faltan) y en qué meses se planea más (columnas por mes).

**Datos:** `queryGraficasDocentes()` y `queryDocentesActivos()` en `modules/Graficas/src/GraficasGateway.php`. La página pide solo planeaciones (`$incluirObservaciones = false`); el método sigue pudiendo traer observaciones si se vuelven a necesitar. El navegador recibe listas de nombres/materias una sola vez y cada planeación como `[fecha, docente, id]` (sin IDs de persona). Constantes al inicio de la página: `$fechaMinima` (2020-01-01) y `$incluirFechasFuturas` (false: se excluye lo posterior a hoy).

**Archivos (subir al servidor):**
- `modules/Graficas/docentes.php`
- `modules/Graficas/src/GraficasGateway.php`

**Supuestos / no verificado (sin acceso a la BD):**
- Planeaciones: se atribuyen a `gibbonPlannerEntry.gibbonPersonIDCreator` (columna confirmada en `gibbon.sql`). Respaldo: si ese creador ya no existe en `gibbonPerson`, se atribuyen a cada docente (`role = 'Teacher'`) de la clase y se cuentan una sola vez en los totales.
- Docentes: solo cuentan personas que son docentes de alguna clase (`gibbonCourseClassPerson.role = 'Teacher'`). "Docentes activos" (denominador del cumplimiento) = estado `Full` y con clase asignada en el año escolar actual; así se excluye al personal administrativo y de apoyo de `gibbonStaff`. Las planeaciones solo se atribuyen a quien es o fue docente de alguna clase. Si un docente activo no tiene clases asignadas este año, no aparecerá. El cruce con quienes planearon se hace por el texto "Apellido Nombre" (no se envían IDs); dos personas con el mismo nombre se confundirían.
- Con ~90.000 eventos el navegador recibe unos pocos MB de datos; si la página tarda, avisa.
- Acceso: limitar la acción "Graficas docentes" a directivos (Administración > Permisos de acción).

**Consultas para validar:**
```sql
SELECT YEAR(date) AS anio, COUNT(*) FROM gibbonPlannerEntry GROUP BY YEAR(date) ORDER BY 1;
SELECT COUNT(*) AS sin_creador FROM gibbonPlannerEntry pe
 WHERE NOT EXISTS (SELECT 1 FROM gibbonPerson p WHERE p.gibbonPersonID = pe.gibbonPersonIDCreator);
SELECT COUNT(DISTINCT p.gibbonPersonID) AS docentes_activos
 FROM gibbonPerson p
 INNER JOIN gibbonCourseClassPerson ccp ON ccp.gibbonPersonID = p.gibbonPersonID AND ccp.role = 'Teacher'
 INNER JOIN gibbonCourseClass cc ON cc.gibbonCourseClassID = ccp.gibbonCourseClassID
 INNER JOIN gibbonCourse c ON c.gibbonCourseID = cc.gibbonCourseID
 INNER JOIN gibbonSchoolYear y ON y.gibbonSchoolYearID = c.gibbonSchoolYearID AND y.status = 'Current'
 WHERE p.status = 'Full';
```

**Notas:** sin prueba contra la BD; se probó la página con datos inventados (sintaxis PHP y JS, escritorio y celular).

---

## 2026-10-27 — Gráficas de estudiantes: se muda al módulo Graficas

**Qué cambió:** el dashboard ahora vive completo en `modules/Graficas/estudiantes.php` (la opción "Graficas de estudiantes" del menú), con su propio gateway `modules/Graficas/src/GraficasGateway.php`. Se quitó de Informes Escolares: se borró `graficasEstudiantes.php`, el script `registrar_accion_graficas.sql` y el método `queryGraficasEstudiantes()` de `InformesEscolaresGateway.php`. El módulo Graficas queda versionado (se agregó a `modules/.gitignore`).

**Archivos (subir al servidor):**
- `modules/Graficas/estudiantes.php`
- `modules/Graficas/src/GraficasGateway.php` (carpeta `src` nueva)
- `modules/Informes Escolares/src/InformesEscolaresGateway.php`

**Borrar del servidor:** `modules/Informes Escolares/graficasEstudiantes.php`.

**Quitar la acción extra en la BD** (la que creó `registrar_accion_graficas.sql`):
```sql
DELETE p FROM gibbonPermission p
INNER JOIN gibbonAction a ON a.gibbonActionID = p.gibbonActionID
INNER JOIN gibbonModule m ON m.gibbonModuleID = a.gibbonModuleID
WHERE m.name = 'Informes Escolares' AND a.URLList = 'graficasEstudiantes.php';

DELETE a FROM gibbonAction a
INNER JOIN gibbonModule m ON m.gibbonModuleID = a.gibbonModuleID
WHERE m.name = 'Informes Escolares' AND a.URLList = 'graficasEstudiantes.php';
```

---

## 2026-10-26 — Informes Escolares: nuevo dashboard "Gráficas de estudiantes"

**Qué se agregó:** página `graficasEstudiantes.php` (también servida desde `modules/Graficas/estudiantes.php`) con Google Charts (loader oficial, idioma `es`) y la paleta azul de los reportes. Filtros en una barra: año escolar y género con botones segmentados, grado en una lista; por defecto muestran todo el año actual. Franja con total, niñas, niños y edad promedio. 5 gráficas, cada una con una pregunta como título, una frase corta con el resultado y un ícono (i) arriba a la derecha que abre la explicación detallada (cómo se calcula, supuestos): estudiantes por grado, niñas y niños, extraedad por grado ("5 de 17"), estudiantes nuevos vs. antiguos (columnas) y evolución por año (ignora el filtro de año). Se corrigió que la última gráfica saliera en blanco: `getFilteredRows` falla con una lista de filtros vacía. Si una gráfica falla, las demás se siguen dibujando. Al navegador solo llegan año, grado, grupo, género, edad y antigüedad (sin nombres, identificadores ni localidad).

**Datos:** nuevo método `queryGraficasEstudiantes()` en el gateway (una fila por estudiante y año escolar, años Past y Current; en el año actual solo status `Full`). El grado sale de `gibbonYearGroup`, el grupo con `TRIM`.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/src/InformesEscolaresGateway.php`
- `modules/Informes Escolares/graficasEstudiantes.php`
- `modules/Graficas/estudiantes.php`

**Registrar la acción (sin tocar manifest.php):** correr en phpMyAdmin `modules/Informes Escolares/registrar_accion_graficas.sql`. Crea la acción "Gráficas de estudiantes" copiando categoría y permisos por rol de `reportAttendances.php`, y es repetible. No hay que subir ese archivo al servidor, solo ejecutarlo.

**No verificado (sin acceso a la BD):**
- `gibbonPerson.nationality`: no aparece en `gibbon.sql` del repositorio. El gateway comprueba si la columna existe y, si no, devuelve "Sin dato" (la columna se cambia en `$columnaNacionalidad`, al inicio de la página). Ninguna de las 8 gráficas usa nacionalidad todavía.
- `gibbonPerson.address1District`, `dob`, `gender` y `gibbonYearGroup.name/sequenceNumber`: sí están en `gibbon.sql`, pero conviene confirmarlas en la BD real.
- Edad esperada por grado (1°=6 … 11°=16): es un supuesto mío, configurable en `$edadEsperadaPorGrado`. "En edad" = esperada o esperada+1; "Extraedad" = esperada+2 o más (`$margenExtraedad`). El número de grado se toma del nombre en `gibbonYearGroup`: un número ("1°") o la palabra ("Primero" … "Once"). Si no se reconoce (p. ej. "Transición"), ese grado no entra en la gráfica de extraedad.

**Consultas para validar:**
```sql
SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'gibbonPerson'
  AND column_name IN ('nationality','address1District','dob','gender');
SELECT gibbonYearGroupID, name, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber;
SELECT name, status, sequenceNumber, firstDay FROM gibbonSchoolYear ORDER BY sequenceNumber;
```

**Notas:** sin prueba contra la BD ni visual; solo se verificó la sintaxis (`php -l` y `node --check` del JavaScript).

---

## 2026-10-22 — Informes Escolares: Ingreso de Notas, Notas por Curso, Asistencia, Planeación y Observaciones

**Problemas y causas:**
- **reportEntryNotes.php** y **reportGradesByCourse.php** daban error porque llamaban a `StaffGateway::queryReportEntryNotesData()` y `StudentGateway::queryGradesByCourse()`, métodos que ya no existen en el núcleo.
- **reportAttendances.php** no daba error pero salía en ceros: comparaba el nombre del grupo con `'1ro'`, `'2do'`... exacto.
- **reportPlanning.php** titulaba su columna "Número de Observaciones" por error. Planeación y Observaciones listan a todos los profesores activos (con 0 si no tienen registros), por diseño.

**Solución:** ambos métodos se reconstruyeron en `src/InformesEscolaresGateway.php`; asistencia ahora agrupa por el número con que empieza el nombre del grupo; se corrigió el título de Planeación y se agregó una descripción a Planeación y Observaciones. Se añade `consultas_diagnostico.sql` para revisar los datos en la BD.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/src/InformesEscolaresGateway.php`
- `modules/Informes Escolares/reportEntryNotes.php`
- `modules/Informes Escolares/reportGradesByCourse.php`
- `modules/Informes Escolares/reportPlanning.php`
- `modules/Informes Escolares/reportStudentObservations.php`

**Notas:** reconstrucción de mejor esfuerzo (no hay copia del código original) y sin prueba contra la BD; validar con las consultas de `consultas_diagnostico.sql`.

---

## 2026-09-30 — medicalReportStudents.php: la barra horizontal vuelve a quedar siempre a la vista

**Problema:** al dejar solo la tabla con scroll, el recuadro no estaba limitando la altura y la barra horizontal volvía a quedar al final de los 50 estudiantes.

**Solución:** el recuadro de scroll (altura máxima 75 % de la pantalla, scroll en ambos sentidos) ahora se aplica con JavaScript directamente al contenedor de la tabla, sin depender de `:has()`, y se reaplica cada vez que la tabla se redibuja (ordenar, paginar). Título, contador, Imprimir y Exportar siguen quietos; solo se mueve la tabla.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado sintaxis PHP y JS; sin prueba visual.

---

## 2026-09-30 — medicalReportStudents.php: solo la tabla se desplaza; se quita la barra superior

**Cambios:**
- Se eliminó la barra de scroll horizontal duplicada de arriba (y su JavaScript). Queda solo la de abajo.
- El recuadro con scroll ahora envuelve **únicamente la tabla**, no todo el reporte. "Datos del Reporte", "Resultados 1 a N de N", Imprimir y Exportar quedan quietos; al hacer scroll solo se mueve la tabla.
- Se mantienen: altura máxima de 75 % de la pantalla (la barra horizontal siempre visible), encabezados/foto/nombre fijos, corrección del ensanchamiento de la página e impresión completa.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** usa el selector CSS `:has()` (navegadores actuales: Chrome/Edge 105+, Firefox 121+, Safari 15.4+). Sin prueba visual.

---

## 2026-09-30 — medicalReportStudents.php: barra de scroll horizontal siempre a la vista

**Problema:** con 50 estudiantes por página, la barra de scroll horizontal quedaba al final de la tabla y había que bajar hasta abajo para usarla.

**Solución:**
- La tabla va en un recuadro con altura máxima del 75 % de la pantalla que se desplaza en ambos sentidos, así la barra horizontal queda siempre visible en la parte de abajo del recuadro.
- Se agregó además una barra horizontal duplicada **arriba** de la tabla, sincronizada con la de abajo (solo aparece si la tabla no cabe). Se recalcula sola al ordenar o paginar.
- Los encabezados, la foto y el nombre del estudiante quedan fijos al desplazarse.
- Al imprimir no hay recuadro, barra ni columnas fijas.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado sintaxis PHP y JS; sin prueba visual. El Excel y los datos no cambian.

---

## 2026-09-30 — medicalReportStudents.php: corrección de la página que se seguía ensanchando

**Problema:** aunque la tabla tenía su propio scroll, toda la página (menú y buscador incluidos) seguía saliéndose de la pantalla.

**Causa:** el contenedor de la página (`#content`) es un elemento flex (`lg:flex-1`) y, por defecto, un elemento flex no puede ser más angosto que su contenido; por eso el recuadro con scroll nunca llegaba a limitarse.

**Solución:** en los estilos de esta página se agregó `min-width: 0` y `max-width: 100%` a `#content-wrap`, `#content`, `#content-inner` y al reporte, y `overflow-x: clip` a `#content`. Así el recuadro de la tabla sí queda limitado al ancho de la tarjeta y el scroll horizontal ocurre solo dentro de él. Solo afecta a esta página.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado solo sintaxis PHP; sin prueba visual.

---

## 2026-09-30 — medicalReportStudents.php: scroll dentro de la tarjeta, columnas fijas y tabla compacta

**Problema:** la tabla (14 columnas, ~1144 px) era más ancha que la pantalla (965 px); su contenedor no recortaba el desborde y empujaba toda la página, incluido el menú, generando scroll horizontal general.

**Solución (solo presentación; no cambia datos, botones ni paginador):**
- La tabla va en un recuadro con `overflow-x: auto` y `max-width: 100%`: el scroll horizontal queda solo dentro del reporte y la página ya no se ensancha.
- **Foto** y **Estudiante** quedan fijas a la izquierda al desplazarse (`position: sticky`); Estudiante tiene ancho mínimo de 180 px.
- Celdas más compactas (padding reducido, letra 0.875rem) y encabezados que bajan de línea (máx. 120 px).
- Al imprimir: sin recuadro de scroll, sin columnas fijas y con ancho automático.
- Se mantienen las 14 columnas (no se juntaron los celulares).

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado solo sintaxis PHP; no se pudo probar visualmente a 1366/1024/768 px. Falta revisarlo en el servidor.

---

## 2026-09-30 — medicalReportStudents.php: se revierte a la primera versión

**Motivo:** por petición, se descartan los ajustes de ancho posteriores y se vuelve a la primera versión (la que corrige el error de `queryStudentsData_3` y agrega el Excel con diseño). La tabla en pantalla vuelve a mostrar todas las columnas con foto y sin estilos de ajuste; puede necesitar scroll horizontal.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

---

## 2026-09-30 — medicalReportStudents.php: se restaura la tabla completa, con scroll interno

**Problema:** el ajuste anterior deformó la tabla (anchos fijos en porcentaje, letra reducida y sin foto).

**Solución:** se quitaron esos ajustes. La tabla vuelve a mostrar **todas** las columnas, incluida la **foto**, con su tamaño normal y anchos automáticos. La tabla va dentro de un recuadro con `overflow-x: auto` y ancho máximo del 100 %: si no cabe, el scroll horizontal ocurre dentro del recuadro y la tabla/página no se salen de la pantalla.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado solo sintaxis PHP; falta ver el resultado en pantalla. El Excel no cambia.

---

## 2026-09-30 — medicalReportStudents.php: la tabla ya no se corta (ancho fijo sin foto)

**Problema:** el primer ajuste no bastó; la tabla seguía cortándose a la altura de "Celular 1".

**Solución (más robusta):**
- Se quitó la **foto** de la vista en pantalla (queda comentada en el código; el Excel nunca la incluyó).
- La tabla ahora usa **ancho fijo repartido en porcentajes** (suma 99 %: estudiante 11, RH 4, medicación 6, detalles 11, vacunas 6, comentarios 11, contacto 1: 9/7/7/6, contacto 2: 9/6/6) con letra al 80 % y texto que baja de línea. Con ancho fijo la tabla siempre mide el 100 % de la pantalla, sin importar cuánto texto tengan las celdas.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado solo sintaxis PHP; falta verlo en pantalla. Si alguna columna queda angosta, se ajustan los porcentajes.

---

## 2026-09-30 — medicalReportStudents.php: tabla ajustada al ancho de la pantalla

**Problema:** al consultar un grupo, la tabla (13 columnas) se salía de la pantalla y obligaba a hacer scroll horizontal.

**Solución:** se agregó un bloque de estilos solo para esta tabla: letra un poco más pequeña (85 %), márgenes laterales reducidos, texto largo (detalles de medicación, comentarios) que baja de línea, contenido alineado arriba y foto más pequeña (máx. 48 px). La consulta, las columnas y el Excel no cambian.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`

**Notas:** verificado solo sintaxis PHP; falta ver el resultado en pantalla. Si aún se sale, se puede reducir más la letra o quitar la foto.

---

## 2026-09-30 — Arreglo de medicalReportStudents.php y reportStudentsReligion.php (+ Excel con diseño)

**Problema:** ambas páginas mostraban error al elegir un grupo. Llamaban a `StudentGateway::queryStudentsData_3()`, un método que ya no existe en el núcleo de Gibbon.

**Solución:**
- Se agregó `queryStudentsData_3()` al gateway propio del módulo (`InformesEscolaresGateway`), que sobrevive a las actualizaciones del núcleo. Trae los estudiantes activos del grupo con sus datos médicos (`gibbonPersonMedical.*`, así aparecen también columnas propias como `vacunas10Years`), religión y contactos de emergencia. Es una reconstrucción: validar contra lo que se espera ver.
- Ambas páginas usan ahora ese gateway; el formulario solo se muestra en pantalla (no al exportar) y se cambió `$gibbon->session` por `$session`.
- **Excel con el diseño unificado:**
  - Médico (`ReporteMedico_<grupo>_<fecha>`): Apellidos, Nombres | RH, Medicación permanente (Sí/No), Detalles, Vacunas 10 años, Comentarios | Contacto de emergencia 1 y 2 (Nombre, Parentesco, Celular 1 y 2).
  - Religión (`Religion_<grupo>_<fecha>`): Apellidos, Nombres, Religión.
- La vista en pantalla no cambia.

**Seguridad:** en `medicalReportStudents.php` había un bloque de código viejo comentado con el **usuario y la contraseña de la base de datos** escritos en texto. Se eliminó el bloque (era código muerto). Como ese texto ya quedó en el historial de git, se recomienda cambiar esa contraseña.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/medicalReportStudents.php`
- `modules/Informes Escolares/reportStudentsReligion.php`
- `modules/Informes Escolares/src/InformesEscolaresGateway.php` (**actualizado**: método nuevo)
- `modules/Informes Escolares/src/StyledSpreadsheetRenderer.php` (necesario para exportar; ya subido antes, debe estar la última versión)

**Notas:**
- Verificado solo sintaxis PHP; la consulta SQL no se pudo ejecutar aquí (sin base de datos). Falta probar en el servidor.
- `informeProfesoresDatosActualizacion.php` usa el mismo método eliminado y seguirá fallando hasta que se cambie a este gateway (no se tocó).

---

## 2026-09-30 — Reporte de edad promedio / sexo (report_formGroupSummary.php): fechas, español y Excel

**Para qué sirven las fechas (se dejó documentado en la pantalla, en español):** por defecto el reporte cuenta a los estudiantes matriculados en el año escolar actual con estado Activo. Si se escriben fechas, muestra cómo estaba el colegio en ese período: cuenta a los estudiantes cuya fecha de inicio es anterior o igual a "Desde" y cuya fecha de salida es posterior o igual a "Hasta" (o no tienen esas fechas), sin importar su estado actual. La edad promedio siempre se calcula con la fecha de hoy.

**Errores encontrados y corregidos:**
1. **Filtro de fechas defectuoso (del núcleo de Gibbon):** "Desde" y "Hasta" usaban el mismo parámetro SQL `:date`, así que con las dos fechas puestas, la segunda pisaba a la primera y el resultado era incorrecto. Ahora la consulta vive en la propia página, con parámetros separados, sin tocar el núcleo.
2. **Si se escribía solo una fecha,** la otra se completaba únicamente en pantalla; al exportar o imprimir se usaba otra distinta. Ahora se completa siempre, antes de consultar.
3. **Fila "Todos los grupos":** la edad promedio era un promedio de promedios (sin ponderar por cantidad de estudiantes). Ahora se calcula con todos los estudiantes.
4. **Hombres + Mujeres no sumaba el Total** (los demás sexos no se mostraban). Ahora aparece la columna "Otros / No especificado" cuando hay alguno.
5. La edad ya no sale con formato de texto (`FORMAT`), sino como número redondeado.

**Español:** título, introducción, etiquetas de fechas, columnas (Grupo, Edad promedio (años), Hombres, Mujeres, Total), botón "Limpiar filtros" y fila "Todos los grupos".

**Excel:** título, fecha de generación, período consultado, bandas de color (Grupo / Edad / Estudiantes por sexo / Total), números guardados como números (se pueden sumar y ordenar), fila de totales resaltada y fuera del filtro, columna de grupo fija e impresión horizontal. Archivo: `EdadPromedio_Sexo_AAAA-MM-DD.xlsx`.

**Corrección en el renderer de Excel compartido:** las bandas de color por sección y el resaltado en rojo de fechas sin actualizar no se aplicaban (todo caía en una sola sección), porque las columnas no se identificaban por su nombre. Ya funciona en los Excel de Resumen de emergencia, Estudiantes, Directorio y este.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/report_formGroupSummary.php`
- `modules/Informes Escolares/src/StyledSpreadsheetRenderer.php` (**actualizado**: volver a subirlo)
- `modules/Informes Escolares/report_student_emergencySummary.php` (1 línea corregida en el Excel)

**Notas:** verificado sintaxis PHP y generación de un .xlsx de prueba; falta probar con datos reales (la consulta SQL no se pudo ejecutar aquí, sin base de datos).

---

## 2026-09-30 — Corrección: reportStudents.php y report_contact_student.php fallaban al abrirse

**Problema:** en el servidor ambas páginas mostraban "¡Gibbon ha terminado!". Las dos cargaban `src/StyledSpreadsheetRenderer.php` al inicio; si ese archivo no está en el servidor (o está en otra carpeta), PHP falla antes de mostrar cualquier cosa.

**Solución:** el archivo del renderer ahora solo se carga cuando se presiona Exportar. Sin él, las páginas en pantalla funcionan igual que antes; para que el Excel con diseño funcione hay que subirlo.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/reportStudents.php`
- `modules/Informes Escolares/report_contact_student.php`
- `modules/Informes Escolares/src/StyledSpreadsheetRenderer.php` (**debe quedar en la carpeta `src`**, junto a `InformesEscolaresGateway.php`; es necesario para exportar)

---

## 2026-09-30 — Excel con diseño en el Directorio (report_contact_student.php)

**Problema:** el Excel del Directorio salía feo: dirección y acudientes apilados en una sola celda, sin estilo.

**Solución:** al exportar se arma un Excel con columnas planas y el mismo estilo de los otros reportes: título, fecha y total, bandas de color por sección, filas alternadas con bordes, filtros, columnas fijas e impresión horizontal.
- **Estudiante:** Grupo, Apellidos, Nombres, Dirección (de la familia, en una línea; si hay varias familias van separadas por ` | `; si no hay familia, la del estudiante).
- **Acudiente 1 y Acudiente 2:** Nombre, Parentesco, Teléfonos (con su tipo) y Correo. Si hay más de dos acudientes, del tercero en adelante se juntan en Acudiente 2, separados por ` | `.
- Archivo: `Directorio_AAAA-MM-DD.xlsx`.

La vista en pantalla y la impresión no cambian; el orden y la consulta tampoco.

**Archivos (subir al servidor):**
- `modules/Informes Escolares/report_contact_student.php` (modificado)
- `modules/Informes Escolares/src/StyledSpreadsheetRenderer.php` (ya se subió en el cambio anterior de reportStudents.php; si aún no está en el servidor, súbelo también)

**Notas:** verificado solo sintaxis PHP; falta probar con datos reales en el servidor.

---

## 2026-09-30 — Arreglo de Exportar en reportStudents.php y Excel con diseño

**Problema:** el botón Exportar de `reportStudents.php` no generaba el Excel. Al exportar, la página seguía mostrando el formulario "Elegir grupo" antes de la tabla; esa salida HTML impide que el navegador reciba el archivo correctamente.

**Solución:**
- El formulario solo se muestra en pantalla (no en exportar ni imprimir).
- Al exportar se arma un Excel con columnas planas (Apellidos, Nombres, Usuario, T.I, Acceso, Teléfono, Email papá, Email mamá, Dirección de casa) y el mismo estilo del Excel de emergencia: título con el grupo, fecha y total, bandas de color por sección (Estudiante / Contacto), filas alternadas con bordes, apellidos y nombres fijos al desplazarse, filtros en los encabezados e impresión horizontal. El archivo se llama `ReporteEstudiantes_<grupo>_<fecha>`.
- Se creó un renderer reutilizable para otros reportes del módulo.
- Se cambió `$gibbon->session` por `$session` en este archivo (la forma actual de Gibbon).

**Archivos (subir al servidor):**
- `modules/Informes Escolares/reportStudents.php` (modificado)
- `modules/Informes Escolares/src/StyledSpreadsheetRenderer.php` (**archivo nuevo**, en la carpeta `src`)

**Notas:**
- Verificado: sintaxis PHP y generación de un .xlsx válido con datos de prueba. Falta probar con datos reales en el servidor.
- La pantalla no cambia, salvo que el Excel ya no incluye la foto.

---

## 2026-09-30 — Tabla de estudiantes sin scroll horizontal y nuevo diseño del selector de emergencia

**Cambios:**
- **`reportStudents.php`:** la tabla se ajusta al ancho de la pantalla. El texto largo (correos, direcciones) baja de línea en lugar de ensanchar la tabla, así que ya no hace falta desplazarse horizontalmente. Solo se agregó un bloque de estilos antes de mostrar la tabla; la consulta y las columnas no cambian.
- **`report_student_emergencySummary.php`:**
  - Se eliminó el botón "Quitar visibles". Quedan "Seleccionar todo" (antes "Seleccionar visibles"; selecciona a todos los estudiantes que se ven con los filtros actuales) y "Limpiar selección".
  - Nuevo diseño con el color de la página `#3575EF`: encabezado azul con texto blanco, fondo azul muy claro, campos con borde azul y foco resaltado, botón principal azul, contador de seleccionados en forma de etiqueta, grupos con banda azul clara y casillas en azul. Los estudiantes seleccionados siguen en gris más oscuro, ahora con la barra lateral azul.

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/reportStudents.php`
- `modules/Informes Escolares/report_student_emergencySummary.php`

**Notas:**
- Verificado: sintaxis PHP y JS. Falta probar visualmente en el servidor.

---

## 2026-09-29 — Resumen de emergencia: reporte automático, selección resaltada y Excel con mejor diseño

**Cambios en `report_student_emergencySummary.php`:**
- **Panel "Elegir estudiantes":** siempre inicia abierto. Se puede minimizar con el botón **−** (pasa a **+**).
- **Reporte automático:** se quitó el botón "Generar reporte". La tabla se actualiza sola (sin recargar la página) unos instantes después de marcar o desmarcar estudiantes; si no hay ninguno marcado, la tabla desaparece.
- **Estudiantes seleccionados:** ahora se ven con fondo gris más oscuro, texto en negrita y una barra oscura a la izquierda.
- **Excel:** diseño propio (sin tocar el núcleo): título, fecha de generación y total de estudiantes; bandas de color por sección (Estudiante, Acudiente 1 y 2, Emergencia 1 y 2) con encabezados en el color de su sección; filas alternadas con bordes suaves; texto ajustado; fecha de actualización vencida en rojo; nombre y apellido fijos al desplazarse; filtros en los encabezados; impresión horizontal ajustada al ancho con encabezados repetidos y pie con número de página.

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/report_student_emergencySummary.php`

**Notas:**
- Verificado: sintaxis PHP y JS, y generación de un .xlsx válido con datos de prueba. Falta probar con datos reales en el servidor.

---

## 2026-09-29 — Corrección: el reporte y el Excel salían vacíos (Informes Escolares)

**Problema:** tras el rediseño, "Resumen de Datos de Emergencia del Estudiante" no traía información y el botón Exportar generaba el Excel vacío.

**Causa:** los IDs de estudiante en Gibbon llevan ceros a la izquierda (`0000001234`). El rediseño los convertía a número (`1234`) y la consulta ya no encontraba a ningún estudiante.

**Solución:** los IDs se conservan como texto (validando que sean solo dígitos). Además, el botón "Ocultar filtros" ahora es un botón compacto **−** (minimizar) que cambia a **+** (expandir).

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/report_student_emergencySummary.php`

**Notas:**
- "Generar reporte" solo muestra la tabla; el Excel se descarga con el botón **Exportar** de la tabla "Resumen de Datos de Emergencia del Estudiante".
- Verificado solo sintaxis PHP; falta probar en el servidor.

---

## 2026-09-29 — Rediseño de Resumen de emergencia (Informes Escolares)

**Qué se mejoró en `report_student_emergencySummary.php`:**
- **Selección de estudiantes:** se reemplazó el selector múltiple por una lista con casillas, agrupada por curso y grupo. Tiene buscador por nombre/apellido, filtro por Curso y por Grupo (los grupos dependen del curso elegido), botones "Seleccionar visibles", "Quitar visibles" y "Limpiar selección", y un contador de seleccionados.
- **Ocultar/Mostrar filtros:** el botón ahora funciona. Al generar el reporte, el panel se pliega solo para dar espacio a la tabla.
- **Excel (exportar):** ahora cada dato va en su propia columna (Apellidos, Nombres, Curso, Grupo, Última actualización, Email, Teléfono estudiante, Acudiente 1 y 2 con parentesco/teléfonos/correo, Emergencia 1 y 2 con parentesco y dos teléfonos), con anchos definidos. El archivo se llama `ResumenEmergencia_AAAA-MM-DD`.
- **Tabla en pantalla:** se agregó Curso - Grupo bajo el nombre del estudiante. La vista de impresión no cambia.

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/report_student_emergencySummary.php`

**Notas:**
- Solo se modificó este archivo; no se tocó el núcleo, la consulta ni otros reportes. El diseño usa HTML/JS propio dentro de la página, sin dependencias nuevas.
- La lista muestra estudiantes activos del año escolar actual (mismo criterio que el selector anterior).
- Verificado: sintaxis PHP y JS sin errores. Falta probar en el servidor.

---

## 2026-09-29 — Arreglo del resumen de emergencia (Informes Escolares)

**Problema:** `Informes Escolares > report_student_emergencySummary.php` mostraba "¡Gibbon ha terminado!". El archivo usaba APIs que ya no existen en el núcleo de Gibbon: `$gibbon->session` y la función global `getSettingByScope()`.

**Solución:** se reemplazaron por las APIs actuales, igual que en la versión del módulo Students: `$session` y `SettingGateway::getSettingByScope()`.

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/report_student_emergencySummary.php`

**Notas:**
- Cambio mínimo (5 líneas); no se tocó la lógica ni el diseño del reporte. Sin verificar en el servidor todavía (php -l sin errores de sintaxis).
- Otros archivos de este módulo aún usan `$gibbon->session` (no se modificaron).

**Versionamiento:** el módulo `Informes Escolares` se agregó a la lista blanca de `modules/.gitignore`. Los archivos `lib/google/error_log` (117 MB) y `db/zemfzeav_cema.csv` (61 MB) se sacaron del historial y se agregaron a `.gitignore` porque superan el límite de GitHub. Siguen en el disco, solo no se versionan.

---

## 2026-09-29 — Arreglo del reporte de estudiantes (Informes Escolares)

**Problema:** en `Informes Escolares > reportStudents.php`, al elegir un curso y pulsar "Ir" aparecía "¡Gibbon ha terminado!". El código llamaba a `StudentGateway::queryStudentsData()`, un método que ya no existe en el núcleo de Gibbon.

**Solución:** se reconstruyó la consulta en el gateway propio del módulo (que sobrevive a actualizaciones del núcleo) y el reporte pasó a usarlo.

**Archivos modificados (subir al servidor):**
- `modules/Informes Escolares/reportStudents.php` — usa `InformesEscolaresGateway` en lugar de `StudentGateway`; se quitó una variable sin uso.
- `modules/Informes Escolares/src/InformesEscolaresGateway.php` — se agregó el método `queryStudentsData()`.

**Notas:**
- Correos de papá/mamá: se toman de los adultos de la familia del estudiante según género (`M` = papá, `F` = mamá), priorizando `contactPriority`. Es una reconstrucción, no la consulta original.
- Dirección: `gibbonFamily.homeAddress` de la familia del estudiante.
- No se modificó el núcleo ni otros reportes.
- Estado: probado por el usuario, funciona.
