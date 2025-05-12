<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href = 'home.php';</script>";
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "utilisateur";
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM table_admin WHERE id = ?";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get pending reservations
$reservations_query = "SELECT r.*, l.titre, l.auteur, u.nom, u.prenom 
                      FROM reservations r 
                      JOIN livres l ON r.livre_id = l.id 
                      JOIN table_utilisateur u ON r.user_id = u.id 
                      WHERE r.status = 'en_attente'";
$reservations = mysqli_query($conn, $reservations_query);

// Get all books
$books_query = "SELECT * FROM livres";
$books = mysqli_query($conn, $books_query);

// Get all users
$users_query = "SELECT * FROM table_utilisateur";
$users = mysqli_query($conn, $users_query);

// Handle book addition
if(isset($_POST['add_book'])) {
    $isbn = $_POST['isbn'];
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $categorie = $_POST['categorie'];
    $description = $_POST['description'];
    $nombre_exemplaires = $_POST['nombre_exemplaires'];
    $date_publication = $_POST['date_publication'];
    
    // Handle image upload
    $image_couverture = '';
    if(isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image_couverture"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["image_couverture"]["tmp_name"], $target_file)) {
            $image_couverture = $target_file;
        }
    }
    
    $query = "INSERT INTO livres (isbn, titre, auteur, categorie, description, nombre_exemplaires, 
              disponible, date_publication, image_couverture) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssiiss", $isbn, $titre, $auteur, $categorie, $description, 
                          $nombre_exemplaires, $nombre_exemplaires, $date_publication, $image_couverture);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Livre ajouté avec succès!";
        echo "<script>window.location.href = 'admin.php';</script>";
        exit();
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout du livre: " . mysqli_error($conn);
        echo "<script>window.location.href = 'admin.php';</script>";
        exit();
    }
}

// Handle book deletion
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $book_id = $_GET['id'];
    
    // Delete the book
    $query = "DELETE FROM livres WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Livre supprimé avec succès!";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du livre: " . mysqli_error($conn);
    }
}

// Handle book editing
if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    // Redirect to edit page or show edit modal
    // You can implement this part later
}

// Redirect back to admin page using JavaScript
echo "<script>window.location.href = 'admin.php';</script>";
exit();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Bibliothèque</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }

        .sidebar .nav-link {
            color: white;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
        }

        .sidebar .nav-link.active {
            background-color: var(--secondary-color);
        }

        .main-content {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .admin-header {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('architecture.jpeg');
            background-size: cover;
            color: white;
            padding: 50px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=random" 
                         class="rounded-circle" style="width: 150px; height: 150px;">
                </div>
                <div class="col-md-10">
                    <h1>Administration</h1>
                    <p class="lead">Bienvenue dans le panneau d'administration</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="mb-4">Menu Admin</h3>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                    </a>
                    <a class="nav-link" href="#books">
                        <i class="fas fa-book me-2"></i>Gestion des livres
                    </a>
                    <a class="nav-link" href="#users">
                        <i class="fas fa-users me-2"></i>Gestion des utilisateurs
                    </a>
                    <a class="nav-link" href="#reservations">
                        <i class="fas fa-calendar-alt me-2"></i>Réservations
                    </a>
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Dashboard Section -->
                <section id="dashboard" class="mb-5">
                    <h2 class="mb-4">Tableau de bord</h2>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Livres</h5>
                                    <h2 class="card-text"><?php echo mysqli_num_rows($books); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Utilisateurs</h5>
                                    <h2 class="card-text"><?php echo mysqli_num_rows($users); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">Réservations en attente</h5>
                                    <h2 class="card-text"><?php echo mysqli_num_rows($reservations); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Books Management Section -->
                <section id="books" class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestion des Livres</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                            <i class="fas fa-plus me-2"></i>Ajouter un livre
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Auteur</th>
                                    <th>Catégorie</th>
                                    <th>Disponibles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($book = mysqli_fetch_assoc($books)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($book['auteur']); ?></td>
                                    <td><?php echo htmlspecialchars($book['categorie']); ?></td>
                                    <td><?php echo $book['disponible']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editBook(<?php echo $book['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Users Management Section -->
                <section id="users" class="mb-5">
                    <h2 class="mb-4">Gestion des Utilisateurs</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['telephone']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Reservations Section -->
                <section id="reservations" class="mb-5">
                    <h2 class="mb-4">Réservations en attente</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Livre</th>
                                    <th>Date de réservation</th>
                                    <th>Date de retour prévue</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($reservation = mysqli_fetch_assoc($reservations)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['titre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_retour_prevue'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectReservation(<?php echo $reservation['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Ajouter un nouveau livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="titre" class="form-label">Titre</label>
                                <input type="text" class="form-control" id="titre" name="titre" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="auteur" class="form-label">Auteur</label>
                                <input type="text" class="form-control" id="auteur" name="auteur" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <select class="form-select" id="categorie" name="categorie" required>
                                    <?php
                                    $categories_query = "SELECT nom FROM categories";
                                    $categories_result = mysqli_query($conn, $categories_query);
                                    while($category = mysqli_fetch_assoc($categories_result)) {
                                        echo "<option value='" . htmlspecialchars($category['nom']) . "'>" . 
                                             htmlspecialchars($category['nom']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_exemplaires" class="form-label">Nombre d'exemplaires</label>
                                <input type="number" class="form-control" id="nombre_exemplaires" name="nombre_exemplaires" 
                                       min="1" value="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_publication" class="form-label">Date de publication</label>
                                <input type="date" class="form-control" id="date_publication" name="date_publication">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image_couverture" class="form-label">Image de couverture</label>
                            <input type="file" class="form-control" id="image_couverture" name="image_couverture" 
                                   accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_book" class="btn btn-primary">Ajouter le livre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        function approveReservation(id) {
            if(confirm('Approuver cette réservation ?')) {
                fetch('handle_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + id + '&action=approve'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }
        }

        function rejectReservation(id) {
            if(confirm('Rejeter cette réservation ?')) {
                fetch('handle_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + id + '&action=reject'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }
        }

        function deleteBook(bookId) {
            if(confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) {
                window.location.href = 'handle_book.php?action=delete&id=' + bookId;
            }
        }

        function deleteUser(id) {
            if(confirm('Supprimer cet utilisateur ?')) {
                fetch('handle_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + id + '&action=delete'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }
        }

        // Function to handle book editing
        function editBook(bookId) {
            window.location.href = 'handle_book.php?action=edit&id=' + bookId;
        }

        // Handle add book form submission
        document.getElementById('addBookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('handle_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
        });

        // Fonction pour afficher les messages de succès/erreur
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }

        // Vérifier s'il y a des messages de session
        <?php if(isset($_SESSION['success'])): ?>
            showMessage('<?php echo $_SESSION['success']; ?>', 'success');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            showMessage('<?php echo $_SESSION['error']; ?>', 'danger');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>