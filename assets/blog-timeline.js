/**
 * Blog Timeline & Carousel
 * Gestion de la timeline interactive et du carousel d'articles
 */

(function() {
  'use strict';

  // Attendre que le DOM soit chargé
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    const carousel = document.querySelector('.blog-carousel');
    if (!carousel) return;

    const track = carousel.querySelector('.blog-carousel-track');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    const timelineDots = document.querySelectorAll('.timeline-dot');
    const cards = document.querySelectorAll('.blog-carousel .blog-card');

    if (!track || !prevBtn || !nextBtn || cards.length === 0) return;

    let currentIndex = 0;
    const totalCards = cards.length;

    // Calculer combien de cartes sont visibles
    function getVisibleCards() {
      const width = window.innerWidth;
      if (width <= 768) return 1;
      if (width <= 1024) return 2;
      return 3;
    }

    // Mettre à jour la position du carousel
    function updateCarousel() {
      const visibleCards = getVisibleCards();
      const cardWidth = cards[0].offsetWidth;
      const gap = 30; // Gap entre les cartes (voir CSS)

      // Calculer le décalage
      const offset = currentIndex * (cardWidth + gap);
      track.style.transform = `translateX(-${offset}px)`;

      // Mettre à jour les boutons
      prevBtn.disabled = currentIndex === 0;
      nextBtn.disabled = currentIndex >= totalCards - visibleCards;

      // Mettre à jour la timeline
      updateTimeline();
    }

    // Mettre à jour la timeline (point actif)
    function updateTimeline() {
      timelineDots.forEach((dot, index) => {
        if (index === currentIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    // Navigation précédent
    prevBtn.addEventListener('click', function() {
      if (currentIndex > 0) {
        currentIndex--;
        updateCarousel();
      }
    });

    // Navigation suivant
    nextBtn.addEventListener('click', function() {
      const visibleCards = getVisibleCards();
      if (currentIndex < totalCards - visibleCards) {
        currentIndex++;
        updateCarousel();
      }
    });

    // Click sur timeline
    timelineDots.forEach((dot, index) => {
      dot.addEventListener('click', function() {
        currentIndex = index;

        // Ajuster si on dépasse la limite
        const visibleCards = getVisibleCards();
        const maxIndex = totalCards - visibleCards;
        if (currentIndex > maxIndex) {
          currentIndex = maxIndex;
        }

        updateCarousel();
      });
    });

    // Gestion du responsive
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        // Réajuster l'index si nécessaire
        const visibleCards = getVisibleCards();
        const maxIndex = totalCards - visibleCards;
        if (currentIndex > maxIndex) {
          currentIndex = maxIndex;
        }
        updateCarousel();
      }, 250);
    });

    // Navigation clavier (accessibilité)
    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft') {
        prevBtn.click();
      } else if (e.key === 'ArrowRight') {
        nextBtn.click();
      }
    });

    // Initialiser
    updateCarousel();
  }
})();
