<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Get report parameters from GET request
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Validate and sanitize inputs
$report_type_clean = htmlspecialchars($report_type);
$date_from_clean = htmlspecialchars($date_from);
$date_to_clean = htmlspecialchars($date_to);

// --- CSV Download Logic ---
if (isset($_GET['download_csv']) && $_GET['download_csv'] == 1) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report_type_clean . '_report_' . $date_from_clean . '_to_' . $date_to_clean . '.csv"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Define CSV headers based on report type
    $csv_headers = [
        'Booking ID', 'Customer', 'Room Number', 'Room Type', 'Check-in Date',
        'Check-out Date', 'Total Price (PHP)', 'Status', 'Booked At'
    ];
    fputcsv($output, $csv_headers);

    // Prepare and execute the query for CSV data
    $csv_query = "
        SELECT 
            b.id as booking_id,
            u.username as customer,
            r.room_number,
            r.room_type,
            b.check_in,
            b.check_out,
            b.total_price,
            b.status,
            b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        WHERE b.created_at BETWEEN ? AND ?
    ";
    $csv_params = [$date_from_clean . ' 00:00:00', $date_to_clean . ' 23:59:59'];
    $csv_types = "ss";

    switch ($report_type_clean) {
        case 'confirmed':
            $csv_query .= " AND b.status = 'confirmed'";
            break;
        case 'cancelled':
            $csv_query .= " AND b.status = 'cancelled'";
            break;
        case 'completed':
            $csv_query .= " AND b.status = 'completed'";
            break;
        // For revenue report, we'll just filter by completed and sum separately later if needed
    }
    $csv_query .= " ORDER BY b.created_at DESC";

    $csv_stmt = $conn->prepare($csv_query);
    if ($csv_stmt) {
        $csv_stmt->bind_param($csv_types, ...$csv_params);
        $csv_stmt->execute();
        $csv_bookings = $csv_stmt->get_result();

        // Output data rows
        while ($row = $csv_bookings->fetch_assoc()) {
            // Format total_price for CSV
            $row['total_price'] = '₱' . number_format($row['total_price'], 2);
            // Format status
            $row['status'] = ucfirst($row['status']);

            fputcsv($output, $row);
        }
        $csv_stmt->close();
    } else {
        // Log error or output a message to console if development
        // For production, avoid exposing detailed errors
        error_log("CSV Query preparation failed: " . $conn->error);
        // You might output a simpler error message to the CSV itself or just stop
        fputcsv($output, ['Error generating report. Please try again.']);
    }

    fclose($output);
    exit(); // Stop script execution after CSV output
}

// --- End CSV Download Logic ---

// --- HTML Display Logic (Remaining of the original PHP for HTML display) ---
// Base query using prepared statements for date range
$query = "
    SELECT 
        b.id as booking_id,
        b.check_in,
        b.check_out,
        b.total_price,
        b.status,
        b.created_at,
        u.username as customer,
        r.room_number,
        r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.created_at BETWEEN ? AND ?
";

$params = [$date_from_clean . ' 00:00:00', $date_to_clean . ' 23:59:59'];
$types = "ss";

// Add filters based on report type
switch ($report_type_clean) {
    case 'confirmed':
        $query .= " AND b.status = 'confirmed'";
        break;
    case 'cancelled':
        $query .= " AND b.status = 'cancelled'";
        break;
    case 'completed':
        $query .= " AND b.status = 'completed'";
        break;
    case 'revenue':
        // For revenue, we only count completed bookings, but the main query is for all details
        // The total_revenue calculation will handle the sum
        break;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $bookings = $stmt->get_result();
} else {
    $_SESSION['error'] = "Database query preparation failed: " . $conn->error;
    $bookings = false; // Indicate query failure
}


