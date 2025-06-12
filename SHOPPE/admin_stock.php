<?php
// Include the database configuration file
include 'config.php';

// Start the PHP session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has an 'admin' role.
// If not, redirect them to the home page (index.php) and stop further script execution.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// --- Handle Stock Update Request ---
// Check if the request method is POST and if 'product_id' and 'stock' values are set in the POST data.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id']) && isset($_POST['stock'])) {
    // Get the product ID from the POST data
    $product_id = $_POST['product_id'];
    // Get the new stock value from the POST data and cast it to an integer for safety
    $new_stock = (int)$_POST['stock'];

    // Prepare a SQL statement to update the 'stock' column for a specific product
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    // Execute the prepared statement with the new stock and product ID
    $stmt->execute([$new_stock, $product_id]);
    // Set a success message to be displayed to the user
    $_SESSION['success_message'] = "Stock updated successfully for product ID: " . $product_id;

    // --- Check and Resolve Stock Alerts ---
    // First, retrieve the product's current stock threshold from the database.
    $product_info_stmt = $pdo->prepare("SELECT stock_threshold FROM products WHERE id = ?");
    $product_info_stmt->execute([$product_id]);
    $product_info = $product_info_stmt->fetch(PDO::FETCH_ASSOC);

    // If product information is found and the new stock is above the alert threshold,
    // then mark any existing 'low_stock' alerts for this product as resolved.
    if ($product_info && $new_stock > $product_info['stock_threshold']) {
        $alert_stmt = $pdo->prepare("UPDATE stock_alerts SET resolved = TRUE
                                      WHERE product_id = ? AND resolved = FALSE AND alert_type = 'low_stock'");
        $alert_stmt->execute([$product_id]);
    }
    
    // Redirect the user back to the admin stock management page after the update
    header("Location: admin_stock.php");
    exit(); // Stop further script execution
}

// --- Handle Alert Resolution Request ---
// Check if 'resolve' parameter is present in the GET request.
if (isset($_GET['resolve'])) {
    // Prepare a SQL statement to mark a specific stock alert as resolved
    $stmt = $pdo->prepare("UPDATE stock_alerts SET resolved = TRUE WHERE id = ?");
    // Execute the statement with the alert ID from the GET data
    $stmt->execute([$_GET['resolve']]);
    // Set a success message
    $_SESSION['success_message'] = "Stock alert marked as resolved.";
    // Redirect the user back to the admin stock management page
    header("Location: admin_stock.php");
    exit(); // Stop further script execution
}

