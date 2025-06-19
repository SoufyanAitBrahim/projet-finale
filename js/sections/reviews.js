        class ReviewsSlider {
            constructor() {
                this.slider = document.getElementById('slider');
                this.dotsContainer = document.getElementById('dots-container');
                this.slides = Array.from(document.querySelectorAll('.reviews__slide'));
                this.currentIndex = 0;
                this.autoSlideInterval = null;
                this.slidesPerView = this.getSlidesPerView();
                this.totalSlides = this.slides.length;
                
                this.init();
                this.setupEventListeners();
                this.startAutoSlide();
            }

            getSlidesPerView() {
                const width = window.innerWidth;
                if (width > 900) return 5;
                if (width > 550) return 3;
                return 2;
            }

            init() {
                this.createDots();
                this.updateSlider();
            }

            createDots() {
                this.dotsContainer.innerHTML = '';
                const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
                
                for (let i = 0; i <= maxIndex; i++) {
                    const dot = document.createElement('div');
                    dot.classList.add('mydots');
                    if (i === this.currentIndex) {
                        dot.classList.add('active');
                    }
                    dot.addEventListener('click', () => this.goToSlide(i));
                    this.dotsContainer.appendChild(dot);
                }
            }

            updateSlider() {
                // Update slide positions
                const slideWidth = 100 / this.slidesPerView;
                const translateX = -this.currentIndex * slideWidth;
                this.slider.style.transform = `translateX(${translateX}%)`;

                // Update active states
                this.slides.forEach((slide, index) => {
                    slide.classList.remove('active');
                    const slideCenter = this.currentIndex + Math.floor(this.slidesPerView / 2);
                    if (index === slideCenter) {
                        slide.classList.add('active');
                    }
                });

                // Update dots
                const dots = this.dotsContainer.querySelectorAll('.mydots');
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === this.currentIndex);
                });
            }

            nextSlide() {
                const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
                this.currentIndex = this.currentIndex >= maxIndex ? 0 : this.currentIndex + 1;
                this.updateSlider();
            }

            goToSlide(index) {
                this.currentIndex = index;
                this.updateSlider();
                this.resetAutoSlide();
            }

            startAutoSlide() {
                this.autoSlideInterval = setInterval(() => {
                    this.nextSlide();
                }, 3000);
            }

            stopAutoSlide() {
                if (this.autoSlideInterval) {
                    clearInterval(this.autoSlideInterval);
                    this.autoSlideInterval = null;
                }
            }

            resetAutoSlide() {
                this.stopAutoSlide();
                this.startAutoSlide();
            }

            handleResize() {
                const newSlidesPerView = this.getSlidesPerView();
                if (newSlidesPerView !== this.slidesPerView) {
                    this.slidesPerView = newSlidesPerView;
                    const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
                    this.currentIndex = Math.min(this.currentIndex, maxIndex);
                    this.createDots();
                    this.updateSlider();
                }
            }

            setupEventListeners() {
                // Pause auto-slide on hover
                // this.slider.addEventListener('mouseenter', () => this.stopAutoSlide());
                // this.slider.addEventListener('mouseleave', () => this.startAutoSlide());

                // Handle window resize
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => this.handleResize(), 150);
                });

                // Touch/swipe support for mobile
                let startX = 0;
                let isDragging = false;

                this.slider.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                    this.stopAutoSlide();
                });

                this.slider.addEventListener('touchmove', (e) => {
                    if (!isDragging) return;
                    e.preventDefault();
                });

                this.slider.addEventListener('touchend', (e) => {
                    if (!isDragging) return;
                    
                    const endX = e.changedTouches[0].clientX;
                    const diff = startX - endX;
                    
                    if (Math.abs(diff) > 50) {
                        if (diff > 0) {
                            this.nextSlide();
                        } else {
                            const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
                            this.currentIndex = this.currentIndex <= 0 ? maxIndex : this.currentIndex - 1;
                            this.updateSlider();
                        }
                    }
                    
                    isDragging = false;
                    this.resetAutoSlide();
                });
            }
        }

        // Initialize slider when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new ReviewsSlider();
        });