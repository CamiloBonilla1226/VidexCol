# Registro de cambios

Cada entrada indica qué se cambió, por qué y **qué archivos hay que subir al servidor**.

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
