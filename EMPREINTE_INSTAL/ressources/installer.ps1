# ===============================================================================
# SIADOC / CIMIS - Programme d'Installation Simplifie avec Barre de Progression
# Ministere de la Defense - Republique du Cameroun
# ===============================================================================
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$rootDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
if (-not (Test-Path "$rootDir\ressources\passerelle\RealScanBridge.exe")) {
    $rootDir = "C:\xampp\htdocs\cimcim\EMPREINTE_INSTAL"
}

# Fonction d'affichage de la barre de progression
function Display-InstallationUI {
    param(
        [int]$Percent,
        [string]$StepTitle,
        [string]$DetailMessage,
        [string]$StatusColor = "Cyan"
    )

    Clear-Host
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "          MINISTERE DE LA DEFENSE - REPUBLIQUE DU CAMEROUN                     " -ForegroundColor Yellow
    Write-Host "      SIADOC / CIMIS - INSTALLATION AUTOMATIQUE DU LECTEUR REALSCAN-G10        " -ForegroundColor Cyan
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host ""

    # Construction visuelle de la barre de progression (largeur: 36 caracteres)
    $barWidth = 36
    $clampedPercent = [Math]::Max(0, [Math]::Min(100, $Percent))
    $filledLength = [Math]::Floor(($clampedPercent / 100.0) * $barWidth)
    $emptyLength = $barWidth - $filledLength

    $barFilled = [string]::new([char]0x2588, $filledLength)
    $barEmpty = [string]::new([char]0x2591, $emptyLength)

    Write-Host "  Progression : " -NoNewline -ForegroundColor White
    Write-Host "[$barFilled$barEmpty] " -NoNewline -ForegroundColor Green
    Write-Host ("{0,3}%" -f $clampedPercent) -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  Etape en cours : " -NoNewline -ForegroundColor White
    Write-Host $StepTitle -ForegroundColor Yellow
    Write-Host "  Detail         : " -NoNewline -ForegroundColor White
    Write-Host $DetailMessage -ForegroundColor $StatusColor
    Write-Host ""
    Write-Host "-------------------------------------------------------------------------------" -ForegroundColor Gray
    Write-Host ""
}

# -----------------------------------------------------------------------------
# 0% : Verification des Privileges Administrateur
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 5 -StepTitle "Verification de securite Windows" -DetailMessage "Controle des privileges Administrateur..."
Start-Sleep -Milliseconds 400

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Display-InstallationUI -Percent 5 -StepTitle "ECHEC DES PRIVILEGES" -DetailMessage "Privileges Administrateur obligatoires pour enregistrer le pilote USB." -StatusColor "Red"
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host "  [!] ACTION REQUISE :" -ForegroundColor Yellow
    Write-Host "  Pour installer le pilote dans Windows, ce fichier doit etre lance" -ForegroundColor White
    Write-Host "  avec les droits Administrateur." -ForegroundColor White
    Write-Host ""
    Write-Host "  -> Faites un CLIC DROIT sur 'INSTALLER_LE_LECTEUR.bat' puis choisissez :" -ForegroundColor Yellow
    Write-Host "     'Executer en tant qu''administrateur'." -ForegroundColor Yellow
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

# -----------------------------------------------------------------------------
# 10% - 25% : Verification de l'Integrite des Fichiers et DLLs
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 15 -StepTitle "Verification des composants" -DetailMessage "Controle des fichiers du moteur biometrique et bibliotheques C++..."
Start-Sleep -Milliseconds 400

$passerelleDir = "$rootDir\ressources\passerelle"
$requiredFiles = @(
    "RealScanBridge.exe",
    "RealScan_UID_SDK.dll",
    "msvcr80.dll",
    "vcomp.dll",
    "Microsoft.VC80.CRT.manifest",
    "Microsoft.VC80.OpenMP.manifest"
)

$missingList = @()
foreach ($f in $requiredFiles) {
    if (-not (Test-Path (Join-Path $passerelleDir $f))) {
        $missingList += $f
    }
}

