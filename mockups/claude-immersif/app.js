// ========================================
// SAPI IMMERSIF - Interactions JavaScript
// ========================================

document.addEventListener('DOMContentLoaded', () => {

  // ========================================
  // Menu Burger Toggle
  // ========================================
  const menuBurger = document.querySelector('.menu-burger');
  const menuPanel = document.querySelector('.menu-panel');
  const menuClose = document.querySelector('.menu-close');
  const menuLinks = document.querySelectorAll('.menu-link');

  menuBurger.addEventListener('click', () => {
    menuPanel.classList.add('active');
    document.body.style.overflow = 'hidden';
  });

  menuClose.addEventListener('click', () => {
    menuPanel.classList.remove('active');
    document.body.style.overflow = '';
  });

  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      menuPanel.classList.remove('active');
      document.body.style.overflow = '';
    });
  });

  // ========================================
  // Navigation Dots - Active Section Tracking
  // ========================================
  const sections = document.querySelectorAll('.section-fullscreen');
  const navDots = document.querySelectorAll('.nav-dots .dot');

  const observerOptions = {
    threshold: 0.5,
    rootMargin: '0px'
  };

  const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const sectionId = entry.target.id;

        // Update active dot
        navDots.forEach(dot => {
          const dotSection = dot.getAttribute('data-section');
          if (dotSection === sectionId) {
            dot.classList.add('active');
          } else {
            dot.classList.remove('active');
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach(section => sectionObserver.observe(section));

  // Smooth scroll for nav dots
  navDots.forEach(dot => {
    dot.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = dot.getAttribute('href');
      const targetSection = document.querySelector(targetId);

      if (targetSection) {
        targetSection.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ========================================
  // Carousel de Produits avec Drag
  // ========================================
  const carouselTrack = document.querySelector('.carousel-track');
  const carouselSlides = document.querySelectorAll('.carousel-slide');
  const carouselDots = document.querySelectorAll('.carousel-dot');
  const prevBtn = document.querySelector('.carousel-btn.prev');
  const nextBtn = document.querySelector('.carousel-btn.next');

  let currentIndex = 0;
  let isDragging = false;
  let startPos = 0;
  let currentTranslate = 0;
  let prevTranslate = 0;

  // Update carousel position
  function updateCarousel() {
    const slideWidth = carouselSlides[0].offsetWidth;
    const gap = 32; // 2rem gap
    const offset = -(currentIndex * (slideWidth + gap));

    carouselTrack.style.transform = `translateX(${offset}px)`;

    // Update dots
    carouselDots.forEach((dot, index) => {
      if (index === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  // Previous slide
  prevBtn.addEventListener('click', () => {
    currentIndex = Math.max(0, currentIndex - 1);
    updateCarousel();
  });

  // Next slide
  nextBtn.addEventListener('click', () => {
    currentIndex = Math.min(carouselSlides.length - 1, currentIndex + 1);
    updateCarousel();
  });

  // Dots navigation
  carouselDots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      currentIndex = index;
      updateCarousel();
    });
  });

  // Touch/Drag events
  carouselTrack.addEventListener('mousedown', startDrag);
  carouselTrack.addEventListener('touchstart', startDrag);
  carouselTrack.addEventListener('mouseup', endDrag);
  carouselTrack.addEventListener('touchend', endDrag);
  carouselTrack.addEventListener('mousemove', drag);
  carouselTrack.addEventListener('touchmove', drag);
  carouselTrack.addEventListener('mouseleave', endDrag);

  function startDrag(e) {
    isDragging = true;
    startPos = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
    carouselTrack.style.cursor = 'grabbing';
  }

  function drag(e) {
    if (!isDragging) return;
    e.preventDefault();

    const currentPosition = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
    const diff = currentPosition - startPos;

    // Si le drag dépasse 100px, change de slide
    if (Math.abs(diff) > 100) {
      if (diff > 0 && currentIndex > 0) {
        currentIndex--;
        updateCarousel();
        isDragging = false;
      } else if (diff < 0 && currentIndex < carouselSlides.length - 1) {
        currentIndex++;
        updateCarousel();
        isDragging = false;
      }
    }
  }

  function endDrag() {
    isDragging = false;
    carouselTrack.style.cursor = 'grab';
  }

  // Auto-play carousel (optional)
  let autoplayInterval = setInterval(() => {
    if (currentIndex < carouselSlides.length - 1) {
      currentIndex++;
    } else {
      currentIndex = 0;
    }
    updateCarousel();
  }, 5000);

  // Pause autoplay on hover
  carouselTrack.addEventListener('mouseenter', () => {
    clearInterval(autoplayInterval);
  });

  carouselTrack.addEventListener('mouseleave', () => {
    autoplayInterval = setInterval(() => {
      if (currentIndex < carouselSlides.length - 1) {
        currentIndex++;
      } else {
        currentIndex = 0;
      }
      updateCarousel();
    }, 5000);
  });

  // ========================================
  // Scroll-triggered Reveal Animations
  // ========================================
  const revealElements = document.querySelectorAll('.story-text-block, .atelier-content, .products-header-center');

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, {
    threshold: 0.2,
    rootMargin: '0px 0px -100px 0px'
  });

  revealElements.forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(50px)';
    el.style.transition = 'all 1s cubic-bezier(0.76, 0, 0.24, 1)';
    revealObserver.observe(el);
  });

  // ========================================
  // Parallax Effect on Scroll
  // ========================================
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;

        // Parallax sur le hero background
        const heroBackground = document.querySelector('.hero-background');
        if (heroBackground) {
          heroBackground.style.transform = `translateY(${scrolled * 0.4}px)`;
        }

        // Parallax sur les images story
        const mediaLayer = document.querySelector('.media-layer');
        if (mediaLayer) {
          mediaLayer.style.transform = `translateY(${scrolled * 0.08}px) scale(1.1)`;
        }

        ticking = false;
      });

      ticking = true;
    }
  });

  // ========================================
  // Add to Cart Functionality
  // ========================================
  const addCartBtns = document.querySelectorAll('.btn-add-cart');
  let cartCount = 0;

  addCartBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      cartCount++;

      // Visual feedback
      btn.textContent = 'Ajouté au panier !';
      btn.style.background = 'var(--color-charcoal)';

      setTimeout(() => {
        btn.textContent = 'Ajouter au panier';
        btn.style.background = 'var(--color-copper)';
      }, 2000);

      // Notification (optionnelle)
      showNotification('Produit ajouté au panier');
    });
  });

  // ========================================
  // Newsletter Form
  // ========================================
  const newsletterForm = document.querySelector('.form-immersive');

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = newsletterForm.querySelector('input[type="email"]');
      const button = newsletterForm.querySelector('button[type="submit"]');

      button.textContent = 'Inscription réussie !';
      button.style.background = 'var(--color-charcoal)';
      input.disabled = true;

      setTimeout(() => {
        button.textContent = "S'inscrire";
        button.style.background = 'var(--color-copper)';
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
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
      position: fixed;
      top: 2rem;
      right: 2rem;
      background: var(--color-copper);
      color: white;
      padding: 1rem 2rem;
      border-radius: 50px;
      font-weight: 600;
      z-index: 99999;
      animation: slideInRight 0.5s ease-out;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = 'slideOutRight 0.5s ease-out forwards';
      setTimeout(() => {
        notification.remove();
      }, 500);
    }, 3000);
  }

  // Add notification animations to CSS dynamically
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);

  // ========================================
  // Smooth Scroll for All Links
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
  // Keyboard Navigation
  // ========================================
  document.addEventListener('keydown', (e) => {
    // Escape key closes menu
    if (e.key === 'Escape' && menuPanel.classList.contains('active')) {
      menuPanel.classList.remove('active');
      document.body.style.overflow = '';
    }

    // Arrow keys for carousel navigation
    if (e.key === 'ArrowLeft' && currentIndex > 0) {
      currentIndex--;
      updateCarousel();
    }
    if (e.key === 'ArrowRight' && currentIndex < carouselSlides.length - 1) {
      currentIndex++;
      updateCarousel();
    }
  });

  // ========================================
  // Console Message
  // ========================================
  console.log('🎬 SAPI IMMERSIF loaded - Experience ready!');
});
