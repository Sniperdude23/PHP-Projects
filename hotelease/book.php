<?php 
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $user_id = $_SESSION['user_id'];
    
    // Calculate total price
    $room = $conn->query("SELECT price_per_night FROM rooms WHERE id = $room_id")->fetch_assoc();
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $interval = $date1->diff($date2);
    $nights = $interval->days;
    $total_price = $nights * $room['price_per_night'];
    
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissd", $user_id, $room_id, $check_in, $check_out, $total_price);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Booking successful!";
        redirect('my_bookings.php');
    } else {
        $error = "Booking failed. Please try again.";
    }
}

// Get room details
if (!isset($_GET['room_id'])) {
    redirect('index.php');
}

$room_id = $_GET['room_id'];
$room = $conn->query("SELECT * FROM rooms WHERE id = $room_id")->fetch_assoc();

if (!$room) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HotelEase - Book Room</title>
</head>
<body>
    <h1>Book Room #<?php echo $room['room_number']; ?></h1>
    
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    
    <?php if ($room['image_path']): ?>
        <img src="<?php echo $room['image_path']; ?>" width="300" style="float:left; margin-right:20px;">
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        
        <div>
            <label>Room Type:</label>
            <span><?php echo $room['room_type']; ?></span>
        </div>
        <div>
            <label>Price per night:</label>
            <span><?php echo formatPeso($room['price_per_night']); ?></span>
        </div>
        <div>
            <label>Capacity:</label>
            <span><?php echo $room['capacity']; ?> person(s)</span>
        </div>
        <div>
            <label>Check-in Date:</label>
            <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div>
            <label>Check-out Date:</label>
            <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
        </div>
        <div style="clear:both;"></div>
        <button type="submit">Confirm Booking</button>
    </form>
    
    <a href="index.php">Back to Rooms</a>
</body>
</html>