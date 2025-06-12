<?php
include 'config.php'; // Include your database configuration

// Start session if not already started (important for $_SESSION variables)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // First, get the image_url to delete the file
        $stmt_select_image = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt_select_image->execute([$id]);
        $product_image = $stmt_select_image->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        // If product deleted successfully, attempt to delete the image file
        if ($stmt->rowCount() > 0) {
            if ($product_image && file_exists($product_image)) {
                unlink($product_image); // Delete the actual file
            }
            $_SESSION['message'] = 'Product deleted successfully!';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Product not found or could not be deleted.';
            $_SESSION['msg_type'] = 'danger';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Error deleting product: ' . $e->getMessage();
        $_SESSION['msg_type'] = 'danger';
    }
    header("Location: admin_products.php"); // Redirect after deletion
    exit();
}

// Handle form submission for adding a product (now within this file via modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    $category = trim($_POST['category']);
    $brand = trim($_POST['brand']);

    // Basic validation
    if (empty($name) || empty($description) || $price === false || $stock === false || empty($category)) {
        $_SESSION['message'] = 'Please fill in all required fields and ensure price/stock are valid numbers.';
        $_SESSION['msg_type'] = 'danger';
    } else {
        // Handle image upload (simplified for demonstration)
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            // Ensure the 'uploads' directory exists
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_name = uniqid() . '_' . basename($_FILES['image']['name']); // Unique filename to prevent overwrites
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Allow certain file formats
            $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($imageFileType, $allowed_types)) {
                $_SESSION['message'] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
                $_SESSION['msg_type'] = 'danger';
            } elseif ($_FILES['image']['size'] > $max_file_size) {
                $_SESSION['message'] = 'Sorry, your file is too large (max 5MB).';
                $_SESSION['msg_type'] = 'danger';
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                } else {
                    $_SESSION['message'] = 'Sorry, there was an error uploading your image.';
                    $_SESSION['msg_type'] = 'danger';
                }
            }
        }

        if (!isset($_SESSION['message']) || $_SESSION['msg_type'] !== 'danger') { // Only proceed if no file upload error
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, brand, image_url)
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock, $category, $brand, $image_url]);
                $_SESSION['message'] = 'Product added successfully!';
                $_SESSION['msg_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Database Error: ' . $e->getMessage();
                $_SESSION['msg_type'] = 'danger';
            }
        }
    }
    header("Location: admin_products.php"); // Redirect after adding (or error)
    exit();
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define categories for the dropdown (You can fetch these from a database if you have a categories table)
$categories = ['Electronics', 'Fashion', 'Home & Living', 'Sports & Outdoor', 'Health & Beauty', 'Groceries', 'Books', 'Toys', 'Automotive', 'Collectibles'];

// --- New: Get total count of unread messages for dashboard badge ---
$total_unread_messages = 0;
try {
    $total_unread_stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
    $total_unread_messages = $total_unread_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching total unread messages for badge: " . $e->getMessage());
    // In a real application, you might show a user-friendly error or default to 0
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5; /* Light gray background */
        }
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250); /* Shopee orange gradient */
        }
        .shopee-text-orange {
            color: #ee4d2d; /* Shopee orange text */
        }
        /* Custom scrollbar for sidebar (optional, for aesthetics) */
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

        /* Active link styling for sidebar */
        .sidebar-item.active-link {
            background-color: #fcece8; /* Very light orange for active background */
            color: #ee4d2d; /* Shopee orange text for active link */
            font-weight: 700; /* Bold */
        }
        .sidebar-item.active-link svg,
        .sidebar-item.active-link i { /* Also target <i> for Font Awesome */
            color: #ee4d2d; /* Shopee orange icon for active link */
        }
        .sidebar-item:not(.active-link):hover .shopee-svg-hover,
        .sidebar-item:not(.active-link):hover i { /* Apply shopee-text-orange on hover for icons */
            color: #ee4d2d;
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

        /* Modal specific styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Slightly darker overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Ensure modal is on top of everything */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* More prominent shadow */
            width: 90%;
            max-width: 550px; /* Slightly wider modal */
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            max-height: 90vh; /* Limit height for scrollable content */
            overflow-y: auto; /* Enable scrolling for long forms */
        }
        .modal-overlay.show .modal-content {
            transform: translateY(0);
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.2rem;
            font-size: 2rem; /* Larger close button */
            cursor: pointer;
            color: #999;
            transition: color 0.2s ease;
        }
        .modal-close:hover {
            color: #333;
        }

        /* Alert message styling */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
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
                    <a href="admin_products.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out active-link">
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
            <h1 class="text-2xl font-bold text-gray-800">Manage Products</h1>
            <div class="flex items-center space-x-4">
                <button id="addProductBtn" class="shopee-gradient text-white px-4 py-2 rounded-full shadow hover:opacity-90 transition duration-300 ease-in-out flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Product
                </button>
                <a href="logout.php" class="bg-white text-black px-4 py-2 rounded-full shadow hover:bg-gray-800 transition duration-300 ease-in-out text-sm">
                    Logout
                </a>
            </div>
        </header>

        <?php
        if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> mb-4">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Product List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (₱)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format(htmlspecialchars($product['price']), 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $product['stock'] <= 5 ? 'text-red-500 font-semibold' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($product['stock']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="admin_edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="text-shopee-orange hover:text-orange-700 mr-4">Edit</a>
                                        <a href="admin_products.php?delete=<?php echo htmlspecialchars($product['id']); ?>"
                                            onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');"
                                            class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="addProductModal" class="modal-overlay hidden">
        <div class="modal-content">
            <span class="modal-close" id="closeModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Add New Product</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="add_product" value="1">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name:</label>
                    <input type="text" id="name" name="name" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description:</label>
                    <textarea id="description" name="description" required rows="4"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm"></textarea>
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₱):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity:</label>
                    <input type="number" id="stock" name="stock" min="0" required
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
                    <select id="category" name="category" required
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand:</label>
                    <input type="text" id="brand" name="brand"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Product Image:</label>
                    <input type="file" id="image" name="image" accept="image/*"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-shopee-orange file:text-white hover:file:opacity-90">
                </div>

                <div>
                    <button type="submit"
                             class="w-full shopee-gradient text-white font-bold py-3 px-4 rounded-md shadow-lg hover:opacity-90 transition duration-300 ease-in-out flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Apply active link styling based on the current page
            var currentPath = window.location.pathname.split('/').pop();

            // Find the current page in navigation and highlight it
            $('.sidebar-item').each(function() {
                var linkPath = $(this).attr('href').split('/').pop();
                if (currentPath === linkPath) {
                    $(this).addClass('active-link'); // Use the specific active-link class
                }
            });

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

            // Modal functionality
            const addProductBtn = $('#addProductBtn');
            const addProductModal = $('#addProductModal');
            const closeModal = $('#closeModal');

            addProductBtn.on('click', function() {
                addProductModal.addClass('show'); // Use 'show' class for transition
            });

            closeModal.on('click', function() {
                addProductModal.removeClass('show');
            });

            // Close modal if user clicks outside of it
            $(window).on('click', function(event) {
                if ($(event.target).is(addProductModal)) {
                    addProductModal.removeClass('show');
                }
            });

            // Close modal if escape key is pressed
            $(document).on('keydown', function(event) {
                if (event.key === "Escape" && addProductModal.hasClass('show')) {
                    addProductModal.removeClass('show');
                }
            });
        });
    </script>
</body>
</html>