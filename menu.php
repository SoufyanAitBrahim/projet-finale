<?php
include 'includes/config.php';
include 'includes/functions.php';

// Handle AJAX request for loading more meals
if (isset($_POST['load_more']) && isset($_POST['offset'])) {
    $offset = intval($_POST['offset']);

    try {
        // Get one meal from each category at the specified position (offset)
        $sql = "SELECT m.*, c.NAME as category_name
                FROM MEALS m
                INNER JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
                WHERE (
                    SELECT COUNT(*)
                    FROM MEALS m2
                    WHERE m2.ID_CATEGORIES = m.ID_CATEGORIES
                    AND m2.ID_MEALS <= m.ID_MEALS
                ) = ?
                ORDER BY m.ID_CATEGORIES";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$offset]);
        $meals = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($meals);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

try {
    // Get initial meals (first meal from each category)
    $sql = "SELECT m.*, c.NAME as category_name
            FROM MEALS m
            INNER JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
            WHERE (
                SELECT COUNT(*)
                FROM MEALS m2
                WHERE m2.ID_CATEGORIES = m.ID_CATEGORIES
                AND m2.ID_MEALS <= m.ID_MEALS
            ) = 1
            ORDER BY m.ID_CATEGORIES";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $initial_meals = $stmt->fetchAll();
    
    // Check if there are more meals available (check if any category has more than 1 meal)
    $check_more_sql = "SELECT COUNT(DISTINCT ID_CATEGORIES) as categories_with_more
                       FROM (
                           SELECT ID_CATEGORIES, COUNT(*) as meal_count
                           FROM MEALS 
                           GROUP BY ID_CATEGORIES
                           HAVING meal_count > 1
                       ) as cat_counts";
    
    $check_stmt = $pdo->prepare($check_more_sql);
    $check_stmt->execute();
    $has_more = $check_stmt->fetchColumn() > 0;
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
    $initial_meals = [];
    $has_more = false;
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=person" />
    <link rel="icon" href="/sushiyozar.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <title>Sushiyouzar - Menu</title>
    <style>
    body {
        background: #0a0a0a;
        color: #ffffff;
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
    }

    .menu {
        background: #0a0a0a;
        padding: 60px 0;
        min-height: 100vh;
    }

    .menu__script {
        text-align: center;
        margin-bottom: 50px;
    }

    .menu__title h2 {
        color: #ffffff;
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .menu__descriptiont h3 {
        color: #ffffff;
        font-size: 2rem;
        margin-bottom: 20px;
    }

    .highlight {
        color: #ff6b35;
    }

    .menu__descriptiont p {
        color: #cccccc;
        font-size: 1.1rem;
        max-width: 150vw;
        margin: 0 auto;
        line-height: 1.6;
    }

    .slider-viewport {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .categories-container {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 30px !important;
        margin-bottom: 40px !important;
        max-width: none !important;
    }

    .menu__categories {
        background: #1a1a1a !important;
        border-radius: 15px !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
        border: 2px solid transparent !important;
        width: auto !important;
        max-width: none !important;
    }

    .menu__categories:hover {
        transform: translateY(-10px);
        border-color: #ff6b35;
        box-shadow: 0 15px 35px rgba(255, 107, 53, 0.3);
    }

    .categorie__img {
        position: relative;
        overflow: hidden;
    }

    .categorie__img img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .categorie__card {
        border-radius: 20px;
        padding: 5px 20px;
        text-align: center;
        border-radius: 20px;
        border: 1px solid rgba(255, 112, 0, 0.1);

    }

    .categorie__card:hover {
        /* transform: scale(1.1); */
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 111, 0, 0.79);
    }

    .categories__script {
        padding: 0px 10px;
        text-align: center;
    }

    .categories__script h4 {
        color: #ffffff;
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .categories__script p {
        color: #cccccc;
        font-size: 0.9rem;
        margin-bottom: 10px;
        line-height: 1.4;
    }

    .categories__script p:last-of-type {
        color: #ff6b35;
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }

    .menu-bnt {
        margin-bottom: 10px;
    }

    .categories__order-btn:hover {
        background: #e55a2b;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    }

    .categories__order-btn a {
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        font-size: 1rem;

    }

    .see-more-container {
        text-align: center;
        margin: 60px 0 40px 0;
    }

    .see-more-btn {
        background: transparent;
        color: #ff6b35;
        border: 2px solid #ff6b35;
        padding: 15px 40px;
        border-radius: 30px;
        cursor: pointer;
        font-size: 1.1rem;
        font-weight: bold;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .see-more-btn:hover {
        background: #ff6b35;
        color: #ffffff;
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
    }

    .see-more-btn:disabled {
        background: #333;
        color: #666;
        border-color: #333;
        cursor: not-allowed;
        transform: none;
    }

    .loading {
        text-align: center;
        color: #ff6b35;
        font-size: 1.1rem;
        margin: 20px 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .error-message {
        text-align: center;
        color: #ff6b35;
        margin: 20px 0;
        padding: 20px;
        background: #2a1a1a;
        border: 1px solid #ff6b35;
        border-radius: 10px;
        max-width: 600px;
        margin: 20px auto;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .categories-container {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 25px !important;
        }

        .menu__categories {
            width: auto !important;
        }
    }

    @media (max-width: 768px) {
        .categories-container {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 20px !important;
        }

        .menu__categories {
            width: auto !important;
        }

        .menu__title h2 {
            font-size: 2.5rem;
        }

        .menu__descriptiont h3 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .categories-container {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }

        .menu__categories {
            width: auto !important;
        }

        .slider-viewport {
            padding: 0 15px;
        }
    }

    /* Override external CSS specifically */
    @media (min-width: 901px) {
        .menu__categories {
            width: auto !important;
        }
    }

    @media (max-width: 900px) {
        .menu__categories {
            width: auto !important;
        }
    }

    @media (max-width: 550px) {
        .menu__categories {
            width: auto !important;
        }
    }
    </style>
</head>

<body>
    <section class="menu">
        <div class="menu__script">
            <div class="menu__title">
                <h2>MENU</h2>
            </div>
            <div class="menu__descriptiont">
                <h3><span class="highlight">Explore</span> Our Foods</h3>
                <p>
                    Lorem ipsum dolor sit amet consectetur. Dolor elit vitae nunc varius. Facilisis eget cras sit semper
                    sit enim. Turpis aliquet at ac eu donec ut. Sagittis vestibulum at quis non massa netus.
                </p>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="error-message">
            Error loading menu: <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="slider-viewport">
            <div class="categories-container" id="categoriesContainer">
                <?php if (empty($initial_meals)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">No menu items available at the moment.</p>
                </div>
                <?php else: ?>
                <?php foreach ($initial_meals as $index => $meal): ?>
                <div class="menu__categories" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="categories__img">
                        <img src="<?php echo !empty($meal['IMAGE_URL']) ? htmlspecialchars($meal['IMAGE_URL']) : 'asset/testing.jpg'; ?>"
                            alt="<?php echo htmlspecialchars($meal['NAME']); ?>"
                            onerror="this.src='assets/testing.jpg'" />
                    </div>
                    <div class="categorie__card">
                        <div class="categories__script">
                            <h4><?php echo htmlspecialchars($meal['NAME']); ?></h4>
                            <p>10 - 15 Min </p>
                            <p><strong>$<?php echo number_format($meal['PRICE'], 2); ?></strong></p>
                            <button class="categories__order-btn menu-bnt">
                                <a href="order.php?meal=<?php echo $meal['ID_MEALS']; ?>">Order Now</a>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($has_more && !empty($initial_meals)): ?>
            <div class="see-more-container">
                <button class="see-more-btn" id="seeMoreBtn" onclick="loadMore()">
                    See More
                </button>
            </div>
            <?php endif; ?>

            <div class="loading" id="loading" style="display: none;">
                Loading more delicious meals... 🍱
            </div>
        </div>
    </section>

    <script>
    let currentOffset = 2; // Start from 2 since we already loaded the first meal from each category
    let isLoading = false;

    function loadMore() {
        if (isLoading) return;

        isLoading = true;
        document.getElementById('loading').style.display = 'block';
        document.getElementById('seeMoreBtn').disabled = true;

        // Create FormData for POST request
        const formData = new FormData();
        formData.append('load_more', '1');
        formData.append('offset', currentOffset);

        fetch('menu.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('categoriesContainer');

                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.length === 0) {
                    // No more meals to load
                    document.getElementById('seeMoreBtn').style.display = 'none';
                    document.getElementById('loading').innerHTML = 'That\'s all our delicious meals! 🎉';
                    setTimeout(() => {
                        document.getElementById('loading').style.display = 'none';
                    }, 2000);
                } else {
                    // Add new meals to the container
                    data.forEach((meal, index) => {
                        const mealElement = document.createElement('div');
                        mealElement.className = 'menu__categories';
                        mealElement.style.animationDelay = (index * 0.1) + 's';

                        const imageUrl = meal.IMAGE_URL ||
                            'https://c.animaapp.com/mbcpgwidQDBZ5R/img/rectangle-670-2.svg';

                        mealElement.innerHTML = `
                            <div class="categories__img">
                                <img src="${imageUrl}"
                                     alt="${meal.NAME}"
                                     onerror="this.src='asset/testing.jpg'" />
                            </div>
                            <div class="categorie__card">
                                <div class="categories__script">
                                    <h4>${meal.NAME}</h4>
                                    <p>10 - 15 Min </p>
                                    <p>$${parseFloat(meal.PRICE).toFixed(2)}</p>
                                    <button class="categories__order-btn menu-bnt">
                                        <a href="order.php?meal=${meal.ID_MEALS}">Order Now</a>
                                    </button>
                                </div>
                            </div>
                        `;

                        container.appendChild(mealElement);
                    });

                    currentOffset++;
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('seeMoreBtn').disabled = false;
                }

                isLoading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loading').innerHTML = 'Error loading meals. Please try again.';
                document.getElementById('seeMoreBtn').disabled = false;
                isLoading = false;

                setTimeout(() => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('seeMoreBtn').disabled = false;
                }, 3000);
            });
    }

    // Add smooth scrolling animation when new items are loaded
    function smoothScrollToNewItems() {
        const newItems = document.querySelectorAll('.menu__categories:last-child');
        if (newItems.length > 0) {
            newItems[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
    </script>
</body>

</html>

<?php include 'includes/footer.php'; ?>