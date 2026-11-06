-- ============================================================================
-- Control financiero y seguimiento: entradas del submenu GRAFICAS
-- Base de datos: twsselvr_videx_app
--
-- Ejecutar en phpMyAdmin con la base twsselvr_videx_app SELECCIONADA.
-- Ejecutar UNA SOLA VEZ (si se repite, se duplican las filas).
-- ============================================================================

-- PASO 1 (opcional). Ver como esta hoy la tabla del menu de graficas.
SELECT * FROM menu_graphs ORDER BY orden;

-- PASO 2. Comprobar que las entradas nuevas NO existan todavia.
-- Debe devolver 0 filas. Si devuelve filas, NO ejecute el paso 3.
SELECT id, nombre, php FROM menu_graphs WHERE php IN ('graphs_rentabilidad', 'graphs_cartera');

-- PASO 3. Crear las dos entradas (quedan al final del submenu; cambie 'orden' si las quiere en otro lugar).
-- Si el INSERT falla por una columna obligatoria, copie el error y el resultado del PASO 1.
INSERT INTO menu_graphs (nombre, php, opc, extra, orden) VALUES
  ('Rentabilidad Proyectos',  'graphs_rentabilidad', 0, '', 100),
  ('Cartera y Seguimiento',   'graphs_cartera',      0, '', 101);

-- PASO 4. Verificar.
SELECT id, nombre, php, opc, extra, orden FROM menu_graphs WHERE php IN ('graphs_rentabilidad', 'graphs_cartera');

-- PASO 5. Dar acceso a los usuarios.
--   Recomendado: Usuario > Acceso > marcar "Rentabilidad Proyectos" y "Cartera y Seguimiento"
--   (la pantalla de permisos lee menu_graphs, asi que las dos opciones aparecen solas).
--   Alternativa por SQL (reemplace 123 por el id del usuario; repita por cada usuario):
--
-- INSERT INTO usuarios_menu_graphs (idUsuario, idMenu)
--   SELECT 123, id FROM menu_graphs WHERE php IN ('graphs_rentabilidad', 'graphs_cartera');
--
-- NOTA: el usuario tambien debe tener activado el acceso a graficas (usuario.acceso_graphs = 1).
--       El superusuario puede abrir las paginas, pero para que le aparezcan en el menu lateral
--       tambien debe tener las dos opciones marcadas.

-- ============================================================================
-- DESHACER (solo si hay que retirar las opciones):
-- DELETE FROM usuarios_menu_graphs WHERE idMenu IN (SELECT id FROM menu_graphs WHERE php IN ('graphs_rentabilidad','graphs_cartera'));
-- DELETE FROM menu_graphs WHERE php IN ('graphs_rentabilidad','graphs_cartera');
-- ============================================================================
