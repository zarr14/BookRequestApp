<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "utilisateur";
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image_couverture']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image_couverture']['tmp_name'], $upload_path)) {
            $image_path = $upload_path;
        }
    }
    
    // Handle different actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['titre']) && isset($_POST['auteur']) && isset($_POST['categorie']) && isset($_POST['nombre_exemplaires'])) {
                    $titre = $_POST['titre'];
                    $auteur = $_POST['auteur'];
                    $categorie = $_POST['categorie'];
                    $nombre_exemplaires = $_POST['nombre_exemplaires'];
                    
                    $insert_book = "INSERT INTO livres (titre, auteur, categorie, nombre_exemplaires, disponible, image_couverture) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_book);
                    mysqli_stmt_bind_param($stmt, "sssiis", $titre, $auteur, $categorie, $nombre_exemplaires, $nombre_exemplaires, $image_path);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Livre ajouté avec succès']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du livre']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                }
                break;
                
            case 'edit':
                if (isset($_POST['book_id']) && isset($_POST['titre']) && isset($_POST['auteur']) && isset($_POST['categorie'])) {
                    $book_id = $_POST['book_id'];
                    $titre = $_POST['titre'];
                    $auteur = $_POST['auteur'];
                    $categorie = $_POST['categorie'];
                    
                    $update_book = "UPDATE livres SET titre = ?, auteur = ?, categorie = ?";
                    $params = [$titre, $auteur, $categorie];
                    $types = "sss";
                    
                    if ($image_path) {
                        $update_book .= ", image_couverture = ?";
                        $params[] = $image_path;
                        $types .= "s";
                    }
                    
                    $update_book .= " WHERE id = ?";
                    $params[] = $book_id;
                    $types .= "i";
                    
                    $stmt = mysqli_prepare($conn, $update_book);
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Livre modifié avec succès']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                }
                break;
                
            case 'delete':
                if (isset($_POST['book_id'])) {
                    $book_id = $_POST['book_id'];
                    
                    // Check if book has active reservations
                    $check_reservations = "SELECT COUNT(*) as count FROM reservations 
                                         WHERE livre_id = ? AND status IN ('en_attente', 'approuvee')";
                    $stmt = mysqli_prepare($conn, $check_reservations);
                    mysqli_stmt_bind_param($stmt, "i", $book_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    
                    if ($result['count'] > 0) {
                        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer un livre avec des réservations actives']);
                        exit();
                    }
                    
                    // Delete book
                    $delete_book = "DELETE FROM livres WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $delete_book);
                    mysqli_stmt_bind_param($stmt, "i", $book_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Livre supprimé avec succès']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID du livre manquant']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action invalide']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 