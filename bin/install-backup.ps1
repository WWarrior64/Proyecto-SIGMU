# Windows Task Scheduler Setup for SIGMU Weekly Backup
# ============================================================
# Este script crea una tarea programada en Windows que se ejecutará
# todos los lunes a las 7:00 AM para respaldar la base de datos de SIGMU.
# Debe ejecutarse con privilegios de Administrador.

$ScriptDir  = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectDir = (Resolve-Path "$ScriptDir\..").Path
$BackupScript = Join-Path $ProjectDir "config\backup.php"

Write-Host "=== Configurador de Respaldo Semanal (Windows) ===" -ForegroundColor Cyan
Write-Host "Ruta del script de backup: $BackupScript"

# Verificar privilegios de administrador
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Warning "ERROR: Este script requiere ser ejecutado como Administrador para registrar la tarea programada."
    Write-Host "Por favor, abre PowerShell como Administrador e inténtalo de nuevo." -ForegroundColor Yellow
    Exit 1
}

# Verificar que PHP esté disponible en el PATH y obtener su ruta absoluta
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($null -eq $phpCommand) {
    Write-Warning "ATENCIÓN: 'php' no se detectó en el PATH de Windows."
    Write-Host "Asegúrate de que PHP esté instalado y agregado al PATH antes de instalar la tarea." -ForegroundColor Yellow
    Exit 1
}

$PhpExe = $phpCommand.Source
Write-Host "PHP detectado en: $PhpExe" -ForegroundColor Green

$TaskName = "SIGMU_Database_Backup"

# IMPORTANTE: New-ScheduledTaskAction no tiene -WorkingDirectory.
# Usamos la ruta absoluta de PHP y del script para evitar problemas de contexto.
$Action = New-ScheduledTaskAction -Execute "`"$PhpExe`"" -Argument "`"$BackupScript`""

$Trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Monday -At 7:00am

# StartWhenAvailable: si el equipo estaba apagado el lunes, ejecuta la tarea
# en cuanto el equipo vuelva a estar encendido.
$Settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 1)

Write-Host "Registrando tarea programada '$TaskName'..." -ForegroundColor Cyan

try {
    Register-ScheduledTask `
        -TaskName    $TaskName `
        -Action      $Action `
        -Trigger     $Trigger `
        -Settings    $Settings `
        -Description "Backup semanal automático de la base de datos SIGMU (estructura y registros)" `
        -RunLevel    Highest `
        -Force | Out-Null

    Write-Host "¡Tarea programada registrada exitosamente!" -ForegroundColor Green
    Write-Host "Se ejecutará todos los lunes a las 7:00 AM." -ForegroundColor Green
    Write-Host "Si el equipo estaba apagado, se ejecutará en el próximo inicio." -ForegroundColor Green
} catch {
    Write-Error "Fallo al registrar la tarea programada: $_"
    Exit 1
}
