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

      // Show modal with loading state
      this.modal.setAttribute('aria-hidden', 'false');
      this.loading.style.display = 'flex';
      this.body.style.display = 'none';
      document.body.style.overflow = 'hidden';

      // Fetch product data
      this.fetchProductData(productId, productUrl);
    },

    closeModal: function() {
      this.modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      this.body.innerHTML = '';
      this.currentGalleryIndex = 0;
      this.galleryImages = [];
    },

    fetchProductData: function(productId, productUrl) {
      // Use WordPress REST API to get product data
      fetch(`${window.location.origin}/wp-json/wc/v3/products/${productId}`)
        .then(response => {
          // If REST API fails, fetch from product URL
          if (!response.ok) {
            return this.fetchFromPage(productUrl);
          }
          return response.json();
        })
        .then(product => {
          if (product) {
            this.renderProduct(product);
          } else {
            this.showError();
          }
        })
        .catch(error => {
          console.error('Quick view error:', error);
          // Fallback: fetch from product page
          this.fetchFromPage(productUrl);
        });
    },

    fetchFromPage: function(productUrl) {
      return fetch(productUrl)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          // Extract product data from page
          const productData = {
            name: doc.querySelector('.product-title-v2')?.textContent || '',
            price_html: doc.querySelector('.product-price-v2 .price-amount')?.innerHTML || '',
            short_description: doc.querySelector('.product-tagline')?.textContent || '',
            images: [],
            permalink: productUrl
          };

          // Get main image
          const mainImage = doc.querySelector('.product-gallery-main img');
          if (mainImage) {
            productData.images.push({
              src: mainImage.src,
              alt: mainImage.alt || productData.name
            });
          }

          // Get gallery images
          const galleryThumbs = doc.querySelectorAll('.product-gallery-thumbnails button');
          galleryThumbs.forEach((thumb, index) => {
            if (index > 0) { // Skip first (already added as main)
              const img = thumb.querySelector('img');
              if (img && img.dataset.fullUrl) {
                productData.images.push({
                  src: img.dataset.fullUrl,
                  alt: img.alt || productData.name
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
