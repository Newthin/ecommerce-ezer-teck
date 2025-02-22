<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders of the logged-in user
$order_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
?>
<style>
/* Orders section */
.orders h2 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

/* Styled Table */
.styled-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

.styled-table th, .styled-table td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

.styled-table th {
    background: #007BFF;
    color: white;
    text-transform: uppercase;
}

.styled-table tr:nth-child(even) {
    background: #f2f2f2;
}

/* Status badges */
.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: bold;
}

.status.pending {
    background: #ffc107;
    color: #333;
}

.status.processing {
    background: #17a2b8;
    color: white;
}

.status.completed {
    background: #28a745;
    color: white;
}

.status.cancelled {
    background: #dc3545;
    color: white;
}

/* Button styles */
.btn {
    text-decoration: none;
    background: #28a745;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    transition: 0.3s;
    display: inline-block;
}

.btn:hover {
    background: #218838;
}

/* No orders message */
.no-orders {
    text-align: center;
    color: #777;
    font-size: 18px;
    padding: 20px;
}

</style>
<main>
    <section class="orders-container">
        <h2>Your Orders</h2>

        <?php if ($order_result->num_rows > 0): ?>
            <table class= "orders-table" border="1">
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
                <?php while ($order = $order_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                    <td>
                        <a href="order_details.php?order_id=<?php echo $order['id']; ?>">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

