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

  // Close menu with close button
  const mobileMenuClose = document.querySelector('.mobile-menu-close');
  if (mobileMenuClose) {
    mobileMenuClose.addEventListener('click', closeMenu);
  }

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

  // Update quantity from mini cart
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.mini-cart-qty-minus, .mini-cart-qty-plus');
    if (!btn) return;
    e.preventDefault();

    var selector = btn.closest('.mini-cart-qty-selector');
    if (!selector) return;

    var cartItemKey = selector.getAttribute('data-cart-item-key');
    var valueEl = selector.querySelector('.mini-cart-qty-value');
    var qty = parseInt(valueEl.textContent, 10) || 1;

    if (btn.classList.contains('mini-cart-qty-minus')) {
      qty = Math.max(0, qty - 1);
    } else {
      qty = qty + 1;
    }

    // Disable buttons during request
    selector.classList.add('is-loading');

    fetch(wc_add_to_cart_params.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'sapi_update_mini_cart_qty',
        cart_item_key: cartItemKey,
        quantity: qty,
        nonce: (typeof sapiMenu !== 'undefined') ? sapiMenu.miniCartNonce : ''
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success && data.data.fragments) {
        if (typeof jQuery !== 'undefined') {
          jQuery.each(data.data.fragments, function(key, value) {
            jQuery(key).replaceWith(value);
          });
          jQuery(document.body).trigger('wc_fragments_refreshed');
        }
      } else {
        if (typeof jQuery !== 'undefined') {
          jQuery(document.body).trigger('wc_fragment_refresh');
        }
      }
    })
    .catch(function() {
      selector.classList.remove('is-loading');
    });
  });

  // Update mini cart on AJAX add to cart
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('added_to_cart', function() {
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

/**
 * Global Search Modal
 * Recherche globale avec Ctrl+K et autocomplétion
 */

(function() {
  'use strict';

  // Elements
  const searchToggle = document.querySelector('.search-toggle');
  const searchModal = document.querySelector('.global-search-modal');
  const searchOverlay = document.querySelector('.global-search-overlay');
  const searchClose = document.querySelector('.global-search-close');
  const searchInput = document.getElementById('global-search-input');
  const searchResultsList = document.querySelector('.search-results-list');
  const searchResultsEmpty = document.querySelector('.search-results-empty');
  const body = document.body;

  if (!searchToggle || !searchModal || !searchOverlay || !searchInput) {
    return;
  }

  let searchTimeout;
  let currentResults = [];
  let selectedIndex = -1;
  let previouslyFocused = null;
  let focusTrapHandler = null;

  /**
   * Focus trap — keeps Tab inside the modal (WCAG 2.1)
   */
  function setupFocusTrap() {
    const focusable = searchModal.querySelectorAll(
      'button:not([disabled]), [href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if (!focusable.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    focusTrapHandler = (e) => {
      if (e.key !== 'Tab') return;
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    };
    document.addEventListener('keydown', focusTrapHandler);
  }

  function removeFocusTrap() {
    if (focusTrapHandler) {
      document.removeEventListener('keydown', focusTrapHandler);
      focusTrapHandler = null;
    }
  }

  /**
   * Open search modal
   */
  function openSearch() {
    previouslyFocused = document.activeElement;
    searchModal.setAttribute('aria-hidden', 'false');
    searchOverlay.classList.add('is-visible');
    body.style.overflow = 'hidden';

    setTimeout(() => {
      searchInput.focus();
      setupFocusTrap();
    }, 100);
  }

  /**
   * Close search modal
   */
  function closeSearch() {
    removeFocusTrap();
    searchModal.setAttribute('aria-hidden', 'true');
    searchOverlay.classList.remove('is-visible');
    body.style.overflow = '';
    searchInput.value = '';
    clearResults();
    selectedIndex = -1;

    if (previouslyFocused && previouslyFocused.focus) {
      previouslyFocused.focus();
    }
  }

  /**
   * Clear search results
   */
  function clearResults() {
    searchResultsList.innerHTML = '';
    searchResultsList.style.display = 'none';
    searchResultsEmpty.style.display = 'flex';
    currentResults = [];
  }

  /**
   * Perform search with AJAX
   */
  function performSearch(query) {
    if (!query || query.length < 2) {
      clearResults();
      return;
    }

    // Show loading state
    searchResultsEmpty.innerHTML = '<p>Recherche en cours...</p>';
    searchResultsEmpty.style.display = 'flex';
    searchResultsList.style.display = 'none';

    // AJAX search request - use custom endpoint with metadata support
    fetch(window.location.origin + '/wp-json/sapi/v1/products/search?query=' + encodeURIComponent(query))
      .then(response => response.json())
      .then(products => {
        if (products && products.length > 0) {
          displayResults(products);
        } else {
          showNoResults();
        }
      })
      .catch(error => {
        console.error('Search error:', error);
        showNoResults();
      });
  }

  /**
   * Display search results
   */
  function displayResults(products) {
    currentResults = products;
    searchResultsEmpty.style.display = 'none';
    searchResultsList.style.display = 'block';
    searchResultsList.innerHTML = '';

    products.forEach((product, index) => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = product.link;
      a.className = 'search-result-item';
      a.setAttribute('data-index', index);

      // Product data from custom endpoint
      const imageUrl = product.image || '';
      const priceHTML = product.price ? `<span class="search-result-price">${product.price}</span>` : '';
      const categoryHTML = product.categories && product.categories.length > 0 ? `<span>${product.categories[0]}</span>` : '';

      a.innerHTML = `
        <div class="search-result-image">
          ${imageUrl ? `<img src="${imageUrl}" alt="${product.title}" loading="lazy">` : ''}
        </div>
        <div class="search-result-info">
          <h4 class="search-result-title">${product.title}</h4>
          <div class="search-result-meta">
            ${categoryHTML}
          </div>
        </div>
        ${priceHTML}
      `;

      // Mouse events
      a.addEventListener('mouseenter', function() {
        selectedIndex = index;
        updateSelection();
      });

      a.addEventListener('click', function(e) {
        // Let the link work naturally
      });

      li.appendChild(a);
      searchResultsList.appendChild(li);
    });
  }

  /**
   * Show no results message
   */
  function showNoResults() {
    searchResultsEmpty.innerHTML = `
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
      <p>Aucun résultat trouvé</p>
    `;
    searchResultsEmpty.style.display = 'flex';
    searchResultsList.style.display = 'none';
    currentResults = [];
  }

  /**
   * Update selected item
   */
  function updateSelection() {
    const items = searchResultsList.querySelectorAll('.search-result-item');
    items.forEach((item, index) => {
      if (index === selectedIndex) {
        item.classList.add('active');
        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } else {
        item.classList.remove('active');
      }
    });
  }

  /**
   * Event Listeners
   */

  // Toggle search on button click
  searchToggle.addEventListener('click', function() {
    openSearch();
  });

  // Close search
  if (searchClose) {
    searchClose.addEventListener('click', closeSearch);
  }

  searchOverlay.addEventListener('click', closeSearch);

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Ctrl+K or Cmd+K to open search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      if (searchModal.getAttribute('aria-hidden') === 'true') {
        openSearch();
      } else {
        closeSearch();
      }
    }

    // Escape to close
    if (e.key === 'Escape' && searchModal.getAttribute('aria-hidden') === 'false') {
      closeSearch();
    }

    // Arrow navigation when search is open
    if (searchModal.getAttribute('aria-hidden') === 'false' && currentResults.length > 0) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex = Math.min(selectedIndex + 1, currentResults.length - 1);
        updateSelection();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex = Math.max(selectedIndex - 1, -1);
        updateSelection();
      } else if (e.key === 'Enter' && selectedIndex >= 0) {
        e.preventDefault();
        const selectedItem = searchResultsList.querySelector(`[data-index="${selectedIndex}"]`);
        if (selectedItem) {
          window.location.href = selectedItem.href;
        }
      }
    }
  });

  // Real-time search with debounce
  searchInput.addEventListener('input', function() {
    const query = this.value.trim();

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      performSearch(query);
    }, 300);
  });

})();
