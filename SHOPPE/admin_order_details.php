<?php
// Include the configuration file, which likely contains database connection details and starts the session.
include 'config.php';

// --- Security Check: Ensure the user is logged in and has 'admin' role ---
// If the 'user_id' is not set in the session (meaning not logged in) OR the user's 'role' is not 'admin',
// then redirect them to the home page (index.php) and stop further script execution.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// --- Input Validation: Check if an order ID is provided in the URL ---
// If the 'id' parameter is not set in the URL (e.g., admin_order_details.php?id=123),
// redirect the user back to the admin orders list page (admin_orders.php) and stop.
if (!isset($_GET['id'])) {
    header("Location: admin_orders.php");
    exit();
}

// Get the order ID from the URL and store it in a variable.
$order_id = $_GET['id'];

// --- Database Operations: Fetch order and order item details ---
// Use a try-catch block to handle potential database errors gracefully.
try {
    // Prepare a SQL statement to select order details.
    // It joins the 'orders' table with the 'users' table to get the username and email of the customer.
    // The 'WHERE' clause filters for the specific order ID.
    $order_stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                WHERE o.id = ?");
    // Execute the prepared statement, passing the order ID as a parameter to prevent SQL injection.
    $order_stmt->execute([$order_id]);
    // Fetch the result of the query as an associative array, making it easy to access columns by name.
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    // If no order was found with the given ID, redirect back to the orders list.
    if (!$order) {
        header("Location: admin_orders.php");
        exit();
    }

    // Prepare another SQL statement to get all items associated with this order.
    // It joins 'order_items' with 'products' to get product names and image URLs.
    $items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?");
    // Execute the statement with the order ID.
    $items_stmt->execute([$order_id]);
    // Fetch all the found items as an associative array.
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If a database error occurs, log the error message to the server's error log.
    error_log("Database error fetching order details: " . $e->getMessage());
    // Display a user-friendly error message on the page instead of the raw error.
    echo "<p>An error occurred while fetching order details. Please try again later.</p>";
    // Stop script execution.
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Order #<?php echo htmlspecialchars($order_id); ?> - Shopee Theme</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Custom CSS styles to match the Shopee theme */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5; /* Light gray background */
        }
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250); /* Shopee orange gradient for headers/buttons */
        }
        .shopee-text-orange {
            color: #ee4d2d; /* Shopee orange for text elements */
        }
        /* Custom scrollbar styling for a better look (optional) */
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
                    <a href="admin_dashboard.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-lg font-medium">Dashboard</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_products.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Products</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_orders.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out active-link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Orders</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_users.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H2v-2a3 3 0 015.356-1.857M9 20v-2m3 2v-2m-3 2h0m3 0h0m-1-9a4 4 0 11-8 0 4 4 0 018 0zm1-9a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-lg font-medium">Manage Users</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_analytics.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-lg font-medium">Analytics</span>
                    </a>
                </li>
                <li class="mb-2">
                    <a href="admin_stock.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m0 0h5m-5 0h5m-5 0h5M9 7h10m0 0l-3 3m3-3l-3-3M9 7v10m0 0h10"/>
                        </svg>
                        <span class="text-lg font-medium">Stock Management</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-500">Welcome, <strong class="font-semibold text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <a href="logout.php" class="mt-3 inline-block bg-black text-white px-4 py-2 rounded-full shadow hover:bg-gray-800 transition duration-300 ease-in-out text-sm">Logout</a>
        </div>
    </aside>

    <main class="flex-grow p-6">
        <header class="bg-white rounded-lg shadow-md p-4 mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Order Details #<?php echo htmlspecialchars($order_id); ?></h1>
            <a href="admin_orders.php" class="text-sm shopee-text-orange hover:underline">Back to Orders</a>
        </header>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Customer Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <p><strong class="text-gray-600">Name:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                <p><strong class="text-gray-600">Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Order Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <p><strong class="text-gray-600">Status:</strong> <span class="font-medium <?php 
                    // Use a switch statement to apply different Tailwind CSS classes based on the order status.
                    switch ($order['status']) {
                        case 'pending': echo 'text-yellow-600'; break;
                        case 'processing': echo 'text-blue-600'; break;
                        case 'completed': echo 'text-green-600'; break;
                        case 'cancelled': echo 'text-red-600'; break;
                        default: echo 'text-gray-600'; // Default color if status is unknown.
                    }
                ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></p>
                <p><strong class="text-gray-600">Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                <p><strong class="text-gray-600">Total Amount:</strong> <span class="text-xl font-bold shopee-text-orange">₱<?php echo number_format($order['total_amount'], 2); ?></span></p>
                <p><strong class="text-gray-600">Payment Method:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))); ?></p>
            </div>
            <div class="mt-4">
                <p><strong class="text-gray-600">Shipping Address:</strong></p>
                <p class="bg-gray-100 p-3 rounded-md mt-1 text-gray-700"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Order Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap flex items-center">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-12 w-12 rounded-md object-cover mr-3">
                                    <?php endif; ?>
                                    <span class="text-gray-800"><?php echo htmlspecialchars($item['name']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-50">
                            <td colspan="3" class="px-6 py-4 text-right text-lg font-bold text-gray-800">Total Order Amount:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-lg font-bold shopee-text-orange">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // JavaScript to manage active link styling in the sidebar.
            var currentPath = window.location.pathname.split('/').pop();
            // Since this is a detail page, we want the "Manage Orders" link in the sidebar to appear active.
            $('.sidebar-item[href="admin_orders.php"]').addClass('bg-gray-100 text-shopee-orange font-semibold');
            $('.sidebar-item[href="admin_orders.php"]').find('svg').addClass('shopee-text-orange');
            
            // Add a subtle hover effect to all sidebar items.
            $('.sidebar-item').hover(
                function() {
                    // On mouse hover, add Shopee orange text color to the span and SVG icon.
                    $(this).find('span').addClass('shopee-text-orange');
                    $(this).find('svg').addClass('shopee-text-orange');
                },
                function() {
                    // On mouse leave, remove the hover effect only if the item is not the currently active link.
                    if (!$(this).hasClass('bg-gray-100')) {
                        $(this).find('span').removeClass('shopee-text-orange');
                        $(this).find('svg').removeClass('shopee-text-orange');
                    }
                }
            );
        });
    </script>
</body>
</html>