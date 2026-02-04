/**
 * Mobile Menu Toggle
 * Gestion du menu burger responsive
 */

(function() {
  'use strict';

  // Elements
  const menuToggle = document.querySelector('.menu-toggle');
  const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
  const mobileMenuLinks = document.querySelectorAll('.mobile-nav-menu a');
  const body = document.body;

  if (!menuToggle || !mobileMenuOverlay) {
    return;
  }

  /**
   * Toggle mobile menu
   */
  function toggleMenu() {
    const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  /**
   * Open mobile menu
   */
  function openMenu() {
    menuToggle.setAttribute('aria-expanded', 'true');
    mobileMenuOverlay.classList.add('active');
    body.style.overflow = 'hidden';
  }

  /**
   * Close mobile menu
   */
  function closeMenu() {
    menuToggle.setAttribute('aria-expanded', 'false');
    mobileMenuOverlay.classList.remove('active');
    body.style.overflow = '';
  }

  /**
   * Event Listeners
   */
  menuToggle.addEventListener('click', toggleMenu);

  // Close menu when clicking on overlay background
  mobileMenuOverlay.addEventListener('click', function(e) {
    if (e.target === mobileMenuOverlay) {
      closeMenu();
    }
  });

  // Close menu when clicking on a menu link
  mobileMenuLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      closeMenu();
    });
  });

  // Close menu with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileMenuOverlay.classList.contains('active')) {
      closeMenu();
    }
  });

  // Close menu on window resize if going back to desktop
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      if (window.innerWidth > 768 && mobileMenuOverlay.classList.contains('active')) {
        closeMenu();
      }
    }, 250);
  });

})();
