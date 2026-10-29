@echo off
setlocal
title SIADOC / CIMIS - Installation du Lecteur d'Empreintes RealScan-G10
color 0B
cls

cd /d "%~dp0"

:: Verification des droits administrateur
net session >nul 2>&1
if %errorlevel% equ 0 goto RUN_INSTALL

:: Elevation automatique des privileges
echo.
echo =======================================================================
echo   DEMANDE D'AUTORISATION ADMINISTRATEUR WINDOWS
echo =======================================================================
echo   Veuillez cliquer sur "OUI" dans la fenetre qui va s'afficher
echo   afin de permettre l'enregistrement automatique du pilote.
echo =======================================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell.exe -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"\"%~dp0ressources\installer.ps1\"\"' -Verb RunAs"
exit /b

:RUN_INSTALL
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0ressources\installer.ps1"
exit /b
