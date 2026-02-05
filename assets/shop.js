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
  // PRODUCT FILTERS (Client-side filtering for shop page)
  // =============================================
  const productFilters = {
    currentFilter: 'all',

    init: function() {
      const filterContainer = document.querySelector('.product-filters-js');
      if (!filterContainer) {
        // Fallback: navigation-based filters for category pages
        this.initNavigationFilters();
        return;
      }

      // Client-side filtering for shop page
      const filterBtns = filterContainer.querySelectorAll('.filter-btn');
      filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const filter = btn.dataset.filter;

          // Update active state
          filterContainer.querySelector('.filter-btn.active')?.classList.remove('active');
          btn.classList.add('active');

          // Apply filter
          this.applyFilter(filter);
        });
      });
    },

    initNavigationFilters: function() {
      const filterBtns = document.querySelectorAll('.filter-btn');
      if (!filterBtns.length) return;

      filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
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
    },

    applyFilter: function(filter) {
      this.currentFilter = filter;
      const slides = document.querySelectorAll('.products-carousel-slide[data-categories]');

      slides.forEach(slide => {
        const categories = slide.dataset.categories || '';
        if (filter === 'all' || categories.includes(filter)) {
          slide.style.display = '';
          slide.classList.remove('is-filtered-out');
        } else {
          slide.style.display = 'none';
          slide.classList.add('is-filtered-out');
        }
      });

      // Reset carousel to beginning and recalculate
      if (productsCarousel.carousel) {
        productsCarousel.currentIndex = 0;
        productsCarousel.recalculateVisibleSlides();
        productsCarousel.createDots();
        productsCarousel.updateCarousel();
        productsCarousel.resetAutoScroll();
      }
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
  // PRODUCT CARDS (animation disabled for reliability)
  // =============================================
  const productCards = {
    init: function() {
      // Cards are visible by default via CSS
      // Animation was causing visibility issues, disabled for now
    }
  };

  // =============================================
  // PRODUCTS CAROUSEL (Category pages + Shop page)
  // Auto-scrolling with pause on hover, supports filtering
  // =============================================
  const productsCarousel = {
    carousel: null,
    track: null,
    allSlides: [],      // All slides (including filtered out)
    visibleSlides: [],  // Currently visible slides
    prevBtn: null,
    nextBtn: null,
    dotsContainer: null,
    currentIndex: 0,
    slidesPerView: 4,
    touchStartX: 0,
    touchEndX: 0,
    autoScrollInterval: null,
    autoScrollDelay: 4000, // 4 seconds between slides
    isPaused: false,

    init: function() {
      this.carousel = document.querySelector('[data-products-carousel]');
      if (!this.carousel) return;

      this.track = this.carousel.querySelector('.products-carousel-track');
      this.allSlides = Array.from(this.carousel.querySelectorAll('.products-carousel-slide'));
      this.prevBtn = document.querySelector('.products-carousel-prev');
      this.nextBtn = document.querySelector('.products-carousel-next');
      this.dotsContainer = document.querySelector('.products-carousel-dots');

      if (!this.track || this.allSlides.length === 0) return;

      this.recalculateVisibleSlides();
      this.calculateSlidesPerView();
      this.createDots();
      this.bindEvents();
      this.updateCarousel();
      this.startAutoScroll();
    },

    recalculateVisibleSlides: function() {
      this.visibleSlides = this.allSlides.filter(slide => {
        return !slide.classList.contains('is-filtered-out') && slide.style.display !== 'none';
      });
    },

    calculateSlidesPerView: function() {
      const width = window.innerWidth;
      if (width <= 540) {
        this.slidesPerView = 1;
      } else if (width <= 900) {
        this.slidesPerView = 2;
      } else if (width <= 1200) {
        this.slidesPerView = 3;
      } else {
        this.slidesPerView = 4;
      }
    },

    createDots: function() {
      if (!this.dotsContainer) return;

      this.dotsContainer.innerHTML = '';
      const totalDots = Math.ceil(this.visibleSlides.length / this.slidesPerView);

      for (let i = 0; i < totalDots; i++) {
        const dot = document.createElement('button');
        dot.classList.add('products-carousel-dot');
        if (i === 0) dot.classList.add('active');
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dot.addEventListener('click', () => {
          this.goToSlide(i * this.slidesPerView);
          this.resetAutoScroll();
        });
        this.dotsContainer.appendChild(dot);
      }
    },

    bindEvents: function() {
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', () => {
          this.prev();
          this.resetAutoScroll();
        });
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', () => {
          this.next();
          this.resetAutoScroll();
        });
      }

      // Pause auto-scroll on hover
      this.carousel.addEventListener('mouseenter', () => {
        this.isPaused = true;
      });
      this.carousel.addEventListener('mouseleave', () => {
        this.isPaused = false;
      });

      // Touch events for mobile swipe
      this.track.addEventListener('touchstart', (e) => {
        this.touchStartX = e.touches[0].clientX;
        this.isPaused = true;
      }, { passive: true });

      this.track.addEventListener('touchend', (e) => {
        this.touchEndX = e.changedTouches[0].clientX;
        this.handleSwipe();
        this.isPaused = false;
        this.resetAutoScroll();
      }, { passive: true });

      // Recalculate on resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          this.calculateSlidesPerView();
          this.recalculateVisibleSlides();
          this.createDots();
          this.currentIndex = Math.min(this.currentIndex, Math.max(0, this.visibleSlides.length - this.slidesPerView));
          this.updateCarousel();
        }, 200);
      });
    },

    startAutoScroll: function() {
      this.autoScrollInterval = setInterval(() => {
        if (!this.isPaused) {
          this.nextOrLoop();
        }
      }, this.autoScrollDelay);
    },

    resetAutoScroll: function() {
      clearInterval(this.autoScrollInterval);
      this.startAutoScroll();
    },

    nextOrLoop: function() {
      const maxIndex = Math.max(0, this.visibleSlides.length - this.slidesPerView);
      if (this.currentIndex >= maxIndex) {
        // Loop back to start
        this.currentIndex = 0;
      } else {
        this.currentIndex++;
      }
      this.updateCarousel();
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
      const maxIndex = Math.max(0, this.visibleSlides.length - this.slidesPerView);
      if (this.currentIndex < maxIndex) {
        this.currentIndex++;
        this.updateCarousel();
      }
    },

    goToSlide: function(index) {
      const maxIndex = Math.max(0, this.visibleSlides.length - this.slidesPerView);
      this.currentIndex = Math.min(Math.max(0, index), maxIndex);
      this.updateCarousel();
    },

    updateCarousel: function() {
      if (!this.visibleSlides.length) {
        this.track.style.transform = 'translateX(0)';
        return;
      }

      // Calculate slide width including gap from first visible slide
      const slideWidth = this.visibleSlides[0].offsetWidth;
      const gap = 24; // 1.5rem = 24px
      const offset = this.currentIndex * (slideWidth + gap);

      this.track.style.transform = `translateX(-${offset}px)`;

      // Update buttons state
      const maxIndex = Math.max(0, this.visibleSlides.length - this.slidesPerView);
      if (this.prevBtn) {
        this.prevBtn.disabled = this.currentIndex === 0;
      }
      if (this.nextBtn) {
        this.nextBtn.disabled = this.currentIndex >= maxIndex;
      }

      // Update dots
      if (this.dotsContainer) {
        const dots = this.dotsContainer.querySelectorAll('.products-carousel-dot');
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
    productsCarousel.init();
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