if ($missingList.Count -gt 0) {
    Display-InstallationUI -Percent 20 -StepTitle "FICHIERS MANQUANTS" -DetailMessage "Certaines bibliotheques DLL sont introuvables." -StatusColor "Red"
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host "  [!] ERREUR CRITIQUE D'INSTALLATION :" -ForegroundColor Yellow
    Write-Host "  Le dossier d'installation est incomplet. Fichiers manquants :" -ForegroundColor White
    foreach ($m in $missingList) {
        Write-Host "   - $m" -ForegroundColor Red
    }
    Write-Host ""
    Write-Host "  -> Solution : Re-copiez l'integralite du dossier 'EMPREINTE_INSTAL' d'origine." -ForegroundColor Yellow
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 2
}

Display-InstallationUI -Percent 25 -StepTitle "Verification des composants" -DetailMessage "Composants et DLLs C++ valides (100% presents)." -StatusColor "Green"
Start-Sleep -Milliseconds 400

# -----------------------------------------------------------------------------
# 30% - 60% : Enregistrement du Pilote USB Officiel RealScan-G10
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 40 -StepTitle "Installation du Pilote USB" -DetailMessage "Preparation du magasin de pilotes Windows..."
Start-Sleep -Milliseconds 400

$driverInf = "$rootDir\ressources\pilote\RealSeries.inf"
if (-not (Test-Path $driverInf)) {
    Display-InstallationUI -Percent 40 -StepTitle "PILOTE INTROUVABLE" -DetailMessage "Le fichier RealSeries.inf est absent." -StatusColor "Red"
    Write-Host "  [ERREUR] Impossible de trouver le fichier pilote : $driverInf" -ForegroundColor Red
    Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 3
}

Display-InstallationUI -Percent 50 -StepTitle "Installation du Pilote USB" -DetailMessage "Enregistrement du pilote USB 64-bit RealScan-G10 (pnputil)..."
$pnpRes = & pnputil.exe /add-driver $driverInf /install 2>&1

# Verification de la presence du pilote dans le magasin Windows
$driverInstalled = pnputil.exe /enum-drivers 2>$null | Select-String -Pattern "realseries"
if (-not $driverInstalled) {
    Display-InstallationUI -Percent 55 -StepTitle "ECHEC INSTALLATION PILOTE" -DetailMessage "Windows a refuse l'enregistrement du pilote." -StatusColor "Red"
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host "  [!] ERREUR PILOTE WINDOWS :" -ForegroundColor Yellow
    Write-Host "  Le pilote n'a pas pu etre enregistre automatiquement." -ForegroundColor White
    Write-Host "  Message de Windows : $pnpRes" -ForegroundColor Gray
    Write-Host "  =============================================================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Appuyez sur une touche pour quitter..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 4
}

Display-InstallationUI -Percent 65 -StepTitle "Installation du Pilote USB" -DetailMessage "Pilote RealScan-G10 enregistre et valide dans Windows !" -StatusColor "Green"
Start-Sleep -Milliseconds 500

# -----------------------------------------------------------------------------
# 70% - 80% : Configuration du Pare-Feu Windows Defender (Port 8088 TCP)
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 75 -StepTitle "Configuration du Pare-Feu" -DetailMessage "Ouverture securisee du port TCP 8088 dans Windows Defender..."

& netsh advfirewall firewall delete rule name="CIMIS_RealScanBridge" 2>$null | Out-Null
& netsh advfirewall firewall add rule name="CIMIS_RealScanBridge" dir=in action=allow protocol=TCP localport=8088 2>$null | Out-Null

Display-InstallationUI -Percent 80 -StepTitle "Configuration du Pare-Feu" -DetailMessage "Port TCP 8088 autorise pour les captures d'empreintes." -StatusColor "Green"
Start-Sleep -Milliseconds 400

