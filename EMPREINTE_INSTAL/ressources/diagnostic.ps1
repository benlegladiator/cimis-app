# ===============================================================================
# SIADOC / CIMIS - Outil de Diagnostic et d'Autodiagnostic RealScan-G10
# Ministere de la Defense - Republique du Cameroun
# ===============================================================================
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$rootDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
if (-not (Test-Path "$rootDir\ressources\passerelle\RealScanBridge.exe")) {
    $rootDir = "C:\xampp\htdocs\cimcim\EMPREINTE_INSTAL"
}

Clear-Host
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host "          MINISTERE DE LA DEFENSE - REPUBLIQUE DU CAMEROUN                     " -ForegroundColor Yellow
Write-Host "     SIADOC / CIMIS : CONTROLE & DIAGNOSTIC AUTOMATISE DU LECTEUR              " -ForegroundColor Cyan
Write-Host "                  Suprema RealScan-G10 (Port 8088)                             " -ForegroundColor White
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host ""

$errorsCount = 0
$warningsCount = 0

# -----------------------------------------------------------------------------
# 1. Verification des Droits Administrateur
# -----------------------------------------------------------------------------
Write-Host "[1/7] Verification des Privileges Administrateur Windows..." -ForegroundColor White
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    Write-Host "      [OK] Session executee avec les privileges Administrateur." -ForegroundColor Green
} else {
    Write-Host "      [ATTENTION] La session courante est en droits Utilisateur Standard." -ForegroundColor Yellow
    Write-Host "      * Cause   : Le terminal ou le script a ete ouvert par un simple double-clic." -ForegroundColor Gray
    Write-Host "      * Impact  : Impossible d'installer le pilote dans le magasin Windows ou d'ouvrir le pare-feu." -ForegroundColor Gray
    Write-Host "      * Action  : Faites un CLIC DROIT sur le fichier puis choisissez :" -ForegroundColor Yellow
    Write-Host "                  'Executer en tant qu''administrateur'." -ForegroundColor Yellow
    $warningsCount++
}
Write-Host ""

# -----------------------------------------------------------------------------
# 2. Verification des Fichiers et DLLs Indispensables
# -----------------------------------------------------------------------------
Write-Host "[2/7] Verification de l'Integrite des Fichiers & DLLs du Moteur..." -ForegroundColor White
$passerelleDir = "$rootDir\ressources\passerelle"
$requiredFiles = @(
    @{ Name = "RealScanBridge.exe"; Desc = "Passerelle Web HTTP biometrique"; Critical = $true },
    @{ Name = "RealScan_UID_SDK.dll"; Desc = "Pilote SDK natif C++ RealScan-G10"; Critical = $true },
    @{ Name = "msvcr80.dll"; Desc = "Runtime Visual C++ 2005 (CRT 32-bit)"; Critical = $true },
    @{ Name = "msvcp80.dll"; Desc = "Runtime C++ Standard Library 2005"; Critical = $false },
    @{ Name = "vcomp.dll"; Desc = "Bibliotheque OpenMP VC++ 2005"; Critical = $true },
    @{ Name = "Microsoft.VC80.CRT.manifest"; Desc = "Manifeste SxS CRT 8.0.50727.762"; Critical = $true },
    @{ Name = "Microsoft.VC80.OpenMP.manifest"; Desc = "Manifeste SxS OpenMP 8.0.50727.762"; Critical = $true }
)

$missingCritical = @()
foreach ($item in $requiredFiles) {
    $fullPath = Join-Path $passerelleDir $item.Name
    if (Test-Path $fullPath) {
        Write-Host ("      [OK] " + $item.Name.PadRight(30) + " (" + $item.Desc + ")") -ForegroundColor Green
    } else {
        if ($item.Critical) {
            Write-Host ("      [ERREUR] " + $item.Name.PadRight(26) + " INTROUVABLE ! (" + $item.Desc + ")") -ForegroundColor Red
            $missingCritical += $item.Name
            $errorsCount++
        } else {
            Write-Host ("      [AVERTISSEMENT] " + $item.Name.PadRight(20) + " absent.") -ForegroundColor Yellow
            $warningsCount++
        }
    }
}
if ($missingCritical.Count -gt 0) {
    Write-Host "      * Cause  : Le dossier d'installation a ete copie de maniere incomplete ou tronquee." -ForegroundColor Red
    Write-Host "      * Action : Re-copiez l'integralite du dossier 'EMPREINTE_INSTAL' depuis la source officielle." -ForegroundColor Red
}
Write-Host ""

