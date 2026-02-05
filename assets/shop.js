/**
 * Sapi Maison - Shop JavaScript
 * Mini cart, filters, variations, swatches
 */

(function() {
  'use strict';

  // =============================================
  // MINI CART
  // =============================================
  const miniCart = {
    panel: null,
    overlay: null,
    toggle: null,
    closeBtn: null,
    isOpen: false,

    init: function() {
      this.panel = document.getElementById('mini-cart');
      this.overlay = document.getElementById('mini-cart-overlay');
      this.toggle = document.querySelector('.mini-cart-toggle');
      this.closeBtn = document.querySelector('.mini-cart-close');

      if (!this.panel || !this.toggle) return;

      this.toggle.addEventListener('click', (e) => {
        e.preventDefault();
        this.open();
      });

      if (this.closeBtn) {
        this.closeBtn.addEventListener('click', () => this.close());
      }

      if (this.overlay) {
        this.overlay.addEventListener('click', () => this.close());
      }

      // Close on escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) {
          this.close();
        }
      });

      // Listen for WooCommerce cart updates
      document.body.addEventListener('added_to_cart', () => this.refresh());
      document.body.addEventListener('removed_from_cart', () => this.refresh());
      document.body.addEventListener('updated_cart_totals', () => this.refresh());
    },

    open: function() {
      if (!this.panel) return;

      this.isOpen = true;
      this.panel.classList.add('is-open');
      this.panel.setAttribute('aria-hidden', 'false');

      if (this.overlay) {
        this.overlay.classList.add('is-visible');
      }

      if (this.toggle) {
        this.toggle.setAttribute('aria-expanded', 'true');
      }

      document.body.style.overflow = 'hidden';
    },

    close: function() {
      if (!this.panel) return;

      this.isOpen = false;
      this.panel.classList.remove('is-open');
      this.panel.setAttribute('aria-hidden', 'true');

      if (this.overlay) {
        this.overlay.classList.remove('is-visible');
      }

      if (this.toggle) {
        this.toggle.setAttribute('aria-expanded', 'false');
      }

      document.body.style.overflow = '';
    },

    refresh: function() {
      // WooCommerce handles fragment updates via AJAX
      // This refreshes the cart count badge
      if (typeof wc_cart_fragments_params !== 'undefined') {
        jQuery(document.body).trigger('wc_fragment_refresh');
      }
    }
  };

  // =============================================
  // PRODUCT FILTERS (Animated)
  // =============================================
  const productFilters = {
    init: function() {
      const filterBtns = document.querySelectorAll('.filter-btn');
      if (!filterBtns.length) return;

      filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          // Allow navigation - just add visual feedback before navigating
          const currentActive = document.querySelector('.filter-btn.active');
          if (currentActive) {
            currentActive.classList.remove('active');
          }
          btn.classList.add('active');
        });
      });

      // Mark current category as active
      const currentPath = window.location.pathname;
      filterBtns.forEach(btn => {
        const href = btn.getAttribute('href');
        if (href && currentPath.includes(btn.dataset.filter) && btn.dataset.filter !== 'all') {
          document.querySelector('.filter-btn.active')?.classList.remove('active');
          btn.classList.add('active');
        }
      });
    }
  };

  // =============================================
  // VARIATION SWATCHES
  // =============================================
  const variationSwatches = {
    init: function() {
      const swatchContainers = document.querySelectorAll('.sapi-swatches');
      if (!swatchContainers.length) return;

      swatchContainers.forEach(container => {
        const buttons = container.querySelectorAll('.sapi-swatch-btn');
        const hiddenSelect = container.previousElementSibling;

        if (!hiddenSelect || hiddenSelect.tagName !== 'SELECT') return;

        buttons.forEach(btn => {
          btn.addEventListener('click', () => {
            // Remove active from siblings
            buttons.forEach(b => b.classList.remove('is-selected'));
            // Add active to clicked
            btn.classList.add('is-selected');
            // Update hidden select
            hiddenSelect.value = btn.dataset.value;
            // Trigger change event for WooCommerce
            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
          });
        });
      });
    }
  };

  // =============================================
  // DYNAMIC PRICE UPDATE
  // =============================================
  const dynamicPrice = {
    originalPrice: null,
    priceElement: null,

    init: function() {
      const form = document.querySelector('.variations_form');
      if (!form) return;

      this.priceElement = document.querySelector('.single-product-price .price, .product-price-display .price');
      if (this.priceElement) {
        this.originalPrice = this.priceElement.innerHTML;
      }

      // Listen for variation changes
      if (typeof jQuery !== 'undefined') {
        jQuery(form).on('found_variation', (event, variation) => {
          this.updatePrice(variation);
        });

        jQuery(form).on('reset_data', () => {
          this.resetPrice();
        });
      }
    },

    updatePrice: function(variation) {
      if (!this.priceElement || !variation.price_html) return;

      // Smooth transition
      this.priceElement.style.opacity = '0';
      this.priceElement.style.transform = 'translateY(-5px)';

      setTimeout(() => {
        this.priceElement.innerHTML = variation.price_html;
        this.priceElement.style.opacity = '1';
        this.priceElement.style.transform = 'translateY(0)';
      }, 150);
    },

    resetPrice: function() {
      if (!this.priceElement || !this.originalPrice) return;

      this.priceElement.style.opacity = '0';
      setTimeout(() => {
        this.priceElement.innerHTML = this.originalPrice;
        this.priceElement.style.opacity = '1';
      }, 150);
    }
  };

  // =============================================
  // PRODUCT GALLERY THUMBNAILS
  // =============================================
  const productGallery = {
    init: function() {
      const thumbnails = document.querySelectorAll('.product-thumbnails img, .flex-control-thumbs img');
      if (!thumbnails.length) return;

      thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
          // Remove active state from all
          thumbnails.forEach(t => t.classList.remove('is-active'));
          // Add to clicked
          this.classList.add('is-active');
        });
      });
    }
  };

  // =============================================
  // PRODUCT CARDS ANIMATION
  // =============================================
  const productCards = {
    init: function() {
      const cards = document.querySelectorAll('.product-card-cinetique');
      if (!cards.length) return;

      // Intersection Observer for scroll animations
      if ('IntersectionObserver' in window) {
        // First, add will-animate class to prepare for animation
        cards.forEach(card => card.classList.add('will-animate'));

        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              // Get the index from all observed cards for staggering
              const allCards = Array.from(cards);
              const index = allCards.indexOf(entry.target);

              // Staggered animation
              setTimeout(() => {
                entry.target.classList.add('is-visible');
              }, (index % 4) * 100); // Stagger within each row
              observer.unobserve(entry.target);
            }
          });
        }, {
          threshold: 0.1,
          rootMargin: '50px'
        });

        cards.forEach(card => observer.observe(card));
      }
      // No fallback needed - cards are visible by default now
    }
  };

  // =============================================
  // CATEGORY CAROUSEL
  // =============================================
  const categoryCarousel = {
    carousel: null,
    track: null,
    slides: [],
    prevBtn: null,
    nextBtn: null,
    dotsContainer: null,
    currentIndex: 0,
    slidesPerView: 3,
    touchStartX: 0,
    touchEndX: 0,

    init: function() {
      this.carousel = document.querySelector('[data-carousel]');
      if (!this.carousel) return;

      this.track = this.carousel.querySelector('.carousel-track');
      this.slides = this.carousel.querySelectorAll('.carousel-slide');
      this.prevBtn = document.querySelector('.carousel-prev');
      this.nextBtn = document.querySelector('.carousel-next');
      this.dotsContainer = document.querySelector('.carousel-dots');

      if (!this.track || this.slides.length === 0) return;

      this.calculateSlidesPerView();
      this.createDots();
      this.bindEvents();
      this.updateCarousel();
    },

    calculateSlidesPerView: function() {
      const width = window.innerWidth;
      if (width <= 640) {
        this.slidesPerView = 1;
      } else if (width <= 1024) {
        this.slidesPerView = 2;
      } else {
        this.slidesPerView = 3;
      }
    },

    createDots: function() {
      if (!this.dotsContainer) return;

      this.dotsContainer.innerHTML = '';
      const totalDots = Math.ceil(this.slides.length / this.slidesPerView);

      for (let i = 0; i < totalDots; i++) {
        const dot = document.createElement('button');
        dot.classList.add('carousel-dot');
        if (i === 0) dot.classList.add('active');
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dot.addEventListener('click', () => this.goToSlide(i * this.slidesPerView));
        this.dotsContainer.appendChild(dot);
      }
    },

    bindEvents: function() {
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', () => this.prev());
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', () => this.next());
      }

      // Touch events for mobile swipe
      this.track.addEventListener('touchstart', (e) => {
        this.touchStartX = e.touches[0].clientX;
      }, { passive: true });

      this.track.addEventListener('touchend', (e) => {
        this.touchEndX = e.changedTouches[0].clientX;
        this.handleSwipe();
      }, { passive: true });

      // Recalculate on resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          this.calculateSlidesPerView();
          this.createDots();
          this.currentIndex = Math.min(this.currentIndex, this.slides.length - this.slidesPerView);
          this.updateCarousel();
        }, 200);
      });
    },

    handleSwipe: function() {
      const diff = this.touchStartX - this.touchEndX;
      const threshold = 50;

      if (diff > threshold) {
        this.next();
      } else if (diff < -threshold) {
        this.prev();
      }
    },

    prev: function() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
        this.updateCarousel();
      }
    },

    next: function() {
      const maxIndex = this.slides.length - this.slidesPerView;
      if (this.currentIndex < maxIndex) {
        this.currentIndex++;
        this.updateCarousel();
      }
    },

    goToSlide: function(index) {
      const maxIndex = this.slides.length - this.slidesPerView;
      this.currentIndex = Math.min(Math.max(0, index), maxIndex);
      this.updateCarousel();
    },

    updateCarousel: function() {
      // Calculate slide width including gap
      const slideWidth = this.slides[0].offsetWidth;
      const gap = 24; // 1.5rem = 24px
      const offset = this.currentIndex * (slideWidth + gap);

      this.track.style.transform = `translateX(-${offset}px)`;

      // Update buttons state
      const maxIndex = this.slides.length - this.slidesPerView;
      if (this.prevBtn) {
        this.prevBtn.disabled = this.currentIndex === 0;
      }
      if (this.nextBtn) {
        this.nextBtn.disabled = this.currentIndex >= maxIndex;
      }

      // Update dots
      if (this.dotsContainer) {
        const dots = this.dotsContainer.querySelectorAll('.carousel-dot');
        const activeDotIndex = Math.floor(this.currentIndex / this.slidesPerView);
        dots.forEach((dot, i) => {
          dot.classList.toggle('active', i === activeDotIndex);
        });
      }
    }
  };

  // =============================================
  // QUANTITY BUTTONS
  // =============================================
  const quantityButtons = {
    init: function() {
      document.addEventListener('click', (e) => {
        if (e.target.classList.contains('qty-btn')) {
          const btn = e.target;
          const input = btn.parentElement.querySelector('.qty');
          if (!input) return;

          let value = parseInt(input.value) || 1;
          const min = parseInt(input.getAttribute('min')) || 1;
          const max = parseInt(input.getAttribute('max')) || 999;

          if (btn.classList.contains('qty-minus')) {
            value = Math.max(min, value - 1);
          } else if (btn.classList.contains('qty-plus')) {
            value = Math.min(max, value + 1);
          }

          input.value = value;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }
  };

  // =============================================
  // INIT
  // =============================================
  function init() {
    miniCart.init();
    productFilters.init();
    variationSwatches.init();
    dynamicPrice.init();
    productGallery.init();
    productCards.init();
    quantityButtons.init();
    categoryCarousel.init();
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Re-init on AJAX content load (for WooCommerce)
  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('ajaxComplete', function() {
      variationSwatches.init();
      productCards.init();
    });
  }

})();
