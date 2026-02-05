// ========================================
// SAPI FLUIDE - Interactions JavaScript
// ========================================

document.addEventListener('DOMContentLoaded', () => {

  // Scroll-triggered animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  // Observe tous les éléments avec animation
  const animatedElements = document.querySelectorAll('.product-card, .story-content, .newsletter-content');
  animatedElements.forEach(el => observer.observe(el));

  // Parallax effect sur le hero et les images
  window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;

    // Parallax hero image
    const heroImage = document.querySelector('.hero-image');
    if (heroImage) {
      heroImage.style.transform = `translateY(${scrolled * 0.15}px)`;
    }

    // Parallax story images
    const storyImages = document.querySelectorAll('.story-image');
    storyImages.forEach((img, index) => {
      const speed = index === 0 ? 0.08 : 0.12;
      img.style.transform = `translateY(${scrolled * speed}px)`;
    });
  });

  // Navigation background on scroll
  const nav = document.querySelector('.nav-minimal');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 100) {
      nav.style.background = 'rgba(250, 249, 246, 0.95)';
      nav.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
    } else {
      nav.style.background = 'rgba(250, 249, 246, 0.8)';
      nav.style.boxShadow = 'none';
    }
  });

  // Product filters
  const filterBtns = document.querySelectorAll('.filter-btn');
  const productCards = document.querySelectorAll('.product-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      // Remove active class from all buttons
      filterBtns.forEach(b => b.classList.remove('active'));
      // Add active to clicked button
      btn.classList.add('active');

      const filter = btn.textContent.toLowerCase().trim();

      productCards.forEach(card => {
        const category = card.getAttribute('data-category');

        if (filter === 'tout' || category === filter) {
          card.style.display = 'block';
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, 10);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          setTimeout(() => {
            card.style.display = 'none';
          }, 300);
        }
      });
    });
  });

  // Smooth scroll for anchor links
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

  // Newsletter form submission
  const newsletterForm = document.querySelector('.newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = newsletterForm.querySelector('input[type="email"]');
      const button = newsletterForm.querySelector('button');

      // Simulate submission
      button.textContent = 'Inscrit !';
      button.style.background = '#6B6B6B';
      input.disabled = true;

      setTimeout(() => {
        button.textContent = "S'inscrire";
        button.style.background = 'var(--color-terracotta)';
        input.disabled = false;
        input.value = '';
      }, 3000);
    });
  }

  // Enhanced hover effect for product cards with mouse follow
  productCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = (y - centerY) / 30;
      const rotateY = (centerX - x) / 30;

      const image = card.querySelector('.product-image');
      if (image) {
        image.style.transform = `scale(1.08) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      }
    });

    card.addEventListener('mouseleave', () => {
      const image = card.querySelector('.product-image');
      if (image) {
        image.style.transform = 'scale(1) rotateX(0) rotateY(0)';
      }
    });
  });

  // Add cart functionality (basic)
  const cartCount = document.querySelector('.cart-count');
  let itemCount = 0;

  document.querySelectorAll('.overlay-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      itemCount++;
      cartCount.textContent = itemCount;

      // Animation du panier
      cartCount.style.transform = 'scale(1.5)';
      setTimeout(() => {
        cartCount.style.transform = 'scale(1)';
      }, 300);

      // Feedback visuel
      btn.textContent = 'Ajouté !';
      btn.style.background = '#6B6B6B';
      setTimeout(() => {
        btn.textContent = 'Voir les détails';
        btn.style.background = 'var(--color-terracotta)';
      }, 1500);
    });
  });

  // Cursor custom effect (optional - décommentez si souhaité)
  /*
  const cursor = document.createElement('div');
  cursor.className = 'custom-cursor';
  document.body.appendChild(cursor);

  document.addEventListener('mousemove', (e) => {
    cursor.style.left = e.clientX + 'px';
    cursor.style.top = e.clientY + 'px';
  });

  document.querySelectorAll('.product-card, button, a').forEach(el => {
    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
  });
  */

  console.log('🎨 SAPI FLUIDE loaded - Interactions ready!');
});

// Performance optimization: debounce scroll events
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

// Use debounce for scroll events if performance is an issue
// window.addEventListener('scroll', debounce(() => { ... }, 10));
