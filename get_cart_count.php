<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json'); // Make sure the response is JSON

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['total_items'] ?? 0;
} else {
    $cart_count = 0;
}

// ✅ Send JSON response
echo json_encode(["cart_count" => $cart_count]);
exit();
?>