// --- Fetch Product Data ---
// Get all products from the database and sort them by stock in ascending order.
// This helps to easily see low stock items.
$products = $pdo->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Active Stock Alerts ---
// Get all active (unresolved) stock alerts.
// Join with the 'products' table to get product names and current stock.
// Order alerts by creation date (newest first).
$alerts = $pdo->query("SELECT sa.*, p.name, p.stock as current_product_stock 
                         FROM stock_alerts sa 
                         JOIN products p ON sa.product_id = p.id 
                         WHERE sa.resolved = FALSE 
                         ORDER BY sa.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- Stock Forecasting Data ---
// Initialize an empty array to store forecasting results.
$forecast_data = [];
// Prepare and execute a SQL query to get sales data for the last 30 days for each product.
// It calculates total sales and average daily sales.
// It also includes products that had no sales in the last 30 days using LEFT JOIN.
$forecast_stmt = $pdo->query("
    SELECT 
        p.id, 
        p.name, 
        p.stock, 
        COALESCE(SUM(oi.quantity), 0) as total_sales_30_days, /* Sum of quantities sold in last 30 days, 0 if no sales */
        COALESCE(SUM(oi.quantity)/30, 0) as avg_daily_sales /* Average daily sales, 0 if no sales */
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    LEFT JOIN orders o ON oi.order_id = o.id 
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR o.id IS NULL -- Only consider sales from last 30 days, or include products with no sales history
    GROUP BY p.id, p.name, p.stock
    ORDER BY p.name ASC
");
// Fetch all the raw data from the query
$raw_forecast_data = $forecast_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the raw forecasting data to calculate "days remaining"
foreach ($raw_forecast_data as $item) {
    // Calculate days remaining: current stock divided by average daily sales.
    // If average daily sales is 0, set days remaining to a very large number (effectively infinite)
    // to indicate no sales and therefore no depletion.
    $days_remaining = ($item['avg_daily_sales'] > 0) ? $item['stock'] / $item['avg_daily_sales'] : PHP_INT_MAX;
    // Add the calculated data to the forecast_data array
    $forecast_data[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'stock' => $item['stock'],
        'avg_daily_sales' => $item['avg_daily_sales'],
        'days_remaining' => $days_remaining
    ];
}

// Sort the forecast data based on 'days_remaining' in ascending order.
// This brings items that will run out soon to the top.
usort($forecast_data, function($a, $b) {
    return $a['days_remaining'] <=> $b['days_remaining'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Define custom styles for the page */
        body {
            font-family: 'Roboto', sans-serif; /* Apply Roboto font */
            background-color: #f5f5f5; /* Light gray background */
        }
        /* Define a Shopee-like orange gradient for backgrounds */
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250);
        }
        /* Define Shopee orange text color */
        .shopee-text-orange {
            color: #ee4d2d;
        }
        /* Custom scrollbar styling for the sidebar (optional, for aesthetics) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px; /* Width of the scrollbar */
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1; /* Color of the scrollbar track */
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888; /* Color of the scrollbar thumb */
            border-radius: 4px; /* Rounded corners for the thumb */
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555; /* Color of the scrollbar thumb on hover */
        }
        /* Active link styling for sidebar (specific to this page) */
        .sidebar-item.active-link {
            background-color: #fcece8; /* Very light orange for active background */
            color: #ee4d2d; /* Shopee orange text for active link */
            font-weight: 700; /* Bold */
        }
        .sidebar-item.active-link svg {
            color: #ee4d2d; /* Shopee orange icon for active link */
        }
        .sidebar-item:not(.active-link):hover .shopee-svg-hover {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for icons */
        }
        .sidebar-item:not(.active-link):hover span {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for text */
        }
        /* Alert specific colors */
        .alert-low-stock { background-color: #fee2e2; color: #dc2626; border-color: #ef4444; } /* Light red background, dark red text, red border */
        .alert-out-of-stock { background-color: #fef2f2; color: #b91c1c; border-color: #ef4444; } /* Lighter red background, darker red text, red border */
        /* Forecasting status colors */
        .forecast-urgent { color: #dc2626; font-weight: bold; } /* Red and bold for urgent items */
        .forecast-soon { color: #f59e0b; } /* Amber for items needing restock soon */
        .forecast-ok { color: #10b981; } /* Green for items with sufficient stock */
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
                        <?php
                            // Assuming you might want a badge here for new messages, similar to the dashboard
                            // You would need to fetch this count in admin_stock.php if you want it here.
                            // For now, it's just a placeholder as this data isn't fetched in this file.
                            // If you copy the logic from admin.php, uncomment this:
                            // if (isset($total_unread_messages) && $total_unread_messages > 0): ?>
                            <?php //endif; ?>
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
            <h1 class="text-2xl font-bold text-gray-800">Stock Management</h1>
            <a href="logout.php" class="bg-white text-black border border-gray-300 px-4 py-2 rounded-md shadow-sm hover:bg-gray-100 transition duration-300 ease-in-out">
                Logout
            </a>
        </header>

        <?php
        // Display any success or error messages stored in the session
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Success!</strong>
                        <span class="block sm:inline">' . $_SESSION['success_message'] . '</span>
                    </div>';
            unset($_SESSION['success_message']); // Clear the message after displaying
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">' . $_SESSION['error_message'] . '</span>
                    </div>';
            unset($_SESSION['error_message']); // Clear the message after displaying
        }
        ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Stock Alerts</h2>
            <?php if (empty($alerts)): ?>
                <p class="text-gray-600">No active stock alerts at the moment. All good!</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Triggered</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($alerts as $alert): ?>
                                <tr class="<?php echo ($alert['alert_type'] == 'out_of_stock') ? 'alert-out-of-stock' : 'alert-low-stock'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $alert['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo ($alert['current_product_stock'] <= 0) ? 'text-red-700 font-bold' : 'text-gray-700'; ?>"><?php echo $alert['current_product_stock']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold"><?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="admin_stock.php?resolve=<?php echo $alert['id']; ?>" class="text-green-600 hover:text-green-900 mr-4" onclick="return confirm('Mark this alert as resolved?');">Mark as Resolved</a>
                                        <a href="admin_products.php?edit=<?php echo $alert['product_id']; ?>" class="shopee-text-orange hover:text-orange-700">Edit Product</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">All Products Stock</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $product['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="stock" value="<?php echo $product['stock']; ?>" min="0" 
                                                   class="w-24 border border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-shopee-orange focus:border-shopee-orange">
                                            <button type="submit" class="bg-shopee-orange text-white px-4 py-2 rounded-md shadow-sm hover:bg-orange-600 transition duration-300 ease-in-out text-sm">Update</button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="shopee-text-orange hover:text-orange-700">Edit Product</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Stock Forecasting (Next 30 Days)</h2>
            <p class="text-sm text-gray-600 mb-4">Based on average daily sales over the last 30 days. "Days Remaining" indicates how many days current stock will last.</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Daily Sales</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Remaining</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($forecast_data) > 0): ?>
                            <?php foreach ($forecast_data as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $item['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $item['stock']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo number_format($item['avg_daily_sales'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php 
                                            // Display "N/A" if no sales in 30 days, otherwise show formatted days remaining
                                            if ($item['days_remaining'] === PHP_INT_MAX) {
                                                echo 'N/A (No sales in 30 days)';
                                            } else {
                                                echo number_format($item['days_remaining'], 1);
                                            }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php 
                                        // Determine and display stock status based on days remaining and sales
                                        if ($item['stock'] == 0 && $item['avg_daily_sales'] > 0): ?>
                                            <span class="forecast-urgent">OUT OF STOCK</span>
                                        <?php elseif ($item['days_remaining'] < 3 && $item['avg_daily_sales'] > 0): ?>
                                            <span class="forecast-urgent">URGENT RESTOCK</span>
                                        <?php elseif ($item['days_remaining'] < 7 && $item['avg_daily_sales'] > 0): ?>
                                            <span class="forecast-soon">Restock Soon</span>
                                        <?php else: ?>
                                            <span class="forecast-ok">OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No product sales data for forecasting or no products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Get the current page filename (e.g., "admin_stock.php")
            var currentPath = window.location.pathname.split('/').pop();
            
            // Loop through each sidebar item link
            $('.sidebar-item').each(function() {
                // Get the filename from the link's href attribute
                var linkPath = $(this).attr('href').split('/').pop();
                
                // If the link's filename matches the current page's filename
                if (currentPath === linkPath) {
                    // Add active styling classes to the link and its SVG icon
                    $(this).addClass('active-link'); // Use the new active-link class
                }
            });

            // Add a subtle hover effect to sidebar items (non-active ones)
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