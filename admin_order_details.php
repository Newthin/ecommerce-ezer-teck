<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect non-admins
    exit();
}

include 'includes/db.php';

if (!isset($_GET['order_id'])) {
    echo "Invalid order ID.";
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch ordered items
$order_items_query = "SELECT products.name, products.price, order_items.quantity 
                      FROM order_items 
                      JOIN products ON order_items.product_id = products.id 
                      WHERE order_items.order_id = ?";
$stmt = $conn->prepare($order_items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-container">

<h1>Order Details</h1>
<a href="admin_orders.php">Back to Orders</a>

<p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
<p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
<p><strong>Order Date:</strong> <?php echo $order['order_date']; ?></p>
<p><strong>Status:</strong> <?php echo $order['status']; ?></p>

<h3>Items:</h3>
<table class="admin-table">
    <tr>
        <th>Product</th>
        <th>Quantity</th>
        <th>Price</th>
    </tr>

    <?php 
    $total_price = 0;
    while ($item = $items_result->fetch_assoc()): 
        $subtotal = $item['price'] * $item['quantity'];
        $total_price += $subtotal;
    ?>
    <tr>
        <td><?php echo htmlspecialchars($item['name']); ?></td>
        <td><?php echo $item['quantity']; ?></td>
        <td>$<?php echo number_format($subtotal, 2); ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<p><strong>Total Amount:</strong> $<?php echo number_format($total_price, 2); ?></p>

</body>
</html>

