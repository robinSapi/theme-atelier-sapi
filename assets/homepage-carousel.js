/**
 * Homepage Fullscreen Carousel
 * Auto-rotation toutes les 3 secondes
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    const carousel = document.querySelector('.homepage-carousel-fullscreen');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.carousel-slide');
    const dots = carousel.querySelectorAll('.carousel-dot');

    if (slides.length === 0) return;

    let currentIndex = 0;
    let autoRotateInterval;

    /**
     * Show slide at given index
     */
    function showSlide(index) {
      // Remove active class from all slides and dots
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));

      // Add active class to current slide and dot
      slides[index].classList.add('active');
      if (dots[index]) {
        dots[index].classList.add('active');
      }

      currentIndex = index;
    }

    /**
     * Go to next slide
     */
    function nextSlide() {
      const nextIndex = (currentIndex + 1) % slides.length;
      showSlide(nextIndex);
    }

    /**
     * Start auto-rotation
     */
    function startAutoRotate() {
      stopAutoRotate(); // Clear any existing interval
      autoRotateInterval = setInterval(nextSlide, 3000); // 3 seconds
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
        startAutoRotate(); // Restart auto-rotation from this slide
      });
    });

    // Pause on hover (better UX)
    carousel.addEventListener('mouseenter', stopAutoRotate);
    carousel.addEventListener('mouseleave', startAutoRotate);

    // Pause on touch/focus (mobile accessibility)
    carousel.addEventListener('touchstart', stopAutoRotate);
    carousel.addEventListener('touchend', startAutoRotate);

    // Start auto-rotation
    startAutoRotate();

    // Cleanup on page unload
    window.addEventListener('beforeunload', stopAutoRotate);
  }
})();
