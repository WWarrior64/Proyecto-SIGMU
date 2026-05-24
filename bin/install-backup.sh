#!/usr/bin/env bash

# Linux Crontab Setup for SIGMU Weekly Backup
# ============================================================
# Este script agrega una tarea al cron de Linux para ejecutar
# el backup todos los lunes a las 7:00 AM.

set -e

# Obtener ruta absoluta del proyecto
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKUP_SCRIPT="$PROJECT_DIR/config/backup.php"
CRON_LOG="$PROJECT_DIR/storage/logs/backup_cron.log"

echo -e "\e[36m=== Configurador de Respaldo Semanal (Linux) ===\e[0m"
echo "Ruta del script de backup: $BACKUP_SCRIPT"

# Verificar que PHP esté instalado y obtener su ruta ABSOLUTA
# (Cron tiene un PATH mínimo, por lo que 'php' sin ruta completa podría no encontrarse)
PHP_PATH=$(command -v php 2>/dev/null || true)
if [ -z "$PHP_PATH" ]; then
    echo -e "\e[31mERROR: 'php' no se encontró en el sistema. Instala PHP antes de continuar.\e[0m"
    exit 1
fi
echo -e "\e[32mPHP encontrado en: $PHP_PATH\e[0m"

# Crear directorio de logs si no existe
mkdir -p "$PROJECT_DIR/storage/logs"
touch "$CRON_LOG"

# Definir la línea del cron usando la ruta ABSOLUTA de PHP
# Formato: 0 7 * * 1 -> Minuto 0, Hora 7, Cualquier día, Cualquier mes, Lunes
CRON_ENTRY="0 7 * * 1 $PHP_PATH $BACKUP_SCRIPT >> $CRON_LOG 2>&1"

# Leer crontab actual del usuario (sin fallar si está vacío)
CURRENT_CRON=$(crontab -l 2>/dev/null || true)

# Verificar si el script ya está registrado para evitar duplicados
if echo "$CURRENT_CRON" | grep -Fq "$BACKUP_SCRIPT"; then
    echo -e "\e[32mEl script de respaldo ya se encuentra configurado en el crontab.\e[0m"
    echo "No se realizaron cambios."
else
    # Agregar la nueva tarea al crontab conservando las existentes
    (echo "$CURRENT_CRON"; echo "$CRON_ENTRY") | crontab -
    echo -e "\e[32m¡Tarea programada agregada al crontab exitosamente!\e[0m"
    echo "Frecuencia: Todos los lunes a las 7:00 AM"
    echo "PHP usado:  $PHP_PATH"
    echo "Script:     $BACKUP_SCRIPT"
    echo "Log salida: $CRON_LOG"
fi
