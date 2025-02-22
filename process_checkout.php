<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json"); // Ensure JSON response
session_start();
include 'includes/db.php';

// Debug: Check if request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Not a POST request"]);
    exit();
}

// Debug: Check what data is being received
file_put_contents("debug_log.txt", print_r($_POST, true)); // Log to a file

// Validate required fields
if (!isset($_POST['name'], $_POST['email'], $_POST['address'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Please log in to complete your purchase."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$address = trim($_POST['address']);

// Check if cart is empty
$cart_query = "SELECT * FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Your cart is empty."]);
    exit();
}

// Insert order into orders table
$order_query = "INSERT INTO orders (user_id, name, email, address, order_date, status) VALUES (?, ?, ?, ?, NOW(), 'Pending')";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("isss", $user_id, $name, $email, $address);
$stmt->execute();
$order_id = $stmt->insert_id;

// Move cart items to order_items table
while ($cart_item = $cart_result->fetch_assoc()) {
    $product_id = $cart_item['product_id'];
    $quantity = $cart_item['quantity'];

    // Fetch product price
    $product_price_query = "SELECT price FROM products WHERE id = ?";
    $stmt = $conn->prepare($product_price_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $price_result = $stmt->get_result();
    $product = $price_result->fetch_assoc();
    $product_price = $product['price'];

    // Insert into order_items
    $insert_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_item_query);
    $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $product_price);
    $stmt->execute();
}

// Clear the cart after successful checkout
$clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($clear_cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// ✅ Return JSON Response for AJAX Redirect
echo json_encode(["status" => "success", "redirect" => "order_confirmation.php?order_id=" . $order_id]);
exit();
?>