// Calculate total revenue for revenue report
$total_revenue = 0;
if ($report_type_clean === 'revenue') {
    $revenue_query = "SELECT SUM(total_price) as total FROM bookings WHERE status = 'completed' AND created_at BETWEEN ? AND ?";
    $revenue_stmt = $conn->prepare($revenue_query);
    if ($revenue_stmt) {
        $revenue_stmt->bind_param("ss", $date_from_clean . ' 00:00:00', $date_to_clean . ' 23:59:59');
        $revenue_stmt->execute();
        $revenue_result = $revenue_stmt->get_result();
        $total_revenue = $revenue_result->fetch_assoc()['total'];
    } else {
        $_SESSION['error'] = "Revenue query preparation failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase - Reports</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px; /* Added margin for separation */
        }

        .form-group {
            margin-bottom: 0; /* Adjusted for form-grid */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--dark);
        }

        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input[type="date"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
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
            margin-right: 10px; /* Added for spacing between buttons */
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-info { /* Style for download button */
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #16a085;
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
            .btn {
                margin-right: 0;
                margin-bottom: 10px;
                width: 100%;
                justify-content: center;
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
                    <li><a href="manage_bookings.php"><i class="fas fa-calendar-check"></i> <span>Manage Bookings</span></a></li>
                    <li><a href="reports.php" class="active"><i class="fas fa-chart-pie"></i> <span>Generate Reports</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        <div id="main-content">
            <div class="header">
                <h1>Generate Reports</h1>
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
                    <h2>Report Filters</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <form id="reportForm" method="GET" action="reports.php">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="report_type">Report Type:</label>
                                <select name="report_type" id="report_type">
                                    <option value="daily" <?php echo $report_type_clean === 'daily' ? 'selected' : ''; ?>>Daily Bookings</option>
                                    <option value="confirmed" <?php echo $report_type_clean === 'confirmed' ? 'selected' : ''; ?>>Confirmed Bookings</option>
                                    <option value="cancelled" <?php echo $report_type_clean === 'cancelled' ? 'selected' : ''; ?>>Cancelled Bookings</option>
                                    <option value="completed" <?php echo $report_type_clean === 'completed' ? 'selected' : ''; ?>>Completed Bookings</option>
                                    <option value="revenue" <?php echo $report_type_clean === 'revenue' ? 'selected' : ''; ?>>Revenue Report</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_from">Date From:</label>
                                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from_clean); ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">Date To:</label>
                                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to_clean); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-chart-line"></i> Generate Report</button>
                        <button type="button" id="downloadCsvBtn" class="btn btn-info"><i class="fas fa-file-csv"></i> Download CSV</button>
                    </form>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>
                        <?php 
                        echo ucfirst(str_replace('_', ' ', $report_type_clean)) . " Report";
                        if ($report_type_clean === 'revenue') {
                            echo " - Total Revenue: ₱" . number_format($total_revenue, 2); // Peso currency
                        }
                        ?>
                    </h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <p>Period: <?php echo htmlspecialchars($date_from_clean); ?> to <?php echo htmlspecialchars($date_to_clean); ?></p>
                    <?php if ($bookings && $bookings->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Booked At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_number'] . " - " . $booking['room_type']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                                        <td>₱<?php echo number_format($booking['total_price'], 2); ?></td> <td><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 20px;">No bookings found for the selected criteria.</p>
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
        
        // Initialize all sections as expanded (or adjust as needed)
        $('.section-content').show();
        
        // Add active class to current page link
        $('a[href="reports.php"]').addClass('active');

        // Tooltip for sidebar icons when collapsed
        $(document).on('mouseenter', '#sidebar.active li a', function() {
            var tooltipText = $(this).find('span').text().trim();
            $(this).append('<div class="tooltiptext">' + tooltipText + '</div>');
        }).on('mouseleave', '#sidebar.active li a', function() {
            $(this).find('.tooltiptext').remove();
        });

        // Handle CSV Download Button Click
        $('#downloadCsvBtn').click(function() {
            var form = $('#reportForm');
            // Create a hidden input to signal CSV download
            var downloadInput = $('<input>').attr({
                type: 'hidden',
                name: 'download_csv',
                value: '1'
            });
            form.append(downloadInput);
            form.submit();
            downloadInput.remove(); // Remove the hidden input after submission
        });
    </script>
</body>
</html>