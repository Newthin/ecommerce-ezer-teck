<?php
session_start(); // Start the session
include 'includes/db.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Get total cart items for the user
$cart_count = 0;
if ($user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['total_items'] ?? 0;
}
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to login page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnagkazoEnterprise</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class= "navbar">
        <h1 class= "company">Anagkazo Enterprise</h1>
           <nav>
                <a href="index.php">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php">
                    🛒 Cart <span id="cart-count">(<?= $cart_count; ?>)</span>
                    </a>
                    <a href="?logout">Logout</a>
                    <a href="orders.php">My Orders</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Sign Up</a>
                <?php endif; ?>
           
           </nav>
    </header>
</body> 
