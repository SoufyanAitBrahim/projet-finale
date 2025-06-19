<?php
include 'includes/config.php';
include 'includes/functions.php';

// Get meal ID from URL parameter
$meal_id = isset($_GET['meal']) ? intval($_GET['meal']) : 0;

if ($meal_id <= 0) {
    header('Location: menu.php');
    exit;
}

try {
    // Get the selected meal with category information
    $sql = "SELECT m.*, c.NAME as category_name
            FROM MEALS m
            INNER JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
            WHERE m.ID_MEALS = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meal_id]);
    $selected_meal = $stmt->fetch();

    if (!$selected_meal) {
        header('Location: menu.php');
        exit;
    }

    // Get related meals from the same category (excluding the selected meal)
    $related_sql = "SELECT m.*, c.NAME as category_name
                    FROM MEALS m
                    INNER JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
                    WHERE m.ID_CATEGORIES = ? AND m.ID_MEALS != ?
                    ORDER BY m.ID_MEALS
                    LIMIT 6";

    $related_stmt = $pdo->prepare($related_sql);
    $related_stmt->execute([$selected_meal['ID_CATEGORIES'], $meal_id]);
    $related_meals = $related_stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = $e->getMessage();
    header('Location: menu.php');
    exit;
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
    <title>Order - <?php echo htmlspecialchars($selected_meal['NAME']); ?> | Sushiyouzar</title>

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

        .order-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .main-meal-section {
            display: flex;
            gap: 40px;
            margin-bottom: 60px;
            align-items: center;
        }

        .meal-image-container {
            flex: 1;
            max-width: 450px;
            padding: 20px;
        }

        .meal-image {
            width: 100%;
            height: 350px;
            border-radius: 15px;
            border: 3px solid #ff6b35;
            object-fit: cover;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
            
        }

        .meal-details {
            flex: 1;
            padding-left: 20px;
        }

        .meal-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .meal-title .highlight {
            color: #ff6b35;
        }

        .meal-ingredients {
            margin-bottom: 30px;
        }

        .meal-ingredients h4 {
            color: #ffffff;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .ingredients-list {
            list-style: none;
            padding: 0;
        }

        .ingredients-list li {
            color: #cccccc;
            margin-bottom: 5px;
            position: relative;
            padding-left: 15px;
        }

        .ingredients-list li:before {
            content: "•";
            color: #ff6b35;
            position: absolute;
            left: 0;
        }

        .meal-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.4rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #ff6b35;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #ff6b35;
            /* border: 2px solid #ff6b35; */
        }

        .btn-secondary:hover {
            background: #ff6b35;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .explore-section {
            margin-top: 60px;
        }

        .section-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 40px;
            color: #ffffff;
        }

        .section-title .highlight {
            color: #ff6b35;
        }

        .related-meals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .meal-card {
            background: #1a1a1a;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .meal-card:hover {
            transform: translateY(-5px);
            border-color: #ff6b35;
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.2);
        }

        .meal-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .meal-card-content {
            padding: 20px;
        }

        .meal-card-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .meal-card-price {
            color: #ff6b35;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .meal-card-btn {
            width: 100%;
            padding: 12px;
            background: #ff6b35;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .meal-card-btn:hover {
            background: #e55a2b;
        }

        .see-more-container {
            text-align: center;
            margin-top: 40px;
        }

        .see-more-btn {
            background: transparent;
            color: #ff6b35;
            border: 2px solid #ff6b35;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .see-more-btn:hover {
            background: #ff6b35;
            color: #ffffff;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .main-meal-section {
                flex-direction: column;
                text-align: center;
            }

            .meal-details {
                padding-left: 0;
                padding-top: 20px;
            }

            .meal-title {
                font-size: 2rem;
            }

            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }

            .related-meals-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="order-container">
        <!-- Main Meal Section -->
        <div class="main-meal-section">
            <div class="meal-image-container">
                <img src="<?php echo !empty($selected_meal['IMAGE_URL']) ? htmlspecialchars($selected_meal['IMAGE_URL']) : 'assets/testing.jpg'; ?>"
                     alt="<?php echo htmlspecialchars($selected_meal['NAME']); ?>"
                     class="meal-image"
                     onerror="this.src='asset/testing.jpg'" />
            </div>

            <div class="meal-details">
                <h1 class="meal-title">
                    <?php
                    $name_parts = explode(' ', $selected_meal['NAME']);
                    if (count($name_parts) > 1) {
                        echo htmlspecialchars($name_parts[0]) . ' <span class="highlight">' . htmlspecialchars(implode(' ', array_slice($name_parts, 1))) . '</span>';
                    } else {
                        echo '<span class="highlight">' . htmlspecialchars($selected_meal['NAME']) . '</span>';
                    }
                    ?>
                </h1>

                <div class="meal-ingredients">
                    <h4>Description:</h4>
                    <ul class="ingredients-list">
                        <?php
                        $description_parts = explode('.', $selected_meal['DESCRIPTION']);
                        foreach ($description_parts as $part) {
                            $part = trim($part);
                            if (!empty($part)) {
                                echo '<li>' . htmlspecialchars($part) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>

                <div class="meal-price">
                    $<?php echo number_format($selected_meal['PRICE'], 2); ?>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="addToCart(<?php echo $selected_meal['ID_MEALS']; ?>)">
                        Add to cart
                    </button>
                    <a href="menu.php" class="btn btn-secondary">
                        Continue
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Meals Section -->
        <?php if (!empty($related_meals)): ?>
        <div class="explore-section">
            <h2 class="section-title">
                Explore our <span class="highlight">menu</span>
            </h2>

            <div class="related-meals-grid" id="relatedMealsGrid">
                <?php foreach ($related_meals as $index => $meal): ?>
                <div class="meal-card" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <img src="<?php echo !empty($meal['IMAGE_URL']) ? htmlspecialchars($meal['IMAGE_URL']) : 'asset/testing.jpg'; ?>"
                         alt="<?php echo htmlspecialchars($meal['NAME']); ?>"
                         class="meal-card-image"
                         onerror="this.src='asset/testing.jpg'" />

                    <div class="meal-card-content">
                        <h3 class="meal-card-title"><?php echo htmlspecialchars($meal['NAME']); ?></h3>
                        <div class="meal-card-price">$<?php echo number_format($meal['PRICE'], 2); ?></div>
                        <button class="meal-card-btn" onclick="orderMeal(<?php echo $meal['ID_MEALS']; ?>)">
                            Order Now
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="see-more-container">
                <button class="see-more-btn" onclick="window.location.href='menu.php'">
                    See More
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add to cart functionality
        function addToCart(mealId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            form.innerHTML = `
                <input type="hidden" name="meal_id" value="${mealId}">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="add_to_cart" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Order meal functionality
        function orderMeal(mealId) {
            window.location.href = 'order.php?meal=' + mealId;
        }

        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.meal-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>