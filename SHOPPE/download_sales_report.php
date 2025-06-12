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
header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the CSV header row
fputcsv($output, array('Order ID', 'User ID', 'Total Amount', 'Status', 'Shipping Address', 'Payment Method', 'Order Date'));

// Fetch all orders
$stmt = $pdo->query("SELECT id, user_id, total_amount, status, shipping_address, payment_method, created_at FROM orders ORDER BY created_at DESC");

// Loop through the data and output it
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

// Close the file pointer
fclose($output);
exit();
?>