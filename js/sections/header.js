// Toggle mobile menu function
export function toggleMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.getElementById('mobileMenuOverlay');

    hamburger.classList.toggle('active');
    overlay.classList.toggle('active');

    // Prevent body scroll when menu is open
    if (overlay.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
}

// Close mobile menu function
export function closeMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.getElementById('mobileMenuOverlay');

    hamburger.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Handle active menu state for desktop menu
document.addEventListener('DOMContentLoaded', function () {
    const menuLinks = document.querySelectorAll('.header__menu a');

    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Remove active class from all links
            menuLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
        });
    });
});

// Close menu on escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeMobileMenu();
    }
});

// Close menu when clicking outside
document.getElementById('mobileMenuOverlay').addEventListener('click', function (e) {
    if (e.target === this) {
        closeMobileMenu();
    }
});

// Close mobile menu when resizing to desktop view
window.addEventListener('resize', function () {
    if (window.innerWidth > 900) {
        closeMobileMenu();
    }
});