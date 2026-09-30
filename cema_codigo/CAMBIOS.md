# Registro de cambios

Cada entrada indica qué se cambió, por qué y **qué archivos hay que subir al servidor**.

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
