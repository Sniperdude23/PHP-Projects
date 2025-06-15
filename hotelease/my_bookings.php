<?php 
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$bookings = $conn->query("
    SELECT b.*, r.room_number, r.room_type, r.image_path
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = $user_id
    ORDER BY b.check_in DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase | My Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --gray-1: #111111;
            --gray-2: #222222;
            --gray-5: #555555;
            --gray-9: #999999;
            --gray-e: #eeeeee;
            --gold: #D4AF37;
            --error: #f44336;
            --success: #4CAF50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--black);
            color: var(--white);
            line-height: 1.8;
            padding-top: 100px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }
        
        /* Header - Same as theme */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 25px 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        header.scrolled {
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.95);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .logo span {
            font-weight: 300;
            opacity: 0.8;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            margin-left: 30px;
            font-size: 0.9rem;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
            position: relative;
            transition: all 0.3s ease;
            opacity: 0.8;
        }
        
        .nav-links a:hover {
            opacity: 1;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--white);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .welcome-message {
            margin-right: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Bookings Section */
        .bookings-section {
            padding: 80px 0;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 40px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 80px;
            height: 2px;
            background-color: var(--gold);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: var(--gold);
            text-decoration: none;
            font-size: 0.9rem;
            transition: opacity 0.3s ease;
        }
        
        .back-link:hover {
            opacity: 0.8;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .message.success {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--success);
            border-left: 3px solid var(--success);
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        
        .bookings-table th {
            font-family: 'Playfair Display', serif;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--gold);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }
        
        .bookings-table td {
            padding: 20px 15px;
            border-bottom: 1px solid var(--gray-2);
            vertical-align: middle;
        }
        
        .bookings-table tr:hover td {
            background-color: var(--gray-1);
        }
        
        .room-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 2px;
        }
        
        .room-info {
            display: flex;
            flex-direction: column;
        }
        
        .room-number {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .room-type {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status.pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #FFC107;
        }
        
        .status.confirmed {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }
        
        .status.cancelled {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--error);
        }
        
        .status.completed {
            background-color: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }
        
        .action-btn {
            padding: 8px 15px;
            background-color: transparent;
            color: var(--white);
            border: 1px solid var(--error);
            text-decoration: none;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .action-btn:hover {
            background-color: var(--error);
            color: var(--white);
        }
        
        .no-bookings {
            text-align: center;
            padding: 50px 0;
            opacity: 0.7;
        }
        
        /* Footer - Same as theme */
        footer {
            background-color: var(--gray-1);
            padding: 80px 0 30px;
            text-align: center;
            margin-top: 100px;
        }
        
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 30px;
            display: inline-block;
            color: var(--white);
            text-decoration: none;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: var(--white);
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.9rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .social-links {
            margin-bottom: 40px;
        }
        
        .social-links a {
            color: var(--white);
            margin: 0 10px;
            font-size: 1.2rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .social-links a:hover {
            opacity: 1;
        }
        
        .copyright {
            font-size: 0.8rem;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .bookings-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 140px;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .bookings-table th,
            .bookings-table td {
                padding: 12px 8px;
                font-size: 0.85rem;
            }
            
            .room-image {
                width: 70px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header id="header">
        <div class="container header-content">
            <a href="index.php" class="logo">HOTEL<span>EASE</span></a>
            <div class="nav-links">
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="my_bookings.php" class="active">My Bookings</a>
                <a href="logout.php">Logout</a>
                <a href="index.php#rooms">Rooms</a>
                <a href="index.php#contact">Contact</a>
            </div>
        </div>
    </header>

    <!-- Bookings Section -->
    <section class="bookings-section">
        <div class="container">
            <h1 class="section-title">My Bookings</h1>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message success"><?php echo $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Rooms
            </a>
            
            <?php if ($bookings->num_rows > 0): ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Image</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="room-info">
                                        <span class="room-number">Room <?php echo $booking['room_number']; ?></span>
                                        <span class="room-type"><?php echo $booking['room_type']; ?> Suite</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($booking['image_path']): ?>
                                        <img src="<?php echo $booking['image_path']; ?>" class="room-image" alt="Room <?php echo $booking['room_number']; ?>">
                                    <?php else: ?>
                                        <img src="images/room-placeholder.jpg" class="room-image" alt="Room Image">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_in'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_out'])); ?></td>
                                <td><?php echo formatPeso($booking['total_price']); ?></td>
                                <td>
                                    <span class="status <?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                        <a href="cancel_booking.php?booking_id=<?php echo $booking['id']; ?>" class="action-btn">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-bookings">
                    <p>You have no bookings yet.</p>
                    <a href="index.php#rooms" class="btn btn-filled" style="margin-top: 20px; display: inline-block;">
                        Browse Rooms
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <a href="index.php" class="footer-logo">HOTEL<span>EASE</span></a>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="index.php#rooms">Rooms</a>
                <a href="#">Amenities</a>
                <a href="#">Gallery</a>
                <a href="#">About</a>
                <a href="index.php#contact">Contact</a>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
            <p class="copyright">© <?php echo date('Y'); ?> HotelEase. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Confirmation for cancel action
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to cancel this booking?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>