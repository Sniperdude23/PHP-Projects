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

// --- Data Retrieval for Analytics Dashboard ---

// Get sales data for chart (last 30 days)
$sales_data = [];
try {
    $stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as total_sales
                         FROM orders
                         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY DATE(created_at)
                         ORDER BY date ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sales_data[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    // Handle error gracefully, e.g., display an empty chart or error message
    $sales_data = [];
}


// Fill in missing dates with 0 sales for the chart to show a continuous line
$dates = [];
$currentDate = new DateTime();
for ($i = 0; $i < 30; $i++) {
    $dates[] = $currentDate->format('Y-m-d');
    $currentDate->modify('-1 day');
}
$dates = array_reverse($dates); // Sort dates in ascending order

$full_sales_data = [];
foreach ($dates as $date) {
    $found = false;
    foreach ($sales_data as $s_data) {
        if ($s_data['date'] == $date) {
            $full_sales_data[] = $s_data;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $full_sales_data[] = ['date' => $date, 'total_sales' => 0];
    }
}


// Get top products
$top_products = [];
try {
    $top_stmt = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_sold
                               FROM order_items oi
                               JOIN products p ON oi.product_id = p.id
                               GROUP BY p.id
                               ORDER BY total_sold DESC
                               LIMIT 5"); // Limit to top 5
    while ($row = $top_stmt->fetch(PDO::FETCH_ASSOC)) {
        $top_products[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching top products: " . $e->getMessage());
    $top_products = [];
}

// Get category sales
$category_sales = [];
try {
    $cat_stmt = $pdo->query("SELECT p.category, SUM(oi.quantity * oi.price) as total_sales
                               FROM order_items oi
                               JOIN products p ON oi.product_id = p.id
                               WHERE p.category IS NOT NULL AND p.category != ''
                               GROUP BY p.category
                               ORDER BY total_sales DESC");
    while ($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
        $category_sales[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching category sales: " . $e->getMessage());
    $category_sales = [];
}

// Get total sales and total orders (All time)
$total_sales_all_time = 0;
$total_orders_all_time = 0;
try {
    $overall_stmt = $pdo->query("SELECT SUM(total_amount) as total_sales_sum, COUNT(id) as total_orders_count FROM orders");
    $overall_data = $overall_stmt->fetch(PDO::FETCH_ASSOC);
    if ($overall_data) {
        $total_sales_all_time = $overall_data['total_sales_sum'] ?? 0;
        $total_orders_all_time = $overall_data['total_orders_count'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching overall sales/orders: " . $e->getMessage());
}

// Get total products
$total_products = 0;
try {
    $products_stmt = $pdo->query("SELECT COUNT(id) as product_count FROM products");
    $products_data = $products_stmt->fetch(PDO::FETCH_ASSOC);
    if ($products_data) {
        $total_products = $products_data['product_count'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching total products: " . $e->getMessage());
}

// Get total users (customers)
$total_users = 0;
try {
    $users_stmt = $pdo->query("SELECT COUNT(id) as user_count FROM users WHERE role = 'customer'");
    $users_data = $users_stmt->fetch(PDO::FETCH_ASSOC);
    if ($users_data) {
        $total_users = $users_data['user_count'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching total users: " . $e->getMessage());
}


// Get customer acquisition data (last 30 days)
$customer_acquisition_data = [];
try {
    $cust_stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(id) as new_users
                               FROM users
                               WHERE role = 'customer' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                               GROUP BY DATE(created_at)
                               ORDER BY date ASC");
    while ($row = $cust_stmt->fetch(PDO::FETCH_ASSOC)) {
        $customer_acquisition_data[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching customer acquisition data: " . $e->getMessage());
    $customer_acquisition_data = [];
}


// Fill in missing dates with 0 new users for the chart
$full_customer_acquisition_data = [];
foreach ($dates as $date) { // Re-using the 'dates' array from sales data
    $found = false;
    foreach ($customer_acquisition_data as $c_data) {
        if ($c_data['date'] == $date) {
            $full_customer_acquisition_data[] = $c_data;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $full_customer_acquisition_data[] = ['date' => $date, 'new_users' => 0];
    }
}


// Get low stock products
$low_stock_products = [];
$low_stock_threshold = 10; // Define your low stock threshold
try {
    $low_stock_stmt = $pdo->prepare("SELECT name, stock FROM products WHERE stock <= ? AND stock > 0 ORDER BY stock ASC");
    $low_stock_stmt->execute([$low_stock_threshold]);
    while ($row = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)) {
        $low_stock_products[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching low stock products: " . $e->getMessage());
    $low_stock_products = [];
}

// Get out of stock products
$out_of_stock_products = [];
try {
    $out_of_stock_stmt = $pdo->query("SELECT name FROM products WHERE stock = 0 ORDER BY name ASC");
    while ($row = $out_of_stock_stmt->fetch(PDO::FETCH_ASSOC)) {
        $out_of_stock_products[] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching out of stock products: " . $e->getMessage());
    $out_of_stock_products = [];
}

// --- New: Average Order Value (AOV) ---
$average_order_value = 0;
if ($total_orders_all_time > 0) {
    $average_order_value = $total_sales_all_time / $total_orders_all_time;
}

// --- New: Top Performing Category by Sales (for quick view) ---
$top_category_name = 'N/A';
$top_category_sales = 0;
if (!empty($category_sales)) {
    $top_category = $category_sales[0]; // Assuming category_sales is already ordered by total_sales DESC
    $top_category_name = $top_category['category'];
    $top_category_sales = $top_category['total_sales'];
}

// --- New: Get total count of unread messages for dashboard badge ---
$total_unread_messages = 0;
try {
    $total_unread_stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
    $total_unread_messages = $total_unread_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching total unread messages: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Shopee Theme</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
        }
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250);
        }
        .shopee-text-orange {
            color: #ee4d2d;
        }
        .shopee-bg-orange {
            background-color: #ee4d2d;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .download-button {
            @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white shopee-bg-orange hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee-orange transition duration-300 ease-in-out;
        }

        /* Active link styling for sidebar (specific to this page) */
        .sidebar-item.active-link {
            background-color: #fcece8; /* Very light orange for active background */
            color: #ee4d2d; /* Shopee orange text for active link */
            font-weight: 700; /* Bold */
        }
        .sidebar-item.active-link svg,
        .sidebar-item.active-link i {
            color: #ee4d2d; /* Shopee orange icon for active link */
        }
        .sidebar-item:not(.active-link):hover .shopee-svg-hover,
        .sidebar-item:not(.active-link):hover i {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for icons */
        }
        .sidebar-item:not(.active-link):hover span {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for text */
        }

        .shopee-badge {
            background-color: #ff0000; /* Red for new messages */
            color: white;
            font-size: 0.75rem;
            padding: 0.15rem 0.4rem;
            border-radius: 9999px; /* Pill shape */
            margin-left: auto; /* Push to the right */
        }
    </style>
</head>
<body class="flex min-h-screen">
    <aside class="w-64 bg-white shadow-lg flex flex-col custom-scrollbar overflow-y-auto">
        <div class="p-6 shopee-gradient text-white text-center">
            <h2 class="text-3xl font-bold">Admin Hub</h2>
        </div>
        <nav class="flex-grow p-4">
            <ul>
                <li class="mb-2">
                    <a href="admin.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-lg font-medium">Dashboard</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_products.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Products</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_orders.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Orders</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_users.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H2v-2a3 3 0 015.356-1.857M9 20v-2m3 2v-2m-3 2h0m3 0h0m-1-9a4 4 0 11-8 0 4 4 0 018 0zm1-9a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Users</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_analytics.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-lg font-medium">Analytics</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_stock.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 shopee-svg-hover" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m0 0h5m-5 0h5m-5 0h5M9 7h10m0 0l-3 3m3-3l-3-3M9 7v10m0 0h10"/>
                        </svg>
                        <span class="text-lg font-medium">Stock Management</span>
                    </a>
                </li>
                 <li class="mb-2">
                    <a href="admin_contact_messages.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <i class="fas fa-envelope h-6 w-6 mr-3 shopee-svg-hover"></i>
                        <span class="text-lg font-medium">Contact Messages</span>
                        <?php if ($total_unread_messages > 0): /* */?>
                            <span class="shopee-badge"><?php echo $total_unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-500">Welcome, <strong class="font-semibold text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
        </div>
    </aside>

    <main class="flex-grow p-6">
        <header class="bg-white rounded-lg shadow-md p-4 mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Analytics Dashboard</h1>
            <div class="flex items-center">
                <input type="text" placeholder="Search..." class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-shopee-orange mr-4 hidden">
                <a href="logout.php" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md shadow-sm hover:bg-gray-100 transition duration-300 ease-in-out">Logout</a>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Sales (All Time)</h3>
                <p class="text-3xl font-bold shopee-text-orange">₱<?php echo number_format($total_sales_all_time, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Orders (All Time)</h3>
                <p class="text-3xl font-bold shopee-text-orange"><?php echo number_format($total_orders_all_time); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Products</h3>
                <p class="text-3xl font-bold shopee-text-orange"><?php echo number_format($total_products); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Total Customers</h3>
                <p class="text-3xl font-bold shopee-text-orange"><?php echo number_format($total_users); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Average Order Value (AOV)</h3>
                <p class="text-3xl font-bold shopee-text-orange">₱<?php echo number_format($average_order_value, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center col-span-1 md:col-span-2 lg:col-span-1">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Top Performing Category</h3>
                <p class="text-3xl font-bold shopee-text-orange"><?php echo htmlspecialchars($top_category_name); ?></p>
                <?php if ($top_category_sales > 0): ?>
                    <p class="text-base text-gray-500">(₱<?php echo number_format($top_category_sales, 2); ?> in sales)</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Sales Last 30 Days</h2>
                <canvas id="salesChart" height="300"></canvas>
                <script>
                    const salesCtx = document.getElementById('salesChart').getContext('2d');
                    const salesChart = new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($full_sales_data, 'date')); ?>,
                            datasets: [{
                                label: 'Daily Sales',
                                data: <?php echo json_encode(array_column($full_sales_data, 'total_sales')); ?>,
                                backgroundColor: 'rgba(238, 77, 45, 0.1)',
                                borderColor: '#ee4d2d',
                                borderWidth: 2,
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Top Selling Products</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units Sold</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-gray-500 text-center">No top products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['total_sold']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Sales by Category</h2>
                <canvas id="categorySalesChart" height="300"></canvas>
                <script>
                    const categorySalesCtx = document.getElementById('categorySalesChart').getContext('2d');
                    const categorySalesChart = new Chart(categorySalesCtx, {
                        type: 'bar', // Changed to bar chart for easier comparison of category sales
                        data: {
                            labels: <?php echo json_encode(array_column($category_sales, 'category')); ?>,
                            datasets: [{
                                label: 'Sales by Category',
                                data: <?php echo json_encode(array_column($category_sales, 'total_sales')); ?>,
                                backgroundColor: [
                                    '#ee4d2d', // Shopee orange
                                    '#FFC107', // Amber
                                    '#4CAF50', // Green
                                    '#2196F3', // Blue
                                    '#9C27B0', // Purple
                                    '#FF5722', // Deep Orange
                                    '#00BCD4', // Cyan
                                    '#8BC34A', // Light Green
                                    '#FFEB3B', // Yellow
                                    '#607D8B'  // Blue Grey
                                ],
                                borderColor: [
                                    '#ee4d2d',
                                    '#FFC107',
                                    '#4CAF50',
                                    '#2196F3',
                                    '#9C27B0',
                                    '#FF5722',
                                    '#00BCD4',
                                    '#8BC34A',
                                    '#FFEB3B',
                                    '#607D8B'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed.y;
                                            return label + ': ₱' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Customer Acquisition Last 30 Days (Wave Chart)</h2>
                <canvas id="customerAcquisitionChart" height="300"></canvas>
                <script>
                    const customerAcquisitionCtx = document.getElementById('customerAcquisitionChart').getContext('2d');
                    const customerAcquisitionChart = new Chart(customerAcquisitionCtx, {
                        type: 'line', // Still a line chart for wave effect
                        data: {
                            labels: <?php echo json_encode(array_column($full_customer_acquisition_data, 'date')); ?>,
                            datasets: [{
                                label: 'New Customers',
                                data: <?php echo json_encode(array_column($full_customer_acquisition_data, 'new_users')); ?>,
                                backgroundColor: 'rgba(59, 130, 246, 0.3)', // More opaque for wave effect
                                borderColor: '#3b82f6',
                                borderWidth: 2,
                                tension: 0.4, // Increased tension for a wavier line
                                fill: true, // Fill the area under the line
                                pointRadius: 3, // Smaller points for a smoother wave
                                pointBackgroundColor: '#3b82f6',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#3b82f6',
                                pointHoverBorderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += context.parsed.y.toLocaleString() + ' customers';
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            if (Number.isInteger(value)) {
                                                return value;
                                            }
                                        }
                                    }
                                },
                                x: {
                                    // You might want to adjust the x-axis tick display for better readability if dates are too crowded
                                    ticks: {
                                        autoSkip: true,
                                        maxTicksLimit: 10 // Show fewer date labels if there are many
                                    }
                                }
                            }
                        }
                    });
                </script>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Low Stock Products (< <?php echo $low_stock_threshold; ?> units)</h2>
                <div class="overflow-x-auto max-h-64 custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($low_stock_products)): ?>
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-gray-500 text-center">No products with low stock.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-500 font-semibold"><?php echo htmlspecialchars($product['stock']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Out of Stock Products</h2>
                <div class="overflow-x-auto max-h-64 custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($out_of_stock_products)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-center">No products currently out of stock.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($out_of_stock_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Download Reports</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="download_sales_report.php" class="download-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Download Sales Report (CSV)
                </a>
                <a href="download_products_report.php" class="download-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Download Products Report (CSV)
                </a>
                <a href="download_users_report.php" class="download-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Download Users Report (CSV)
                </a>
            </div>
        </div>

    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Apply active link styling based on the current page
            var currentPath = window.location.pathname.split('/').pop();

            // Default to dashboard if no specific page is in URL or it's admin.php
            if (currentPath === '' || currentPath === 'admin.php') {
                $('.sidebar-item[href="admin.php"]').addClass('active-link');
            } else {
                // Find the current page in navigation and highlight it
                $('.sidebar-item').each(function() {
                    var linkPath = $(this).attr('href').split('/').pop();
                    if (currentPath === linkPath) {
                        $(this).addClass('active-link');
                    }
                });
            }

            // Add hover effect to sidebar links
            $('.sidebar-item:not(.active-link)').hover(
                function() {
                    $(this).find('span').addClass('shopee-text-orange');
                    $(this).find('svg, i').addClass('shopee-text-orange'); // Also target <i> for Font Awesome
                },
                function() {
                    $(this).find('span').removeClass('shopee-text-orange');
                    $(this).find('svg, i').removeClass('shopee-text-orange'); // Also target <i> for Font Awesome
                }
            );
        });
    </script>
</body>
</html>