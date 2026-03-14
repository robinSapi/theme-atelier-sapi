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
      <h3 class="robin-conseil__products-title">Ma s&eacute;lection pour votre projet</h3>
    </div>
    <div class="robin-conseil__actions">
      <a href="/nos-creations/?filtre=ma-selection" class="robin-conseil__selection-btn" id="<?php echo $prefix; ?>-selection-btn" style="display:none">Voir ma s&eacute;lection personnalis&eacute;e</a>
      <button type="button" class="robin-conseil__contact-btn" id="<?php echo $prefix; ?>-contact-btn">Contacter Robin</button>
    </div>
    <div class="robin-conseil__contact-form" id="<?php echo $prefix; ?>-contact-form" style="display:none">
      <p class="robin-conseil__contact-intro">Robin vous recontactera rapidement&nbsp;:</p>
      <div class="robin-conseil__contact-fields">
        <div class="robin-conseil__contact-row">
          <input type="email" class="robin-conseil__contact-input" id="<?php echo $prefix; ?>-contact-email"
                 placeholder="E-mail *"
                 aria-label="<?php esc_attr_e('E-mail', 'theme-sapi-maison'); ?>" required>
          <input type="tel" class="robin-conseil__contact-input" id="<?php echo $prefix; ?>-contact-phone"
                 placeholder="T&eacute;l&eacute;phone"
                 aria-label="<?php esc_attr_e('Téléphone', 'theme-sapi-maison'); ?>">
        </div>
        <textarea class="robin-conseil__contact-input robin-conseil__contact-textarea" id="<?php echo $prefix; ?>-contact-msg"
                  placeholder="Message (facultatif)"
                  aria-label="<?php esc_attr_e('Message facultatif', 'theme-sapi-maison'); ?>" rows="2"></textarea>
        <button type="button" class="robin-conseil__contact-send" id="<?php echo $prefix; ?>-contact-send" disabled>Envoyer</button>
      </div>
      <p class="robin-conseil__contact-success" id="<?php echo $prefix; ?>-contact-success" style="display:none">
        Merci&nbsp;! Robin vous recontactera tr&egrave;s bient&ocirc;t.
      </p>
    </div>
  </div>
  <?php
}
