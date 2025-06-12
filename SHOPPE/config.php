<?php
$host = 'localhost';
$dbname = 'shopee_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // FIXED HERE
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
