<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelEase | Timeless Luxury</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --gray-1: #111111;
            --gray-2: #222222;
            --gray-5: #555555;
            --gray-9: #999999;
            --gray-e: #eeeeee;
            --gold: #D4AF37;
            --error: #f44336;
            --success: #4CAF50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--black);
            color: var(--white);
            line-height: 1.8;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }
        
        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 25px 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        header.scrolled {
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.95);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .logo span {
            font-weight: 300;
            opacity: 0.8;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            margin-left: 30px;
            font-size: 0.9rem;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
            position: relative;
            transition: all 0.3s ease;
            opacity: 0.8;
        }
        
        .nav-links a:hover {
            opacity: 1;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--white);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .welcome-message {
            margin-right: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Hero Section */
        .hero {
            height: 100vh;
            min-height: 800px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') 
                        no-repeat center center/cover;
            display: flex;
            align-items: center;
            position: relative;
            margin-top: 80px;
            animation: fadeIn 1.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(transparent, var(--black));
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 30px;
            position: relative;
            z-index: 2;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            opacity: 0.8;
            font-weight: 300;
            color: var(--gray-e);
            animation: slideInLeft 1s ease-out;
        }
        
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 4.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 30px;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
            animation: slideInRight 1s ease-out;
        }
        
        .hero-text {
            font-size: 1.1rem;
            margin-bottom: 40px;
            opacity: 0.9;
            max-width: 600px;
            line-height: 1.8;
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        @keyframes slideInLeft {
            from { transform: translateX(-50px); opacity: 0; }
            to { transform: translateX(0); opacity: 0.8; }
        }
        
        @keyframes slideInRight {
            from { transform: translateX(50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 0.9; }
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background-color: transparent;
            color: var(--white);
            border: 1px solid var(--white);
            text-decoration: none;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeIn 1s ease-out 0.6s both;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--white);
            color: var(--black);
        }
        
        .btn-filled {
            background-color: var(--gold);
            border-color: var(--gold);
            color: var(--black);
        }
        
        .btn-filled:hover {
            background-color: transparent;
            color: var(--gold);
        }
        
        /* Rooms Section */
        .section {
            padding: 120px 0;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 80px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 2px;
            background-color: var(--white);
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 40px;
        }
        
        .room-card {
            position: relative;
            overflow: hidden;
            height: 500px;
            transition: all 0.5s ease;
        }
        
        .room-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .room-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 30px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            transform: translateY(100px);
            transition: all 0.5s ease;
        }
        
        .room-type {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .room-price {
            font-size: 1.2rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .room-desc {
            margin-bottom: 20px;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .room-meta {
            display: flex;
            margin-bottom: 20px;
        }
        
        .meta-item {
            margin-right: 20px;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .room-card:hover .room-overlay {
            transform: translateY(0);
        }
        
        .room-card:hover .room-image {
            transform: scale(1.05);
        }
        
        /* Footer */
        footer {
            background-color: var(--gray-1);
            padding: 80px 0 30px;
            text-align: center;
        }
        
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 30px;
            display: inline-block;
            color: var(--white);
            text-decoration: none;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: var(--white);
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.9rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .social-links {
            margin-bottom: 40px;
        }
        
        .social-links a {
            color: var(--white);
            margin: 0 10px;
            font-size: 1.2rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .social-links a:hover {
            opacity: 1;
        }
        
        .copyright {
            font-size: 0.8rem;
            opacity: 0.5;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }
        
        .modal-content {
            background-color: var(--black);
            margin: 10% auto;
            padding: 40px;
            border: 1px solid var(--gray-5);
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: slideDown 0.4s ease-out;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 1.8rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .close-modal:hover {
            opacity: 1;
        }
        
        .modal h2 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            background-color: transparent;
            border: 1px solid var(--gray-5);
            color: var(--white);
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--white);
        }
        
        .modal-message {
            color: var(--success);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-error {
            color: var(--error);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-footer-text {
            text-align: center;
            margin-top: 20px;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .modal-footer-text a {
            color: var(--white);
            text-decoration: underline;
            cursor: pointer;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3.5rem;
            }
            
            .rooms-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .nav-links a {
                margin: 0 10px;
            }
            
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-text {
                font-size: 1rem;
            }
            
            .hero {
                min-height: 700px;
                text-align: center;
            }
            
            .hero-content {
                margin: 0 auto;
            }
            
            .hero-text {
                margin-left: auto;
                margin-right: auto;
            }
            
            .modal-content {
                margin: 20% auto;
                padding: 30px;
            }
        }
        
        @media (max-width: 576px) {
            .hero {
                min-height: 600px;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .rooms-grid {
                grid-template-columns: 1fr;
            }
            
            .room-card {
                height: 400px;
            }
            
            .btn {
                padding: 12px 25px;
                font-size: 0.8rem;
            }
            
            .modal-content {
                margin: 30% auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header id="header">
        <div class="container header-content">
            <a href="#" class="logo">HOTEL<span>EASE</span></a>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <?php if (isAdmin()): ?>
                        <a href="admin_dashboard.php">Dashboard</a>
                    <?php else: ?>
                        <a href="my_bookings.php">My Bookings</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="#" class="modal-trigger" data-modal="login-modal">Login</a>
                    <a href="#" class="modal-trigger" data-modal="register-modal">Register</a>
                <?php endif; ?>
                <a href="#rooms">Rooms</a>
                <a href="#contact">Contact</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h4 class="hero-subtitle">Since 1928</h4>
            <h1 class="hero-title">Timeless Elegance, Modern Luxury</h1>
            <p class="hero-text">HotelEase stands as a beacon of sophistication in the heart of the city. Our meticulously designed rooms and unparalleled service create an experience that lingers in memory long after your stay.</p>
            <a href="#rooms" class="btn">Explore Rooms</a>
        </div>
    </section>

    <!-- Rooms Section -->
    <section class="section" id="rooms">
        <div class="container">
            <h2 class="section-title">Our Signature Suites</h2>
            <div class="rooms-grid">
                <?php
                try {
                    $sql = "SELECT * FROM rooms WHERE available = TRUE LIMIT 6";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='room-card'>";
                            
                            $imagePath = $row['image_path'] ?? 'images/room-placeholder.jpg';
                            echo "<img src='{$imagePath}' class='room-image' alt='{$row['room_type']}'>";
                            
                            echo "<div class='room-overlay'>";
                            echo "<h3 class='room-type'>{$row['room_type']} Suite</h3>";
                            echo "<p class='room-price'>" . formatPeso($row['price_per_night']) . " / night</p>";
                            
                            // Room meta information
                            echo "<div class='room-meta'>";
                            echo "<span class='meta-item'>{$row['capacity']} " . ($row['capacity'] > 1 ? 'Guests' : 'Guest') . "</span>";
                            echo "</div>";
                            
                            echo "<p class='room-desc'>" . substr($row['description'], 0, 150) . "...</p>";
                            
                            // Booking button
                            if (isLoggedIn() && !isAdmin()) {
                                echo "<a href='book.php?room_id={$row['id']}' class='btn'>Reserve Now</a>";
                            } elseif (!isLoggedIn()) {
                                echo "<a href='#' class='btn modal-trigger' data-modal='login-modal'>Login to Book</a>";
                            }
                            
                            echo "</div></div>";
                        }
                    } else {
                        echo "<p style='grid-column: 1/-1; text-align: center;'>Our rooms are currently fully booked. Please check back later.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='grid-column: 1/-1; text-align: center; color: var(--error);'>Error loading rooms. Please try again later.</p>";
                    error_log("Database error: " . $e->getMessage());
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <a href="#" class="footer-logo">HOTEL<span>EASE</span></a>
            <div class="footer-links">
                <a href="#">Home</a>
                <a href="#rooms">Rooms</a>
                <a href="#">Amenities</a>
                <a href="#">Gallery</a>
                <a href="#">About</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
            <p class="copyright">© 2023 HotelEase. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Login to HotelEase</h2>
            <div id="login-message"></div>
            <form id="login-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-filled">Login</button>
            </form>
            <p class="modal-footer-text">Don't have an account? <a href="#" class="switch-modal" data-current="login-modal" data-target="register-modal">Register here</a></p>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Create an Account</h2>
            <div id="register-message"></div>
            <form id="register-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-filled">Register</button>
            </form>
            <p class="modal-footer-text">Already have an account? <a href="#" class="switch-modal" data-current="register-modal" data-target="login-modal">Login here</a></p>
        </div>
    </div>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.classList.contains('modal-trigger') || this.classList.contains('switch-modal')) {
                    return;
                }
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const modalTriggers = document.querySelectorAll('.modal-trigger');
            const modals = document.querySelectorAll('.modal');
            const closeButtons = document.querySelectorAll('.close-modal');
            const switchModalLinks = document.querySelectorAll('.switch-modal');
            
            // Open modal
            modalTriggers.forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modalId = this.getAttribute('data-modal');
                    document.getElementById(modalId).style.display = 'block';
                });
            });
            
            // Close modal
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });
            
            // Switch between modals
            switchModalLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const currentModal = this.getAttribute('data-current');
                    const targetModal = this.getAttribute('data-target');
                    
                    document.getElementById(currentModal).style.display = 'none';
                    document.getElementById(targetModal).style.display = 'block';
                });
            });
            
            // Close when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
            
            // Handle form submissions with AJAX
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const loginMessage = document.getElementById('login-message');
            const registerMessage = document.getElementById('register-message');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'login.php', loginMessage);
                });
            }
            
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'register.php', registerMessage);
                });
            }
            
            function submitForm(form, action, messageElement) {
                const formData = new FormData(form);
                const modalId = form.closest('.modal').id;
                
                fetch(action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showMessage(messageElement, data.message, 'success');
                        
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        showMessage(messageElement, data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage(messageElement, 'An error occurred. Please try again.', 'error');
                });
            }
            
            function showMessage(element, message, type) {
                element.textContent = message;
                element.className = type === 'success' ? 'modal-message' : 'modal-error';
                element.style.display = 'block';
                
                // Clear message after 5 seconds
                setTimeout(() => {
                    element.textContent = '';
                    element.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>