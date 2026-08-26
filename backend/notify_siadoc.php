<?php
/**
 * MODULE DE NOTIFICATION WEBHOOK SIADOC ↔ CIMIS
 * Conforme aux spécifications officielles de l'équipe SIADOC (v2026)
 */
require_once __DIR__ . '/config.php';

/**
 * Notifie SIADOC en temps réel lors d'un changement d'état d'une carte militaire (Activation / Désactivation).
 *
 * @param string $matricule Le matricule militaire officiel
 * @param string $statut 'ACTIVE' ou 'DESACTIVE'
 * @param string $motif Raison du changement d'état (ex: "Perte de la carte", "Réactivation administrative", etc.)
 * @return array Résultat détaillé avec code HTTP et réponse SIADOC
 */
function notifierSiadocStatutCarte($matricule, $statut = 'DESACTIVE', $motif = 'Changement de statut CIMIS') {
    if (empty($matricule)) {
        return ['success' => false, 'error' => 'Matricule obligatoire manquant'];
    }

    // Normalisation stricte du statut exigée par SIADOC : "ACTIVE" ou "DESACTIVE"
    $statut_upper = strtoupper(trim($statut));
    if ($statut_upper === 'ACTIVE' || $statut_upper === 'ACTIF' || $statut === 0 || $statut === '0') {
        $statut_norm = 'ACTIVE';
    } else {
        $statut_norm = 'DESACTIVE';
    }

    $url     = defined('SIADOC_WEBHOOK_URL') ? SIADOC_WEBHOOK_URL : 'https://siadoc.onrender.com/api/cimis/webhook/statut-carte';
    $api_key = defined('SIADOC_API_KEY')     ? SIADOC_API_KEY     : 'cimis-demo-token-2026';

    $payload = [
        'matricule' => (string)$matricule,
        'statut'    => $statut_norm,
        'motif'      => (string)$motif
    ];

    $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json_payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $api_key
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response   = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $success = ($http_code === 200);

    // Journalisation dans la table d'activité BDD
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (NULL, :action, :desc, :ip)");
            $stmt->execute([
                'action' => 'NOTIF_SIADOC_' . $statut_norm,
                'desc'   => "Statut: {$statut_norm} | Mat: {$matricule} | HTTP: {$http_code} | Response: " . substr($response, 0, 150),
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {}
    }

    return [
        'success'   => $success,
        'http_code' => $http_code,
        'response'  => $response,
        'payload'   => $payload,
        'error'     => $curl_error ?: ($success ? null : 'Erreur HTTP ' . $http_code)
    ];
}
