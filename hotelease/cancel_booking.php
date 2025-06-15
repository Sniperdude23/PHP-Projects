<?php 
require_once 'config.php';

if (!isLoggedIn() || isAdmin() || !isset($_GET['booking_id'])) {
    redirect('index.php');
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to the user
$booking = $conn->query("SELECT * FROM bookings WHERE id = $booking_id AND user_id = $user_id")->fetch_assoc();

if (!$booking) {
    redirect('my_bookings.php');
}

// Update booking status to cancelled
$conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");

$_SESSION['message'] = "Booking #$booking_id has been cancelled.";
redirect('my_bookings.php');
?>