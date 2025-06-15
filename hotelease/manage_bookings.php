<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Update booking status
if (isset($_GET['update_status'])) {
    $booking_id = $_GET['booking_id'];
    $status = $_GET['status'];
    
    $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    
    if (in_array($status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Booking status updated!";
        } else {
            $_SESSION['error'] = "Failed to update booking status: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Invalid status provided.";
    }
    redirect('manage_bookings.php'); // Redirect to prevent re-submission on refresh
}

$bookings = $conn->query("
    SELECT b.*, u.username, r.room_number, r.room_type 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase - Manage Bookings</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #1abc9c;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        #app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--dark);
            color: white;
            transition: all var(--transition-speed) ease;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        #sidebar > div {
            padding: 20px;
        }

        #sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #sidebar .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        #sidebar .user-profile:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar .user-profile > div:first-child {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            font-size: 18px;
        }

        #sidebar .user-profile small {
            opacity: 0.7;
            font-size: 0.8em;
        }

        #sidebar ul {
            list-style: none;
        }

        #sidebar ul li {
            margin-bottom: 5px;
        }

        #sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        #sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        #sidebar ul li a.active {
            background: var(--primary);
            color: white;
        }

        /* Main Content Styles */
        #main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all var(--transition-speed) ease;
            padding: 20px;
        }

        #main-content.active {
            margin-left: var(--sidebar-collapsed-width);
        }

        #sidebar.active {
            width: var(--sidebar-collapsed-width);
            overflow: hidden;
        }

        #sidebar.active h2,
        #sidebar.active .user-profile > div:last-child,
        #sidebar.active ul li a span {
            display: none;
        }

        #sidebar.active .user-profile {
            justify-content: center;
            padding: 10px 0;
        }

        #sidebar.active ul li a {
            justify-content: center;
            padding: 15px 5px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            color: var(--dark);
        }

        /* Section Styles */
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background: var(--light);
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            font-size: 1.3em;
            color: var(--dark);
        }

        .section-content {
            padding: 20px;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            border: 1px solid #eee;
            text-align: left;
        }

        .data-table th {
            background-color: var(--dark);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .data-table tr:hover {
            background-color: #f1f1f1;
        }
        
        .data-table td select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 0.95em;
            transition: border-color 0.3s;
        }

        .data-table td select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }


        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        #sidebarToggle {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        #sidebarToggle:hover {
            background: #2980b9;
        }

        /* Message/Error Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            #sidebar {
                width: var(--sidebar-collapsed-width);
                overflow: hidden;
            }
            
            #sidebar h2,
            #sidebar .user-profile > div:last-child,
            #sidebar ul li a span {
                display: none;
            }
            
            #sidebar .user-profile {
                justify-content: center;
                padding: 10px 0;
            }
            
            #sidebar ul li a {
                justify-content: center;
                padding: 15px 5px;
            }
            
            #main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <div id="sidebar">
            <div>
                <h2>HotelEase</h2>
                <div class="user-profile">
                    <div><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <div>
                        <div><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <small>Admin</small>
                    </div>
                </div>
                
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> <span>Manage Rooms</span></a></li>
                    <li><a href="manage_bookings.php" class="active"><i class="fas fa-calendar-check"></i> <span>Manage Bookings</span></a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Generate Reports</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        <div id="main-content">
            <div class="header">
                <h1>Manage Bookings</h1>
                <button id="sidebarToggle" class="btn"><i class="fas fa-bars"></i></button>
            </div>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i>' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <div class="section">
                <div class="section-header">
                    <h2>Booking List</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>User</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Booked At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number'] . " - " . $booking['room_type']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                                        <td>₱<?php echo number_format($booking['total_price'], 2); ?></td> <td><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                        <td>
                                            <select onchange="updateStatus(<?php echo htmlspecialchars($booking['id']); ?>, this.value)">
                                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px;">No bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        $('#sidebarToggle').click(function() {
            $('#sidebar, #main-content').toggleClass('active');
            $(this).find('i').toggleClass('fa-bars fa-times');
            
            // Store sidebar state in localStorage
            localStorage.setItem('sidebarCollapsed', $('#sidebar').hasClass('active'));
        });
        
        // Check saved sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            $('#sidebar, #main-content').addClass('active');
            $('#sidebarToggle i').removeClass('fa-bars').addClass('fa-times');
        }
        
        // Collapsible sections
        $('.section-header').click(function() {
            $(this).next('.section-content').slideToggle();
            $(this).find('span').text(function(_, text) {
                return text === '▼' ? '▲' : '▼';
            });
        });
        
        // Initialize all sections as expanded (or adjust as needed)
        $('.section-content').show();
        
        // Add active class to current page link
        $('a[href="manage_bookings.php"]').addClass('active');

        // Tooltip for sidebar icons when collapsed
        $(document).on('mouseenter', '#sidebar.active li a', function() {
            var tooltipText = $(this).find('span').text().trim();
            $(this).append('<div class="tooltiptext">' + tooltipText + '</div>');
        }).on('mouseleave', '#sidebar.active li a', function() {
            $(this).find('.tooltiptext').remove();
        });

        function updateStatus(bookingId, status) {
            if (confirm("Are you sure you want to change booking status to " + status + "?")) {
                window.location.href = "manage_bookings.php?update_status=1&booking_id=" + bookingId + "&status=" + status;
            }
        }
    </script>
</body>
</html>