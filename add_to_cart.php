<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // Ensure JSON response

    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;
    if ($product_id == 0) {
        echo json_encode(["status" => "error", "message" => "Invalid request."]);
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Please log in to add items to your cart."]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST["quantity"]) ? intval($_POST["quantity"]) : 1;
    $response = []; // Store final response

    // Check if product already exists in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update quantity if already in cart
        $update_sql = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        if ($stmt->execute()) {
            $response["status"] = "success";
            $response["message"] = "Cart updated!";
        } else {
            $response["status"] = "error";
            $response["message"] = "Error updating cart.";
        }
    } else {
        // Insert product into cart
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        if ($stmt->execute()) {
            $response["status"] = "success";
            $response["message"] = "Product added to cart!";
        } else {
            $response["status"] = "error";
            $response["message"] = "Error adding to cart.";
        }
    }

    // Get updated cart count
    $count_query = "SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response["cart_count"] = $row['total_items'] ?? 0;

    echo json_encode($response); // ✅ Send only ONE JSON response
    exit();
}
echo json_encode(["status" => "error", "message" => "Invalid request."]);
exit();
?>

