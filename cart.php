<?php
include 'includes/header.php';
include 'includes/auth.php';
checkAuth(); // Restrict access to logged-in users
include 'includes/db.php';

// Fetch cart items for the logged-in user
$user_id = $_SESSION['user_id'];
$sql = "SELECT products.*, cart.quantity FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = $user_id";
$result = $conn->query($sql);
?>

<main>
    <section class="cart">
        <h2>Shopping Cart</h2>
        <div class="cart-items">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="cart-item">
                    <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                    <h3><?php echo $row['name']; ?></h3>
                    <p>Price: $<?php echo $row['price']; ?></p>

                    <div class="quantity-controls">
                        <button class="decrease" data-id="<?php echo $row['id']; ?>">-</button>
                        <span class="quantity"><?php echo $row['quantity']; ?></span>
                        <button class="increase" data-id="<?php echo $row['id']; ?>">+</button>
                    </div>

                    <button class="remove" data-id="<?php echo $row['id']; ?>">Remove</button>
                </div>
            <?php endwhile; ?>
        </div>
        <a href="checkout.php">Proceed to Checkout</a>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".increase").forEach(button => {
        button.addEventListener("click", function() {
            let productId = this.getAttribute("data-id");
            updateQuantity(productId, "increase");
        });
    });

    document.querySelectorAll(".decrease").forEach(button => {
        button.addEventListener("click", function() {
            let productId = this.getAttribute("data-id");
            updateQuantity(productId, "decrease");
        });
    });

    document.querySelectorAll(".remove").forEach(button => {
        button.addEventListener("click", function() {
            let productId = this.getAttribute("data-id");
            removeFromCart(productId);
        });
    });

    function updateQuantity(productId, action) {
        fetch("update_cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + productId + "&action=" + action
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            location.reload(); // Refresh cart after update
        });
    }

    function removeFromCart(productId) {
        fetch("remove_from_cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + productId
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            location.reload(); // Refresh cart after removing item
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>