# -----------------------------------------------------------------------------
# 3. Verification du Pilote Windows USB
# -----------------------------------------------------------------------------
Write-Host "[3/7] Verification du Pilote Windows RealScan (realseries.inf)..." -ForegroundColor White
$driverCheck = pnputil.exe /enum-drivers 2>$null | Select-String -Pattern "realseries"
if ($driverCheck) {
    Write-Host "      [OK] Pilote RealScan-G10 enregistre et valide dans le magasin Windows." -ForegroundColor Green
} else {
    Write-Host "      [ERREUR] Pilote RealScan USB NON DETECTE dans le systeme !" -ForegroundColor Red
    Write-Host "      * Cause  : Le pilote USB du lecteur n'a pas encore ete enregistre sur cette machine." -ForegroundColor Gray
    Write-Host "      * Impact : Windows ne peut pas reconnaitre le scanner biometrique branche." -ForegroundColor Gray
    Write-Host "      * Action : Lancez '1-INSTALLATION_COMPLETE.bat' avec 'Executer en tant qu''administrateur'." -ForegroundColor Yellow
    $errorsCount++
}
Write-Host ""

# -----------------------------------------------------------------------------
# 4. Verification de la Detection Physique du Scanner (USB VID_16D1)
# -----------------------------------------------------------------------------
Write-Host "[4/7] Verification de la Connexion Physique USB du Scanner..." -ForegroundColor White
$usbDevice = Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue | Where-Object { 
    $_.DeviceID -match "VID_16D1" -or $_.Name -match "RealScan" -or $_.Description -match "RealScan"
}

if ($usbDevice) {
    $devName = if ($usbDevice.Name) { $usbDevice.Name } else { "Suprema RealScan-G10" }
    Write-Host "      [OK] Scanner RealScan-G10 detecte physiquement sur le port USB !" -ForegroundColor Green
    Write-Host "           Peripherique : $devName" -ForegroundColor Cyan
    Write-Host "           Identifiant  : $($usbDevice.DeviceID)" -ForegroundColor Gray
    Write-Host "           Etat Windows : $($usbDevice.Status) (Code Erreur: $($usbDevice.ConfigManagerErrorCode))" -ForegroundColor Gray
    if ($usbDevice.ConfigManagerErrorCode -ne 0) {
        Write-Host "      [ATTENTION] Le peripherique USB presente un code d'erreur Windows ($($usbDevice.ConfigManagerErrorCode))." -ForegroundColor Yellow
        Write-Host "      * Cause  : Conflit de pilote ou port USB instable." -ForegroundColor Gray
        Write-Host "      * Action : Debranchez le lecteur, rebranchez-le sur une autre prise USB, puis relancez." -ForegroundColor Yellow
        $warningsCount++
    }
} else {
    Write-Host "      [ATTENTION] Aucun lecteur biometrique RealScan n'est connecte en USB !" -ForegroundColor Yellow
    Write-Host "      * Causes possibles :" -ForegroundColor Gray
    Write-Host "         1. Le cordon USB du lecteur n'est pas branche sur l'ordinateur." -ForegroundColor Gray
    Write-Host "         2. Le scanner est branche sur un concentrateur (Hub USB) passif non alimente." -ForegroundColor Gray
    Write-Host "         3. Le port USB en facade est sous-alimente pour alimenter le prisme optique." -ForegroundColor Gray
    Write-Host "      * Action recommandee :" -ForegroundColor Yellow
    Write-Host "         -> Branchez le cable USB DIRECTEMENT sur un port USB a l'arriere de la tour ou du PC portable." -ForegroundColor Yellow
    Write-Host "         -> Si le scanner dispose d'un interrupteur, assurez-vous qu'il est sur ON." -ForegroundColor Yellow
    $warningsCount++
}
Write-Host ""

# -----------------------------------------------------------------------------
# 5. Verification de la Regle du Pare-Feu Windows (Port 8088 TCP)
# -----------------------------------------------------------------------------
Write-Host "[5/7] Verification de l'Autorisation Pare-Feu (Port 8088)..." -ForegroundColor White
$fwRule = Get-NetFirewallRule -DisplayName "CIMIS_RealScanBridge" -ErrorAction SilentlyContinue
if (-not $fwRule) {
    $fwRule = Get-NetFirewallRule -Name "CIMIS_RealScanBridge" -ErrorAction SilentlyContinue
}

$fwOk = $false
if ($fwRule) {
    if ($fwRule.Enabled -eq "True" -or $fwRule.Enabled -eq 1 -or "$($fwRule.Enabled)" -eq "True") {
        $fwOk = $true
    }
} else {
    $firewallOutput = netsh advfirewall firewall show rule name="CIMIS_RealScanBridge" 2>$null
    if ($LASTEXITCODE -eq 0 -and ($firewallOutput -match "Oui|Yes")) {
        $fwOk = $true
    }
}

if ($fwOk) {
    Write-Host "      [OK] Regle Pare-feu 'CIMIS_RealScanBridge' active pour le port TCP 8088." -ForegroundColor Green
} else {
    Write-Host "      [ATTENTION] Regle Pare-Feu manquante ou inactive pour le port 8088." -ForegroundColor Yellow
    Write-Host "      * Cause  : Le pare-feu Windows n'a pas encore ete configure pour ce service." -ForegroundColor Gray
    Write-Host "      * Impact : L'application web locale pourrait etre bloquee pour communiquer avec le scanner." -ForegroundColor Gray
    Write-Host "      * Action : Lancez '1-INSTALLATION_COMPLETE.bat' en administrateur pour configurer le pare-feu." -ForegroundColor Yellow
    $warningsCount++
}
Write-Host ""

