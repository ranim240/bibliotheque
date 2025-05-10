-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 10 mai 2025 à 16:00
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bibliotheque`
--

-- --------------------------------------------------------

--
-- Structure de la table `emprunts`
--

DROP TABLE IF EXISTS `emprunts`;
CREATE TABLE IF NOT EXISTS `emprunts` (
  `id_emprunt` int NOT NULL AUTO_INCREMENT,
  `id_livre` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `date_emprunt` datetime NOT NULL,
  `date_retour` datetime DEFAULT NULL,
  PRIMARY KEY (`id_emprunt`),
  KEY `id_livre` (`id_livre`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `emprunts`
--

INSERT INTO `emprunts` (`id_emprunt`, `id_livre`, `id_utilisateur`, `date_emprunt`, `date_retour`) VALUES
(1, 1, 1, '2025-05-01 10:00:00', NULL),
(2, 2, 2, '2025-04-15 14:30:00', '2025-05-05 12:00:00'),
(3, 3, 3, '2025-05-03 09:15:00', NULL),
(4, 4, 4, '2025-04-20 11:00:00', '2025-05-02 15:30:00');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

DROP TABLE IF EXISTS `livres`;
CREATE TABLE IF NOT EXISTS `livres` (
  `id_livre` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(255) NOT NULL,
  `annee` int DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_livre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id_livre`, `titre`, `auteur`, `annee`, `genre`, `created_at`) VALUES
(1, 'Le Petit Prince', 'Antoine de Saint-Exupéry', 1943, 'Conte', '2025-05-10 16:00:19'),
(2, '1984', 'George Orwell', 1949, 'Dystopie', '2025-05-10 16:00:19'),
(3, 'Harry Potter à l\'école des sorciers', 'J.K. Rowling', 1997, 'Fantasy', '2025-05-10 16:00:19'),
(4, 'Les Misérables', 'Victor Hugo', 1862, 'Roman', '2025-05-10 16:00:19'),
(5, 'L\'Étranger', 'Albert Camus', 1942, 'Philosophique', '2025-05-10 16:00:19');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `created_at`) VALUES
(1, 'Dupont', 'Jean', 'jean.dupont@example.com', '2025-05-10 16:00:19'),
(2, 'Martin', 'Sophie', 'sophie.martin@example.com', '2025-05-10 16:00:19'),
(3, 'Lefèvre', 'Pierre', 'pierre.lefevre@example.com', '2025-05-10 16:00:19'),
(4, 'Dubois', 'Marie', 'marie.dubois@example.com', '2025-05-10 16:00:19');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `emprunts`
--
ALTER TABLE `emprunts`
  ADD CONSTRAINT `emprunts_ibfk_1` FOREIGN KEY (`id_livre`) REFERENCES `livres` (`id_livre`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
