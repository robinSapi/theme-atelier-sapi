/**
 * Homepage Fullscreen Carousel
 * Auto-rotation toutes les 5 secondes + navigation manuelle (dots, flèches, swipe)
 * Met à jour la naming card (.naming-link) à chaque changement de slide.
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  /**
   * Reproduit la logique de product-name-formatter.js pour le naming carousel :
   * split au premier espace → firstname (Montserrat upper) + restname (Square Peg).
   */
  function formatProductName(name) {
    const trimmed = (name || '').trim();
    if (!trimmed) return '';
    const firstSpace = trimmed.indexOf(' ');
    if (firstSpace === -1) {
      return '<span class="product-firstname">' + escapeHtml(trimmed) + '</span>';
    }
    const firstname = trimmed.substring(0, firstSpace);
    const restname  = trimmed.substring(firstSpace + 1);
    // P9-a : nom commençant par un article (La, Le, Les, L') → tout en Square Peg
    if (/^(la|le|les)$/i.test(firstname) || /^l['']/i.test(firstname)) {
      return '<span class="product-restname">' + escapeHtml(trimmed) + '</span>';
    }
    return '<span class="product-firstname">' + escapeHtml(firstname) + '</span>'
         + '<span class="product-restname">' + escapeHtml(restname) + '</span>';
  }

  function init() {
    const carousel = document.querySelector('.homepage-carousel-fullscreen');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.carousel-slide');
    const dots = carousel.querySelectorAll('.carousel-dot');
    const prevBtn = carousel.querySelector('.carousel-arrow-prev');
    const nextBtn = carousel.querySelector('.carousel-arrow-next');
    const namingLink = carousel.querySelector('#carousel-naming-link');
    const slidesData = window.SAPI_CAROUSEL_DATA || [];

    if (slides.length === 0) return;

    let currentIndex = 0;
    let autoRotateInterval;

    /**
     * Met à jour le contenu de la naming card pour la slide à l'index donné.
     * - Slide produit : split firstname/restname.
     * - Slide promo : titre brut, classe .is-promo (typo Square Peg pleine).
     * - URL vide → pointer-events: none côté link (lien désactivé).
     */
    function updateNamingCard(index) {
      if (!namingLink || !slidesData[index]) return;
      const data = slidesData[index];
      if (data.isPromo) {
        namingLink.innerHTML = escapeHtml(data.name || '');
        namingLink.classList.add('is-promo');
      } else {
        namingLink.innerHTML = formatProductName(data.name);
        namingLink.classList.remove('is-promo');
      }
      if (data.url) {
        namingLink.setAttribute('href', data.url);
        namingLink.style.pointerEvents = '';
      } else {
        namingLink.setAttribute('href', '#');
        namingLink.style.pointerEvents = 'none';
      }
      namingLink.setAttribute('aria-label', 'Découvrir ' + (data.name || ''));
    }

    /**
     * Show slide at given index
     */
    function showSlide(index) {
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));

      slides[index].classList.add('active');
      if (dots[index]) {
        dots[index].classList.add('active');
      }

      currentIndex = index;
      updateNamingCard(index);
    }

    /**
     * Go to next slide
     */
    function nextSlide() {
      const nextIndex = (currentIndex + 1) % slides.length;
      showSlide(nextIndex);
    }

    function prevSlide() {
      const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
      showSlide(prevIndex);
    }

    /**
     * Start auto-rotation
     */
    function startAutoRotate() {
      stopAutoRotate();
      autoRotateInterval = setInterval(nextSlide, 5000);
    }

    /**
     * Stop auto-rotation
     */
    function stopAutoRotate() {
      if (autoRotateInterval) {
        clearInterval(autoRotateInterval);
      }
    }

    /**
     * Dot click handlers
     */
    dots.forEach((dot, index) => {
      dot.addEventListener('click', function() {
        showSlide(index);
        startAutoRotate();
      });
    });

    /**
     * Arrow click handlers (prev / next, wrap-around)
     */
    if (prevBtn) {
      prevBtn.addEventListener('click', function() {
        prevSlide();
        startAutoRotate();
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        nextSlide();
        startAutoRotate();
      });
    }

    /**
     * Swipe tactile sur le carousel : swipe gauche → next, droite → prev.
     * Cohabite avec la pause autoplay au touch (acquis M22).
     */
    const SWIPE_THRESHOLD = 50; // px
    let touchStartX = null;
    const swipeTarget = carousel.querySelector('.carousel-slides') || carousel;

    swipeTarget.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    swipeTarget.addEventListener('touchend', function(e) {
      if (touchStartX === null) return;
      const touchEndX = e.changedTouches[0].screenX;
      const diff = touchEndX - touchStartX;
      if (Math.abs(diff) > SWIPE_THRESHOLD) {
        if (diff < 0) {
          nextSlide();
        } else {
          prevSlide();
        }
        startAutoRotate();
      }
      touchStartX = null;
    }, { passive: true });

    // Init naming card avec la 1ʳᵉ slide
    updateNamingCard(0);

    // Start auto-rotation
    startAutoRotate();

    // Mobile : pause au touch pour laisser le temps de viser une slide,
    // redémarrage 3 s après le relâchement (acquis M22).
    let touchResumeTimer;
    carousel.addEventListener('touchstart', function() {
      stopAutoRotate();
      clearTimeout(touchResumeTimer);
    }, { passive: true });
    carousel.addEventListener('touchend', function() {
      clearTimeout(touchResumeTimer);
      touchResumeTimer = setTimeout(startAutoRotate, 3000);
    }, { passive: true });

    // Cleanup on page unload
    window.addEventListener('beforeunload', stopAutoRotate);
  }
})();
