<?php
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "images/";
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file upload was successful
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $_FILES["image"]["error"]);
    }

    // Validate file type
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowed_types)) {
        die("Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.");
    }

    // Validate file size (max 5MB)
    if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        die("File size too large. Max allowed size is 5MB.");
    }

    // Ensure the images/ directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Load the image and resize
    $max_width = 500; // Max width for resizing
    $image = null;

    switch ($imageFileType) {
        case "jpg":
        case "jpeg":
            $image = imagecreatefromjpeg($_FILES["image"]["tmp_name"]);
            break;
        case "png":
            $image = imagecreatefrompng($_FILES["image"]["tmp_name"]);
            break;
        case "gif":
            $image = imagecreatefromgif($_FILES["image"]["tmp_name"]);
            break;
        default:
            die("Unsupported image format.");
    }

    if ($image) {
        $orig_width = imagesx($image);
        $orig_height = imagesy($image);
        $new_width = $max_width;
        $new_height = ($orig_height / $orig_width) * $max_width;

        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

        // Save as JPEG for consistency
        $final_image_path = $target_dir . pathinfo($image_name, PATHINFO_FILENAME) . ".jpg";
        imagejpeg($resized_image, $final_image_path, 90); // 90% quality
        imagedestroy($image);
        imagedestroy($resized_image);

        $image_name = basename($final_image_path); // Update filename
    } else {
        die("Error processing image.");
    }

    // Insert product data into the database
    $name = $_POST["name"];
    $price = $_POST["price"];
    $description = $_POST["description"];

    $sql = "INSERT INTO products (name, price, description, image) 
            VALUES ('$name', '$price', '$description', '$image_name')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Product added successfully!');
                window.location.href = 'admin.php';
              </script>";
    } else {
        echo "Database error: " . $conn->error;
    }
}
?>

