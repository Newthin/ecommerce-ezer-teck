<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect non-admins to login
    exit();
}

include 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-container">

<h1>Admin Panel - Manage Products</h1>
<nav>
     <a href="index.php">Logout</a>
     <a href="add_product.php">Add New Product</a>
     <a href="admin_orders.php">Manage Orders</a>
</nav>
<table class="admin-table" border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Description</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>

    <?php
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>$ {$row['price']}</td>";
        echo "<td>{$row['description']}</td>";
        echo "<td><img src='images/{$row['image']}' width='50'></td>";
        echo "<td>
                <a href='edit_product.php?id={$row['id']}'>Edit</a> | 
                <a href='delete_product.php?id={$row['id']}' onclick='return confirm(\"Are you sure?\")'>Delete</a>
              </td>";
        echo "</tr>";
    }
    ?>

</table>

</body>
</html>

