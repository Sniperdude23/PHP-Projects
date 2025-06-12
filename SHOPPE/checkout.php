<?php
include 'config.php';

// Start the session if not already started (important for $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if cart is empty, redirect to cart if it is
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Initialize error variable
$error = '';
$user_email = '';
$total_summary = 0; // Initialize total for display

// Get user's default shipping address (simplified - using email as a placeholder)
// In a real application, you would fetch actual user addresses
try {
    $user_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_email = htmlspecialchars($user['email']);
    }
} catch (PDOException $e) {
    // Log the error (e.g., error_log($e->getMessage());)
    $error = "Error fetching user details.";
}


// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Create order
        $shipping_address = $_POST['shipping_address'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';

        if (empty($shipping_address) || empty($payment_method)) {
            throw new Exception("Shipping address and payment method are required.");
        }

        $total = 0;

        // Calculate total and fetch product details for stock check
        $product_ids = array_keys($_SESSION['cart']);
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $products_stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
            $products_stmt->execute($product_ids);

            $products_in_cart = [];
            while ($row = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
                $products_in_cart[$row['id']] = $row;
                // Check for sufficient stock before processing
                if ($products_in_cart[$row['id']]['stock'] < $_SESSION['cart'][$row['id']]) {
                    throw new Exception("Insufficient stock for " . htmlspecialchars($row['name']) . ".");
                }
                $total += $row['price'] * $_SESSION['cart'][$row['id']];
            }
        } else {
            throw new Exception("Your cart is empty. Please add products before checking out.");
        }

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method)
                                 VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $total,
            $shipping_address,
            $payment_method
        ]);
        $order_id = $pdo->lastInsertId();

        // 2. Add order items and update stock
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $product_data = $products_in_cart[$product_id]; // Get product data fetched earlier

            // Add order item
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price)
                                         VALUES (?, ?, ?, ?)");
            $item_stmt->execute([
                $order_id,
                $product_id,
                $quantity,
                $product_data['price']
            ]);

            // Update stock
            $new_stock = $product_data['stock'] - $quantity;
            $update_stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $update_stmt->execute([$new_stock, $product_id]);

            // Check for stock alerts
            // First, check if an alert for this product and type already exists to avoid duplicates
            $existing_alert_stmt = $pdo->prepare("SELECT id FROM stock_alerts WHERE product_id = ? AND alert_type = ? AND resolved = FALSE");

            if ($new_stock <= 0) {
                $existing_alert_stmt->execute([$product_id, 'out']);
                if (!$existing_alert_stmt->fetch()) { // Only insert if no unresolved 'out' alert exists
                    $alert_stmt = $pdo->prepare("INSERT INTO stock_alerts (product_id, current_stock, alert_type)
                                                 VALUES (?, ?, 'out')");
                    $alert_stmt->execute([$product_id, $new_stock]);
                }
            } elseif ($new_stock <= 5) { // Example threshold for 'low' stock
                $existing_alert_stmt->execute([$product_id, 'low']);
                if (!$existing_alert_stmt->fetch()) { // Only insert if no unresolved 'low' alert exists
                    $alert_stmt = $pdo->prepare("INSERT INTO stock_alerts (product_id, current_stock, alert_type)
                                                 VALUES (?, ?, 'low')");
                    $alert_stmt->execute([$product_id, $new_stock]);
                }
            }
        }

        // 3. Update sales analytics
        $today = date('Y-m-d');
        $analytics_stmt = $pdo->prepare("INSERT INTO sales_analytics (date, total_sales, total_orders)
                                         VALUES (?, ?, 1)
                                         ON DUPLICATE KEY UPDATE
                                         total_sales = total_sales + VALUES(total_sales),
                                         total_orders = total_orders + 1");
        $analytics_stmt->execute([$today, $total]);

        $pdo->commit();

        // Clear cart
        unset($_SESSION['cart']);

        // Redirect to order confirmation
        header("Location: order_confirmation.php?id=$order_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Checkout failed: " . htmlspecialchars($e->getMessage());
    }
}

// Re-calculate total for initial display on page load or after an error
$product_data_for_display = [];
if (!empty($_SESSION['cart'])) {
    $product_ids_for_display = array_keys($_SESSION['cart']);
    $placeholders_for_display = implode(',', array_fill(0, count($product_ids_for_display), '?'));
    $stmt_for_display = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders_for_display)");
    $stmt_for_display->execute($product_ids_for_display);
    while ($product_for_display = $stmt_for_display->fetch(PDO::FETCH_ASSOC)) {
        $product_data_for_display[$product_for_display['id']] = $product_for_display;
        $total_summary += $product_for_display['price'] * $_SESSION['cart'][$product_for_display['id']];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shopee Theme</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5; /* Light gray background */
        }
        .shopee-gradient {
            background-image: linear-gradient(to right, #ee4d2d, #ff7250); /* Shopee orange gradient */
        }
        .shopee-text-orange {
            color: #ee4d2d; /* Shopee orange text */
        }
        /* Custom styling for the select dropdown arrow */
        .select-wrapper {
            position: relative;
        }
        .select-wrapper::after {
            content: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>');
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #4a5568; /* Default text color for the arrow */
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <header class="bg-white shadow-md py-4">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold shopee-text-orange">Shopee Clone</a>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="products.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Shop</a></li>
                    <li><a href="cart.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Cart</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Profile</a></li>
                        <li><a href="logout.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Login</a></li>
                        <li><a href="register.php" class="text-gray-700 hover:text-shopee-orange transition duration-200">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Checkout</h1>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Shipping Information</h2>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                        <input type="email" id="email" value="<?php echo $user_email; ?>" disabled
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline cursor-not-allowed">
                    </div>

                    <div class="mb-4">
                        <label for="shipping_address" class="block text-gray-700 text-sm font-bold mb-2">Shipping Address:</label>
                        <textarea name="shipping_address" id="shipping_address" rows="4" placeholder="Enter your full shipping address" required
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-shopee-orange"></textarea>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Payment Method</h2>
                    <div class="relative select-wrapper mb-4">
                        <select name="payment_method" required
                                class="block appearance-none w-full bg-white border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-shopee-orange">
                            <option value="credit_card">Credit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Order Summary</h2>
                    <ul class="mb-4">
                        <?php
                        if (!empty($product_data_for_display)) {
                            foreach ($product_data_for_display as $product_id => $product) {
                                $quantity = $_SESSION['cart'][$product_id];
                                $subtotal = $product['price'] * $quantity;
                                echo "<li class='flex justify-between items-center py-2 border-b border-gray-200 last:border-b-0'>";
                                echo "<span class='text-gray-700'>{$product['name']} x {$quantity}</span>";
                                echo "<span class='font-semibold text-gray-800'>₱" . number_format($subtotal, 2) . "</span>";
                                echo "</li>";
                            }
                        } else {
                            echo "<li class='text-gray-500'>Your cart is empty.</li>";
                        }
                        ?>
                        <li class="flex justify-between items-center py-4 border-t border-gray-300 mt-4">
                            <strong class="text-xl text-gray-800">Total:</strong>
                            <strong class="text-xl shopee-text-orange">₱<?php echo number_format($total_summary, 2); ?></strong>
                        </li>
                    </ul>
                </div>

                <button type="submit" class="shopee-gradient text-white px-6 py-3 rounded-lg shadow-md font-bold text-xl w-full hover:opacity-90 transition duration-300 ease-in-out">
                    Place Order
                </button>
            </form>
        </div>
    </main>

    <footer class="bg-white shadow-inner py-4 mt-8">
        <div class="container mx-auto px-4 text-center text-gray-600">
            &copy; <?php echo date('Y'); ?> Shopee Clone. All rights reserved.
        </div>
    </footer>
</body>
</html>