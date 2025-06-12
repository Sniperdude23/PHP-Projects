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
    error_log("Error fetching categories for shipping.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on shipping page
$search_query = ''; // No search query on shipping page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Policy - Shoppies</title>
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

        .policy-section { /* Reusing the same styling as privacy.php for consistency */
            background-color: var(--light-color);
            padding: 3rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .policy-section h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .policy-section h2 {
            color: var(--dark-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .policy-section h3 {
            color: var(--dark-color);
            margin-top: 1.5rem;
            margin-bottom: 0.8rem;
            font-size: 1.4rem;
        }

        .policy-section p {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .policy-section ul {
            list-style: disc;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .policy-section ol {
            list-style: decimal;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .policy-section ul li,
        .policy-section ol li {
            margin-bottom: 0.5rem;
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
            .policy-section {
                padding: 2rem;
            }
            .policy-section h1 {
                font-size: 2rem;
            }
            .policy-section h2 {
                font-size: 1.5rem;
            }
            .policy-section p {
                font-size: 1rem;
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
            .policy-section {
                padding: 1.5rem;
            }
            .policy-section h1 {
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
            <section class="policy-section">
                <h1>Shipping Policy - Shoppies</h1>
                <p><strong>Last Updated: <?php echo date("F j, Y"); ?></strong></p>

                <p>Thank you for shopping at Shoppies! We are committed to providing you with the best shopping experience, including fast and reliable shipping for your orders. Please read our shipping policy carefully to understand how we process and ship your purchases.</p>

                <h2>1. Order Processing Time</h2>
                <p>All orders are processed within <strong>[e.g., 1-2 business days]</strong> (excluding weekends and holidays) after receiving your order confirmation email. You will receive another notification when your order has shipped.</p>
                <ul>
                    <li>Orders placed before [e.g., 2:00 PM PST] on a business day will typically be processed on the same day.</li>
                    <li>Orders placed after [e.g., 2:00 PM PST] or on weekends/holidays will be processed on the next business day.</li>
                    <li>During peak seasons (e.g., holiday sales, special promotions), processing times may be extended. We appreciate your patience during these periods.</li>
                </ul>

                <h2>2. Shipping Methods and Costs</h2>
                <p>We partner with reliable courier services to ensure your items arrive safely and on time. Shipping costs are calculated based on the weight, dimensions, and destination of your order, and will be displayed at checkout.</p>
                <h3>Domestic Shipping (within the Philippines):</h3>
                <ul>
                    <li><strong>Standard Shipping:</strong> [e.g., Flat rate of PHP 100 for Metro Manila, PHP 180 for Provincial areas. Or, based on weight/volume.]</li>
                    <li><strong>Free Shipping:</strong> We may offer free standard shipping on orders over [e.g., PHP 1,500]. This will be automatically applied at checkout.</li>
                    <li><strong>Cash on Delivery (COD):</strong> Available in eligible areas. There may be a small additional COD fee applied by the courier.</li>
                </ul>
                <p><strong>International Shipping:</strong><br>
                Currently, Shoppies only ships within the Philippines. We do not offer international shipping at this time. Please subscribe to our newsletter for updates if this changes in the future.</p>

                <h2>3. Estimated Delivery Times</h2>
                <p>Once your order has been processed and shipped, estimated delivery times are as follows:</p>
                <ul>
                    <li><strong>Metro Manila:</strong> 2-5 business days</li>
                    <li><strong>Luzon (Outside Metro Manila):</strong> 3-7 business days</li>
                    <li><strong>Visayas & Mindanao:</strong> 5-10 business days</li>
                </ul>
                <p>Please note that these are estimated delivery times and can vary due to factors beyond our control, such as courier delays, weather conditions, or unforeseen circumstances.</p>

                <h2>4. Order Tracking</h2>
                <p>Once your order is shipped, you will receive an email notification from us that includes a tracking number and a link to track your order. You can typically expect this email within 24-48 hours after your order has been processed.</p>
                <p>You can also track your order directly on our website by visiting our <a href="track_order.php">Track Your Order</a> page and entering your tracking number.</p>

                <h2>5. Shipping Delays & Issues</h2>
                <ul>
                    <li><strong>Incorrect Address:</strong> It is the customer's responsibility to provide an accurate and complete shipping address. Orders shipped to an incorrect address provided by the customer are not eligible for refunds or free re-shipment. Additional shipping charges may apply for re-delivery attempts or returns due to incorrect addresses.</li>
                    <li><strong>Unforeseen Delays:</strong> While we strive for timely deliveries, delays can occasionally occur due to courier operational issues, extreme weather, natural disasters, or other uncontrollable events. We appreciate your understanding in such situations.</li>
                    <li><strong>Lost or Damaged Packages:</strong> If your package is lost in transit or arrives damaged, please contact us immediately at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a> with your order number and details of the issue. We will work with the courier to investigate and find a resolution.</li>
                </ul>

                <h2>6. Refused Delivery</h2>
                <p>Customers who refuse delivery of an order will be responsible for the original shipping charges, any return shipping charges, and a restocking fee if applicable. This amount will be deducted from any refund issued.</p>

                <h2>7. Contact Information</h2>
                <p>If you have any questions or concerns regarding your order's shipping, please don't hesitate to contact our customer support team:</p>
                <ul>
                    <li>Email: <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a></li>
                    <li>Phone: 09122196241</li>
                    <li>Or visit our <a href="contact.php">Contact Us</a> page.</li>
                </ul>
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