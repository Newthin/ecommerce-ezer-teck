<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
    $product_id = intval($_POST["id"]);
    $user_id = $_SESSION['user_id'];

    $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);

    if ($stmt->execute()) {
        echo "Product removed from cart!";
    } else {
        echo "Error removing product.";
    }
} else {
    echo "Invalid request.";
}
?>

