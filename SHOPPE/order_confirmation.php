<?php include 'config.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
$items_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order! Your order number is #<?php echo $order_id; ?></p>
    
    <h2>Order Details</h2>
    <p>Status: <?php echo ucfirst($order['status']); ?></p>
    <p>Order Date: <?php echo $order['created_at']; ?></p>
    <p>Total Amount: $<?php echo $order['total_amount']; ?></p>
    <p>Shipping Address: <?php echo nl2br($order['shipping_address']); ?></p>
    <p>Payment Method: <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
    
    <h3>Order Items</h3>
    <ul>
        <?php foreach ($items as $item): ?>
            <li>
                <?php echo $item['name']; ?> - 
                <?php echo $item['quantity']; ?> x $<?php echo $item['price']; ?> = 
                $<?php echo $item['quantity'] * $item['price']; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <p><a href="orders.php">View All Orders</a> | <a href="products.php">Continue Shopping</a></p>
</body>
</html>