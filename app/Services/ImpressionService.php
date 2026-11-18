<?php
/**
 * CIMIS - Service d'Impression & Gestion des Cartes PVC (Ã‰tape 7 DÃ©couplage)
 * Centralise la recherche filtrÃ©e des personnels, la prÃ©paration des lots d'impression PVC,
 * la gestion des files d'attente de reÃ§us et la traÃ§abilitÃ© des rÃ©impressions.
 */

if (!defined('DB_NAME')) {
    require_once dirname(__DIR__, 2) . '/config/config.php';
}

if (!function_exists('buildImpressionFilterQuery')) {
    /**
     * Construit les clauses WHERE et les paramètres pour la recherche avancée multi-critères
     * Garantit une correspondance exacte et intelligente pour tous les filtres (Nom/Prénom, Matricule, Grade, Unité, Année, CNI).
     */
    function buildImpressionFilterQuery(array $filters): array {
        $where = [];
        $params = [];

        // 1. Nom / Prénom (recherche multi-mots insensible à l'ordre avec paramètres PDO uniques)
        if (!empty($filters['search_nom'])) {
            $rawNom = trim($filters['search_nom']);
            $words = array_values(array_filter(preg_split('/\s+/', $rawNom)));
            if (!empty($words)) {
                $wordClauses = [];
                foreach ($words as $idx => $word) {
                    $k1 = 's_nom_n_' . $idx;
                    $k2 = 's_nom_p_' . $idx;
                    $k3 = 's_nom_np_' . $idx;
                    $k4 = 's_nom_pn_' . $idx;
                    $wordClauses[] = "(nom LIKE :$k1 OR prenom LIKE :$k2 OR CONCAT(nom, ' ', prenom) LIKE :$k3 OR CONCAT(prenom, ' ', nom) LIKE :$k4)";
                    $params[$k1] = '%' . $word . '%';
                    $params[$k2] = '%' . $word . '%';
                    $params[$k3] = '%' . $word . '%';
                    $params[$k4] = '%' . $word . '%';
                }
                $where[] = '(' . implode(' AND ', $wordClauses) . ')';
            }
        }

        // 2. Matricule Militaire / CIM (recherche sur les deux colonnes)
        if (!empty($filters['search_matricule'])) {
            $rawMat = trim($filters['search_matricule']);
            $where[] = "(matricule LIKE :s_mat1 OR matricule_militaire LIKE :s_mat2)";
            $params['s_mat1'] = '%' . $rawMat . '%';
            $params['s_mat2'] = '%' . $rawMat . '%';
        }

        // 3. Grade Militaire (correspondance exacte et stricte par grade, insensible à la casse et aux accents)
        if (!empty($filters['search_grade'])) {
            $rawGrade = trim($filters['search_grade']);
            $cleanGrade = preg_replace('/[éèêë]/iu', 'e', $rawGrade);
            $cleanGrade = preg_replace('/[àâä]/iu', 'a', $cleanGrade);
            $cleanGrade = preg_replace('/[îï]/iu', 'i', $cleanGrade);
            $cleanGrade = preg_replace('/[ôö]/iu', 'o', $cleanGrade);
            $cleanGrade = preg_replace('/[ûüù]/iu', 'u', $cleanGrade);
            $cleanGrade = trim($cleanGrade);

            $where[] = "(LOWER(TRIM(grade)) = LOWER(TRIM(:s_grade_exact)) OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(grade), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'à', 'a')) = LOWER(TRIM(:s_grade_clean)))";
            $params['s_grade_exact'] = $rawGrade;
            $params['s_grade_clean'] = $cleanGrade;
        }

        // 4. Unité / Corps (supporte toutes les variantes réelles de BDD et SIADOC)
        if (!empty($filters['search_unite'])) {
            $uniteVal = trim($filters['search_unite']);
            if ($uniteVal === 'SIADOC') {
                $where[] = "(source_system = 'SIADOC' OR siadoc_sync_status = 'SYNCED' OR matricule_militaire LIKE 'SIA%' OR matricule_militaire LIKE '%-AT-%' OR matricule_militaire LIKE '%-GN-%' OR matricule_militaire LIKE '%-AA-%' OR matricule_militaire LIKE '%-AM-%')";
            } elseif (stripos($uniteVal, 'AIR') !== false) {
                $where[] = "(unite LIKE '%AIR%' OR unite LIKE '%Air%')";
            } elseif (stripos($uniteVal, 'TERRE') !== false) {
                $where[] = "(unite LIKE '%TERRE%' OR unite LIKE '%Terre%')";
            } elseif (stripos($uniteVal, 'GENDAR') !== false) {
                $where[] = "(unite LIKE '%GENDAR%' OR unite LIKE '%Gendar%')";
            } elseif (stripos($uniteVal, 'MARIN') !== false) {
                $where[] = "(unite LIKE '%MARIN%' OR unite LIKE '%Marin%')";
            } elseif (stripos($uniteVal, 'CIVIL') !== false) {
                $where[] = "(unite LIKE '%CIVIL%' OR unite LIKE '%Civil%')";
            } else {
                $where[] = "(unite = :s_unite OR unite LIKE :s_unite_like)";
                $params['s_unite'] = $uniteVal;
                $params['s_unite_like'] = '%' . $uniteVal . '%';
            }
        }

        // 5. Année Promotion Grade (recherche multi-format sur date_dernier_grade ET annee_dernier_galon)
        if (!empty($filters['search_annee'])) {
            $annee = trim($filters['search_annee']);
            $where[] = "(date_dernier_grade LIKE :s_annee1 OR annee_dernier_galon LIKE :s_annee2 OR date_dernier_grade LIKE :s_annee3)";
            $params['s_annee1'] = '%' . $annee . '%';
            $params['s_annee2'] = '%' . $annee . '%';
            $params['s_annee3'] = $annee . '-%';
        }

        // 6. Numéro CNI
        if (!empty($filters['search_cni'])) {
            $where[] = "numero_cni LIKE :s_cni";
            $params['s_cni'] = '%' . trim($filters['search_cni']) . '%';
        }

        return [$where, $params];
    }
}

