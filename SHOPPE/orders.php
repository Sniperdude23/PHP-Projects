<?php
include 'config.php'; // Include database connection and session start

/**
 * Ensures a session is started.
 * This is a safeguard, as `config.php` should ideally handle session initialization.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects the user to the login page if they are not authenticated.
 * This security measure prevents unauthorized access to the orders page.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Fetches all orders associated with the currently logged-in user from the database.
 * Orders are sorted by creation date in descending order (newest first).
 */
$stmt = $pdo->prepare("SELECT id, created_at, total_amount, status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]); // Execute the statement with the user's ID
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all matching orders as an associative array
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ShopeeClone</title>
    <style>
        :root {
            --primary-color: #ee4d2d;
            --secondary-color: #ff7337;
            --light-color: #fff;
            --dark-color: #333;
            --gray-color: #f5f5f5;
            --text-color: #555;
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
            display: flex; /* Added for sticky footer */
            flex-direction: column; /* Added for sticky footer */
            min-height: 100vh; /* Added for sticky footer */
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
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
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

        .main-content {
            flex-grow: 1; /* Added for sticky footer */
            background-color: var(--light-color);
            padding: 2rem;
            border-radius: 5px;
            margin: 1rem auto; /* Centered with auto margins */
            width: 90%; /* To match container width */
            max-width: 1200px; /* To match container max-width */
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-color);
            font-size: 1.8rem;
        }

        /* Table specific styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden; /* Ensures rounded corners apply to content */
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary-color);
            color: var(--light-color);
            font-weight: bold;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: var(--gray-color);
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        td a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        td a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .no-orders-message {
            text-align: center;
            padding: 3rem;
            background-color: var(--light-color);
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }

        .no-orders-message p {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .no-orders-message a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .no-orders-message a:hover {
            text-decoration: underline;
        }
        
        .add-to-cart { /* Reusing the button style from the product page for "Continue Shopping" */
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--light-color);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 3px;
            transition: background-color 0.3s;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .add-to-cart:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo"></div>
                <div class="user-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                        <a href="cart.php">Cart</a>
                        <a href="orders.php">Orders</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): // Added isset for safety ?>
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
        <div class="main-content">
            <h2 class="section-title">My Orders</h2>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders-message">
                    <p>You haven't placed any orders yet. Start exploring our products!</p>
                    <a href="index.php">Continue Shopping</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($order['created_at']))); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                                <td><a href="order_details.php?id=<?php echo htmlspecialchars($order['id']); ?>">View Details</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 2rem;">
                <a href="index.php" class="add-to-cart">Continue Shopping</a>
            </p>
        </div>
    </div>
</body>
</html>