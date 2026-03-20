<?php
/**
 * Thankyou — Atelier Sâpi
 * Template customisé : miroir de la page panier (2 colonnes 65/35)
 */
defined('ABSPATH') || exit;
?>

<div class="sapi-thankyou-outer">

<?php if ($order && !$order->has_status('failed')) : ?>

  <!-- Barre de progression — étapes 1 & 2 complétées, 3 active -->
  <div class="checkout-progress">
    <div class="progress-step completed">
      <div class="step-number">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <span class="step-label">Panier</span>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step completed">
      <div class="step-number">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <span class="step-label">Paiement</span>
    </div>
  </div>

  <!-- Grand merci — animation manuscrite SVG -->
  <div class="thankyou-merci">
    <svg viewBox="0 0 500 160" class="thankyou-merci-svg" aria-label="Merci !">
      <text x="250" y="120" text-anchor="middle">Merci !</text>
    </svg>
  </div>

  <!-- Hero succès -->
  <div class="thankyou-hero">
    <h1>Votre luminaire va bientôt voir le jour</h1>
    <p class="thankyou-subtitle">
      Merci <?php echo esc_html($order->get_billing_first_name()); ?>, votre commande <strong>#<?php echo esc_html($order->get_order_number()); ?></strong> est confirmée.<br>
      Un email de confirmation a été envoyé à <strong><?php echo esc_html($order->get_billing_email()); ?></strong>.
    </p>
  </div>

  <!-- Réassurance post-achat -->
  <div class="thankyou-reassurance">
    <div class="thankyou-reassurance-item">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
      </svg>
      <div>
        <strong>On fabrique dès que possible</strong>
        <span>Votre pièce sera préparée avec soin dans l'atelier lyonnais de Robin</span>
      </div>
    </div>
    <div class="thankyou-reassurance-item">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
      <div>
        <strong>Contactez Robin si besoin</strong>
        <span>Robin est disponible pour toute question sur votre commande</span>
      </div>
    </div>
    <div class="thankyou-reassurance-item">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1" y="3" width="15" height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5" cy="18.5" r="2.5"></circle>
        <circle cx="18.5" cy="18.5" r="2.5"></circle>
      </svg>
      <div>
        <strong>Suivi de livraison par email</strong>
        <span>Vous recevrez un email avec le suivi dès l'expédition</span>
      </div>
    </div>
  </div>

  <!-- Layout 2 colonnes -->
  <div class="thankyou-layout">

    <!-- Colonne gauche (65%) — Détail de la commande -->
    <div class="thankyou-main">
      <div class="thankyou-card">
        <?php wc_get_template('order/order-details.php', array('order_id' => $order->get_id())); ?>
      </div>
    </div>

    <!-- Colonne droite (35%) — Récapitulatif + Adresse -->
    <div class="thankyou-sidebar">

      <?php if ($order->get_formatted_shipping_address() || $order->get_formatted_billing_address()) : ?>
      <div class="thankyou-card">
        <?php wc_get_template('order/order-details-customer.php', array('order' => $order)); ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Réseaux sociaux -->
  <div class="thankyou-social">
    <p>Suivez l'aventure Sâpi</p>
    <div class="thankyou-social-links">
      <a href="https://www.instagram.com/atelier_sapi/" target="_blank" rel="noopener" aria-label="Instagram">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
          <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
          <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
        </svg>
      </a>
      <a href="https://www.facebook.com/ateliersapi" target="_blank" rel="noopener" aria-label="Facebook">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
        </svg>
      </a>
      <a href="https://www.pinterest.fr/ateliersapi/" target="_blank" rel="noopener" aria-label="Pinterest">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 12a4 4 0 1 1 8 0c0 2.5-1.5 4.5-3 6l-1 4"></path>
          <path d="M12 2a10 10 0 1 0 4 19.2"></path>
        </svg>
      </a>
    </div>
  </div>

  <!-- CTA retour boutique -->
  <div class="thankyou-cta">
    <a href="<?php echo esc_url(home_url('/nos-creations/')); ?>" class="thankyou-cta-btn">
      Continuer à explorer les créations
    </a>
  </div>

  <?php do_action('woocommerce_thankyou', $order->get_id()); ?>

<?php elseif ($order && $order->has_status('failed')) : ?>

  <!-- Commande échouée -->
  <div class="thankyou-hero thankyou-hero--failed">
    <span class="section-number">03 — Confirmation</span>
    <h1><?php esc_html_e('Un problème est survenu', 'woocommerce'); ?></h1>
    <p class="thankyou-subtitle"><?php esc_html_e('Votre paiement n\'a pas pu être traité. Veuillez réessayer.', 'theme-sapi-maison'); ?></p>
    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="thankyou-btn-retry">
      <?php esc_html_e('Réessayer le paiement', 'woocommerce'); ?>
    </a>
  </div>

  <?php do_action('woocommerce_thankyou', $order->get_id()); ?>

<?php else : ?>

  <!-- Fallback sans commande -->
  <div class="thankyou-hero">
    <span class="section-number">03 — Confirmation</span>
    <h1><?php echo esc_html(apply_filters('woocommerce_thankyou_order_received_text', __('Votre commande a été reçue.', 'woocommerce'), null)); ?></h1>
  </div>

<?php endif; ?>

</div>