if (!function_exists('searchPersonnelsForImpression')) {
    /**
     * Recherche avancée des personnels militaires pour l'impression avec filtres dynamiques
     */
    function searchPersonnelsForImpression(PDO $pdo, array $filters = [], int $limit = 200): array {
        list($filterWhere, $params) = buildImpressionFilterQuery($filters);
        $where = array_merge(["(supprimer = 0 OR supprimer IS NULL)"], $filterWhere);

        $sql = "
            SELECT id, matricule, matricule_militaire, nom, prenom, unite, grade, photo, 
                   numero_cni, date_dernier_grade, suspendus, statut_militaire, 
                   date_changement_statut, motif_changement_statut, autorite_changement_statut, 
                   nb_reimpressions, date_derniere_reimpression 
            FROM candidat 
            WHERE " . implode(' AND ', $where) . " 
            ORDER BY id DESC 
            LIMIT " . (int)$limit;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur recherche personnels impression: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getCandidatsByMatricules')) {
    /**
     * RÃ©cupÃ¨re les fiches complÃ¨tes des candidats par liste de matricules
     */
    function getCandidatsByMatricules(PDO $pdo, $matricules): array {
        if (is_string($matricules)) {
            $matricules = explode(',', $matricules);
        }
        $matriculesArray = array_values(array_filter(array_map('trim', (array)$matricules)));

        if (empty($matriculesArray)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($matriculesArray) - 1) . '?';
        $params = array_merge($matriculesArray, $matriculesArray);

        $sql = "
            SELECT * FROM candidat 
            WHERE (matricule IN ($placeholders) OR matricule_militaire IN ($placeholders))
              AND (supprimer = 0 OR supprimer IS NULL)
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur getCandidatsByMatricules: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('recordImpressionBatch')) {
    /**
     * Met à jour le statut carte_imprimee, incrémente les compteurs et enregistre l'historique et l'audit
     */
    function recordImpressionBatch(PDO $pdo, array $candidats, string $operator = '', string $typeImpression = 'PVC_CR80'): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $operator = $operator ?: ($_SESSION['username'] ?? 'ADMIN_IMPRESSION');
        $ip = function_exists('cimis_get_client_ip') ? cimis_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if (!isset($_SESSION['pending_receipts'])) {
            $_SESSION['pending_receipts'] = [];
        }

        $ids = [];
        $stHist = $pdo->prepare("
            INSERT INTO historique_impressions (candidat_id, matricule, nom_complet, date_impression, imprime_par, ip_machine, type_impression, motif_reimpression)
            VALUES (?, ?, ?, NOW(), ?, ?, ?, 'Impression carte officielle')
        ");

        foreach ($candidats as $cand) {
            if (!empty($cand['id'])) {
                $c_id = (int)$cand['id'];
                $ids[] = $c_id;
                if (!in_array($c_id, $_SESSION['pending_receipts'], true)) {
                    $_SESSION['pending_receipts'][] = $c_id;
                }

                // Insertion dans historique_impressions
                try {
                    $mat = $cand['matricule_militaire'] ?: ($cand['matricule'] ?? 'N/A');
                    $nomComplet = trim(($cand['nom'] ?? '') . ' ' . ($cand['prenom'] ?? ''));
                    $stHist->execute([$c_id, $mat, $nomComplet, $operator, $ip, $typeImpression]);
                } catch (Exception $eHist) {
                    error_log("Erreur insertion historique_impressions: " . $eHist->getMessage());
                }
            }
        }

        if (!empty($ids)) {
            try {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $pdo->prepare("
                    UPDATE candidat 
                    SET carte_imprimee = 1,
                        nombre_impressions = COALESCE(nombre_impressions, 0) + 1,
                        nb_reimpressions = COALESCE(nb_reimpressions, 0) + 1, 
                        derniere_impression = NOW(),
                        date_derniere_reimpression = NOW() 
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute($ids);

                // Journalisation globale MINDEF (Qui a fait quoi)
                if (function_exists('cimis_audit_log')) {
                    $countC = count($ids);
                    $desc = "Tirage de $countC carte(s) ($typeImpression) par l'opérateur $operator";
                    cimis_audit_log($pdo, 'CARTE_IMPRESSION', $desc, 'IMPRESSION', null, $desc, null, $operator, $ip);
                }

                // Notification Webhook automatique vers SIADOC
                if (!function_exists('notifySiadocCarteImprimee')) {
                    $siadocServicePath = __DIR__ . '/SiadocApiService.php';
                    if (file_exists($siadocServicePath)) {
                        require_once $siadocServicePath;
                    }
                }
                if (function_exists('notifySiadocCarteImprimee') && !empty($candidatsToHist)) {
                    foreach ($candidatsToHist as $cand) {
                        $matMil = trim($cand['matricule_militaire'] ?? '');
                        $numCarte = trim($cand['matricule'] ?? '');
                        if (!empty($matMil)) {
                            notifySiadocCarteImprimee($matMil, $numCarte, '', 'Vaguemestre DRH / Direction', 'Atelier Impression CIMIS');
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Erreur mise à jour compteurs impression: " . $e->getMessage());
            }
        }

        return $ids;
    }
}

if (!function_exists('softDeleteMultipleCandidats')) {
    /**
     * Déplace une sélection de candidats vers la corbeille (supprimer = 1)
     */
    function softDeleteMultipleCandidats(PDO $pdo, array $ids, string $operator = ''): int {
        $cleanIds = array_filter(array_map('intval', $ids));
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = str_repeat('?,', count($cleanIds) - 1) . '?';
        $params = array_merge([$operator ?: 'ADMIN'], $cleanIds);

        $sql = "UPDATE candidat SET supprimer = 1, supprime_par = ?, date_suppression = NOW() WHERE id IN ($placeholders)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->rowCount();
            if ($count > 0 && file_exists(__DIR__ . '/SecurityService.php')) {
                require_once __DIR__ . '/SecurityService.php';
                logSecurityActivity($pdo, null, 'SUPPRESSION_CANDIDAT', "$count carte(s) déplacée(s) vers la corbeille par $operator");
            }
            return $count;
        } catch (PDOException $e) {
            error_log("Erreur suppression multiple: " . $e->getMessage());
            return 0;
        }
    }
}