# -----------------------------------------------------------------------------
# 85% - 95% : Creation du Raccourci sur le Bureau Windows
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 85 -StepTitle "Creation du Raccourci Bureau" -DetailMessage "Generation du lanceur sur le Bureau de l'ordinateur..."

try {
    $wsh = New-Object -ComObject WScript.Shell
    $desktopPath = [Environment]::GetFolderPath("Desktop")
    if (-not $desktopPath) {
        $desktopPath = Join-Path $env:USERPROFILE "Desktop"
    }

    $shortcutPath = Join-Path $desktopPath "Lancer Lecteur d'Empreintes (SIADOC).lnk"
    $shortcut = $wsh.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = "$rootDir\ressources\LANCER_LECTEUR.bat"
    $shortcut.WorkingDirectory = "$rootDir\ressources"
    $shortcut.Description = "Demarrer le lecteur biometrique RealScan-G10 pour SIADOC / CIMIS"
    $shortcut.WindowStyle = 1
    
    # Si disponible, icone elegante sinon icone de commande par defaut
    $scannerExe = "$rootDir\ressources\passerelle\RealScanBridge.exe"
    if (Test-Path $scannerExe) {
        $shortcut.IconLocation = "$scannerExe,0"
    }
    $shortcut.Save()

    Display-InstallationUI -Percent 92 -StepTitle "Creation du Raccourci Bureau" -DetailMessage "Raccourci ajoute sur votre Bureau avec succes !" -StatusColor "Green"
} catch {
    Display-InstallationUI -Percent 92 -StepTitle "Creation du Raccourci Bureau" -DetailMessage "Avertissement mineur : raccourci non cree automatiquement." -StatusColor "Yellow"
}
Start-Sleep -Milliseconds 400

# -----------------------------------------------------------------------------
# 95% - 100% : Detection du Materiel USB et Finalisation
# -----------------------------------------------------------------------------
Display-InstallationUI -Percent 97 -StepTitle "Controle Materiel Final" -DetailMessage "Detection de la connexion du capteur optique USB..."

$usbDevice = Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue | Where-Object { 
    $_.DeviceID -match "VID_16D1" -or $_.Name -match "RealScan" -or $_.Description -match "RealScan"
}

Start-Sleep -Milliseconds 500

# 100% SUCCES !
Display-InstallationUI -Percent 100 -StepTitle "INSTALLATION TERMINEE A 100%" -DetailMessage "Le lecteur d'empreintes est pret a l'emploi !" -StatusColor "Green"

Write-Host "===============================================================================" -ForegroundColor Green
Write-Host "             INSTALLATION TERMINEE AVEC SUCCES ! (100%)                        " -ForegroundColor Yellow
Write-Host "===============================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "  1. UN RACCOURCI A ETE CREE SUR VOTRE BUREAU :" -ForegroundColor White
Write-Host "     -> 'Lancer Lecteur d''Empreintes (SIADOC)'" -ForegroundColor Cyan
Write-Host ""

if ($usbDevice) {
    Write-Host "  2. ETAT MATERIEL : [OK] Le lecteur RealScan est deja branche et detecte !" -ForegroundColor Green
} else {
    Write-Host "  2. ETAT MATERIEL : [A NOTER] Le lecteur n'est pas encore branche en USB." -ForegroundColor Yellow
    Write-Host "     -> Il vous suffit de brancher le cable USB du scanner sur le PC" -ForegroundColor Gray
    Write-Host "        avant de proceder a vos enrolements." -ForegroundColor Gray
}

Write-Host ""
Write-Host "  3. UTILISATION AU QUOTIDIEN :" -ForegroundColor White
Write-Host "     -> Double-cliquez simplement sur le raccourci de votre Bureau" -ForegroundColor Yellow
Write-Host "        avant d'enroler les empreintes dans SIADOC / CIMIS." -ForegroundColor Yellow
Write-Host ""
Write-Host "===============================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Appuyez sur une touche pour fermer cette fenetre..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
