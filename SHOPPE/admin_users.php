<?php
// Load database connection settings
include 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow access if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    // Prevent admin from deleting their own account
    if ($_GET['delete'] == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own admin account.";
    } else {
        try {
            // Delete user from database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $_SESSION['success_message'] = "User deleted successfully.";
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
        }
    }
    header("Location: admin_users.php");
    exit();
}

// Handle user role change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    // Prevent admin from changing their own role to non-admin
    if ($target_user_id == $_SESSION['user_id'] && $new_role != 'admin') {
        $_SESSION['error_message'] = "You cannot change your own role from admin.";
    } else {
        try {
            // Update user role in database
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_user_id]);
            $_SESSION['success_message'] = "User role updated successfully.";
        } catch (PDOException $e) {
            error_log("Error updating user role: " . $e->getMessage());
            $_SESSION['error_message'] = "Error updating user role: " . $e->getMessage();
        }
    }
    header("Location: admin_users.php");
    exit();
}

// Get all users from database
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = []; // Ensure $users is an empty array if there's an error
    $_SESSION['error_message'] = "Error fetching users: " . $e->getMessage();
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
    <title>Manage Users - Admin Panel (Shopee Theme)</title>
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
                    <a href="admin_users.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out active-link">
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
            <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
            <a href="logout.php" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md shadow-sm hover:bg-gray-100 transition duration-300 ease-in-out">
                Logout
            </a>
        </header>

        <?php
        // Show success message if set
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">' . htmlspecialchars($_SESSION['success_message']) . '</span>
                  </div>';
            unset($_SESSION['success_message']);
        }
        // Show error message if set
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">' . htmlspecialchars($_SESSION['error_message']) . '</span>
                  </div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">User List</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <select name="role" onchange="this.form.submit()"
                                                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm
                                                    <?php echo ($user['role'] == 'admin') ? 'bg-orange-100 text-shopee-orange font-semibold' : 'text-gray-700'; ?>">
                                                <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="admin_users.php?delete=<?php echo htmlspecialchars($user['id']); ?>"
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                               class="text-red-600 hover:text-red-900">Delete</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Run after the page is fully loaded
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