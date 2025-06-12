<?php
include 'config.php'; // Include database connection and session start

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fetch categories for the navigation (similar to index.php)
$categories = [];
try {
    $stmt_categories = $pdo->query("SELECT DISTINCT c.category FROM products p JOIN (SELECT DISTINCT category FROM products WHERE stock > 0) AS c ON p.category = c.category WHERE p.category IS NOT NULL AND p.category != '' ORDER BY c.category ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching categories for track_orders.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on this page
$search_query = ''; // No search query on this page

$order_details = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_input = trim($_POST['search_input'] ?? '');

    if (!empty($search_input)) {
        try {
            // Attempt to find order by order_id or tracking_number
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       GROUP_CONCAT(CONCAT(pi.product_name, ' (Qty: ', oi.quantity, ' @ Php', FORMAT(oi.price, 2), ')') SEPARATOR '<br>') AS items_list
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN products pi ON oi.product_id = pi.product_id
                WHERE o.order_id = :search_input OR o.tracking_number = :search_input
                GROUP BY o.order_id
                LIMIT 1
            ");
            $stmt->execute(['search_input' => $search_input]);
            $order_details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order_details) {
                $error_message = "Order or tracking number not found. Please double-check your input.";
            }
        } catch (PDOException $e) {
            error_log("Error tracking order: " . $e->getMessage());
            $error_message = "An error occurred while fetching your order. Please try again later.";
        }
    } else {
        $error_message = "Please enter an Order ID or Tracking Number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Shoppies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS from the theme will go here */
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

        .logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
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

        /* Added for header category nav */
        .category-nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 0.5rem 0;
        }

        .category-nav li {
            margin: 0 1rem;
        }

        .category-nav a {
            color: var(--light-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s;
        }

        .category-nav a.active {
            text-decoration: underline;
        }

        .main-content {
            flex-grow: 1;
            padding: 2rem 0;
            margin: 0 auto;
            width: 90%;
            max-width: 1200px;
        }

        .track-order-section { /* Reusing policy-section styling for consistency */
            background-color: var(--light-color);
            padding: 3rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center; /* Center the form elements */
        }

        .track-order-section h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .track-order-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .track-order-form label {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .track-order-form input[type="text"] {
            width: 100%;
            max-width: 400px;
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .track-order-form button {
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .track-order-form button:hover {
            background-color: var(--secondary-color);
        }

        .error-message {
            color: var(--primary-color);
            margin-top: 1rem;
            font-weight: bold;
        }

        .order-results {
            text-align: left;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .order-results h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .order-details p {
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .order-details p strong {
            color: var(--dark-color);
        }

        .order-items {
            margin-top: 1.5rem;
            border-top: 1px dashed #ddd;
            padding-top: 1rem;
        }

        .order-items h3 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .order-items ul {
            list-style: none; /* Removed disc for cleaner look */
            padding-left: 0;
        }

        .order-items ul li {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-items ul li span {
            color: var(--dark-color);
        }

        .order-details .status-tag {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            font-weight: bold;
            color: var(--light-color);
            margin-left: 10px;
        }

        .status-Pending {
            background-color: #ffc107; /* Warning yellow */
        }
        .status-Processing {
            background-color: #17a2b8; /* Info blue */
        }
        .status-Shipped {
            background-color: #007bff; /* Primary blue */
        }
        .status-Delivered {
            background-color: #28a745; /* Success green */
        }
        .status-Cancelled {
            background-color: #dc3545; /* Danger red */
        }
        .status-Returned {
            background-color: #6c757d; /* Secondary gray */
        }

        /* Footer Styles - (Copied from previous files) */
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
            .track-order-section {
                padding: 2rem;
            }
            .track-order-section h1 {
                font-size: 2rem;
            }
            .track-order-form input[type="text"] {
                max-width: 100%;
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
            .logo img {
                height: 30px;
                margin-bottom: 0.5rem;
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
            .category-nav ul {
                flex-wrap: wrap;
                justify-content: flex-start;
            }
            .category-nav li {
                margin: 0.5rem 0.5rem 0.5rem 0;
            }
            .main-content {
                padding: 1rem 0;
            }
            .track-order-section {
                padding: 1.5rem;
            }
            .track-order-section h1 {
                font-size: 1.8rem;
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
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fe/Shopee.svg" alt="Shoppies Logo">
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
                <ul>
                    <li>
                        <a href="index.php" class="category-link">
                            All Products
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="index.php?category=<?php echo urlencode($cat); ?>"
                               class="category-link">
                                <?php echo htmlspecialchars($cat); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-content">
        <div class="container">
            <section class="track-order-section">
                <h1>Track Your Order</h1>
                <p>Enter your Order ID or Tracking Number below to check the status of your order.</p>

                <form class="track-order-form" method="POST" action="track_orders.php">
                    <label for="search_input">Order ID or Tracking Number:</label>
                    <input type="text" id="search_input" name="search_input" placeholder="e.g., ORD-12345 or TRK-67890" required value="<?php echo htmlspecialchars($_POST['search_input'] ?? ''); ?>">
                    <button type="submit">Track Order</button>
                    <?php if (!empty($error_message)): ?>
                        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                    <?php endif; ?>
                </form>

                <?php if ($order_details): ?>
                    <div class="order-results">
                        <h2>Order Status: <span class="status-tag status-<?php echo htmlspecialchars($order_details['order_status']); ?>"><?php echo htmlspecialchars($order_details['order_status']); ?></span></h2>
                        <div class="order-details">
                            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['order_id']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo date("F j, Y, h:i A", strtotime($order_details['order_date'])); ?></p>
                            <p><strong>Total Amount:</strong> Php <?php echo htmlspecialchars(number_format($order_details['total_amount'], 2)); ?></p>
                            <p><strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                            <?php if (!empty($order_details['tracking_number'])): ?>
                                <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order_details['tracking_number']); ?> 
                                </p>
                            <?php else: ?>
                                <p><strong>Tracking Number:</strong> Not yet available (<?php echo htmlspecialchars($order_details['order_status']); ?>)</p>
                            <?php endif; ?>
                        </div>
                        <div class="order-items">
                            <h3>Items in this Order:</h3>
                            <ul>
                                <?php
                                // Split the concatenated items_list and format them
                                $items = explode('<br>', $order_details['items_list']);
                                foreach ($items as $item) {
                                    echo '<li><span>' . $item . '</span></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>We are an online marketplace committed to providing quality products at affordable prices with excellent customer service.</p>
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
                    <li><a href="track_orders.php">Track Your Order</a></li>
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
                    <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Shoppies. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>