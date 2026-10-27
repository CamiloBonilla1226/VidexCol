-- Registra la acción "Gráficas de estudiantes" en Informes Escolares SIN editar manifest.php.
-- Copia categoría, menú y permisos (por rol) de la acción de reportAttendances.php,
-- así queda con el mismo permiso que los otros reportes. Se puede correr más de una vez.
-- Correr en phpMyAdmin sobre la BD zemfzeav_cema_30 y luego limpiar caché/volver a iniciar sesión.

INSERT INTO gibbonAction
    (gibbonModuleID, name, precedence, category, description, helpURL, URLList, entryURL, entrySidebar, menuShow,
     defaultPermissionAdmin, defaultPermissionTeacher, defaultPermissionStudent, defaultPermissionParent, defaultPermissionSupport,
     categoryPermissionStaff, categoryPermissionStudent, categoryPermissionParent, categoryPermissionOther)
SELECT a.gibbonModuleID, 'Gráficas de estudiantes', 0, a.category,
       'Dashboard con gráficas y filtros de estudiantes', NULL, 'graficasEstudiantes.php', 'graficasEstudiantes.php',
       a.entrySidebar, a.menuShow,
       a.defaultPermissionAdmin, a.defaultPermissionTeacher, a.defaultPermissionStudent, a.defaultPermissionParent, a.defaultPermissionSupport,
       a.categoryPermissionStaff, a.categoryPermissionStudent, a.categoryPermissionParent, a.categoryPermissionOther
FROM gibbonAction AS a
INNER JOIN gibbonModule ON gibbonModule.gibbonModuleID = a.gibbonModuleID
WHERE gibbonModule.name = 'Informes Escolares'
  AND a.URLList LIKE '%reportAttendances.php%'
  AND NOT EXISTS (SELECT 1 FROM gibbonAction x WHERE x.gibbonModuleID = a.gibbonModuleID AND x.name = 'Gráficas de estudiantes')
LIMIT 1;

INSERT INTO gibbonPermission (gibbonRoleID, gibbonActionID)
SELECT p.gibbonRoleID, nuevo.gibbonActionID
FROM gibbonAction AS origen
INNER JOIN gibbonModule ON gibbonModule.gibbonModuleID = origen.gibbonModuleID
INNER JOIN gibbonPermission AS p ON p.gibbonActionID = origen.gibbonActionID
INNER JOIN gibbonAction AS nuevo ON nuevo.gibbonModuleID = origen.gibbonModuleID AND nuevo.name = 'Gráficas de estudiantes'
WHERE gibbonModule.name = 'Informes Escolares'
  AND origen.URLList LIKE '%reportAttendances.php%'
  AND NOT EXISTS (SELECT 1 FROM gibbonPermission y WHERE y.gibbonRoleID = p.gibbonRoleID AND y.gibbonActionID = nuevo.gibbonActionID);

-- Verificación: debe mostrar la acción nueva y los roles con permiso.
SELECT a.gibbonActionID, a.name, a.URLList, COUNT(p.permissionID) AS roles_con_permiso
FROM gibbonAction a
INNER JOIN gibbonModule m ON m.gibbonModuleID = a.gibbonModuleID
LEFT JOIN gibbonPermission p ON p.gibbonActionID = a.gibbonActionID
WHERE m.name = 'Informes Escolares' AND a.name = 'Gráficas de estudiantes'
GROUP BY a.gibbonActionID;
