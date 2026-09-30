# Análisis de consultas a base de datos – Módulo "Informes Escolares" (CEMA)

Sistema: **Gibbon** (usa `gibbonFormGroup`, por lo tanto es v25 o superior).
Módulo: `/modules/Informes Escolares/`

## Hallazgo principal

**Ningún archivo de esta carpeta contiene SQL activo ni credenciales de conexión.** Todos usan la conexión de Gibbon (`$pdo`, `$connection2`) a través de *Gateways* (clases PHP del core, en `src/Domain/...` de Gibbon). Las consultas reales viven **fuera de esta carpeta**, así que la BD es la que esté configurada en `config.php` de la instalación de Gibbon donde se despliegue el módulo.

La única conexión explícita a una BD está **comentada** en `medicalReportStudents.php` (líneas 69-177):

| Dato | Valor |
|---|---|
| Host | `localhost` |
| Base de datos | `zemfzeav_cema` |
| Usuario | `zemfzeav_cema` |
| Contraseña | (expuesta en texto plano en el archivo, línea 72) |

> ⚠️ **Seguridad:** esa contraseña quedó en el código y en git. Conviene cambiarla y borrar el bloque comentado.

`CEMA-VIEJO/` tiene archivos **idénticos** a `CEMA/` (diff vacío), así que no sirve para comparar qué cambió.

## Resumen por archivo

| Archivo | Gateway / método usado | Tablas (BD Gibbon) | Origen del método |
|---|---|---|---|
| `reportStudents.php` | `StudentGateway::queryStudentsData($criteria, $gibbonFormGroupID)` | `gibbonPerson`, `gibbonStudentEnrolment`, `gibbonFormGroup` + tablas de familia/padres (email_father, email_mother, homeAddress, studentID) | **Método personalizado** (no existe en Gibbon estándar) |
| `reportStudentsReligion.php` | `StudentGateway::queryStudentsData_3(...)` | `gibbonPerson` (campo `religion`), `gibbonStudentEnrolment`, `gibbonFormGroup` | **Personalizado** |
| `medicalReportStudents.php` | `StudentGateway::queryStudentsData_3(...)` | `gibbonPerson` (emergency1/2…), `gibbonPersonMedical` (bloodType, longTermMedication, longTermMedicationDetails, vacunas10Years, comment), `gibbonStudentEnrolment`, `gibbonFormGroup` | **Personalizado** |
| `report_contact_student.php` | `StudentReportGateway::queryStudentTransport`, `FamilyGateway::selectFamiliesByStudent`, `selectFamilyAdultsByStudent` | `gibbonPerson`, `gibbonStudentEnrolment`, `gibbonFormGroup`, `gibbonFamily`, `gibbonFamilyChild`, `gibbonFamilyAdult` | Core (`queryStudentTransport` puede estar modificado) |
| `report_student_emergencySummary.php` | `StudentReportGateway::queryStudentDetails`, `FamilyGateway::selectFamilyAdultsByStudent`; `getSettingByScope(... 'Data Updater','cutoffDate')` | `gibbonPerson`, `gibbonStudentEnrolment`, `gibbonFamily*`, `gibbonSetting` | Core |
| `report_formGroupSummary.php` | `StudentReportGateway::queryStudentCountByFormGroup($criteria, $gibbonSchoolYearID)` | `gibbonFormGroup`, `gibbonYearGroup`, `gibbonStudentEnrolment`, `gibbonPerson` (gender, dob) | Core (columnas `totalOther`/`totalUnspecified` se leen del resultado) |
| `data_staff_manage.php` | `StaffUpdateGateway::queryDataUpdates($criteria, $gibbonSchoolYearID)`; `SchoolYearGateway` | `gibbonStaffUpdate`, `gibbonPerson` (alias `target` y `updater`), `gibbonSchoolYear` | Core |

Todos validan acceso con `isActionAccessible(...)` → tablas `gibbonAction` / `gibbonPermission` / `gibbonModule` (el módulo debe llamarse exactamente "Informes Escolares").

## Columnas que usan las vistas (deben venir en el resultado del query)

- `reportStudents.php`: `image_240, surname, preferredName, username, canLogin, phone1, phone1CountryCode, studentID, email_father, email_mother, homeAddress`
- `reportStudentsReligion.php`: `image_240, surname, preferredName, religion`
- `medicalReportStudents.php`: `image_240, surname, preferredName, bloodType, longTermMedication, longTermMedicationDetails, vacunas10Years, comment, emergency1Name, emergency1Number1, emergency1Number2, emergency1Relationship, emergency2Name, emergency2Number1, emergency2Number2`
- `report_formGroupSummary.php`: `formGroup, meanAge, totalMale, totalFemale, total` (y `totalOther`, `totalUnspecified`)

## Causas probables de las fallas (ordenadas por probabilidad)

