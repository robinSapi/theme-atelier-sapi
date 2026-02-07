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
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = (y - centerY) / 20;
      const rotateY = (centerX - x) / 20;

      const productImage = card.querySelector('.product-image');
      if (productImage) {
        productImage.style.transform = `scale(1.1) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      }
    });

    card.addEventListener('mouseleave', () => {
      const productImage = card.querySelector('.product-image');
      if (productImage) {
        productImage.style.transform = 'scale(1) rotateX(0) rotateY(0)';
      }
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
  // Parallax Effect on Scroll
  // ========================================
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;

        // Parallax on hero image only (not atelier)
        const heroImage = document.querySelector('.bento-hero .bento-bg');
        if (heroImage) {
          heroImage.style.transform = `translateY(${scrolled * 0.1}px) scale(1.05)`;
        }

        // Header background opacity on scroll
        const header = document.querySelector('.site-header');
        if (header) {
          if (scrolled > 100) {
            header.style.background = 'rgba(254, 253, 251, 0.98)';
            header.style.boxShadow = '0 2px 20px rgba(50, 50, 50, 0.12)';
          } else {
            header.style.background = 'rgba(254, 253, 251, 0.95)';
            header.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
          }
        }

        ticking = false;
      });

      ticking = true;
    }
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
  // Console Message
  // ========================================
  console.log('%cSAPI CINÉTIQUE PREMIUM', 'font-size: 24px; font-weight: bold; color: #937D68; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);');
  console.log('%cDesign architectural - Interactions avancées 2.0', 'font-size: 12px; color: #8A8A8A;');
  console.log('%cFeatures: Scroll Progress | Image Zoom | Form Enhancements | Cart Animations | Back to Top', 'font-size: 10px; color: #585858;');
  console.log('%cShortcuts: C (collections) | ESC (close zoom) | Konami Code (surprise)', 'font-size: 10px; color: #585858;');

});
