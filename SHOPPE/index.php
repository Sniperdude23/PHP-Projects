<?php
include 'config.php'; // Include database connection and session start

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
// Handle category filter
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

$products = [];
$categories = []; // To store all distinct categories

// Fetch all distinct categories from the database
try {
    // Only fetch categories that have at least one in-stock product
    $stmt_categories = $pdo->query("SELECT DISTINCT c.category FROM products p JOIN (SELECT DISTINCT category FROM products WHERE stock > 0) AS c ON p.category = c.category WHERE p.category IS NOT NULL AND p.category != '' ORDER BY c.category ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Start with the base query to only include products with stock > 0
$sql = "SELECT id, name, price, stock, image_url, category FROM products WHERE stock > 0";
$params = [];
$where_clauses = []; // These will be added as AND clauses to the initial WHERE stock > 0

if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

if (!empty($selected_category)) {
    $where_clauses[] = "category = ?";
    $params[] = $selected_category;
}

if (count($where_clauses) > 0) {
    $sql .= " AND " . implode(" AND ", $where_clauses); // Use AND to combine with WHERE stock > 0
}

// Always order by name by default
$sql .= " ORDER BY name ASC";

// Remove the LIMIT clause to show all products
// $sql .= " LIMIT 5"; // Commented out to show all products

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = []; // Ensure $products is an empty array on error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoppies</title>
    <style>
        :root {
            --primary-color: #ee4d2d;
            --secondary-color: #ff7337;
            --light-color: #fff;
            --dark-color: #333;
            --text-color: #555;
            --cloud-white: #f8f8f8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: var(--cloud-white);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }

        .user-actions a {
            color: var(--light-color);
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.3rem 0.6rem;
            border-radius: 3px;
            transition: background-color 0.3s;
        }

        .user-actions a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .search-bar {
            margin: 1rem auto;
            display: flex;
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .search-bar input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-right: none;
            outline: none;
            font-size: 1rem;
        }

        .search-bar button {
            background-color: var(--secondary-color);
            color: var(--light-color);
            border: none;
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background-color: var(--primary-color);
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            gap: 1.5rem;
            margin: 1rem auto;
            width: 90%;
            max-width: 1200px;
        }

        .sidebar {
            flex: 0 0 220px;
            background-color: var(--light-color);
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            align-self: flex-start;
        }

        .sidebar-title {
            color: var(--dark-color);
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .category-list ul {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 0.7rem;
        }

        .category-list a {
            text-decoration: none;
            color: var(--text-color);
            font-size: 1.1rem;
            transition: color 0.2s, font-weight 0.2s;
            display: block;
            padding: 0.3rem 0;
        }

        .category-list a:hover {
            color: var(--primary-color);
            font-weight: bold;
        }

        .category-list a.active {
            color: var(--primary-color);
            font-weight: bold;
            text-decoration: underline;
        }

        .product-section {
            flex-grow: 1;
            background-color: transparent;
            padding: 2rem;
            border-radius: 3px;
            box-shadow: none;
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
            font-size: 1.8rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            border: 1px solid #eee;
            border-radius: 1px;
            padding: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: var(--light-color);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
            margin-bottom: 0.75rem;
            border-radius: 3px;
        }

        .product-name {
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-weight: bold;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-name a {
            text-decoration: none;
            color: inherit;
        }

        .product-name a:hover {
            color: var(--primary-color);
        }

        .product-price {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .product-stock {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .product-stock.out-of-stock {
            color: #d9534f;
            font-weight: bold;
        }

        .add-to-cart {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--light-color);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 3px;
            transition: background-color 0.3s;
            text-align: center;
            margin-top: auto;
            border: none;
            cursor: pointer;
        }

        .add-to-cart:hover {
            background-color: var(--secondary-color);
        }

        .out-of-stock-btn {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
            padding: 0.6rem 1.2rem;
            border-radius: 3px;
            text-align: center;
            margin-top: auto;
        }

        .no-products-message {
            text-align: center;
            padding: 3rem;
            background-color: var(--light-color);
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }

        .no-products-message p {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .no-products-message a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .no-products-message a:hover {
            text-decoration: underline;
        }

        /* Footer Styles */
        footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 2rem 0;
            margin-top: 2rem;
        }

        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 1.5rem;
            padding: 0 1rem;
        }

        .footer-section h3 {
            color: var(--light-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: var(--primary-color);
        }

        .contact-info {
            color: #bbb;
            line-height: 1.8;
        }

        .contact-info a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
        }

        .contact-info a:hover {
            color: var(--primary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: var(--light-color);
            background-color: #444;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .social-links a:hover {
            background-color: var(--primary-color);
        }

        .footer-bottom {
            background-color: #222;
            text-align: center;
            padding: 1rem 0;
            font-size: 0.9rem;
            color: #bbb;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
                gap: 1rem;
            }
            .sidebar {
                flex: none;
                width: 100%;
            }
            .product-section {
                padding: 1.5rem;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
            .footer-section {
                min-width: 150px;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .logo {
                margin-bottom: 0.5rem;
            }
            .logo img {
                height: 30px;
            }
            .logo-text {
                font-size: 1.2rem;
            }
            .user-actions {
                width: 100%;
                display: flex;
                justify-content: space-around;
                margin-top: 0.5rem;
            }
            .user-actions a {
                margin-left: 0;
                margin-right: 0.5rem;
            }
            .search-bar {
                margin: 0.5rem auto;
            }
            .main-content {
                padding: 1rem 0;
            }
            .product-section {
                padding: 1rem;
            }
            .section-title {
                font-size: 1.5rem;
            }
            .product-name {
                font-size: 1rem;
            }
            .product-price {
                font-size: 1.1rem;
            }
            .footer-container {
                flex-direction: column;
            }
            .footer-section {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fe/Shopee.svg" alt="Shopee Logo">
                    <span class="logo-text">Shoppies</span>
                </div>
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                        <a href="cart.php">Cart</a>
                        <a href="orders.php">Orders</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <a href="admin.php">Admin</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="category-nav">
                <ul style="list-style: none; display: flex; justify-content: center; padding: 0.5rem 0;">
                    <li style="margin: 0 1rem;">
                        <a href="index.php" class="category-link <?php echo (empty($selected_category) && empty($search_query)) ? 'active' : ''; ?>" style="color: var(--light-color); text-decoration: none; font-weight: bold; transition: color 0.2s;">
                            All Products
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li style="margin: 0 1rem;">
                            <a href="index.php?category=<?php echo urlencode($cat); ?>"
                               class="category-link <?php echo ($selected_category === $cat) ? 'active' : ''; ?>"
                               style="color: var(--light-color); text-decoration: none; transition: color 0.2s;">
                                <?php echo htmlspecialchars($cat); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <form method="GET" action="index.php" class="search-bar">
            <input type="text" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_query); ?>">
            <?php if (!empty($selected_category)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
            <?php endif; ?>
            <button type="submit">Search</button>
        </form>

        <div class="main-content">
            <aside class="sidebar">
                <h3 class="sidebar-title">Categories</h3>
                <div class="category-list">
                    <ul>
                        <li>
                            <a href="index.php" class="<?php echo (empty($selected_category) && empty($search_query)) ? 'active' : ''; ?>">All Products</a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?category=<?php echo urlencode($cat); ?>"
                                   class="<?php echo ($selected_category === $cat) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <div class="product-section">
                <h2 class="section-title">
                    <?php
                    if (!empty($search_query)) {
                        echo 'Search Results for "' . htmlspecialchars($search_query) . '"';
                        if (!empty($selected_category)) {
                            echo ' in ' . htmlspecialchars($selected_category);
                        }
                    } elseif (!empty($selected_category)) {
                        echo htmlspecialchars($selected_category) . ' Products';
                    } else {
                        echo 'All Available Products';
                    }
                    ?>
                </h2>
                <div class="products-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php
                                    $image_src = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/200x150?text=No+Image';
                                ?>
                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <h3 class="product-name">
                                    <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-stock">Stock:
                                    <span class="<?php echo ($product['stock'] == 0) ? 'out-of-stock' : ''; ?>">
                                        <?php echo htmlspecialchars($product['stock']); ?>
                                    </span>
                                </p>
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add_to_cart.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="add-to-cart">Add to Cart</a>
                                <?php else: ?>
                                    <span class="out-of-stock-btn">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products-message">
                            <p>No products found matching your criteria.</p>
                            <?php if (!empty($search_query) || !empty($selected_category)): ?>
                                <a href="index.php">View All Available Products</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Welcome to Shoppies, your premier online marketplace for quality products at unbeatable prices!</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="faq.php">FAQs</a></li>
                    <li><a href="shipping.php">Shipping Policy</a></li>
                    <li><a href="returns.php">Returns & Refunds</a></li>
                    <li><a href="track_order.php">Track Your Order</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact Info</h3>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> gelongo.gelmae@gmail.com</p>
                    <p><i class="fas fa-phone"></i> 09122196241</p>
                    <p><i class="fas fa-map-marker-alt"></i> Binalbagan Negros Occidental </p>
                </div>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Shoppies. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>