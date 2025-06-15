<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Add new room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $description = $_POST['description'];
    $price = $_POST['price_per_night'];
    $capacity = $_POST['capacity'];
    $available = isset($_POST['available']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, description, price_per_night, capacity, available) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdii", $room_number, $room_type, $description, $price, $capacity, $available);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Room added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add room: " . $stmt->error;
    }
    redirect('manage_rooms.php'); // Redirect to prevent form re-submission
}

// Update room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $description = $_POST['description'];
    $price = $_POST['price_per_night'];
    $capacity = $_POST['capacity'];
    $available = isset($_POST['available']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_type = ?, description = ?, price_per_night = ?, capacity = ?, available = ? WHERE id = ?");
    $stmt->bind_param("sssdiii", $room_number, $room_type, $description, $price, $capacity, $available, $room_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Room updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update room: " . $stmt->error;
    }
    redirect('manage_rooms.php'); // Redirect to prevent form re-submission
}

// Delete room
if (isset($_GET['delete_room'])) {
    $room_id = $_GET['delete_room'];
    
    // Check if room has bookings
    $has_bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = ?");
    $has_bookings_stmt->bind_param("i", $room_id);
    $has_bookings_stmt->execute();
    $has_bookings_result = $has_bookings_stmt->get_result();
    $has_bookings = $has_bookings_result->fetch_assoc()['count'];
    
    if ($has_bookings > 0) {
        $_SESSION['error'] = "Cannot delete room with existing bookings.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $delete_stmt->bind_param("i", $room_id);
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Room deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete room: " . $delete_stmt->error;
        }
    }
    redirect('manage_rooms.php'); // Redirect after deletion
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase - Manage Rooms</title>
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

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--dark);
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.2);
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

        .data-table td input[type="text"],
        .data-table td input[type="number"],
        .data-table td textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .data-table td input[type="checkbox"] {
            transform: scale(1.1);
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

        .btn-success {
            background-color: var(--secondary);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            margin-left: 10px;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #16a085;
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 8px;
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
                        <div><?php echo $_SESSION['username']; ?></div>
                        <small>Admin</small>
                    </div>
                </div>
                
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="manage_rooms.php" class="active"><i class="fas fa-bed"></i> <span>Manage Rooms</span></a></li>
                    <li><a href="manage_bookings.php"><i class="fas fa-calendar-check"></i> <span>Manage Bookings</span></a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Generate Reports</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        <div id="main-content">
            <div class="header">
                <h1>Manage Rooms</h1>
                <button id="sidebarToggle" class="btn"><i class="fas fa-bars"></i></button>
            </div>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i>' . $_SESSION['message'] . '</div>';
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <div class="section">
                <div class="section-header">
                    <h2>Add New Room</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <form method="POST">
                        <input type="hidden" name="add_room" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="room_number">Room Number:</label>
                                <input type="text" id="room_number" name="room_number" required>
                            </div>
                            <div class="form-group">
                                <label for="room_type">Room Type:</label>
                                <input type="text" id="room_type" name="room_type" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="price_per_night">Price per Night (₱):</label>
                                <input type="number" step="0.01" id="price_per_night" name="price_per_night" required>
                            </div>
                            <div class="form-group">
                                <label for="capacity">Capacity:</label>
                                <input type="number" id="capacity" name="capacity" required>
                            </div>
                            <div class="form-group">
                                <label for="available">Available:</label>
                                <input type="checkbox" id="available" name="available" checked>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Room</button>
                    </form>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Room List</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room #</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Capacity</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                <tr>
                                    <form method="POST">
                                        <input type="hidden" name="update_room" value="1">
                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                        
                                        <td><input type="text" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>"></td>
                                        <td><input type="text" name="room_type" value="<?php echo htmlspecialchars($room['room_type']); ?>"></td>
                                        <td><textarea name="description"><?php echo htmlspecialchars($room['description']); ?></textarea></td>
                                        <td><input type="number" step="0.01" name="price_per_night" value="<?php echo htmlspecialchars($room['price_per_night']); ?>"></td>
                                        <td><input type="number" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>"></td>
                                        <td><input type="checkbox" name="available" <?php echo $room['available'] ? 'checked' : ''; ?>></td>
                                        <td>
                                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-edit"></i> Update</button>
                                            <a href="manage_rooms.php?delete_room=<?php echo $room['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete room <?php echo htmlspecialchars($room['room_number']); ?>? This action cannot be undone.');"><i class="fas fa-trash-alt"></i> Delete</a>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php if ($rooms->num_rows === 0): ?>
                        <p style="text-align: center; padding: 20px;">No rooms found. Add a new room above!</p>
                    <?php endif; ?>
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
        
        // Initialize all sections as expanded
        $('.section-content').show();
        
        // Add active class to current page link
        $('a[href="manage_rooms.php"]').addClass('active');
        
        // Tooltip for sidebar icons when collapsed
        $(document).on('mouseenter', '#sidebar.active li a', function() {
            var tooltipText = $(this).find('span').text().trim();
            $(this).append('<div class="tooltiptext">' + tooltipText + '</div>');
        }).on('mouseleave', '#sidebar.active li a', function() {
            $(this).find('.tooltiptext').remove();
        });
    </script>
</body>
</html>