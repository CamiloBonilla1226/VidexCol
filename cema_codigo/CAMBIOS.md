# Registro de cambios

Cada entrada indica qué se cambió, por qué y **qué archivos hay que subir al servidor**.

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
