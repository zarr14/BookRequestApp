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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id']) && isset($_POST['action'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    // Get reservation details
    $get_reservation = "SELECT * FROM reservations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $get_reservation);
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    mysqli_stmt_execute($stmt);
    $reservation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit();
    }
    
    if ($action === 'approve') {
        // Update reservation status
        $update_reservation = "UPDATE reservations SET status = 'approuvee' WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_reservation);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Réservation approuvée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'approbation']);
        }
    } 
    elseif ($action === 'reject') {
        // Update reservation status and return book to available
        $update_reservation = "UPDATE reservations SET status = 'refusee' WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_reservation);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update book availability
            $update_book = "UPDATE livres SET disponible = disponible + 1 WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_book);
            mysqli_stmt_bind_param($stmt, "i", $reservation['livre_id']);
            mysqli_stmt_execute($stmt);
            
            echo json_encode(['success' => true, 'message' => 'Réservation rejetée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du rejet']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}
?> 