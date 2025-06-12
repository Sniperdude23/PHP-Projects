<?php 
include 'config.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - ShopeeClone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ee4d2d;
            --secondary-color: #ff7337;
            --light-color: #fff;
            --dark-color: #333;
            --gray-color: #f5f5f5;
            --text-color: #555;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: var(--gray-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        header {
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
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
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--light-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-actions a, .user-actions button {
            color: var(--light-color);
            text-decoration: none;
            padding: 0.5rem 0.8rem;
            border-radius: 3px;
            transition: all 0.3s;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .user-actions a:hover, .user-actions button:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .user-actions .cart-count {
            background-color: var(--light-color);
            color: var(--primary-color);
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .search-bar {
            margin: 1rem 0;
            display: flex;
            width: 100%;
            max-width: 600px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 0.7rem;
            border: none;
            border-radius: 3px 0 0 3px;
            font-size: 1rem;
        }
        
        .search-bar button {
            background-color: var(--secondary-color);
            color: var(--light-color);
            border: none;
            padding: 0 1.5rem;
            border-radius: 0 3px 3px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-bar button:hover {
            background-color: #e04a2d;
        }
        
        .product-detail {
            background-color: var(--light-color);
            padding: 2rem;
            border-radius: 5px;
            margin: 1rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .main-image {
            height: 400px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .thumbnail-container {
            display: flex;
            gap: 1rem;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            background-color: #f9f9f9;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid transparent;
        }
        
        .thumbnail:hover, .thumbnail.active {
            border-color: var(--primary-color);
        }
        
        .thumbnail img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .product-title {
            font-size: 1.8rem;
            color: var(--dark-color);
        }
        
        .product-price {
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .product-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
        }
        
        .meta-item i {
            color: var(--primary-color);
        }
        
        .product-stock {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            background-color: #f0f0f0;
            border-radius: 20px;
            display: inline-block;
        }
        
        .in-stock {
            color: var(--success-color);
        }
        
        .out-of-stock {
            color: #dc3545;
        }
        
        .product-description {
            margin-top: 1.5rem;
            line-height: 1.8;
        }
        
        .product-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-selector input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 3px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--light-color);
        }
        
        .btn-primary:hover {
            background-color: #e04a2d;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: #ffeeeb;
        }
        
        .btn-disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        
        footer {
            text-align: center;
            padding: 1.5rem 0;
            background-color: var(--dark-color);
            color: var(--light-color);
            margin-top: 2rem;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: var(--success-color);
            color: white;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-title {
                font-size: 1.5rem;
            }
            
            .product-price {
                font-size: 1.5rem;
            }
            
            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="notification" id="notification">
            <?php echo $_SESSION['success_message']; ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-shopping-bag"></i>
                    ShopeeClone
                </a>
                
                <form method="GET" action="index.php" class="search-bar">
                    <input type="text" name="search" placeholder="Search for products...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php 
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $cart = $stmt->fetch();
                                $cart_count = $cart['total'] ?? 0;
                                if ($cart_count > 0) {
                                    echo '<span class="cart-count">'.$cart_count.'</span>';
                                }
                            }
                            ?>
                        </a>
                        <a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders</a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin</a>
                        <?php endif; ?>
                        <form action="logout.php" method="post">
                            <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="product-detail">
            <div class="product-images">
                <div class="main-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-image fa-5x" style="color: #ddd;"></i>
                    <?php endif; ?>
                </div>
                <div class="thumbnail-container">
                    <!-- You could add multiple thumbnails here if you have multiple product images -->
                    <div class="thumbnail active">
                        <?php if ($product['image_url']): ?>
                            <img src="<?php echo $product['image_url']; ?>" alt="Thumbnail">
                        <?php else: ?>
                            <i class="fas fa-image" style="color: #ddd;"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-meta">
                    <span class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span><?php echo $product['category'] ?? 'N/A'; ?></span>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-industry"></i>
                        <span><?php echo $product['brand'] ?? 'N/A'; ?></span>
                    </span>
                </div>
                
                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                
                <div class="product-stock <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                    <?php echo $product['stock'] > 0 ? 'In Stock ('.$product['stock'].' available)' : 'Out of Stock'; ?>
                </div>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <form method="POST" action="add_to_cart.php" class="product-actions">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if ($product['stock'] > 0): ?>
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled" disabled>
                            Out of Stock
                        </button>
                    <?php endif; ?>
                    
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ShopeeClone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Show notification if it exists
        const notification = document.getElementById('notification');
        if (notification) {
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.opacity = '1';
            }, 100);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }, 3000);
        }
        
        // Simple thumbnail switcher functionality
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.querySelector('.main-image img');
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', () => {
                // Remove active class from all thumbnails
                thumbnails.forEach(t => t.classList.remove('active'));
                // Add active class to clicked thumbnail
                thumb.classList.add('active');
                // Update main image (if you have multiple images)
                if (thumb.querySelector('img')) {
                    mainImage.src = thumb.querySelector('img').src;
                }
            });
        });
    </script>
</body>
</html>