1. **`StudentGateway::queryStudentsData` y `queryStudentsData_3` no existen o fueron sobrescritas.** Son métodos agregados a mano al core de Gibbon (`src/Domain/Students/StudentGateway.php`). Cualquier actualización de Gibbon borra esos cambios → `Call to undefined method`. Esos archivos **no están en el repositorio**, por eso no se pueden revisar aquí. Afecta a 3 de los 7 reportes.
2. **Columnas/tablas personalizadas inexistentes tras migración o actualización:** `gibbonPersonMedical.vacunas10Years`, `gibbonPerson.religion`, `email_father`, `email_mother`, `homeAddress`, `studentID`. Verificar con `DESCRIBE gibbonPersonMedical;` y `DESCRIBE gibbonPerson;` en la BD activa.
3. **Renombre `gibbonRollGroup` → `gibbonFormGroup` (Gibbon v25).** Si el método personalizado o la BD aún usa `RollGroup`, falla. Los archivos ya usan `FormGroup`.
4. **BD equivocada:** si `config.php` apunta a otra base que no sea `zemfzeav_cema` (o la de cada colegio), faltan las columnas custom.
5. **Módulo/acciones mal registradas:** si el nombre del módulo o las acciones en `gibbonAction` no coinciden con `Informes Escolares/<archivo>.php`, sale "You do not have access to this action".
6. **Bug menor en `report_formGroupSummary.php`:** se suman `totalOther` y `totalUnspecified` aunque las columnas están comentadas; si el query del core no las devuelve, da warnings.
7. **Bug menor en `reportStudents.php`:** la columna `username` se agrega dos veces (líneas 92 y 99).
8. **Nota:** `medicalReportStudents.php` y `reportStudentsReligion.php` comparten `queryStudentsData_3`; si ese método solo trae `religion`, faltan `bloodType`, `emergency*`, etc. en el reporte médico.

## Qué necesito para cerrar el diagnóstico

- El archivo `src/Domain/Students/StudentGateway.php` de la instalación (o el de `StudentReportGateway.php`) del servidor.
- El mensaje de error exacto (log de PHP / pantalla) de cada reporte que falla.
- La versión de Gibbon instalada y el `config.php` (sin contraseña) para confirmar la BD.

## Consultas de verificación (ejecutar en la BD)

```sql
SELECT DATABASE();
DESCRIBE gibbonPerson;
DESCRIBE gibbonPersonMedical;
SHOW TABLES LIKE 'gibbonFormGroup';
SHOW TABLES LIKE 'gibbonRollGroup';
SELECT name, version FROM gibbonModule WHERE name LIKE 'Informes%';
SELECT name FROM gibbonAction WHERE gibbonModuleID = (SELECT gibbonModuleID FROM gibbonModule WHERE name='Informes Escolares');
```

---

## ACTUALIZACIÓN: causa confirmada tras revisar `StudentGateway.php`, `StudentReportGateway.php` y `config.php`

- **Base de datos confirmada** (`config.php`): `zemfzeav_cema` en `localhost`. Hay contraseña en texto plano en ese archivo; no subirlo a git.
- **`CEMA/` y `CEMA-VIEJO/` son idénticos** también en los gateways y en config.
- **`StudentGateway.php` NO contiene `queryStudentsData` ni `queryStudentsData_3`.** Es el `StudentGateway` estándar de Gibbon (define solo `queryStudentsBySchoolYear`, `queryStudentEnrolmentBySchoolYear`, `queryStudentEnrolmentByFormGroup`, etc.). Por eso fallan con `Call to undefined method` estos 3 reportes:
  - `reportStudents.php` → `queryStudentsData`
  - `reportStudentsReligion.php` → `queryStudentsData_3`
  - `medicalReportStudents.php` → `queryStudentsData_3`
- Esos métodos no aparecen en ningún commit del repo ni en otra carpeta; el código original se perdió al actualizar Gibbon y hay que **reescribirlos**.
- `StudentReportGateway.php` **sí** tiene `queryStudentDetails`, `queryStudentTransport` y `queryStudentCountByFormGroup`, con las tablas correctas (`gibbonFormGroup`). Los reportes `report_contact_student`, `report_student_emergencySummary` y `report_formGroupSummary` no deberían fallar por este motivo.
- Tablas que deben consultar los métodos a reescribir:
  - `queryStudentsData`: `gibbonPerson`, `gibbonStudentEnrolment` (filtro por `gibbonFormGroupID`), y datos de familia (emails de papá/mamá, dirección) vía `gibbonFamilyChild`/`gibbonFamilyAdult`.
  - `queryStudentsData_3`: `gibbonPerson.religion`, `gibbonPerson.emergency1*/emergency2*`, `gibbonPersonMedical.*`.

## Pendiente para reescribirlos

Ejecutar en `zemfzeav_cema` y compartir el resultado: `DESCRIBE gibbonPerson;` y `DESCRIBE gibbonPersonMedical;`, para confirmar que existen `religion`, `studentID`, `vacunas10Years`, `emergency*` y de dónde salen `email_father`, `email_mother` y `homeAddress`.

---

## SOLUCIÓN APLICADA

Se agregaron `queryStudentsData` y `queryStudentsData_3` al final de `StudentGateway.php` (sin probar contra la BD real).

- Verificado con `DESCRIBE`: `gibbonPerson` tiene `religion`, `studentID`, `emergency1*/emergency2*`, `address1`; `gibbonPersonMedical` tiene `bloodType`, `longTermMedication`, `longTermMedicationDetails`, `vacunas10Years`, `comment`.
- `email_father`, `email_mother` y `homeAddress` **no son columnas**: se calculan (`address1` y emails de adultos de familia con `gender` M/F).
- Filtros: estado `Full`, fechas vigentes, matriculado en el grupo elegido.
- Despliegue: copiar `StudentGateway.php` a `src/Domain/Students/` del servidor (respaldar el original) y probar los 3 reportes.
- Supuestos a validar: papá/mamá por `gender` (alternativa: `contactPriority`) y solo estudiantes `Full`.
