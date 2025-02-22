<?php
include 'includes/header.php';
include 'includes/db.php';

// Get the product ID from the URL
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
} else {
    header('Location: index.php');
    exit();
}

// Fetch product details using prepared statements
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo "Product not found.";
    exit();
}

?>

<main>
    <section class="product-details">
        <div class="product-image">
            <img src="images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
        </div>
        <div class="product-info">
            <h2><?php echo $product['name']; ?></h2>
            <p class="price">$<?php echo $product['price']; ?></p>
            <p class="description"><?php echo $product['description']; ?></p>
            <form id="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1">
                <button type="submit">Add to Cart</button>
            </form>
        </div>
    </section>
</main>

<script>
document.getElementById("add-to-cart-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission

    let formData = new FormData(this);

    fetch("add_to_cart.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json()) // Parse JSON response
    .then(data => {
        alert(data.message); // Show pop-up message
        if (data.status === "success") {
            window.location.href = data.redirect; // Redirect to cart
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>


<?php include 'includes/footer.php'; ?>
