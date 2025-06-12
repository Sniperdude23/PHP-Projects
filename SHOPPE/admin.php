<?php
// Include the configuration file
include 'config.php';

// Check if user is logged in and has admin role
// Redirect to index.php if not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle marking contact message as read via AJAX
if (isset($_POST['mark_as_read']) && isset($_POST['message_id'])) {
    $message_id = $_POST['message_id'];
    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Error marking contact message as read: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit(); // Important: exit after AJAX response
}

// Get sales statistics for dashboard:
// - Total sales amount
// - Total number of orders
$sales_stmt = $pdo->query("SELECT SUM(total_amount) as total_sales, COUNT(*) as total_orders FROM orders");
$sales = $sales_stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

// Get total number of products in the database
$products_stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$products = $products_stmt->fetch(PDO::FETCH_ASSOC);

// Get count of products with low stock (5 or fewer items remaining)
$low_stock_stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock <= 5");
$low_stock = $low_stock_stmt->fetch(PDO::FETCH_ASSOC);

// Get count of active stock alerts that haven't been resolved
$alerts_stmt = $pdo->query("SELECT COUNT(*) as active_alerts FROM stock_alerts WHERE resolved = FALSE");
$alerts = $alerts_stmt->fetch(PDO::FETCH_ASSOC);

// --- New: Fetch unread contact messages ---
$unread_messages = [];
try {
    $unread_messages_stmt = $pdo->query("SELECT id, name, email, subject, message, created_at FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $unread_messages = $unread_messages_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching unread contact messages: " . $e->getMessage());
    // Optionally, set a message to display to the admin about the error
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
    <title>Admin Panel - Shopee Theme</title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        /* Set default font family */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5; /* Light gray background */
        }

        /* Shopee brand gradient */
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250); /* Shopee orange gradient */
        }

        /* Shopee brand text color */
        .shopee-text-orange {
            color: #ee4d2d; /* Shopee orange text */
        }

        /* Custom scrollbar styling for sidebar */
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
                    <a href="admin.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out active-link">
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
                        <?php if ($total_unread_messages > 0): ?>
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
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <a href="logout.php" class="bg-white text-black border border-gray-300 px-4 py-2 rounded-md shadow-sm hover:bg-gray-100 transition duration-300 ease-in-out">
                Logout
            </a>
        </header>

        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Quick Stats</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-300 ease-in-out">
                    <h3 class="text-lg font-medium text-gray-500">Total Sales</h3>
                    <p class="text-3xl font-bold shopee-text-orange mt-2">₱<?php echo number_format($sales['total_sales'] ?? 0, 2); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-300 ease-in-out">
                    <h3 class="text-lg font-medium text-gray-500">Total Orders</h3>
                    <p class="text-3xl font-bold shopee-text-orange mt-2"><?php echo $sales['total_orders'] ?? 0; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-300 ease-in-out">
                    <h3 class="text-lg font-medium text-gray-500">Total Products</h3>
                    <p class="text-3xl font-bold shopee-text-orange mt-2"><?php echo $products['total_products'] ?? 0; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-300 ease-in-out">
                    <h3 class="text-lg font-medium text-gray-500">Low Stock Items</h3>
                    <p class="text-3xl font-bold <?php echo ($low_stock['low_stock'] ?? 0) > 0 ? 'text-red-500' : 'shopee-text-orange'; ?> mt-2"><?php echo $low_stock['low_stock'] ?? 0; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition duration-300 ease-in-out">
                    <h3 class="text-lg font-medium text-gray-500">Active Alerts</h3>
                    <p class="text-3xl font-bold <?php echo ($alerts['active_alerts'] ?? 0) > 0 ? 'text-red-500' : 'shopee-text-orange'; ?> mt-2"><?php echo $alerts['active_alerts'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($unread_messages)): ?>
        <div class="mb-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2 flex justify-between items-center">
                Recent Unread Contact Messages
                <a href="admin_contact_messages.php" class="text-sm shopee-text-orange hover:underline">View All Messages</a>
            </h2>
            <div class="space-y-4">
                <?php foreach ($unread_messages as $message): ?>
                    <div id="message-<?php echo $message['id']; ?>" class="bg-gray-50 p-4 rounded-md border border-gray-200">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-lg font-semibold text-gray-800">From: <?php echo htmlspecialchars($message['name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</p>
                                <p class="text-sm text-gray-600">Subject: <?php echo htmlspecialchars($message['subject']); ?></p>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo date('M d, Y H:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                        <p class="text-gray-700 mt-2 line-clamp-3"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <div class="mt-3 text-right">
                            <button
                                type="button"
                                class="mark-as-read-btn bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition duration-200"
                                data-message-id="<?php echo $message['id']; ?>"
                            >
                                Mark as Read
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="mb-8 bg-white rounded-lg shadow-md p-6 text-center text-gray-600">
            <p class="text-lg">No new unread contact messages.</p>
        </div>
        <?php endif; ?>


        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="admin_products.php" class="shopee-gradient text-white p-4 rounded-lg shadow-md flex items-center justify-center text-center font-bold text-lg hover:opacity-90 transition duration-300 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Manage Products
                </a>

                <a href="admin_orders.php" class="shopee-gradient text-white p-4 rounded-lg shadow-md flex items-center justify-center text-center font-bold text-lg hover:opacity-90 transition duration-300 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    View Orders
                </a>

                <a href="admin_stock.php" class="shopee-gradient text-white p-4 rounded-lg shadow-md flex items-center justify-center text-center font-bold text-lg hover:opacity-90 transition duration-300 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10m0 0h5m-5 0h5m-5 0h5M9 7h10m0 0l-3 3m3-3l-3-3M9 7v10m0 0h10"/>
                    </svg>
                    Update Stock
                </a>
            </div>
        </div>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Apply active link styling based on current page
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

            // Hover effect for sidebar items (non-active ones)
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

            // AJAX for marking contact messages as read
            $(document).on('click', '.mark-as-read-btn', function() {
                const button = $(this);
                const messageId = button.data('message-id');
                const messageCard = $('#message-' + messageId);

                $.ajax({
                    url: 'admin.php', // Send to current page
                    type: 'POST',
                    data: {
                        mark_as_read: true,
                        message_id: messageId
                    },
                    dataType: 'json', // Expect JSON response
                    success: function(response) {
                        if (response.success) {
                            messageCard.fadeOut(300, function() {
                                $(this).remove(); // Remove the card after fading out
                                // Update the unread message count in the sidebar
                                const unreadBadge = $('.shopee-badge');
                                let currentCount = parseInt(unreadBadge.text());
                                if (!isNaN(currentCount) && currentCount > 0) {
                                    currentCount--;
                                    if (currentCount === 0) {
                                        unreadBadge.remove(); // Remove badge if no unread messages
                                    } else {
                                        unreadBadge.text(currentCount);
                                    }
                                }
                                // If all messages are read, show the "No new messages" text
                                if ($('.mark-as-read-btn').length === 0) {
                                    $('.mb-8.bg-white.rounded-lg.shadow-md.p-6')
                                        .html('<p class="text-lg text-gray-600 text-center">No new unread contact messages.</p>');
                                }
                            });
                        } else {
                            alert('Error marking message as read: ' + (response.error || 'Unknown error.'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error, xhr.responseText);
                        alert('Could not mark message as read. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>