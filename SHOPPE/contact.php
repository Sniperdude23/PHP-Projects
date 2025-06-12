<?php
include 'config.php'; // Include database connection and session start

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables for form feedback
$form_message = '';
$message_type = ''; // 'success' or 'error'

// Initialize form fields with empty values or submitted values
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $form_message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Prepare to save message to database
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);

            $form_message = 'Thank you for your message! We will get back to you soon.';
            $message_type = 'success';

            // Clear form fields on success
            $name = $email = $subject = $message = '';

            // In a real application, you might still want to send an email notification here
            // Example for sending email (requires mail server setup):
            /*
            $to = 'your_support_email@example.com'; // Change this to your actual support email
            $email_subject_notification = "New Contact Form Submission: " . htmlspecialchars($subject);
            $email_body_notification = "Name: " . htmlspecialchars($name) . "\n"
                                     . "Email: " . htmlspecialchars($email) . "\n"
                                     . "Subject: " . htmlspecialchars($subject) . "\n"
                                     . "Message:\n" . htmlspecialchars($message);
            $headers = "From: webmaster@shoppies.com\r\nReply-To: " . $email;

            if (!mail($to, $email_subject_notification, $email_body_notification, $headers)) {
                // Log or handle email sending failure, but don't show to user if DB save was successful
                error_log("Failed to send contact form email notification for: " . $email);
            }
            */

        } catch (PDOException $e) {
            // Log the error and provide a generic message to the user
            error_log("Error saving contact message to database: " . $e->getMessage());
            $form_message = 'There was an error processing your message. Please try again later.';
            $message_type = 'error';
        }
    }
}


// Fetch categories for the navigation (similar to index.php)
$categories = [];
try {
    $stmt_categories = $pdo->query("SELECT DISTINCT c.category FROM products p JOIN (SELECT DISTINCT category FROM products WHERE stock > 0) AS c ON p.category = c.category WHERE p.category IS NOT NULL AND p.category != '' ORDER BY c.category ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching categories for contact.php: " . $e->getMessage());
    $categories = [];
}

$selected_category = ''; // No category selected on contact page
$search_query = ''; // No search query on contact page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Shoppies</title>
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

        .contact-section {
            background-color: var(--light-color);
            padding: 3rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .contact-info-col {
            flex: 1;
            min-width: 280px; /* Minimum width for info column */
        }

        .contact-form-col {
            flex: 2; /* Take more space for the form */
            min-width: 320px; /* Minimum width for form column */
        }

        .contact-section h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            text-align: center;
            width: 100%; /* Make heading span full width */
        }

        .contact-section h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .contact-info-col p {
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .contact-info-col p i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        /* Form Styles */
        .contact-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            background-color: var(--cloud-white); /* Light background for input */
            color: var(--dark-color);
        }

        .contact-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .contact-form button {
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }

        .contact-form button:hover {
            background-color: var(--secondary-color);
        }

        .form-feedback {
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }

        .form-feedback.success {
            background-color: #d4edda; /* Light green */
            color: #155724; /* Dark green */
            border: 1px solid #c3e6cb;
        }

        .form-feedback.error {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            border: 1px solid #f5c6cb;
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
            .contact-section {
                flex-direction: column; /* Stack contact info and form vertically */
            }
            .contact-info-col, .contact-form-col {
                min-width: unset; /* Remove min-width to allow full width */
                width: 100%;
            }
            .contact-section {
                padding: 2rem;
            }
            .contact-section h1 {
                font-size: 2rem;
            }
            .contact-section h2 {
                font-size: 1.5rem;
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
            .contact-section {
                padding: 1.5rem;
            }
            .contact-section h1 {
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
            <section class="contact-section">
                <h1>Contact Us</h1>

                <div class="contact-info-col">
                    <h2>Reach Out to Shoppies!</h2>
                    <p>
                        We're here to help and answer any question you might have. We look forward to hearing from you.
                    </p>
                    <p><i class="fas fa-envelope"></i> Email: gelongo.gelmae@gmail.com</p>
                    <p><i class="fas fa-phone-alt"></i> Phone: 09122196241</p>
                    <p><i class="fas fa-map-marker-alt"></i> Address: Binalbagan, Negros Occidental, Philippines</p>

                    <h2 style="margin-top: 2rem;">Business Hours</h2>
                    <p><i class="far fa-clock"></i> Monday - Friday: 9:00 AM - 6:00 PM (PST)</p>
                    <p><i class="far fa-clock"></i> Saturday: 10:00 AM - 3:00 PM (PST)</p>
                    <p><i class="far fa-clock"></i> Sunday: Closed</p>

                    <h2 style="margin-top: 2rem;">Follow Us</h2>
                    <div class="social-links" style="justify-content: flex-start;">
                        <a href="#" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="contact-form-col">
                    <h2>Send Us a Message</h2>
                    <?php if ($form_message): ?>
                        <div class="form-feedback <?php echo $message_type; ?>">
                            <?php echo htmlspecialchars($form_message); ?>
                        </div>
                    <?php endif; ?>
                    <form action="contact.php" method="POST" class="contact-form">
                        <label for="name">Your Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

                        <label for="email">Your Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>

                        <label for="message">Message:</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($message); ?></textarea>

                        <button type="submit">Send Message</button>
                    </form>
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