<?php 
include 'config.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// Verify order belongs to user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Shopee-like System</title>
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
            padding: 2rem 0;
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

        .order-container {
            background-color: var(--light-color);
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .order-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-box {
            background-color: var(--cloud-white);
            padding: 1rem;
            border-radius: 5px;
        }

        .info-box h3 {
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .info-box p {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .status-pending {
            color: #f0ad4e;
        }

        .status-completed {
            color: #5cb85c;
        }

        .status-cancelled {
            color: #d9534f;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--cloud-white);
            font-weight: bold;
            color: var(--dark-color);
        }

        tr:hover {
            background-color: rgba(238, 77, 45, 0.05);
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-cell img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 3px;
        }

        .total-row {
            font-weight: bold;
            background-color: var(--cloud-white);
        }

        .back-link {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--light-color);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 3px;
            transition: background-color 0.3s;
        }

        .back-link:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
        </div>
    </header>

    <div class="container">
        <div class="order-container">
            <div class="order-header">
                <h1>Order #<?php echo htmlspecialchars($order_id); ?></h1>
                <p class="status-<?php echo strtolower($order['status']); ?>">
                    Status: <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                </p>
            </div>

            <div class="order-info">
                <div class="info-box">
                    <h3>Order Date</h3>
                    <p><?php echo htmlspecialchars($order['created_at']); ?></p>
                </div>
                <div class="info-box">
                    <h3>Total Amount</h3>
                    <p>₱<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
                <div class="info-box">
                    <h3>Payment Method</h3>
                    <p><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></p>
                </div>
            </div>

            <div class="info-box">
                <h3>Shipping Address</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>

            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="product-cell">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3">Total</td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <a href="orders.php" class="back-link">Back to Orders</a>
        </div>
    </div>
</body>
</html>