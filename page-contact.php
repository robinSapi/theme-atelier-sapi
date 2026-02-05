<?php
/*
Template Name: Contact
*/

// Handle form submission
$form_submitted = false;
$form_success = false;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sapi_contact_nonce'])) {
  // Verify nonce
  if (!wp_verify_nonce($_POST['sapi_contact_nonce'], 'sapi_contact_form')) {
    $form_error = 'Erreur de sécurité. Veuillez réessayer.';
  }
  // Honeypot check (anti-spam)
  elseif (!empty($_POST['website'])) {
    $form_error = 'Spam détecté.';
  }
  else {
    $form_submitted = true;

    // Sanitize inputs
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    // Validate
    if (empty($name) || empty($email) || empty($message)) {
      $form_error = 'Veuillez remplir tous les champs.';
    } elseif (!is_email($email)) {
      $form_error = 'Adresse email invalide.';
    } else {
      // Prepare email
      $to = 'contact@atelier-sapi.fr';
      $subject = '[Atelier Sapi] Nouveau message de ' . $name;
      $body = "Nom: $name\n";
      $body .= "Email: $email\n\n";
      $body .= "Message:\n$message";

      $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
      );

      // Send email
      if (wp_mail($to, $subject, $body, $headers)) {
        $form_success = true;
      } else {
        $form_error = 'Erreur lors de l\'envoi. Veuillez réessayer ou nous contacter directement par email.';
      }
    }
  }
}

get_header();
?>

<section class="contact-hero" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Bandeau-Rouge.jpg');">
  <div class="contact-hero-overlay"></div>
  <div class="contact-hero-content">
    <h1>Prise de contact</h1>
    <p>Personnalisation, questions, service après vente, dites-nous ce qui vous traverse l'ampoule !</p>
  </div>
</section>

<section class="contact-body">
  <p>Vous n'avez pas trouvé le luminaire de vos rêves ? Vous avez un doute ou n'arrivez pas à faire votre choix ? Vous avez un problème avec votre commande ? Contactez-nous, nous répondrons le plus vite possible ! 🚀</p>
  <div class="contact-actions">
    <a class="button" href="mailto:contact@atelier-sapi.fr">ENVOYER UN MAIL ✉️</a>
    <a class="button button-outline" href="tel:0680435585">PASSER UN COUP DE FIL 📞</a>
    <a class="button" href="#formulaire">COMPLETER LE FORMULAIRE 📋</a>
  </div>
</section>

<section id="formulaire" class="contact-form">
  <h2>Contactez-nous</h2>

  <?php if ($form_success) : ?>
    <div class="form-message form-message--success">
      <p><strong>Message envoyé !</strong></p>
      <p>Merci pour votre message. Nous vous répondrons dans les plus brefs délais.</p>
    </div>
  <?php else : ?>

    <?php if ($form_error) : ?>
      <div class="form-message form-message--error">
        <p><?php echo esc_html($form_error); ?></p>
      </div>
    <?php endif; ?>

    <form action="<?php echo esc_url(get_permalink()); ?>#formulaire" method="post">
      <?php wp_nonce_field('sapi_contact_form', 'sapi_contact_nonce'); ?>

      <!-- Honeypot anti-spam (hidden field) -->
      <div style="display: none;" aria-hidden="true">
        <label for="website">Ne pas remplir</label>
        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
      </div>

      <label for="contact-name">Nom</label>
      <input id="contact-name" type="text" name="name" required value="<?php echo esc_attr($_POST['name'] ?? ''); ?>">

      <label for="contact-email">Email</label>
      <input id="contact-email" type="email" name="email" required value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">

      <label for="contact-message">Message</label>
      <textarea id="contact-message" name="message" rows="6" required><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>

      <button type="submit">Envoyer</button>
    </form>
  <?php endif; ?>
</section>

<?php
get_footer();
