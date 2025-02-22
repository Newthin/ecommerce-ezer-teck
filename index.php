<?php
include 'includes/header.php';
include 'includes/db.php';

// Fetch products from the database
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<main>
    <section class="products">
        <h2>Featured Products</h2>
        <div class="product-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product">
                    <a href="product.php?id=<?php echo $row['id']; ?>">
                        <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                        <h3><?php echo $row['name']; ?></h3>
                        <p>$<?php echo $row['price']; ?></p>
                    </a>
                    <button class="add-to-cart" data-id="<?php echo $row['id']; ?>">Add to Cart</button>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
</main>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".add-to-cart").forEach(button => {
        button.addEventListener("click", function() {
            let productId = this.getAttribute("data-id");

            fetch("add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "product_id=" + productId
            })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                if (data.status === "success") {
                    updateCartCount(); // 🔥 Fetch updated cart count
                    alert("Product added to cart!");
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        });
    });

    // ✅ Function to fetch and update the cart count dynamically
    function updateCartCount() {
        fetch("get_cart_count.php") // 🔥 Fetch latest cart count
        .then(response => response.json())
        .then(data => {
            let cartCountElement = document.getElementById("cart-count");
            if (cartCountElement) {
                cartCountElement.innerText = `(${data.cart_count})`;
            }
        })
        .catch(error => console.error("Error fetching cart count:", error));
    }
});
</script>

<?php include 'includes/footer.php'; ?>
