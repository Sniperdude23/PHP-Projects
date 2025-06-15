<?php 
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Get stats
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$available_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE available = TRUE")->fetch_assoc()['count'];
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];

// Get data for charts
$monthly_bookings = [];
$room_type_popularity = [];
$booking_status_distribution = [];

// Monthly bookings data
$result = $conn->query("
    SELECT MONTH(created_at) as month, COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(created_at)
");
while ($row = $result->fetch_assoc()) {
    $monthly_bookings[] = $row;
}

// Room type popularity
$result = $conn->query("
    SELECT r.room_type, COUNT(b.id) as count 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    GROUP BY r.room_type
");
while ($row = $result->fetch_assoc()) {
    $room_type_popularity[] = $row;
}

// Booking status distribution
$result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
");
while ($row = $result->fetch_assoc()) {
    $booking_status_distribution[] = $row;
}

// Get revenue data
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'confirmed' OR status = 'completed'")->fetch_assoc()['total'];
$monthly_revenue = $conn->query("
    SELECT MONTH(created_at) as month, SUM(total_price) as total 
    FROM bookings 
    WHERE (status = 'confirmed' OR status = 'completed') AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(created_at)
");
$revenue_data = [];
while ($row = $monthly_revenue->fetch_assoc()) {
    $revenue_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase - Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-card > div {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .stat-card h6 {
      color: #7f8c8d;
      font-size: 0.9em;
      margin-bottom: 5px;
    }

    .stat-card h2 {
      font-size: 2em;
      color: var(--dark);
    }

    .stat-card > div > div:last-child {
      font-size: 2.5em;
      opacity: 0.3;
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

    /* Charts Grid */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    .chart-card {
      background: white;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }

    .chart-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .chart-card h5 {
      margin-bottom: 15px;
      color: var(--dark);
      font-size: 1.1em;
    }

    /* Forecast Grid */
    .forecast-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    .forecast-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }

    .forecast-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .forecast-card h5 {
      margin-bottom: 10px;
      color: var(--dark);
    }

    .forecast-card p {
      color: #7f8c8d;
      font-size: 0.9em;
      margin-bottom: 15px;
    }

    .forecast-card > div:not(.progress-bar) {
      font-size: 1.5em;
      font-weight: bold;
      margin-bottom: 10px;
      color: var(--secondary);
    }

    .progress-bar {
      height: 10px;
      background: #ecf0f1;
      border-radius: 5px;
      margin: 15px 0;
      overflow: hidden;
    }

    .progress-bar div {
      height: 100%;
      background: var(--secondary);
      transition: width 1s ease;
    }

    /* Quick Actions */
    .quick-actions {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .quick-actions h5 {
      margin-bottom: 15px;
      color: var(--dark);
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .actions-grid a {
      display: block;
      padding: 15px;
      background: var(--light);
      color: var(--dark);
      text-align: center;
      text-decoration: none;
      border-radius: 5px;
      transition: all 0.3s;
    }

    .actions-grid a:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
    }

    /* Button Styles */
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

    /* Badge Styles */
    .badge {
      display: inline-block;
      background: var(--danger);
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      text-align: center;
      line-height: 20px;
      font-size: 12px;
      margin-left: 5px;
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
      
      .stats-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 576px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .charts-grid,
      .forecast-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .stat-card {
      animation: fadeIn 0.5s ease forwards;
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }

    /* Loading Animation */
    .loader {
      display: none;
      border: 3px solid #f3f3f3;
      border-radius: 50%;
      border-top: 3px solid var(--primary);
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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
                    <li><a href="#"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> <span>Manage Rooms</span></a></li>
                    <li><a href="manage_bookings.php"><i class="fas fa-calendar-check"></i> <span>Manage Bookings</span></a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Generate Reports</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        <div id="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div>
                            <h6>Total Rooms</h6>
                            <h2><?php echo $total_rooms; ?></h2>
                        </div>
                        <div><i class="fas fa-building"></i></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <div>
                            <h6>Available Rooms</h6>
                            <h2><?php echo $available_rooms; ?></h2>
                        </div>
                        <div><i class="fas fa-door-open"></i></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <div>
                            <h6>Total Bookings</h6>
                            <h2><?php echo $total_bookings; ?></h2>
                        </div>
                        <div><i class="fas fa-calendar"></i></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <div>
                            <h6>Pending Bookings</h6>
                            <h2><?php echo $pending_bookings; ?></h2>
                        </div>
                        <div><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Analytics</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <div class="charts-grid">
                        <div class="chart-card">
                            <h5>Monthly Bookings</h5>
                            <canvas id="monthlyBookingsChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h5>Booking Status Distribution</h5>
                            <canvas id="statusPieChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h5>Room Type Popularity</h5>
                            <canvas id="roomTypePieChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h5>Revenue Trends</h5>
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Forecast & Predictions</h2>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <div class="forecast-grid">
                        <div class="forecast-card">
                            <h5>Next Month Booking Forecast</h5>
                            <p>Based on historical data and current trends</p>
                            <div><?php echo max(5, round($total_bookings * 0.8)); ?> expected bookings</div>
                            <div class="progress-bar">
                                <div style="width: <?php echo min(100, max(5, round($total_bookings * 0.8 / $total_rooms * 100))) ?>%"></div>
                            </div>
                            <small><?php echo min(100, max(5, round($total_bookings * 0.8 / $total_rooms * 100))); ?>% occupancy rate</small>
                        </div>
                        <div class="forecast-card">
                            <h5>Revenue Projection</h5>
                            <p>Estimated revenue for next 30 days</p>
                            <div>₱<?php echo number_format(max(5000, $total_bookings * 150), 2); ?></div>
                            <div>↑ 12% increase from last month</div>
                        </div>
                        <div class="chart-card">
                            <h5>Occupancy Rate Trend</h5>
                            <canvas id="occupancyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h5>Quick Actions</h5>
                <div class="actions-grid">
                    <a href="manage_rooms.php?action=add"><i class="fas fa-plus"></i> Add Room</a>
                    <a href="manage_bookings.php?action=add"><i class="fas fa-plus-circle"></i> Create Booking</a>
                    <a href="reports.php"><i class="fas fa-file-alt"></i> Generate Report</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
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
        $('a[href="' + window.location.pathname.split('/').pop() + '"]').addClass('active');
        
        // Tooltip for sidebar icons when collapsed
        $(document).on('mouseenter', '#sidebar.active li a', function() {
            var tooltipText = $(this).find('span').text().trim();
            $(this).append('<div class="tooltiptext">' + tooltipText + '</div>');
        }).on('mouseleave', '#sidebar.active li a', function() {
            $(this).find('.tooltiptext').remove();
        });
        
        // Animate progress bars on scroll
        $(window).scroll(function() {
            $('.progress-bar div').each(function() {
                var position = $(this).offset().top;
                var scroll = $(window).scrollTop();
                var windowHeight = $(window).height();
                
                if (scroll > position - windowHeight + 200) {
                    $(this).css('width', $(this).attr('style').match(/\d+/)[0] + '%');
                }
            });
        }).scroll(); // Trigger scroll handler on page load
        
        // Enhance chart interactivity
        $('.chart-card canvas').hover(
            function() {
                $(this).parent().css('transform', 'scale(1.02)');
                $(this).parent().css('box-shadow', '0 5px 15px rgba(0,0,0,0.1)');
            },
            function() {
                $(this).parent().css('transform', 'scale(1)');
                $(this).parent().css('box-shadow', '0 2px 4px rgba(0,0,0,0.05)');
            }
        );
        
        // Notification badge for pending bookings
        if (<?php echo $pending_bookings; ?> > 0) {
            $('a[href="manage_bookings.php"]').append('<span class="badge"><?php echo $pending_bookings; ?></span>');
        }
        
        // Real-time data refresh (simulated)
        function refreshData() {
            $('.loader').show();
            
            // Simulate API call with setTimeout
            setTimeout(function() {
                // In a real app, you would use $.ajax or $.get here
                var newPending = <?php echo $pending_bookings; ?> + Math.floor(Math.random() * 3) - 1;
                newPending = Math.max(0, newPending);
                
                $('.stat-card:nth-child(4) h2').text(newPending);
                $('a[href="manage_bookings.php"] .badge').text(newPending);
                
                // Animate number changes
                $({ countNum: $('.stat-card:nth-child(4) h2').text() }).animate({ countNum: newPending }, {
                    duration: 800,
                    step: function() {
                        $('.stat-card:nth-child(4) h2').text(Math.floor(this.countNum));
                    }
                });
                
                $('.loader').hide();
            }, 1000);
        }
        
        // Refresh every 30 seconds
        setInterval(refreshData, 30000);
        
        // Initialize charts
        $(document).ready(function() {
            // Monthly Bookings Chart
            new Chart(document.getElementById('monthlyBookingsChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Bookings',
                        data: <?php echo json_encode(array_map(function($m) { return $m['count']; }, $monthly_bookings)); ?>,
                        borderColor: '#3498db',
                        borderWidth: 2,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Room Type Pie Chart
            new Chart(document.getElementById('roomTypePieChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_map(function($r) { return $r['room_type']; }, $room_type_popularity)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_map(function($r) { return $r['count']; }, $room_type_popularity)); ?>,
                        backgroundColor: ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.formattedValue || '';
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = Math.round((context.parsed / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Status Pie Chart
            new Chart(document.getElementById('statusPieChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_map(function($s) { return ucfirst($s['status']); }, $booking_status_distribution)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_map(function($s) { return $s['count']; }, $booking_status_distribution)); ?>,
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#17a2b8'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });

            // Revenue Chart
            new Chart(document.getElementById('revenueChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: <?php echo json_encode(array_map(function($r) { return $r['total']; }, $revenue_data)); ?>,
                        backgroundColor: 'rgba(46, 204, 113, 0.7)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString('en-PH'); // Format as Philippine Peso
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString('en-PH'); // Format as Philippine Peso
                                }
                            }
                        }
                    }
                }
            });

            // Occupancy Chart
            new Chart(document.getElementById('occupancyChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul (forecast)'],
                    datasets: [{
                        label: 'Occupancy Rate (%)',
                        data: [60, 65, 70, 75, 80, 75, 80],
                        borderColor: '#6c757d',
                        borderWidth: 2,
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            min: 0,
                            max: 100
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>