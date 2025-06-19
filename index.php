<?php
include 'includes/config.php';
include 'includes/functions.php';

// Initialize variables with empty arrays to prevent errors
$categories = [];
$featured_meals = [];
$offers = [];
$restaurants = [];

try {
    // Get categories for menu section
    $categories = getCategories($pdo);

    // Get featured meals (first 6 meals)
    $featured_meals = $pdo->query("SELECT * FROM MEALS LIMIT 6")->fetchAll();

    // Get offers
    $offers = getOffers($pdo);

    // Get restaurants for reservation
    $restaurants = getRestaurants($pdo);

} catch (PDOException $e) {
    // If database queries fail, show a setup message
    $database_error = true;
    $error_message = $e->getMessage();
}

include 'includes/header.php';

// If there's a database error, show setup instructions
if (isset($database_error)) {
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-warning">';
    echo '<h4>Database Setup Required</h4>';
    echo '<p>It looks like the database tables haven\'t been created yet. Please run the setup first:</p>';
    echo '<p><a href="simple_setup.php" class="btn btn-primary">Run Database Setup</a></p>';
    echo '<p><small>Error: ' . htmlspecialchars($error_message) . '</small></p>';
    echo '</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=person" />
    <link rel="icon" href="/sushiyozar.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <title>Sushiyouzar</title>
</head>

<body>
    <section class="about">
        <div class="about__title">
            <h2>ABOUT US</h2>
        </div>

        <div class="about__content">
            <div class="content__img">
                <img src="assets/helti.jpg" alt="sushi">
            </div>

            <div class="content__script">
                <h3>
                    We provide<br /> <span class="highlight">Healthy Food</span>
                </h3>
                <p>
                    Food For Us Comes From Our Relatives Whether They Have Wings Or Fins Or Roots That Is How We
                    Consider Food. Food Has A Culture. It Has History. It Has A Story. It Has Relationships.
                </p>
            </div>
        </div>

        <div class="button">
            <div class="booking-button">
                <div class="text-wrapper-8">BOOK A TABLE</div>
            </div>
            <div class="ordering-button">
                <div class="text-wrapper-9">Contact Us</div>
            </div>
        </div>
    </section>

    <section class="menu">
        <div class="menu__script">
            <div class="menu__title">
                <h2>MENU</h2>
            </div>
            <div class="menu__descriptiont">
                <h3> <span class="highlight">Explore</span> Our Foods</h3>
                <p>
                    Lorem ipsum dolor sit amet consectetur. Dolor elit vitae nunc varius. Facilisis eget cras sit semper
                    sit enim. Turpis aliquet at ac eu donec ut. Sagittis vestibulum at quis non massa netus.
                </p>
            </div>
        </div>

        <div class="menu__sliders">
            <div class="menu__arrow" id="prevBtn">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="white" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>

            <div class="slider-viewport">
                <div class="categories-container" id="categoriesContainer">
                    <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <div class="menu__categories">
                        <div class="categories__img">
                            <img src="https://c.animaapp.com/mbcpgwidQDBZ5R/img/rectangle-670-2.svg" alt="Sushi" />
                        </div>
                        <div class="categories__card">
                            <div class="categories__script">
                                <h4><?php echo htmlspecialchars($category['NAME']); ?></h4>
                                <p>Time: 10 - 15 Minutes | Serves: 1</p>
                                <button class="categories__order-btn"><a href="menu.php">Order Now</a></button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-5">
                                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No categories available yet</h5>
                                <p class="text-muted">Please run the database setup to add sample categories.</p>
                                <a href="simple_setup.php" class="btn btn-primary">Setup Database</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="menu__arrow" id="nextBtn">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18L15 12L9 6" stroke="white" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </div>
    </section>

    <section class="location">
        <div class="location__script">
            <div class="location__title">
                <h2>LOCATION</h2>
            </div>
            <div class="location__descriptiont">
                <h3>Find Us & Taste <span class="highlight">the Experience</span></h3>
                <p>
                    Visit our sushi spot or check if we deliver to your area — we're never too far when cravings call.
                </p>
                <h5>Booking request <span class="highlight"><a
                            href="https://wa.me/+212636889433">+2126-36889433</a></span> or fill the order form</h5>
            </div>
        </div>

        <div class="location__slider-container">
            <div class="location__sliders" id="locationSlider">
                <!-- Slide 1: Rabat -->
                <div class="location__slide">
                    <div class="google-map__location" style="background-image: url('assets/location-map.jpg');">
                        <div class="google__icon">
                            <p><span class="highlight">G</span>oogle</p>
                        </div>
                    </div>
                    <div class="content__location">
                        <div class="restaurant__img">
                            <img src="assets/rabat-restaut.jpg" alt="Restaurant Rabat">
                        </div>
                        <div class="restaurant__title">
                            <h3><span class="highlight">R</span>abat</h3>
                            <p>HAY RIAD</p>
                        </div>
                        <div class="restaurant__script">
                            <h3>Opening time</h3>
                            <p>
                                Monday to Saturday <br>
                                10:00 am - 23:00 pm
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Casablanca -->
                <div class="location__slide">
                    <div class="google-map__location" style="background-image: url('assets/location-map.jpg');">
                        <div class="google__icon">
                            <p><span class="highlight">G</span>oogle</p>
                        </div>
                    </div>
                    <div class="content__location">
                        <div class="restaurant__img">
                            <img src="assets/rabat-restaut.jpg" alt="Restaurant Casablanca">
                        </div>
                        <div class="restaurant__title">
                            <h3><span class="highlight">R</span>abat</h3>
                            <p>TEMARA</p>
                        </div>
                        <div class="restaurant__script">
                            <h3>Opening time</h3>
                            <p>
                                Monday to Sunday <br>
                                11:00 am - 24:00 pm
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="slider__indicators">
            <div class="indicator active" onclick="goToSlide(0)"></div>
            <div class="indicator" onclick="goToSlide(1)"></div>
        </div>
    </section>

    <section class="reviews">
        <div class="reviews__script">
            <div class="reviews__title">
                <h2>REVIEWS</h2>
            </div>
            <div class="reviews__descriptiont">
                <h3>Client <span class="highlight">experiences</span></h3>
                <h5>Booking request <span class="highlight"><a
                            href="https://wa.me/+212636889433">+2126-36889433</a></span> or fill the order form</h5>
            </div>
        </div>

        <div class="reviews__slider-container">
            <div class="reviews__sliders" id="slider">
                <div class="reviews__slide active">
                    <div class="slide__title">
                        <div class="title__icon">S</div>
                        <h3><span class="highlight">S</span>oufian</h3>
                    </div>
                    <div class="slide__description">
                        <p>Amazing sushi experience! The vinegar rice was perfectly seasoned and the salmon was
                            incredibly fresh. Definitely coming back for more!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">A</div>
                        <h3><span class="highlight">A</span>mina</h3>
                    </div>
                    <div class="slide__description">
                        <p>Best sushi in town! The combination of avocado and raw salmon is perfect. The presentation
                            was beautiful and service was excellent.</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">M</div>
                        <h3><span class="highlight">M</span>ohamed</h3>
                    </div>
                    <div class="slide__description">
                        <p>Outstanding quality and taste! The nori sheet was crispy and fresh. This place exceeded all
                            my expectations. Highly recommended!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">F</div>
                        <h3><span class="highlight">F</span>atima</h3>
                    </div>
                    <div class="slide__description">
                        <p>Incredible flavors and perfect texture! The sesame seeds added the perfect crunch. Will
                            definitely order again soon!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">Y</div>
                        <h3><span class="highlight">Y</span>oussef</h3>
                    </div>
                    <div class="slide__description">
                        <p>Fresh ingredients and authentic taste! The chef really knows how to prepare perfect sushi.
                            Amazing dining experience overall.</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">L</div>
                        <h3><span class="highlight">L</span>aila</h3>
                    </div>
                    <div class="slide__description">
                        <p>Absolutely delicious! The balance of flavors is perfect. Great attention to detail and
                            excellent customer service.</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">K</div>
                        <h3><span class="highlight">K</span>arim</h3>
                    </div>
                    <div class="slide__description">
                        <p>Top quality sushi with fresh salmon and perfect rice preparation. The booking process was
                            smooth and delivery was quick!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">N</div>
                        <h3><span class="highlight">N</span>adia</h3>
                    </div>
                    <div class="slide__description">
                        <p>Exceptional sushi experience! Every bite was a burst of flavor. The presentation was
                            Instagram-worthy and taste was even better!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>

                <div class="reviews__slide">
                    <div class="slide__title">
                        <div class="title__icon">N</div>
                        <h3><span class="highlight">N</span>adia</h3>
                    </div>
                    <div class="slide__description">
                        <p>Exceptional sushi experience! Every bite was a burst of flavor. The presentation was
                            Instagram-worthy and taste was even better!</p>
                    </div>
                    <div class="slide__rating">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="reviews-mydots" id="dots-container">
            <!-- Dots will be generated by JavaScript -->
        </div>
    </section>

    <script type="module" src="js/script.js"></script>
</body>

</html>
<?php include 'includes/footer.php'; ?>