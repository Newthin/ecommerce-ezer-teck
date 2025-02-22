<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"]) && isset($_POST["action"])) {
    $product_id = intval($_POST["id"]);
    $user_id = $_SESSION['user_id'];
    $action = $_POST["action"];

    if ($action === "increase") {
        $sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
    } elseif ($action === "decrease") {
        // Ensure quantity does not go below 1
        $sql = "UPDATE cart SET quantity = GREATEST(quantity - 1, 1) WHERE user_id = ? AND product_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo "Cart updated!";
    } else {
        echo "Error updating cart.";
    }
} else {
    echo "Invalid request.";
}
?>

