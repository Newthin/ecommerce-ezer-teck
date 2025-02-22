<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Unauthorized access.");
}

include 'includes/db.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = intval($_GET['id']); // Ensure product_id is an integer

// Debug: Log the product ID
error_log("Deleting product with ID: $product_id");

// Delete product from the cart first
$sql_cart = "DELETE FROM cart WHERE product_id = $product_id";
if (!$conn->query($sql_cart)) {
    die("Error deleting product from cart: " . $conn->error);
}

// Fetch product details to delete the associated image
$sql = "SELECT * FROM products WHERE id = $product_id";
$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();
$image_path = "images/" . $product['image'];

// Debug: Log the image path
error_log("Image path: $image_path");

// Delete product from the products table
$sql_product = "DELETE FROM products WHERE id = $product_id";
if (!$conn->query($sql_product)) {
    die("Error deleting product: " . $conn->error);
}

// Delete image file if it exists
if (file_exists($image_path)) {
    if (!unlink($image_path)) {
        error_log("Failed to delete image file: $image_path");
    } else {
        error_log("Image file deleted: $image_path");
    }
} else {
    error_log("Image file not found: $image_path");
}

// Debug: Log success
error_log("Product deleted successfully!");

// Redirect to admin page
header("Location: admin.php");
exit();
?>
