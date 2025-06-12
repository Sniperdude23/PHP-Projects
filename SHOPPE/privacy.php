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
    error_log("Error fetching categories for privacy.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on privacy page
$search_query = ''; // No search query on privacy page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Shoppies</title>
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

        .policy-section {
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
                <h1>Privacy Policy for Shoppies</h1>
                <p><strong>Last Updated: <?php echo date("F j, Y"); ?></strong></p>

                <p>Welcome to Shoppies! This Privacy Policy describes how Shoppies ("we," "us," or "our") collects, uses, and discloses your personal information when you visit or make a purchase from our website at [Your Website URL Here] (the "Site").</p>

                <h2>1. Information We Collect</h2>
                <p>We collect various types of information in connection with the services we provide, including:</p>
                <ul>
                    <li><strong>Personal Information you provide to us:</strong> This may include your name, email address, shipping address, billing address, phone number, and payment information (e.g., credit card details). We collect this when you register for an account, place an order, subscribe to our newsletter, or contact us.</li>
                    <li><strong>Automatically Collected Information:</strong> When you access and use the Site, we may automatically collect certain information about your device, including information about your web browser, IP address, time zone, and some of the cookies that are installed on your device. Additionally, as you browse the Site, we collect information about the individual web pages or products that you view, what websites or search terms referred you to the Site, and information about how you interact with the Site.</li>
                    <li><strong>Information from Third Parties:</strong> We may receive information about you from third parties, such as payment processors or analytics providers, to help us provide and improve our services.</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <p>We use the information we collect for various purposes, including:</p>
                <ul>
                    <li>To process and fulfill your orders, including managing payments, shipping, and providing order confirmations.</li>
                    <li>To communicate with you about your orders, products, services, and promotional offers.</li>
                    <li>To provide, maintain, and improve our Site and services.</li>
                    <li>To personalize your experience on the Site and to provide product recommendations.</li>
                    <li>To analyze trends, administer the Site, track users’ movements around the Site, and gather demographic information.</li>
                    <li>To detect and prevent fraudulent transactions and other illegal activities.</li>
                    <li>To comply with legal obligations and enforce our terms and conditions.</li>
                </ul>

                <h2>3. Sharing Your Personal Information</h2>
                <p>We share your Personal Information with third parties to help us use your Personal Information, as described above. For example:</p>
                <ul>
                    <li>We use [Payment Gateway Name, e.g., PayPal, Stripe] to process payments. You can read more about how [Payment Gateway Name] uses your Personal Information here: [Link to Payment Gateway's Privacy Policy].</li>
                    <li>We use Google Analytics to help us understand how our customers use the Site. You can read more about how Google uses your Personal Information here: <a href="https://policies.google.com/privacy" target="_blank">https://policies.google.com/privacy</a>. You can also opt-out of Google Analytics here: <a href="https://tools.google.com/dlpage/gaoptout" target="_blank">https://tools.google.com/dlpage/gaoptout</a>.</li>
                    <li>We may share your Personal Information to comply with applicable laws and regulations, to respond to a subpoena, search warrant or other lawful request for information we receive, or to otherwise protect our rights.</li>
                </ul>
                <p>We do not sell, rent, or trade your personal information to third parties.</p>

                <h2>4. Your Rights</h2>
                <p>If you are a resident of certain regions (like the European Economic Area), you have certain rights regarding your personal information. These include the right to access, correct, update, or request deletion of your personal information. If you would like to exercise these rights, please contact us through the contact information below.</p>

                <h2>5. Data Retention</h2>
                <p>When you place an order through the Site, we will maintain your Order Information for our records unless and until you ask us to erase this information.</p>

                <h2>6. Security</h2>
                <p>We implement a variety of security measures to maintain the safety of your personal information when you place an order or enter, submit, or access your personal information. However, no method of transmission over the Internet or electronic storage is 100% secure.</p>

                <h2>7. Changes to This Privacy Policy</h2>
                <p>We may update this privacy policy from time to time in order to reflect, for example, changes to our practices or for other operational, legal, or regulatory reasons. We will notify you of any changes by posting the new Privacy Policy on this page.</p>

                <h2>8. Contact Us</h2>
                <p>For more information about our privacy practices, if you have questions, or if you would like to make a complaint, please contact us by e-mail at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a> or by mail using the details provided below:</p>
                <p>Shoppies Customer Support<br>
                Binalbagan, Negros Occidental, Philippines</p>
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