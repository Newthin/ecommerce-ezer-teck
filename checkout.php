<?php
include 'includes/header.php';
?>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    .checkout {
        width: 40%;
        margin: 50px auto;
        padding: 20px;
        background: #ffffff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
        color: #555;
    }

    input, textarea {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 18px;
        cursor: pointer;
        margin-top: 20px;
        transition: 0.3s;
    }

    button:hover {
        background: #0056b3;
    }

    @media (max-width: 768px) {
        .checkout {
            width: 90%;
        }
    }
</style>

<main>
    <div class="checkout">
    <section>
        <h2>Checkout</h2>
        <form id="checkoutForm" method="POST" action="process_checkout.php">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="address">Shipping Address:</label>
            <textarea id="address" name="address" required></textarea>

            <button type="submit">Place Order</button>
        </form>
    </section>
    </div>
</main>

<script>
document.getElementById("checkoutForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent page reload
    let formData = new FormData(this);
    let urlEncodedData = new URLSearchParams(formData).toString();

    fetch("process_checkout.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: urlEncodedData
    })
    .then(response => response.text()) // Get raw text response
    .then(text => {
        console.log("Raw Response:", text); // Log raw response for debugging
        try {
            let data = JSON.parse(text); // Try to parse JSON
            console.log("Parsed JSON:", data);
            if (data.status === "success") {
                window.location.href = data.redirect;
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error("JSON Parse Error:", error);
            alert("Invalid response from server.");
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert("An error occurred while processing your request.");
    });
});
</script>


<?php include 'includes/footer.php'; ?>

