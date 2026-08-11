-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Hôte : sql113.infinityfree.com
-- Généré le :  mar. 14 avr. 2026 à 06:44
-- Version du serveur :  11.4.10-MariaDB
-- Version de PHP :  7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `if0_39882531_cimis`
--

-- --------------------------------------------------------

--
-- Structure de la table `api_sync_log`
--

CREATE TABLE `api_sync_log` (
  `id` int(11) NOT NULL,
  `system` varchar(100) NOT NULL COMMENT 'Nom du système externe',
  `last_sync` datetime NOT NULL COMMENT 'Date de dernière synchronisation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Journal des synchronisations API';

-- --------------------------------------------------------

--
-- Structure de la table `candidat`
--

CREATE TABLE `candidat` (
  `id` int(11) NOT NULL,
  `matricule` varchar(20) DEFAULT NULL,
  `matricule_militaire` varchar(20) DEFAULT NULL COMMENT 'Matricule militaire (optionnel pour civils)',
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL DEFAULT '1990-01-01' COMMENT 'Date de naissance du candidat',
  `sexe` enum('MASCULIN','FEMININ') NOT NULL,
  `type_personnel` enum('MILITAIRE','CIVIL') NOT NULL DEFAULT 'MILITAIRE' COMMENT 'Type de personnel (militaire ou civil)',
  `numero_cni` varchar(255) DEFAULT NULL,
  `date_enrolement` date DEFAULT current_timestamp(),
  `date_creation_carte` datetime DEFAULT NULL COMMENT 'Date de création de la carte',
  `date_expiration_carte` date DEFAULT NULL COMMENT 'Date d''expiration de la carte',
  `statut_carte` enum('ACTIVE','EXPIREE','SUSPENDUE','PERDUE','RENOUVELEE') DEFAULT 'ACTIVE' COMMENT 'Statut actuel de la carte',
  `motif_suspension` text DEFAULT NULL COMMENT 'Motif de suspension de la carte',
  `date_dernier_grade` date DEFAULT NULL,
  `suspendus` tinyint(1) DEFAULT 0,
  `grade` varchar(255) DEFAULT NULL,
  `fonction` varchar(255) DEFAULT NULL COMMENT 'Fonction pour le personnel civil',
  `direction` varchar(255) DEFAULT NULL COMMENT 'Direction/Service pour le personnel civil',
  `unite` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `taille` varchar(10) DEFAULT NULL COMMENT 'Taille en cm',
  `poids` varchar(10) DEFAULT NULL COMMENT 'Poids en kg',
  `groupe_sanguin` varchar(5) DEFAULT NULL COMMENT 'Groupe sanguin',
  `code_qr` varchar(255) DEFAULT NULL COMMENT 'Code QR unique',
  `empreinte_data` text DEFAULT NULL COMMENT 'Données empreinte digitale',
  `annee_dernier_galon` year(4) DEFAULT NULL COMMENT 'Année du dernier galon',
  `source_system` varchar(100) DEFAULT 'CIMIS' COMMENT 'Système source des données',
  `date_modification` datetime DEFAULT NULL COMMENT 'Date de dernière modification',
  `sync_status` enum('pending','synced','error') DEFAULT 'pending' COMMENT 'Statut de synchronisation',
  `nb_reimpressions` int(11) DEFAULT 0 COMMENT 'Nombre de fois que la carte a été réimprimée',
  `date_derniere_reimpression` date DEFAULT NULL COMMENT 'Date de la dernière réimpression'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidat`
--

INSERT INTO `candidat` (`id`, `matricule`, `matricule_militaire`, `nom`, `prenom`, `date_naissance`, `sexe`, `type_personnel`, `numero_cni`, `date_enrolement`, `date_creation_carte`, `date_expiration_carte`, `statut_carte`, `motif_suspension`, `date_dernier_grade`, `suspendus`, `grade`, `fonction`, `direction`, `unite`, `photo`, `taille`, `poids`, `groupe_sanguin`, `code_qr`, `empreinte_data`, `annee_dernier_galon`, `source_system`, `date_modification`, `sync_status`, `nb_reimpressions`, `date_derniere_reimpression`) VALUES
(270, 'CIM-65098', '47842', 'DJIEGOUE SINDJU', 'NOELLIE GRACE', '1999-05-12', 'FEMININ', 'MILITAIRE', 'NLNL4564K', '2026-04-02', NULL, NULL, 'ACTIVE', NULL, '2021-01-01', 0, 'LIEUTENANT COLONEL', NULL, NULL, 'GENDARMERIE', 'img/candidats/CIM-65098_1775130515.PNG', '150', '65', 'A-', 'img/qrcodes/47842_qr.png', NULL, 2021, 'CIMIS', NULL, 'pending', 0, NULL),
(271, 'CIM-30377', 'A14/748544', 'ESOLA MBIDA', 'JOELLE ROSE', '2000-02-18', 'FEMININ', 'MILITAIRE', 'NLNKN4879', '2026-04-02', NULL, NULL, 'ACTIVE', NULL, '2020-01-01', 0, 'ADJUDANT CHEF MAJOR', NULL, NULL, 'ARMÉE DE L\'AIR', 'img/candidats/CIM-30377_1775130619.jpg', '165', '70', 'B+', 'img/qrcodes/A14_748544_qr.png', NULL, 2020, 'CIMIS', NULL, 'pending', 0, NULL),
(274, 'CIM-11235', 'M14/757654', 'TALLA BIAR', 'THOMA RAN', '1988-12-10', 'MASCULIN', 'MILITAIRE', 'KJNBKUH45', '2026-04-02', NULL, NULL, 'ACTIVE', NULL, '2015-01-01', 0, 'QUARTIER MAITRE DE 1ERE CLASSE', NULL, NULL, 'MARINE NATIONALE', 'img/candidats/CIM-11235_1775131023.PNG', '190', '85', 'A-', 'img/qrcodes/M14_757654_qr.png', NULL, 2015, 'CIMIS', NULL, 'pending', 0, NULL),
(275, 'CIM-55741', '7945', 'SALAM ZOA', 'IBRAHIM', '0001-11-10', 'MASCULIN', 'MILITAIRE', 'FGDS789514', '2026-04-02', NULL, NULL, 'ACTIVE', NULL, '2020-01-01', 0, 'MARECHAL DES LOGIS CHEF', NULL, NULL, 'GENDARMERIE', 'img/candidats/CIM-55741_1775131286.PNG', '180', '85', 'AB+', 'img/qrcodes/7945_qr.png', NULL, 2020, 'CIMIS', NULL, 'pending', 0, NULL),
(276, 'CIM-24669', 'A06/1454', 'ENGAMBA ZEH', 'ANDRE PATRICK', '1995-01-12', 'MASCULIN', 'MILITAIRE', 'RLF416578', '2026-04-02', NULL, NULL, 'ACTIVE', NULL, '2026-01-01', 0, 'LIEUTENANT', NULL, NULL, 'ARMÉE DE L\'AIR', 'img/candidats/CIM-24669_1775136588.jpg', '185', '86', 'A+', 'img/qrcodes/A06_1454_qr.png', NULL, 2026, 'CIMIS', NULL, 'pending', 0, NULL),
(277, 'CIM-66325', 'T11/450943', 'NTSAMA BALLA', 'JEAN-MARIE', '1990-04-07', 'MASCULIN', 'MILITAIRE', '4658278VV', '2026-04-07', NULL, NULL, 'ACTIVE', NULL, '2020-01-01', 0, 'CAPITAINE', NULL, NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-66325_1775549621.jpg', '175', '70', 'AB+', 'img/qrcodes/CIM-66325_qr.png', NULL, 2020, 'CIMIS', NULL, 'pending', 0, NULL),
(278, 'CIM-52690', 'T98/23465', 'GOUFAN A RIM', 'GEORGES BERNARD', '1966-04-07', 'MASCULIN', 'MILITAIRE', 'HRBDJ1364', '2026-04-07', NULL, NULL, 'ACTIVE', NULL, '2018-01-01', 0, 'COLONEL', NULL, NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-52690_1775563786.jpg', '180', '85', 'B+', 'img/qrcodes/CIM-52690_qr.png', NULL, 2018, 'CIMIS', NULL, 'pending', 0, NULL),
(279, 'CIM-68215', 'M19/66485', 'ONANA', 'MARCELIN CHRISTIAN', '2001-03-05', 'MASCULIN', 'MILITAIRE', '46582783T', '2026-04-07', NULL, NULL, 'ACTIVE', NULL, '2024-01-01', 0, 'ENSEIGNE DE VAISSEAU 1ERE CLASSE', NULL, NULL, 'MARINE NATIONALE', 'img/candidats/CIM-68215_1775565223.PNG', '178', '77', 'O+', 'img/qrcodes/CIM-68215_qr.png', NULL, 2024, 'CIMIS', NULL, 'pending', 0, NULL),
(280, 'CIM-82549', 'T20/15499', 'MBASSI TCHOYA', 'JUDITH', '2000-12-05', 'FEMININ', 'MILITAIRE', 'FGHXFTY23', '2026-04-07', NULL, NULL, 'ACTIVE', NULL, '2023-01-01', 0, 'ASPIRANT', NULL, NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-82549_1775566489.jpg', '175', '80', 'AB+', 'img/qrcodes/CIM-82549_qr.png', NULL, 2023, 'CIMIS', NULL, 'pending', 0, NULL),
(281, 'CIM-53828', 'T11/14310', 'KINGUE MOTASSI', 'PAUL ALOïS', '1988-06-02', 'MASCULIN', 'MILITAIRE', 'AA2929111', '2026-04-09', NULL, NULL, 'ACTIVE', NULL, '2025-01-01', 0, 'ADJUDANT CHEF', NULL, NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-53828_1775748125.jpg', '167', '59', 'A+', 'img/qrcodes/CIM-53828_qr.png', NULL, 2025, 'CIMIS', NULL, 'pending', 0, NULL),
(282, 'CIM-07937', 'T21/234235', 'IEMTSA LONTCHI', 'KRISPEN', '2000-08-10', 'MASCULIN', 'MILITAIRE', '101041611', '2026-04-10', NULL, NULL, 'ACTIVE', NULL, '2022-01-01', 0, 'LIEUTENANT', NULL, NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-07937_1775820667.PNG', '185', '93', 'A+', 'img/qrcodes/CIM-07937_qr.png', NULL, 2022, 'CIMIS', NULL, 'pending', 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `demandes_impression`
--

CREATE TABLE `demandes_impression` (
  `id` int(11) NOT NULL,
  `candidat_id` int(11) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `motif_demande` enum('PERTE','VOL','USURE','CASSE','ERREUR','AUTRE') NOT NULL,
  `description_motif` text DEFAULT NULL,
  `statut` enum('EN_ATTENTE','APPROUVEE','REFUSEE','TRAITEE') DEFAULT 'EN_ATTENTE',
  `date_demande` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_traitement` timestamp NULL DEFAULT NULL,
  `traite_par` int(11) DEFAULT NULL,
  `motif_refus` text DEFAULT NULL,
  `priorite` enum('NORMAL','URGENT','TRES_URGENT') DEFAULT 'NORMAL',
  `pieces_jointes` text DEFAULT NULL,
  `commentaire_interne` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_traitements`
--

CREATE TABLE `historique_traitements` (
  `id` int(11) NOT NULL,
  `demande_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `action` enum('CREATION','APPROBATION','REFUS','IMPRESSION','LIVRAISON') NOT NULL,
  `commentaire` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `tentative_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` enum('SUCCES','ECHEC') DEFAULT 'ECHEC',
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `table_concernee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` enum('SUCCES','ERREUR','AVERTISSEMENT') DEFAULT 'SUCCES'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `notifications_demandes`
--

CREATE TABLE `notifications_demandes` (
  `id` int(11) NOT NULL,
  `demande_id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `type_notification` enum('NOUVELLE_DEMANDE','APPROUVEE','REFUSEE','TRAITEE') NOT NULL,
  `message` text NOT NULL,
  `lue` tinyint(1) DEFAULT 0,
  `date_notification` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `granted` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_derniere_activite` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_expiration` timestamp NULL DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('SUPER_ADMIN','ADMIN_ENROLEMENT','ADMIN_IMPRESSION') DEFAULT 'ADMIN_ENROLEMENT',
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_derniere_connexion` timestamp NULL DEFAULT NULL,
  `deux_factors_enabled` tinyint(1) DEFAULT 0,
  `deux_factors_secret` varchar(255) DEFAULT NULL,
  `dernier_ip` varchar(45) DEFAULT NULL,
  `nombre_echecs` int(11) DEFAULT 0,
  `compte_verrouille` tinyint(1) DEFAULT 0,
  `date_verrouillage` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `username`, `email`, `password`, `role`, `actif`, `date_creation`, `date_derniere_connexion`, `deux_factors_enabled`, `deux_factors_secret`, `dernier_ip`, `nombre_echecs`, `compte_verrouille`, `date_verrouillage`) VALUES
(15, 'super_admin_cimis', NULL, '$2y$10$L2weePsY7r/5qFSdoTTLH.79c00vkDBRjL1oezxRkc.I528ZbSFbO', 'SUPER_ADMIN', 1, '2026-03-19 04:36:17', '2026-04-14 10:38:49', 0, NULL, '127.0.0.1', 0, 0, NULL),
(16, 'admin_enrolement_cimis', NULL, '$2y$10$k.VCR4FAjapOuUVxAeXeOOb77.t5asJtNcg0lcOqoHbLO7fqDp5c2', 'ADMIN_ENROLEMENT', 1, '2026-03-19 04:36:18', '2026-04-03 12:45:26', 0, NULL, NULL, 0, 0, NULL),
(17, 'admin_impression_cimis', NULL, '$2y$10$GQPIc036/SRoxCTogLXBMOBifaKcZv29/jZo/ZEbMTRV4jfXo4Yjq', 'ADMIN_IMPRESSION', 1, '2026-03-19 04:36:18', NULL, 0, NULL, NULL, 0, 0, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `api_sync_log`
--
ALTER TABLE `api_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_system` (`system`),
  ADD KEY `idx_last_sync` (`last_sync`);

--
-- Index pour la table `candidat`
--
ALTER TABLE `candidat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `idx_source_system` (`source_system`),
  ADD KEY `idx_sync_status` (`sync_status`),
  ADD KEY `idx_date_modification` (`date_modification`),
  ADD KEY `idx_matricule_militaire` (`matricule_militaire`);

--
-- Index pour la table `demandes_impression`
--
ALTER TABLE `demandes_impression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidat_id` (`candidat_id`),
  ADD KEY `traite_par` (`traite_par`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_demande` (`date_demande`),
  ADD KEY `idx_matricule` (`matricule`),
  ADD KEY `idx_priorite` (`priorite`),
  ADD KEY `idx_demandes_complet` (`statut`,`priorite`,`date_demande`);

--
-- Index pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `idx_demande` (`demande_id`),
  ADD KEY `idx_date_action` (`date_action`),
  ADD KEY `idx_historique_complet` (`demande_id`,`date_action`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_adresse`),
  ADD KEY `idx_tentative_time` (`tentative_time`);

--
-- Index pour la table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_logs_date` (`date_action`),
  ADD KEY `idx_logs_action` (`action`);

--
-- Index pour la table `notifications_demandes`
--
ALTER TABLE `notifications_demandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demande_id` (`demande_id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_lue` (`lue`),
  ADD KEY `idx_date_notification` (`date_notification`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`role`,`module`,`action`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session` (`session_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `api_sync_log`
--
ALTER TABLE `api_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `candidat`
--
ALTER TABLE `candidat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT pour la table `demandes_impression`
--
ALTER TABLE `demandes_impression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT pour la table `notifications_demandes`
--
ALTER TABLE `notifications_demandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `demandes_impression`
--
ALTER TABLE `demandes_impression`
  ADD CONSTRAINT `demandes_impression_ibfk_1` FOREIGN KEY (`candidat_id`) REFERENCES `candidat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_impression_ibfk_2` FOREIGN KEY (`traite_par`) REFERENCES `utilisateur` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  ADD CONSTRAINT `historique_traitements_ibfk_1` FOREIGN KEY (`demande_id`) REFERENCES `demandes_impression` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_traitements_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications_demandes`
--
ALTER TABLE `notifications_demandes`
  ADD CONSTRAINT `notifications_demandes_ibfk_1` FOREIGN KEY (`demande_id`) REFERENCES `demandes_impression` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_demandes_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;

-- ===================================================
-- AMÉLIORATIONS DES TABLES POUR LA GESTION SÉCURITÉ
-- ===================================================

-- Mise à jour de la table utilisateur existante
-- Ajout du champ actif si manquant (pour compatibilité)
ALTER TABLE `utilisateur` 
ADD COLUMN IF NOT EXISTS `actif` tinyint(1) DEFAULT 1 
COMMENT '1=actif, 0=inactif/désactivé';

-- Ajout du champ motif_desactivation pour stocker le motif de désactivation
ALTER TABLE `utilisateur` 
ADD COLUMN IF NOT EXISTS `motif_desactivation` TEXT DEFAULT NULL 
COMMENT 'Motif de désactivation du compte utilisateur';

-- Ajout du champ statut_militaire à la table candidat
ALTER TABLE `candidat` 
ADD COLUMN IF NOT EXISTS `statut_militaire` 
ENUM(
    'ACTIF', 'EN_MISSION', 'EN_FORMATION', 'EN_PERMISSION', 'EN_CONGE',
    'SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 
    'EN_ATTENTE_AFFECTATION', 'EN_RETRAITE_TRANSITOIRE',
    'DESERTEUR', 'REVOQUE', 'DEMISSIONNAIRE', 'RETRAITE', 'DECES',
    'RESERVE', 'RESERVE_ACTIVE', 'HONORAIRE', 'CONTRACTUEL', 'CIVIL'
) 
DEFAULT 'ACTIF' 
COMMENT 'Statut militaire du personnel';

-- Ajout des champs complémentaires pour le suivi des changements de statut
ALTER TABLE `candidat` 
ADD COLUMN IF NOT EXISTS `date_changement_statut` DATE DEFAULT NULL 
COMMENT 'Date du dernier changement de statut',
ADD COLUMN IF NOT EXISTS `motif_changement_statut` TEXT DEFAULT NULL 
COMMENT 'Motif du changement de statut',
ADD COLUMN IF NOT EXISTS `autorite_changement_statut` VARCHAR(255) DEFAULT NULL 
COMMENT 'Autorité ayant validé le changement';

-- Création de la table activity_log pour le suivi des actions administratives
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'Type d\'action (DESACTIVATION_UTILISATEUR, REACTIVATION_UTILISATEUR, etc.)',
  `description` text DEFAULT NULL COMMENT 'Description détaillée de l\'action',
  `ip_address` varchar(45) DEFAULT NULL,
  `date_action` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `date_action` (`date_action`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mise à jour des données existantes - tous les candidats actifs par défaut
UPDATE `candidat` SET `statut_militaire` = 'ACTIF' WHERE `statut_militaire` IS NULL;

-- Création d'index pour optimiser les recherches par statut
ALTER TABLE `candidat` ADD INDEX IF NOT EXISTS `idx_statut_militaire` (`statut_militaire`);
ALTER TABLE `candidat` ADD INDEX IF NOT EXISTS `idx_suspendus` (`suspendus`);
ALTER TABLE `candidat` ADD INDEX IF NOT EXISTS `idx_statut_carte` (`statut_carte`);

-- ===================================================
-- TRIGGERS POUR SYNCHRONISATION AUTOMATIQUE
-- ===================================================

-- Trigger pour synchroniser statut_militaire -> suspendus
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `sync_statut_to_suspendus`
BEFORE UPDATE ON `candidat`
FOR EACH ROW
BEGIN
    -- Si le statut militaire change vers un statut de suspension
    IF NEW.statut_militaire IN ('SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 'DESERTEUR', 'REVOQUE') 
       AND OLD.statut_militaire NOT IN ('SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 'DESERTEUR', 'REVOQUE') THEN
        SET NEW.suspendus = 1;
        SET NEW.statut_carte = 'SUSPENDUE';
    
    -- Si le statut militaire change vers un statut actif
    ELSEIF NEW.statut_militaire IN ('ACTIF', 'EN_MISSION', 'EN_FORMATION', 'EN_PERMISSION', 'EN_CONGE', 'RESERVE', 'RESERVE_ACTIVE') 
           AND OLD.statut_militaire IN ('SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 'DESERTEUR', 'REVOQUE') THEN
        SET NEW.suspendus = 0;
        SET NEW.statut_carte = 'ACTIVE';
    END IF;
END//
DELIMITER ;

-- Trigger pour synchroniser suspendus -> statut_militaire
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `sync_suspendus_to_statut`
BEFORE UPDATE ON `candidat`
FOR EACH ROW
BEGIN
    -- Si suspendus passe de 0 à 1
    IF NEW.suspendus = 1 AND OLD.suspendus = 0 THEN
        SET NEW.statut_militaire = 'SUSPENDU';
        SET NEW.statut_carte = 'SUSPENDUE';
    
    -- Si suspendus passe de 1 à 0
    ELSEIF NEW.suspendus = 0 AND OLD.suspendus = 1 THEN
        SET NEW.statut_militaire = 'ACTIF';
        SET NEW.statut_carte = 'ACTIVE';
    END IF;
END//
DELIMITER ;

-- Trigger pour l'insertion
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `sync_on_insert`
BEFORE INSERT ON `candidat`
FOR EACH ROW
BEGIN
    -- Si statut_militaire est un statut de suspension
    IF NEW.statut_militaire IN ('SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 'DESERTEUR', 'REVOQUE') THEN
        SET NEW.suspendus = 1;
        SET NEW.statut_carte = 'SUSPENDUE';
    ELSE
        SET NEW.suspendus = 0;
        SET NEW.statut_carte = 'ACTIVE';
    END IF;
END//
DELIMITER ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
