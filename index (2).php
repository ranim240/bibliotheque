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
                if ($_POST['action'] === 'add_livre') {
                    $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, annee, genre) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$titre, $auteur, $annee ?: null, $genre]);
                    $_SESSION['success_message'] = "Livre ajouté avec succès.";
                } elseif ($_POST['action'] === 'edit_livre' && $id_livre) {
                    $stmt = $pdo->prepare("UPDATE livres SET titre = ?, auteur = ?, annee = ?, genre = ? WHERE id_livre = ?");
                    $stmt->execute([$titre, $auteur, $annee ?: null, $genre, $id_livre]);
                    $_SESSION['success_message'] = "Livre modifié avec succès.";
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Le titre et l'auteur sont requis.";
        }
    }

    // Supprimer un livre
    if (isset($_POST['action']) && $_POST['action'] === 'delete_livre' && isset($_POST['id_livre'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM livres WHERE id_livre = ?");
            $stmt->execute([(int)$_POST['id_livre']]);
            $_SESSION['success_message'] = "Livre supprimé avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
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
                    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email) VALUES (?, ?, ?)");
                    $stmt->execute([$nom, $prenom, $email]);
                    $_SESSION['success_message'] = "Utilisateur ajouté avec succès.";
                } elseif ($_POST['action'] === 'edit_utilisateur' && $id_utilisateur) {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_utilisateur = ?");
                    $stmt->execute([$nom, $prenom, $email, $id_utilisateur]);
                    $_SESSION['success_message'] = "Utilisateur modifié avec succès.";
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Le nom, le prénom et l'email sont requis.";
        }
    }

    // Supprimer un utilisateur
    if (isset($_POST['action']) && $_POST['action'] === 'delete_utilisateur' && isset($_POST['id_utilisateur'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([(int)$_POST['id_utilisateur']]);
            $_SESSION['success_message'] = "Utilisateur supprimé avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // Ajouter un emprunt
    if (isset($_POST['action']) && $_POST['action'] === 'add_emprunt') {
        $id_livre = (int)$_POST['id_livre'] ?? 0;
        $id_utilisateur = (int)$_POST['id_utilisateur'] ?? 0;

        if ($id_livre && $id_utilisateur) {
            try {
                $stmt = $pdo->prepare("INSERT INTO emprunts (id_livre, id_utilisateur, date_emprunt) VALUES (?, ?, NOW())");
                $stmt->execute([$id_livre, $id_utilisateur]);
                $_SESSION['success_message'] = "Emprunt enregistré avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
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

// Récupérer tous les livres
$livres_query = "SELECT * FROM livres";
if (isset($_GET['search_livre']) && !empty($_GET['search_livre'])) {
    $search = '%' . trim($_GET['search_livre']) . '%';
    $livres_query .= " WHERE titre LIKE ? OR auteur LIKE ?";
    $stmt = $pdo->prepare($livres_query);
    $stmt->execute([$search, $search]);
} else {
    $stmt = $pdo->query($livres_query);
}
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les utilisateurs
$utilisateurs_query = "SELECT * FROM utilisateurs";
if (isset($_GET['search_utilisateur']) && !empty($_GET['search_utilisateur'])) {
    $search = '%' . trim($_GET['search_utilisateur']) . '%';
    $utilisateurs_query .= " WHERE nom LIKE ?";
    $stmt = $pdo->prepare($utilisateurs_query);
    $stmt->execute([$search]);
} else {
    $stmt = $pdo->query($utilisateurs_query);
}
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les emprunts
$stmt = $pdo->query("
    SELECT e.id_emprunt, e.id_livre, e.id_utilisateur, e.date_emprunt, e.date_retour, 
           l.titre, u.nom, u.prenom 
    FROM emprunts e 
    JOIN livres l ON e.id_livre = l.id_livre 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
");
$emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <style>
        body {
            padding: 20px;
        }

        .nav-item .nav-link.active {
            background-color: #2E7D32;
            color: white;
        }

        .dashboard {
            text-align: center;
            margin-top: 20px;
        }

        .dashboard h2 {
            color: #4CAF50;
        }

        .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center mb-4">Dashboard - Gestion de Bibliothèque</h1>

        <!-- Afficher les messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Menu de navigation -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $section === 'dashboard' ? 'active' : '' ?>" href="?section=dashboard">Accueil</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'add_livre' ? 'active' : '' ?>" href="?section=add_livre">Ajouter un livre</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'add_utilisateur' ? 'active' : '' ?>" href="?section=add_utilisateur">Ajouter un utilisateur</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'list_livres' ? 'active' : '' ?>" href="?section=list_livres">Liste des livres</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'list_utilisateurs' ? 'active' : '' ?>" href="?section=list_utilisateurs">Liste des utilisateurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'add_emprunt' ? 'active' : '' ?>" href="?section=add_emprunt">Ajouter un emprunt</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $section === 'list_emprunts' ? 'active' : '' ?>" href="?section=list_emprunts">Liste des emprunts</a>
            </li>
        </ul>

        <!-- Contenu de la section -->
        <?php if ($section === 'dashboard'): ?>
            <div class="dashboard">
                <h2>Bienvenue sur le Dashboard</h2>
                <p>Utilisez le menu ci-dessus pour gérer les livres, les utilisateurs et les emprunts de la bibliothèque.</p>
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Nombre de livres</h5>
                                <p class="card-text"><?= count($livres) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Nombre d'utilisateurs</h5>
                                <p class="card-text"><?= count($utilisateurs) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Nombre d'emprunts actifs</h5>
                                <p class="card-text"><?= count(array_filter($emprunts, fn($e) => is_null($e['date_retour']))) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'add_livre'): ?>
            <!-- Formulaire pour ajouter un livre -->
            <h2>Ajouter un livre</h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="add_livre">
                <div class="col-md-6">
                    <label for="titre" class="form-label">Titre</label>
                    <input type="text" class="form-control" name="titre" id="titre" placeholder="Titre" required>
                </div>
                <div class="col-md-6">
                    <label for="auteur" class="form-label">Auteur</label>
                    <input type="text" class="form-control" name="auteur" id="auteur" placeholder="Auteur" required>
                </div>
                <div class="col-md-6">
                    <label for="annee" class="form-label">Année</label>
                    <input type="number" class="form-control" name="annee" id="annee" placeholder="Année">
                </div>
                <div class="col-md-6">
                    <label for="genre" class="form-label">Genre</label>
                    <input type="text" class="form-control" name="genre" id="genre" placeholder="Genre">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>

        <?php elseif ($section === 'list_livres'): ?>
            <!-- Formulaire de recherche pour les livres -->
            <h2>Liste des livres</h2>
            <form method="get" class="mb-3">
                <input type="hidden" name="section" value="list_livres">
                <div class="input-group">
                    <input type="text" class="form-control" name="search_livre" placeholder="Rechercher par titre ou auteur..." value="<?= htmlspecialchars($_GET['search_livre'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                    <?php if (isset($_GET['search_livre']) && !empty($_GET['search_livre'])): ?>
                        <a href="?section=list_livres" class="btn btn-secondary">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
            <!-- Liste des livres -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                <td><?= htmlspecialchars($livre['id_livre']) ?></td>
                                <td><?= htmlspecialchars($livre['titre']) ?></td>
                                <td><?= htmlspecialchars($livre['auteur']) ?></td>
                                <td><?= htmlspecialchars($livre['annee'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($livre['genre'] ?: '-') ?></td>
                                <td class="actions">
                                    <!-- Formulaire pour modifier -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="edit_livre">
                                        <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
                                        <input type="text" name="titre" value="<?= htmlspecialchars($livre['titre']) ?>" required class="form-control d-inline-block" style="width: 150px;">
                                        <input type="text" name="auteur" value="<?= htmlspecialchars($livre['auteur']) ?>" required class="form-control d-inline-block" style="width: 150px;">
                                        <input type="number" name="annee" value="<?= htmlspecialchars($livre['annee']) ?>" class="form-control d-inline-block" style="width: 80px;">
                                        <input type="text" name="genre" value="<?= htmlspecialchars($livre['genre']) ?>" class="form-control d-inline-block" style="width: 100px;">
                                        <button type="submit" class="btn btn-sm btn-warning">Modifier</button>
                                    </form>
                                    <!-- Formulaire pour supprimer -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete_livre">
                                        <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'add_utilisateur'): ?>
            <!-- Formulaire pour ajouter un utilisateur -->
            <h2>Ajouter un utilisateur</h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="add_utilisateur">
                <div class="col-md-4">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" name="nom" id="nom" placeholder="Nom" required>
                </div>
                <div class="col-md-4">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control" name="prenom" id="prenom" placeholder="Prénom" required>
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>

        <?php elseif ($section === 'list_utilisateurs'): ?>
            <!-- Formulaire de recherche pour les utilisateurs -->
            <h2>Liste des utilisateurs</h2>
            <form method="get" class="mb-3">
                <input type="hidden" name="section" value="list_utilisateurs">
                <div class="input-group">
                    <input type="text" class="form-control" name="search_utilisateur" placeholder="Rechercher par nom..." value="<?= htmlspecialchars($_GET['search_utilisateur'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                    <?php if (isset($_GET['search_utilisateur']) && !empty($_GET['search_utilisateur'])): ?>
                        <a href="?section=list_utilisateurs" class="btn btn-secondary">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
            <!-- Liste des utilisateurs -->
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
                                    <!-- Formulaire pour modifier -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="edit_utilisateur">
                                        <input type="hidden" name="id_utilisateur" value="<?= $utilisateur['id_utilisateur'] ?>">
                                        <input type="text" name="nom" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required class="form-control d-inline-block" style="width: 120px;">
                                        <input type="text" name="prenom" value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required class="form-control d-inline-block" style="width: 120px;">
                                        <input type="email" name="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required class="form-control d-inline-block" style="width: 200px;">
                                        <button type="submit" class="btn btn-sm btn-warning">Modifier</button>
                                    </form>
                                    <!-- Formulaire pour supprimer -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete_utilisateur">
                                        <input type="hidden" name="id_utilisateur" value="<?= $utilisateur['id_utilisateur'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'add_emprunt'): ?>
            <!-- Formulaire pour ajouter un emprunt -->
            <h2>Ajouter un emprunt</h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="add_emprunt">
                <div class="col-md-6">
                    <label for="id_livre" class="form-label">Livre</label>
                    <select name="id_livre" id="id_livre" class="form-select" required>
                        <option value="">Sélectionner un livre</option>
                        <?php foreach ($livres as $livre): ?>
                            <option value="<?= $livre['id_livre'] ?>"><?= htmlspecialchars($livre['titre']) ?> (<?= htmlspecialchars($livre['auteur']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="id_utilisateur" class="form-label">Utilisateur</label>
                    <select name="id_utilisateur" id="id_utilisateur" class="form-select" required>
                        <option value="">Sélectionner un utilisateur</option>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <option value="<?= $utilisateur['id_utilisateur'] ?>"><?= htmlspecialchars($utilisateur['nom']) ?> <?= htmlspecialchars($utilisateur['prenom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Enregistrer l'emprunt</button>
                </div>
            </form>

        <?php elseif ($section === 'list_emprunts'): ?>
            <!-- Liste des emprunts -->
            <h2>Liste des emprunts</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Livre</th>
                            <th>Utilisateur</th>
                            <th>Date d'emprunt</th>
                            <th>Date de retour</th>
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
                                            <button type="submit" class="btn btn-sm btn-success">Marquer comme retourné</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>