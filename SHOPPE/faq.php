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
    error_log("Error fetching categories for faq.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on FAQ page
$search_query = ''; // No search query on FAQ page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Shoppies</title>
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

        .faq-section {
            background-color: var(--light-color);
            padding: 3rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .faq-section h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        /* FAQ Accordion Styles */
        .accordion-item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 5px;
            overflow: hidden; /* Ensures child elements respect border-radius */
        }

        .accordion-header {
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .accordion-header:hover {
            background-color: var(--secondary-color);
        }

        .accordion-header .icon {
            margin-left: 10px;
            transition: transform 0.3s ease;
        }

        .accordion-body {
            padding: 20px;
            background-color: var(--cloud-white); /* Lighter background for content */
            color: var(--text-color);
            display: none; /* Hidden by default */
            border-top: 1px solid #eee;
        }

        .accordion-body p {
            margin-bottom: 1em; /* Add space between paragraphs in answer */
        }

        .accordion-body p:last-child {
            margin-bottom: 0;
        }

        /* Styles when accordion is active */
        .accordion-item.active .accordion-header {
            background-color: var(--secondary-color);
        }

        .accordion-item.active .accordion-header .icon {
            transform: rotate(180deg);
        }

        .accordion-item.active .accordion-body {
            display: block; /* Show content when active */
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
            .faq-section {
                padding: 2rem;
            }
            .faq-section h1 {
                font-size: 2rem;
            }
            .accordion-header {
                font-size: 1.1em;
                padding: 12px 15px;
            }
            .accordion-body {
                padding: 15px;
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
            .faq-section {
                padding: 1.5rem;
            }
            .faq-section h1 {
                font-size: 1.8rem;
            }
            .accordion-header {
                font-size: 1em;
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
            <section class="faq-section">
                <h1>Frequently Asked Questions</h1>

                <div class="accordion">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            What payment methods do you accept?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>We accept a variety of payment methods including credit/debit cards (Visa, MasterCard, American Express), GCash, and PayMaya. Cash on Delivery (COD) is also available for eligible areas.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            How long does shipping take?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>Shipping times vary depending on your location. For Metro Manila, delivery typically takes 2-5 business days. For provincial areas, it may take 5-10 business days. Please note that these are estimates and actual delivery times may vary due to unforeseen circumstances or peak seasons.</p>
                            <p>For more details, please refer to our <a href="shipping.php">Shipping Policy</a>.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            Can I track my order?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>Yes, once your order is shipped, you will receive an email with a tracking number and a link to track your package. You can also log in to your Shoppies account and view your order history to find tracking information.</p>
                            <p>You can also visit our <a href="track_order.php">Track Your Order</a> page.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            What is your return policy?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>We offer a 7-day return policy for most items, starting from the date you receive your order. Items must be unused, in their original packaging, and with all tags intact. Some exclusions may apply. For detailed information and steps on how to initiate a return, please visit our <a href="returns.php">Returns & Refunds Policy</a> page.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            How do I cancel an order?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>You may cancel an order if it has not yet been processed for shipping. Please contact our customer support immediately at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a> or call us at 09122196241 with your order number. Once an order has been shipped, it cannot be canceled but may be eligible for return.</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            Do you ship internationally?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>Currently, Shoppies only ships within the Philippines. We are working to expand our shipping services to other countries in the future. Please subscribe to our newsletter for updates!</p>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            How can I contact customer support?
                            <span class="icon fas fa-chevron-down"></span>
                        </div>
                        <div class="accordion-body">
                            <p>You can reach our customer support team via our <a href="contact.php">Contact Us</a> page, by email at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a>, or by phone at 09122196241 during business hours (Monday - Friday, 9:00 AM - 6:00 PM PST).</p>
                        </div>
                    </div>
                    
                    </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accordionHeaders = document.querySelectorAll('.accordion-header');

            accordionHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const accordionItem = this.closest('.accordion-item');
                    const accordionBody = accordionItem.querySelector('.accordion-body');

                    // Toggle active class on the clicked item
                    accordionItem.classList.toggle('active');

                    // Toggle visibility of the body
                    if (accordionBody.style.display === 'block') {
                        accordionBody.style.display = 'none';
                    } else {
                        accordionBody.style.display = 'block';
                    }

                    // Optional: Close other open accordions
                    accordionHeaders.forEach(otherHeader => {
                        const otherItem = otherHeader.closest('.accordion-item');
                        if (otherItem !== accordionItem && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.accordion-body').style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>