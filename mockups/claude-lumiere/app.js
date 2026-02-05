// ========================================
// SAPI LUMIÈRE - Ultimate JavaScript
// Par Claude - Février 2026
// ========================================

'use strict';

// ========================================
// Initialize on DOMContentLoaded
// ========================================

document.addEventListener('DOMContentLoaded', () => {
  // Remove loading class after a delay
  setTimeout(() => {
    document.body.classList.remove('loading');
  }, 1500);

  // Initialize all modules
  initLightCanvas();
  initScrollProgress();
  initScrollAnimations();
  initNavigation();
  initMiniCart();
  initSearch();
  initProductCards();
  initHotspots();
  initTimeline();
  initNewsletter();
  initLightbox();
  initParallax();
  initSmoothScroll();
  initKeyboardShortcuts();

  console.log('%c✨ SAPI LUMIÈRE', 'font-size: 24px; font-weight: bold; background: linear-gradient(135deg, #FFD700, #CD7F32); -webkit-background-clip: text; -webkit-text-fill-color: transparent;');
  console.log('%cUltimate Design Experience', 'font-size: 12px; color: #999;');
});

// ========================================
// Light Canvas - Particle System
// ========================================

function initLightCanvas() {
  const canvas = document.getElementById('light-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let particles = [];
  let mouse = { x: 0, y: 0 };
  let canvasWidth, canvasHeight;

  // Resize canvas
  function resizeCanvas() {
    canvasWidth = window.innerWidth;
    canvasHeight = window.innerHeight;
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;
  }

  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  // Particle class
  class Particle {
    constructor() {
      this.x = Math.random() * canvasWidth;
      this.y = Math.random() * canvasHeight;
      this.size = Math.random() * 2 + 0.5;
      this.speedX = Math.random() * 0.5 - 0.25;
      this.speedY = Math.random() * 0.5 - 0.25;
      this.opacity = Math.random() * 0.5 + 0.2;
      this.glowRadius = Math.random() * 20 + 10;
    }

    update() {
      this.x += this.speedX;
      this.y += this.speedY;

      // Mouse interaction
      const dx = mouse.x - this.x;
      const dy = mouse.y - this.y;
      const distance = Math.sqrt(dx * dx + dy * dy);

      if (distance < 150) {
        const force = (150 - distance) / 150;
        this.x -= dx * force * 0.02;
        this.y -= dy * force * 0.02;
      }

      // Wrap around screen
      if (this.x < 0) this.x = canvasWidth;
      if (this.x > canvasWidth) this.x = 0;
      if (this.y < 0) this.y = canvasHeight;
      if (this.y > canvasHeight) this.y = 0;
    }

    draw() {
      // Glow effect
      const gradient = ctx.createRadialGradient(
        this.x, this.y, 0,
        this.x, this.y, this.glowRadius
      );
      gradient.addColorStop(0, `rgba(255, 215, 0, ${this.opacity})`);
      gradient.addColorStop(1, 'rgba(255, 215, 0, 0)');

      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.glowRadius, 0, Math.PI * 2);
      ctx.fill();

      // Core particle
      ctx.fillStyle = `rgba(255, 215, 0, ${this.opacity + 0.3})`;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  // Create particles
  for (let i = 0; i < 50; i++) {
    particles.push(new Particle());
  }

  // Mouse tracking
  document.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  });

  // Animation loop
  function animate() {
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);

    particles.forEach(particle => {
      particle.update();
      particle.draw();
    });

    // Connect nearby particles
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < 120) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(255, 215, 0, ${0.1 * (1 - distance / 120)})`;
          ctx.lineWidth = 0.5;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(animate);
  }

  animate();
}

// ========================================
// Scroll Progress Bar
// ========================================

function initScrollProgress() {
  const progressBar = document.querySelector('.scroll-progress');
  if (!progressBar) return;

  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrolled = window.pageYOffset;
        const progress = (scrolled / scrollHeight);

        progressBar.style.transform = `scaleX(${progress})`;

        ticking = false;
      });

      ticking = true;
    }
  });
}

// ========================================
// Scroll Animations with IntersectionObserver
// ========================================

function initScrollAnimations() {
  const scrollElements = document.querySelectorAll('[data-scroll]');

  const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -100px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
      }
    });
  }, observerOptions);

  scrollElements.forEach(el => observer.observe(el));
}

// ========================================
// Navigation - Scroll Behavior
// ========================================

function initNavigation() {
  const nav = document.querySelector('.nav-lumiere');
  let lastScroll = 0;

  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll > 100) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }

    // Hide on scroll down, show on scroll up
    if (currentScroll > lastScroll && currentScroll > 500) {
      nav.style.transform = 'translateY(-100%)';
    } else {
      nav.style.transform = 'translateY(0)';
    }

    lastScroll = currentScroll;
  });

  // Nav links smooth scroll
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = link.getAttribute('href');
      const targetSection = document.querySelector(targetId);

      if (targetSection) {
        targetSection.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
}

// ========================================
// Mini Cart System
// ========================================

function initMiniCart() {
  const cartToggle = document.querySelector('.cart-toggle');
  const miniCart = document.querySelector('.mini-cart');
  const cartClose = document.querySelector('.mini-cart-close');
  const cartBadge = document.querySelector('.cart-badge');
  const cartItems = document.querySelector('.mini-cart-items');
  const cartEmpty = document.querySelector('.mini-cart-empty');
  const totalAmount = document.querySelector('.total-amount');

  let cart = [];

  // Toggle cart
  cartToggle?.addEventListener('click', () => {
    miniCart.classList.toggle('active');
    document.body.style.overflow = miniCart.classList.contains('active') ? 'hidden' : '';
  });

  cartClose?.addEventListener('click', () => {
    miniCart.classList.remove('active');
    document.body.style.overflow = '';
  });

  // Add to cart function
  window.addToCart = function(productData) {
    const existingItem = cart.find(item => item.id === productData.id);

    if (existingItem) {
      existingItem.quantity++;
    } else {
      cart.push({
        ...productData,
        quantity: 1
      });
    }

    updateCart();
    showNotification(`${productData.name} ajouté au panier!`);

    // Animate cart badge
    if (cartBadge) {
      cartBadge.style.animation = 'none';
      setTimeout(() => {
        cartBadge.style.animation = 'badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
      }, 10);
    }
  };

  // Update cart display
  function updateCart() {
    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    if (cartBadge) {
      cartBadge.textContent = itemCount;
      cartBadge.style.display = itemCount > 0 ? 'flex' : 'none';
    }

    if (totalAmount) {
      totalAmount.textContent = `${total}€`;
    }

    if (cart.length === 0) {
      cartEmpty.style.display = 'flex';
      cartItems.style.display = 'none';
    } else {
      cartEmpty.style.display = 'none';
      cartItems.style.display = 'block';
      renderCartItems();
    }
  }

  // Render cart items
  function renderCartItems() {
    cartItems.innerHTML = cart.map(item => `
      <div class="mini-cart-item">
        <div class="cart-item-image" style="background-image: url('${item.image}');"></div>
        <div class="cart-item-details">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-price">Qté: ${item.quantity} × ${item.price}€</div>
        </div>
        <button class="cart-item-remove" data-id="${item.id}" aria-label="Retirer">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 5L5 15M5 5l10 10"/>
          </svg>
        </button>
      </div>
    `).join('');

    // Remove item listeners
    document.querySelectorAll('.cart-item-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        const itemId = btn.getAttribute('data-id');
        cart = cart.filter(item => item.id !== itemId);
        updateCart();
        showNotification('Produit retiré du panier');
      });
    });
  }

  updateCart();
}

// ========================================
// Search Overlay
// ========================================

function initSearch() {
  const searchToggle = document.querySelector('.search-toggle');
  const searchOverlay = document.querySelector('.search-overlay');
  const searchClose = document.querySelector('.search-close');
  const searchInput = document.querySelector('.search-input');

  searchToggle?.addEventListener('click', () => {
    searchOverlay.classList.add('active');
    setTimeout(() => searchInput?.focus(), 300);
  });

  searchClose?.addEventListener('click', () => {
    searchOverlay.classList.remove('active');
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      searchOverlay.classList.remove('active');
      document.querySelector('.mini-cart').classList.remove('active');
      document.querySelector('.lightbox').classList.remove('active');
      document.body.style.overflow = '';
    }
  });

  // Search functionality (basic)
  searchInput?.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    // Here you would implement actual search logic
    console.log('Searching for:', query);
  });
}

// ========================================
// Product Cards - Add to Cart
// ========================================

function initProductCards() {
  const addCartBtns = document.querySelectorAll('.btn-add-cart');

  addCartBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const productData = {
        id: btn.getAttribute('data-product-id'),
        name: btn.getAttribute('data-product-name'),
        price: parseFloat(btn.getAttribute('data-product-price')),
        image: btn.closest('.product-card').querySelector('.product-image').style.backgroundImage.slice(5, -2)
      };

      window.addToCart(productData);

      // Button feedback
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<span>Ajouté!</span>';
      btn.style.background = 'var(--gradient-gold)';
      btn.style.color = 'var(--color-black)';

      setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.style.background = '';
        btn.style.color = '';
      }, 1500);
    });
  });

  // Product filters
  const filterBtns = document.querySelectorAll('.filter-btn');
  const productCards = document.querySelectorAll('.product-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.getAttribute('data-filter');

      // Update active state
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Filter products
      productCards.forEach(card => {
        const category = card.getAttribute('data-category');

        if (filter === 'all' || category === filter) {
          card.style.display = 'block';
          setTimeout(() => {
            card.classList.add('in-view');
          }, 10);
        } else {
          card.classList.remove('in-view');
          setTimeout(() => {
            card.style.display = 'none';
          }, 300);
        }
      });
    });
  });

  // Quick view functionality
  const quickViewBtns = document.querySelectorAll('.product-quick-view');

  quickViewBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const productId = btn.getAttribute('data-product');
      openLightbox(productId);
    });
  });
}

// ========================================
// Hotspots - Interactive Atelier
// ========================================

function initHotspots() {
  const hotspots = document.querySelectorAll('.hotspot');
  const panels = document.querySelectorAll('.hotspot-panel');

  let activePanel = null;

  hotspots.forEach(hotspot => {
    hotspot.addEventListener('click', () => {
      const panelId = hotspot.getAttribute('data-hotspot');
      const panel = document.querySelector(`[data-panel="${panelId}"]`);

      // Close active panel if clicking same hotspot
      if (activePanel === panel && panel.classList.contains('active')) {
        panel.classList.remove('active');
        activePanel = null;
        return;
      }

      // Close all panels
      panels.forEach(p => p.classList.remove('active'));

      // Open selected panel
      if (panel) {
        panel.classList.add('active');
        activePanel = panel;
      }
    });
  });

  // Close panel when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.hotspot') && !e.target.closest('.hotspot-panel')) {
      panels.forEach(p => p.classList.remove('active'));
      activePanel = null;
    }
  });
}

// ========================================
// Timeline Scroll Animations
// ========================================

function initTimeline() {
  const timelineItems = document.querySelectorAll('.timeline-item');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
      }
    });
  }, {
    threshold: 0.3
  });

  timelineItems.forEach(item => observer.observe(item));
}

// ========================================
// Newsletter Form
// ========================================

function initNewsletter() {
  const form = document.querySelector('.newsletter-form');

  form?.addEventListener('submit', (e) => {
    e.preventDefault();

    const input = form.querySelector('.newsletter-input');
    const button = form.querySelector('.newsletter-submit');
    const buttonHTML = button.innerHTML;

    // Simulate submission
    button.innerHTML = '<span>Inscription...</span>';
    button.disabled = true;

    setTimeout(() => {
      button.innerHTML = '<span>Inscrit! ✓</span>';
      input.value = '';

      showNotification('Merci! Vous êtes inscrit à notre newsletter.');

      setTimeout(() => {
        button.innerHTML = buttonHTML;
        button.disabled = false;
      }, 2000);
    }, 1000);
  });
}

// ========================================
// Lightbox System
// ========================================

function initLightbox() {
  const lightbox = document.querySelector('.lightbox');
  const lightboxClose = document.querySelector('.lightbox-close');

  lightboxClose?.addEventListener('click', closeLightbox);

  // Close on click outside
  lightbox?.addEventListener('click', (e) => {
    if (e.target === lightbox) {
      closeLightbox();
    }
  });

  function closeLightbox() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
  }

  // Make openLightbox global
  window.openLightbox = openLightbox;
}

function openLightbox(productId) {
  const lightbox = document.querySelector('.lightbox');
  const lightboxImage = lightbox.querySelector('.lightbox-image');
  const lightboxTitle = lightbox.querySelector('.lightbox-title');
  const lightboxCategory = lightbox.querySelector('.lightbox-category');
  const lightboxDescription = lightbox.querySelector('.lightbox-description');
  const lightboxPrice = lightbox.querySelector('.lightbox-price .price-value');

  // Product data (in real app, this would come from a database)
  const products = {
    olivia: {
      name: 'Olivia la Gardiena',
      category: 'Suspension monumentale',
      description: 'Sculpture en bois massif avec diffusion lumineuse douce et enveloppante. Disponible en 4 tailles pour s\'adapter à tous les espaces.',
      price: '219€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg'
    },
    timothee: {
      name: 'Timothée l\'Araignée',
      category: 'Suspension sculptural',
      description: 'La seule araignée que vous voudrez voir descendre du plafond. Design aérien avec 8 branches articulées en bois massif.',
      price: '389€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg'
    },
    claudine: {
      name: 'Claudine la Turbine',
      category: 'Lampadaire cinétique',
      description: 'Inspiré des turbines, ce lampadaire crée un mouvement perpétuel de lumière qui transforme l\'espace avec élégance.',
      price: '259€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/07/Claudine.jpg'
    },
    suze: {
      name: 'Suze la Méduse',
      category: 'Applique organique',
      description: 'Formes aquatiques qui ondulent comme une méduse. Lumière apaisante et diffusion douce pour une ambiance relaxante.',
      price: '129€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg'
    },
    alban: {
      name: 'Alban le Virevoltant',
      category: 'Suspension dynamique',
      description: 'Géométrie en mouvement créant des ombres dansantes. Design contemporain en bois de peuplier avec finition huile.',
      price: '189€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/07/Bandeau-Robin.jpg'
    },
    charlie: {
      name: 'Charlie le Pissenlit',
      category: 'Lampe à poser',
      description: 'Légèreté poétique inspirée du pissenlit. Disponible en plusieurs formats et hauteurs pour s\'adapter à tous les usages.',
      price: '149€',
      image: 'https://atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg'
    }
  };

  const product = products[productId];

  if (product) {
    lightboxImage.src = product.image;
    lightboxImage.alt = product.name;
    lightboxTitle.textContent = product.name;
    lightboxCategory.textContent = product.category;
    lightboxDescription.textContent = product.description;
    lightboxPrice.textContent = product.price;

    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

// ========================================
// Parallax Effects
// ========================================

function initParallax() {
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;

        // Hero images parallax
        const heroImages = document.querySelectorAll('.hero-image');
        heroImages.forEach((img, index) => {
          const speed = 0.05 + (index * 0.02);
          img.style.transform = `translateY(${scrolled * speed}px)`;
        });

        // Timeline images parallax
        const timelineImages = document.querySelectorAll('.timeline-image');
        timelineImages.forEach((img, index) => {
          if (isInViewport(img)) {
            const speed = 0.03;
            img.style.transform = `translateY(${scrolled * speed}px) scale(1)`;
          }
        });

        ticking = false;
      });

      ticking = true;
    }
  });
}

// Helper function
function isInViewport(element) {
  const rect = element.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}

// ========================================
// Smooth Scroll for CTA Buttons
// ========================================

function initSmoothScroll() {
  const scrollButtons = document.querySelectorAll('[data-scroll-to]');

  scrollButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = btn.getAttribute('data-scroll-to');
      const targetSection = document.querySelector(targetId);

      if (targetSection) {
        targetSection.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
}

// ========================================
// Keyboard Shortcuts
// ========================================

function initKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      document.querySelector('.search-toggle')?.click();
    }

    // C for cart
    if (e.key === 'c' && !e.ctrlKey && !e.metaKey) {
      const activeElement = document.activeElement;
      if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
        document.querySelector('.cart-toggle')?.click();
      }
    }
  });
}

// ========================================
// Notification System
// ========================================

function showNotification(message, duration = 3000) {
  const notification = document.createElement('div');
  notification.className = 'notification-toast';
  notification.textContent = message;

  notification.style.cssText = `
    position: fixed;
    top: 6rem;
    right: 3rem;
    padding: 1.25rem 2rem;
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 50px;
    color: var(--color-cream);
    font-size: 0.95rem;
    font-weight: 500;
    z-index: 99999;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 215, 0, 0.2);
    animation: toastSlideIn 0.5s cubic-bezier(0.87, 0, 0.13, 1);
    pointer-events: none;
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'toastSlideOut 0.5s cubic-bezier(0.87, 0, 0.13, 1) forwards';
    setTimeout(() => notification.remove(), 500);
  }, duration);
}

