<?php
// Connexion à la base de données avec MySQLi
$mysqli = new mysqli("localhost", "root", "", "bibliotheque");

// Vérifier la connexion
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

// Message d'erreur ou de succès
$message = '';

// Récupérer la section à afficher
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Traitement des actions (ajout, modification, suppression, emprunt)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ajouter un livre
    if (isset($_POST['ajouter_livre'])) {
        $titre = $_POST['titre'];
        $auteur = $_POST['auteur'];
        $annee = (int)$_POST['annee'];
        $genre = $_POST['genre'];
        $stmt = $mysqli->prepare("INSERT INTO Livres (titre, auteur, annee, genre) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $titre, $auteur, $annee, $genre);
        try {
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Livre ajouté avec succès.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Erreur lors de l'ajout du livre.</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    }
    // Modifier un livre
    if (isset($_POST['modifier_livre'])) {
        $id_livre = $_POST['id_livre'];
        $titre = $_POST['titre'];
        $auteur = $_POST['auteur'];
        $annee = (int)$_POST['annee'];
        $genre = $_POST['genre'];
        $stmt = $mysqli->prepare("UPDATE Livres SET titre = ?, auteur = ?, annee = ?, genre = ? WHERE id_livre = ?");
        $stmt->bind_param("ssisi", $titre, $auteur, $annee, $genre, $id_livre);
        try {
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Livre modifié avec succès.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Erreur lors de la modification du livre.</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    }
    // Ajouter un utilisateur
    if (isset($_POST['ajouter_utilisateur'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $stmt = $mysqli->prepare("INSERT INTO Utilisateurs (nom, prenom, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nom, $prenom, $email);
        try {
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Utilisateur ajouté avec succès.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Erreur lors de l'ajout de l'utilisateur.</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    }
    // Modifier un utilisateur
    if (isset($_POST['modifier_utilisateur'])) {
        $id_utilisateur = $_POST['id_utilisateur'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $stmt = $mysqli->prepare("UPDATE Utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?");
        $stmt->bind_param("sssi", $nom, $prenom, $email, $id_utilisateur);
        try {
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Utilisateur modifié avec succès.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Erreur lors de la modification de l'utilisateur.</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    }
    // Ajouter un emprunt
    if (isset($_POST['ajouter_emprunt'])) {
        $id_livre = $_POST['id_livre'];
        $id_utilisateur = $_POST['id_utilisateur'];
        $date_emprunt = date('Y-m-d');

        // Vérifier si le livre est déjà emprunté et non retourné
        $stmt_check = $mysqli->prepare("SELECT * FROM Emprunts WHERE id_livre = ? AND date_retour IS NULL");
        $stmt_check->bind_param("i", $id_livre);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Ce livre est déjà emprunté et n'a pas été retourné.</div>";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO Emprunts (id_livre, id_utilisateur, date_emprunt) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_livre, $id_utilisateur, $date_emprunt);
            try {
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success' id='emprunt-success-message'>Emprunt ajouté avec succès.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Erreur lors de l'ajout de l'emprunt.</div>";
                }
            } catch (mysqli_sql_exception $e) {
                $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Supprimer un livre
if (isset($_GET['supprimer_livre'])) {
    $id_livre = $_GET['supprimer_livre'];
    $stmt = $mysqli->prepare("DELETE FROM Livres WHERE id_livre = ?");
    $stmt->bind_param("i", $id_livre);
    try {
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Livre supprimé avec succès.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors de la suppression du livre.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $stmt->close();
}

// Supprimer un utilisateur
if (isset($_GET['supprimer_utilisateur'])) {
    $id_utilisateur = $_GET['supprimer_utilisateur'];
    $stmt = $mysqli->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    try {
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Utilisateur supprimé avec succès.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors de la suppression de l'utilisateur.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $stmt->close();
}

// Supprimer un emprunt (considéré comme retour)
if (isset($_GET['supprimer_emprunt'])) {
    $id_emprunt = $_GET['supprimer_emprunt'];
    $stmt = $mysqli->prepare("DELETE FROM Emprunts WHERE id_emprunt = ?");
    $stmt->bind_param("i", $id_emprunt);
    try {
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Emprunt retourné avec succès.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors du retour de l'emprunt.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $stmt->close();
}

// Recherche et données pour affichage (uniquement si nécessaire)
$livres = [];
$utilisateurs = [];
$emprunts = [];
$recherche_livre = isset($_GET['recherche_livre']) ? $_GET['recherche_livre'] : '';
$recherche_utilisateur = isset($_GET['recherche_utilisateur']) ? $_GET['recherche_utilisateur'] : '';
$recherche_emprunt = isset($_GET['recherche_emprunt']) ? $_GET['recherche_emprunt'] : '';

if ($section == '' || $section == 'accueil') {
    // Charger quelques livres pour l'accueil
    $result = $mysqli->query("SELECT * FROM Livres LIMIT 4");
    $livres = $result->fetch_all(MYSQLI_ASSOC);
    // Charger tous les utilisateurs pour le modal d'emprunt
    $result = $mysqli->query("SELECT * FROM Utilisateurs");
    $utilisateurs = $result->fetch_all(MYSQLI_ASSOC);
}

if ($section == 'livres') {
    if ($recherche_livre) {
        $recherche_livre = "%$recherche_livre%";
        $stmt = $mysqli->prepare("SELECT * FROM Livres WHERE titre LIKE ? OR auteur LIKE ?");
        $stmt->bind_param("ss", $recherche_livre, $recherche_livre);
        try {
            $stmt->execute();
            $result = $stmt->get_result();
            $livres = $result->fetch_all(MYSQLI_ASSOC);
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    } else {
        $result = $mysqli->query("SELECT * FROM Livres");
        $livres = $result->fetch_all(MYSQLI_ASSOC);
    }
}

if ($section == 'utilisateurs') {
    if ($recherche_utilisateur) {
        $recherche_utilisateur = "%$recherche_utilisateur%";
        $stmt = $mysqli->prepare("SELECT * FROM Utilisateurs WHERE nom LIKE ? OR prenom LIKE ?");
        $stmt->bind_param("ss", $recherche_utilisateur, $recherche_utilisateur);
        try {
            $stmt->execute();
            $result = $stmt->get_result();
            $utilisateurs = $result->fetch_all(MYSQLI_ASSOC);
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        }
        $stmt->close();
    } else {
        $result = $mysqli->query("SELECT * FROM Utilisateurs");
        $utilisateurs = $result->fetch_all(MYSQLI_ASSOC);
    }
}

if ($section == 'emprunts') {
    $result = $mysqli->query("SELECT * FROM Livres");
    $livres = $result->fetch_all(MYSQLI_ASSOC);
    $result = $mysqli->query("SELECT * FROM Utilisateurs");
    $utilisateurs = $result->fetch_all(MYSQLI_ASSOC);
    try {
        if ($recherche_emprunt) {
            $recherche_emprunt = "%$recherche_emprunt%";
            $stmt = $mysqli->prepare("
                SELECT e.id_emprunt, e.id_livre, e.id_utilisateur, e.date_emprunt, e.date_retour, 
                       l.titre, u.nom, u.prenom
                FROM Emprunts e
                JOIN Livres l ON e.id_livre = l.id_livre
                JOIN Utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                WHERE l.titre LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?
            ");
            $stmt->bind_param("sss", $recherche_emprunt, $recherche_emprunt, $recherche_emprunt);
            $stmt->execute();
            $result = $stmt->get_result();
            $emprunts = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $mysqli->query("
                SELECT e.id_emprunt, e.id_livre, e.id_utilisateur, e.date_emprunt, e.date_retour, 
                       l.titre, u.nom, u.prenom
                FROM Emprunts e
                JOIN Livres l ON e.id_livre = l.id_livre
                JOIN Utilisateurs u ON e.id_utilisateur = u.id_utilisateur
            ");
            $emprunts = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Données pour modification
$livre_modif = null;
if (isset($_GET['modifier_livre']) && $section == 'livres') {
    $id_livre = $_GET['modifier_livre'];
    $stmt = $mysqli->prepare("SELECT * FROM Livres WHERE id_livre = ?");
    $stmt->bind_param("i", $id_livre);
    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $livre_modif = $result->fetch_assoc();
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $stmt->close();
}

$utilisateur_modif = null;
if (isset($_GET['modifier_utilisateur']) && $section == 'utilisateurs') {
    $id_utilisateur = $_GET['modifier_utilisateur'];
    $stmt = $mysqli->prepare("SELECT * FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $utilisateur_modif = $result->fetch_assoc();
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $stmt->close();
}

// Tableau associatif pour associer des images aux genres (non utilisé maintenant, mais conservé pour d'autres sections)
$genre_images = [
    'Roman' => 'https://picsum.photos/200/300?image=102',
    'Science-fiction' => 'https://picsum.photos/200/300?image=103',
    'Fantaisie' => 'https://picsum.photos/200/300?image=104',
    'Policier' => 'https://picsum.photos/200/300?image=105',
    'default' => 'https://picsum.photos/200/300?image=106'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Bibliothèque</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fdfdfd 0%, #f7e4e4 100%);
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding-top: 70px;
        }
        .navbar {
            background-color: #f4e1f4;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
        }
        .navbar-brand {
            color: #6b4e71;
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
        }
        .navbar-brand:hover {
            color: #f5b5b4;
        }
        .nav-link {
            color: #6b4e71;
            font-size: 1.1rem;
            margin-left: 10px;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: #f5b5b4;
        }
        .nav-link.active {
            color: #f5b5b4;
            font-weight: 600;
        }
        .navbar .search-form {
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        .navbar .search-form input {
            border-color: #c9a9d8;
            border-radius: 10px;
            margin-right: 10px;
        }
        .navbar .search-form input:focus {
            border-color: #f5b5b4;
            box-shadow: 0 0 5px rgba(245, 181, 180, 0.5);
        }
        .header-image {
            position: relative;
            height: 200px;
            background-image: url('couverture3.jpg');
            background-size: cover;
            background-position: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-image h1 {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .container {
            max-width: 1200px;
        }
        h2 {
            color: #6b4e71;
            margin-top: 30px;
            margin-bottom: 20px;
            font-family: 'Lora', serif;
        }
        section {
            border: 1px solid #f5b5b4;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: #fff;
            padding: 20px;
        }
        .section-accueil {
            background-color: #fff7f7;
            padding: 40px;
        }
        .section-accueil h2 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
        }
        .book-card {
            margin-bottom: 20px;
            border: 1px solid #f5b5b4;
            border-radius: 10px;
            transition: box-shadow 0.3s;
        }
        .book-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }
        .book-card .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .book-card .card-title {
            font-family: 'Lora', serif;
            color: #6b4e71;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .book-card .card-text {
            color: #6b4e71;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .book-card .btn-emprunter {
            margin-top: auto;
            width: 100%;
        }
        .section-liste-livres, .section-liste-utilisateurs, .section-liste-emprunts {
            background-color: #ffffff;
        }
        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .table th {
            background-color: #c9a9d8;
            color: #fff;
        }
        .btn-primary {
            background-color: #f5b5b4;
            border-color: #f5b5b4;
            margin-left: 1300px;
            color: #6b4e71;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-primary:hover {
            background-color: #f29f9e;
            border-color: #f29f9e;
            transform: scale(1.05);
        }
        .btn-danger {
            background-color: #ffb3b3;
            border-color: #ffb3b3;
            color: #6b4e71;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-danger:hover {
            background-color: #ff9999;
            border-color: #ff9999;
            transform: scale(1.05);
        }
        .form-control {
            border-color: #c9a9d8;
            border-radius: 10px;
        }
        .form-control:focus {
            border-color: #f5b5b4;
            box-shadow: 0 0 5px rgba(245, 181, 180, 0.5);
        }
        .alert {
            border-radius: 10px;
            border: 1px solid #f5b5b4;
        }
        .alert-success {
            background-color: #e6f9e6;
            color: #2e7d32;
        }
        .alert-danger {
            background-color: #ffe6e6;
            color: #d32f2f;
        }
        /* Style personnalisé pour le modal */
        .modal-content {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: none;
            overflow: hidden;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal-header {
            background-color: #f5b5b4;
            color: #6b4e71;
            border-bottom: 2px solid #c9a9d8;
            padding: 20px;
            font-family: 'Playfair Display', serif;
        }
        .modal-title {
            font-size: 1.8rem;
        }
        .modal-body {
            padding: 25px;
            background-color: #fff7f7;
        }
        .modal-footer {
            padding: 15px 25px;
            background-color: #fff;
            border-top: 1px solid #c9a9d8;
        }
        .btn-close {
            color: #6b4e71;
            opacity: 1;
            transition: opacity 0.3s;
        }
        .btn-close:hover {
            opacity: 0.7;
        }
        /* Style pour le Swiper sans images */
        .swiper {
            width: 100%;
            padding: 20px 0;
        }
        .swiper-slide {
            text-align: center;
            font-size: 18px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        .swiper-slide .slide-content {
            color: #6b4e71;
        }
        .swiper-slide h3 {
            font-family: 'Lora', serif;
            color: #6b4e71;
            margin-bottom: 10px;
        }
        .swiper-button-prev,
        .swiper-button-next {
            color: #f5b5b4;
        }
        .swiper-pagination-bullet {
            background: #f5b5b4;
        }
        .swiper-pagination-bullet-active {
            background: #f29f9e;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="?section=accueil">Bibliothèque</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <!-- <li class="nav-item">
                        <a class="nav-link <?php echo ($section == 'accueil' || $section == '') ? 'active' : ''; ?>" href="?section=accueil">Accueil</a>
                    </li> -->
                    
                </ul>
                <!-- Fmulaire de recherche dans la navbar -->
                <!-- <form class="search-form" method="get">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section ?: 'livres'); ?>">
                    <?php if ($section == 'utilisateurs'): ?>
                        <input type="text" name="recherche_utilisateur" class="form-control" placeholder="Rechercher un utilisateur..." value="<?php echo htmlspecialchars($recherche_utilisateur); ?>">
                    <?php elseif ($section == 'emprunts'): ?>
                        <input type="text" name="recherche_emprunt" class="form-control" placeholder="Rechercher un emprunt..." value="<?php echo htmlspecialchars($recherche_emprunt); ?>">
                    <?php else: // Par défaut (Accueil ou Livres) ?>
                        <input type="text" name="recherche_livre" class="form-control" placeholder="Rechercher un livre..." value="<?php echo htmlspecialchars($recherche_livre); ?>">
                    <?php endif; ?> -->
                    <a href="index (2).php"><button type="submit" class="btn btn-primary">Dashboard</button></a>
                <!-- </form> -->
            </div>
        </div>
    </nav>

    <!-- Image d'en-tête (visible uniquement sur la page d'accueil) -->
    <?php if ($section == '' || $section == 'accueil'): ?>
        <div class="header-image">
            <h1>Gestion de Bibliothèque</h1>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Afficher les messages -->
        <?php echo $message; ?>

        <?php if ($section == '' || $section == 'accueil'): ?>
            <!-- Section Accueil -->
            <section class="section-accueil">
                <!-- Section Bienfaits de la lecture -->
                <h2>Les Bienfaits de la Lecture</h2>
                <section class="section-benefits">
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="slide-content">
                                    <h3>Amélioration de la concentration</h3>
                                    <p>La lecture régulière renforce votre capacité à vous concentrer et à rester focalisé.</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="slide-content">
                                    <h3>Réduction du stress</h3>
                                    <p>Plonger dans un livre peut apaiser l'esprit et réduire les tensions quotidiennes.</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="slide-content">
                                    <h3>Développement de l'empathie</h3>
                                    <p>Explorer différents personnages enrichit votre compréhension des émotions d'autrui.</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="slide-content">
                                    <h3>Enrichissement du vocabulaire</h3>
                                    <p>Lire expose à de nouveaux mots, améliorant vos compétences linguistiques.</p>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-pagination"></div>
                    </div>
                </section>

                <!-- <h2>Nos Livres en Vedette</h2>
                <?php if (empty($livres)): ?>
                    <p class="text-center">Aucun livre disponible pour le moment. Ajoutez des livres dans la section "Livres" !</p>
                <?php else: ?>
                    <?php foreach ($livres as $livre): ?>
                        <div class="card book-card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($livre['titre']); ?></h5>
                                <p class="card-text">Genre : <?php echo htmlspecialchars($livre['genre']); ?></p>
                                <button type="button" class="btn btn-primary btn-emprunter" data-bs-toggle="modal" data-bs-target="#empruntModal<?php echo $livre['id_livre']; ?>">Emprunter</button>
                            </div>
                        </div> -->

                        <!-- Modal pour l'emprunt -->
                        <!-- <div class="modal fade" id="empruntModal<?php echo $livre['id_livre']; ?>" tabindex="-1" aria-labelledby="empruntModalLabel<?php echo $livre['id_livre']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="empruntModalLabel<?php echo $livre['id_livre']; ?>">Emprunt de : <?php echo htmlspecialchars($livre['titre']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="post" onsubmit="setTimeout(function(){document.getElementById('empruntModal<?php echo $livre['id_livre']; ?>').style.display='none'; document.body.classList.remove('modal-open'); document.querySelector('.modal-backdrop').remove();}, 500);">
                                            <input type="hidden" name="ajouter_emprunt" value="1">
                                            <input type="hidden" name="id_livre" value="<?php echo $livre['id_livre']; ?>">
                                            <div class="mb-4">
                                                <label for="id_utilisateur_<?php echo $livre['id_livre']; ?>" class="form-label fw-bold" style="color: #6b4e71;">Sélectionner un utilisateur :</label>
                                                <select name="id_utilisateur" id="id_utilisateur_<?php echo $livre['id_livre']; ?>" class="form-control" required>
                                                    <option value="">Choisir un utilisateur</option>
                                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                                        <option value="<?php echo $utilisateur['id_utilisateur']; ?>">
                                                            <?php echo htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Confirmer l'emprunt</button>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <small class="text-muted">Date d'emprunt : <?php echo date('Y-m-d'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center">
                        <a href="?section=livres" class="btn btn-primary">Voir tous les livres</a>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section == 'livres'): ?> -->
            <!-- Section Livres -->
            <!-- <section id="livres"> -->
                <!-- Bouton pour ajouter un livre -->
                <!-- <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterLivreModal">
                        Ajouter un livre
                    </button>
                </div> -->

                <!-- Modal pour ajouter un livre -->
                <!-- <div class="modal fade" id="ajouterLivreModal" tabindex="-1" aria-labelledby="ajouterLivreModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="ajouterLivreModalLabel">Ajouter un livre</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" name="ajouter_livre" value="1">
                                    <div class="mb-3">
                                        <input type="text" name="titre" class="form-control" placeholder="Titre" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="auteur" class="form-control" placeholder="Auteur" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="number" name="annee" class="form-control" placeholder="Année" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="genre" class="form-control" placeholder="Genre" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Liste des livres -->
                <!-- <h2>Liste des livres</h2>
                <section class="section-liste-livres">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Année</th>
                                <th>Genre</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($livres as $livre): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($livre['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($livre['auteur']); ?></td>
                                    <td><?php echo $livre['annee']; ?></td>
                                    <td><?php echo htmlspecialchars($livre['genre']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modifierLivreModal<?php echo $livre['id_livre']; ?>">Modifier</button>
                                        <a href="?section=livres&supprimer_livre=<?php echo $livre['id_livre']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?');">Supprimer</a>
                                    </td>
                                </tr> -->

                                <!-- Modal pour modifier un livre -->
                                <!-- <div class="modal fade" id="modifierLivreModal<?php echo $livre['id_livre']; ?>" tabindex="-1" aria-labelledby="modifierLivreModalLabel<?php echo $livre['id_livre']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modifierLivreModalLabel<?php echo $livre['id_livre']; ?>">Modifier un livre</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post">
                                                    <input type="hidden" name="id_livre" value="<?php echo $livre['id_livre']; ?>">
                                                    <input type="hidden" name="modifier_livre" value="1">
                                                    <div class="mb-3">
                                                        <input type="text" name="titre" class="form-control" placeholder="Titre" value="<?php echo htmlspecialchars($livre['titre']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="auteur" class="form-control" placeholder="Auteur" value="<?php echo htmlspecialchars($livre['auteur']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="number" name="annee" class="form-control" placeholder="Année" value="<?php echo $livre['annee']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="genre" class="form-control" placeholder="Genre" value="<?php echo htmlspecialchars($livre['genre']); ?>" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Modifier</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section> -->
            <!-- </section>
        <?php elseif ($section == 'utilisateurs'): ?> -->
            <!-- Section Utilisateurs -->
            <section id="utilisateurs">
                <!-- Bouton pour ajouter un utilisateur -->
                <!-- <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterUtilisateurModal">
                        Ajouter un utilisateur
                    </button>
                </div> -->

                <!-- Modal pour ajouter un utilisateur -->
                <!-- <div class="modal fade" id="ajouterUtilisateurModal" tabindex="-1" aria-labelledby="ajouterUtilisateurModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="ajouterUtilisateurModalLabel">Ajouter un utilisateur</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" name="ajouter_utilisateur" value="1">
                                    <div class="mb-3">
                                        <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Liste des utilisateurs -->
                <!-- <h2>Liste des utilisateurs</h2>
                <section class="section-liste-utilisateurs">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modifierUtilisateurModal<?php echo $utilisateur['id_utilisateur']; ?>">Modifier</button>
                                        <a href="?section=utilisateurs&supprimer_utilisateur=<?php echo $utilisateur['id_utilisateur']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?');">Supprimer</a>
                                    </td>
                                </tr> -->

                                <!-- Modal pour modifier un utilisateur -->
                                <!-- <div class="modal fade" id="modifierUtilisateurModal<?php echo $utilisateur['id_utilisateur']; ?>" tabindex="-1" aria-labelledby="modifierUtilisateurModalLabel<?php echo $utilisateur['id_utilisateur']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modifierUtilisateurModalLabel<?php echo $utilisateur['id_utilisateur']; ?>">Modifier un utilisateur</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post">
                                                    <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur['id_utilisateur']; ?>">
                                                    <input type="hidden" name="modifier_utilisateur" value="1">
                                                    <div class="mb-3">
                                                        <input type="text" name="nom" class="form-control" placeholder="Nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="prenom" class="form-control" placeholder="Prénom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Modifier</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </section>
        <?php elseif ($section == 'emprunts'): ?> -->
            <!-- Section Emprunts -->
            <!-- <section id="emprunts"> -->
                <!-- Bouton pour ajouter un emprunt -->
                <!-- <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterEmpruntModal">
                        Ajouter un emprunt
                    </button>
                </div> -->

                <!-- Modal pour ajouter un emprunt -->
                <!-- <div class="modal fade" id="ajouterEmpruntModal" tabindex="-1" aria-labelledby="ajouterEmpruntModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="ajouterEmpruntModalLabel">Ajouter un emprunt</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post">
                                    <input type="hidden" name="ajouter_emprunt" value="1">
                                    <div class="mb-3">
                                        <select name="id_livre" class="form-control" required>
                                            <option value="">Sélectionner un livre</option>
                                            <?php foreach ($livres as $livre): ?>
                                                <option value="<?php echo $livre['id_livre']; ?>">
                                                    <?php echo htmlspecialchars($livre['titre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <select name="id_utilisateur" class="form-control" required>
                                            <option value="">Sélectionner un utilisateur</option>
                                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                                <option value="<?php echo $utilisateur['id_utilisateur']; ?>">
                                                    <?php echo htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <small class="text-muted">Date d'emprunt : <?php echo date('Y-m-d'); ?></small>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Liste des emprunts -->
                <!-- <h2>Liste des emprunts</h2>
                <section class="section-liste-emprunts">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre du livre</th>
                                <th>Utilisateur</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emprunts as $emprunt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emprunt['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($emprunt['nom'] . ' ' . $emprunt['prenom']); ?></td>
                                    <td><?php echo $emprunt['date_emprunt']; ?></td>
                                    <td><?php echo $emprunt['date_retour'] ?: 'Non retourné'; ?></td>
                                    <td>
                                        <a href="?section=emprunts&supprimer_emprunt=<?php echo $emprunt['id_emprunt']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer le retour ?');">Retour</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </section>
        <?php endif; ?>
    </div> -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <!-- Script pour ouvrir automatiquement le modal de modification si nécessaire -->
    <?php if ($livre_modif): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modifierModal = new bootstrap.Modal(document.getElementById('modifierLivreModal<?php echo $livre_modif['id_livre']; ?>'));
                modifierModal.show();
            });
        </script>
    <?php endif; ?>
    <?php if ($utilisateur_modif): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modifierUtilisateurModal = new bootstrap.Modal(document.getElementById('modifierUtilisateurModal<?php echo $utilisateur_modif['id_utilisateur']; ?>'));
                modifierUtilisateurModal.show();
            });
        </script>
    <?php endif; ?>
    <!-- Initialisation du Swiper -->
    <script>
        var swiper = new Swiper(".mySwiper", {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            breakpoints: {
                640: {
                    slidesPerView: 1,
                },
                768: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
        });
    </script>
</body>
</html>
<?php
// Fermer la connexion
$mysqli->close();
?>