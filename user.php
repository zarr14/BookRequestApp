<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: home.php");
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

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM table_utilisateur WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get user's active reservations
$reservations_query = "SELECT r.*, l.titre, l.auteur 
                      FROM reservations r 
                      JOIN livres l ON r.livre_id = l.id 
                      WHERE r.user_id = ? AND r.status IN ('en_attente', 'approuvee')";
$stmt = mysqli_prepare($conn, $reservations_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$reservations = mysqli_stmt_get_result($stmt);

// Get available books
$books_query = "SELECT * FROM livres WHERE disponible > 0";
$books_result = mysqli_query($conn, $books_query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></title>
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
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .book-card img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .profile-header {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('architecture.jpeg');
            background-size: cover;
            color: white;
            padding: 50px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['prenom'] . '+' . $user['nom']); ?>&background=random" 
                         class="rounded-circle" style="width: 150px; height: 150px;">
                </div>
                <div class="col-md-10">
                    <h1><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h1>
                    <p class="lead">Membre depuis <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="mb-4">Menu</h3>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#profile">
                        <i class="fas fa-user me-2"></i>Mon Profil
                    </a>
                    <a class="nav-link" href="#books">
                        <i class="fas fa-book me-2"></i>Catalogue
                    </a>
                    <a class="nav-link" href="#reservations">
                        <i class="fas fa-calendar-alt me-2"></i>Mes Réservations
                    </a>
                    <a class="nav-link" href="#history">
                        <i class="fas fa-history me-2"></i>Historique
                    </a>
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Profile Section -->
                <section id="profile" class="mb-5">
                    <h2 class="mb-4">Mon Profil</h2>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>CIN:</strong> <?php echo htmlspecialchars($user['cin']); ?></p>
                                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
                                    <p><strong>Prénom:</strong> <?php echo htmlspecialchars($user['prenom']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($user['telephone']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Books Catalog Section -->
                <section id="books" class="mb-5">
                    <h2 class="mb-4">Catalogue des Livres</h2>
                    <div class="row">
                        <?php while($book = mysqli_fetch_assoc($books_result)): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card book-card">
                                <img src="<?php echo htmlspecialchars($book['image_couverture'] ?? 'https://via.placeholder.com/300x200?text=No+Image'); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($book['titre']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['titre']); ?></h5>
                                    <p class="card-text">
                                        <strong>Auteur:</strong> <?php echo htmlspecialchars($book['auteur']); ?><br>
                                        <strong>Catégorie:</strong> <?php echo htmlspecialchars($book['categorie']); ?><br>
                                        <strong>Disponibles:</strong> <?php echo $book['disponible']; ?>
                                    </p>
                                    <button class="btn btn-primary" onclick="reserveBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-bookmark me-2"></i>Réserver
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- Reservations Section -->
                <section id="reservations" class="mb-5">
                    <h2 class="mb-4">Mes Réservations</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Auteur</th>
                                    <th>Date de réservation</th>
                                    <th>Date de retour prévue</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($reservation = mysqli_fetch_assoc($reservations)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['titre']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['auteur']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_retour_prevue'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $reservation['status'] === 'approuvee' ? 'success' : 
                                                ($reservation['status'] === 'en_attente' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
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

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        function reserveBook(bookId) {
            if(confirm('Voulez-vous réserver ce livre ?')) {
                // Add AJAX call to handle reservation
                fetch('reserve_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'book_id=' + bookId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Livre réservé avec succès!');
                        location.reload();
                    } else {
                        alert('Erreur lors de la réservation: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la réservation');
                });
            }
        }
    </script>
</body>
</html>