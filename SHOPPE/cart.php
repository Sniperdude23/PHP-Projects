<?php
include 'config.php'; // Include your configuration file (likely for database connection and session start)

/**
 * Redirects the user to the login page if they are not authenticated.
 * This ensures that only logged-in users can access the cart page.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Handles updates to product quantities in the cart.
 * This block processes POST requests when a user submits quantity changes.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quantity'])) {
    // Iterate through each product quantity submitted
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id); // Sanitize product ID to an integer
        $quantity = intval($quantity);       // Sanitize quantity to an integer

        // Remove item from cart if quantity is 0 or less
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            // Optional: Fetch product stock to prevent adding more than available
            // This is a good practice to ensure stock limits are respected
            // $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            // $stmt->execute([$product_id]);
            // $product_stock = $stmt->fetchColumn();
            // if ($quantity > $product_stock) {
            //      $quantity = $product_stock; // Cap quantity at available stock
            // }
            $_SESSION['cart'][$product_id] = $quantity; // Update the quantity in the session cart
        }
    }
    // Redirect back to the cart page to reflect changes and prevent form resubmission
    header("Location: cart.php");
    exit();
}

/**
 * Handles removing a single item from the cart.
 * This block processes GET requests when a user clicks the "Remove" link.
 */
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']); // Sanitize the product ID to be removed

    // Check if the item exists in the cart before attempting to remove it
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]); // Remove the product from the session cart
    }
    // Redirect back to the cart page after removal
    header("Location: cart.php");
    exit();
}

/**
 * Calculates the total cost of items in the cart and fetches product details from the database.
 * This function ensures that the displayed cart contents are up-to-date with current product data and stock.
 */
$total = 0; // Initialize total cart value
$cart_items = []; // Array to store detailed cart items

// Proceed only if the cart is not empty in the session
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']); // Get all product IDs currently in the cart
    
    // Create placeholders for the SQL IN clause for prepared statement safety
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    // Prepare and execute a SQL statement to fetch product details for all cart items
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    
    // Iterate through fetched product details and build the cart items array
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $quantity = $_SESSION['cart'][$product['id']]; // Get the quantity from the session cart

        // Adjust quantity if it exceeds available stock (important for preventing over-selling)
        if ($quantity > $product['stock']) {
            $quantity = $product['stock']; // Cap the quantity at the available stock
            $_SESSION['cart'][$product['id']] = $quantity; // Update the session cart with the adjusted quantity
        }
        
        $subtotal = $product['price'] * $quantity; // Calculate subtotal for the current item
        $total += $subtotal; // Add item subtotal to the grand total
        
        // Add current item's details to the cart_items array
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ShopeeClone</title>
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

        /* Cart specific styles */
        .cart-content {
            background-color: var(--light-color);
            padding: 2rem;
            border-radius: 5px;
            margin: 1rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-color);
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .cart-table th, .cart-table td {
            border: 1px solid #eee;
            padding: 0.8rem;
            text-align: left;
        }

        .cart-table th {
            background-color: var(--gray-color);
            color: var(--dark-color);
            font-weight: bold;
        }

        .cart-table td {
            vertical-align: middle;
        }

        .cart-table input[type="number"] {
            width: 70px;
            padding: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }

        .cart-table a.remove-item {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .cart-table a.remove-item:hover {
            text-decoration: underline;
        }

        .cart-total-row td {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .cart-actions {
            display: flex;
            justify-content: flex-end; /* Align buttons to the right */
            gap: 1rem; /* Space between buttons */
            margin-top: 1.5rem;
        }

        .button { /* Moved common button styles to a reusable class */
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none; /* For the anchor tag styled as a button */
            display: inline-block; /* Ensure anchor tag acts like a block for padding */
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: var(--secondary-color);
        }

        /* Specific style for the "Update Cart" button as it's a <button> tag */
        .cart-actions button[type="submit"] {
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .cart-actions button[type="submit"]:hover {
            background-color: var(--secondary-color);
        }


        .empty-cart-message {
            text-align: center;
            padding: 2rem;
            font-size: 1.2rem;
            color: var(--text-color);
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
        <div class="cart-content">
            <h2 class="section-title">Shopping Cart</h2>
            
            <?php if (empty($cart_items)): ?>
                <p class="empty-cart-message">Your cart is empty.</p>
                <p style="text-align: center; margin-top: 2rem;"><a href="index.php" class="button">Continue Shopping</a></p>
            <?php else: ?>
                <form method="POST">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product']['name']); ?></td>
                                    <td>₱<?php echo number_format($item['product']['price'], 2); ?></td>
                                    <td>
                                        <input type="number" name="quantity[<?php echo htmlspecialchars($item['product']['id']); ?>]" 
                                                       value="<?php echo htmlspecialchars($item['quantity']); ?>" 
                                                       min="1" 
                                                       max="<?php echo htmlspecialchars($item['product']['stock']); ?>">
                                    </td>
                                    <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo htmlspecialchars($item['product']['id']); ?>" class="remove-item">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="cart-total-row">
                                <td colspan="3">Total</td>
                                <td>₱<?php echo number_format($total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="cart-actions">
                        <button type="submit">Update Cart</button>
                        <a href="checkout.php" class="button">Proceed to Checkout</a>
                    </div>
                </form>
                <p style="text-align: center; margin-top: 2rem;"><a href="index.php" class="button">Continue Shopping</a></p>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>