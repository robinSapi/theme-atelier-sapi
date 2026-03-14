<?php
/**
 * Template partial — Card "Conseil personnalisé de Robin"
 *
 * @param string $prefix  ID prefix: 'conseils', 'selection' ou 'surmesure'
 */
function sapi_robin_conseil_card( $prefix = 'conseils' ) {
  $prefix = esc_attr( $prefix );
  ?>
  <div class="robin-conseil" id="<?php echo $prefix; ?>-perso-intro" style="display:none">
    <div class="robin-conseil__header">
      <span class="robin-conseil__badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        Conseil personnalis&eacute;
      </span>
      <div class="robin-conseil__chips" id="<?php echo $prefix; ?>-conseil-chips">
      </div>
    </div>
    <div class="robin-conseil__body">
      <div class="robin-conseil__quote">&ldquo;</div>
      <p class="robin-conseil__text" id="<?php echo $prefix; ?>-perso-text"></p>
      <span class="robin-conseil__signature">&mdash; Robin, votre artisan</span>
    </div>
    <div class="robin-conseil__products" id="<?php echo $prefix; ?>-products-grid">
      <h3 class="robin-conseil__products-title">La proposition de Robin pour votre projet</h3>
    </div>
    <div class="robin-conseil__actions">
      <button type="button" class="robin-conseil__reply" id="<?php echo $prefix; ?>-reply-btn">R&eacute;pondre &agrave; Robin</button>
    </div>
    <div class="robin-conseil__chat" id="<?php echo $prefix; ?>-chat" style="display:none">
      <div class="robin-conseil__messages" id="<?php echo $prefix; ?>-messages"></div>
      <div class="robin-conseil__input-row">
        <input type="text" class="robin-conseil__input" id="<?php echo $prefix; ?>-input"
               placeholder="Votre message &agrave; Robin&hellip;"
               aria-label="<?php esc_attr_e('Votre message', 'theme-sapi-maison'); ?>">
        <button type="button" class="robin-conseil__send" id="<?php echo $prefix; ?>-send">Envoyer</button>
      </div>
    </div>
  </div>
  <?php
}
