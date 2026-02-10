/**
 * Product Quick View Modal
 * Pop-up d'aperçu rapide avec galerie photos et infos produit
 */

(function() {
  'use strict';

  const QuickView = {
    modal: null,
    overlay: null,
    body: null,
    closeBtn: null,
    loading: null,
    currentGalleryIndex: 0,
    galleryImages: [],
    autoAdvanceTimer: null,
    autoAdvanceInterval: 3000, // 3 seconds per image
    progressBar: null,
    progressAnimation: null,
    preloadCache: {}, // Cache for preloaded product data
    preloadTimeout: null,

    init: function() {
      this.modal = document.getElementById('quick-view-modal');
      if (!this.modal) return;

      this.overlay = this.modal.querySelector('.quick-view-overlay');
      this.body = this.modal.querySelector('.quick-view-body');
      this.closeBtn = this.modal.querySelector('.quick-view-close');
      this.loading = this.modal.querySelector('.quick-view-loading');

      this.bindEvents();
    },

    bindEvents: function() {
      // Quick view buttons
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.product-quick-view');
        if (btn) {
          e.preventDefault();
          e.stopPropagation();
          this.openModal(btn);
        }
      });

      // Setup IntersectionObserver for automatic preloading when products enter viewport
      this.setupPreloadObserver();

      // Close modal
      if (this.closeBtn) {
        this.closeBtn.addEventListener('click', () => this.closeModal());
      }

      if (this.overlay) {
        this.overlay.addEventListener('click', () => this.closeModal());
      }

      // Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal.getAttribute('aria-hidden') === 'false') {
          this.closeModal();
        }
      });
    },

    setupPreloadObserver: function() {
      // Observe product cards entering the viewport and preload their data
      const observerOptions = {
        root: null,
        rootMargin: '300px', // Start preloading 300px before card enters viewport
        threshold: 0.01
      };

      const preloadObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const card = entry.target;
            const btn = card.querySelector('.product-quick-view');
            if (btn) {
              this.preloadProductData(btn);
              // Once preloaded, stop observing this card
              preloadObserver.unobserve(card);
            }
          }
        });
      }, observerOptions);

      // Observe all product cards
      const productCards = document.querySelectorAll('.product-card-cinetique');
      productCards.forEach(card => preloadObserver.observe(card));
    },

    preloadProductData: function(btn) {
      const productUrl = btn.dataset.productUrl;
      if (!productUrl || this.preloadCache[productUrl]) return; // Already preloaded

      console.log('[Quick View Preload] Starting preload for:', productUrl);

      // Mark as loading to avoid duplicate preloads
      this.preloadCache[productUrl] = { loading: true };

      // Fetch and cache the product page HTML
      fetch(productUrl)
        .then(response => response.text())
        .then(html => {
          console.log('[Quick View Preload] Cached data for:', productUrl);
          this.preloadCache[productUrl] = { html, timestamp: Date.now() };
        })
        .catch(error => {
          console.error('[Quick View Preload] Error:', error);
          delete this.preloadCache[productUrl]; // Clear failed cache
        });
    },

    openModal: function(btn) {
      const productId = btn.dataset.productId;
      const productUrl = btn.dataset.productUrl;

      if (!productId) return;

      // Get product card to extract basic data
      const productCard = btn.closest('.product-card-cinetique') || btn.closest('li');

      // Show modal with loading state
      this.modal.setAttribute('aria-hidden', 'false');
      this.loading.style.display = 'flex';
      this.body.style.display = 'none';
      document.body.style.overflow = 'hidden';

      // Check if we have preloaded data
      const cached = this.preloadCache[productUrl];
      if (cached && cached.html) {
        console.log('[Quick View] Using preloaded data');
        // Use cached data - will be much faster
        this.fetchProductData(productId, productUrl, productCard, cached.html);
      } else {
        // Fetch normally if not preloaded
        this.fetchProductData(productId, productUrl, productCard);
      }
    },

    closeModal: function() {
      this.stopAutoAdvance();
      this.modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      this.body.innerHTML = '';
      this.currentGalleryIndex = 0;
      this.galleryImages = [];
      this.progressBar = null;
    },

    fetchProductData: function(productId, productUrl, productCard, cachedHtml) {
      // Extract basic data from product card
      const productData = {
        name: '',
        price_html: '',
        short_description: '',
        images: [],
        permalink: productUrl
      };

      if (productCard) {
        // Get title
        const titleEl = productCard.querySelector('.product-name, h2');
        if (titleEl) {
          productData.name = titleEl.textContent.trim();
        }

        // Get price
        const priceEl = productCard.querySelector('.price-value, .product-price');
        if (priceEl) {
          productData.price_html = priceEl.innerHTML;
        }

        // Get main image
        const mainImg = productCard.querySelector('.product-image-main img, img');
        if (mainImg) {
          productData.images.push({
            src: mainImg.src,
            alt: mainImg.alt || productData.name
          });
        }

        // Get hover image if available
        const hoverImg = productCard.querySelector('.product-image-hover img');
        if (hoverImg) {
          productData.images.push({
            src: hoverImg.src,
            alt: hoverImg.alt || productData.name
          });
        }
      }

      // If we have basic data, render immediately then load more images
      if (productData.name) {
        this.renderProduct(productData);
        // Load additional images in background (use cached HTML if available)
        this.loadAdditionalImages(productUrl, productData, cachedHtml);
      } else {
        // Fallback: fetch from product page
        this.fetchFromPage(productUrl);
      }
    },

    loadAdditionalImages: function(productUrl, productData, cachedHtml) {
      // Load gallery images and variant data from product page in background
      console.log('[Quick View Debug] Loading additional data for:', productUrl);

      // Use cached HTML if available, otherwise fetch
      const htmlPromise = cachedHtml
        ? Promise.resolve(cachedHtml)
        : fetch(productUrl)
            .then(response => {
              console.log('[Quick View Debug] Fetch response status:', response.status);
              return response.text();
            });

      htmlPromise.then(html => {
          console.log('[Quick View Debug] HTML received, length:', html.length);

          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          const additionalImages = [];

          // Get main image (higher resolution)
          const mainImage = doc.querySelector('.gallery-main-image');
          if (mainImage && mainImage.src) {
            // Replace first image with higher res version
            productData.images[0] = {
              src: mainImage.src,
              alt: mainImage.alt || productData.name
            };
          }

          // Get gallery thumbnails
          const galleryThumbs = doc.querySelectorAll('.gallery-thumb');
          galleryThumbs.forEach((thumb, index) => {
            const imgUrl = thumb.dataset.image || thumb.querySelector('img')?.src;
            if (imgUrl && index > 0) { // Skip first (main image)
              additionalImages.push({
                src: imgUrl,
                alt: `${productData.name} - ${index + 1}`
              });
            }
          });

          // Extract description/tagline - try multiple selectors
          console.log('[Quick View Debug] Looking for description...');

          const tagline = doc.querySelector('.product-tagline');
          console.log('[Quick View Debug] .product-tagline found:', !!tagline, tagline?.textContent?.trim().substring(0, 50));

          if (tagline && tagline.textContent.trim()) {
            productData.short_description = tagline.textContent.trim();
            console.log('[Quick View Debug] Description from .product-tagline:', productData.short_description.substring(0, 50));
          } else {
            // Fallback to WooCommerce short description
            const wcDescription = doc.querySelector('.woocommerce-product-details__short-description');
            console.log('[Quick View Debug] .woocommerce-product-details__short-description found:', !!wcDescription);

            if (wcDescription && wcDescription.textContent.trim()) {
              productData.short_description = wcDescription.textContent.trim();
              console.log('[Quick View Debug] Description from WC short description:', productData.short_description.substring(0, 50));
            } else {
              // Last resort: find any <p> tag between price and form in product-hero-v2
              const heroSection = doc.querySelector('.product-hero-v2, .product-info-v2, .product-summary');
              console.log('[Quick View Debug] Hero section found:', !!heroSection);

              if (heroSection) {
                const paragraphs = heroSection.querySelectorAll('p');
                console.log('[Quick View Debug] Paragraphs in hero section:', paragraphs.length);

                for (let p of paragraphs) {
                  const text = p.textContent.trim();
                  console.log('[Quick View Debug] Checking paragraph:', text.substring(0, 30), 'length:', text.length);

                  // Find first substantial paragraph (more than 20 chars, not just a label)
                  if (text.length > 20 && !text.match(/^(À partir de|Prix|Price)/i)) {
                    productData.short_description = text;
                    console.log('[Quick View Debug] Description from fallback paragraph:', text.substring(0, 50));
                    break;
                  }
                }
              }
            }
          }

          console.log('[Quick View Debug] Final description:', productData.short_description?.substring(0, 100) || 'EMPTY');

          // Extract available sizes from WooCommerce variation select options
          console.log('[Quick View Debug] Looking for sizes...');
          const sizeSelect = doc.querySelector('select[name="attribute_pa_taille"], select[name="pa_taille"]');
          console.log('[Quick View Debug] Size select found:', !!sizeSelect);

          if (sizeSelect) {
            const sizes = Array.from(sizeSelect.querySelectorAll('option'))
              .map(opt => opt.textContent.trim())
              .filter(s => s && s !== 'Choisir une option' && s !== 'Choisir...' && s !== '')
              .filter((v, i, a) => a.indexOf(v) === i); // Remove duplicates
            console.log('[Quick View Debug] Sizes extracted:', sizes);
            if (sizes.length > 0) {
              productData.sizes = sizes;
            }
          }

          // Extract wood/material essences from WooCommerce variation select options
          // Try both 'matiere' (material) and 'bois' (wood) attribute names
          // Try with AND without 'attribute_' prefix
          console.log('[Quick View Debug] Looking for wood/material...');

          // Debug: List ALL selects in the document
          const allSelects = doc.querySelectorAll('select');
          console.log('[Quick View Debug] Total selects found:', allSelects.length);
          allSelects.forEach(sel => {
            console.log('[Quick View Debug] Select name:', sel.getAttribute('name'), 'options:', sel.querySelectorAll('option').length);
          });

          // Try select first
          const woodSelect = doc.querySelector('select[name="attribute_pa_materiau"], select[name="pa_materiau"], select[name="attribute_pa_matiere"], select[name="pa_matiere"], select[name="attribute_pa_bois"], select[name="pa_bois"]');
          console.log('[Quick View Debug] Wood/material select found:', !!woodSelect);

          let woods = [];

          if (woodSelect) {
            console.log('[Quick View Debug] Wood select name:', woodSelect.getAttribute('name'));
            woods = Array.from(woodSelect.querySelectorAll('option'))
              .map(opt => opt.textContent.trim())
              .filter(w => w && w !== 'Choisir une option' && w !== 'Choisir...' && w !== '')
              .filter((v, i, a) => a.indexOf(v) === i); // Remove duplicates
            console.log('[Quick View Debug] Woods/materials from select:', woods);
          }

          // Fallback: extract from variation swatches images (Woo Variation Swatches plugin)
          if (woods.length === 0) {
            console.log('[Quick View Debug] Trying variation swatches images...');
            const swatchImages = doc.querySelectorAll('.variation-swatches img[alt], ul[class*="variation"] img[alt]');
            console.log('[Quick View Debug] Swatch images found:', swatchImages.length);

            woods = Array.from(swatchImages)
              .map(img => img.alt.trim())
              .filter(w => w && w !== 'Choisir une option' && w !== '' && w.length > 1)
              .filter((v, i, a) => a.indexOf(v) === i); // Remove duplicates
            console.log('[Quick View Debug] Woods/materials from swatches:', woods);
          }

          if (woods.length > 0) {
            productData.woods = woods;
          }

          // Add additional images to existing ones
          if (additionalImages.length > 0) {
            productData.images = productData.images.concat(additionalImages);
          }

          // Re-render if modal still open and we have new data
          console.log('[Quick View Debug] Modal still open:', this.modal.getAttribute('aria-hidden') === 'false');
          console.log('[Quick View Debug] Data to update - description:', !!productData.short_description, 'sizes:', !!productData.sizes, 'woods:', !!productData.woods);

          if (this.modal.getAttribute('aria-hidden') === 'false') {
            if (additionalImages.length > 0) {
              this.updateGallery(productData);
            }
            // Update info section with variants if we found any
            if (productData.short_description || productData.sizes || productData.woods) {
              console.log('[Quick View Debug] Calling updateProductInfo with:', {
                description: productData.short_description?.substring(0, 50),
                sizes: productData.sizes,
                woods: productData.woods
              });
              this.updateProductInfo(productData);
            } else {
              console.log('[Quick View Debug] No data to update - skipping updateProductInfo');
            }
          }
        })
        .catch(error => {
          console.error('[Quick View Debug] Fetch error:', error);
          // Not critical, we already have basic info
        });
    },

    fetchFromPage: function(productUrl) {
      // Fallback when card data is not available
      return fetch(productUrl)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          // Extract product data from page
          let shortDesc = '';
          const taglineEl = doc.querySelector('.product-tagline, .woocommerce-product-details__short-description');
          if (taglineEl) {
            shortDesc = taglineEl.textContent.trim();
          } else {
            // Fallback: find first substantial paragraph in product hero section
            const heroSection = doc.querySelector('.product-hero-v2, .product-info-v2, .product-summary');
            if (heroSection) {
              const paragraphs = heroSection.querySelectorAll('p');
              for (let p of paragraphs) {
                const text = p.textContent.trim();
                if (text.length > 20 && !text.match(/^(À partir de|Prix|Price)/i)) {
                  shortDesc = text;
                  break;
                }
              }
            }
          }

          const productData = {
            name: doc.querySelector('.product-title-v2, h1')?.textContent?.trim() || '',
            price_html: doc.querySelector('.product-price-v2 .price-amount, .price')?.innerHTML || '',
            short_description: shortDesc,
            images: [],
            permalink: productUrl
          };

          // Get main image
          const mainImage = doc.querySelector('.gallery-main-image, .woocommerce-product-gallery__image img');
          if (mainImage) {
            productData.images.push({
              src: mainImage.src,
              alt: mainImage.alt || productData.name
            });
          }

          // Get gallery images
          const galleryThumbs = doc.querySelectorAll('.gallery-thumb');
          galleryThumbs.forEach((thumb, index) => {
            if (index > 0) { // Skip first (already added as main)
              const imgUrl = thumb.dataset.image || thumb.querySelector('img')?.src;
              if (imgUrl) {
                productData.images.push({
                  src: imgUrl,
                  alt: `${productData.name} - ${index + 1}`
                });
              }
            }
          });

          return productData;
        })
        .then(productData => {
          this.renderProduct(productData);
        })
        .catch(error => {
          console.error('Fetch from page error:', error);
          this.showError();
        });
    },

    renderProduct: function(product) {
      // Prepare gallery images
      this.galleryImages = product.images || [];
      if (this.galleryImages.length === 0 && product.image) {
        this.galleryImages = [{ src: product.image.src, alt: product.name }];
      }

      const html = `
        <div class="quick-view-grid">
          <div class="quick-view-gallery">
            <div class="quick-view-gallery-main">
              ${this.galleryImages.length > 0 ? `
                <img src="${this.galleryImages[0].src}" alt="${this.galleryImages[0].alt || product.name}" loading="eager">
              ` : `<div class="no-image">Image non disponible</div>`}
              ${this.galleryImages.length > 1 ? `
                <div class="quick-view-progress">
                  <div class="quick-view-progress-bar"></div>
                </div>
              ` : ''}
            </div>
            ${this.galleryImages.length > 1 ? `
              <div class="quick-view-gallery-nav">
                <button type="button" class="gallery-nav-btn gallery-prev" aria-label="Image précédente">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.5 6.5c.3.2.5.5.5.9v9.2c0 .4-.2.7-.5.9-.3.2-.7.2-1 0l-6-4.6c-.3-.2-.5-.6-.5-1s.2-.8.5-1l6-4.6c.3-.2.7-.2 1 .2z"></path>
                  </svg>
                </button>
                <div class="gallery-counter">
                  <span class="gallery-current">1</span> / <span class="gallery-total">${this.galleryImages.length}</span>
                </div>
                <button type="button" class="gallery-nav-btn gallery-next" aria-label="Image suivante">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8.5 6.5c-.3.2-.5.5-.5.9v9.2c0 .4.2.7.5.9.3.2.7.2 1 0l6-4.6c.3-.2.5-.6.5-1s-.2-.8-.5-1l-6-4.6c-.3-.2-.7-.2-1 .2z"></path>
                  </svg>
                </button>
              </div>
              <div class="quick-view-gallery-thumbs">
                ${this.galleryImages.map((img, index) => `
                  <button type="button" class="gallery-thumb ${index === 0 ? 'active' : ''}" data-index="${index}">
                    <img src="${img.src}" alt="${img.alt || product.name}">
                  </button>
                `).join('')}
              </div>
            ` : ''}
          </div>
          <div class="quick-view-info">
            <h2 id="quick-view-title" class="quick-view-title">${product.name}</h2>
            <div class="quick-view-description">${product.short_description || ''}</div>
            <div class="quick-view-price">${product.price_html}</div>
            <div class="quick-view-actions">
              <a href="${product.permalink}" class="btn-view-full">
                Voir la fiche complète →
              </a>
            </div>
          </div>
        </div>
      `;

      this.body.innerHTML = html;
      this.loading.style.display = 'none';
      this.body.style.display = 'block';

      // Get progress bar reference
      this.progressBar = this.body.querySelector('.quick-view-progress-bar');

      // Bind gallery navigation
      if (this.galleryImages.length > 1) {
        this.bindGalleryEvents();
        // Start auto-advance if multiple images
        this.startAutoAdvance();
      }
    },

    bindGalleryEvents: function() {
      const prevBtn = this.body.querySelector('.gallery-prev');
      const nextBtn = this.body.querySelector('.gallery-next');
      const thumbs = this.body.querySelectorAll('.gallery-thumb');
      const gallery = this.body.querySelector('.quick-view-gallery');

      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          this.pauseAutoAdvance();
          this.prevImage();
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          this.pauseAutoAdvance();
          this.nextImage();
        });
      }

      thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
          this.pauseAutoAdvance();
          const index = parseInt(thumb.dataset.index);
          this.goToImage(index);
        });
      });

      // Pause on gallery hover, resume on leave
      if (gallery) {
        gallery.addEventListener('mouseenter', () => {
          this.pauseAutoAdvance();
        });

        gallery.addEventListener('mouseleave', () => {
          this.resumeAutoAdvance();
        });
      }

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (this.modal.getAttribute('aria-hidden') === 'false') {
          if (e.key === 'ArrowLeft') {
            e.preventDefault();
            this.pauseAutoAdvance();
            this.prevImage();
          } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            this.pauseAutoAdvance();
            this.nextImage();
          }
        }
      });
    },

    goToImage: function(index) {
      if (index < 0 || index >= this.galleryImages.length) return;

      this.currentGalleryIndex = index;
      const img = this.galleryImages[index];
      const mainImage = this.body.querySelector('.quick-view-gallery-main img');
      const counter = this.body.querySelector('.gallery-current');
      const thumbs = this.body.querySelectorAll('.gallery-thumb');

      if (mainImage) {
        mainImage.src = img.src;
        mainImage.alt = img.alt || '';
      }

      if (counter) {
        counter.textContent = index + 1;
      }

      thumbs.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
      });

      // Restart auto-advance with new progress bar
      this.startAutoAdvance();
    },

    prevImage: function() {
      const newIndex = this.currentGalleryIndex - 1;
      if (newIndex < 0) {
        this.goToImage(this.galleryImages.length - 1);
      } else {
        this.goToImage(newIndex);
      }
    },

    nextImage: function() {
      const newIndex = this.currentGalleryIndex + 1;
      if (newIndex >= this.galleryImages.length) {
        this.goToImage(0);
      } else {
        this.goToImage(newIndex);
      }
    },

    updateGallery: function(productData) {
      // Update gallery with new images
      this.galleryImages = productData.images;

      const mainImage = this.body.querySelector('.quick-view-gallery-main img');
      const thumbsContainer = this.body.querySelector('.quick-view-gallery-thumbs');
      const galleryTotal = this.body.querySelector('.gallery-total');

      // Update total count
      if (galleryTotal) {
        galleryTotal.textContent = this.galleryImages.length;
      }

      // Re-create thumbnails
      if (thumbsContainer && this.galleryImages.length > 1) {
        thumbsContainer.innerHTML = this.galleryImages.map((img, index) => `
          <button type="button" class="gallery-thumb ${index === this.currentGalleryIndex ? 'active' : ''}" data-index="${index}">
            <img src="${img.src}" alt="${img.alt || productData.name}">
          </button>
        `).join('');

        // Re-bind click events
        thumbsContainer.querySelectorAll('.gallery-thumb').forEach(thumb => {
          thumb.addEventListener('click', () => {
            const index = parseInt(thumb.dataset.index);
            this.goToImage(index);
          });
        });
      }
    },

    updateProductInfo: function(productData) {
      console.log('[Quick View Debug] updateProductInfo called');

      // Update description if found
      const descriptionEl = this.body.querySelector('.quick-view-description');
      console.log('[Quick View Debug] Description element found:', !!descriptionEl);
      console.log('[Quick View Debug] Description to set:', productData.short_description?.substring(0, 50));

      if (descriptionEl && productData.short_description) {
        descriptionEl.textContent = productData.short_description;
        console.log('[Quick View Debug] Description updated in DOM');
      } else {
        console.log('[Quick View Debug] Description NOT updated - element:', !!descriptionEl, 'data:', !!productData.short_description);
      }

      // Update or add variants section
      let variantsEl = this.body.querySelector('.quick-view-variants');

      if (!variantsEl && (productData.sizes || productData.woods)) {
        // Create variants section if it doesn't exist
        const priceEl = this.body.querySelector('.quick-view-price');
        if (priceEl) {
          variantsEl = document.createElement('div');
          variantsEl.className = 'quick-view-variants';
          priceEl.parentNode.insertBefore(variantsEl, priceEl.nextSibling);
        }
      }

      if (variantsEl) {
        let variantsHTML = '';

        if (productData.sizes && productData.sizes.length > 0) {
          variantsHTML += `
            <div class="quick-view-variant-group">
              <div class="quick-view-variant-label">Tailles disponibles</div>
              <div class="quick-view-variant-values">
                ${productData.sizes.map(size => `<strong>${size}</strong>`).join(', ')}
              </div>
            </div>
          `;
        }

        if (productData.woods && productData.woods.length > 0) {
          variantsHTML += `
            <div class="quick-view-variant-group">
              <div class="quick-view-variant-label">Essences disponibles</div>
              <div class="quick-view-variant-values">
                ${productData.woods.map(wood => `<strong>${wood}</strong>`).join(', ')}
              </div>
            </div>
          `;
        }

        variantsEl.innerHTML = variantsHTML;
      }
    },

    // =============================================
    // AUTO-ADVANCE SLIDESHOW
    // =============================================
    startAutoAdvance: function() {
      if (this.galleryImages.length <= 1) return;

      this.stopAutoAdvance(); // Clear any existing timer

      // Start progress bar animation
      if (this.progressBar) {
        this.progressBar.style.transition = 'none';
        this.progressBar.style.width = '0%';

        // Force reflow to restart animation
        void this.progressBar.offsetWidth;

        this.progressBar.style.transition = `width ${this.autoAdvanceInterval}ms linear`;
        this.progressBar.style.width = '100%';
      }

      // Set timer to advance to next image
      this.autoAdvanceTimer = setTimeout(() => {
        this.nextImage();
      }, this.autoAdvanceInterval);
    },

    stopAutoAdvance: function() {
      if (this.autoAdvanceTimer) {
        clearTimeout(this.autoAdvanceTimer);
        this.autoAdvanceTimer = null;
      }

      if (this.progressBar) {
        this.progressBar.style.transition = 'none';
        this.progressBar.style.width = '0%';
      }
    },

    pauseAutoAdvance: function() {
      this.stopAutoAdvance();
    },

    resumeAutoAdvance: function() {
      this.startAutoAdvance();
    },

    showError: function() {
      this.body.innerHTML = `
        <div class="quick-view-error">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <p>Impossible de charger les informations du produit.</p>
        </div>
      `;
      this.loading.style.display = 'none';
      this.body.style.display = 'block';
    }
  };

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => QuickView.init());
  } else {
    QuickView.init();
  }

})();