// Add notification animations dynamically
const style = document.createElement('style');
style.textContent = `
  @keyframes toastSlideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes toastSlideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// ========================================
// Performance Optimization
// ========================================

// Lazy load images
if ('IntersectionObserver' in window) {
  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          imageObserver.unobserve(img);
        }
      }
    });
  });

  document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}

// Debounce helper
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Throttle helper
function throttle(func, limit) {
  let inThrottle;
  return function() {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

// ========================================
// Analytics & Tracking (Placeholder)
// ========================================

function trackEvent(category, action, label) {
  console.log('Event tracked:', { category, action, label });
  // Here you would integrate with Google Analytics, etc.
}

// Track add to cart
document.addEventListener('click', (e) => {
  if (e.target.closest('.btn-add-cart')) {
    const productName = e.target.closest('.btn-add-cart').getAttribute('data-product-name');
    trackEvent('E-commerce', 'Add to Cart', productName);
  }
});

// ========================================
// Easter Egg - Konami Code
// ========================================

(function() {
  let konamiCode = [];
  const konamiSequence = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];

  document.addEventListener('keydown', (e) => {
    konamiCode.push(e.key);
    konamiCode = konamiCode.slice(-10);

    if (konamiCode.join(',') === konamiSequence.join(',')) {
      activateGoldenMode();
    }
  });

  function activateGoldenMode() {
    document.body.style.animation = 'goldenPulse 2s infinite';
    showNotification('🎉 Mode Or activé! Tout brille!', 5000);

    const style = document.createElement('style');
    style.textContent = `
      @keyframes goldenPulse {
        0%, 100% { filter: hue-rotate(0deg) brightness(1); }
        50% { filter: hue-rotate(30deg) brightness(1.2); }
      }
    `;
    document.head.appendChild(style);

    setTimeout(() => {
      document.body.style.animation = '';
    }, 10000);
  }
})();

// ========================================
// Console Art
// ========================================

console.log(`
  _____ ____   ___   ___   ____    __   __ __  __  ___ ____   ____   ___
 / ___// _  | | _ | |_ _| | |  |  | |  | || || ||_  | ||  _| | |_ | | |_ |
| ||   | | | || |_|||  | | |  |  | |  | || || || | | || |_  | |_|  | |_||
| |__  | |_| ||  __/| |_ | |__|  | |__| ||_||_||_| |_||____| |___| |___|
 \____|\___/ |_|  |___|  |____|  \____/

  ✨ Lumières sculptées avec passion
  🎨 Design par Claude
  🚀 Février 2026
`);

console.log('%cShortcuts:', 'font-weight: bold; font-size: 14px; color: #FFD700;');
console.log('%c• Ctrl/Cmd + K → Search', 'font-size: 12px; color: #999;');
console.log('%c• C → Cart', 'font-size: 12px; color: #999;');
console.log('%c• Esc → Close overlays', 'font-size: 12px; color: #999;');
console.log('%c• Konami Code → Easter egg 😉', 'font-size: 12px; color: #999;');
