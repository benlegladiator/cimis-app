-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 05 mai 2026 à 22:12
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cimis`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'Type d''action (DESACTIVATION_UTILISATEUR, REACTIVATION_UTILISATEUR, etc.)',
  `description` text DEFAULT NULL COMMENT 'Description détaillée de l''action',
  `ip_address` varchar(45) DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `date_action`) VALUES
(1, 15, 'DESACTIVATION_UTILISATEUR', 'Désactivation de l\'utilisateur AxcIlpLtSc7dgfBpOn1O6WUyRElvMkp3UVJGOHpWUGx3QU0vam (ADMIN_ENROLEMENT)', '127.0.0.1', '2026-04-27 04:58:58'),
(2, 15, 'DESACTIVATION_UTILISATEUR', 'Désactivation de l\'utilisateur admin_enrolement_cimis (ADMIN_ENROLEMENT) - Motif: abus', '127.0.0.1', '2026-04-27 16:56:57');

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `matricule_militaire` varchar(20) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL DEFAULT '1990-01-01' COMMENT 'Date de naissance du candidat',
  `lieu_naissance` varchar(255) DEFAULT NULL COMMENT 'Lieu de naissance du personnel',
  `sexe` enum('MASCULIN','FEMININ') NOT NULL,
  `numero_cni` varchar(255) DEFAULT NULL,
  `date_enrolement` date DEFAULT current_timestamp(),
  `date_dernier_grade` date DEFAULT NULL,
  `suspendus` tinyint(1) DEFAULT 0,
  `grade` varchar(255) DEFAULT NULL,
  `categorie_civil` varchar(50) DEFAULT NULL COMMENT 'Catégorie pour le personnel civil',
  `unite` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `taille` varchar(10) DEFAULT NULL COMMENT 'Taille en cm',
  `poids` varchar(10) DEFAULT NULL COMMENT 'Poids en kg',
  `groupe_sanguin` varchar(5) DEFAULT NULL COMMENT 'Groupe sanguin',
  `code_qr` varchar(255) DEFAULT NULL COMMENT 'Code QR unique',
  `empreinte_data` text DEFAULT NULL COMMENT 'Données empreinte digitale',
  `annee_dernier_galon` date DEFAULT NULL COMMENT 'Date de la dernière promotion au grade (remplace année dernier galon)',
  `source_system` varchar(100) DEFAULT 'CIMIS' COMMENT 'Système source des données',
  `date_modification` datetime DEFAULT NULL COMMENT 'Date de dernière modification',
  `sync_status` enum('pending','synced','error') DEFAULT 'pending' COMMENT 'Statut de synchronisation',
  `nb_reimpressions` int(11) DEFAULT 0 COMMENT 'Nombre de fois que la carte a été réimprimée (compteur d''impressions)',
  `date_derniere_reimpression` date DEFAULT NULL COMMENT 'Date de la dernière réimpression',
  `statut_militaire` enum('ACTIF','EN_MISSION','EN_FORMATION','EN_PERMISSION','EN_CONGE','SUSPENDU','SUSPENDU_ADMINISTRATIVEMENT','SUSPENDU_MEDICAL','EN_ATTENTE_AFFECTATION','EN_RETRAITE_TRANSITOIRE','DESERTEUR','REVOQUE','DEMISSIONNAIRE','RETRAITE','DECES','RESERVE','RESERVE_ACTIVE','HONORAIRE','CONTRACTUEL','CIVIL') DEFAULT 'ACTIF' COMMENT 'Statut militaire du personnel',
  `date_changement_statut` date DEFAULT NULL COMMENT 'Date du dernier changement de statut',
  `motif_changement_statut` text DEFAULT NULL COMMENT 'Motif du changement de statut',
  `autorite_changement_statut` varchar(255) DEFAULT NULL COMMENT 'Autorité ayant validé le changement',
  `supprimer` tinyint(1) NOT NULL DEFAULT 1,
  `supprimer_par` varchar(50) DEFAULT NULL,
  `date_suppression` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidat`
--

