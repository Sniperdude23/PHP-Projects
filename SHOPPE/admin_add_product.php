<?php
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];

    // Handle image upload (simplified)
    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        // Ensure the 'uploads' directory exists
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES['image']['name']);
        // Move uploaded file and set image_url
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        } else {
            // Optional: Add error handling for file upload failure
            // echo "Sorry, there was an error uploading your file.";
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, brand, image_url)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $stock, $category, $brand, $image_url]);

    header("Location: admin_products.php"); // Redirect back to product list after adding
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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
                    <a href="admin_orders.php" class="sidebar-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition duration-200 ease-in-out">
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
            <p class="text-sm text-gray-500">Welcome, <strong class="font-semibold text-gray-700"><?php echo $_SESSION['username']; ?></strong></p>
            <a href="logout.php" class="mt-3 inline-block bg-shopee-orange text-white px-4 py-2 rounded-full shadow hover:opacity-90 transition duration-300 ease-in-out text-sm">Logout</a>
        </div>
    </aside>

    <main class="flex-grow p-6">
        <header class="bg-white rounded-lg shadow-md p-4 mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Add New Product</h1>
            <a href="admin_products.php" class="text-gray-600 hover:text-shopee-orange flex items-center transition duration-200 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Products
            </a>
        </header>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
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
                    <input type="text" id="category" name="category"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-shopee-orange focus:border-shopee-orange sm:text-sm">
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
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Apply active link styling based on the current page
            var currentPath = window.location.pathname.split('/').pop();
            $('.sidebar-item').each(function() {
                var linkPath = $(this).attr('href').split('/').pop();
                // Check if currentPath matches the link's href, or if it's admin_add_product.php and the Manage Products link is being styled
                if (currentPath === linkPath || (currentPath === 'admin_add_product.php' && linkPath === 'admin_products.php')) {
                    $(this).addClass('bg-gray-100 text-shopee-orange font-semibold');
                    $(this).find('svg').addClass('shopee-text-orange');
                }
            });

            // Add a subtle hover effect to sidebar items
            $('.sidebar-item').hover(
                function() {
                    $(this).find('span').addClass('shopee-text-orange');
                    $(this).find('svg').addClass('shopee-text-orange');
                },
                function() {
                    // Only remove hover class if it's not the active link
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