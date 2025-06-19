class MenuSlider {
    constructor() {
        this.container = document.getElementById('categoriesContainer');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        this.categories = document.querySelectorAll('.menu__categories');
        this.currentIndex = 0;
        this.itemsPerView = this.getItemsPerView();

        this.init();
        this.updateButtons();
    }

    init() {
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());
        window.addEventListener('resize', () => this.handleResize());
    }

    getItemsPerView() {
        const width = window.innerWidth;
        if (width > 900) return 3;
        if (width > 550) return 2;
        return 1;
    }

    handleResize() {
        const newItemsPerView = this.getItemsPerView();
        if (newItemsPerView !== this.itemsPerView) {
            this.itemsPerView = newItemsPerView;
            const maxIndex = Math.max(0, this.categories.length - this.itemsPerView);
            if (this.currentIndex > maxIndex) {
                this.currentIndex = maxIndex;
            }
            this.updateSlider();
            this.updateButtons();
        }
    }

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateSlider();
            this.updateButtons();
        }
    }

    next() {
        const maxIndex = Math.max(0, this.categories.length - this.itemsPerView);
        if (this.currentIndex < maxIndex) {
            this.currentIndex++;
            this.updateSlider();
            this.updateButtons();
        }
    }

    updateSlider() {
        const categoryWidth = this.categories[0].offsetWidth;
        const gap = this.getGap();
        const translateX = -(this.currentIndex * (categoryWidth + gap));
        this.container.style.transform = `translateX(${translateX}px)`;
    }

    getGap() {
        const width = window.innerWidth;
        if (width > 900) return 40;
        if (width > 550) return 30;
        return 20;
    }

    updateButtons() {
        const maxIndex = Math.max(0, this.categories.length - this.itemsPerView);

        if (this.currentIndex === 0) {
            this.prevBtn.classList.add('disabled');
        } else {
            this.prevBtn.classList.remove('disabled');
        }

        if (this.currentIndex >= maxIndex) {
            this.nextBtn.classList.add('disabled');
        } else {
            this.nextBtn.classList.remove('disabled');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
  new MenuSlider();
});

document.addEventListener('click', (e) => {
  if (e.target.closest('.categories__order-btn')) {
    const categoryName = e.target.closest('.menu__categories').querySelector('h4').textContent;
    alert(`Order placed for ${categoryName}!`);
  }
});

// document.addEventListener('DOMContentLoaded', () => {
//     new MenuSlider();
// });

// document.addEventListener('click', (e) => {
//     if (e.target.closest('.categories__order-btn')) {
//         const categoryName = e.target.closest('.menu__categories').querySelector('h4').textContent;
//         alert(`Order placed for ${categoryName}!`);
//     }
// });