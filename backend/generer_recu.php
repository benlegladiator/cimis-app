<?php
/**
 * REÇU OFFICIEL DE DÉLIVRANCE DE CARTE MILITAIRE (MINDEF - CIMIS 2.0)
 * Format A4 Individuel (1 reçu) ou Planche A4 Compacte (4 reçus découpables par page)
 */
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qrcode_generator.php';

// Authentification
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$id_single   = $_GET['id'] ?? $_GET['candidat_id'] ?? null;
$mat_single  = $_GET['matricule'] ?? null;
$ids_raw     = $_GET['ids'] ?? $_POST['ids'] ?? null;
$mode        = $_GET['mode'] ?? 'single'; // 'single' ou 'batch'

$candidats = [];

global $pdo;
if (isset($pdo)) {
    if (!empty($ids_raw)) {
        $id_list = is_array($ids_raw) ? $ids_raw : explode(',', $ids_raw);
        $id_list = array_filter(array_map('intval', $id_list));
        if (!empty($id_list)) {
            $in  = str_repeat('?,', count($id_list) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM candidat WHERE id IN ($in) AND supprimer = 1");
            $stmt->execute(array_values($id_list));
            $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (!empty($id_single)) {
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE id = ? AND supprimer = 1 LIMIT 1");
        $stmt->execute([(int)$id_single]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c) $candidats[] = $c;
    } elseif (!empty($mat_single)) {
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule_militaire = ? OR matricule = ?) AND supprimer = 1 LIMIT 1");
        $stmt->execute([$mat_single, $mat_single]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c) $candidats[] = $c;
    }
}

if (empty($candidats)) {
    die("<h3>Aucun militaire trouvé pour la génération du reçu.</h3><p><a href='../Frontend/impression.php'>Retourner à la liste</a></p>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Délivrance - CIMIS MINDEF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Garamond', 'Times New Roman', serif;
            background: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        .no-print-bar {
            max-width: 1000px;
            margin: 0 auto 20px auto;
            background: #1e293b;
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .btn-print {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-back {
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: bold;
        }

        /* --- PAGE A4 SINGLE --- */
        .page-a4 {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 0 auto 30px auto;
            padding: 20mm 15mm;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            position: relative;
        }

        /* --- BATCH LAYOUT (4 REÇUS PAR PAGE A4) --- */
        .page-a4-batch {
            width: 210mm;
            height: 297mm;
            background: white;
            margin: 0 auto 30px auto;
            padding: 8mm;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 8mm;
            page-break-after: always;
        }

        .receipt-mini {
            border: 2px dashed #94a3b8;
            border-radius: 8px;
            padding: 10px;
            background: #fafafa;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header-mindef {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-mindef h2 { margin: 0; font-size: 1.1rem; color: #1e3a8a; }
        .header-mindef h3 { margin: 2px 0; font-size: 0.95rem; }
        .header-mindef p { margin: 0; font-size: 0.75rem; color: #475569; }

        .receipt-title {
            text-align: center;
            background: #1e293b;
            color: #d4af37;
            padding: 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .identity-table td {
            padding: 4px 6px;
            border-bottom: 1px dotted #cbd5e1;
        }

        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 0.75rem;
            text-align: center;
        }

        .signature-box {
            width: 45%;
            border-top: 1px solid #0f172a;
            padding-top: 5px;
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { background: none; padding: 0; }
            .page-a4, .page-a4-batch { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <h3 style="margin: 0; color: #d4af37;"><i class="fa-solid fa-file-invoice"></i> REÇUS DE DÉLIVRANCE CARTE MILITAIRE</h3>
            <p style="margin: 3px 0 0 0; font-size: 0.85rem; color: #94a3b8;"><?php echo count($candidats); ?> militaire(s) sélectionné(s) • Format A4 Imprimable</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="../Frontend/impression.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> IMPRIMER LES REÇUS A4</button>
        </div>
    </div>

    <?php if ($mode === 'batch' || count($candidats) > 1): ?>
        <!-- IMPRESSION EN PLANCHE A4 COMPACTE (4 REÇUS PAR PAGE) -->
        <?php $chunks = array_chunk($candidats, 4); ?>
        <?php foreach ($chunks as $chunk): ?>
            <div class="page-a4-batch">
                <?php foreach ($chunk as $c): ?>
                    <?php 
                    $mat = $c['matricule_militaire'] ?? $c['matricule'];
                    $recu_no = 'R-CIMIS-' . date('Ymd') . '-' . strtoupper(substr(md5($mat), 0, 5));
                    $qr_path = generateQRCodeForMatricule($mat, $c);
                    ?>
                    <div class="receipt-mini">
                        <div>
                            <div class="header-mindef">
                                <h2>RÉPUBLIQUE DU CAMEROUN</h2>
                                <p>Paix - Travail - Patrie</p>
                                <h3>MINISTÈRE DE LA DÉFENSE</h3>
                                <p style="font-weight: bold;">RÉCÉPISSÉ DE DÉLIVRANCE DE CARTE MILITAIRE</p>
                            </div>

                            <div style="font-size: 0.75rem; text-align: right; color: #64748b; font-weight: bold; margin-bottom: 5px;">
                                N°: <?php echo $recu_no; ?>
                            </div>

                            <table class="identity-table">
                                <tr>
                                    <td><strong>Matricule:</strong></td>
                                    <td><strong style="color: #1e3a8a;"><?php echo htmlspecialchars($mat); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Nom & Prénom:</strong></td>
                                    <td><?php echo htmlspecialchars(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Grade / Corps:</strong></td>
                                    <td><?php echo htmlspecialchars(($c['grade'] ?? '') . ' • ' . ($c['unite'] ?? '')); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>CNI:</strong></td>
                                    <td><?php echo htmlspecialchars($c['numero_cni'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Date Délivrance:</strong></td>
                                    <td><?php echo date('d/m/Y H:i'); ?></td>
                                </tr>
                            </table>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                            <img src="../<?php echo ltrim($qr_path, '/'); ?>" style="width: 45px; height: 45px;" alt="QR">
                            <div class="signature-block" style="width: 75%;">
                                <div class="signature-box">Le Titulaire</div>
                                <div class="signature-box">Officier MINDEF</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- IMPRESSION REÇU A4 INDIVIDUEL GRAND FORMAT -->
        <?php $c = $candidats[0]; ?>
        <?php 
        $mat = $c['matricule_militaire'] ?? $c['matricule'];
        $recu_no = 'R-CIMIS-' . date('Ymd') . '-' . strtoupper(substr(md5($mat), 0, 6));
        $qr_path = generateQRCodeForMatricule($mat, $c);
        $photo_clean = !empty($c['photo']) ? preg_replace('/^(\.\.\/)+/', '', $c['photo']) : '';
        $photo_src = !empty($photo_clean) ? '../' . $photo_clean : '../img/candidats/default.svg';
        ?>
        <div class="page-a4">
            <div class="header-mindef" style="padding-bottom: 20px; margin-bottom: 25px;">
                <h1 style="margin: 0; font-size: 1.6rem; color: #1e3a8a;">RÉPUBLIQUE DU CAMEROUN</h1>
                <p style="font-size: 0.9rem; margin: 3px 0;">Paix - Travail - Patrie</p>
                <h2 style="font-size: 1.3rem; margin: 5px 0;">MINISTÈRE DE LA DÉFENSE</h2>
                <p style="font-size: 0.85rem; color: #475569;">DIRECTION DES PERSONNELS ET DE LA SÉCURITÉ BIOMÉTRIQUE</p>
            </div>

            <div class="receipt-title" style="font-size: 1.2rem; padding: 10px;">
                REÇU OFFICIEL DE DÉLIVRANCE DE CARTE D'IDENTITÉ MILITAIRE
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 0.9rem; color: #475569;">
                <div><strong>N° Récépissé :</strong> <span style="font-family: monospace; font-size: 1rem; color: #1e3a8a;"><?php echo $recu_no; ?></span></div>
                <div><strong>Date d'Émission :</strong> <?php echo date('d/m/Y à H:i:s'); ?></div>
            </div>

            <div style="display: flex; gap: 25px; margin-bottom: 30px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px;">
                <img src="<?php echo htmlspecialchars($photo_src); ?>" alt="Photo Titulaire" style="width: 110px; height: 130px; object-fit: cover; border-radius: 8px; border: 2px solid #1e3a8a;" onerror="this.src='../img/candidats/default.svg';">
                <table class="identity-table" style="font-size: 1rem; margin-bottom: 0;">
                    <tr>
                        <td style="width: 35%;"><strong>Matricule Militaire :</strong></td>
                        <td><strong style="color: #1e3a8a; font-size: 1.1rem;"><?php echo htmlspecialchars($mat); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Nom & Prénom :</strong></td>
                        <td><strong style="text-transform: uppercase;"><?php echo htmlspecialchars(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Grade & Armée :</strong></td>
                        <td><?php echo htmlspecialchars(($c['grade'] ?? '') . ' • ' . ($c['unite'] ?? '')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Carte Nationale d'Identité :</strong></td>
                        <td><?php echo htmlspecialchars($c['numero_cni'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Statut de la Carte :</strong></td>
                        <td><span style="background: #10b981; color: white; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">DELIVRÉE ET ACTIVE</span></td>
                    </tr>
                </table>
            </div>

            <div style="background: #fffbebf8; border: 1px dashed #d4af37; padding: 15px; border-radius: 8px; margin-bottom: 40px; font-size: 0.85rem; color: #78350f;">
                <strong>ATTESTATION DE DÉLIVRANCE :</strong> Le Ministère de la Défense atteste par le présent document que la carte d'identité militaire associée au matricule <strong><?php echo htmlspecialchars($mat); ?></strong> a été confectionnée, vérifiée biométriquement et remise à son titulaire. Ce reçu fait foi de preuve manuscrite et numérique.
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 50px;">
                <div style="text-align: center;">
                    <img src="../<?php echo ltrim($qr_path, '/'); ?>" style="width: 90px; height: 90px; display: block; margin: 0 auto 5px auto;" alt="QR Code">
                    <span style="font-size: 0.75rem; color: #64748b;">Contrôle Biométrique QR</span>
                </div>
                <div class="signature-block" style="width: 70%; font-size: 0.9rem;">
                    <div class="signature-box" style="padding-top: 10px;">
                        <strong>Empreinte & Signature du Titulaire</strong>
                        <div style="height: 60px;"></div>
                    </div>
                    <div class="signature-box" style="padding-top: 10px;">
                        <strong>Pour le Ministre de la Défense</strong><br>
                        <small>L'Officier Émetteur Habilité</small>
                        <div style="height: 60px;"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>
