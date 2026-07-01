---
name: skillfront-skill
description: Guía de buenas prácticas de frontend para el proyecto PFCOLOMBIA (PHP + Bootstrap 3 + jQuery). Úsala siempre que se trabaje en vistas .php con HTML/CSS, formularios, tablas dinámicas, modales, componentes AJAX, o cualquier cambio visual/UX del sistema. Aplícala también cuando se pida "mejorar el diseño", "hacer responsive", "modernizar" o "dejar minimalista" una pantalla, aunque no se mencione explícitamente Bootstrap o CSS. Prioriza siempre respetar la paleta de colores y los estilos ya existentes en el proyecto sobre introducir estilos nuevos no solicitados.
---

# Frontend — PFCOLOMBIA

Skill de referencia para cualquier trabajo de interfaz en PFCOLOMBIA: HTML embebido en PHP, CSS, Bootstrap 3 y jQuery. El objetivo es mantener una interfaz **minimalista, agradable, coherente y responsive**, sin romper lo que ya funciona.

## 0. Regla de oro: no cambiar lo que no se pidió

- Antes de tocar una vista, **leer el archivo completo** y localizar los estilos/CSS que ya usa (clases Bootstrap, `<style>` embebidos, hojas de estilo enlazadas).
- Si la tarea es funcional (ej. "agrega este campo al formulario"), **no rediseñar** la pantalla de paso. Cambiar solo lo necesario para cumplir la tarea.
- Si la tarea es de diseño explícita (ej. "mejora el estilo de esta tabla"), sí se puede intervenir el CSS/HTML de esa pantalla, pero sin alterar el layout general compartido (header, sidebar, footer) a menos que se pida.

## 1. Respetar la paleta de colores existente

- Antes de proponer o escribir cualquier color, **buscar los colores actuales** del proyecto: revisar `<style>` embebidos, hojas `.css` enlazadas, clases Bootstrap personalizadas (`btn-primary`, `panel-primary`, etc.) y cualquier variable de color ya definida.
- Reutilizar esos colores (primario, secundario, éxito, alerta, peligro) en vez de inventar una paleta nueva.
- Si el color exacto no es identificable (por ejemplo, está en un archivo no accesible), preguntar antes de asumir un color arbitrario, o usar los colores semánticos estándar de Bootstrap 3 (`btn-primary`, `text-danger`, `alert-warning`, etc.) que ya se usan en el proyecto como fallback seguro.
- No introducir gradientes, sombras exageradas ni paletas nuevas "de moda" que no encajen con el estilo institucional/ministerial ya establecido.

## 2. Principios de diseño minimalista

- Priorizar espacio en blanco, jerarquía tipográfica clara y pocos elementos por pantalla antes que saturar con decoraciones.
- Usar las utilidades de Bootstrap 3 (`.panel`, `.well`, `.form-group`, `.table`, `.row`/`.col-*`) en vez de CSS custom cuando ya resuelven el caso.
- Evitar CSS inline disperso; si se necesita un ajuste puntual, agregarlo a la hoja de estilos existente del módulo, agrupado y comentado.
- Botones y acciones primarias deben ser evidentes (un solo botón primario por sección/formulario); acciones secundarias en estilo `default`/`link`.
- Mantener consistencia de espaciados (`margin-bottom`, `padding`) entre formularios similares del sistema (LPP, CM, Instituto Bíblico, etc.).

## 3. Responsive

- Todo formulario, tabla o panel nuevo debe comportarse bien en móvil usando el grid de Bootstrap 3 (`col-xs-*`, `col-sm-*`, `col-md-*`).
- Las tablas con muchas columnas (ej. listados de graduados, voluntarios internos/externos) deben envolverse en `.table-responsive` para permitir scroll horizontal en pantallas pequeñas.
- Verificar que los modales, selects dependientes (ej. selector de cárcel/ubicación vía AJAX) y filas dinámicas con contador sigan siendo usables en pantallas pequeñas (botones con tamaño táctil adecuado, no depender solo de hover).
- No asumir un ancho fijo de contenedor; evitar `width` en píxeles fijos para elementos que deban adaptarse.

## 4. Patrones ya usados en el proyecto (mantener consistencia)

- **Selectores dependientes vía AJAX** (ej. país/departamento/municipio, ubicación de cárcel): seguir el mismo patrón de "onChange dispara petición AJAX que llena el siguiente select", no reinventar con otro enfoque (ej. no cambiar a fetch nativo si el resto del proyecto usa `$.ajax`/jQuery).
- **Tablas de filas dinámicas con contador** (ej. agregar voluntarios/graduados en un formulario): mantener el mismo patrón de agregar/quitar filas con jQuery y contador visible, en vez de introducir un plugin nuevo no usado en el proyecto.
- **Validaciones de negocio visibles**: los errores de validación deben mostrarse de forma clara y explícita al usuario (mensaje de error visible), **nunca corregir el dato silenciosamente** sin que el usuario se entere. Esto es una convención ya establecida en el proyecto (ej. módulo LPP) y debe respetarse en cualquier formulario nuevo o modificado.
- **Carga de archivos grandes (fotos/testimonios/videos)**: si se agrega un input de archivo, considerar el patrón ya usado de subida AJAX con barra de progreso para archivos pesados.

## 5. Checklist antes de entregar un cambio de frontend

1. ¿Usé los colores y clases ya existentes en el proyecto en vez de inventar unos nuevos?
2. ¿La pantalla se ve bien en móvil (probar mentalmente en `col-xs-12` / ancho angosto)?
3. ¿Mantuve el mismo patrón de AJAX/jQuery que el resto del proyecto?
4. ¿Los errores de validación son visibles para el usuario, no silenciosos?
5. ¿Evité tocar estilos de otras pantallas o del layout general sin que se pidiera?
6. ¿El HTML generado es semántico y no rompe la estructura de Bootstrap 3 (evitar mezclar clases de Bootstrap 4/5 que no existen en este proyecto)?