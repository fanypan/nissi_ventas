@echo off
REM Script para restaurar backup de ventas_bd en Windows
REM Uso: restore_db.bat [host] [usuario] [contraseña] [base_datos]
REM Ejemplo: restore_db.bat localhost root "" ventas_bd_new

setlocal enabledelayedexpansion

REM Valores por defecto
set HOST=localhost
set USER=root
set PASSWORD=
set DATABASE=ventas_bd
set BACKUP_FILE=backup_completo_ventas_bd.sql

REM Procesar argumentos
if not "%~1"=="" set HOST=%~1
if not "%~2"=="" set USER=%~2
if not "%~3"=="" set PASSWORD=%~3
if not "%~4"=="" set DATABASE=%~4
if not "%~5"=="" set BACKUP_FILE=%~5

echo.
echo ========================================
echo RESTAURADOR DE BACKUP - VENTAS_BD
echo ========================================
echo.
echo Host: %HOST%
echo Usuario: %USER%
echo Base de datos: %DATABASE%
echo Archivo: %BACKUP_FILE%
echo.

REM Verificar que el archivo existe
if not exist "%BACKUP_FILE%" (
    echo Error: El archivo %BACKUP_FILE% no existe
    echo.
    pause
    exit /b 1
)

REM Construir comando mysql
if "%PASSWORD%"=="" (
    set MYSQL_CMD=mysql -h %HOST% -u %USER% %DATABASE%
) else (
    set MYSQL_CMD=mysql -h %HOST% -u %USER% -p%PASSWORD% %DATABASE%
)

echo Iniciando restauracion...
echo.

REM Ejecutar restauracion
%MYSQL_CMD% < "%BACKUP_FILE%"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo Backup restaurado exitosamente!
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo Error durante la restauracion!
    echo ========================================
    echo.
)

pause
