<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=person" />
    <link rel="icon" href="/sushiyozar.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <title>Sushiyouzar</title>
</head>

<body>
    <section>
        <header>
            <nav class="header__nav">
                <div class="header__logo">
                    <div class="header__logo-overlay">
                        <p>S</p>
                    </div>
                    <h4>ushi youzar</h4>
                </div>

                <ul class="header__menu">
                    <li>
                        <a href="index.php" class="active">Home</a>
                    </li>
                    <li>
                        <a href="menu.php">Menu</a>
                    </li>
                    <li>
                        <a href="#reservation">Reservation</a>
                    </li>
                    <li>
                        <a href="#promotions">Promotions</a>
                    </li>
                    <li>
                        <a href="#services">Services</a>
                    </li>
                </ul>

                <div class="header__account_button">
                    <div class="header__account-icon">
                        <a href="#account">
                            <span class="material-symbols-outlined">person</span>
                        </a>
                    </div>
                    <div class="header__button">
                        <button class="header__button-order"><a href="menu.php">Order now</a> </button>
                    </div>
                </div>

                <div class="header__menu-mobile">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </nav>
            <!-- Mobile Menu Overlay -->
            <div class="mobile-menu-overlay" id="mobileMenuOverlay">
                <ul>
                    <li><a href="#home" onclick="closeMobileMenu()">Home</a></li>
                    <li><a href="#menu" onclick="closeMobileMenu()">Menu</a></li>
                    <li><a href="#reservation" onclick="closeMobileMenu()">Reservation</a></li>
                    <li><a href="#promotions" onclick="closeMobileMenu()">Promotions</a></li>
                    <li><a href="#services" onclick="closeMobileMenu()">Services</a></li>
                </ul>

                <div class="mobile-account-section">
                    <div class="mobile-account-icon">
                        <a href="#account" onclick="closeMobileMenu()">
                            <span class="material-symbols-outlined">person</span>
                        </a>
                    </div>
                    <button class="mobile-order-button" onclick="closeMobileMenu()">Order now</button>
                </div>
            </div>
        </header>

        <div class="hero-slides">
            <div class="slide active" style="background-image: url('asset/slider-1.jpg')">
                <section class="main">
                    <div class="div-4">
                        <div class="secondary-title">
                            <img class="img" src="https://c.animaapp.com/mbcpgwidQDBZ5R/img/line-3.svg" />
                            <div class="text-wrapper-6">HELLO, NEW FRIEND</div>
                        </div>
                        <div class="text-wrapper-7">RESERVE YOUR TABLE</div>
                    </div>
                    <div class="buttons">
                        <div class="booking-button">
                            <div class="text-wrapper-8">BOOK A TABLE</div>
                        </div>
                        <div class="ordering-button">
                            <div class="text-wrapper-9">Order now</div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="slide" style="background-image: url('asset/slider-2.jpg')">
                <section class="main">
                    <div class="div-4">
                        <div class="secondary-title">
                            <img class="img" src="https://c.animaapp.com/mbcpgwidQDBZ5R/img/line-3.svg" />
                            <div class="text-wrapper-6">HELLO, NEW FRIEND</div>
                        </div>
                        <div class="text-wrapper-7">RESERVE YOUR TABLE</div>
                    </div>
                    <div class="buttons">
                        <div class="booking-button">
                            <div class="text-wrapper-8">BOOK A TABLE</div>
                        </div>
                        <div class="ordering-button">
                            <div class="text-wrapper-9">Order now</div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="slide" style="background-image: url('asset/slider-3.jpg')">
                <section class="main">
                    <div class="div-4">
                        <div class="secondary-title">
                            <img class="img" src="https://c.animaapp.com/mbcpgwidQDBZ5R/img/line-3.svg" />
                            <div class="text-wrapper-6">HELLO, NEW FRIEND</div>
                        </div>
                        <div class="text-wrapper-7">RESERVE YOUR TABLE</div>
                    </div>
                    <div class="buttons">
                        <div class="booking-button">
                            <div class="text-wrapper-8">BOOK A TABLE</div>
                        </div>
                        <div class="ordering-button">
                            <div class="text-wrapper-9">Order now</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button class="prev">&#10094;</button>
        <button class="next">&#10095;</button>
    </section>
</body>

</html>