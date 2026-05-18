/**
 * Sapi Maison - Shop JavaScript
 * Mini cart, filters, variations, swatches
 */

(function() {
  'use strict';

  // Mini cart is handled by menu.js (loaded on all pages)

  // =============================================
  // PRODUCT FILTERS — version simplifiée (F1a-ter)
  // Plus de pills catégorie ni de search bar sur la page : ce module
  // applique juste l'exclusion des extras (Accessoires / Carte cadeau)
  // par défaut + le hook du méga-filtre intelligent.
  // =============================================
  const productFilters = {
    init: function() {
      // Premier passage : masque les extras + applique le méga-filtre
      // si l'utilisateur arrive avec ?piece=… déjà résolu côté JS
      this.applyFilters();
    },

    applyFilters: function() {
      // Toutes les cards produit visibles dans la grille (ou un carousel)
      let slides;
      if (productsCarousel.allSlides && productsCarousel.allSlides.length > 0) {
        slides = productsCarousel.allSlides;
      } else {
        slides = document.querySelectorAll('.product-card-cinetique');
      }

      const extraCategories = ['accessoires', 'carte-cadeau'];
      let visibleCount = 0;

      slides.forEach(slide => {
        const cats = (slide.dataset.categories || '').split(' ');

        // Par défaut, on cache les extras de la grille (ils restent accessibles
        // via le menu et leurs URLs /categorie-produit/accessoires/ etc.)
        const matchesExtras = !cats.some(c => extraCategories.indexOf(c) !== -1);

        // Méga-filtre (F1a) — chips Robin sur /mes-creations/
        const matchesMega = !window.sapiMegaFilter || window.sapiMegaFilter.cardMatches(slide);

        const shouldShow = matchesExtras && matchesMega;

        if (shouldShow) {
          slide.classList.remove('is-filtered-out');
          slide.style.display = '';
          visibleCount++;
        } else {
          slide.classList.add('is-filtered-out');
          slide.style.display = 'none';
        }
      });

      // Text cards (réassurance) + why-sapi-recap : visibles uniquement
      // quand AUCUN filtre n'est actif (donc grille naturelle)
      const textCards = document.querySelectorAll('.product-text-card');
      const recapCard = document.querySelector('.why-sapi-recap');
      const megaActive = !!(window.sapiMegaFilter && window.sapiMegaFilter.hasAnyAnswer && window.sapiMegaFilter.hasAnyAnswer());
      textCards.forEach(card => {
        if (megaActive) {
          card.classList.add('is-filtered-out');
          card.style.display = 'none';
        } else {
          card.classList.remove('is-filtered-out');
          card.style.display = '';
        }
      });
      if (recapCard) {
        recapCard.style.display = megaActive ? '' : 'none';
      }

      // Message "aucun résultat"
      const noResults = document.querySelector('.woocommerce-no-products-found');
      const productsList = document.querySelector('.product-grid') || document.querySelector('.products.columns-3');
      if (noResults && productsList) {
        if (visibleCount === 0 && slides.length > 0) {
          noResults.style.display = 'block';
          productsList.style.display = 'none';
        } else {
          noResults.style.display = 'none';
          productsList.style.display = '';
        }
      }

      // Reset carousel à l'index 0 et recalcul des slides visibles
      if (productsCarousel.carousel) {
        productsCarousel.currentIndex = 0;
        productsCarousel.recalculateVisibleSlides();
        productsCarousel.createDots();
        productsCarousel.updateCarousel();
        productsCarousel.resetAutoScroll();
      }
    }
  };

  // Hook public pour le méga-filtre — re-trigger applyFilters depuis l'extérieur
  window.sapiShopRefilter = function() {
    productFilters.applyFilters();
  };

  // =============================================
  // VARIATION SWATCHES (Toggle-style selectors)
  // Handles both .sapi-swatches and .attribute-swatch containers
  // =============================================
  const variationSwatches = {
    init: function() {
      this.initAttributeSwatches();
    },

    initAttributeSwatches: function() {
      const swatchContainers = document.querySelectorAll('.attribute-swatch');
      if (!swatchContainers.length) return;

      swatchContainers.forEach(container => {
        // Skip if cinetique.js handles these swatches (product pages)
        if (container.querySelector('.material-option')) return;

        const items = container.querySelectorAll('.swatch-item');

        // Find the associated hidden select
        const hiddenSelect = container.nextElementSibling;
        if (!hiddenSelect || hiddenSelect.tagName !== 'SELECT') return;

        // Set initial selected state based on select value
        const currentValue = hiddenSelect.value;
        items.forEach(item => {
          if (item.dataset.value === currentValue) {
            item.classList.add('selected');
          }
        });

        // Handle click on swatch items
        items.forEach(item => {
          item.addEventListener('click', () => {
            items.forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            hiddenSelect.value = item.dataset.value;
            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
          });
        });
      });

      // Listen for WooCommerce variation reset
      document.querySelectorAll('.reset_variations').forEach(resetBtn => {
        resetBtn.addEventListener('click', () => {
          setTimeout(() => {
            document.querySelectorAll('.attribute-swatch .swatch-item').forEach(item => {
              item.classList.remove('selected');
            });
          }, 10);
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
      this.prevBtn = document.querySelector('.products-carousel-prev');
      this.nextBtn = document.querySelector('.products-carousel-next');
      this.dotsContainer = document.querySelector('.products-carousel-dots');

      // Find slides - they have class products-carousel-slide (added by content-product.php)
      // The class is added via wc_product_class() which generates: class="product ... products-carousel-slide"
      this.allSlides = Array.from(this.carousel.querySelectorAll('.products-carousel-slide'));

      // Fallback: if no slides found with that class, try direct children of track
      if (this.allSlides.length === 0) {
        this.allSlides = Array.from(this.track.querySelectorAll(':scope > li'));
        // Add the class to found slides for filtering to work
        this.allSlides.forEach(slide => slide.classList.add('products-carousel-slide'));
      }

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
        return !slide.classList.contains('is-filtered-out');
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

  // Quantity buttons are created and handled by cinetique.js

  // =============================================
  // INIT
  // =============================================
  function init() {
    productFilters.init();
    variationSwatches.init();
    dynamicPrice.init();
    productGallery.init();
    productCards.init();
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
