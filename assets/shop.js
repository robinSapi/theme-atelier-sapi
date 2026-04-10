/**
 * Sapi Maison - Shop JavaScript
 * Mini cart, filters, variations, swatches
 */

(function() {
  'use strict';

  // Mini cart is handled by menu.js (loaded on all pages)

  // =============================================
  // PRODUCT FILTERS (Client-side filtering for shop page)
  // =============================================
  const productFilters = {
    filters: {
      category: 'all',
      price: 'all',
      wood: 'all',
      size: 'all'
    },

    _robinProductIds: null,

    searchQuery: '',

    init: function() {
      const filterContainer = document.querySelector('.product-filters-js');
      if (!filterContainer) {
        // Fallback: navigation-based filters for category pages
        this.initNavigationFilters();
        return;
      }

      // Category filter buttons
      const filterBtns = filterContainer.querySelectorAll('.filter-btn:not(.filter-btn--robin)');
      filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const filter = btn.dataset.filter;

          // Désactiver le filtre Robin si actif
          this._robinProductIds = null;
          var robinRow = document.getElementById('filter-row-robin');
          if (robinRow) robinRow.classList.remove('is-active');

          // Update active state
          filterContainer.querySelector('.filter-btn.active')?.classList.remove('active');
          btn.classList.add('active');

          // Apply filter
          this.filters.category = filter;
          this.applyFilters();
        });
      });

      // Search bar
      this.initSearch();

      // Advanced dropdown filters
      this.initAdvancedFilters();

      // "Ma sélection" — bouton Robin si projet en cours
      try { this.initRobinSelection(filterContainer); } catch(e) { /* ne jamais casser les filtres */ }

      // Appliquer le filtre initial (masque accessoires par défaut)
      this.applyFilters();
    },

    initRobinSelection: function(filterContainer) {
      if (!filterContainer) return;

      // Vérifier si le visiteur a un projet en cours
      var prefs = {};
      try { prefs = JSON.parse(localStorage.getItem('sapiGuidePrefs') || '{}'); } catch(e) {}
      if (!prefs.answers || !Object.keys(prefs.answers).length) return;

      // Conteneur ligne 3 — bandeau personnalisé
      var robinRow = document.getElementById('filter-row-robin');
      if (!robinRow) return;

      // Construire les chips à partir des réponses
      var labels = prefs.labels || {};
      var chipLabels = [];
      var labelOrder = ['piece', 'taille', 'taille_escalier', 'sortie', 'hauteur', 'table', 'style'];
      labelOrder.forEach(function(key) {
        if (labels[key]) chipLabels.push(labels[key]);
      });

      var chipsHtml = '';
      chipLabels.forEach(function(label, i) {
        if (i > 0) chipsHtml += '<span class="robin-selection-chip-sep" aria-hidden="true">\u00b7</span>';
        chipsHtml += '<span class="robin-selection-chip">' + label + '</span>';
      });

      // Icône crayon
      var iconSvg = '<svg class="robin-selection-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>';

      robinRow.innerHTML =
        iconSvg +
        '<span class="robin-selection-label">La s\u00e9lection pour mon projet</span>' +
        '<div class="robin-selection-chips">' + chipsHtml + '</div>' +
        '<button type="button" class="robin-selection-btn" id="robin-selection-btn">Modifier le projet</button>';

      robinRow.style.display = 'flex';

      // Références
      var editBtn = document.getElementById('robin-selection-btn');
      var self = this;

      // Clic sur le bandeau — active/désactive le filtre
      robinRow.addEventListener('click', function(e) {
        // Ne pas filtrer si clic sur le bouton "Modifier le projet"
        if (e.target === editBtn || editBtn.contains(e.target)) return;

        var isActive = robinRow.classList.contains('is-active');

        if (isActive) {
          // Désactiver → revenir à "tout"
          robinRow.classList.remove('is-active');
          var allBtn = filterContainer.querySelector('.filter-btn[data-filter="all"]');
          if (allBtn) {
            filterContainer.querySelectorAll('.filter-btn.active').forEach(function(b) { b.classList.remove('active'); });
            allBtn.classList.add('active');
          }
          self.filters.category = 'all';
          self._robinProductIds = null;
          self.applyFilters();
        } else {
          // Activer Ma sélection
          filterContainer.querySelectorAll('.filter-btn.active').forEach(function(b) { b.classList.remove('active'); });
          robinRow.classList.add('is-active');
          self.fetchRobinSelection(prefs.answers);
        }
      });

      // Clic sur "Modifier le projet" — ouvrir la modale Robin
      editBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.sapiRobinConseiller && typeof window.openRobinModal === 'function') {
          window.openRobinModal('bandeau');
        } else {
          var bandeau = document.getElementById('robin-bandeau');
          if (bandeau) bandeau.click();
        }
      });

      // Auto-activer si URL contient robin_selection=1 (différé après init)
      var params = new URLSearchParams(window.location.search);
      if (params.get('robin_selection') === '1') {
        window.history.replaceState({}, '', window.location.pathname);
        setTimeout(function() { robinRow.click(); }, 50);
      }
    },

    fetchRobinSelection: function(answers) {
      var self = this;
      var nonce = '';
      if (window.sapiRobinConseiller) nonce = window.sapiRobinConseiller.nonce;
      else if (window.sapiMonProjet) nonce = window.sapiMonProjet.nonce;

      var fd = new FormData();
      fd.append('action', 'sapi_robin_filter_products');
      fd.append('nonce', nonce);
      fd.append('answers', JSON.stringify(answers));

      var xhr = new XMLHttpRequest();
      xhr.open('POST', (window.sapiRobinConseiller || window.sapiMonProjet || {}).ajaxUrl || '/wp-admin/admin-ajax.php', true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) {
          try {
            var resp = JSON.parse(xhr.responseText);
            if (resp.success && resp.data && resp.data.product_ids) {
              self._robinProductIds = resp.data.product_ids.map(String);
              self.applyFilters();
              return;
            }
          } catch(e) {}
        }
        // Erreur — désactiver le filtre
        self._robinProductIds = null;
        self.applyFilters();
      };
      xhr.send(fd);
    },

    initSearch: function() {
      const searchInput = document.getElementById('product-search-input');
      const clearBtn = document.querySelector('.search-clear');
      if (!searchInput) return;

      searchInput.addEventListener('input', () => {
        this.searchQuery = searchInput.value.trim().toLowerCase();
        clearBtn.style.display = this.searchQuery ? 'flex' : 'none';
        this.applyFilters();
      });

      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          searchInput.value = '';
          this.searchQuery = '';
          clearBtn.style.display = 'none';
          this.applyFilters();
        });
      }
    },

    initAdvancedFilters: function() {
      const dropdowns = document.querySelectorAll('.filter-dropdown');
      const resetBtn = document.querySelector('.filter-reset');

      dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.filter-dropdown-toggle');
        const menu = dropdown.querySelector('.filter-dropdown-menu');
        const options = dropdown.querySelectorAll('.filter-option');
        const filterType = dropdown.dataset.filterType;

        // Toggle dropdown
        toggle.addEventListener('click', (e) => {
          e.stopPropagation();
          // Close other dropdowns
          dropdowns.forEach(d => {
            if (d !== dropdown) d.classList.remove('is-open');
          });
          dropdown.classList.toggle('is-open');
        });

        // Handle option selection
        options.forEach(option => {
          option.addEventListener('click', () => {
            // Update active state
            menu.querySelector('.filter-option.active')?.classList.remove('active');
            option.classList.add('active');

            // Get filter value
            const value = option.dataset[filterType];
            this.filters[filterType] = value;

            // Update label if filter is active
            const label = toggle.querySelector('.filter-label');
            if (value !== 'all') {
              label.textContent = option.textContent;
              toggle.classList.add('has-filter');
            } else {
              label.textContent = this.getDefaultLabel(filterType);
              toggle.classList.remove('has-filter');
            }

            // Close dropdown
            dropdown.classList.remove('is-open');

            // Apply all filters
            this.applyFilters();
            this.updateResetButton();
          });
        });
      });

      // Reset button
      if (resetBtn) {
        resetBtn.addEventListener('click', () => {
          this.resetAllFilters();
        });
      }

      // Close dropdowns on outside click
      document.addEventListener('click', () => {
        dropdowns.forEach(d => d.classList.remove('is-open'));
      });
    },

    getDefaultLabel: function(type) {
      const labels = {
        price: 'Prix',
        wood: 'Essence',
        size: 'Taille'
      };
      return labels[type] || type;
    },

    updateResetButton: function() {
      const resetBtn = document.querySelector('.filter-reset');
      if (!resetBtn) return;

      const hasActiveFilters = this.filters.price !== 'all' ||
                               this.filters.wood !== 'all' ||
                               this.filters.size !== 'all';

      resetBtn.style.display = hasActiveFilters ? 'inline-flex' : 'none';
    },

    resetAllFilters: function() {
      // Reset filter values
      this.filters.price = 'all';
      this.filters.wood = 'all';
      this.filters.size = 'all';

      // Reset UI
      document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.filter-dropdown-toggle');
        const filterType = dropdown.dataset.filterType;
        const label = toggle.querySelector('.filter-label');

        toggle.classList.remove('has-filter');
        label.textContent = this.getDefaultLabel(filterType);

        // Reset active option
        const menu = dropdown.querySelector('.filter-dropdown-menu');
        menu.querySelector('.filter-option.active')?.classList.remove('active');
        menu.querySelector('.filter-option[data-' + filterType + '="all"]')?.classList.add('active');
      });

      this.applyFilters();
      this.updateResetButton();
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

    matchesPrice: function(productPrice, filterValue) {
      if (filterValue === 'all') return true;

      const price = parseFloat(productPrice);
      if (isNaN(price)) return true;

      if (filterValue === '0-100') return price < 100;
      if (filterValue === '100-200') return price >= 100 && price < 200;
      if (filterValue === '200-300') return price >= 200 && price < 300;
      if (filterValue === '300+') return price >= 300;

      return true;
    },

    matchesSize: function(productSize, filterValue) {
      if (filterValue === 'all') return true;

      const size = parseFloat(productSize);
      if (isNaN(size)) return true;

      if (filterValue === '0-100') return size < 100;
      if (filterValue === '100-150') return size >= 100 && size < 150;
      if (filterValue === '150-200') return size >= 150 && size < 200;
      if (filterValue === '200+') return size >= 200;

      return true;
    },

    applyFilters: function() {
      // Find all product cards — carousel slides or grid items
      let slides;
      if (productsCarousel.allSlides && productsCarousel.allSlides.length > 0) {
        slides = productsCarousel.allSlides;
      } else {
        slides = document.querySelectorAll('.product-card-cinetique');
      }

      let visibleCount = 0;

      slides.forEach(slide => {
        const categories = slide.dataset.categories || '';
        const price = slide.dataset.price || '';
        const wood = slide.dataset.wood || '';
        const size = slide.dataset.size || '';
        const name = slide.dataset.name || '';

        // Check all filter criteria
        const catList = categories.split(' ');
        const extraCategories = ['accessoires', 'carte-cadeau'];
        let matchesCategory;
        if (this.filters.category === 'all') {
          matchesCategory = !catList.some(function(c) { return extraCategories.indexOf(c) !== -1; });
        } else {
          matchesCategory = catList.includes(this.filters.category);
        }
        const matchesPrice = this.matchesPrice(price, this.filters.price);
        const matchesWood = this.filters.wood === 'all' || wood === this.filters.wood;
        const matchesSize = this.filters.size === 'all' || this.matchesSize(size, this.filters.size);
        const matchesSearch = !this.searchQuery || name.includes(this.searchQuery);

        let shouldShow;
        if (this._robinProductIds) {
          // Filtre "Ma sélection" actif → seuls les IDs filtrés par Robin
          shouldShow = this._robinProductIds.includes(slide.dataset.id);
        } else {
          shouldShow = matchesCategory && matchesPrice && matchesWood && matchesSize && matchesSearch;
        }

        // Use both class AND inline styles to guarantee hiding
        if (shouldShow) {
          slide.classList.remove('is-filtered-out');
          slide.style.display = '';
          visibleCount++;
        } else {
          slide.classList.add('is-filtered-out');
          slide.style.display = 'none';
        }
      });

      // Show/hide individual text cards + recap card based on filters
      var textCards = document.querySelectorAll('.product-text-card');
      var recapCard = document.querySelector('.why-sapi-recap');
      var isFiltered = this.filters.category !== 'all' || this.searchQuery || this._robinProductIds;
      textCards.forEach(function(card) {
        if (isFiltered) {
          card.classList.add('is-filtered-out');
          card.style.display = 'none';
        } else {
          card.classList.remove('is-filtered-out');
          card.style.display = '';
        }
      });
      if (recapCard) {
        recapCard.style.display = isFiltered ? '' : 'none';
      }

      // Show/hide "no results" message
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
