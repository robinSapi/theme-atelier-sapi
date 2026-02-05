// ========================================
// SAPI CINÉTIQUE - Interactions JavaScript
// ========================================

document.addEventListener('DOMContentLoaded', () => {

  // ========================================
  // Custom Cursor
  // ========================================
  const cursor = document.querySelector('.cursor-custom');
  const cursorDot = document.querySelector('.cursor-dot');
  const cursorOutline = document.querySelector('.cursor-outline');

  let mouseX = 0;
  let mouseY = 0;
  let cursorX = 0;
  let cursorY = 0;

  document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;

    // Dot suit directement la souris
    cursorDot.style.left = mouseX + 'px';
    cursorDot.style.top = mouseY + 'px';
  });

  // Outline suit avec un léger delay (smooth)
  function animateCursor() {
    cursorX += (mouseX - cursorX) * 0.15;
    cursorY += (mouseY - cursorY) * 0.15;

    cursorOutline.style.left = cursorX + 'px';
    cursorOutline.style.top = cursorY + 'px';

    requestAnimationFrame(animateCursor);
  }
  animateCursor();

  // Hover effects sur les éléments interactifs
  const interactiveElements = document.querySelectorAll('a, button, .bento-card, .collection-card');

  interactiveElements.forEach(el => {
    el.addEventListener('mouseenter', () => {
      document.body.classList.add('cursor-hover');
    });

    el.addEventListener('mouseleave', () => {
      document.body.classList.remove('cursor-hover');
    });
  });

  // ========================================
  // Menu Toggle
  // ========================================
  const menuToggle = document.querySelector('.menu-toggle');
  const menuOverlay = document.querySelector('.menu-overlay');
  const menuItems = document.querySelectorAll('.menu-item');

  menuToggle.addEventListener('click', () => {
    menuOverlay.classList.toggle('active');

    // Animation de l'icône burger
    const menuIcon = menuToggle.querySelector('.menu-icon');
    const spans = menuIcon.querySelectorAll('span');

    if (menuOverlay.classList.contains('active')) {
      spans[0].style.transform = 'rotate(45deg) translateY(7px)';
      spans[1].style.transform = 'rotate(-45deg) translateY(-7px)';
      menuToggle.querySelector('.menu-label').textContent = 'Fermer';
    } else {
      spans[0].style.transform = '';
      spans[1].style.transform = '';
      menuToggle.querySelector('.menu-label').textContent = 'Menu';
    }
  });

  // Fermer le menu en cliquant sur un lien
  menuItems.forEach(item => {
    item.addEventListener('click', () => {
      menuOverlay.classList.remove('active');
      const menuIcon = menuToggle.querySelector('.menu-icon');
      const spans = menuIcon.querySelectorAll('span');
      spans[0].style.transform = '';
      spans[1].style.transform = '';
      menuToggle.querySelector('.menu-label').textContent = 'Menu';
    });
  });

  // Fermer avec Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menuOverlay.classList.contains('active')) {
      menuOverlay.classList.remove('active');
      const menuIcon = menuToggle.querySelector('.menu-icon');
      const spans = menuIcon.querySelectorAll('span');
      spans[0].style.transform = '';
      spans[1].style.transform = '';
      menuToggle.querySelector('.menu-label').textContent = 'Menu';
    }
  });

  // ========================================
  // Bento Cards Animation on Scroll
  // ========================================
  const bentoCards = document.querySelectorAll('.bento-card');

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
      productImage.style.transform = `scale(1.1) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    });

    card.addEventListener('mouseleave', () => {
      const productImage = card.querySelector('.product-image');
      productImage.style.transform = 'scale(1) rotateX(0) rotateY(0)';
    });
  });

  // ========================================
  // Collection Cards Animation
  // ========================================
  const collectionCards = document.querySelectorAll('.collection-card');

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

  // ========================================
  // Add to Cart (Product Cards)
  // ========================================
  const productOverlays = document.querySelectorAll('.product-overlay');
  const cartNum = document.querySelector('.cart-num');
  let cartCount = 0;

  productCards.forEach(card => {
    card.addEventListener('click', (e) => {
      // Empêcher le clic si on est en train de drag
      if (isDragging) return;

      const productName = card.querySelector('.product-name')?.textContent || 'Produit';

      // Animation du panier
      cartCount++;
      cartNum.textContent = cartCount;

      // Feedback visuel
      const priceTag = card.querySelector('.product-price-tag');
      if (priceTag) {
        const originalText = priceTag.innerHTML;
        priceTag.innerHTML = '<span>Ajouté !</span>';
        priceTag.style.background = 'var(--color-green)';

        setTimeout(() => {
          priceTag.innerHTML = originalText;
          priceTag.style.background = 'var(--color-wood)';
        }, 1500);
      }

      // Notification
      showNotification(`${productName} ajouté au panier`);
    });
  });

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

      buttonText.textContent = 'Inscrit !';
      button.style.background = 'var(--color-green)';
      input.disabled = true;

      setTimeout(() => {
        buttonText.textContent = "S'inscrire";
        button.style.background = 'var(--color-wood)';
        input.disabled = false;
        input.value = '';
      }, 3000);

      showNotification('Inscription réussie !');
    });
  }

  // ========================================
  // CTA Button Click
  // ========================================
  const ctaButton = document.querySelector('.cta-button');

  if (ctaButton) {
    ctaButton.addEventListener('click', () => {
      showNotification('Redirection vers la boutique...');
      // Ici vous pourriez faire une vraie navigation
      // window.location.href = '/boutique';
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
      right: 3rem;
      background: var(--color-white);
      border: 2px solid var(--color-wood);
      color: var(--color-dark);
      padding: 1.25rem 2rem;
      border-radius: 50px;
      font-family: var(--font-body);
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

  // Add notification animations
  const style = document.createElement('style');
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
      color: var(--color-wood);
      font-size: 1.25rem;
    }
  `;
  document.head.appendChild(style);

  // ========================================
  // Parallax Effect on Scroll
  // ========================================
  let isDragging = false;
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;

        // Parallax sur les images hero
        const heroImages = document.querySelectorAll('.bento-bg');
        heroImages.forEach(img => {
          img.style.transform = `translateY(${scrolled * 0.1}px) scale(1.05)`;
        });

        // Header background opacity
        const header = document.querySelector('.header-architectural');
        if (scrolled > 100) {
          header.style.background = 'rgba(254, 253, 251, 0.98)';
          header.style.boxShadow = '0 2px 20px rgba(50, 50, 50, 0.1)';
        } else {
          header.style.background = 'rgba(254, 253, 251, 0.95)';
          header.style.boxShadow = 'none';
        }

        ticking = false;
      });

      ticking = true;
    }
  });

  // ========================================
  // Smooth Scroll
  // ========================================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));

      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ========================================
  // Performance: Reduce animations on low-end devices
  // ========================================
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (prefersReducedMotion.matches) {
    document.body.style.setProperty('--ease-expo', 'cubic-bezier(0.4, 0, 0.2, 1)');
    document.body.style.setProperty('--ease-smooth', 'ease');
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
    // M key opens menu
    if (e.key === 'm' || e.key === 'M') {
      if (!menuOverlay.classList.contains('active')) {
        menuToggle.click();
      }
    }

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
      showNotification('🎉 Konami Code activé ! Mode arc-en-ciel !');

      setTimeout(() => {
        document.body.style.animation = '';
      }, 5000);
    }
  });

  // Add rainbow animation
  const rainbowStyle = document.createElement('style');
  rainbowStyle.textContent = `
    @keyframes rainbow {
      0%, 100% { filter: hue-rotate(0deg); }
      50% { filter: hue-rotate(360deg); }
    }
  `;
  document.head.appendChild(rainbowStyle);

  // ========================================
  // Console Message
  // ========================================
  console.log('%c🎨 SAPI CINÉTIQUE', 'font-size: 24px; font-weight: bold; color: #D4AF37;');
  console.log('%cDesign architectural - Interactions avancées', 'font-size: 12px; color: #8E8E93;');
  console.log('%cShortcuts: M (menu) | C (collections) | Escape (close menu)', 'font-size: 10px; color: #E8E3D5;');
});
