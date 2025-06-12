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
    error_log("Error fetching categories for about.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on about page
$search_query = ''; // No search query on about page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Shoppies</title>
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
            padding: 2rem 0; /* Adjusted padding for standalone content */
            margin: 0 auto;
            width: 90%;
            max-width: 1200px;
        }

        .about-section {
            background-color: var(--light-color);
            padding: 3rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .about-section h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .about-section h2 {
            color: var(--dark-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .about-section p {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .about-section ul {
            list-style: disc;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .about-section ul li {
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
            .about-section {
                padding: 2rem;
            }
            .about-section h1 {
                font-size: 2rem;
            }
            .about-section h2 {
                font-size: 1.5rem;
            }
            .about-section p {
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
            .about-section {
                padding: 1.5rem;
            }
            .about-section h1 {
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
                        <a href="index.php" class="category-link <?php echo (empty($selected_category) && empty($search_query)) ? 'active' : ''; ?>">
                            All Products
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="index.php?category=<?php echo urlencode($cat); ?>"
                               class="category-link <?php echo ($selected_category === $cat) ? 'active' : ''; ?>">
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
            <section class="about-section">
                <h1>About Us</h1>
                <p>Welcome to Shoppies, your ultimate destination for incredible deals and a seamless online shopping experience, inspired by the best in e-commerce!</p>
                <p>We believe that everyone deserves access to high-quality products at competitive prices, delivered right to their doorstep. Our mission is to connect buyers with a vast array of products, from everyday essentials to unique finds, all within a secure and user-friendly platform.</p>

                <h2>Our Story</h2>
                <p>Founded in 2025, Shoppies started with a simple idea: to make online shopping accessible, enjoyable, and reliable for everyone. We noticed a gap in the market for a platform that prioritizes both affordability and customer satisfaction. Since then, we've grown rapidly, thanks to our dedicated team and the trust of our valued customers.</p>

                <h2>What We Offer</h2>
                <ul>
                    <li>Wide Selection: Explore thousands of products across various categories, including electronics, fashion, home goods, beauty, and more.</li>
                    <li>Competitive Prices: We work hard to bring you the best deals and promotions, ensuring you get maximum value for your money.</li>
                    <li>Secure Shopping: Your safety is our priority. We use robust security measures to protect your personal information and payment details.</li>
                    <li>Convenient Experience: Our intuitive interface makes it easy to browse, search, and purchase products from your computer or mobile device.</li>
                    <li>Reliable Delivery: We partner with trusted logistics providers to ensure your orders arrive quickly and safely.</li>
                    <li>Exceptional Customer Service: Our support team is here to assist you with any questions or concerns you may have.</li>
                </ul>

                <h2>Our Commitment</h2>
                <p>At Shoppies, we are committed to continuous improvement. We constantly strive to enhance our product offerings, streamline our processes, and provide an even better shopping experience. Your feedback is invaluable to us as we grow and evolve.</p>
                <p>Thank you for choosing Shoppies. We look forward to serving you!</p>
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