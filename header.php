<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="header-inner">
    <div class="site-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2024/12/logo_sapi.svg" alt="Atelier Sâpi" class="custom-logo">
      </a>
    </div>

    <!-- Menu Desktop -->
    <nav class="primary-nav" aria-label="Menu principal">
      <ul class="nav-menu">
        <li><a href="<?php echo home_url('/'); ?>">Accueil</a></li>
        <li class="menu-item-has-children">
          <a href="<?php echo home_url('/nos-creations/'); ?>">Nos créations</a>
          <ul class="sub-menu">
            <li><a href="<?php echo home_url('/categorie-produit/suspensions/'); ?>">Suspensions</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampadaires/'); ?>">Lampadaires</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/appliques/'); ?>">Appliques</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampeaposer/'); ?>">À poser</a></li>
          </ul>
        </li>
        <li><a href="<?php echo home_url('/lumiere-dartisan/'); ?>">L'artisan</a></li>
        <li><a href="<?php echo home_url('/conseils-eclaires/'); ?>">Conseils</a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>">Contact</a></li>
      </ul>
    </nav>

    <div class="header-actions">
      <!-- Global Search Button -->
      <button class="search-toggle" aria-label="<?php esc_attr_e('Rechercher', 'theme-sapi-maison'); ?>" aria-expanded="false" aria-controls="global-search-modal" title="Rechercher (Ctrl+K)">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
      </button>

      <button class="cart-link mini-cart-toggle" aria-label="<?php esc_attr_e('Ouvrir le panier', 'theme-sapi-maison'); ?>" aria-expanded="false" aria-controls="mini-cart">
        <span class="cart-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </span>
        <?php $cart_count = sapi_maison_cart_count(); ?>
        <span class="cart-count<?php echo $cart_count === 0 ? ' cart-count--empty' : ''; ?>"><?php echo $cart_count > 0 ? esc_html($cart_count) : ''; ?></span>
      </button>

      <!-- Menu Burger Toggle -->
      <button class="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
        <span class="menu-burger">
          <span class="burger-line"></span>
          <span class="burger-line"></span>
          <span class="burger-line"></span>
        </span>
        <span class="menu-toggle-text">Menu</span>
      </button>
    </div>
  </div>

  <!-- Mobile Menu Overlay -->
  <div class="mobile-menu-overlay" id="mobile-menu">
    <nav class="mobile-menu-nav" aria-label="Menu mobile">
      <ul class="mobile-nav-menu">
        <li><a href="<?php echo home_url('/'); ?>">Accueil</a></li>
        <li class="menu-item-has-children">
          <a href="<?php echo home_url('/nos-creations/'); ?>">Nos créations</a>
          <ul class="sub-menu">
            <li><a href="<?php echo home_url('/categorie-produit/suspensions/'); ?>">Suspensions</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampadaires/'); ?>">Lampadaires</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/appliques/'); ?>">Appliques</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampeaposer/'); ?>">À poser</a></li>
          </ul>
        </li>
        <li><a href="<?php echo home_url('/lumiere-dartisan/'); ?>">L'artisan</a></li>
        <li><a href="<?php echo home_url('/conseils-eclaires/'); ?>">Conseils</a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>"><?php esc_html_e('Contact', 'theme-sapi-maison'); ?></a></li>
      </ul>
    </nav>
  </div>

  <!-- Mini Cart Sliding Panel -->
  <div class="mini-cart" id="mini-cart" aria-hidden="true">
    <div class="mini-cart-header">
      <h3><?php esc_html_e('Votre panier', 'theme-sapi-maison'); ?></h3>
      <button class="mini-cart-close" aria-label="<?php esc_attr_e('Fermer le panier', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="mini-cart-content">
      <?php if (function_exists('sapi_render_mini_cart_contents')) : ?>
        <?php sapi_render_mini_cart_contents(); ?>
      <?php endif; ?>
    </div>
    <?php if (function_exists('WC') && WC()->cart) : ?>
    <div class="mini-cart-footer">
      <div class="mini-cart-total">
        <span><?php esc_html_e('Total', 'theme-sapi-maison'); ?></span>
        <strong class="total-amount"><?php echo WC()->cart->get_cart_total(); ?></strong>
      </div>
      <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="btn-view-cart">
        <?php esc_html_e('Voir le panier', 'theme-sapi-maison'); ?>
      </a>
      <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="btn-checkout">
        <?php esc_html_e('Commander', 'theme-sapi-maison'); ?> →
      </a>
    </div>
    <?php endif; ?>
  </div>
  <div class="mini-cart-overlay" id="mini-cart-overlay"></div>

  <!-- Global Search Modal -->
  <div class="global-search-modal" id="global-search-modal" aria-hidden="true">
    <div class="global-search-content">
      <div class="global-search-header">
        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input
          type="text"
          id="global-search-input"
          class="global-search-input"
          placeholder="<?php esc_attr_e('Rechercher un luminaire...', 'theme-sapi-maison'); ?>"
          autocomplete="off"
          aria-label="<?php esc_attr_e('Recherche globale', 'theme-sapi-maison'); ?>"
        />
        <button class="global-search-close" aria-label="<?php esc_attr_e('Fermer la recherche', 'theme-sapi-maison'); ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="global-search-results">
        <div class="search-results-empty">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <p><?php esc_html_e('Commencez à taper pour rechercher...', 'theme-sapi-maison'); ?></p>
        </div>
        <ul class="search-results-list" style="display: none;"></ul>
      </div>
    </div>
  </div>
  <div class="global-search-overlay" id="global-search-overlay"></div>
</header>

<!-- Sticky Reassurance Bar -->
<div class="reassurance-bar reassurance-bar-sticky">
  <div class="reassurance-bar-inner">
    <div class="reassurance-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5" cy="18.5" r="2.5"></circle>
        <circle cx="18.5" cy="18.5" r="2.5"></circle>
      </svg>
      <span>Livraison 48-72h</span>
    </div>
    <div class="reassurance-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
      </svg>
      <span>Fabrication &lt;5 jours</span>
    </div>
    <div class="reassurance-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
      </svg>
      <span>Retours 30 jours</span>
    </div>
    <div class="reassurance-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
      </svg>
      <span>Paiement sécurisé</span>
    </div>
  </div>
</div>

<main class="site-content">
