@echo off
setlocal
title SIADOC / CIMIS - Test Materiel RealScan-G10
color 0E
cls

cd /d "%~dp0..\passerelle"
if exist "TestRealScan.exe" (
    TestRealScan.exe
) else if exist "TestTouch.exe" (
    TestTouch.exe
)
pause
