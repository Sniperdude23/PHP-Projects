<?php
include 'config.php'; // Include database connection and session start

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
// Redirect to index.php if not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// --- Message Management Logic ---

// Mark as Read/Unread
if (isset($_GET['action']) && ($_GET['action'] == 'mark_read' || $_GET['action'] == 'mark_unread') && isset($_GET['id'])) {
    $message_id = $_GET['id'];
    $is_read = ($_GET['action'] == 'mark_read') ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
        $stmt->execute([$is_read, $message_id]);
        $_SESSION['message'] = 'Message status updated successfully!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error updating message status: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        error_log("Error updating contact message status: " . $e->getMessage());
    }
    header("Location: admin_contact_messages.php");
    exit();
}

// Delete Message
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $message_id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $_SESSION['message'] = 'Message deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error deleting message: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        error_log("Error deleting contact message: " . $e->getMessage());
    }
    header("Location: admin_contact_messages.php");
    exit();
}

// --- Pagination Logic ---
$limit = 10; // Number of messages per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of messages for pagination
$total_messages_stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
$total_messages = $total_messages_stmt->fetchColumn();
$total_pages = ceil($total_messages / $limit);

// Fetch messages with pagination
$messages = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, email, subject, message, created_at, is_read FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contact messages for admin: " . $e->getMessage());
    $messages = []; // Ensure messages is an empty array on error
    $_SESSION['message'] = 'Could not load messages: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

// Flash message display
$flash_message = $_SESSION['message'] ?? '';
$flash_message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']); // Clear message after display
unset($_SESSION['message_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .sidebar-item.active-link svg, .sidebar-item.active-link i {
            color: #ee4d2d; /* Shopee orange icon for active link */
        }
        .sidebar-item:not(.active-link):hover .shopee-svg-hover {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for icons */
        }
        .sidebar-item:not(.active-link):hover span {
            color: #ee4d2d; /* Apply shopee-text-orange on hover for text */
        }
        /* Flash message styling */
        .flash-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .flash-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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
                    <a href="admin_contact_messages.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out active-link">
                        <i class="fas fa-envelope h-6 w-6 mr-3 shopee-svg-hover"></i>
                        <span class="text-lg font-medium">Contact Messages</span>
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
            <h1 class="text-2xl font-bold text-gray-800">Manage Contact Messages</h1>
            <a href="logout.php" class="bg-white text-black border border-gray-300 px-4 py-2 rounded-md shadow-sm hover:bg-gray-100 transition duration-300 ease-in-out">
                Logout
            </a>
        </header>

        <?php if ($flash_message): ?>
            <div class="mb-4 p-3 rounded-md border <?php echo ($flash_message_type == 'success') ? 'flash-success' : 'flash-error'; ?>">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 overflow-x-auto">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">All Messages (<?php echo $total_messages; ?>)</h2>
            <?php if (empty($messages)): ?>
                <p class="text-gray-600 text-center py-8">No contact messages found.</p>
            <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sender
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Subject
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Message
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($messages as $msg): ?>
                    <tr class="<?php echo $msg['is_read'] ? 'text-gray-600' : 'font-semibold text-gray-800 bg-yellow-50'; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($msg['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="block"><?php echo htmlspecialchars($msg['name']); ?></span>
                            <span class="block text-gray-500 text-xs"><?php echo htmlspecialchars($msg['email']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($msg['subject']); ?></td>
                        <td class="px-6 py-4 text-sm max-w-sm overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($msg['message']); ?>">
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('M d, Y H:i A', strtotime($msg['created_at'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $msg['is_read'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($msg['is_read']): ?>
                                <a href="?action=mark_unread&id=<?php echo $msg['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Mark Unread</a>
                            <?php else: ?>
                                <a href="?action=mark_read&id=<?php echo $msg['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Mark Read</a>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="text-red-600 hover:text-red-900 delete-btn"
                                data-id="<?php echo $msg['id']; ?>"
                                data-name="<?php echo htmlspecialchars($msg['name']); ?>"
                                data-subject="<?php echo htmlspecialchars($msg['subject']); ?>"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <nav class="mt-6 flex justify-center">
                <ul class="flex list-none">
                    <?php if ($page > 1): ?>
                        <li><a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-l-md">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li>
                            <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo ($i == $page) ? 'shopee-gradient text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li><a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-r-md">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Confirm Deletion</h3>
            <p class="mb-6 text-gray-700">Are you sure you want to delete the message from <strong id="modalSenderName"></strong> (<span id="modalSubject"></span>)? This action cannot be undone.</p>
            <div class="flex justify-center gap-4">
                <button id="cancelDelete" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition duration-200">Cancel</button>
                <a href="#" id="confirmDelete" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-200">Delete</a>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar active link highlighting
            $('.sidebar-item').each(function() {
                if (this.href.includes('admin_contact_messages.php')) {
                    $(this).addClass('active-link');
                } else {
                    $(this).removeClass('active-link');
                }
            });

            // Handle delete modal
            const deleteModal = $('#deleteModal');
            const closeButton = $('.close-button');
            const cancelButton = $('#cancelDelete');
            const confirmDeleteButton = $('#confirmDelete');
            const modalSenderName = $('#modalSenderName');
            const modalSubject = $('#modalSubject');

            $('.delete-btn').on('click', function() {
                const messageId = $(this).data('id');
                const senderName = $(this).data('name');
                const subject = $(this).data('subject');

                modalSenderName.text(senderName);
                modalSubject.text(subject);
                confirmDeleteButton.attr('href', 'admin_contact_messages.php?action=delete&id=' + messageId);
                deleteModal.css('display', 'flex'); // Use flex to center
            });

            closeButton.on('click', function() {
                deleteModal.css('display', 'none');
            });

            cancelButton.on('click', function() {
                deleteModal.css('display', 'none');
            });

            // Close modal if user clicks outside of it
            $(window).on('click', function(event) {
                if ($(event.target).is(deleteModal)) {
                    deleteModal.css('display', 'none');
                }
            });
        });
    </script>
</body>
</html>