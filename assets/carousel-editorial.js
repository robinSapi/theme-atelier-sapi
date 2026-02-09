/**
 * Atelier Éditorial Carousel
 * Enhanced product carousel with Patricia Urquiola-inspired design
 * Features: organic overlap, parallax, momentum drag, elastic resistance, lazy loading
 */

(function() {
  'use strict';

  const CarouselEditorial = {
    // Elements
    wrapper: null,
    track: null,
    slides: [],
    prevBtn: null,
    nextBtn: null,
    counterCurrent: null,
    counterTotal: null,
    thumbnailsContainer: null,

    // State
    currentIndex: 0,
    isDragging: false,
    startX: 0,
    currentX: 0,
    dragOffset: 0,
    velocity: 0,
    lastMoveTime: 0,
    lastMoveX: 0,

    // Configuration - Urquiola organic values
    config: {
      snapThreshold: 0.3,      // Snap to next slide at 30% drag
      elasticResistance: 0.4,  // Resistance when dragging beyond bounds
      momentumMultiplier: 0.8, // Momentum after release
      momentumDecay: 0.92,     // How quickly momentum decays
      animationDuration: 600,  // ms - organic transition
      overlapAmount: 80,       // px - organic overlap between slides
      parallaxImage: 20,       // px - image parallax movement
      parallaxText: 10,        // px - text parallax movement
    },

    // Organic easing (Urquiola signature)
    easing: 'cubic-bezier(0.34, 0.61, 0.4, 0.97)',

    init: function() {
      this.wrapper = document.querySelector('[data-carousel-editorial]');
      if (!this.wrapper) return;

      this.track = this.wrapper.querySelector('.products-carousel-editorial-track');
      this.slides = Array.from(this.wrapper.querySelectorAll('.carousel-editorial-slide'));
      this.prevBtn = this.wrapper.querySelector('.carousel-editorial-prev');
      this.nextBtn = this.wrapper.querySelector('.carousel-editorial-next');
      this.counterCurrent = this.wrapper.querySelector('.counter-current');
      this.counterTotal = this.wrapper.querySelector('.counter-total');
      this.thumbnailsContainer = this.wrapper.querySelector('.carousel-editorial-thumbnails');

      if (!this.track || this.slides.length === 0) return;

      this.setupLazyLoading();
      this.createThumbnails();
      this.bindEvents();
      this.updateCarousel(0);
      this.updateCounter();
      this.setupCustomCursor();
    },

    // =============================================
    // LAZY LOADING - Performance optimization
    // =============================================
    setupLazyLoading: function() {
      const observerOptions = {
        root: null,
        rootMargin: '200px', // Load 200px before entering viewport
        threshold: 0.01
      };

      const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const slide = entry.target;
            const images = slide.querySelectorAll('img[data-src]');
            images.forEach(img => {
              img.src = img.dataset.src;
              img.removeAttribute('data-src');
            });
            imageObserver.unobserve(slide);
          }
        });
      }, observerOptions);

      // Observe all slides
      this.slides.forEach(slide => imageObserver.observe(slide));

      // Load first 3 slides immediately
      this.slides.slice(0, 3).forEach(slide => {
        const images = slide.querySelectorAll('img[data-src]');
        images.forEach(img => {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
        });
      });
    },

    // =============================================
    // THUMBNAILS - Elegant navigation
    // =============================================
    createThumbnails: function() {
      if (!this.thumbnailsContainer) return;

      this.thumbnailsContainer.innerHTML = '';

      this.slides.forEach((slide, index) => {
        const thumbnailUrl = slide.dataset.thumbnail;
        const thumb = document.createElement('button');
        thumb.className = 'carousel-thumbnail';
        thumb.setAttribute('aria-label', `Aller au produit ${index + 1}`);
        thumb.dataset.index = index;

        if (thumbnailUrl) {
          thumb.style.backgroundImage = `url(${thumbnailUrl})`;
        }

        if (index === 0) {
          thumb.classList.add('active');
        }

        thumb.addEventListener('click', () => {
          this.goToSlide(index);
        });

        this.thumbnailsContainer.appendChild(thumb);
      });
    },

    // =============================================
    // EVENT BINDING
    // =============================================
    bindEvents: function() {
      // Navigation buttons
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', () => this.prev());
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', () => this.next());
      }

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (!this.isCarouselInView()) return;

        if (e.key === 'ArrowLeft') {
          e.preventDefault();
          this.prev();
        } else if (e.key === 'ArrowRight') {
          e.preventDefault();
          this.next();
        }
      });

      // Mouse drag with momentum
      this.track.addEventListener('mousedown', (e) => this.handleDragStart(e));
      document.addEventListener('mousemove', (e) => this.handleDragMove(e));
      document.addEventListener('mouseup', (e) => this.handleDragEnd(e));

      // Touch drag with momentum
      this.track.addEventListener('touchstart', (e) => this.handleDragStart(e), { passive: false });
      document.addEventListener('touchmove', (e) => this.handleDragMove(e), { passive: false });
      document.addEventListener('touchend', (e) => this.handleDragEnd(e));

      // Prevent click during drag
      this.track.addEventListener('click', (e) => {
        if (this.isDragging || Math.abs(this.dragOffset) > 5) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);

      // Window resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          this.updateCarousel(0);
        }, 200);
      });
    },

    // =============================================
    // CUSTOM CURSOR - Artisanal detail
    // =============================================
    setupCustomCursor: function() {
      this.track.style.cursor = 'grab';

      this.track.addEventListener('mousedown', () => {
        this.track.style.cursor = 'grabbing';
      });

      document.addEventListener('mouseup', () => {
        if (!this.isDragging) {
          this.track.style.cursor = 'grab';
        }
      });

      this.track.addEventListener('mouseenter', () => {
        if (!this.isDragging) {
          this.track.style.cursor = 'grab';
        }
      });

      this.track.addEventListener('mouseleave', () => {
        this.track.style.cursor = 'grab';
      });
    },

    // =============================================
    // DRAG WITH MOMENTUM - Urquiola micro-interaction
    // =============================================
    handleDragStart: function(e) {
      this.isDragging = true;
      this.startX = this.getPositionX(e);
      this.currentX = this.startX;
      this.dragOffset = 0;
      this.velocity = 0;
      this.lastMoveTime = Date.now();
      this.lastMoveX = this.startX;

      // Cancel any ongoing animation
      if (this.momentumAnimation) {
        cancelAnimationFrame(this.momentumAnimation);
      }

      this.track.style.transition = 'none';
      this.track.style.cursor = 'grabbing';
    },

    handleDragMove: function(e) {
      if (!this.isDragging) return;

      e.preventDefault();

      const currentTime = Date.now();
      const currentX = this.getPositionX(e);
      const deltaX = currentX - this.currentX;
      const deltaTime = currentTime - this.lastMoveTime;

      // Calculate velocity for momentum
      if (deltaTime > 0) {
        this.velocity = deltaX / deltaTime;
      }

      this.currentX = currentX;
      this.dragOffset = this.currentX - this.startX;

      // Apply elastic resistance at boundaries
      const slideWidth = this.getSlideWidth();
      const maxOffset = (this.slides.length - 1) * slideWidth;
      const currentOffset = this.currentIndex * slideWidth;

      let adjustedDragOffset = this.dragOffset;

      // Check boundaries and apply elastic resistance
      if (currentOffset - this.dragOffset < 0) {
        // Dragging past first slide
        const overDrag = Math.abs(currentOffset - this.dragOffset);
        adjustedDragOffset = this.dragOffset + overDrag * this.config.elasticResistance;
      } else if (currentOffset - this.dragOffset > maxOffset) {
        // Dragging past last slide
        const overDrag = (currentOffset - this.dragOffset) - maxOffset;
        adjustedDragOffset = this.dragOffset - overDrag * this.config.elasticResistance;
      }

      // Update track position with adjusted drag
      this.updateTrackPosition(-currentOffset + adjustedDragOffset, false);

      // Apply parallax effect during drag
      this.applyParallax(adjustedDragOffset);

      this.lastMoveTime = currentTime;
      this.lastMoveX = currentX;
    },

    handleDragEnd: function(e) {
      if (!this.isDragging) return;

      this.isDragging = false;
      this.track.style.cursor = 'grab';

      const slideWidth = this.getSlideWidth();

      // Determine target index based on drag distance and velocity
      let targetIndex = this.currentIndex;

      // Check if drag distance exceeds snap threshold
      if (Math.abs(this.dragOffset) > slideWidth * this.config.snapThreshold) {
        if (this.dragOffset > 0) {
          targetIndex = Math.max(0, this.currentIndex - 1);
        } else {
          targetIndex = Math.min(this.slides.length - 1, this.currentIndex + 1);
        }
      }

      // Apply momentum if velocity is high enough
      const momentumThreshold = 0.5;
      if (Math.abs(this.velocity) > momentumThreshold) {
        if (this.velocity > 0) {
          targetIndex = Math.max(0, this.currentIndex - 1);
        } else {
          targetIndex = Math.min(this.slides.length - 1, this.currentIndex + 1);
        }
      }

      // Animate to target slide
      this.goToSlide(targetIndex);

      // Reset drag offset
      this.dragOffset = 0;
    },

    getPositionX: function(e) {
      return e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
    },

    // =============================================
    // PARALLAX EFFECT - Multilayer depth
    // =============================================
    applyParallax: function(offset) {
      const normalizedOffset = offset / this.getSlideWidth();

      this.slides.forEach((slide, index) => {
        const distance = index - this.currentIndex;
        const media = slide.querySelector('.product-media');
        const info = slide.querySelector('.product-info');

        // Calculate parallax values based on distance from current slide
        const imageParallax = distance * this.config.parallaxImage * normalizedOffset;
        const textParallax = distance * this.config.parallaxText * normalizedOffset;

        // Apply GPU-accelerated transforms
        if (media) {
          media.style.transform = `translate3d(${imageParallax}px, 0, 0)`;
        }
        if (info) {
          info.style.transform = `translate3d(${textParallax}px, 0, 0)`;
        }
      });
    },

    // =============================================
    // NAVIGATION
    // =============================================
    prev: function() {
      if (this.currentIndex > 0) {
        this.goToSlide(this.currentIndex - 1);
      }
    },

    next: function() {
      if (this.currentIndex < this.slides.length - 1) {
        this.goToSlide(this.currentIndex + 1);
      }
    },

    goToSlide: function(index) {
      if (index < 0 || index >= this.slides.length) return;
      if (index === this.currentIndex) return;

      this.currentIndex = index;
      this.updateCarousel(this.config.animationDuration);
      this.updateCounter();
      this.updateThumbnails();
      this.updateNavigationButtons();
    },

    // =============================================
    // UPDATE CAROUSEL
    // =============================================
    updateCarousel: function(duration) {
      const slideWidth = this.getSlideWidth();
      const offset = this.currentIndex * slideWidth;

      this.updateTrackPosition(-offset, duration > 0);

      // Apply parallax reset
      this.applyParallax(0);

      // Update overlap classes for organic stacking
      this.updateOverlap();
    },

    updateTrackPosition: function(offset, useTransition) {
      if (useTransition) {
        this.track.style.transition = `transform ${this.config.animationDuration}ms ${this.easing}`;
      } else {
        this.track.style.transition = 'none';
      }

      this.track.style.transform = `translate3d(${offset}px, 0, 0)`;
    },

    getSlideWidth: function() {
      if (this.slides.length === 0) return 0;
      const slideWidth = this.slides[0].offsetWidth;
      return slideWidth - this.config.overlapAmount; // Account for organic overlap
    },

    // =============================================
    // ORGANIC OVERLAP - Intelligent stacking
    // =============================================
    updateOverlap: function() {
      this.slides.forEach((slide, index) => {
        slide.classList.remove('is-prev', 'is-current', 'is-next', 'is-far');

        if (index === this.currentIndex) {
          slide.classList.add('is-current');
        } else if (index === this.currentIndex - 1) {
          slide.classList.add('is-prev');
        } else if (index === this.currentIndex + 1) {
          slide.classList.add('is-next');
        } else {
          slide.classList.add('is-far');
        }
      });
    },

    // =============================================
    // UI UPDATES
    // =============================================
    updateCounter: function() {
      if (this.counterCurrent) {
        this.counterCurrent.textContent = this.currentIndex + 1;
      }
    },

    updateThumbnails: function() {
      if (!this.thumbnailsContainer) return;

      const thumbnails = this.thumbnailsContainer.querySelectorAll('.carousel-thumbnail');
      thumbnails.forEach((thumb, index) => {
        thumb.classList.toggle('active', index === this.currentIndex);
      });
    },

    updateNavigationButtons: function() {
      if (this.prevBtn) {
        this.prevBtn.disabled = this.currentIndex === 0;
        this.prevBtn.style.opacity = this.currentIndex === 0 ? '0.3' : '1';
      }
      if (this.nextBtn) {
        this.nextBtn.disabled = this.currentIndex === this.slides.length - 1;
        this.nextBtn.style.opacity = this.currentIndex === this.slides.length - 1 ? '0.3' : '1';
      }
    },

    // =============================================
    // UTILITIES
    // =============================================
    isCarouselInView: function() {
      if (!this.wrapper) return false;

      const rect = this.wrapper.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    }
  };

  // =============================================
  // INIT
  // =============================================
  function init() {
    CarouselEditorial.init();
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
