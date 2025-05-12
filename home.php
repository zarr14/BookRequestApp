<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "utilisateur";
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle signup
if(isset($_POST["ajouter"])){
    $cin = $_POST["cin"];
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $telephone = $_POST["telephone"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    
    if (!empty($cin) && !empty($nom) && !empty($prenom) && !empty($email) && !empty($telephone) && !empty($password)) {
        // First check if email already exists
        $check_email = "SELECT email FROM table_utilisateur WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error'] = "Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse email.";
        } else {
            // If email doesn't exist, proceed with registration
            $utilisateur = "INSERT INTO table_utilisateur (cin, nom, prenom, email, telephone, passe) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $utilisateur);
            mysqli_stmt_bind_param($stmt, "ssssss", $cin, $nom, $prenom, $email, $telephone, $password);
            
            if(mysqli_stmt_execute($stmt)) {
                // Get the newly created user's ID
                $user_id = mysqli_insert_id($conn);
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'user';
                
                // Redirect to user page
                header("Location: user.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
    }
}

// Handle login
if (isset($_POST["btnlogin"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    if (!empty($email) && !empty($password)) {
        // Check admin first
        $admin_query = "SELECT * FROM table_admin WHERE Gmail = ?";
        $stmt = mysqli_prepare($conn, $admin_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $admin_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($admin_result) > 0) {
            $admin = mysqli_fetch_assoc($admin_result);
            // For admin, check if password matches directly first (for default admin)
            if ($password === 'admin123' && $admin['Gmail'] === 'admin@admin.com') {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                header("Location: admin.php");
                exit();
            }
            // Then check hashed password
            if (password_verify($password, $admin['Password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = 'admin';
                header("Location: admin.php");
                exit();
            }
        }
        
        // Check user
        $user_query = "SELECT * FROM table_utilisateur WHERE email = ?";
        $stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($user_result) > 0) {
            $user = mysqli_fetch_assoc($user_result);
            if (password_verify($password, $user['passe'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'user';
                header("Location: user.php");
                exit();
            }
        }
        
        // If we get here, either the email wasn't found or the password was wrong
        $_SESSION['error'] = "Email ou mot de passe incorrect";
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque Moderne - Accueil</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
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

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('architecture.jpeg');
            background-size: cover;
            background-position: center;
            height: 80vh;
            display: flex;
            align-items: center;
            color: white;
        }

        .auth-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-control {
            border-radius: 8px;
            padding: 0.8rem;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .feature-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-book-reader me-2"></i>
                Bibliothèque Moderne
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-home me-1"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-book me-1"></i> Catalogue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-info-circle me-1"></i> À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Bienvenue dans votre bibliothèque numérique</h1>
                    <p class="lead mb-4">Découvrez des milliers de livres, gérez vos emprunts et explorez de nouveaux horizons littéraires.</p>
                    <div class="d-flex gap-3">
                        <button class="btn btn-primary btn-lg" onclick="showinlogin()">
                            <i class="fas fa-sign-in-alt me-2"></i>Connexion
                        </button>
                        <button class="btn btn-outline-light btn-lg" onclick="showingsignup()">
                            <i class="fas fa-user-plus me-2"></i>Inscription
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Nos Services</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Large Catalogue</h3>
                        <p>Accédez à des milliers de livres dans différents genres et catégories.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Réservation 24/7</h3>
                        <p>Réservez vos livres à tout moment, où que vous soyez.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Notifications</h3>
                        <p>Restez informé de vos emprunts et dates de retour.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Form -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content auth-form">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Connexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" name="btnlogin" class="btn btn-primary w-100">Se connecter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Signup Form -->
    <div class="modal fade" id="signupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content auth-form">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Inscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">CIN</label>
                            <input type="text" class="form-control" name="cin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" name="ajouter" class="btn btn-primary w-100">S'inscrire</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        function showinlogin() {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        }

        function showingsignup() {
            var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
            signupModal.show();
        }
    </script>
</body>
</html>