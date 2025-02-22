<?php
// Load environment variables
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get database credentials from environment variables
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$port = $_ENV['DB_PORT'];

// Connect to PostgreSQL
$connString = "host=$host dbname=$dbname user=$user password=$password port=$port";
$conn = pg_connect($connString);

// Check connection
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>
