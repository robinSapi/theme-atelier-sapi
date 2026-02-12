// ========================================
// SAPI CINÉTIQUE - Front Page Interactions
// ========================================

document.addEventListener('DOMContentLoaded', () => {

  // ========================================
  // Custom Cursor
  // ========================================
  const cursor = document.querySelector('.cursor-custom');
  const cursorDot = document.querySelector('.cursor-dot');
  const cursorOutline = document.querySelector('.cursor-outline');

  // Only initialize cursor on desktop (no touch)
  if (cursor && cursorDot && cursorOutline && !('ontouchstart' in window)) {
    let mouseX = 0;
    let mouseY = 0;
    let cursorX = 0;
    let cursorY = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;

      // Dot follows mouse directly
      cursorDot.style.left = mouseX + 'px';
      cursorDot.style.top = mouseY + 'px';
    });

    // Outline follows with smooth delay
    function animateCursor() {
      cursorX += (mouseX - cursorX) * 0.15;
      cursorY += (mouseY - cursorY) * 0.15;

      cursorOutline.style.left = cursorX + 'px';
      cursorOutline.style.top = cursorY + 'px';

      requestAnimationFrame(animateCursor);
    }
    animateCursor();

    // Hover effects on interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .bento-card, .collection-card');

    interactiveElements.forEach(el => {
      el.addEventListener('mouseenter', () => {
        document.body.classList.add('cursor-hover');
      });

      el.addEventListener('mouseleave', () => {
        document.body.classList.remove('cursor-hover');
      });
    });
  }

  // ========================================
  // Bento Cards Animation on Scroll
  // ========================================
  const bentoCards = document.querySelectorAll('.bento-card');

  if (bentoCards.length > 0) {
    const cardObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }, index * 100);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -100px 0px'
    });

    bentoCards.forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      card.style.transition = 'all 0.8s cubic-bezier(0.87, 0, 0.13, 1)';
      cardObserver.observe(card);
    });
  }

  // ========================================
  // Product Cards - Parallax on Mouse Move
  // ========================================
  const productCards = document.querySelectorAll('.bento-product');

  productCards.forEach(card => {
    const productImage = card.querySelector('.product-image');
    if (!productImage) return;

    let moveRAF = null;
    let rect = null;

    card.addEventListener('mouseenter', () => {
      rect = card.getBoundingClientRect();
    });

    card.addEventListener('mousemove', (e) => {
      if (moveRAF || !rect) return;
      moveRAF = requestAnimationFrame(() => {
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const rotateX = (y - rect.height / 2) / 20;
        const rotateY = (rect.width / 2 - x) / 20;
        productImage.style.transform = `scale(1.1) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        moveRAF = null;
      });
    });

    card.addEventListener('mouseleave', () => {
      if (moveRAF) { cancelAnimationFrame(moveRAF); moveRAF = null; }
      productImage.style.transform = 'scale(1) rotateX(0) rotateY(0)';
    });
  });

  // ========================================
  // Collection Cards Animation
  // ========================================
  const collectionCards = document.querySelectorAll('.collection-card');

  if (collectionCards.length > 0) {
    const collectionObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }, index * 150);
        }
      });
    }, {
      threshold: 0.2
    });

    collectionCards.forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(50px)';
      card.style.transition = 'all 0.8s cubic-bezier(0.87, 0, 0.13, 1)';
      collectionObserver.observe(card);
    });
  }

  // ========================================
  // Newsletter Form
  // ========================================
  const newsletterForm = document.querySelector('.newsletter-form');

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const input = newsletterForm.querySelector('.newsletter-input-kinetic');
      const button = newsletterForm.querySelector('.newsletter-submit-kinetic');
      const buttonText = button.querySelector('span');

      if (!input.value || !input.validity.valid) {
        input.focus();
        return;
      }

      // Visual feedback
      buttonText.textContent = 'Inscrit !';
      button.style.background = 'var(--color-green)';
      input.disabled = true;

      showNotification('Inscription reussie !');

      // Reset after delay
      setTimeout(() => {
        buttonText.textContent = "S'inscrire";
        button.style.background = 'var(--color-wood)';
        input.disabled = false;
        input.value = '';
      }, 3000);
    });
  }

  // ========================================
  // Notification System
  // ========================================
  function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification-kinetic';
    notification.innerHTML = `
      <span class="notification-icon">●</span>
      <span class="notification-text">${message}</span>
    `;

    notification.style.cssText = `
      position: fixed;
      top: 6rem;
      right: 1.5rem;
      background: var(--color-white, #fff);
      border: 2px solid var(--color-wood, #937D68);
      color: var(--color-dark, #323232);
      padding: 1rem 1.5rem;
      border-radius: 50px;
      font-family: var(--font-body, 'Montserrat', sans-serif);
      font-weight: 400;
      font-size: 0.875rem;
      z-index: 99999;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      box-shadow: 0 10px 40px rgba(147, 125, 104, 0.2);
      animation: slideInNotif 0.5s cubic-bezier(0.87, 0, 0.13, 1);
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = 'slideOutNotif 0.5s cubic-bezier(0.87, 0, 0.13, 1) forwards';
      setTimeout(() => {
        notification.remove();
      }, 500);
    }, 3000);
  }

  // Add notification animations to head
  if (!document.querySelector('#cinetique-animations')) {
    const style = document.createElement('style');
    style.id = 'cinetique-animations';
    style.textContent = `
      @keyframes slideInNotif {
        from {
          transform: translateX(100%);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes slideOutNotif {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(100%);
          opacity: 0;
        }
      }
      .notification-icon {
        color: var(--color-wood, #937D68);
        font-size: 1.25rem;
      }
      @keyframes rainbow {
        0%, 100% { filter: hue-rotate(0deg); }
        50% { filter: hue-rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
  }

  // ========================================
  // PREMIUM: Advanced Parallax Effect on Multiple Sections
  // ========================================
  let ticking = false;

  // Mark elements with data-parallax attribute for auto-discovery
  const parallaxElements = document.querySelectorAll('[data-parallax]');
  parallaxElements.forEach(el => {
    const speed = parseFloat(el.getAttribute('data-parallax')) || 0.5;
    el.dataset.parallaxSpeed = speed;
  });

  // Cache DOM selectors for scroll handler (avoid querySelectorAll per frame)
  const cachedScroll = {
    heroImage: document.querySelector('.bento-hero .bento-bg'),
    shopMagazineHero: document.querySelector('.shop-hero-magazine-bg'),
    categoryHeroImage: document.querySelector('.category-hero-visual img'),
    featuredCardsImages: document.querySelectorAll('.category-featured-media img'),
    header: document.querySelector('.site-header'),
  };

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;

        // Parallax on hero image (homepage) - DISABLED
        // if (cachedScroll.heroImage) {
        //   cachedScroll.heroImage.style.transform = `translateY(${scrolled * 0.1}px) scale(1.05)`;
        // }

        // Parallax on shop magazine hero
        if (cachedScroll.shopMagazineHero) {
          cachedScroll.shopMagazineHero.style.transform = `translateY(${scrolled * 0.1}px) scale(1.05)`;
        }

        // Parallax on category hero images
        if (cachedScroll.categoryHeroImage) {
          cachedScroll.categoryHeroImage.style.transform = `translateY(${scrolled * 0.08}px) scale(1.05)`;
        }

        // Parallax on featured cards images
        cachedScroll.featuredCardsImages.forEach(img => {
          const rect = img.getBoundingClientRect();
          if (rect.top < window.innerHeight && rect.bottom > 0) {
            const offset = (window.innerHeight - rect.top) * 0.03;
            img.style.transform = `translateY(-${offset}px) scale(1.05)`;
          }
        });

        // Auto-discovered parallax elements
        parallaxElements.forEach(el => {
          const rect = el.getBoundingClientRect();
          if (rect.top < window.innerHeight && rect.bottom > 0) {
            const speed = parseFloat(el.dataset.parallaxSpeed);
            const offset = (window.innerHeight - rect.top) * speed * 0.01;
            el.style.transform = `translateY(-${offset}px)`;
          }
        });

        // Header background opacity on scroll
        if (cachedScroll.header) {
          if (scrolled > 100) {
            cachedScroll.header.style.background = 'rgba(254, 253, 251, 0.98)';
            cachedScroll.header.style.boxShadow = '0 2px 20px rgba(50, 50, 50, 0.12)';
          } else {
            cachedScroll.header.style.background = 'rgba(254, 253, 251, 0.95)';
            cachedScroll.header.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
          }
        }

        ticking = false;
      });

      ticking = true;
    }
  }, { passive: true });

  // ========================================
  // PREMIUM: Canvas Wood-Themed Particles Background
  // ========================================
  const canvasContainers = document.querySelectorAll('[data-particles="wood"]');

  canvasContainers.forEach(container => {
    const canvas = document.createElement('canvas');
    canvas.className = 'particles-canvas';
    canvas.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
      opacity: 0.4;
    `;

    container.style.position = 'relative';
    container.insertBefore(canvas, container.firstChild);

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationId;

    // Resize canvas
    function resizeCanvas() {
      canvas.width = container.offsetWidth;
      canvas.height = container.offsetHeight;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Particle class
    class Particle {
      constructor() {
        this.reset();
        this.y = Math.random() * canvas.height;
      }

      reset() {
        this.x = Math.random() * canvas.width;
        this.y = -10;
        this.size = Math.random() * 3 + 1;
        this.speedY = Math.random() * 0.5 + 0.2;
        this.speedX = Math.random() * 0.3 - 0.15;
        this.opacity = Math.random() * 0.5 + 0.2;
        // Wood-themed colors: warm browns, golds, creams
        const colors = ['#937D68', '#C5A880', '#8B7355', '#E8DCC8', '#B89968'];
        this.color = colors[Math.floor(Math.random() * colors.length)];
      }

      update() {
        this.y += this.speedY;
        this.x += this.speedX;

        // Reset if out of bounds
        if (this.y > canvas.height) {
          this.reset();
        }
        if (this.x < 0 || this.x > canvas.width) {
          this.x = Math.random() * canvas.width;
        }
      }

      draw() {
        ctx.fillStyle = this.color;
        ctx.globalAlpha = this.opacity;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
      }
    }

    // Create particles
    const particleCount = Math.min(50, Math.floor(canvas.width / 20));
    for (let i = 0; i < particleCount; i++) {
      particles.push(new Particle());
    }

    // Animation loop
    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      particles.forEach(particle => {
        particle.update();
        particle.draw();
      });

      animationId = requestAnimationFrame(animate);
    }

    // Start animation only when in view
    const canvasObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animate();
        } else {
          cancelAnimationFrame(animationId);
        }
      });
    });
    canvasObserver.observe(container);
  });

  // ========================================
  // Smooth Scroll for Anchor Links
  // ========================================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);

      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ========================================
  // Performance: Reduced Motion Support
  // ========================================
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (prefersReducedMotion.matches) {
    document.documentElement.style.setProperty('--ease-expo', 'ease');
    document.documentElement.style.setProperty('--ease-smooth', 'ease');
  }

  // ========================================
  // Image Lazy Loading Enhancement
  // ========================================
  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.style.opacity = '1';
        imageObserver.unobserve(img);
      }
    });
  });

  document.querySelectorAll('.product-image, .collection-visual').forEach(img => {
    img.style.opacity = '0';
    img.style.transition = 'opacity 0.6s ease';
    imageObserver.observe(img);
  });

  // ========================================
  // Keyboard Shortcuts
  // ========================================
  document.addEventListener('keydown', (e) => {
    // Don't trigger if typing in input
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    // C key scrolls to collections
    if (e.key === 'c' || e.key === 'C') {
      const collectionsSection = document.querySelector('.collections-kinetic');
      if (collectionsSection) {
        collectionsSection.scrollIntoView({ behavior: 'smooth' });
      }
    }
  });

  // ========================================
  // Easter Egg: Konami Code
  // ========================================
  let konamiCode = [];
  const konamiSequence = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];

  document.addEventListener('keydown', (e) => {
    konamiCode.push(e.key);
    konamiCode = konamiCode.slice(-10);

    if (konamiCode.join(',') === konamiSequence.join(',')) {
      document.body.style.animation = 'rainbow 2s infinite';
      showNotification('Konami Code active ! Mode arc-en-ciel !');

      setTimeout(() => {
        document.body.style.animation = '';
      }, 5000);
    }
  });

  // ========================================
  // Product Page - Premium Material Swatches
  // ========================================
  const materialSwatches = document.querySelectorAll('.material-option');

  if (materialSwatches.length > 0) {
    materialSwatches.forEach(swatch => {
      swatch.addEventListener('click', function(e) {
        e.preventDefault();

        const attributeContainer = this.closest('.attribute-swatch');
        if (!attributeContainer) return;

        // Remove selected class from siblings
        const siblings = attributeContainer.querySelectorAll('.material-option');
        siblings.forEach(s => {
          s.classList.remove('selected', 'just-selected');
        });

        // Add selected class to clicked swatch
        this.classList.add('selected', 'just-selected');

        // Remove animation class after animation completes
        setTimeout(() => {
          this.classList.remove('just-selected');
        }, 250);

        // Update hidden select for WooCommerce compatibility
        const selectElement = attributeContainer.nextElementSibling;
        if (selectElement && selectElement.tagName === 'SELECT') {
          const value = this.getAttribute('data-value');
          selectElement.value = value;

          // Trigger change event for WooCommerce variations
          const event = new Event('change', { bubbles: true });
          selectElement.dispatchEvent(event);

          // jQuery trigger for older WooCommerce versions
          if (typeof jQuery !== 'undefined') {
            jQuery(selectElement).trigger('change');
          }
        }
      });
    });

    // Sync initial selection from select to swatches (if any)
    const attributeContainers = document.querySelectorAll('.attribute-swatch');
    attributeContainers.forEach(container => {
      const selectElement = container.nextElementSibling;
      if (selectElement && selectElement.tagName === 'SELECT' && selectElement.value) {
        const selectedValue = selectElement.value;
        const correspondingSwatch = container.querySelector(`[data-value="${selectedValue}"]`);
        if (correspondingSwatch) {
          correspondingSwatch.classList.add('selected');
        }
      }
    });
  }

  // ========================================
  // PREMIUM: Generic Fade-In Animation for ALL Pages
  // ========================================
  const fadeInElements = document.querySelectorAll('.editorial-block, .why-sapi-card, .use-cases-list li, .blog-card, .post-nav-item, section[class*="artisan-"], section[class*="advice-"]');

  if (fadeInElements.length > 0) {
    const fadeInObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          fadeInObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    fadeInElements.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
      fadeInObserver.observe(el);
    });
  }

  // ========================================
  // PREMIUM: Scroll Progress Indicator
  // ========================================
  const progressBar = document.createElement('div');
  progressBar.className = 'scroll-progress-bar';
  progressBar.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-orange, #E35B24) 0%, var(--bois-dore, #937D68) 100%);
    width: 0%;
    z-index: 99999;
    transition: width 0.1s ease-out;
  `;
  document.body.appendChild(progressBar);

  window.addEventListener('scroll', () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    progressBar.style.width = scrolled + '%';
  });

  // ========================================
  // PREMIUM: Enhanced Product Image Zoom
  // ========================================
  const productMainImages = document.querySelectorAll('.product-gallery-main img, .single-post-featured img');

  productMainImages.forEach(img => {
    img.addEventListener('mouseenter', function() {
      this.style.cursor = 'zoom-in';
    });

    img.addEventListener('click', function() {
      // Create zoom overlay
      const overlay = document.createElement('div');
      overlay.className = 'image-zoom-overlay';
      overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: zoom-out;
        animation: fadeIn 0.3s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      `;

      const zoomedImg = this.cloneNode(true);
      zoomedImg.style.cssText = `
        max-width: 95vw;
        max-height: 95vh;
        object-fit: contain;
        animation: scaleIn 0.4s cubic-bezier(0.87, 0, 0.13, 1);
        box-shadow: 0 20px 80px rgba(0, 0, 0, 0.5);
        border-radius: 8px;
      `;

      overlay.appendChild(zoomedImg);
      document.body.appendChild(overlay);

      // Close on click
      overlay.addEventListener('click', () => {
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => overlay.remove(), 300);
      });

      // Close on ESC key
      const closeOnEsc = (e) => {
        if (e.key === 'Escape') {
          overlay.style.animation = 'fadeOut 0.3s ease';
          setTimeout(() => overlay.remove(), 300);
          document.removeEventListener('keydown', closeOnEsc);
        }
      };
      document.addEventListener('keydown', closeOnEsc);
    });
  });

  // ========================================
  // PREMIUM: Form Field Micro-Interactions
  // ========================================
  const formInputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea, select');

  formInputs.forEach(input => {
    // Floating label effect
    input.addEventListener('focus', function() {
      this.style.transform = 'translateY(-2px)';
      this.style.transition = 'transform 0.3s ease';

      const label = this.previousElementSibling;
      if (label && label.tagName === 'LABEL') {
        label.style.transform = 'translateY(-2px) scale(0.9)';
        label.style.color = 'var(--color-orange, #E35B24)';
      }
    });

    input.addEventListener('blur', function() {
      this.style.transform = 'translateY(0)';

      const label = this.previousElementSibling;
      if (label && label.tagName === 'LABEL' && !this.value) {
        label.style.transform = 'translateY(0) scale(1)';
        label.style.color = '';
      }
    });

    // Success animation on valid input
    input.addEventListener('input', function() {
      if (this.validity.valid && this.value !== '') {
        this.style.borderColor = 'var(--vert-confiance, #018501)';
      } else {
        this.style.borderColor = '';
      }
    });
  });

  // ========================================
  // PREMIUM: Enhanced Cart Button Feedback
  // ========================================
  const addToCartButtons = document.querySelectorAll('.single_add_to_cart_button, .add_to_cart_button');

  addToCartButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      // Only if not disabled
      if (!this.disabled) {
        // Add success animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = 'scale(1)';
        }, 150);

        // Create flying icon effect
        const icon = document.createElement('span');
        icon.textContent = '🛒';
        icon.style.cssText = `
          position: fixed;
          font-size: 24px;
          pointer-events: none;
          z-index: 99999;
          animation: flyToCart 1s ease-out forwards;
        `;

        const rect = this.getBoundingClientRect();
        icon.style.left = rect.left + rect.width / 2 + 'px';
        icon.style.top = rect.top + rect.height / 2 + 'px';

        document.body.appendChild(icon);

        setTimeout(() => icon.remove(), 1000);
      }
    });
  });

  // Add flying cart animation
  if (!document.querySelector('#cart-animation-style')) {
    const style = document.createElement('style');
    style.id = 'cart-animation-style';
    style.textContent = `
      @keyframes flyToCart {
        0% {
          transform: translate(0, 0) scale(1);
          opacity: 1;
        }
        100% {
          transform: translate(calc(100vw - 100px), -80vh) scale(0.3);
          opacity: 0;
        }
      }
      @keyframes fadeOut {
        to {
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  }

  // ========================================
  // PREMIUM: Number Input Enhancements
  // ========================================
  const quantityInputs = document.querySelectorAll('input[type="number"].qty');

  quantityInputs.forEach(input => {
    const wrapper = input.parentElement;

    // Add visual feedback on change
    input.addEventListener('change', function() {
      this.style.transform = 'scale(1.05)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 200);
    });

    // Smooth transitions
    input.style.transition = 'all 0.2s ease';
  });

  // ========================================
  // PREMIUM: Back to Top Button
  // ========================================
  const backToTop = document.createElement('button');
  backToTop.className = 'back-to-top-btn';
  backToTop.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  `;
  backToTop.style.cssText = `
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-orange, #E35B24) 0%, #D14F1C 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(227, 91, 36, 0.3);
    z-index: 9999;
    transition: all 0.3s ease;
  `;

  document.body.appendChild(backToTop);

  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 500) {
      backToTop.style.display = 'flex';
      backToTop.style.animation = 'fadeIn 0.3s ease';
    } else {
      backToTop.style.display = 'none';
    }
  });

  backToTop.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  backToTop.addEventListener('mouseenter', function() {
    this.style.transform = 'translateY(-4px) scale(1.1)';
    this.style.boxShadow = '0 12px 28px rgba(227, 91, 36, 0.4)';
  });

  backToTop.addEventListener('mouseleave', function() {
    this.style.transform = 'translateY(0) scale(1)';
    this.style.boxShadow = '0 8px 20px rgba(227, 91, 36, 0.3)';
  });

  // ========================================
  // PREMIUM: Copy to Clipboard for Product URLs
  // ========================================
  const shareButtons = document.querySelectorAll('[data-share="copy-url"]');

  shareButtons.forEach(btn => {
    btn.addEventListener('click', async function(e) {
      e.preventDefault();
      try {
        await navigator.clipboard.writeText(window.location.href);
        showNotification('Lien copié !');
      } catch (err) {
        console.error('Failed to copy:', err);
      }
    });
  });

  // ========================================
  // PREMIUM: Enhanced Product Filters with Animated Transitions
  // ========================================
  const filterButtons = document.querySelectorAll('.product-filters-js .filter-btn');
  const advancedFilters = document.querySelectorAll('.filter-dropdown .filter-option');
  const filterReset = document.querySelector('.filter-reset');
  const productItems = document.querySelectorAll('.products-carousel-track .product');

  let activeFilters = {
    category: 'all',
    price: 'all',
    wood: 'all',
    size: 'all',
    searchText: ''
  };

  // Category filter (main buttons)
  filterButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      // Update active state with smooth transition
      filterButtons.forEach(b => {
        b.classList.remove('active');
        b.style.transform = 'scale(1)';
      });
      this.classList.add('active');
      this.style.transform = 'scale(1.05)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 200);

      activeFilters.category = this.getAttribute('data-filter');
      applyFilters();
    });
  });

  // Advanced filters (dropdowns)
  advancedFilters.forEach(option => {
    option.addEventListener('click', function() {
      const filterType = this.closest('[data-filter-type]').getAttribute('data-filter-type');
      const filterValue = this.getAttribute('data-' + filterType);

      // Update active state in dropdown
      const siblings = this.closest('.filter-dropdown-menu').querySelectorAll('.filter-option');
      siblings.forEach(s => s.classList.remove('active'));
      this.classList.add('active');

      // Update label
      const label = this.closest('.filter-dropdown').querySelector('.filter-label');
      if (filterValue !== 'all') {
        label.textContent = this.textContent;
      } else {
        // Reset to default label
        const defaultLabels = {
          'price': 'Prix',
          'wood': 'Essence',
          'size': 'Taille'
        };
        label.textContent = defaultLabels[filterType];
      }

      activeFilters[filterType] = filterValue;
      applyFilters();

      // Show reset button if any advanced filter is active
      const hasActiveFilters = Object.entries(activeFilters).some(([key, value]) => {
        return key !== 'category' && value !== 'all' && value !== '';
      });
      if (filterReset) {
        filterReset.style.display = hasActiveFilters ? 'flex' : 'none';
      }

      // Close dropdown with animation
      const dropdown = this.closest('.filter-dropdown-menu');
      dropdown.style.animation = 'slideUp 0.3s ease';
      setTimeout(() => {
        dropdown.style.animation = '';
      }, 300);
    });
  });

  // Reset filters
  if (filterReset) {
    filterReset.addEventListener('click', function() {
      activeFilters = {
        category: activeFilters.category, // Keep category filter
        price: 'all',
        wood: 'all',
        size: 'all',
        searchText: ''
      };

      // Reset search input
      const searchInput = document.getElementById('product-search-input');
      const searchClear = document.querySelector('.search-clear');
      if (searchInput) {
        searchInput.value = '';
      }
      if (searchClear) {
        searchClear.style.display = 'none';
      }

      // Reset all advanced filters UI
      advancedFilters.forEach(option => {
        option.classList.remove('active');
        const filterType = option.getAttribute('data-price') || option.getAttribute('data-wood') || option.getAttribute('data-size');
        if (filterType === 'all') {
          option.classList.add('active');
        }
      });

      // Reset labels
      document.querySelectorAll('.filter-dropdown .filter-label').forEach(label => {
        const dropdown = label.closest('.filter-dropdown');
        const type = dropdown.getAttribute('data-filter-type');
        const defaultLabels = {
          'price': 'Prix',
          'wood': 'Essence',
          'size': 'Dimensions'
        };
        label.textContent = defaultLabels[type];
      });

      this.style.display = 'none';
      applyFilters();
    });
  }

  // Apply filters with smooth animations
  function applyFilters() {
    let visibleCount = 0;

    productItems.forEach((product, index) => {
      const matchesCategory = activeFilters.category === 'all' ||
        product.classList.contains('product_cat-' + activeFilters.category);

      // Get product price for filtering
      const priceElement = product.querySelector('.price .woocommerce-Price-amount');
      let productPrice = 0;
      if (priceElement) {
        const priceText = priceElement.textContent.replace(/[^0-9.,]/g, '').replace(',', '.');
        productPrice = parseFloat(priceText) || 0;
      }

      const matchesPrice = activeFilters.price === 'all' || checkPriceRange(productPrice, activeFilters.price);

      // For wood and size, check product attributes (would need data attributes in PHP)
      const matchesWood = activeFilters.wood === 'all' ||
        (product.dataset.wood && product.dataset.wood.includes(activeFilters.wood));
      const matchesSize = activeFilters.size === 'all' ||
        (product.dataset.size && checkSizeRange(parseFloat(product.dataset.size) || 0, activeFilters.size));

      // Text search - check product title
      const productTitle = product.querySelector('.woocommerce-loop-product__title');
      const matchesSearch = !activeFilters.searchText ||
        (productTitle && productTitle.textContent.toLowerCase().includes(activeFilters.searchText.toLowerCase()));

      const shouldShow = matchesCategory && matchesPrice && matchesWood && matchesSize && matchesSearch;

      if (shouldShow) {
        // Staggered fade-in animation
        setTimeout(() => {
          product.style.display = '';
          product.style.opacity = '0';
          product.style.transform = 'translateY(20px) scale(0.95)';

          requestAnimationFrame(() => {
            product.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            product.style.opacity = '1';
            product.style.transform = 'translateY(0) scale(1)';
          });
        }, visibleCount * 50);
        visibleCount++;
      } else {
        // Fade-out animation
        product.style.transition = 'all 0.3s ease';
        product.style.opacity = '0';
        product.style.transform = 'translateY(-20px) scale(0.9)';
        setTimeout(() => {
          product.style.display = 'none';
        }, 300);
      }
    });

    // Show notification with count
    showNotification(`${visibleCount} produit${visibleCount > 1 ? 's' : ''} trouvé${visibleCount > 1 ? 's' : ''}`);
  }

  function checkPriceRange(price, range) {
    if (range === '0-100') return price < 100;
    if (range === '100-200') return price >= 100 && price < 200;
    if (range === '200-300') return price >= 200 && price < 300;
    if (range === '300+') return price >= 300;
    return true;
  }

  function checkSizeRange(size, range) {
    if (range === '0-100') return size < 100;
    if (range === '100-150') return size >= 100 && size < 150;
    if (range === '150-200') return size >= 150 && size < 200;
    if (range === '200+') return size >= 200;
    return true;
  }

  // Dropdown toggle animations
  document.querySelectorAll('.filter-dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
      const dropdown = this.closest('.filter-dropdown');
      const menu = dropdown.querySelector('.filter-dropdown-menu');
      const isOpen = dropdown.classList.contains('open');

      // Close all other dropdowns
      document.querySelectorAll('.filter-dropdown.open').forEach(d => {
        if (d !== dropdown) {
          d.classList.remove('open');
        }
      });

      dropdown.classList.toggle('open');

      if (!isOpen) {
        menu.style.animation = 'slideDown 0.3s ease';
      }
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.filter-dropdown')) {
      document.querySelectorAll('.filter-dropdown.open').forEach(d => {
        d.classList.remove('open');
      });
    }
  });

  // Product search functionality
  const searchInput = document.getElementById('product-search-input');
  const searchClear = document.querySelector('.search-clear');

  if (searchInput) {
    // Real-time search with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function() {
      const value = this.value.trim();

      // Show/hide clear button
      if (searchClear) {
        searchClear.style.display = value ? 'flex' : 'none';
      }

      // Show reset button if search has text
      if (filterReset) {
        const hasFilters = value || Object.entries(activeFilters).some(([key, val]) => {
          return key !== 'category' && key !== 'searchText' && val !== 'all' && val !== '';
        });
        filterReset.style.display = hasFilters ? 'flex' : 'none';
      }

      // Debounce search to avoid too many filter calls
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        activeFilters.searchText = value;
        applyFilters();
      }, 300);
    });

    // Clear search
    if (searchClear) {
      searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.focus();
        this.style.display = 'none';
        activeFilters.searchText = '';
        applyFilters();

        // Hide reset button if no other filters active
        if (filterReset) {
          const hasOtherFilters = Object.entries(activeFilters).some(([key, val]) => {
            return key !== 'category' && key !== 'searchText' && val !== 'all' && val !== '';
          });
          filterReset.style.display = hasOtherFilters ? 'flex' : 'none';
        }
      });
    }
  }

  // ========================================
  // PREMIUM: Infinite Scroll for Blog Archive
  // ========================================
  const blogArchive = document.querySelector('.blog-archive-grid');
  const loadMoreTrigger = document.querySelector('.load-more-trigger');

  if (blogArchive && loadMoreTrigger) {
    let currentPage = 1;
    let isLoading = false;
    const maxPages = parseInt(loadMoreTrigger.dataset.maxPages) || 1;

    const loadMoreObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !isLoading && currentPage < maxPages) {
          loadMorePosts();
        }
      });
    }, {
      rootMargin: '200px'
    });

    loadMoreObserver.observe(loadMoreTrigger);

    async function loadMorePosts() {
      isLoading = true;
      currentPage++;

      // Show loading state
      loadMoreTrigger.innerHTML = `
        <div class="loading-spinner">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" opacity="0.25"/>
            <path d="M12 2a10 10 0 0 1 10 10" opacity="0.75">
              <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
            </path>
          </svg>
          <span>Chargement...</span>
        </div>
      `;

      try {
        // Fetch next page
        const response = await fetch(`?paged=${currentPage}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (response.ok) {
          const html = await response.text();
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newPosts = doc.querySelectorAll('.blog-card');

          // Add new posts with staggered animation
          newPosts.forEach((post, index) => {
            post.style.opacity = '0';
            post.style.transform = 'translateY(30px)';
            blogArchive.appendChild(post);

            setTimeout(() => {
              post.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
              post.style.opacity = '1';
              post.style.transform = 'translateY(0)';
            }, index * 100);
          });

          // Reset loading state
          if (currentPage < maxPages) {
            loadMoreTrigger.innerHTML = '<span>Scroll pour plus d\'articles</span>';
          } else {
            loadMoreTrigger.innerHTML = '<span>Vous avez tout vu ! ✨</span>';
            loadMoreTrigger.style.opacity = '0.6';
            loadMoreObserver.disconnect();
          }
        }
      } catch (error) {
        console.error('Error loading posts:', error);
        loadMoreTrigger.innerHTML = '<span>Erreur de chargement</span>';
      }

      isLoading = false;
    }
  }

  // ========================================
  // Console Message
  // ========================================
  console.log('%cSAPI CINÉTIQUE PREMIUM v2.0', 'font-size: 24px; font-weight: bold; color: #937D68; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);');
  console.log('%cDesign architectural - Interactions avancées Ultra', 'font-size: 12px; color: #8A8A8A;');
  console.log('%cCore: Scroll Progress | Image Zoom | Form Enhancements | Cart Animations | Back to Top', 'font-size: 10px; color: #585858;');
  console.log('%cPremium: Advanced Parallax | Canvas Particles | Animated Filters | Infinite Scroll', 'font-size: 10px; color: #937D68; font-weight: bold;');
  console.log('%cShortcuts: C (collections) | ESC (close zoom) | Konami Code (surprise)', 'font-size: 10px; color: #585858;');
  console.log('%cUsage: Add data-parallax="0.5" for parallax, data-particles="wood" for canvas particles', 'font-size: 9px; color: #B89968;');

});
