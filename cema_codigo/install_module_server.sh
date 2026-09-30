#!/bin/bash
# Script para instalar el módulo Reporte Planificación en el servidor
# Ejecutar este script en el terminal web del servidor

cd /home/zemfzeav/cema.pe.edu.co/modules/

# Extraer el módulo
tar -xzf reporte_planificacion_module.tar.gz

# Dar permisos correctos
chown -R zemfzeav:zemfzeav "Reporte Planificacion"
chmod -R 755 "Reporte Planificacion"

# Verificar que se extrajo correctamente
ls -la "Reporte Planificacion"

echo "Módulo instalado. Ahora accede al panel de módulos de Gibbon para activarlo."