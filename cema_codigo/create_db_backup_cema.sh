#!/bin/bash
echo "=== CREANDO RESPALDO DE BASE DE DATOS CEMA ==="
echo "Fecha: $(date)"
echo ""

DB_HOST="75.102.22.86"
DB_USER="zemfzeav_cema_22"
DB_PASS='@Lbi[!ZpIQ=.'
DB_NAME="zemfzeav_cema_30"

BACKUP_FILE="backup_cema_$(date +%Y%m%d_%H%M%S).sql"

echo "Creando respaldo en: ./${BACKUP_FILE}"
echo "Base de datos: ${DB_NAME} @ ${DB_HOST}"
echo ""

mysqldump -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" > "${BACKUP_FILE}"

if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
    BACKUP_SIZE=$(ls -l "${BACKUP_FILE}" | awk '{print $5}')
    BACKUP_SIZE_MB=$((BACKUP_SIZE / 1024 / 1024))
    echo "✅ RESPALDO CREADO EXITOSAMENTE"
    echo "📁 Archivo: ${BACKUP_FILE}"
    echo "📊 Tamaño: ${BACKUP_SIZE_MB} MB"
    echo "📍 Ubicación: $(pwd)/${BACKUP_FILE}"
else
    echo "❌ ERROR: No se pudo crear el respaldo o está vacío"
    echo "Verificando conexión a MySQL:"
    mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -e "SELECT VERSION();" 2>&1
fi

echo ""
echo "=== RESPALDO COMPLETADO ==="
