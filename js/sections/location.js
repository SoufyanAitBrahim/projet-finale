let currentSlide = 0;
const totalSlides = 2;
const slider = document.getElementById('locationSlider');
const indicators = document.querySelectorAll('.indicator');
let autoSlideInterval;

function updateSlider() {
    const translateX = -currentSlide * 50; // 50% for each slide
    slider.style.transform = `translateX(${translateX}%)`;

    // Update indicators
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentSlide);
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateSlider();
}

function goToSlide(slideIndex) {
    currentSlide = slideIndex;
    updateSlider();
    resetAutoSlide();
}

function startAutoSlide() {
    autoSlideInterval = setInterval(nextSlide, 3000); // 3 seconds
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
}

// Initialize auto-slide
startAutoSlide();

// Pause auto-slide on hover
const sliderContainer = document.querySelector('.location__slider-container');
sliderContainer.addEventListener('mouseenter', () => {
    clearInterval(autoSlideInterval);
});

sliderContainer.addEventListener('mouseleave', () => {
    startAutoSlide();
});

// Initialize
updateSlider();