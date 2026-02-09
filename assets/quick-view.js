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

      // Fetch product data with card context
      this.fetchProductData(productId, productUrl, productCard);
    },

    closeModal: function() {
      this.modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      this.body.innerHTML = '';
      this.currentGalleryIndex = 0;
      this.galleryImages = [];
    },

    fetchProductData: function(productId, productUrl, productCard) {
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
        // Load additional images in background
        this.loadAdditionalImages(productUrl, productData);
      } else {
        // Fallback: fetch from product page
        this.fetchFromPage(productUrl);
      }
    },

    loadAdditionalImages: function(productUrl, productData) {
      // Load gallery images from product page in background
      fetch(productUrl)
        .then(response => response.text())
        .then(html => {
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

          // Add additional images to existing ones
          if (additionalImages.length > 0) {
            productData.images = productData.images.concat(additionalImages);
            // Re-render gallery if modal still open
            if (this.modal.getAttribute('aria-hidden') === 'false') {
              this.updateGallery(productData);
            }
          }
        })
        .catch(error => {
          console.log('Could not load additional images:', error);
          // Not critical, we already have basic images
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
          const productData = {
            name: doc.querySelector('.product-title-v2, h1')?.textContent?.trim() || '',
            price_html: doc.querySelector('.product-price-v2 .price-amount, .price')?.innerHTML || '',
            short_description: doc.querySelector('.product-tagline')?.textContent?.trim() || '',
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
            </div>
            ${this.galleryImages.length > 1 ? `
              <div class="quick-view-gallery-nav">
                <button type="button" class="gallery-nav-btn gallery-prev" aria-label="Image précédente">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                  </svg>
                </button>
                <div class="gallery-counter">
                  <span class="gallery-current">1</span> / <span class="gallery-total">${this.galleryImages.length}</span>
                </div>
                <button type="button" class="gallery-nav-btn gallery-next" aria-label="Image suivante">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
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
            <div class="quick-view-price">${product.price_html}</div>
            ${product.short_description ? `
              <div class="quick-view-description">
                ${product.short_description}
              </div>
            ` : ''}
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

      // Bind gallery navigation
      if (this.galleryImages.length > 1) {
        this.bindGalleryEvents();
      }
    },

    bindGalleryEvents: function() {
      const prevBtn = this.body.querySelector('.gallery-prev');
      const nextBtn = this.body.querySelector('.gallery-next');
      const thumbs = this.body.querySelectorAll('.gallery-thumb');

      if (prevBtn) {
        prevBtn.addEventListener('click', () => this.prevImage());
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => this.nextImage());
      }

      thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
          const index = parseInt(thumb.dataset.index);
          this.goToImage(index);
        });
      });

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (this.modal.getAttribute('aria-hidden') === 'false') {
          if (e.key === 'ArrowLeft') {
            e.preventDefault();
            this.prevImage();
          } else if (e.key === 'ArrowRight') {
            e.preventDefault();
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
