<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // Your database connection

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="products_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the CSV header row
fputcsv($output, array('Product ID', 'Name', 'Description', 'Price', 'Stock', 'Category', 'Brand', 'Created At', 'Updated At'));

// Fetch all products
$stmt = $pdo->query("SELECT id, name, description, price, stock, category, brand, created_at, updated_at FROM products ORDER BY name ASC");

// Loop through the data and output it
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Exclude image_url for simplicity in CSV, or include if needed
    // You might want to sanitize description for CSV if it contains commas or newlines
    $rowData = [
        $row['id'],
        $row['name'],
        $row['description'],
        $row['price'],
        $row['stock'],
        $row['category'],
        $row['brand'],
        $row['created_at'],
        $row['updated_at']
    ];
    fputcsv($output, $rowData);
}

// Close the file pointer
fclose($output);
exit();
?>