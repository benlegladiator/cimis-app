@echo off
setlocal EnableDelayedExpansion
title SIADOC / CIMIS - Lecteur d'Empreintes RealScan-G10
color 0A
cls

cd /d "%~dp0"
set "ROOT_DIR=%~dp0"

echo =======================================================================
echo    MINISTERE DE LA DEFENSE - REPUBLIQUE DU CAMEROUN
echo    SIADOC / CIMIS : SERVICE DU LECTEUR REALSCAN-G10
echo =======================================================================
echo.

rem 1. Verification si le service tourne deja
powershell -NoProfile -Command "$p = Get-Process RealScanBridge -ErrorAction SilentlyContinue; if ($p) { exit 10 } else { exit 0 }"
if %errorlevel% neq 10 goto CHECK_USB_STATUS

echo =======================================================================
echo  [OK] LE SERVICE DU LECTEUR D'EMPREINTES EST DEJA EN LIGNE ET PRET !
echo =======================================================================
echo.
powershell -NoProfile -Command "try { $r = Invoke-RestMethod http://127.0.0.1:8088/status -TimeoutSec 2; if ($r.connected -eq $true) { Write-Host ' [OK] Le lecteur RealScan-G10 est CONNECTE et OPERATIONNEL.' -ForegroundColor Green } else { Write-Host ' [A NOTER] Le service tourne, mais le cable USB du scanner n''est pas encore branche.' -ForegroundColor Yellow; Write-Host '  -> Branchez le scanner RealScan-G10 en USB pour activer la capture.' -ForegroundColor Yellow } } catch { Write-Host ' [ATTENTION] Le processus existe mais ne repond pas sur le port 8088.' -ForegroundColor Yellow }"
echo.
echo  Vous pouvez effectuer vos enrolements directement dans SIADOC / CIMIS.
echo.
echo  Appuyez sur une touche pour fermer cette fenetre...
pause >nul
exit /b 0

:CHECK_USB_STATUS
rem 2. Verification du branchement USB
:CHECK_USB_LOOP
powershell -NoProfile -Command "$dev = Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue | Where-Object { $_.DeviceID -match 'VID_16D1' -or $_.Name -match 'RealScan' }; if ($dev) { Write-Host ' [OK] Scanner RealScan-G10 detecte sur le port USB.' -ForegroundColor Green; exit 0 } else { exit 1 }"
if %errorlevel% equ 0 goto START_SERVICE

echo.
echo =======================================================================
echo  [ATTENTION] LE LECTEUR REALSCAN N'EST PAS DETECTE EN USB !
echo =======================================================================
echo  Veuillez brancher le cordon USB du lecteur sur l'ordinateur.
echo.
echo  -> Appuyez sur ENTREE des que vous l'avez branche,
echo     ou tapez C pour continuer quand meme :
set "REP="
set /p "REP=  Appuyez sur Entree pour re-verifier (ou C pour ignorer) : "
if /i "%REP%"=="C" goto START_SERVICE
goto CHECK_USB_LOOP

:START_SERVICE
echo.
echo =======================================================================
echo    SERVICE BIOMETRIQUE ACTIF - ECOUTE SUR LE PORT 8088
echo    SIADOC / CIMIS est connecte au lecteur d'empreintes.
echo.
echo    [IMPORTANT] Laissez cette fenetre ouverte pendant vos enrolements.
echo    (Vous pouvez la reduire dans la barre des taches)
echo =======================================================================
echo.

cd /d "%ROOT_DIR%passerelle"
RealScanBridge.exe 8088

if %errorlevel% neq 0 (
    echo.
    echo =======================================================================
    echo  [ERREUR] Le service s'est arrete avec le code : %errorlevel%
    echo =======================================================================
    echo.
    echo  Verifiez que le lecteur est bien branche et relancez le raccourci.
    pause
)
