<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$sql = "SELECT * FROM products WHERE id = $product_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Product not found.";
    exit();
}

$product = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $product['image']; // Keep old image if no new file is uploaded

    // Check if a new image is uploaded
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "images/";
        $image_name = time() . "_" . basename($_FILES["image"]["name"]); // Rename file with timestamp
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type and size
        if ($_FILES["image"]["size"] > 500000) {
            echo "File too large.";
            exit();
        }

        if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            echo "Only JPG, JPEG, PNG & GIF files are allowed.";
            exit();
        }

        // Move the uploaded file to the target directory before processing
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            echo "File upload failed.";
            exit();
        }

        // Load the uploaded image for resizing
        switch ($imageFileType) {
            case "jpg":
            case "jpeg":
                $image = imagecreatefromjpeg($target_file);
                break;
            case "png":
                $image = imagecreatefrompng($target_file);
                break;
            case "gif":
                $image = imagecreatefromgif($target_file);
                break;
            default:
                die("Unsupported image format.");
        }

        if ($image) {
            $max_width = 500; // Fixed width
            $max_height = 500; // Fixed height
            $orig_width = imagesx($image);
            $orig_height = imagesy($image);

            // Calculate aspect ratio
            $orig_ratio = $orig_width / $orig_height;
            $new_ratio = $max_width / $max_height;

            // Resize and crop to fit 500x500
            if ($orig_ratio > $new_ratio) {
                // Original image is wider than the target ratio
                $new_width = $orig_height * $new_ratio;
                $new_height = $orig_height;
                $x_offset = ($orig_width - $new_width) / 2;
                $y_offset = 0;
            } else {
                // Original image is taller than the target ratio
                $new_width = $orig_width;
                $new_height = $orig_width / $new_ratio;
                $x_offset = 0;
                $y_offset = ($orig_height - $new_height) / 2;
            }

            // Create a new image with the target dimensions
            $resized_image = imagecreatetruecolor($max_width, $max_height);
            imagecopyresampled($resized_image, $image, 0, 0, $x_offset, $y_offset, $max_width, $max_height, $new_width, $new_height);

            // Save as JPEG for consistency
            $final_image_path = $target_dir . pathinfo($image_name, PATHINFO_FILENAME) . ".jpg";
            imagejpeg($resized_image, $final_image_path, 90); // 90% quality

            imagedestroy($image);
            imagedestroy($resized_image);

            // Delete the original uploaded image to save storage
            unlink($target_file);

            $image = basename($final_image_path); // Update image name in DB
        } else {
            die("Error processing image.");
        }
    }

    // Update the product in the database
    $sql = "UPDATE products SET name='$name', price='$price', description='$description', image='$image' WHERE id=$product_id";

    if ($conn->query($sql)) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error updating product: " . $conn->error;
    }
}
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
<form method="POST" enctype="multipart/form-data" class="edit-product-container">

    <h2>Edit Product</h2>
    <label>Name:</label>
    <input type="text" name="name" value="<?php echo $product['name']; ?>" required>

    <label>Price:</label>
    <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>

    <label>Description:</label>
    <textarea name="description" required><?php echo $product['description']; ?></textarea>

    <label>Current Image:</label>
    <img src="images/<?php echo $product['image']; ?>" width="100"><br>

    <label>New Image (optional):</label>
    <input type="file" name="image">

    <button type="submit">Update Product</button>
</form>
</body>
</html>
