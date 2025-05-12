<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if book is available
    $check_book = "SELECT disponible FROM livres WHERE id = ? AND disponible > 0";
    $stmt = mysqli_prepare($conn, $check_book);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Livre non disponible']);
        exit();
    }
    
    // Check if user already has a pending reservation for this book
    $check_reservation = "SELECT id FROM reservations 
                         WHERE user_id = ? AND livre_id = ? 
                         AND status IN ('en_attente', 'approuvee')";
    $stmt = mysqli_prepare($conn, $check_reservation);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà réservé ce livre']);
        exit();
    }
    
    // Create reservation
    $date_retour = date('Y-m-d', strtotime('+14 days')); // 2 weeks loan period
    $insert_reservation = "INSERT INTO reservations (user_id, livre_id, date_retour_prevue, status) 
                          VALUES (?, ?, ?, 'en_attente')";
    $stmt = mysqli_prepare($conn, $insert_reservation);
    mysqli_stmt_bind_param($stmt, "iis", $user_id, $book_id, $date_retour);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update book availability
        $update_book = "UPDATE livres SET disponible = disponible - 1 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_book);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        
        echo json_encode(['success' => true, 'message' => 'Réservation effectuée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la réservation']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}
?> 