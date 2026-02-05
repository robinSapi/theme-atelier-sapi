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