INSERT INTO `candidat` (`id`, `matricule`, `matricule_militaire`, `nom`, `prenom`, `date_naissance`, `lieu_naissance`, `sexe`, `numero_cni`, `date_enrolement`, `date_dernier_grade`, `suspendus`, `grade`, `categorie_civil`, `unite`, `photo`, `taille`, `poids`, `groupe_sanguin`, `code_qr`, `empreinte_data`, `annee_dernier_galon`, `source_system`, `date_modification`, `sync_status`, `nb_reimpressions`, `date_derniere_reimpression`, `statut_militaire`, `date_changement_statut`, `motif_changement_statut`, `autorite_changement_statut`, `supprimer`, `supprimer_par`, `date_suppression`) VALUES
(255, 'CIM-10968', '447474', 'MBOCK', 'MARIE CLAIRE', '1990-01-01', NULL, 'MASCULIN', '1234567890124', '2026-03-26', '2023-08-20', 0, 'Gendarme-Major', NULL, 'GENDARMERIE NATIONALE', 'img/candidats/CIM-10968_1776972298.PNG', '', '', '', NULL, NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(259, 'CIM-37133', '95842', 'NDJOCK', 'EMMANUEL', '1990-01-01', NULL, 'MASCULIN', '1234567890128', '2026-03-26', '2022-12-01', 0, 'COLONEL', NULL, 'GENDARMERIE', 'img/candidats/CIM-37133_1775127047.PNG', '165', '80', '', NULL, NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 0, 'super_admin_cimis', '2026-04-29 17:58:29'),
(264, 'CIM-48075', '15478', 'MARIE LUC', 'LE PEINE', '1995-01-12', NULL, 'MASCULIN', 'NFRLF416578', '2026-04-02', '2022-01-01', 0, 'ADJUDANT CHEF MAJOR', NULL, 'GENDARMERIE', 'img/candidats/CIM-48075_1775127108.PNG', '165', '70', 'AB-', '../img/qrcodes/15478_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(265, 'CIM-48559', 'T20/15478', 'MARIE LUC', 'LE PEINE', '1995-01-12', NULL, 'MASCULIN', 'NIRLF416578', '2026-04-02', '2022-01-01', 0, 'LIEUTENANT COLONEL', NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-48559_1775113494.PNG', '165', '70', 'AB+', '../img/qrcodes/T20_15478_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(266, 'CIM-61012', 'M11/458755', 'MARIE LUC', 'LE PEINE', '1995-01-12', NULL, 'FEMININ', 'NIRLO416578', '2026-04-02', '2022-01-01', 0, 'MÉCANICIEN', NULL, 'CIVIL', 'img/candidats/CIM-61012_1775114648.PNG', '165', '70', 'B+', '../img/qrcodes/M11_458755_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(267, 'CIM-40199', 'T15/45452', 'NOELLA ETAN', 'RAN BENOIT', '2000-02-12', NULL, 'MASCULIN', 'DFHJF4571', '2026-04-02', '2022-01-01', 0, 'ADJUDANT', NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-40199_1775129692.jpg', '175', '85', 'B+', '../img/qrcodes/T15_45452_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(268, 'CIM-92136', 'T10/214578', 'STAMA MBALLA', 'YANNICK', '1985-02-15', NULL, 'MASCULIN', 'HKJJ78954', '2026-04-02', '2022-01-01', 0, 'CAPITAINE', NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-92136_1775130294.PNG', '175', '70', 'A-', '../img/qrcodes/T10_214578_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(269, 'CIM-18961', 'M10/48781', 'ONANA', 'JEAN MARCELIN', '2001-03-15', NULL, 'MASCULIN', 'NLKJ45684', '2026-04-02', '2021-01-01', 0, 'QUARTIER MAITRE DE 1ERE CLASSE', NULL, 'MARINE NATIONALE', 'img/candidats/CIM-18961_1775130396.PNG', '175', '75', '', '../img/qrcodes/M10_48781_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 0, 'super_admin_cimis', '2026-04-29 18:11:21'),
(270, 'CIM-65098', '47842', 'DJIEGOUE SINDJU', 'NOELLIE GRACE', '1999-05-12', NULL, 'FEMININ', 'NLNL4564K', '2026-04-02', '2021-01-01', 0, 'LIEUTENANT COLONEL', NULL, 'GENDARMERIE', 'img/candidats/CIM-65098_1775130515.PNG', '150', '65', 'A-', '../img/qrcodes/47842_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(271, 'CIM-30377', 'A14/748544', 'ESOLA MBIDA', 'JOELLE ROSE', '2000-02-18', NULL, 'FEMININ', 'NLNKN4879', '2026-04-02', '2020-01-01', 0, 'ADJUDANT CHEF MAJOR', NULL, 'ARMÉE DE L\'AIR', 'img/candidats/CIM-30377_1775130619.jpg', '165', '70', 'B+', '../img/qrcodes/A14_748544_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(272, 'CIM-49004', 'T17/78452', 'MBALLA MBESSA', 'ANNE MICHELLE', '1988-05-29', NULL, 'FEMININ', 'QDSQ4571C', '2026-04-02', '2024-01-01', 0, 'ASPIRANT', NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-49004_1775130717.PNG', '175', '70', 'B+', '../img/qrcodes/T17_78452_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(273, 'CIM-40744', 'T88/25478', 'GOUFAN A RIM', 'GEORGES', '1965-12-23', NULL, 'MASCULIN', 'DFJ4576FD', '2026-04-02', '2015-01-01', 0, 'COLONEL', NULL, 'ARMÉE DE TERRE', 'img/candidats/CIM-40744_1775130908.png', '180', '85', 'B-', '../img/qrcodes/T88_25478_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(274, 'CIM-11235', 'M14/757654', 'TALLA BIAR', 'THOMA RAN', '1988-12-10', NULL, 'MASCULIN', 'KJNBKUH45', '2026-04-02', '2015-01-01', 0, 'QUARTIER MAITRE DE 1ERE CLASSE', NULL, 'MARINE NATIONALE', 'img/candidats/CIM-11235_1775131023.PNG', '190', '85', 'A-', '../img/qrcodes/M14_757654_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 0, 'super_admin_cimis', '2026-04-29 18:07:30'),
(275, 'CIM-55741', '7945', 'SALAM ZOA', 'IBRAHIM', '0001-11-10', NULL, 'MASCULIN', 'FGDS789514', '2026-04-02', '2020-01-01', 0, 'MARECHAL DES LOGIS CHEF', NULL, 'GENDARMERIE', 'img/candidats/CIM-55741_1775131286.PNG', '180', '85', 'AB+', '../img/qrcodes/7945_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(276, 'CIM-24669', 'M06/1454', 'ONGUENE NAMADO', 'MARTIAL QUATORE', '1995-01-12', 'BAMENDA', 'MASCULIN', 'RLF416578', '2026-04-02', '2026-01-01', 0, 'Capitaine de Corvette', '', 'MARINE NATIONALE', 'img/candidats/CIM-24669_1775136588.jpg', '185', '86', 'A+', '../img/qrcodes/A06_1454_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'SUSPENDU_ADMINISTRATIVEMENT', NULL, 'detournement DE FOND PUBLIC', NULL, 1, NULL, NULL),
(277, 'CIM-07443', 'M06/1454', 'DOUANLA ATANGO', 'JUNIOR PAPI', '1995-01-12', NULL, 'MASCULIN', 'RLF416579', '2026-04-23', '2020-01-01', 0, 'SECOND MAITRE', NULL, 'MARINE NATIONALE', 'img/candidats/CIM-07443_1776929347.PNG', '185', '86', 'AB-', '../img/qrcodes/CIM-07443_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(278, 'CIM-24500', '5846', 'ARTHUR', 'PERIN PANTA', '2008-04-01', NULL, 'MASCULIN', 'CNWXWCWXC', '2026-04-23', '2020-01-01', 0, 'Chef d\'Escadron (C/E)', NULL, 'GENDARMERIE NATIONALE', 'img/candidats/CIM-24500_1776970441.PNG', '180', '75', 'A-', '../img/qrcodes/CIM-24500_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(283, 'CIM-32255', '36363', 'ARTHUR BLOD', 'PERIN PANTA', '2008-04-01', NULL, 'MASCULIN', 'CNWXWCWX8', '2026-04-27', '2020-01-01', 0, 'Maréchal des Logis-Chef (MDL/C)', NULL, 'GENDARMERIE NATIONALE', 'img/candidats/CIM-32255_1777268568.PNG', '175', '70', 'AB+', '../img/qrcodes/CIM-32255_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(284, 'CIM-46042', 'A11/5825', 'DANNO TOLLO', 'GILBERT BION', '2000-11-12', 'YAOUNDE', 'MASCULIN', 'GGFHGFNVN', '2026-04-28', '0000-00-00', 0, 'Adjudant-Chef Major', '', 'ARMÉE DE L\'AIR', 'img/candidats/CIM-46042_1777380543.png', '160', '70', 'AB-', '../img/qrcodes/CIM-46042_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(285, 'CIM-73538', '', 'DANNO TOLLO', 'GILBERT BION', '2000-11-12', 'YAOUNDE', 'MASCULIN', 'GGFHGFNXX', '2026-04-28', '0000-00-00', 0, 'COMPTABLE', '', 'CIVIL', 'img/candidats/CIM-73538_1777441955.PNG', '160', '70', 'O+', '../img/qrcodes/CIM-73538_qr.png', NULL, '0000-00-00', 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, '', NULL, 1, NULL, NULL),
(286, 'CIM-31270', '', 'MARIE LUC', 'PERIN PANTA', '2000-06-02', 'DOUALA', 'MASCULIN', 'LFHDFGIDFHOIF', '2026-04-29', '0000-00-00', 0, 'TECHNICIEN', 'CADRE_CONTRACTUEL', 'CIVIL', 'img/candidats/CIM-31270_1777454550.png', '150', '60', 'AB-', '../img/qrcodes/CIM-31270_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(287, 'CIM-64314', 'M18/2552', 'BEN TAO BANDA', 'JULLU CESAR TIM', '1995-02-24', 'BUEA', 'FEMININ', 'JHJH4242JK', '2026-04-30', '0000-00-00', 0, 'Capitaine de Frégate', '', 'MARINE NATIONALE', 'img/candidats/CIM-64314_1777538330.png', '180', '80', 'A-', '../img/qrcodes/CIM-64314_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(288, 'CIM-17457', 'T18/2552', 'BEN TAO BANDA', 'JULLU CESAR TIM', '1995-02-24', 'BUEA', 'FEMININ', 'JHJH42425', '2026-04-30', '0000-00-00', 0, 'Chef de Bataillon', '', 'ARMÉE DE TERRE', '../img/candidats/CIM-17457_1777538727.png', '180', '80', 'A+', '../img/qrcodes/CIM-17457_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(289, 'CIM-09710', 'A12/4525', 'BEN TAO BANDA', 'JULLU CESAR TIM', '1995-02-24', 'BUEA', 'FEMININ', 'JIJH42425', '2026-05-01', '0000-00-00', 0, 'Capitaine', '', 'ARMÉE DE L\'AIR', '../img/candidats/CIM-09710_1777642993.png', '180', '80', 'A-', '../img/qrcodes/CIM-09710_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL),
(290, 'CIM-25700', 'M12/4525', 'BEN TAO BANDA', 'JULLU CESAR TIM', '1995-02-24', 'BUEA', 'FEMININ', 'JHJH42420', '2026-05-01', '0000-00-00', 0, 'Capitaine de Frégate', '', 'MARINE NATIONALE', '../img/candidats/CIM-25700_1777647124.png', '180', '80', 'AB-', '../img/qrcodes/CIM-25700_qr.png', NULL, NULL, 'CIMIS', NULL, 'pending', 0, NULL, 'ACTIF', NULL, NULL, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `card_generations`
--

CREATE TABLE `card_generations` (
  `id` int(11) NOT NULL,
  `candidat_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template` varchar(50) NOT NULL DEFAULT 'default',
  `card_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','error') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `card_templates`
--

CREATE TABLE `card_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT '#FFFFFF',
  `primary_color` varchar(7) DEFAULT '#1E40AF',
  `secondary_color` varchar(7) DEFAULT '#3730A3',
  `text_color` varchar(7) DEFAULT '#000000',
  `accent_color` varchar(7) DEFAULT '#DC2626',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `card_templates`
--

INSERT INTO `card_templates` (`id`, `name`, `slug`, `description`, `preview_image`, `background_color`, `primary_color`, `secondary_color`, `text_color`, `accent_color`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Standard', 'default', 'Template standard CIMO', NULL, '#FFFFFF', '#1E40AF', '#3730A3', '#000000', '#DC2626', 1, NULL, '2026-05-01 05:22:16', '2026-05-01 05:22:16'),
(2, 'Armée de Terre', 'armee_terre', 'Template avec couleurs Armée de Terre', NULL, '#FFFFFF', '#228B22', '#006400', '#000000', '#8B4513', 1, NULL, '2026-05-01 05:22:16', '2026-05-01 05:22:16'),
(3, 'Marine Nationale', 'marine', 'Template avec couleurs Marine Nationale', NULL, '#FFFFFF', '#00008B', '#0000FF', '#000000', '#FFD700', 1, NULL, '2026-05-01 05:22:16', '2026-05-01 05:22:16'),
(4, 'Armée de l\'Air', 'air', 'Template avec couleurs Armée de l\'Air', NULL, '#FFFFFF', '#87CEEB', '#4682B4', '#000000', '#FF8C00', 1, NULL, '2026-05-01 05:22:16', '2026-05-01 05:22:16'),
(5, 'Gendarmerie', 'gendarmerie', 'Template avec couleurs Gendarmerie', NULL, '#FFFFFF', '#FFD700', '#FFA500', '#000000', '#800080', 1, NULL, '2026-05-01 05:22:16', '2026-05-01 05:22:16');

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
-- Déchargement des données de la table `logs`
--

INSERT INTO `logs` (`id`, `utilisateur_id`, `username`, `action`, `module`, `table_concernee`, `id_enregistrement`, `details`, `ip_adresse`, `user_agent`, `date_action`, `statut`) VALUES
(1, 10, 'T-A-M-G-CIMIS2.0', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:12:26', 'SUCCES'),
(2, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:06', 'SUCCES'),
(3, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:11', 'SUCCES'),
(4, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:13', 'SUCCES'),
(5, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:14', 'SUCCES'),
(6, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:14', 'SUCCES'),
(7, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:15', 'SUCCES'),
(8, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:15', 'SUCCES'),
(9, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:15', 'SUCCES'),
(10, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:16', 'SUCCES'),
(11, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:16', 'SUCCES'),
(12, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:17', 'SUCCES'),
(13, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:42', 'SUCCES'),
(14, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:40:44', 'SUCCES'),
(15, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:31', 'SUCCES'),
(16, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:32', 'SUCCES'),
(17, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:32', 'SUCCES'),
(18, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:32', 'SUCCES'),
(19, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:32', 'SUCCES'),
(20, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:46', 'SUCCES'),
(21, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:47', 'SUCCES'),
(22, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:47', 'SUCCES'),
(23, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:47', 'SUCCES'),
(24, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:47', 'SUCCES'),
(25, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:54', 'SUCCES'),
(26, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:54', 'SUCCES'),
(27, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:54', 'SUCCES'),
(28, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:54', 'SUCCES'),
(29, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:46:55', 'SUCCES'),
(30, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:09', 'SUCCES'),
(31, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:10', 'SUCCES'),
(32, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:10', 'SUCCES'),
(33, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:10', 'SUCCES'),
(34, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:10', 'SUCCES'),
(35, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:51', 'SUCCES'),
(36, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:51', 'SUCCES'),
(37, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:51', 'SUCCES'),
(38, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:51', 'SUCCES'),
(39, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:47:51', 'SUCCES'),
(40, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:48:22', 'SUCCES'),
(41, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:48:22', 'SUCCES'),
(42, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:48:22', 'SUCCES'),
(43, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:48:22', 'SUCCES'),
(44, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:48:22', 'SUCCES'),
(45, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:51:16', 'SUCCES'),
(46, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:51:17', 'SUCCES'),
(47, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:51:18', 'SUCCES'),
(48, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:51:18', 'SUCCES'),
(49, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:51:18', 'SUCCES'),
(50, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(51, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(52, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(53, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(54, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(55, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:24', 'SUCCES'),
(56, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(57, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(58, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(59, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(60, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(61, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:27', 'SUCCES'),
(62, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:28', 'SUCCES'),
(63, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:28', 'SUCCES'),
(64, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:28', 'SUCCES'),
(65, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:28', 'SUCCES'),
(66, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:30', 'SUCCES'),
(67, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:30', 'SUCCES'),
(68, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:50', 'SUCCES'),
(69, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:52', 'SUCCES'),
(70, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:52', 'SUCCES'),
(71, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:52', 'SUCCES'),
(72, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:52', 'SUCCES'),
(73, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:54:52', 'SUCCES'),
(74, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(75, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(76, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(77, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(78, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(79, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 02:59:24', 'SUCCES'),
(80, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(81, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(82, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(83, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(84, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(85, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:02:29', 'SUCCES'),
(86, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(87, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(88, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(89, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(90, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(91, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:03:53', 'SUCCES'),
(92, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:47', 'SUCCES'),
(93, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:47', 'SUCCES'),
(94, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:48', 'SUCCES'),
(95, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:48', 'SUCCES'),
(96, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:48', 'SUCCES'),
(97, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:07:48', 'SUCCES'),
(98, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:37', 'SUCCES'),
(99, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:38', 'SUCCES'),
(100, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:38', 'SUCCES'),
(101, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:38', 'SUCCES'),
(102, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:38', 'SUCCES'),
(103, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:11:38', 'SUCCES'),
(104, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(105, 11, 'admin_enrolement', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(106, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(107, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(108, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(109, 11, 'admin_enrolement', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:12:59', 'SUCCES'),
(110, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:48', 'SUCCES'),
(111, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(112, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(113, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(114, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(115, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(116, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:13:49', 'SUCCES'),
(117, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:12', 'SUCCES'),
(118, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:13', 'SUCCES'),
(119, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:13', 'SUCCES'),
(120, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:13', 'SUCCES'),
(121, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:14', 'SUCCES'),
(122, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:14', 'SUCCES'),
(123, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:14', 'SUCCES'),
(124, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:15', 'SUCCES'),
(125, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:15', 'SUCCES'),
(126, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:15', 'SUCCES'),
(127, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:15', 'SUCCES'),
(128, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:15', 'SUCCES'),
(129, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:16', 'SUCCES'),
(130, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:16', 'SUCCES'),
(131, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:16', 'SUCCES'),
(132, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(133, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(134, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(135, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(136, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(137, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:17', 'SUCCES'),
(138, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(139, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(140, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(141, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(142, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(143, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(144, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:19', 'SUCCES'),
(145, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:20', 'SUCCES'),
(146, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:21', 'SUCCES'),
(147, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:21', 'SUCCES'),
(148, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:21', 'SUCCES'),
(149, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:21', 'SUCCES'),
(150, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:22', 'SUCCES'),
(151, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:22', 'SUCCES'),
(152, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:24', 'SUCCES'),
(153, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:25', 'SUCCES'),
(154, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:26', 'SUCCES'),
(155, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:26', 'SUCCES'),
(156, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:26', 'SUCCES'),
(157, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:26', 'SUCCES'),
(158, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:26', 'SUCCES'),
(159, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'dashboard', NULL, NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:54', 'SUCCES'),
(160, 12, 'admin_impression', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:54', 'SUCCES'),
(161, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:54', 'SUCCES'),
(162, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'enrolement', 'candidat', NULL, '{\"action\":\"create\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:55', 'SUCCES'),
(163, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"view\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:55', 'SUCCES'),
(164, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'impression', 'candidat', NULL, '{\"action\":\"create\",\"granted\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:55', 'SUCCES'),
(165, 12, 'admin_impression', 'VERIFICATION_PERMISSION', 'verification', NULL, NULL, '{\"action\":\"view\",\"granted\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:15:55', 'SUCCES'),
(166, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:16:54', 'SUCCES'),
(167, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:00', 'SUCCES'),
(168, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:00', 'SUCCES'),
(169, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:02', 'SUCCES'),
(170, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:03', 'SUCCES'),
(171, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:04', 'SUCCES'),
(172, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:04', 'SUCCES'),
(173, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:05', 'SUCCES'),
(174, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:17:06', 'SUCCES'),
(175, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:27', 'SUCCES'),
(176, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:29', 'SUCCES');
INSERT INTO `logs` (`id`, `utilisateur_id`, `username`, `action`, `module`, `table_concernee`, `id_enregistrement`, `details`, `ip_adresse`, `user_agent`, `date_action`, `statut`) VALUES
(177, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:30', 'SUCCES'),
(178, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:32', 'SUCCES'),
(179, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:33', 'SUCCES'),
(180, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:34', 'SUCCES'),
(181, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:34', 'SUCCES'),
(182, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:35', 'SUCCES'),
(183, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:35', 'SUCCES'),
(184, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:36', 'SUCCES'),
(185, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(186, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(187, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(188, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(189, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(190, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(191, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(192, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(193, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(194, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:38', 'SUCCES'),
(195, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:39', 'SUCCES'),
(196, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:18:39', 'SUCCES'),
(197, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:19:39', 'SUCCES'),
(198, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:19:42', 'SUCCES'),
(199, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:19:44', 'SUCCES'),
(200, 14, 'super_admin_test', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:20:22', 'SUCCES'),
(201, 14, 'super_admin_test', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:22:00', 'SUCCES'),
(202, 14, 'super_admin_test', 'ACCES_PAGE', 'demande_impression', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:31:11', 'SUCCES'),
(203, 14, 'super_admin_test', 'ACCES_PAGE', 'demande_impression', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:31:35', 'SUCCES'),
(204, 14, 'super_admin_test', 'ACCES_PAGE', 'demande_impression', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:34:40', 'SUCCES'),
(205, 14, 'super_admin_test', 'ACCES_PAGE', 'details_demande', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:34:55', 'SUCCES'),
(206, 14, 'super_admin_test', 'ACCES_PAGE', 'details_demande', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:37:55', 'SUCCES'),
(207, 14, 'super_admin_test', 'ACCES_PAGE', 'approve_demande', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:38:34', 'SUCCES'),
(208, 14, 'super_admin_test', 'APPROBATION_DEMANDE_IMPRESSION', 'demandes_impression', NULL, 3, '\"{\\\"demande_id\\\":3,\\\"matricule\\\":\\\"CIM-26182\\\",\\\"traite_par\\\":\\\"super_admin_test\\\"}\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:38:34', 'SUCCES'),
(209, 14, 'super_admin_test', 'ACCES_PAGE', 'demande_impression', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:38:59', 'SUCCES'),
(210, 14, 'super_admin_test', 'ACCES_PAGE', 'details_demande', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:40:15', 'SUCCES'),
(211, 14, 'super_admin_test', 'ACCES_PAGE', 'details_demande', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 03:47:01', 'SUCCES'),
(212, 14, 'FAIURuVexZ3Z+NcRQgZnii9oVmEyNndHclh1SlRWZnNNek5mZG', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 04:10:40', 'SUCCES'),
(213, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 04:37:17', 'SUCCES'),
(214, 15, 'super_admin_cimis', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 04:37:22', 'SUCCES'),
(215, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 04:37:27', 'SUCCES'),
(216, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-19 04:39:15', 'SUCCES'),
(217, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 00:52:50', 'SUCCES'),
(218, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 00:53:31', 'SUCCES'),
(219, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 00:53:51', 'SUCCES'),
(220, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 00:56:02', 'SUCCES'),
(221, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:18:23', 'SUCCES'),
(222, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:18:30', 'SUCCES'),
(223, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:18:57', 'SUCCES'),
(224, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:19:08', 'SUCCES'),
(225, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:20:53', 'SUCCES'),
(226, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:23:11', 'SUCCES'),
(227, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:23:22', 'SUCCES'),
(228, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 01:23:33', 'SUCCES'),
(229, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:48:16', 'SUCCES'),
(230, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:07', 'SUCCES'),
(231, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:14', 'SUCCES'),
(232, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:21', 'SUCCES'),
(233, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:22', 'SUCCES'),
(234, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:28', 'SUCCES'),
(235, 15, 'super_admin_cimis', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:34', 'SUCCES'),
(236, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:49:39', 'SUCCES'),
(237, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:58:23', 'SUCCES'),
(238, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 13:58:42', 'SUCCES'),
(239, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 15:02:38', 'SUCCES'),
(240, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 15:03:17', 'SUCCES'),
(241, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 15:03:26', 'SUCCES'),
(242, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 15:03:29', 'SUCCES'),
(243, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 15:03:30', 'SUCCES'),
(244, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-20 23:56:19', 'SUCCES'),
(245, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:01:09', 'SUCCES'),
(246, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:01:16', 'SUCCES'),
(247, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:01:32', 'SUCCES'),
(248, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:01:33', 'SUCCES'),
(249, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:08:08', 'SUCCES'),
(250, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:09:00', 'SUCCES'),
(251, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:22:37', 'SUCCES'),
(252, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:24:25', 'SUCCES'),
(253, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:24:29', 'SUCCES'),
(254, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:24:39', 'SUCCES'),
(255, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:24:53', 'SUCCES'),
(256, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:24:56', 'SUCCES'),
(257, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:25:32', 'SUCCES'),
(258, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:34:33', 'SUCCES'),
(259, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:36:15', 'SUCCES'),
(260, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:57:00', 'SUCCES'),
(261, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:57:17', 'SUCCES'),
(262, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 00:58:06', 'SUCCES'),
(263, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 01:04:59', 'SUCCES'),
(264, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 01:05:07', 'SUCCES'),
(265, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 01:19:36', 'SUCCES'),
(266, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-21 01:22:46', 'SUCCES'),
(267, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:17:09', 'SUCCES'),
(268, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:17:31', 'SUCCES'),
(269, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:17:41', 'SUCCES'),
(270, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:17:49', 'SUCCES'),
(271, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:17:54', 'SUCCES'),
(272, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:21:31', 'SUCCES'),
(273, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:21:46', 'SUCCES'),
(274, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 10:22:08', 'SUCCES'),
(275, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 12:39:55', 'SUCCES'),
(276, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 12:56:51', 'SUCCES'),
(277, 15, 'super_admin_cimis', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 12:56:57', 'SUCCES'),
(278, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 12:58:14', 'SUCCES'),
(279, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 13:00:15', 'SUCCES'),
(280, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 13:19:21', 'SUCCES'),
(281, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 13:19:41', 'SUCCES'),
(282, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 13:19:50', 'SUCCES'),
(283, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 14:10:23', 'SUCCES'),
(284, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 14:13:44', 'SUCCES'),
(285, 15, 'super_admin_cimis', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 14:14:03', 'SUCCES'),
(286, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:15:07', 'SUCCES'),
(287, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:33:02', 'SUCCES'),
(288, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:38:05', 'SUCCES'),
(289, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:39:35', 'SUCCES'),
(290, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:52:29', 'SUCCES'),
(291, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:53:19', 'SUCCES'),
(292, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 15:59:37', 'SUCCES'),
(293, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 16:06:31', 'SUCCES'),
(294, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-24 16:07:09', 'SUCCES'),
(295, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:19:13', 'SUCCES'),
(296, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:28:23', 'SUCCES'),
(297, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:32:46', 'SUCCES'),
(298, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:36:50', 'SUCCES'),
(299, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:37:17', 'SUCCES'),
(300, 15, 'super_admin_cimis', 'ACCES_PAGE', 'security_dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:37:33', 'SUCCES'),
(301, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:38:20', 'SUCCES'),
(302, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:41:26', 'SUCCES'),
(303, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:47:14', 'SUCCES'),
(304, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:47:19', 'SUCCES'),
(305, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:52:57', 'SUCCES'),
(306, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:53:11', 'SUCCES'),
(307, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:59:20', 'SUCCES'),
(308, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-25 14:59:26', 'SUCCES'),
(309, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 07:56:26', 'SUCCES'),
(310, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 07:57:18', 'SUCCES'),
(311, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:04:33', 'SUCCES'),
(312, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:04:38', 'SUCCES'),
(313, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:09:11', 'SUCCES'),
(314, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:19:49', 'SUCCES'),
(315, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:20:07', 'SUCCES'),
(316, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:20:17', 'SUCCES'),
(317, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:20:55', 'SUCCES'),
(318, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 08:26:51', 'SUCCES'),
(319, 15, 'super_admin_cimis', 'DECONNEXION_TIMEOUT', 'auth', 'utilisateur', 0, '15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 01:00:00', 'SUCCES'),
(320, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 01:00:23', 'SUCCES'),
(321, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 01:00:31', 'SUCCES'),
(322, 15, 'super_admin_cimis', 'ACCES_PAGE', 'dashboard', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 01:00:50', 'SUCCES'),
(323, 15, 'super_admin_cimis', 'ACCES_PAGE', 'visualiser_carte', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 01:01:04', 'SUCCES'),
(324, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 09:20:41', 'SUCCES'),
(325, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 09:20:56', 'SUCCES'),
(326, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 10:15:43', 'SUCCES'),
(327, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-28 10:16:24', 'SUCCES'),
(328, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 20:45:40', 'SUCCES'),
(329, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 20:46:00', 'SUCCES'),
(330, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 15:58:26', 'SUCCES'),
(331, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 15:58:43', 'SUCCES'),
(332, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 23:52:57', 'SUCCES'),
(333, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 23:53:19', 'SUCCES'),
(334, 15, 'super_admin_cimis', 'SESSION_TIMEOUT', 'SECURITY', NULL, NULL, 'Déconnexion automatique après 30 minutes d\'inactivité', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36 Edg/146.0.0.0', '2026-04-02 05:42:32', 'SUCCES'),
(335, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36 Edg/146.0.0.0', '2026-04-02 05:43:00', 'SUCCES'),
(336, 15, 'super_admin_cimis', 'LOGIN_SUCCESS', 'AUTH', NULL, NULL, 'Connexion réussie', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-06 14:45:55', 'SUCCES');

-- --------------------------------------------------------

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

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `module`, `action`, `granted`, `created_at`) VALUES
(1, 'SUPER_ADMIN', 'dashboard', 'view', 1, '2026-03-19 01:55:28'),
(2, 'SUPER_ADMIN', 'enrolement', 'view', 1, '2026-03-19 01:55:28'),
(3, 'SUPER_ADMIN', 'enrolement', 'create', 1, '2026-03-19 01:55:28'),
(4, 'SUPER_ADMIN', 'enrolement', 'edit', 1, '2026-03-19 01:55:28'),
(5, 'SUPER_ADMIN', 'enrolement', 'delete', 1, '2026-03-19 01:55:28'),
(6, 'SUPER_ADMIN', 'impression', 'view', 1, '2026-03-19 01:55:28'),
(7, 'SUPER_ADMIN', 'impression', 'create', 1, '2026-03-19 01:55:28'),
(8, 'SUPER_ADMIN', 'impression', 'edit', 1, '2026-03-19 01:55:28'),
(9, 'SUPER_ADMIN', 'impression', 'delete', 1, '2026-03-19 01:55:28'),
(10, 'SUPER_ADMIN', 'visualisation', 'view', 1, '2026-03-19 01:55:28'),
(11, 'SUPER_ADMIN', 'verification', 'view', 1, '2026-03-19 01:55:28'),
(12, 'SUPER_ADMIN', 'utilisateurs', 'view', 1, '2026-03-19 01:55:28'),
(13, 'SUPER_ADMIN', 'utilisateurs', 'create', 1, '2026-03-19 01:55:28'),
(14, 'SUPER_ADMIN', 'utilisateurs', 'edit', 1, '2026-03-19 01:55:28'),
(15, 'SUPER_ADMIN', 'utilisateurs', 'delete', 1, '2026-03-19 01:55:28'),
(16, 'ADMIN_ENROLEMENT', 'dashboard', 'view', 1, '2026-03-19 01:55:28'),
(17, 'ADMIN_ENROLEMENT', 'enrolement', 'view', 1, '2026-03-19 01:55:28'),
(18, 'ADMIN_ENROLEMENT', 'enrolement', 'create', 1, '2026-03-19 01:55:28'),
(19, 'ADMIN_ENROLEMENT', 'enrolement', 'edit', 0, '2026-03-19 01:55:28'),
(20, 'ADMIN_ENROLEMENT', 'enrolement', 'delete', 0, '2026-03-19 01:55:28'),
(21, 'ADMIN_ENROLEMENT', 'visualisation', 'view', 1, '2026-03-19 01:55:28'),
(22, 'ADMIN_ENROLEMENT', 'verification', 'view', 0, '2026-03-19 01:55:28'),
(23, 'ADMIN_IMPRESSION', 'dashboard', 'view', 1, '2026-03-19 01:55:28'),
(24, 'ADMIN_IMPRESSION', 'impression', 'view', 1, '2026-03-19 01:55:28'),
(25, 'ADMIN_IMPRESSION', 'impression', 'create', 1, '2026-03-19 01:55:28'),
(26, 'ADMIN_IMPRESSION', 'impression', 'edit', 1, '2026-03-19 01:55:28'),
(27, 'ADMIN_IMPRESSION', 'impression', 'delete', 0, '2026-03-19 01:55:28'),
(28, 'ADMIN_IMPRESSION', 'visualisation', 'view', 1, '2026-03-19 01:55:28'),
(29, 'ADMIN_IMPRESSION', 'verification', 'view', 0, '2026-03-19 01:55:28'),
(30, 'OFFICIER', 'dashboard', 'view', 1, '2026-03-19 01:55:28'),
(31, 'OFFICIER', 'visualisation', 'view', 1, '2026-03-19 01:55:28'),
(32, 'OFFICIER', 'verification', 'view', 1, '2026-03-19 01:55:28'),
(33, 'SUPERVISOR', 'dashboard', 'view', 1, '2026-03-19 01:55:28'),
(34, 'SUPERVISOR', 'visualisation', 'view', 1, '2026-03-19 01:55:28'),
(35, 'SUPERVISOR', 'verification', 'view', 1, '2026-03-19 01:55:28'),
(36, 'SUPERVISOR', 'enrolement', 'view', 1, '2026-03-19 01:55:28'),
(37, 'SUPERVISOR', 'impression', 'view', 1, '2026-03-19 01:55:28'),
(87, 'ADMIN_ENROLEMENT', 'impression', 'view', 0, '2026-03-19 02:58:58'),
(88, 'ADMIN_ENROLEMENT', 'impression', 'create', 0, '2026-03-19 02:58:58'),
(89, 'ADMIN_ENROLEMENT', 'impression', 'edit', 0, '2026-03-19 02:58:58'),
(90, 'ADMIN_ENROLEMENT', 'impression', 'delete', 0, '2026-03-19 02:58:58'),
(91, 'ADMIN_ENROLEMENT', 'impression', 'print', 0, '2026-03-19 02:58:58'),
(93, 'ADMIN_ENROLEMENT', 'security', 'view', 0, '2026-03-19 02:58:58'),
(94, 'ADMIN_ENROLEMENT', 'security', 'manage_users', 0, '2026-03-19 02:58:59'),
(95, 'ADMIN_ENROLEMENT', 'security', 'view_logs', 0, '2026-03-19 02:58:59'),
(100, 'ADMIN_IMPRESSION', 'impression', 'print', 1, '2026-03-19 02:58:59'),
(101, 'ADMIN_IMPRESSION', 'enrolement', 'view', 1, '2026-03-19 02:58:59'),
(102, 'ADMIN_IMPRESSION', 'enrolement', 'create', 0, '2026-03-19 02:58:59'),
(103, 'ADMIN_IMPRESSION', 'enrolement', 'edit', 0, '2026-03-19 02:58:59'),
(104, 'ADMIN_IMPRESSION', 'enrolement', 'delete', 0, '2026-03-19 02:58:59'),
(106, 'ADMIN_IMPRESSION', 'security', 'view', 0, '2026-03-19 02:58:59'),
(107, 'ADMIN_IMPRESSION', 'security', 'manage_users', 0, '2026-03-19 02:58:59'),
(108, 'ADMIN_IMPRESSION', 'security', 'view_logs', 0, '2026-03-19 02:58:59'),
(135, 'SUPER_ADMIN', 'demandes_impression', 'view', 1, '2026-03-19 03:30:50'),
(136, 'SUPER_ADMIN', 'demandes_impression', 'create', 1, '2026-03-19 03:30:50'),
(137, 'SUPER_ADMIN', 'demandes_impression', 'edit', 1, '2026-03-19 03:30:50'),
(138, 'SUPER_ADMIN', 'demandes_impression', 'delete', 1, '2026-03-19 03:30:50'),
(139, 'SUPER_ADMIN', 'demandes_impression', 'approve', 1, '2026-03-19 03:30:50'),
(140, 'SUPER_ADMIN', 'demandes_impression', 'refuse', 1, '2026-03-19 03:30:50'),
(141, 'ADMIN_IMPRESSION', 'demandes_impression', 'view', 1, '2026-03-19 03:30:50'),
(142, 'ADMIN_IMPRESSION', 'demandes_impression', 'create', 1, '2026-03-19 03:30:50'),
(143, 'ADMIN_IMPRESSION', 'demandes_impression', 'edit', 1, '2026-03-19 03:30:50'),
(144, 'ADMIN_IMPRESSION', 'demandes_impression', 'delete', 0, '2026-03-19 03:30:50'),
(145, 'ADMIN_IMPRESSION', 'demandes_impression', 'approve', 1, '2026-03-19 03:30:50'),
(146, 'ADMIN_IMPRESSION', 'demandes_impression', 'refuse', 1, '2026-03-19 03:30:50'),
(147, 'ADMIN_ENROLEMENT', 'demandes_impression', 'view', 1, '2026-03-19 03:30:50'),
(148, 'ADMIN_ENROLEMENT', 'demandes_impression', 'create', 1, '2026-03-19 03:30:50'),
(149, 'ADMIN_ENROLEMENT', 'demandes_impression', 'edit', 0, '2026-03-19 03:30:50'),
(150, 'ADMIN_ENROLEMENT', 'demandes_impression', 'delete', 0, '2026-03-19 03:30:50'),
(151, 'ADMIN_ENROLEMENT', 'demandes_impression', 'approve', 0, '2026-03-19 03:30:50'),
(152, 'ADMIN_ENROLEMENT', 'demandes_impression', 'refuse', 0, '2026-03-19 03:30:50'),
(172, 'SUPER_ADMIN', 'dashboard', 'create', 1, '2026-03-19 04:36:18'),
(173, 'SUPER_ADMIN', 'dashboard', 'edit', 1, '2026-03-19 04:36:18'),
(174, 'SUPER_ADMIN', 'dashboard', 'delete', 1, '2026-03-19 04:36:18'),
(175, 'SUPER_ADMIN', 'dashboard', 'approve', 1, '2026-03-19 04:36:18'),
(176, 'SUPER_ADMIN', 'dashboard', 'refuse', 1, '2026-03-19 04:36:19'),
(177, 'SUPER_ADMIN', 'dashboard', 'manage_users', 1, '2026-03-19 04:36:19'),
(178, 'SUPER_ADMIN', 'dashboard', 'view_logs', 1, '2026-03-19 04:36:19'),
(179, 'SUPER_ADMIN', 'dashboard', 'print', 1, '2026-03-19 04:36:19'),
(184, 'SUPER_ADMIN', 'enrolement', 'approve', 1, '2026-03-19 04:36:19'),
(185, 'SUPER_ADMIN', 'enrolement', 'refuse', 1, '2026-03-19 04:36:19'),
(186, 'SUPER_ADMIN', 'enrolement', 'manage_users', 1, '2026-03-19 04:36:19'),
(187, 'SUPER_ADMIN', 'enrolement', 'view_logs', 1, '2026-03-19 04:36:19'),
(188, 'SUPER_ADMIN', 'enrolement', 'print', 1, '2026-03-19 04:36:19'),
(193, 'SUPER_ADMIN', 'impression', 'approve', 1, '2026-03-19 04:36:19'),
(194, 'SUPER_ADMIN', 'impression', 'refuse', 1, '2026-03-19 04:36:19'),
(195, 'SUPER_ADMIN', 'impression', 'manage_users', 1, '2026-03-19 04:36:20'),
(196, 'SUPER_ADMIN', 'impression', 'view_logs', 1, '2026-03-19 04:36:20'),
(197, 'SUPER_ADMIN', 'impression', 'print', 1, '2026-03-19 04:36:20'),
(199, 'SUPER_ADMIN', 'verification', 'create', 1, '2026-03-19 04:36:20'),
(200, 'SUPER_ADMIN', 'verification', 'edit', 1, '2026-03-19 04:36:20'),
(201, 'SUPER_ADMIN', 'verification', 'delete', 1, '2026-03-19 04:36:20'),
(202, 'SUPER_ADMIN', 'verification', 'approve', 1, '2026-03-19 04:36:20'),
(203, 'SUPER_ADMIN', 'verification', 'refuse', 1, '2026-03-19 04:36:20'),
(204, 'SUPER_ADMIN', 'verification', 'manage_users', 1, '2026-03-19 04:36:20'),
(205, 'SUPER_ADMIN', 'verification', 'view_logs', 1, '2026-03-19 04:36:20'),
(206, 'SUPER_ADMIN', 'verification', 'print', 1, '2026-03-19 04:36:20'),
(207, 'SUPER_ADMIN', 'security', 'view', 1, '2026-03-19 04:36:20'),
(208, 'SUPER_ADMIN', 'security', 'create', 1, '2026-03-19 04:36:20'),
(209, 'SUPER_ADMIN', 'security', 'edit', 1, '2026-03-19 04:36:20'),
(210, 'SUPER_ADMIN', 'security', 'delete', 1, '2026-03-19 04:36:20'),
(211, 'SUPER_ADMIN', 'security', 'approve', 1, '2026-03-19 04:36:20'),
(212, 'SUPER_ADMIN', 'security', 'refuse', 1, '2026-03-19 04:36:20'),
(213, 'SUPER_ADMIN', 'security', 'manage_users', 1, '2026-03-19 04:36:20'),
(214, 'SUPER_ADMIN', 'security', 'view_logs', 1, '2026-03-19 04:36:20'),
(215, 'SUPER_ADMIN', 'security', 'print', 1, '2026-03-19 04:36:20'),
(222, 'SUPER_ADMIN', 'demandes_impression', 'manage_users', 1, '2026-03-19 04:36:21'),
(223, 'SUPER_ADMIN', 'demandes_impression', 'view_logs', 1, '2026-03-19 04:36:21'),
(224, 'SUPER_ADMIN', 'demandes_impression', 'print', 1, '2026-03-19 04:36:21'),
(226, 'ADMIN_ENROLEMENT', 'dashboard', 'create', 1, '2026-03-19 04:36:21'),
(227, 'ADMIN_ENROLEMENT', 'dashboard', 'edit', 0, '2026-03-19 04:36:21'),
(228, 'ADMIN_ENROLEMENT', 'dashboard', 'delete', 0, '2026-03-19 04:36:21'),
(238, 'ADMIN_IMPRESSION', 'dashboard', 'create', 1, '2026-03-19 04:36:21'),
(239, 'ADMIN_IMPRESSION', 'dashboard', 'edit', 1, '2026-03-19 04:36:22'),
(240, 'ADMIN_IMPRESSION', 'dashboard', 'delete', 0, '2026-03-19 04:36:22'),
(241, 'ADMIN_IMPRESSION', 'dashboard', 'approve', 1, '2026-03-19 04:36:22'),
(242, 'ADMIN_IMPRESSION', 'dashboard', 'refuse', 1, '2026-03-19 04:36:22'),
(243, 'ADMIN_IMPRESSION', 'dashboard', 'print', 1, '2026-03-19 04:36:22'),
(248, 'ADMIN_IMPRESSION', 'impression', 'approve', 1, '2026-03-19 04:36:22'),
(249, 'ADMIN_IMPRESSION', 'impression', 'refuse', 1, '2026-03-19 04:36:22'),
(257, 'ADMIN_IMPRESSION', 'demandes_impression', 'print', 1, '2026-03-19 04:36:23');

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `key`, `value`, `description`, `type`, `updated_by`, `updated_at`) VALUES
(1, 'pvc_width_mm', '85.6', 'Largeur des cartes PVC en mm', 'number', NULL, '2026-05-01 05:22:16'),
(2, 'pvc_height_mm', '53.98', 'Hauteur des cartes PVC en mm', 'number', NULL, '2026-05-01 05:22:16'),
(3, 'pvc_dpi', '300', 'Résolution des cartes PVC en DPI', 'number', NULL, '2026-05-01 05:22:16'),
(4, 'max_batch_size', '50', 'Nombre maximum de cartes par lot', 'number', NULL, '2026-05-01 05:22:16'),
(5, 'photo_max_size_mb', '5', 'Taille maximale des photos en MB', 'number', NULL, '2026-05-01 05:22:16'),
(6, 'document_max_size_mb', '10', 'Taille maximale des documents en MB', 'number', NULL, '2026-05-01 05:22:16'),
(7, 'session_timeout_minutes', '60', 'Délai d\'expiration de session en minutes', 'number', NULL, '2026-05-01 05:22:16'),
(8, 'max_login_attempts', '5', 'Nombre maximum de tentatives de connexion', 'number', NULL, '2026-05-01 05:22:16'),
(9, 'login_lockout_minutes', '15', 'Durée de blocage après tentatives échouées en minutes', 'number', NULL, '2026-05-01 05:22:16'),
(10, 'maintenance_mode', 'false', 'Mode maintenance du système', 'boolean', NULL, '2026-05-01 05:22:16');

-- --------------------------------------------------------

--
-- Structure de la table `token_blacklist`
--

CREATE TABLE `token_blacklist` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `matricule` varchar(20) DEFAULT NULL,
  `role` enum('SUPER_ADMIN','ADMIN_IMPRESSION','ADMIN_ENROLEMENT','OFFICIER') NOT NULL DEFAULT 'OFFICIER',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `nom`, `prenom`, `matricule`, `role`, `active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@cimo.mil', 'Administrateur', 'Système', 'ADM001', 'SUPER_ADMIN', 1, NULL, '2026-05-01 05:22:13', '2026-05-01 05:22:13'),
(2, 'officier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'officier@cimo.mil', 'Test', 'Officier', 'OFF001', 'OFFICIER', 1, NULL, '2026-05-01 05:22:13', '2026-05-01 05:22:13'),
(3, 'enrollment', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enroll@cimo.mil', 'Admin', 'Enrollment', 'ENR001', 'ADMIN_ENROLEMENT', 1, NULL, '2026-05-01 05:22:13', '2026-05-01 05:22:13'),
(4, 'impression', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'print@cimo.mil', 'Admin', 'Impression', 'IMP001', 'ADMIN_IMPRESSION', 1, NULL, '2026-05-01 05:22:13', '2026-05-01 05:22:13');

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
  `date_verrouillage` timestamp NULL DEFAULT NULL,
  `motif_desactivation` text DEFAULT NULL COMMENT 'Motif de désactivation du compte utilisateur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `username`, `email`, `password`, `role`, `actif`, `date_creation`, `date_derniere_connexion`, `deux_factors_enabled`, `deux_factors_secret`, `dernier_ip`, `nombre_echecs`, `compte_verrouille`, `date_verrouillage`, `motif_desactivation`) VALUES
(15, 'super_admin_cimis', NULL, '$2y$10$Eha/exZVeRQM8HgueSV1juASoDCLa1uFMP79Rheyd6ja3V1JVsK5u', 'SUPER_ADMIN', 1, '2026-03-19 04:36:17', '2026-05-05 19:48:14', 0, NULL, '127.0.0.1', 0, 0, NULL, NULL),
(16, 'admin_enrolement_cimis', NULL, '$2y$10$k.VCR4FAjapOuUVxAeXeOOb77.t5asJtNcg0lcOqoHbLO7fqDp5c2', 'ADMIN_ENROLEMENT', 0, '2026-03-19 04:36:18', '2026-04-27 04:44:21', 0, NULL, NULL, 0, 0, '2026-04-27 16:56:56', 'abus'),
(17, 'admin_impression_cimis', NULL, '$2y$10$QtY83l/bBIBA4yRZX.ALc.GVYnWUCXEHloOdj2ZEtDNCyx1cEMyaS', 'ADMIN_IMPRESSION', 1, '2026-03-19 04:36:18', '2026-05-01 05:27:53', 0, NULL, NULL, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_demandes_en_attente`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_demandes_en_attente` (
`id` int(11)
,`candidat_id` int(11)
,`matricule` varchar(50)
,`nom` varchar(255)
,`prenom` varchar(255)
,`motif_demande` enum('PERTE','VOL','USURE','CASSE','ERREUR','AUTRE')
,`description_motif` text
,`statut` enum('EN_ATTENTE','APPROUVEE','REFUSEE','TRAITEE')
,`date_demande` timestamp
,`date_traitement` timestamp
,`traite_par` int(11)
,`motif_refus` text
,`priorite` enum('NORMAL','URGENT','TRES_URGENT')
,`pieces_jointes` text
,`commentaire_interne` text
,`photo` varchar(255)
,`grade` varchar(255)
,`unite` varchar(255)
,`createur_nom` varchar(50)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_statistiques_demandes`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vue_statistiques_demandes` (
`statut` enum('EN_ATTENTE','APPROUVEE','REFUSEE','TRAITEE')
,`nombre` bigint(21)
,`mois` varchar(7)
,`motif_demande` enum('PERTE','VOL','USURE','CASSE','ERREUR','AUTRE')
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_demandes_en_attente`
--
DROP TABLE IF EXISTS `vue_demandes_en_attente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_demandes_en_attente`  AS SELECT `di`.`id` AS `id`, `di`.`candidat_id` AS `candidat_id`, `di`.`matricule` AS `matricule`, `di`.`nom` AS `nom`, `di`.`prenom` AS `prenom`, `di`.`motif_demande` AS `motif_demande`, `di`.`description_motif` AS `description_motif`, `di`.`statut` AS `statut`, `di`.`date_demande` AS `date_demande`, `di`.`date_traitement` AS `date_traitement`, `di`.`traite_par` AS `traite_par`, `di`.`motif_refus` AS `motif_refus`, `di`.`priorite` AS `priorite`, `di`.`pieces_jointes` AS `pieces_jointes`, `di`.`commentaire_interne` AS `commentaire_interne`, `c`.`photo` AS `photo`, `c`.`grade` AS `grade`, `c`.`unite` AS `unite`, `u`.`username` AS `createur_nom` FROM ((`demandes_impression` `di` left join `candidat` `c` on(`di`.`candidat_id` = `c`.`id`)) left join `utilisateur` `u` on(`di`.`traite_par` = `u`.`id`)) WHERE `di`.`statut` = 'EN_ATTENTE' ORDER BY `di`.`priorite` DESC, `di`.`date_demande` ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_statistiques_demandes`
--
DROP TABLE IF EXISTS `vue_statistiques_demandes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_statistiques_demandes`  AS SELECT `demandes_impression`.`statut` AS `statut`, count(0) AS `nombre`, date_format(`demandes_impression`.`date_demande`,'%Y-%m') AS `mois`, `demandes_impression`.`motif_demande` AS `motif_demande` FROM `demandes_impression` GROUP BY `demandes_impression`.`statut`, date_format(`demandes_impression`.`date_demande`,'%Y-%m'), `demandes_impression`.`motif_demande` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `date_action` (`date_action`);

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_resource` (`resource_type`,`resource_id`),
  ADD KEY `idx_created_at` (`created_at`);

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
  ADD KEY `idx_matricule_militaire` (`matricule_militaire`),
  ADD KEY `idx_statut_militaire` (`statut_militaire`),
  ADD KEY `idx_suspendus` (`suspendus`),
  ADD KEY `idx_matricule` (`matricule`),
  ADD KEY `idx_unite` (`unite`),
  ADD KEY `idx_grade` (`grade`),
  ADD KEY `idx_categorie_civil` (`categorie_civil`),
  ADD KEY `idx_supprimer` (`supprimer`),
  ADD KEY `idx_supprimer_par` (`supprimer_par`);

--
-- Index pour la table `card_generations`
--
ALTER TABLE `card_generations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_candidat_id` (`candidat_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `card_templates`
--
ALTER TABLE `card_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

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
-- Index pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `idx_key` (`key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Index pour la table `token_blacklist`
--
ALTER TABLE `token_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`active`);

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
-- AUTO_INCREMENT pour la table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `api_sync_log`
--
ALTER TABLE `api_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `candidat`
--
ALTER TABLE `candidat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=291;

--
-- AUTO_INCREMENT pour la table `card_generations`
--
ALTER TABLE `card_generations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `card_templates`
--
ALTER TABLE `card_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT pour la table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `token_blacklist`
--
ALTER TABLE `token_blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Contraintes pour la table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `card_generations`
--
ALTER TABLE `card_generations`
  ADD CONSTRAINT `card_generations_ibfk_1` FOREIGN KEY (`candidat_id`) REFERENCES `candidat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `card_generations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `card_templates`
--
ALTER TABLE `card_templates`
  ADD CONSTRAINT `card_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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

--
-- Contraintes pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
