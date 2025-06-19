<?php
include 'includes/config.php';
include 'includes/functions.php';

// Initialize cart from session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding items to cart
if (isset($_POST['add_to_cart'])) {
    $meal_id = intval($_POST['meal_id']);
    $quantity = intval($_POST['quantity']) ?: 1;

    if ($meal_id > 0) {
        if (isset($_SESSION['cart'][$meal_id])) {
            $_SESSION['cart'][$meal_id] += $quantity;
        } else {
            $_SESSION['cart'][$meal_id] = $quantity;
        }
    }

    header('Location: cart.php');
    exit;
}

// Handle removing items from cart
if (isset($_POST['remove_from_cart'])) {
    $meal_id = intval($_POST['meal_id']);
    unset($_SESSION['cart'][$meal_id]);

    header('Location: cart.php');
    exit;
}

// Handle updating quantities
if (isset($_POST['update_quantity'])) {
    $meal_id = intval($_POST['meal_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0) {
        $_SESSION['cart'][$meal_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$meal_id]);
    }

    header('Location: cart.php');
    exit;
}

// Handle order submission
if (isset($_POST['submit_order'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $location = trim($_POST['location']);

    try {
        // Check if client exists by phone number
        $check_client = $pdo->prepare("SELECT ID_CLIENTS FROM CLIENTS WHERE PHONE_NUMBER = ?");
        $check_client->execute([$phone]);
        $existing_client = $check_client->fetch();

        if ($existing_client) {
            // Update existing client info (name and location only)
            $update_client = $pdo->prepare("UPDATE CLIENTS SET FULLNAME = ?, LOCATION = ? WHERE PHONE_NUMBER = ?");
            $update_client->execute([$name, $location, $phone]);
            $client_id = $existing_client['ID_CLIENTS'];
        } else {
            // Create new client with all required info
            $insert_client = $pdo->prepare("INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL, PASSWORD, LOCATION) VALUES (?, ?, ?, ?, ?)");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_client->execute([$name, $phone, $email, $hashed_password, $location]);
            $client_id = $pdo->lastInsertId();
        }

        // Calculate total
        $total = 0;
        foreach ($_SESSION['cart'] as $meal_id => $quantity) {
            $meal_stmt = $pdo->prepare("SELECT PRICE FROM MEALS WHERE ID_MEALS = ?");
            $meal_stmt->execute([$meal_id]);
            $meal = $meal_stmt->fetch();
            if ($meal) {
                $total += $meal['PRICE'] * $quantity;
            }
        }

        // Insert each meal as a separate order record
        $insert_order = $pdo->prepare("INSERT INTO `order` (id_clients, id_meals, total_amount, delivery_address, quantity, date_order) VALUES (?, ?, ?, ?, ?, NOW())");

        foreach ($_SESSION['cart'] as $meal_id => $quantity) {
            $meal_stmt = $pdo->prepare("SELECT PRICE FROM MEALS WHERE ID_MEALS = ?");
            $meal_stmt->execute([$meal_id]);
            $meal = $meal_stmt->fetch();
            if ($meal) {
                $meal_total = $meal['PRICE'] * $quantity;
                $insert_order->execute([$client_id, $meal_id, $meal_total, $location, $quantity]);
            }
        }

        // Clear cart
        $_SESSION['cart'] = [];

        $success_message = "Order submitted successfully! Total: $" . number_format($total, 2);

    } catch (PDOException $e) {
        $error_message = "Error submitting order: " . $e->getMessage();
    }
}

// Get cart items with meal details
$cart_items = [];
$cart_total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $meal_id => $quantity) {
        try {
            $stmt = $pdo->prepare("SELECT m.*, c.NAME as category_name FROM MEALS m
                                  INNER JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
                                  WHERE m.ID_MEALS = ?");
            $stmt->execute([$meal_id]);
            $meal = $stmt->fetch();

            if ($meal) {
                $meal['quantity'] = $quantity;
                $meal['subtotal'] = $meal['PRICE'] * $quantity;
                $cart_items[] = $meal;
                $cart_total += $meal['subtotal'];
            }
        } catch (PDOException $e) {
            // Skip invalid items
        }
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=person" />
    <link rel="icon" href="/sushiyozar.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <title>Sushiyouzar - Cart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0a0a;
            color: #ffffff;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .cart-title {
            text-align: center;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 40px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .cart-title .highlight {
            color: #ff6b35;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 40px;
        }

        .cart-items {
            background: #1a1a1a;
            border-radius: 15px;
            padding: 30px;
            border: 2px solid #333;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #333;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #ff6b35;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .item-category {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .item-price {
            color: #ff6b35;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .item-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            background: #ff6b35;
            color: #ffffff;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .qty-btn:hover {
            background: #e55a2b;
            transform: scale(1.1);
        }

        .qty-input {
            width: 50px;
            text-align: center;
            background: #333;
            color: #ffffff;
            border: 1px solid #555;
            border-radius: 5px;
            padding: 5px;
        }

        .remove-btn {
            background: #dc3545;
            color: #ffffff;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .checkout-section {
            background: #1a1a1a;
            border-radius: 15px;
            padding: 30px;
            border: 2px solid #333;
            height: fit-content;
        }

        .checkout-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 20px;
            text-align: center;
        }

        .total-section {
            background: #333;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-row.final {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b35;
            border-top: 1px solid #555;
            padding-top: 10px;
            margin-top: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #ffffff;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background: #333;
            color: #ffffff;
            border: 1px solid #555;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .submit-btn {
            width: 100%;
            background: #ff6b35;
            color: #ffffff;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        .submit-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: #1a1a1a;
            border-radius: 15px;
            border: 2px solid #333;
        }

        .empty-cart h3 {
            color: #ffffff;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .empty-cart p {
            color: #cccccc;
            margin-bottom: 25px;
        }

        .continue-shopping {
            background: #ff6b35;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .continue-shopping:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }

        .success-message, .error-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .success-message {
            background: #28a745;
            color: #ffffff;
        }

        .error-message {
            background: #dc3545;
            color: #ffffff;
        }

        .client-type-info {
            background: #333;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ff6b35;
        }

        .client-type-info h4 {
            color: #ff6b35;
            margin-bottom: 10px;
        }

        .client-type-info p {
            color: #cccccc;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .cart-title {
                font-size: 2rem;
            }

            .cart-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .item-controls {
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .cart-container {
                padding: 20px 15px;
            }

            .cart-items, .checkout-section {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="cart-container">
        <h1 class="cart-title">
            Your <span class="highlight">Cart</span>
        </h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h3>Your cart is empty</h3>
                <p>Add some delicious meals to your cart to get started!</p>
                <a href="menu.php" class="continue-shopping">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <!-- Cart Items Section -->
                <div class="cart-items">
                    <h2 style="color: #ffffff; margin-bottom: 20px;">Order Items</h2>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo !empty($item['IMAGE_URL']) ? htmlspecialchars($item['IMAGE_URL']) : 'assets/sushi-1.png'; ?>"
                                 alt="<?php echo htmlspecialchars($item['NAME']); ?>"
                                 class="item-image"
                                 onerror="this.src='asset/testing.jpg'" />

                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['NAME']); ?></div>
                                <div class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <div class="item-price">$<?php echo number_format($item['PRICE'], 2); ?> each</div>
                            </div>

                            <div class="item-controls">
                                <div class="quantity-controls">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="meal_id" value="<?php echo $item['ID_MEALS']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                        <button type="submit" name="update_quantity" class="qty-btn">-</button>
                                    </form>

                                    <input type="number" value="<?php echo $item['quantity']; ?>"
                                           class="qty-input" min="1"
                                           onchange="updateQuantity(<?php echo $item['ID_MEALS']; ?>, this.value)">

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="meal_id" value="<?php echo $item['ID_MEALS']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                        <button type="submit" name="update_quantity" class="qty-btn">+</button>
                                    </form>
                                </div>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="meal_id" value="<?php echo $item['ID_MEALS']; ?>">
                                    <button type="submit" name="remove_from_cart" class="remove-btn">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Checkout Section -->
                <div class="checkout-section">
                    <h2 class="checkout-title">Checkout</h2>

                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Delivery:</span>
                            <span>Free</span>
                        </div>
                        <div class="total-row final">
                            <span>Total:</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                    </div>

                    <form method="POST" id="checkoutForm">
                        <div class="client-type-info">
                            <h4>Customer Information</h4>
                            <p>New customers: Fill all fields below. Existing customers: Only name, phone, and location required.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="name">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="location">Delivery Address *</label>
                            <textarea id="location" name="location" class="form-input" rows="3" required></textarea>
                        </div>

                        <button type="submit" name="submit_order" class="submit-btn">
                            Place Order - $<?php echo number_format($cart_total, 2); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update quantity function
        function updateQuantity(mealId, quantity) {
            if (quantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    removeFromCart(mealId);
                }
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="meal_id" value="${mealId}">
                <input type="hidden" name="quantity" value="${quantity}">
                <input type="hidden" name="update_quantity" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Remove from cart function
        function removeFromCart(mealId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="meal_id" value="${mealId}">
                <input type="hidden" name="remove_from_cart" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Check if client exists by phone
        let checkTimeout;
        document.getElementById('phone').addEventListener('input', function() {
            clearTimeout(checkTimeout);
            const phone = this.value.trim();

            if (phone.length >= 8) {
                checkTimeout = setTimeout(() => {
                    checkExistingClient(phone);
                }, 500);
            }
        });

        function checkExistingClient(phone) {
            fetch('check_client.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'phone=' + encodeURIComponent(phone)
            })
            .then(response => response.json())
            .then(data => {
                const emailField = document.getElementById('email');
                const passwordField = document.getElementById('password');
                const infoDiv = document.querySelector('.client-type-info');

                if (data.exists) {
                    emailField.required = false;
                    passwordField.required = false;
                    emailField.style.opacity = '0.5';
                    passwordField.style.opacity = '0.5';
                    infoDiv.innerHTML = `
                        <h4 style="color: #28a745;">Existing Customer Found!</h4>
                        <p>Welcome back! Just confirm your name and delivery address.</p>
                    `;
                } else {
                    emailField.required = true;
                    passwordField.required = true;
                    emailField.style.opacity = '1';
                    passwordField.style.opacity = '1';
                    infoDiv.innerHTML = `
                        <h4>New Customer</h4>
                        <p>Please fill all fields to create your account.</p>
                    `;
                }
            })
            .catch(error => {
                console.log('Error checking client:', error);
            });
        }

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!name || !phone || !location) {
                e.preventDefault();
                alert('Please fill in all required fields (Name, Phone, Location)');
                return;
            }

            if (phone.length < 8) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return;
            }
        });

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>