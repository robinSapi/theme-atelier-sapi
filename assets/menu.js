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

/**
 * Auto-hide WooCommerce notifications
 * Les messages de succès disparaissent automatiquement après 5 secondes
 */

(function() {
  'use strict';

  // Wait for DOM to be fully loaded
  function initNotifications() {
    const notifications = document.querySelectorAll('.woocommerce-message, .woocommerce-info');

    notifications.forEach(function(notification) {
      // Skip if already processed
      if (notification.hasAttribute('data-notice-processed')) return;
      notification.setAttribute('data-notice-processed', 'true');

      // Add close button
      const closeButton = document.createElement('button');
      closeButton.className = 'woocommerce-notice-close';
      closeButton.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
      closeButton.setAttribute('aria-label', 'Fermer');
      closeButton.style.cssText = 'position: absolute; top: 8px; right: 8px; background: none; border: none; cursor: pointer; padding: 4px; opacity: 0.5; transition: opacity 0.3s;';

      closeButton.addEventListener('mouseenter', function() {
        this.style.opacity = '1';
      });

      closeButton.addEventListener('mouseleave', function() {
        this.style.opacity = '0.5';
      });

      closeButton.addEventListener('click', function() {
        notification.style.animation = 'slideOutToRight 0.3s ease-out';
        setTimeout(function() {
          notification.remove();
        }, 300);
      });

      notification.style.position = 'relative';
      notification.style.paddingRight = '3rem';
      notification.appendChild(closeButton);

      // Auto-hide after 5 seconds
      setTimeout(function() {
        if (notification.parentElement) {
          notification.style.animation = 'slideOutToRight 0.3s ease-out';
          setTimeout(function() {
            notification.remove();
          }, 300);
        }
      }, 5000);
    });
  }

  // Run on page load
  setTimeout(initNotifications, 100);

  // Re-run when WooCommerce updates cart (via AJAX)
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_wc_div', initNotifications);
  }

  // Add slide out animation
  if (!document.querySelector('#woocommerce-notification-animations')) {
    const style = document.createElement('style');
    style.id = 'woocommerce-notification-animations';
    style.textContent = `
      @keyframes slideOutToRight {
        from {
          opacity: 1;
          transform: translateX(0);
        }
        to {
          opacity: 0;
          transform: translateX(100px);
        }
      }
    `;
    document.head.appendChild(style);
  }

})();

/**
 * Mini Cart Toggle
 * Gestion du panier latéral sliding
 */

(function() {
  'use strict';

  // Elements
  const miniCartToggle = document.querySelector('.mini-cart-toggle');
  const miniCart = document.querySelector('.mini-cart');
  const miniCartOverlay = document.querySelector('.mini-cart-overlay');
  const miniCartClose = document.querySelector('.mini-cart-close');
  const body = document.body;

  if (!miniCartToggle || !miniCart || !miniCartOverlay) {
    return;
  }

  /**
   * Open mini cart
   */
  function openMiniCart() {
    miniCartToggle.setAttribute('aria-expanded', 'true');
    miniCart.classList.add('is-open');
    miniCart.setAttribute('aria-hidden', 'false');
    miniCartOverlay.classList.add('is-visible');
    body.style.overflow = 'hidden';
  }

  /**
   * Close mini cart
   */
  function closeMiniCart() {
    miniCartToggle.setAttribute('aria-expanded', 'false');
    miniCart.classList.remove('is-open');
    miniCart.setAttribute('aria-hidden', 'true');
    miniCartOverlay.classList.remove('is-visible');
    body.style.overflow = '';
  }

  /**
   * Event Listeners
   */

  // Toggle cart on button click
  miniCartToggle.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const isOpen = miniCart.classList.contains('is-open');
    if (isOpen) {
      closeMiniCart();
    } else {
      openMiniCart();
    }
  });

  // Close cart on close button click
  if (miniCartClose) {
    miniCartClose.addEventListener('click', closeMiniCart);
  }

  // Close cart when clicking on overlay
  miniCartOverlay.addEventListener('click', closeMiniCart);

  // Close cart with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && miniCart.classList.contains('is-open')) {
      closeMiniCart();
    }
  });

  // Remove item from cart
  document.addEventListener('click', function(e) {
    if (e.target.closest('.mini-cart-item-remove')) {
      e.preventDefault();
      const removeButton = e.target.closest('.mini-cart-item-remove');
      const cartItemKey = removeButton.getAttribute('data-cart-item-key');

      if (!cartItemKey) return;

      // Add loading state
      removeButton.disabled = true;
      removeButton.style.opacity = '0.5';

      // AJAX request to remove item
      fetch(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_from_cart'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          'cart_item_key': cartItemKey
        })
      })
      .then(response => response.json())
      .then(data => {
        // Trigger WooCommerce cart update
        if (typeof jQuery !== 'undefined') {
          jQuery(document.body).trigger('wc_fragment_refresh');
        }
      })
      .catch(error => {
        console.error('Error removing item:', error);
        removeButton.disabled = false;
        removeButton.style.opacity = '1';
      });
    }
  });

  // Update mini cart on AJAX add to cart
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('added_to_cart', function() {
      // Auto-open cart when item added
      openMiniCart();
    });

    // Update cart count badge
    jQuery(document.body).on('wc_fragments_refreshed', function() {
      // Cart count is already updated via WooCommerce fragments
      // Just ensure cart is visible if items exist
      const cartItems = document.querySelectorAll('.mini-cart-item');
      if (cartItems.length === 0) {
        closeMiniCart();
      }
    });
  }

})();
