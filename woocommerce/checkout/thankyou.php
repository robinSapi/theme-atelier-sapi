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
      <span class="step-label">Commande</span>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step active">
      <div class="step-number">3</div>
      <span class="step-label">Confirmation</span>
    </div>
  </div>

  <!-- Hero succès -->
  <div class="thankyou-hero">
    <div class="thankyou-success-icon">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
    </div>
    <span class="section-number">03 — Confirmation</span>
    <h1>Commande confirmée !</h1>
    <p class="thankyou-subtitle">
      Merci <?php echo esc_html($order->get_billing_first_name()); ?>, votre commande <strong>#<?php echo esc_html($order->get_order_number()); ?></strong> est en cours de préparation.<br>
      Un email de confirmation a été envoyé à <strong><?php echo esc_html($order->get_billing_email()); ?></strong>.
    </p>
  </div>

  <!-- Layout 2 colonnes -->
  <div class="thankyou-layout">

    <!-- Colonne gauche (65%) — Détail de la commande -->
    <div class="thankyou-main">
      <div class="thankyou-card">
        <h2><?php esc_html_e('Détail de la commande', 'woocommerce'); ?></h2>
        <?php wc_get_template('order/order-details.php', array('order_id' => $order->get_id())); ?>
      </div>
    </div>

    <!-- Colonne droite (35%) — Récapitulatif + Adresse -->
    <div class="thankyou-sidebar">

      <div class="thankyou-card">
        <h2><?php esc_html_e('Votre commande', 'woocommerce'); ?></h2>
        <ul class="thankyou-overview">
          <li>
            <span><?php esc_html_e('Commande', 'woocommerce'); ?></span>
            <strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
          </li>
          <li>
            <span><?php esc_html_e('Date', 'woocommerce'); ?></span>
            <strong><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
          </li>
          <li>
            <span><?php esc_html_e('Email', 'woocommerce'); ?></span>
            <strong><?php echo esc_html($order->get_billing_email()); ?></strong>
          </li>
          <li class="thankyou-overview__total">
            <span><?php esc_html_e('Total', 'woocommerce'); ?></span>
            <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
          </li>
          <li>
            <span><?php esc_html_e('Paiement', 'woocommerce'); ?></span>
            <strong><?php echo esc_html($order->get_payment_method_title()); ?></strong>
          </li>
        </ul>
      </div>

      <?php if ($order->get_formatted_shipping_address() || $order->get_formatted_billing_address()) : ?>
      <div class="thankyou-card">
        <?php wc_get_template('order/order-details-customer.php', array('order' => $order)); ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- CTA retour boutique -->
  <div class="thankyou-cta">
    <a href="<?php echo esc_url(home_url('/nos-creations/')); ?>" class="thankyou-cta-btn">
      ← Continuer à explorer nos créations
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
