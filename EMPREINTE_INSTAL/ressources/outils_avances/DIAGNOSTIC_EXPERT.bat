@echo off
setlocal
title SIADOC / CIMIS - Outils de Diagnostic Avance
color 0B
cls

cd /d "%~dp0..\.."
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0..\diagnostic.ps1"
pause
