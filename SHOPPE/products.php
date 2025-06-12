<?php
include 'config.php'; // Include database connection and session start

// Start the session if it hasn't been started yet (config.php should do this, but good to be explicit)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get search query and category filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build the base query
$sql = "SELECT id, name, price, image_url, description, stock, category FROM products WHERE 1=1";
$params = [];

// Add search condition
if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

// Add category condition
if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

// Order by name
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all distinct categories for the filter dropdown
// Ensure 'category' column is not NULL or empty string
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Shopee Clone</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5; /* Light gray background */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            max-width: 1200px; /* Equivalent to mx-auto */
            margin-left: auto;
            margin-right: auto;
            padding-left: 1.5rem; /* Equivalent to p-6 */
            padding-right: 1.5rem; /* Equivalent to p-6 */
        }

        .shopee-orange {
            background-color: #ee4d2d; /* Shopee's primary orange */
        }

        .shopee-text-orange {
            color: #ee4d2d; /* Shopee orange text */
        }

        .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .rounded-lg {
            border-radius: 0.5rem; /* Equivalent to rounded-lg */
        }

        /* Header */
        header {
            background-color: #ee4d2d;
            color: white;
            padding: 1rem; /* Equivalent to p-4 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 1.5rem; /* Equivalent to text-2xl */
            font-weight: 700; /* Equivalent to font-bold */
        }

        header h1 a {
            color: white;
            text-decoration: none;
        }

        header h1 a:hover {
            color: #e2e8f0; /* Equivalent to hover:text-gray-200 */
        }

        header nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1rem; /* Equivalent to space-x-4 */
        }

        header nav ul li a {
            color: white;
            text-decoration: none;
        }

        header nav ul li a:hover {
            color: #e2e8f0; /* Equivalent to hover:text-gray-200 */
        }

        header nav ul li.font-bold {
            font-weight: 700;
        }

        /* Main Content */
        main {
            flex-grow: 1;
            padding: 1.5rem; /* Equivalent to p-6 */
        }

        main h1 {
            font-size: 2.25rem; /* Equivalent to text-4xl */
            font-weight: 700; /* Equivalent to font-bold */
            color: #374151; /* Equivalent to text-gray-800 */
            margin-bottom: 2rem; /* Equivalent to mb-8 */
            text-align: center;
        }

        /* Filter and Search Section */
        .filter-section {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem; /* Equivalent to p-6 */
            margin-bottom: 2rem; /* Equivalent to mb-8 */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 1rem; /* Equivalent to space-y-4 md:space-y-0 md:space-x-4 */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .filter-section {
                flex-direction: row;
                gap: 1rem;
            }
        }

        .filter-form {
            width: 100%; /* Equivalent to w-full */
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem; /* Equivalent to space-y-4 md:space-y-0 md:space-x-4 */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .filter-form {
                width: auto; /* Equivalent to md:w-auto */
                flex-direction: row;
            }
        }

        .filter-form input[type="text"] {
            width: 100%; /* Equivalent to w-full */
            border: 1px solid #d1d5db; /* Equivalent to border border-gray-300 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Equivalent to shadow-sm */
            padding: 0.5rem 1rem; /* Equivalent to py-2 px-4 */
            outline: none;
            color: #374151; /* Equivalent to text-gray-700 */
        }

        .filter-form input[type="text"]:focus {
            outline: none;
            border-color: #ee4d2d; /* Equivalent to focus:border-transparent with ring */
            box-shadow: 0 0 0 2px rgba(238, 77, 45, 0.5); /* Equivalent to focus:ring-2 focus:ring-shopee-orange */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .filter-form input[type="text"] {
                width: 16rem; /* Equivalent to md:w-64 */
            }
        }

        .filter-form select {
            display: block;
            appearance: none;
            width: 100%; /* Equivalent to w-full */
            background-color: white;
            border: 1px solid #d1d5db; /* Equivalent to border border-gray-300 */
            color: #374151; /* Equivalent to text-gray-700 */
            padding: 0.5rem 1rem; /* Equivalent to py-2 px-4 */
            padding-right: 2rem; /* Equivalent to pr-8 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Equivalent to shadow-sm */
            line-height: 1.25; /* Equivalent to leading-tight */
            outline: none;
        }

        .filter-form select:focus {
            background-color: white; /* Equivalent to focus:bg-white */
            border-color: #ee4d2d; /* Equivalent to focus:border-shopee-orange */
            box-shadow: 0 0 0 2px rgba(238, 77, 45, 0.5); /* Equivalent to focus:ring-2 focus:ring-shopee-orange */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .filter-form select {
                width: 12rem; /* Equivalent to md:w-48 */
            }
        }

        .filter-form button {
            width: 100%; /* Equivalent to w-full */
            background-color: #ee4d2d;
            color: white;
            font-weight: 600; /* Equivalent to font-semibold */
            padding: 0.5rem 1.5rem; /* Equivalent to py-2 px-6 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); /* Equivalent to shadow */
            transition-property: background-color;
            transition-duration: 300ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* Equivalent to transition duration-300 ease-in-out */
            border: none;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color: #e64a2b; /* Equivalent to hover:bg-orange-600 */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .filter-form button {
                width: auto; /* Equivalent to md:w-auto */
            }
        }

        .clear-filters-link {
            width: 100%; /* Equivalent to w-full */
            text-align: center; /* Equivalent to text-center */
            color: #ee4d2d;
            transition-property: color;
            transition-duration: 300ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500; /* Equivalent to font-medium */
            text-decoration: none;
            margin-top: 1rem; /* Equivalent to mt-4 */
        }

        .clear-filters-link:hover {
            color: #cc3c21; /* Equivalent to hover:text-orange-700 */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .clear-filters-link {
                width: auto; /* Equivalent to md:w-auto */
                text-align: right; /* Equivalent to md:text-right */
                margin-top: 0; /* Equivalent to md:mt-0 */
            }
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr)); /* Equivalent to grid-cols-1 */
            gap: 1.5rem; /* Equivalent to gap-6 */
        }

        @media (min-width: 640px) { /* sm breakpoint */
            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)); /* Equivalent to sm:grid-cols-2 */
            }
        }

        @media (min-width: 768px) { /* md breakpoint */
            .product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)); /* Equivalent to md:grid-cols-3 */
            }
        }

        @media (min-width: 1024px) { /* lg breakpoint */
            .product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)); /* Equivalent to lg:grid-cols-4 */
            }
        }

        /* Product Card */
        .product-card {
            background-color: white;
            border-radius: 0.5rem; /* Equivalent to rounded-lg */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Equivalent to shadow-md */
            overflow: hidden;
            transition-property: transform, box-shadow;
            transition-duration: 300ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* Equivalent to transition duration-300 ease-in-out */
            display: flex;
            flex-direction: column;
        }

        .product-card-hover:hover {
            transform: translateY(-5px); /* Lift effect */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Stronger shadow */
        }

        .product-card a.image-link {
            display: block;
            height: 12rem; /* Equivalent to h-48 */
            width: 100%; /* Equivalent to w-full */
            overflow: hidden;
        }

        .product-card a.image-link img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card .content {
            padding: 1rem; /* Equivalent to p-4 */
            display: flex;
            flex-grow: 1;
            flex-direction: column;
        }

        .product-card .content h3 {
            font-size: 1.125rem; /* Equivalent to text-lg */
            font-weight: 600; /* Equivalent to font-semibold */
            color: #374151; /* Equivalent to text-gray-800 */
            margin-bottom: 0.5rem; /* Equivalent to mb-2 */
            flex-grow: 1;
        }

        .product-card .content h3 a {
            color: #374151;
            text-decoration: none;
            transition-property: color;
            transition-duration: 200ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* Equivalent to transition duration-200 ease-in-out */
        }

        .product-card .content h3 a:hover {
            color: #ee4d2d; /* Equivalent to hover:text-shopee-orange */
        }

        .product-card .content p {
            font-size: 0.875rem; /* Equivalent to text-sm */
            color: #4b5563; /* Equivalent to text-gray-600 */
            margin-bottom: 0.25rem; /* Equivalent to mb-1 */
        }

        .product-card .content p.stock-info {
            margin-bottom: 0.75rem; /* Equivalent to mb-3 */
        }

        .product-card .content p span {
            font-weight: 500; /* Equivalent to font-medium */
        }

        .product-card .content p span.out-of-stock {
            color: #dc2626; /* Equivalent to text-red-600 */
        }

        .product-card .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .product-card .actions .price {
            color: #ee4d2d;
            font-weight: 700; /* Equivalent to font-bold */
            font-size: 1.25rem; /* Equivalent to text-xl */
        }

        .product-card .actions .add-to-cart-btn {
            background-color: #ee4d2d;
            color: white;
            font-weight: 500; /* Equivalent to font-medium */
            padding: 0.5rem 1rem; /* Equivalent to py-2 px-4 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); /* Equivalent to shadow */
            transition-property: background-color;
            transition-duration: 300ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* Equivalent to transition duration-300 ease-in-out */
            text-decoration: none;
            font-size: 0.875rem; /* Equivalent to text-sm */
        }

        .product-card .actions .add-to-cart-btn:hover {
            background-color: #e64a2b; /* Equivalent to hover:bg-orange-600 */
        }

        .product-card .actions .out-of-stock-label {
            background-color: #9ca3af; /* Equivalent to bg-gray-400 */
            color: white;
            font-weight: 500; /* Equivalent to font-medium */
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: not-allowed;
        }

        /* No Products Found */
        .no-products-message {
            text-align: center;
            padding-top: 2.5rem; /* Equivalent to py-10 */
            padding-bottom: 2.5rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .no-products-message p {
            color: #374151; /* Equivalent to text-gray-700 */
            font-size: 1.125rem; /* Equivalent to text-lg */
        }

        .no-products-message p.sub-text {
            color: #6b7280; /* Equivalent to text-gray-500 */
            margin-top: 0.5rem; /* Equivalent to mt-2 */
        }

        .no-products-message a {
            display: inline-block;
            margin-top: 1rem; /* Equivalent to mt-4 */
            color: #ee4d2d;
            text-decoration: none;
            font-weight: 500; /* Equivalent to font-medium */
        }

        .no-products-message a:hover {
            color: #cc3c21; /* Equivalent to hover:text-orange-700 */
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Shopee Clone</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li class="font-bold"><a href="products.php">Products</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="cart.php">Cart (<?php echo count($_SESSION['cart'] ?? []); ?>)</a></li>
                        <li><a href="profile.php">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin.php" class="font-bold">Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Our Products</h1>
        
        <div class="filter-section">
            <form method="GET" action="products.php" class="filter-form">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                
                <select name="category" class="category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit">
                    Apply Filter
                </button>
            </form>
            <?php if (!empty($search_query) || !empty($category_filter)): ?>
                <a href="products.php" class="clear-filters-link">Clear Filters</a>
            <?php endif; ?>
        </div>
        
        <?php if (count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card product-card-hover">
                        <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="image-link">
                            <?php 
                                $image_src = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/300x300?text=No+Image';
                            ?>
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <div class="content">
                            <h3>
                                <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <p>Category: <span><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></span></p>
                            <p class="stock-info">Stock: 
                                <span class="<?php echo ($product['stock'] == 0) ? 'out-of-stock' : ''; ?>">
                                    <?php echo htmlspecialchars($product['stock']); ?>
                                </span>
                            </p>
                            <div class="actions">
                                <span class="shopee-text-orange price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add_to_cart.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="add-to-cart-btn">
                                        Add to Cart
                                    </a>
                                <?php else: ?>
                                    <span class="out-of-stock-label">
                                        Out of Stock
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products-message">
                <p>No products found matching your criteria.</p>
                <?php if (!empty($search_query) || !empty($category_filter)): ?>
                    <p class="sub-text">Try clearing your filters or searching for something else.</p>
                    <a href="products.php">View All Products</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>