# -----------------------------------------------------------------------------
# 6. Verification du Port Reseau 8088 & Processus
# -----------------------------------------------------------------------------
Write-Host "[6/7] Verification de l'Ecoute Reseau (Port 8088)..." -ForegroundColor White
$realScanProc = Get-Process RealScanBridge -ErrorAction SilentlyContinue | Select-Object -First 1
$tcpConn = Get-NetTCPConnection -LocalPort 8088 -ErrorAction SilentlyContinue | Select-Object -First 1

if ($realScanProc) {
    Write-Host "      [OK] La passerelle RealScanBridge est DEJA EN LIGNE (PID: $($realScanProc.Id))." -ForegroundColor Green
} elseif ($tcpConn) {
    $proc = Get-Process -Id $tcpConn.OwningProcess -ErrorAction SilentlyContinue
    $procName = if ($proc) { $proc.ProcessName } else { "PID $($tcpConn.OwningProcess)" }
    Write-Host "      [CONFLIT] Le port 8088 est occupe par un autre processus : $procName." -ForegroundColor Red
    Write-Host "      * Cause  : Une autre application utilise deja le port 8088." -ForegroundColor Gray
    Write-Host "      * Action : Fermez l'application $procName pour liberer le port 8088." -ForegroundColor Yellow
    $errorsCount++
} else {
    Write-Host "      [DISPONIBLE] Le port 8088 est libre et pret a etre utilise." -ForegroundColor Cyan
}
Write-Host ""

# -----------------------------------------------------------------------------
# 7. Test de Communication HTTP avec la Passerelle (http://127.0.0.1:8088/status)
# -----------------------------------------------------------------------------
Write-Host "[7/7] Test de Dialogue HTTP avec le Service Biometrique..." -ForegroundColor White
try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8088/status" -TimeoutSec 3 -ErrorAction Stop
    if ($response.connected -eq $true) {
        Write-Host "      [SUCCES TOTAL] La passerelle repond et le scanner est PRET POUR LA CAPTURE !" -ForegroundColor Green
        Write-Host "                     Modele detecte : $($response.model)" -ForegroundColor Green
        Write-Host "                     Statut service : $($response.status)" -ForegroundColor Green
    } else {
        Write-Host "      [PASSERELLE ACTIVE - SCANNER EN ATTENTE DE BRANCHEMENT]" -ForegroundColor Yellow
        Write-Host "      * Le service HTTP 8088 fonctionne parfaitement, mais le scanner RealScan-G10" -ForegroundColor Gray
        Write-Host "        n'est pas encore relie au port USB." -ForegroundColor Gray
        Write-Host "      * Action : Branchez simplement le scanner en USB. Le service le reconnaitra aussitot !" -ForegroundColor Green
        $warningsCount++
    }
} catch {
    if ($realScanProc) {
        Write-Host "      [ATTENTION] Le processus RealScanBridge tourne mais ne repond pas sur HTTP." -ForegroundColor Yellow
        Write-Host "      * Solution : Fermez le processus puis relancez '2-DEMARRER_SERVICE_EMPREINTE.bat'." -ForegroundColor Yellow
        $warningsCount++
    } else {
        Write-Host "      [INFORMATION] La passerelle n'est pas encore demarree." -ForegroundColor Gray
        Write-Host "      * Pour la lancer : Double-cliquez sur '2-DEMARRER_SERVICE_EMPREINTE.bat'." -ForegroundColor Cyan
    }
}
Write-Host ""

# -----------------------------------------------------------------------------
# BILAN GLOBAL & RECOMMANDATIONS
# -----------------------------------------------------------------------------
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host "                             BILAN DU DIAGNOSTIC                               " -ForegroundColor Yellow
Write-Host "===============================================================================" -ForegroundColor Cyan

if ($errorsCount -eq 0 -and $warningsCount -eq 0) {
    Write-Host "  RESULTAT : EXCELLENT (0 Erreur, 0 Avertissement)" -ForegroundColor Green
    Write-Host "  -> Toutes les conditions sont remplies." -ForegroundColor Green
    Write-Host "  -> Votre machine est 100% operationnelle pour l'enrolement SIADOC / CIMIS !" -ForegroundColor Green
} elseif ($errorsCount -eq 0) {
    Write-Host "  RESULTAT : PRET SOUS RESERVE ($warningsCount avertissement(s) non bloquant(s))" -ForegroundColor Yellow
    if (-not $usbDevice) {
        Write-Host "  -> POINT PRINCIPAL : Il vous suffit de brancher le scanner RealScan-G10 en USB." -ForegroundColor Yellow
    }
    Write-Host "  -> Tout le reste (pilote, DLLs, serveur) est configure correctement." -ForegroundColor Green
} else {
    Write-Host "  RESULTAT : ATTENTION REQUISE ($errorsCount erreur(s) bloquante(s))" -ForegroundColor Red
    Write-Host "  -> Consultez les actions encadrees en rouge ci-dessus pour retablir la configuration." -ForegroundColor Yellow
}
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host ""
