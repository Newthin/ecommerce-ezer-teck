<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure order_id is provided in URL
if (!isset($_GET['order_id'])) {
    echo "<p>Invalid order.</p>";
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    echo "<p>Order not found.</p>";
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch order items
$order_items_query = "SELECT products.name, products.price, order_items.quantity 
                      FROM order_items 
                      JOIN products ON order_items.product_id = products.id 
                      WHERE order_items.order_id = ?";
$stmt = $conn->prepare($order_items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<main>
    <section class="orders-container">
        <h2>Order Details</h2>
        <p><strong>Order ID:</strong> <?php echo $order_id; ?></p>
        <p><strong>Order Date:</strong> <?php echo $order['order_date']; ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>

        <h3>Items:</h3>
        <table class= "orders-table" border="1">
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
    </section>
</main>

<?php include 'includes/footer.php'; ?>

