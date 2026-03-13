<?php
$is_simplified = function_exists('is_cart') && (is_cart() || is_checkout());
$logo_id  = get_theme_mod('custom_logo');
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : home_url('/wp-content/uploads/2024/12/logo_sapi.svg');
$logo_alt = get_bloginfo('name');
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-to-content" href="#main-content"><?php esc_html_e('Aller au contenu', 'theme-sapi-maison'); ?></a>

<?php if ($is_simplified) : ?>

<header class="site-header site-header--simplified">
  <div class="header-inner header-inner--centered">
    <div class="site-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="custom-logo">
      </a>
    </div>
  </div>
</header>

<?php else : ?>

<header class="site-header">
  <div class="header-inner">
    <div class="site-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="custom-logo">
      </a>
    </div>

    <!-- Menu Desktop -->
    <?php
    wp_nav_menu([
      'theme_location' => 'primary',
      'container'      => 'nav',
      'container_class' => 'primary-nav',
      'container_aria_label' => __('Menu principal', 'theme-sapi-maison'),
      'menu_class'     => 'nav-menu',
      'menu_id'        => '',
      'fallback_cb'    => 'sapi_fallback_primary_menu',
    ]);
    ?>

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
        <span class="cart-count<?php echo $cart_count === 0 ? ' cart-count--empty' : ''; ?>" aria-live="polite" aria-atomic="true"><?php echo $cart_count > 0 ? esc_html($cart_count) : ''; ?></span>
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
    <button class="mobile-menu-close" aria-label="<?php esc_attr_e('Fermer le menu', 'theme-sapi-maison'); ?>">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
    <?php
    wp_nav_menu([
      'theme_location' => 'primary',
      'container'      => 'nav',
      'container_class' => 'mobile-menu-nav',
      'container_aria_label' => __('Menu mobile', 'theme-sapi-maison'),
      'menu_class'     => 'mobile-nav-menu',
      'menu_id'        => 'mobile-nav-menu',
      'fallback_cb'    => 'sapi_fallback_mobile_menu',
    ]);
    ?>
  </div>

  <!-- Mini Cart Sliding Panel -->
  <div class="mini-cart" id="mini-cart" aria-hidden="true">
    <div class="mini-cart-header">
      <h3><?php esc_html_e('Votre panier', 'theme-sapi-maison'); ?></h3>
      <button class="mini-cart-close" aria-label="<?php esc_attr_e('Fermer le panier', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <?php if (function_exists('sapi_render_mini_cart_contents')) : ?>
      <?php sapi_render_mini_cart_contents(); ?>
    <?php endif; ?>
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

<!-- Bandeau "Mon projet" — questionnaire permanent -->
<?php
require_once get_template_directory() . '/inc/guide-data.php';
$mon_projet_steps = sapi_guide_get_steps();
$mon_projet_icons = sapi_guide_get_icons();
$conseils_url = get_permalink(get_page_by_path('conseils-eclaires'));
$shop_url = class_exists('WooCommerce') ? esc_url(wc_get_page_permalink('shop')) : '/nos-creations/';
?>
<div class="mon-projet-bar" id="mon-projet-bar">
  <!-- État replié -->
  <div class="mon-projet-collapsed">
    <div class="mon-projet-left">
      <span class="mon-projet-label">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        Mon projet
      </span>
      <div class="mon-projet-chips" id="mon-projet-chips">
        <span class="mon-projet-placeholder">Cliquez pour d&eacute;finir votre projet</span>
      </div>
    </div>
    <div class="mon-projet-actions">
      <a href="<?php echo esc_url($conseils_url); ?>" class="mon-projet-btn-conseils">Les conseils de Robin</a>
      <a href="<?php echo esc_url($shop_url); ?>?filtre=ma-selection" class="mon-projet-btn-selection" id="mon-projet-btn-selection" style="display:none;">Ma s&eacute;lection</a>
    </div>
    <button class="mon-projet-toggle" id="mon-projet-toggle" type="button" aria-expanded="false" aria-controls="mon-projet-expanded">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </button>
  </div>

  <!-- État déplié — questionnaire inline -->
  <div class="mon-projet-expanded" id="mon-projet-expanded" aria-hidden="true">
    <?php foreach ($mon_projet_steps as $step) :
      $step_id = esc_attr($step['id']);
      $icon_map = $mon_projet_icons;
    ?>
    <div class="mon-projet-question" data-step="<?php echo $step_id; ?>">
      <span class="mon-projet-question-label"><?php echo esc_html($step['question']); ?></span>
      <div class="mon-projet-choices" data-step="<?php echo $step_id; ?>">
        <?php foreach ($step['choices'] as $choice) :
          $icon_key = isset($choice['icon']) ? $choice['icon'] : '';
          $icon_svg = isset($icon_map[$icon_key]) ? $icon_map[$icon_key] : '';
        ?>
        <button class="mon-projet-choice" type="button"
                data-step="<?php echo $step_id; ?>"
                data-slug="<?php echo esc_attr($choice['slug']); ?>"
                data-label="<?php echo esc_attr($choice['label']); ?>">
          <?php if ($icon_svg) : ?>
            <span class="mon-projet-choice-icon"><?php echo $icon_svg; ?></span>
          <?php endif; ?>
          <span class="mon-projet-choice-text"><?php echo esc_html($choice['label']); ?></span>
          <?php if (!empty($choice['dim'])) : ?>
            <span class="mon-projet-choice-dim"><?php echo esc_html($choice['dim']); ?></span>
          <?php endif; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="mon-projet-actions-row">
      <button class="mon-projet-reset" id="mon-projet-reset" type="button">R&eacute;initialiser</button>
      <button class="mon-projet-validate" id="mon-projet-validate" type="button" style="display:none;">Valider mon projet</button>
    </div>
  </div>
</div>

<?php endif; ?>

<main class="site-content" id="main-content">
