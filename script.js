let cart = [];
let cartCount = 0;

function addToCart(productName, price) {
    cart.push({ name: productName, price: price });
    cartCount++;
    updateCartCount();
    alert(`${productName} added to cart!`);
}

function updateCartCount() {
    document.getElementById('cart-count').textContent = cartCount;
}

// Existing cart functionality...

// Login Form Submission
document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    // Basic validation
    if (email && password) {
        alert('Login successful!');
        window.location.href = 'index.html'; // Redirect to homepage
    } else {
        alert('Please fill in all fields.');
    }
});

// Signup Form Submission
document.getElementById('signup-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const name = document.getElementById('signup-name').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;

    // Basic validation
    if (name && email && password) {
        alert('Account created successfully!');
        window.location.href = 'login.html'; // Redirect to login page
    } else {
        alert('Please fill in all fields.');
    }
});