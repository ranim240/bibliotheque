<?php
// Démarrer la session pour les messages
session_start();

// Connexion à la base de données avec PDO
$host = 'localhost';
$dbname = 'bibliotheque';
$username = 'root'; // Remplacez par votre utilisateur MySQL
$password = ''; // Remplacez par votre mot de passe MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Initialiser les messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Gestion des actions (ajout, modification, suppression, recherche, emprunt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter ou modifier un livre
    if (isset($_POST['action']) && ($_POST['action'] === 'add_livre' || $_POST['action'] === 'edit_livre')) {
        $titre = trim($_POST['titre'] ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $annee = isset($_POST['annee']) ? (int)$_POST['annee'] : null;
        $genre = trim($_POST['genre'] ?? '');
        $id_livre = isset($_POST['id_livre']) ? (int)$_POST['id_livre'] : null;

        if ($titre && $auteur) {
            try {
                // Vérifier si le livre existe déjà (basé sur titre et auteur)
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM livres WHERE titre = ? AND auteur = ? AND id_livre != ?");
                $check_stmt->execute([$titre, $auteur, $id_livre ?: 0]);
                $exists = $check_stmt->fetchColumn();

                if ($exists > 0 && $_POST['action'] === 'add_livre') {
                    $_SESSION['error_message'] = "Un livre avec ce titre et cet auteur existe déjà.";
                } else {
                    if ($_POST['action'] === 'add_livre') {
                        $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, annee, genre) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$titre, $auteur, $annee ?: null, $genre]);
                        $_SESSION['success_message'] = "Livre ajouté avec succès.";
                    } elseif ($_POST['action'] === 'edit_livre' && $id_livre) {
                        $stmt = $pdo->prepare("UPDATE livres SET titre = ?, auteur = ?, annee = ?, genre = ? WHERE id_livre = ?");
                        $stmt->execute([$titre, $auteur, $annee ?: null, $genre, $id_livre]);
                        $_SESSION['success_message'] = "Livre modifié avec succès.";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Le titre et l'auteur sont requis.";
        }
    }

  
    // Ajouter ou modifier un utilisateur
    if (isset($_POST['action']) && ($_POST['action'] === 'add_utilisateur' || $_POST['action'] === 'edit_utilisateur')) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $id_utilisateur = isset($_POST['id_utilisateur']) ? (int)$_POST['id_utilisateur'] : null;

    if ($nom && $prenom && $email) {
        try {
            if ($_POST['action'] === 'add_utilisateur') {
                // Vérifier si l'email existe déjà
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
                $check_stmt->execute([$email]);
                if ($check_stmt->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Cet email est déjà utilisé.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email) VALUES (?, ?, ?)");
                    $stmt->execute([$nom, $prenom, $email]);
                    $_SESSION['success_message'] = "Utilisateur ajouté avec succès.";
                }
            } elseif ($_POST['action'] === 'edit_utilisateur' && $id_utilisateur) {
                // Vérifier si l'email est déjà utilisé par un autre utilisateur
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ? AND id_utilisateur != ?");
                $check_stmt->execute([$email, $id_utilisateur]);
                if ($check_stmt->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Cet email est déjà utilisé par un autre utilisateur.";
                } else {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?");
                    $stmt->execute([$nom, $prenom, $email, $id_utilisateur]);
                    $_SESSION['success_message'] = "Utilisateur modifié avec succès.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de l'opération sur l'utilisateur : " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Le nom, le prénom et un email valide sont requis.";
    }
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Code existant pour add_livre, edit_livre, add_utilisateur, etc.)

    // Marquer un emprunt comme retourné
    if (isset($_POST['action']) && $_POST['action'] === 'return_emprunt' && isset($_POST['id_emprunt'])) {
        try {
            $stmt = $pdo->prepare("UPDATE emprunts SET date_retour = NOW() WHERE id_emprunt = ?");
            $stmt->execute([(int)$_POST['id_emprunt']]);
            $_SESSION['success_message'] = "Livre retourné avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // AJOUTER ICI : Supprimer un livre
    if (isset($_POST['action']) && $_POST['action'] === 'delete_livre' && isset($_POST['id_livre'])) {
        try {
            $id_livre = (int)$_POST['id_livre'];
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE id_livre = ? AND date_retour IS NULL");
            $check_stmt->execute([$id_livre]);
            if ($check_stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Impossible de supprimer ce livre : il est actuellement emprunté.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
                $stmt->execute([$id_livre]);
                $_SESSION['success_message'] = "Livre supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de la suppression du livre : " . $e->getMessage();
        }
    }

    // AJOUTER ICI : Supprimer un utilisateur
    if (isset($_POST['action']) && $_POST['action'] === 'delete_utilisateur' && isset($_POST['id_utilisateur'])) {
        try {
            $id_utilisateur = (int)$_POST['id_utilisateur'];
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE id_utilisateur = ? AND date_retour IS NULL");
            $check_stmt->execute([$id_utilisateur]);
            if ($check_stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Impossible de supprimer cet utilisateur : il a des emprunts actifs.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);
                $_SESSION['success_message'] = "Utilisateur supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de la suppression de l'utilisateur : " . $e->getMessage();
        }
    }

    // Redirection après action pour éviter resoumission
    $redirect_section = 'dashboard';
    if (isset($_POST['action'])) {
        if (strpos($_POST['action'], 'livre') !== false) {
            $redirect_section = 'list_livres';
        } elseif (strpos($_POST['action'], 'utilisateur') !== false) {
            $redirect_section = 'list_utilisateurs';
        } elseif (strpos($_POST['action'], 'emprunt') !== false) {
            $redirect_section = 'list_emprunts';
        }
    }
    header("Location: dashboard.php?section=$redirect_section");
    exit;
}

    // Ajouter un emprunt
   
    if (isset($_POST['action']) && $_POST['action'] === 'add_emprunt') {
    $id_livre = (int)$_POST['id_livre'] ?? 0;
    $id_utilisateur = (int)$_POST['id_utilisateur'] ?? 0;

    if ($id_livre && $id_utilisateur) {
        try {
            // Vérifier si le livre est déjà emprunté
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE id_livre = ? AND date_retour IS NULL");
            $check_stmt->execute([$id_livre]);
            if ($check_stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Ce livre est déjà emprunté.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO emprunts (id_livre, id_utilisateur, date_emprunt) VALUES (?, ?, NOW())");
                $stmt->execute([$id_livre, $id_utilisateur]);
                $_SESSION['success_message'] = "Emprunt enregistré avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement de l'emprunt : " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Veuillez sélectionner un livre et un utilisateur.";
    }
}

    // Marquer un emprunt comme retourné
    if (isset($_POST['action']) && $_POST['action'] === 'return_emprunt' && isset($_POST['id_emprunt'])) {
        try {
            $stmt = $pdo->prepare("UPDATE emprunts SET date_retour = NOW() WHERE id_emprunt = ?");
            $stmt->execute([(int)$_POST['id_emprunt']]);
            $_SESSION['success_message'] = "Livre retourné avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // Redirection après action pour éviter resoumission
    $redirect_section = 'dashboard';
    if (isset($_POST['action'])) {
        if (strpos($_POST['action'], 'livre') !== false) {
            $redirect_section = 'list_livres';
        } elseif (strpos($_POST['action'], 'utilisateur') !== false) {
            $redirect_section = 'list_utilisateurs';
        } elseif (strpos($_POST['action'], 'emprunt') !== false) {
            $redirect_section = 'list_emprunts';
        }
    }
    header("Location: dashboard.php?section=$redirect_section");
    exit;
}

// Récupérer tous les genres distincts pour le filtre
$stmt = $pdo->query("SELECT DISTINCT genre FROM livres WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
$genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer tous les livres avec filtrage par genre, année et recherche
$livres_query = "SELECT * FROM livres";
$livres_params = [];
$where_clauses = [];

if (isset($_GET['filter_genre']) && !empty($_GET['filter_genre']) && $_GET['filter_genre'] !== 'all') {
    $where_clauses[] = "genre = ?";
    $livres_params[] = $_GET['filter_genre'];
}

if (isset($_GET['search_livre']) && !empty($_GET['search_livre'])) {
    $search = '%' . trim($_GET['search_livre']) . '%';
    $where_clauses[] = "(titre LIKE ? OR auteur LIKE ?)";
    $livres_params[] = $search;
    $livres_params[] = $search;
}

if (isset($_GET['filter_annee']) && !empty($_GET['filter_annee'])) {
    $where_clauses[] = "annee = ?";
    $livres_params[] = (int)$_GET['filter_annee'];
}

if (!empty($where_clauses)) {
    $livres_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $pdo->prepare($livres_query);
$stmt->execute($livres_params);
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier la disponibilité des livres
$emprunts_actifs = $pdo->query("SELECT id_livre FROM emprunts WHERE date_retour IS NULL")->fetchAll(PDO::FETCH_COLUMN);
$livres_disponibles = array_map(function ($livre) use ($emprunts_actifs) {
    $livre['disponible'] = !in_array($livre['id_livre'], $emprunts_actifs);
    return $livre;
}, $livres);

// Récupérer tous les utilisateurs avec recherche par nom ou email
$utilisateurs_query = "SELECT * FROM utilisateurs";
$utilisateurs_params = [];
$where_clauses = [];

if (isset($_GET['search_utilisateur']) && !empty($_GET['search_utilisateur'])) {
    $search = '%' . trim($_GET['search_utilisateur']) . '%';
    $where_clauses[] = "(nom LIKE ? OR email LIKE ?)";
    $utilisateurs_params[] = $search;
    $utilisateurs_params[] = $search;
}

if (!empty($where_clauses)) {
    $utilisateurs_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $pdo->prepare($utilisateurs_query);
$stmt->execute($utilisateurs_params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les emprunts avec filtrage par statut et date
$emprunts_query = "
    SELECT e.id_emprunt, e.id_livre, e.id_utilisateur, e.date_emprunt, e.date_retour, 
           l.titre, u.nom, u.prenom 
    FROM emprunts e 
    JOIN livres l ON e.id_livre = l.id_livre 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
";
$where_clauses = [];
$emprunts_params = [];

$filter = $_GET['filter_emprunts'] ?? 'all';
if ($filter === 'active') {
    $where_clauses[] = "e.date_retour IS NULL";
} elseif ($filter === 'returned') {
    $where_clauses[] = "e.date_retour IS NOT NULL";
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $where_clauses[] = "e.date_emprunt >= ?";
    $emprunts_params[] = $_GET['date_debut'];
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $where_clauses[] = "e.date_emprunt <= ?";
    $emprunts_params[] = $_GET['date_fin'];
}

if (!empty($where_clauses)) {
    $emprunts_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$emprunts_query .= " ORDER BY e.date_emprunt DESC LIMIT 5"; // Limiter à 5 pour l'aperçu
$stmt = $pdo->prepare($emprunts_query);
$stmt->execute($emprunts_params);
$emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques : Nombre d'emprunts par utilisateur
$stats_query = "
    SELECT u.id_utilisateur, u.nom, u.prenom, 
           COUNT(e.id_emprunt) as total_emprunts, 
           SUM(CASE WHEN e.date_retour IS NULL THEN 1 ELSE 0 END) as emprunts_actifs
    FROM utilisateurs u
    LEFT JOIN emprunts e ON u.id_utilisateur = e.id_utilisateur
    GROUP BY u.id_utilisateur, u.nom, u.prenom
    ORDER BY total_emprunts DESC
";
$stats = $pdo->query($stats_query)->fetchAll(PDO::FETCH_ASSOC);

// Déterminer la section active
$section = $_GET['section'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestion de Bibliothèque</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #fff5f3;
            /* Variation claire de #fae1dd pour le fond */
        }

        .sidebar {
            width: 250px;
            background-color: #fae1dd;
            /* Couleur principale */
            min-height: 100vh;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar .nav {
            flex-direction: column;
        }

        .sidebar .nav-link {
            color: #333 !important;
            /* Texte sombre pour contraste */
            padding: 10px 20px;
            border-radius: 0;
            margin: 0;
            border-bottom: 1px solid #e6b8b2;
            /* Variation plus foncée de #fae1dd */
        }

        .sidebar .nav-link:hover {
            background-color: #fcd5ce;
            /* Couleur d'accent pour survol */
        }

        .sidebar .nav-link.active {
            background-color: #e6b8b2;
            /* Variation plus foncée pour l'élément actif */
            border-color: #e6b8b2;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .dashboard {
            text-align: center;
        }

        .dashboard h2 {
            color: #ff70a6;
            /* Couleur spécifique pour "Bienvenue dans votre Bibliothèque" */
        }

        .card {
            border-color: #fae1dd;
            /* Bordures des cartes */
            background-color: #fff5f3;
            /* Fond clair pour les cartes */
        }

        .card-title {
            color: #ff70a6;
            /* Couleur pour les titres des cartes */
        }

        .card-title i {
            color: #ff70a6;
            /* Icônes des titres des cartes */
        }

        .btn-primary {
            background-color: #fae1dd;
            /* Boutons principaux */
            border-color: #fae1dd;
            color: #333;
            /* Texte sombre pour lisibilité */
        }

        .btn-primary:hover {
            background-color: #fcd5ce;
            /* Survol des boutons principaux */
            border-color: #fcd5ce;
        }

        .btn-warning {
            background-color: #e6b8b2;
            /* Variation pour les boutons d'édition */
            border-color: #e6b8b2;
            color: #333;
        }

        .btn-warning:hover {
            background-color: #fcd5ce;
            /* Survol */
            border-color: #fcd5ce;
        }

        .btn-success {
            background-color: #fae1dd;
            /* Boutons de succès (retour d'emprunt) */
            border-color: #fae1dd;
            color: #333;
        }

        .btn-success:hover {
            background-color: #fcd5ce;
            /* Survol */
            border-color: #fcd5ce;
        }

        .btn-secondary {
            background-color: #f7d1cd;
            /* Boutons secondaires */
            border-color: #f7d1cd;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #fcd5ce;
            /* Survol */
            border-color: #fcd5ce;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .bi {
            margin-right: 5px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .disponible {
            color: #d89b94;
            /* Variation plus foncée pour contraste */
            font-weight: bold;
        }

        .emprunte {
            color: #e6b8b2;
            /* Variation pour les livres empruntés */
            font-weight: bold;
        }

        /* Styles pour les titres des sections */
        section.list_livres h2,
        section.add_livre h2,
        section.list_utilisateurs h2,
        section.add_utilisateur h2,
        section.list_emprunts h2,
        section.add_emprunt h2,
        .bienfaits h2,
        .featured-books h2 {
            color: #ff70a6;
            /* Couleur pour les titres des sections */
        }

        section.list_livres h2 i,
        section.add_livre h2 i,
        section.list_utilisateurs h2 i,
        section.add_utilisateur h2 i,
        section.list_emprunts h2 i,
        section.add_emprunt h2 i,
        .bienfaits h2 i,
        .featured-books h2 i {
            color: #ff70a6;
            /* Icônes des titres */
        }

        .form-section {
            max-width: 600px;
            margin: 0 auto;
        }

        .alert {
            position: sticky;
            top: 20px;
            z-index: 1000;
        }

        .alert-success {
            background-color: #fff5f3;
            /* Fond clair pour alerte succès */
            color: #d89b94;
            /* Texte contrasté */
        }

        .alert-danger {
            background-color: #fcd5ce;
            /* Fond pour alerte erreur */

            color: #d89b94;
        }

        /* Styles pour l'image d'en-tête */
        .header-image {
            background: url('couverture3.jpg') no-repeat center center;
            background-size: cover;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e6b8b2;
        }

        .header-image h1 {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            font-size: 2.5rem;
            background-color: rgba(250, 225, 221, 0.7); /* #fae1dd avec opacité */
            padding: 10px 20px;
            border-radius: 5px;
        }

        /* Styles pour le carrousel Bienfaits de la lecture */
        .bienfaits {
            margin-bottom: 30px;
        }

        .swiper {
            width: 100%;
            height: 300px;
        }

        .swiper-slide {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            font-size: 1.2rem;
            background-color: #fff5f3;
            border: 1px solid #fae1dd;
            border-radius: 10px;
            padding: 20px;
        }

        .swiper-slide h3 {
            color: #ff70a6;
        }

        .swiper-slide p {
            color: #333;
        }

        /* Styles pour les livres en vedette */
        .featured-books {
            margin-bottom: 30px;
        }

        .featured-books .card {
            transition: transform 0.3s;
        }

        .featured-books .card:hover {
            transform: scale(1.05);
            background-color: #fcd5ce;
        }

        .featured-books .card-img-top {
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #e6b8b2;
        }

        .featured-books .card-title {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar .nav {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .sidebar .nav-link {
                border-bottom: none;
                border-right: 1px solid #e6b8b2;
            }

            .header-image h1 {
                font-size: 1.5rem;
            }

            .swiper {
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link <?= $section === 'dashboard' ? 'active' : '' ?>" href="?section=dashboard">
                <i class="bi bi-house-door"></i> Accueil
            </a>
            <a class="nav-link <?= in_array($section, ['add_livre', 'list_livres']) ? 'active' : '' ?>" href="?section=list_livres">
                <i class="bi bi-book"></i> Livres
            </a>
            <a class="nav-link <?= $section === 'add_livre' ? 'active' : '' ?>" href="?section=add_livre" style="padding-left: 40px;">
                <i class="bi bi-plus-circle"></i> Ajouter
            </a>
            <a class="nav-link <?= $section === 'list_livres' ? 'active' : '' ?>" href="?section=list_livres" style="padding-left: 40px;">
                <i class="bi bi-list"></i> Liste
            </a>
            <a class="nav-link <?= in_array($section, ['add_utilisateur', 'list_utilisateurs']) ? 'active' : '' ?>" href="?section=list_utilisateurs">
                <i class="bi bi-people"></i> Utilisateurs
            </a>
            <a class="nav-link <?= $section === 'add_utilisateur' ? 'active' : '' ?>" href="?section=add_utilisateur" style="padding-left: 40px;">
                <i class="bi bi-plus-circle"></i> Ajouter
            </a>
            <a class="nav-link <?= $section === 'list_utilisateurs' ? 'active' : '' ?>" href="?section=list_utilisateurs" style="padding-left: 40px;">
                <i class="bi bi-list"></i> Liste
            </a>
            <a class="nav-link <?= in_array($section, ['add_emprunt', 'list_emprunts']) ? 'active' : '' ?>" href="?section=list_emprunts">
                <i class="bi bi-arrow-right-circle"></i> Emprunts
            </a>
            <a class="nav-link <?= $section === 'add_emprunt' ? 'active' : '' ?>" href="?section=add_emprunt" style="padding-left: 40px;">
                <i class="bi bi-plus-circle"></i> Ajouter
            </a>
            <a class="nav-link <?= $section === 'list_emprunts' ? 'active' : '' ?>" href="?section=list_emprunts" style="padding-left: 40px;">
                <i class="bi bi-list"></i> Liste
            </a>
            <a class="nav-link <?= $section === 'stats' ? 'active' : '' ?>" href="?section=stats">
                <i class="bi bi-bar-chart"></i> Statistiques
            </a>
        </nav>
    </div>
    <div class="content">
        <!-- Afficher les messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Contenu de la section -->
        <?php if ($section === 'dashboard'): ?>
            <div class="dashboard">
                <!-- Image d'en-tête -->
                <div class="header-image">
                    <h1>Bienvenue dans votre Bibliothèque</h1>
                </div>

                <!-- Bienfaits de la lecture -->
                <!-- <div class="bienfaits">
                    <h2><i class="bi bi-lightbulb"></i> Bienfaits de la Lecture</h2>
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <h3>Amélioration de la Concentration</h3>
                                <p>La lecture stimule l'attention et la mémoire à long terme.</p>
                            </div>
                            <div class="swiper-slide">
                                <h3>Réduction du Stress</h3>
                                <p>Une bonne histoire peut apaiser l'esprit en quelques minutes.</p>
                            </div>
                            <div class="swiper-slide">
                                <h3>Développement Personnel</h3>
                                <p>Apprenez de nouvelles perspectives et élargissez vos horizons.</p>
                            </div>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>

                Nos Livres en Vedette -->
                <!-- <div class="featured-books">
                    <h2><i class="bi bi-star"></i> Nos Livres en Vedette</h2>
                    <div class="row">
                        <?php
                        // Récupérer quelques livres en vedette (par exemple, les 3 premiers)
                        $featured_livres = array_slice($livres_disponibles, 0, 3);
                        foreach ($featured_livres as $livre):
                        ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <img src="https://picsum.photos/200/300?rand<?= $livre['id_livre'] ?>" class="card-img-top" alt="<?= htmlspecialchars($livre['titre']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($livre['titre']) ?></h5>
                                        <p class="card-text">Auteur : <?= htmlspecialchars($livre['auteur']) ?></p>
                                        <p class="card-text">
                                            Statut : <?= $livre['disponible'] ? '<span class="disponible">Disponible</span>' : '<span class="emprunte">Emprunté</span>' ?>
                                        </p>
                                        <a href="?section=add_emprunt" class="btn btn-primary">Emprunter</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div> -->

                <h3>Gérez facilement vos livres, utilisateurs et emprunts.</h3>
                <!-- <p></p> -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-book"></i> Livres</h5>
                                <p class="card-text"><?= count($livres) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people"></i> Utilisateurs</h5>
                                <p class="card-text"><?= count($utilisateurs) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-arrow-right-circle"></i> Emprunts Actifs</h5>
                                <p class="card-text"><?= count(array_filter($emprunts, fn($e) => is_null($e['date_retour']))) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <h4>Actions Rapides</h4>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="?section=add_livre" class="btn btn-primary"><i class="bi bi-book"></i> Ajouter un Livre</a>
                        <a href="?section=add_emprunt" class="btn btn-primary"><i class="bi bi-arrow-right-circle"></i> Nouvel Emprunt</a>
                        <a href="?section=add_utilisateur" class="btn btn-primary"><i class="bi bi-person-plus"></i> Ajouter un Utilisateur</a>
                    </div>
                </div>
                <div>
                    <h4>Derniers Emprunts</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Utilisateur</th>
                                    <th>Date d'Emprunt</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emprunts as $emprunt): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($emprunt['titre']) ?></td>
                                        <td><?= htmlspecialchars($emprunt['nom']) ?> <?= htmlspecialchars($emprunt['prenom']) ?></td>
                                        <td><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                                        <td>
                                            <?php if (is_null($emprunt['date_retour'])): ?>
                                                <span class="disponible">Actif</span>
                                            <?php else: ?>
                                                <span class="emprunte">Retourné</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="?section=list_emprunts" class="btn btn-secondary">Voir tous les emprunts</a>
                </div>
            </div>

        <?php elseif ($section === 'add_livre'): ?>
            <!-- Formulaire pour ajouter un livre -->
            <div class="form-section">
                <h2><i class="bi bi-book"></i> Ajouter un Livre</h2>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="add_livre">
                    <div class="col-md-6">
                        <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="titre" id="titre" placeholder="Ex. Le Petit Prince" required>
                    </div>
                    <div class="col-md-6">
                        <label for="auteur" class="form-label">Auteur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="auteur" id="auteur" placeholder="Ex. Antoine de Saint-Exupéry" required>
                    </div>
                    <div class="col-md-6">
                        <label for="annee" class="form-label">Année</label>
                        <input type="number" class="form-control" name="annee" id="annee" placeholder="Ex. 1943">
                    </div>
                    <div class="col-md-6">
                        <label for="genre" class="form-label">Genre</label>
                        <input type="text" class="form-control" name="genre" id="genre" placeholder="Ex. Conte">
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <a href="?section=dashboard" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Ajouter le Livre</button>
                    </div>
                </form>
            </div>

        <?php elseif ($section === 'list_livres'): ?>
            <!-- Liste des livres -->
            <h2><i class="bi bi-book-fill"></i> Liste des Livres</h2>
            <form method="get" class="mb-3">
                <input type="hidden" name="section" value="list_livres">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="filter_genre" class="form-label">Filtrer par Genre</label>
                        <select name="filter_genre" id="filter_genre" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= ($_GET['filter_genre'] ?? 'all') === 'all' ? 'selected' : '' ?>>Tous les Genres</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= htmlspecialchars($genre) ?>" <?= ($_GET['filter_genre'] ?? '') === $genre ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($genre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_annee" class="form-label">Filtrer par Année</label>
                        <input type="number" class="form-control" name="filter_annee" id="filter_annee" placeholder="Ex. 2020" value="<?= htmlspecialchars($_GET['filter_annee'] ?? '') ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-4">
                        <label for="search_livre" class="form-label">Rechercher</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_livre" id="search_livre" placeholder="Titre ou Auteur..." value="<?= htmlspecialchars($_GET['search_livre'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Rechercher un livre"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </div>
            </form>
            <?php if ((isset($_GET['search_livre']) && !empty($_GET['search_livre'])) || (isset($_GET['filter_genre']) && $_GET['filter_genre'] !== 'all') || (isset($_GET['filter_annee']) && !empty($_GET['filter_annee']))): ?>
                <a href="?section=list_livres" class="btn btn-secondary mb-3"><i class="bi bi-x-circle"></i> Réinitialiser les Filtres</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Année</th>
                            <th>Genre</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livres_disponibles as $livre): ?>
                            <tr>
                                <td><?= htmlspecialchars($livre['id_livre']) ?></td>
                                <td><?= htmlspecialchars($livre['titre']) ?></td>
                                <td><?= htmlspecialchars($livre['auteur']) ?></td>
                                <td><?= htmlspecialchars($livre['annee'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($livre['genre'] ?: '-') ?></td>
                                <td>
                                    <?php if ($livre['disponible']): ?>
                                        <span class="disponible">Disponible</span>
                                    <?php else: ?>
                                        <span class="emprunte">Emprunté</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editLivreModal<?= $livre['id_livre'] ?>" data-bs-toggle="tooltip" title="Modifier ce livre">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete_livre">
                                        <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')" data-bs-toggle="tooltip" title="Supprimer ce livre">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Modal pour modifier un livre -->
                            <div class="modal fade" id="editLivreModal<?= $livre['id_livre'] ?>" tabindex="-1" aria-labelledby="editLivreModalLabel<?= $livre['id_livre'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editLivreModalLabel<?= $livre['id_livre'] ?>">Modifier le Livre</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="action" value="edit_livre">
                                                <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
                                                <div class="mb-3">
                                                    <label for="titre_<?= $livre['id_livre'] ?>" class="form-label">Titre</label>
                                                    <input type="text" class="form-control" name="titre" id="titre_<?= $livre['id_livre'] ?>" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="auteur_<?= $livre['id_livre'] ?>" class="form-label">Auteur</label>
                                                    <input type="text" class="form-control" name="auteur" id="auteur_<?= $livre['id_livre'] ?>" value="<?= htmlspecialchars($livre['auteur']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="annee_<?= $livre['id_livre'] ?>" class="form-label">Année</label>
                                                    <input type="number" class="form-control" name="annee" id="annee_<?= $livre['id_livre'] ?>" value="<?= htmlspecialchars($livre['annee']) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="genre_<?= $livre['id_livre'] ?>" class="form-label">Genre</label>
                                                    <input type="text" class="form-control" name="genre" id="genre_<?= $livre['id_livre'] ?>" value="<?= htmlspecialchars($livre['genre']) ?>">
                                                </div>
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="?section=dashboard" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Retour</a>

        <?php elseif ($section === 'add_utilisateur'): ?>
            <!-- Formulaire pour ajouter un utilisateur -->
            <div class="form-section">
                <h2><i class="bi bi-person-plus"></i> Ajouter un Utilisateur</h2>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="add_utilisateur">
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nom" id="nom" placeholder="Ex. Dupont" required>
                    </div>
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="prenom" id="prenom" placeholder="Ex. Jean" required>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="Ex. jean.dupont@example.com" required>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <a href="?section=dashboard" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Ajouter l'Utilisateur</button>
                    </div>
                </form>
            </div>

        <?php elseif ($section === 'list_utilisateurs'): ?>
            <!-- Liste des utilisateurs -->
            <h2><i class="bi bi-people-fill"></i> Liste des Utilisateurs</h2>
            <form method="get" class="mb-3">
                <input type="hidden" name="section" value="list_utilisateurs">
                <div class="input-group">
                    <input type="text" class="form-control" name="search_utilisateur" placeholder="Rechercher par nom ou email..." value="<?= htmlspecialchars($_GET['search_utilisateur'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Rechercher un utilisateur"><i class="bi bi-search"></i></button>
                </div>
            </form>
            <?php if (isset($_GET['search_utilisateur']) && !empty($_GET['search_utilisateur'])): ?>
                <a href="?section=list_utilisateurs" class="btn btn-secondary mb-3"><i class="bi bi-x-circle"></i> Réinitialiser</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr>
                                <td><?= htmlspecialchars($utilisateur['id_utilisateur']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['prenom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                <td class="actions">
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUtilisateurModal<?= $utilisateur['id_utilisateur'] ?>" data-bs-toggle="tooltip" title="Modifier cet utilisateur">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete_utilisateur">
                                        <input type="hidden" name="id_utilisateur" value="<?= $utilisateur['id_utilisateur'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')" data-bs-toggle="tooltip" title="Supprimer cet utilisateur">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Modal pour modifier un utilisateur -->
                            <div class="modal fade" id="editUtilisateurModal<?= $utilisateur['id_utilisateur'] ?>" tabindex="-1" aria-labelledby="editUtilisateurModalLabel<?= $utilisateur['id_utilisateur'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editUtilisateurModalLabel<?= $utilisateur['id_utilisateur'] ?>">Modifier l'Utilisateur</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="action" value="edit_utilisateur">
                                                <input type="hidden" name="id_utilisateur" value="<?= $utilisateur['id_utilisateur'] ?>">
                                                <div class="mb-3">
                                                    <label for="nom_<?= $utilisateur['id_utilisateur'] ?>" class="form-label">Nom</label>
                                                    <input type="text" class="form-control" name="nom" id="nom_<?= $utilisateur['id_utilisateur'] ?>" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="prenom_<?= $utilisateur['id_utilisateur'] ?>" class="form-label">Prénom</label>
                                                    <input type="text" class="form-control" name="prenom" id="prenom_<?= $utilisateur['id_utilisateur'] ?>" value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="email_<?= $utilisateur['id_utilisateur'] ?>" class="form-label">Email</label>
                                                    <input type="email" class="form-control" name="email" id="email_<?= $utilisateur['id_utilisateur'] ?>" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="?section=dashboard" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Retour</a>

        <?php elseif ($section === 'add_emprunt'): ?>
            <!-- Formulaire pour ajouter un emprunt -->
            <div class="form-section">
                <h2><i class="bi bi-arrow-right-circle"></i> Nouvel Emprunt</h2>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="add_emprunt">
                    <div class="col-12">
                        <label for="id_livre" class="form-label">Livre <span class="text-danger">*</span></label>
                        <select name="id_livre" id="id_livre" class="form-select" required>
                            <option value="">Sélectionner un livre</option>
                            <?php foreach ($livres as $livre): ?>
                                <option value="<?= $livre['id_livre'] ?>"><?= htmlspecialchars($livre['titre']) ?> (<?= htmlspecialchars($livre['auteur']) ?>) <?= in_array($livre['id_livre'], $emprunts_actifs) ? '[Emprunté]' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="id_utilisateur" class="form-label">Utilisateur <span class="text-danger">*</span></label>
                        <select name="id_utilisateur" id="id_utilisateur" class="form-select" required>
                            <option value="">Sélectionner un utilisateur</option>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <option value="<?= $utilisateur['id_utilisateur'] ?>"><?= htmlspecialchars($utilisateur['nom']) ?> <?= htmlspecialchars($utilisateur['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <a href="?section=dashboard" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Enregistrer l'Emprunt</button>
                    </div>
                </form>
            </div>

        <?php elseif ($section === 'list_emprunts'): ?>
            <!-- Liste des emprunts -->
            <h2><i class="bi bi-list-check"></i> Liste des Emprunts</h2>
            <form method="get" class="mb-3">
                <input type="hidden" name="section" value="list_emprunts">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="filter_emprunts" class="form-label">Filtrer par Statut</label>
                        <select name="filter_emprunts" id="filter_emprunts" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tous les Emprunts</option>
                            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Actifs</option>
                            <option value="returned" <?= $filter === 'returned' ? 'selected' : '' ?>>Retournés</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date_debut" class="form-label">Date de Début</label>
                        <input type="date" class="form-control" name="date_debut" id="date_debut" value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-4">
                        <label for="date_fin" class="form-label">Date de Fin</label>
                        <input type="date" class="form-control" name="date_fin" id="date_fin" value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
            <?php if ((isset($_GET['filter_emprunts']) && $_GET['filter_emprunts'] !== 'all') || (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) || (isset($_GET['date_fin']) && !empty($_GET['date_fin']))): ?>
                <a href="?section=list_emprunts" class="btn btn-secondary mb-3"><i class="bi bi-x-circle"></i> Réinitialiser</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Livre</th>
                            <th>Utilisateur</th>
                            <th>Date d'Emprunt</th>
                            <th>Date de Retour</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprunts as $emprunt): ?>
                            <tr>
                                <td><?= htmlspecialchars($emprunt['id_emprunt']) ?></td>
                                <td><?= htmlspecialchars($emprunt['titre']) ?></td>
                                <td><?= htmlspecialchars($emprunt['nom']) ?> <?= htmlspecialchars($emprunt['prenom']) ?></td>
                                <td><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                                <td><?= htmlspecialchars($emprunt['date_retour'] ?: 'Non retourné') ?></td>
                                <td>
                                    <?php if (is_null($emprunt['date_retour'])): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="return_emprunt">
                                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Marquer ce livre comme retourné">
                                                <i class="bi bi-arrow-left-circle"></i> Retour
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="?section=dashboard" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Retour</a>

        <?php elseif ($section === 'stats'): ?>
            <!-- Section Statistiques -->
            <h2><i class="bi bi-bar-chart"></i> Statistiques</h2>
            <h3>Nombre d'Emprunts par Utilisateur</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Nombre Total d'Emprunts</th>
                            <th>Emprunts Actifs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td><?= htmlspecialchars($stat['nom']) ?> <?= htmlspecialchars($stat['prenom']) ?></td>
                                <td><?= htmlspecialchars($stat['total_emprunts']) ?></td>
                                <td><?= htmlspecialchars($stat['emprunts_actifs']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="?section=dashboard" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        // Activer les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialiser le Swiper
        var swiper = new Swiper(".mySwiper", {
            pagination: {
                el: ".swiper-pagination",
                dynamicBullets: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            loop: true,
        });
    </script>
</body>

</html>
```

<?php