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
    error_log("Error fetching categories for returns.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on returns page
$search_query = ''; // No search query on returns page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds Policy - Shoppies</title>
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
                <h1>Returns & Refunds Policy</h1>
                <p><strong>Last Updated: <?php echo date("F j, Y"); ?></strong></p>

                <p>At Shoppies, your satisfaction is our priority. We understand that sometimes, an item might not be what you expected. This policy outlines our guidelines for returns and refunds.</p>
                <p>Please note that "No Return, No Exchange" is generally prohibited by the Philippine Consumer Act (RA 7394) for defective goods. We fully comply with consumer protection laws.</p>

                <h2>1. Eligibility for Returns</h2>
                <p>You may be eligible for a return if:</p>
                <ul>
                    <li>The item is **defective, damaged, or expired** upon arrival.</li>
                    <li>The item delivered is **wrong or does not match the product description/photo** on our website.</li>
                    <li>You received an **incomplete product or missing parts/accessories**.</li>
                    <li>You have a **valid reason for change of mind** (subject to conditions below).</li>
                </ul>
                <h3>Conditions for Return:</h3>
                <ul>
                    <li>Returns must be initiated within <strong>[e.g., 7 days]</strong> from the date you received your order.</li>
                    <li>The item must be unused, unwashed, and in the same condition that you received it.</li>
                    <li>It must be in its original packaging with all tags, labels, and accessories attached.</li>
                    <li>Proof of purchase (order number, invoice) is required.</li>
                </ul>

                <h2>2. Non-Returnable Items</h2>
                <p>The following items generally cannot be returned or exchanged unless they are defective or damaged upon arrival:</p>
                <ul>
                    <li>Items explicitly marked as "non-returnable" or "final sale" on the product page.</li>
                    <li>Personalized or customized items.</li>
                    <li>Used or unsealed intimate apparel, swimwear, and personal care products for hygiene reasons.</li>
                    <li>Perishable goods (e.g., food, flowers) past their expiry or intended use date.</li>
                    <li>Gift cards.</li>
                    <li>Digital products.</li>
                </ul>
                <p>For defective or damaged non-returnable items, please contact us immediately.</p>

                <h2>3. How to Initiate a Return</h2>
                <p>To start a return, please follow these steps:</p>
                <ol>
                    <li>Contact our Customer Support within the eligible return period (e.g., 7 days) via email at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a> or call us at 09122196241.</li>
                    <li>Provide your order number, the name of the item(s) you wish to return, and the reason for the return (e.g., "defective," "wrong item," "change of mind"). Attaching photos or videos of the issue is highly recommended for damaged/defective items.</li>
                    <li>Our team will review your request and provide you with instructions on how to proceed. This may include arranging for pick-up or providing a return address.</li>
                    <li>Package the item securely in its original packaging, including all accessories, manuals, and free gifts received with the purchase.</li>
                    <li>Ship the item back to us as instructed. We recommend using a trackable shipping service.</li>
                </ol>

                <h2>4. Refunds</h2>
                <p>Once your returned item is received and inspected, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>
                <ul>
                    <li>If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within <strong>[e.g., 7-14 business days]</strong>.</li>
                    <li>For Cash on Delivery (COD) orders, refunds will typically be processed via [e.g., GCash, bank transfer, or store credit]. We will contact you for your preferred refund method.</li>
                    <li>Shipping fees from the original purchase are generally non-refundable unless the return is due to our error (e.g., wrong item, defective product).</li>
                </ul>
                <h3>Late or Missing Refunds:</h3>
                <p>If you haven't received a refund yet, first check your bank account again. Then contact your credit card company, it may take some time before your refund is officially posted. Next contact your bank. There is often some processing time before a refund is posted. If you've done all of this and you still have not received your refund, please contact us at <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a>.</p>

                <h2>5. Exchanges</h2>
                <p>We primarily offer refunds for eligible returns. If you wish to exchange an item for a different size, color, or a different product, you will need to return the original item for a refund (as per our policy) and then place a new order for the desired item. This ensures you get the correct item faster and avoids inventory complexities.</p>
                <p>Exchanges are only offered for defective or wrong items received (e.g., wrong size shipped) if the correct item is available in stock. In such cases, we will arrange for the pick-up of the incorrect item and delivery of the correct item at no additional cost to you.</p>

                <h2>6. Return Shipping Costs</h2>
                <ul>
                    <li><strong>Our Error (Defective, Damaged, Wrong Item):</strong> Shoppies will cover the cost of return shipping. We will provide a return label or arrange for pick-up.</li>
                    <li><strong>Change of Mind / Other Reasons:</strong> If you are returning an item due to a change of mind or other reasons not attributable to our error, you will be responsible for the return shipping costs. These costs are non-refundable.</li>
                </ul>

                <h2>7. Policy Changes</h2>
                <p>We reserve the right to modify this returns and refunds policy at any time. Any changes will be effective immediately upon posting on our website. It is your responsibility to review this policy periodically for updates.</p>

                <h2>8. Contact Us</h2>
                <p>For any questions regarding our Returns & Refunds Policy, please contact our Customer Support:</p>
                <ul>
                    <li>Email: <a href="mailto:gelongo.gelmae@gmail.com">gelongo.gelmae@gmail.com</a></li>
                    <li>Phone: 09122196241</li>
                    <li>Visit our <a href="contact.php">Contact Us</a> page.</li>
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