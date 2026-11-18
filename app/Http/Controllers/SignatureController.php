<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SignatureService;
use PDO;

require_once __DIR__ . '/../../Services/SignatureService.php';
require_once __DIR__ . '/../../Services/SecurityService.php';
require_once __DIR__ . '/../../Services/AuthService.php';

class SignatureController extends Controller
{
    public function index(Request $request)
    {
        cimis_start_session_safe();

        // 1. Authentification requise
        if (empty($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            return redirect()->route('login');
        }

        // 2. Contrôle d'accès strict Super Admin
        $userRole = $_SESSION['role'] ?? '';
        if ($userRole !== 'SUPER_ADMIN') {
            return redirect('/cimcim/dashboard')->with('error', 'Accès refusé : Ce module de souveraineté étatique est strictement réservé au Super Administrateur.');
        }

        global $pdo;
        if (!$pdo) {
            require_once base_path('config/config.php');
        }

        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $username = (string)($_SESSION['username'] ?? 'SUPER_ADMIN');
        $ip       = $request->ip() ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agent    = $request->userAgent() ?? $_SERVER['HTTP_USER_AGENT'] ?? 'CIMIS Security Agent';

        $error = null;
        $success = null;

        // 3. Traitement des requêtes POST
        if ($request->isMethod('post')) {
            $submittedToken = $request->input('_token') ?? $request->input('csrf_token') ?? $request->header('X-CSRF-TOKEN') ?? '';
            $csrfValid = cimis_verify_csrf_token($submittedToken);
            if (!$csrfValid && $request->hasSession()) {
                $sessionToken = $request->session()->token();
                if (!empty($sessionToken) && hash_equals($sessionToken, (string)$submittedToken)) {
                    $csrfValid = true;
                }
            }
            if (!$csrfValid && function_exists('csrf_token')) {
                $cToken = csrf_token();
                if (!empty($cToken) && hash_equals((string)$cToken, (string)$submittedToken)) {
                    $csrfValid = true;
                }
            }
            if (!$csrfValid) {
                $error = "Échec de validation de sécurité : Jeton CSRF invalide ou expiré. Veuillez recharger la page.";
            } else {
                $action = $request->input('action', '');

                // ==========================================
                // A. TÉLÉVERSEMENT D'UNE NOUVELLE SIGNATURE
                // ==========================================
                if ($action === 'upload_signature') {
                    $type = $request->input('signature_type', '');
                    if (!in_array($type, ['mindef', 'drh'], true)) {
                        $error = "Type d'autorité invalide (Doit être 'mindef' ou 'drh').";
                    } else {
                        $password = $request->input('superadmin_password', '');
                        if (!SignatureService::verifySuperAdminPassword($pdo, $userId, $password)) {
                            $error = "Authentification échouée : Mot de passe Super Administrateur incorrect. Action bloquée par mesure de sécurité.";
                        } else {
                            $file = $_FILES['signature_file'] ?? null;
                            $val = SignatureService::validatePngUpload($file);
                            if (!$val['ok']) {
                                $error = $val['error'];
                            } else {
                                try {
                                    $autoRemoveWhite = (bool)$request->input('auto_remove_white', 1);
                                    $res = SignatureService::processAndDeploySignature($file['tmp_name'], $type, $autoRemoveWhite);

                                    // Mise à jour du nom et titre du signataire si renseignés
                                    $cfg = SignatureService::getConfig();
                                    $newName = trim((string)$request->input('signatory_name', ''));
                                    $newTitle = trim((string)$request->input('signatory_title', ''));

                                    if ($newName !== '') {
                                        $cfg[$type]['name'] = $newName;
                                    }
                                    if ($newTitle !== '') {
                                        $cfg[$type]['title'] = $newTitle;
                                    }
                                    $cfg[$type]['updated_at'] = date('Y-m-d H:i:s');
                                    $cfg[$type]['updated_by'] = $username;
                                    SignatureService::saveConfig($cfg);

                                    // Journalisation d'audit de sécurité
                                    $label = ($type === 'mindef') ? 'MINDEF (Ministre de la Défense)' : 'DRH (Directeur RH)';
                                    $details = "Remplacement réussi de la signature officielle [$label]. Résolution: {$res['dimensions']}. SHA256: {$res['sha256']}. Nom officiel: " . ($newName ?: $cfg[$type]['name']);
                                    SignatureService::logAudit($pdo, $userId, $username, 'REMPLACEMENT_SIGNATURE_OFFICIELLE', $details, $ip, $agent);

                                    $success = "La signature officielle pour $label a été téléversée, assainie, dorée et déployée avec succès sur les cartes militaires !";
                                } catch (\Throwable $e) {
                                    $error = "Erreur lors du traitement de la signature : " . $e->getMessage();
                                }
                            }
                        }
                    }
                }

                // ==========================================
                // B. MISE À JOUR DES LIBELLÉS DU SIGNATAIRE
                // ==========================================
                elseif ($action === 'update_signatory_info') {
                    $type = $request->input('signature_type', '');
                    if (!in_array($type, ['mindef', 'drh'], true)) {
                        $error = "Type d'autorité invalide.";
                    } else {
                        $password = $request->input('superadmin_password', '');
                        if (!SignatureService::verifySuperAdminPassword($pdo, $userId, $password)) {
                            $error = "Authentification échouée : Mot de passe Super Administrateur incorrect.";
                        } else {
                            $newName = trim((string)$request->input('signatory_name', ''));
                            $newTitle = trim((string)$request->input('signatory_title', ''));

                            if ($newName === '' && $newTitle === '') {
                                $error = "Veuillez renseigner au moins le nom ou le titre de l'autorité.";
                            } else {
                                $cfg = SignatureService::getConfig();
                                $oldName = $cfg[$type]['name'] ?? '';
                                if ($newName !== '') $cfg[$type]['name'] = $newName;
                                if ($newTitle !== '') $cfg[$type]['title'] = $newTitle;
                                $cfg[$type]['updated_at'] = date('Y-m-d H:i:s');
                                $cfg[$type]['updated_by'] = $username;
                                SignatureService::saveConfig($cfg);

                                $label = ($type === 'mindef') ? 'MINDEF' : 'DRH';
                                $details = "Mise à jour des mentions textuelles de l'autorité [$label] : Ancien nom: '$oldName' -> Nouveau nom: '{$cfg[$type]['name']}'. Titre: '{$cfg[$type]['title']}'";
                                SignatureService::logAudit($pdo, $userId, $username, 'MODIFICATION_IDENTITE_SIGNATAIRE', $details, $ip, $agent);

                                $success = "Les informations officielles du signataire ($label) ont été mises à jour avec succès.";
                            }
                        }
                    }
                }

                // ==========================================
                // C. RESTAURATION D'UNE ANCIENNE VERSION
                // ==========================================
                elseif ($action === 'restore_archive') {
                    $archiveFilename = $request->input('archive_filename', '');
                    $type = $request->input('signature_type', '');
                    $password = $request->input('superadmin_password', '');

                    if (!SignatureService::verifySuperAdminPassword($pdo, $userId, $password)) {
                        $error = "Authentification échouée : Mot de passe Super Administrateur incorrect pour autoriser la restauration.";
                    } else {
                        try {
                            SignatureService::restoreArchive($archiveFilename, $type);
                            $label = ($type === 'mindef') ? 'MINDEF' : 'DRH';
                            $details = "Restauration d'une version précédente de la signature [$label] depuis l'archive '$archiveFilename'.";
                            SignatureService::logAudit($pdo, $userId, $username, 'RESTAURATION_SIGNATURE_ARCHIVEE', $details, $ip, $agent);

                            $success = "La signature $label a été restaurée avec succès depuis l'archive ($archiveFilename).";
                        } catch (\Throwable $e) {
                            $error = "Erreur lors de la restauration : " . $e->getMessage();
                        }
                    }
                }
            }
        }

        $signatures = SignatureService::getSignaturesDetails();
        $archives   = SignatureService::getArchivesList();

        return view('admin.signatures.index', [
            'signatures' => $signatures,
            'archives'   => $archives,
            'error'      => $error,
            'success'    => $success,
            'username'   => $username
        ]);
    }
}
