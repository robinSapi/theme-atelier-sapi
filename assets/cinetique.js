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

        // Parallax on hero images
        const heroImages = document.querySelectorAll('.bento-bg');
        heroImages.forEach(img => {
          img.style.transform = `translateY(${scrolled * 0.1}px) scale(1.05)`;
        });

        // Header background opacity on scroll
        const header = document.querySelector('.site-header');
        if (header) {
          if (scrolled > 100) {
            header.style.background = 'rgba(254, 253, 251, 0.98)';
            header.style.boxShadow = '0 2px 20px rgba(50, 50, 50, 0.1)';
          } else {
            header.style.background = 'rgba(254, 253, 251, 0.95)';
            header.style.boxShadow = 'none';
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
  // Console Message
  // ========================================
  console.log('%cSAPI CINETIQUE', 'font-size: 24px; font-weight: bold; color: #937D68;');
  console.log('%cDesign architectural - Interactions avancees', 'font-size: 12px; color: #8A8A8A;');
  console.log('%cShortcuts: C (collections) | Konami Code (surprise)', 'font-size: 10px; color: #585858;');

});
