<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect non-admins
    exit();
}

include 'includes/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    // Validate order ID and status
    if (!is_numeric($order_id) || empty($new_status)) {
        die("Invalid input.");
    }

    // Update the order status
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $new_status, $order_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    // Redirect to avoid form resubmission
    header("Location: admin_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-container">

<h1>Admin Panel - Manage Orders</h1>
<nav>
    <a href="index.php">Logout</a>
    <a href="admin.php">Back to Admin Panel</a>
</nav>

<table class="admin-table" border="1">
    <tr>
        <th>Order ID</th>
        <th>Customer Name</th>
        <th>Shipping Address</th>
        <th>Order Date</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php
    // Fetch all orders
    $sql = "SELECT * FROM orders ORDER BY order_date DESC";
    $result = $conn->query($sql);

    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['address']}</td>";
        echo "<td>{$row['order_date']}</td>";
        echo "<td>
                <form method='POST'>
                    <input type='hidden' name='order_id' value='{$row['id']}'>
                    <select name='status'>
                        <option value='Pending' " . ($row['status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                        <option value='Processing' " . ($row['status'] == 'Processing' ? 'selected' : '') . ">Processing</option>
                        <option value='Completed' " . ($row['status'] == 'Completed' ? 'selected' : '') . ">Completed</option>
                        <option value='Cancelled' " . ($row['status'] == 'Cancelled' ? 'selected' : '') . ">Cancelled</option>
                    </select>
                    <button type='submit' name='update_status'>Update</button>
                </form>
              </td>";
        echo "<td><a href='admin_order_details.php?order_id={$row['id']}'>View Details</a></td>";
        echo "</tr>";
    }
    ?>

</table>

</body>
</html>
