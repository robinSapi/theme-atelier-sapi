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
    $name = sanitize_text_field($_POST['fullname'] ?? '');
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

      // Projet Robin (si existant)
      $robin_project = sanitize_textarea_field($_POST['robin_project'] ?? '');
      if (!empty($robin_project)) {
        $body .= "Projet du client:\n$robin_project\n\n";
      }

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

<section class="contact-hero" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/07/Charlie-Bandeau-2.jpg');">
  <div class="contact-hero-overlay"></div>
  <div class="contact-hero-content">
    <h1>Parlons de votre projet</h1>
    <p>Une question, une envie, un projet sur mesure — Robin vous répond personnellement.</p>
  </div>
</section>

<section class="contact-main">
  <div class="contact-main-grid">

    <!-- Colonne gauche — Infos -->
    <div class="contact-info">
      <h2 class="contact-info-title">Un projet, une question ?</h2>
      <p class="contact-info-text">Chaque luminaire a son histoire, et j'aime les entendre. Que ce soit pour une création sur mesure, un conseil personnalisé ou simplement pour discuter de votre projet d'éclairage, n'hésitez pas.</p>

      <div class="contact-cards">
        <a href="mailto:contact@atelier-sapi.fr" class="contact-card">
          <span class="contact-card-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="M22 4L12 13L2 4"/>
            </svg>
          </span>
          <span class="contact-card-content">
            <span class="contact-card-label">Email</span>
            <span class="contact-card-value">contact@atelier-sapi.fr</span>
          </span>
        </a>

        <a href="tel:+33680435585" class="contact-card">
          <span class="contact-card-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </span>
          <span class="contact-card-content">
            <span class="contact-card-label">Téléphone</span>
            <span class="contact-card-value">06 80 43 55 85</span>
          </span>
        </a>

        <div class="contact-card contact-card--atelier">
          <div class="contact-card-atelier-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d381.97568401845297!2d4.8402947801140135!3d45.81651268781072!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47f495937d942563%3A0xa6e8cc815b5b180d!2sAtelier%20S%C3%A2pi!5e1!3m2!1sfr!2sfr!4v1774866581452!5m2!1sfr!2sfr" width="100%" height="180" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <span class="contact-card-content">
            <span class="contact-card-label">L'atelier — sur rendez-vous</span>
            <span class="contact-card-value"><a href="https://maps.app.goo.gl/vt9whr9AN9At9cMF8" target="_blank" rel="noopener noreferrer">Lyon, France</a></span>
            <span class="contact-card-sub">Lun&ndash;Ven : 9h&ndash;18h &middot; Sam : sur rendez-vous</span>
            <span class="contact-card-sub"><a href="tel:+33680435585">Appelez pour prendre rendez-vous</a></span>
          </span>
        </div>
      </div>
    </div>

    <!-- Colonne droite — Formulaire -->
    <div class="contact-form-wrapper">
      <h2 class="contact-form-title">Votre message</h2>

      <div class="contact-form-card">
        <?php if ($form_success) : ?>
          <div class="form-message form-message--success">
            <p><strong>Message envoyé !</strong></p>
            <p>Merci pour votre message. Je vous répondrai dans les plus brefs délais.</p>
          </div>
        <?php else : ?>

          <?php if ($form_error) : ?>
            <div class="form-message form-message--error">
              <p><?php echo esc_html($form_error); ?></p>
            </div>
          <?php endif; ?>

          <form action="" method="post">
            <?php wp_nonce_field('sapi_contact_form', 'sapi_contact_nonce'); ?>

            <!-- Honeypot anti-spam (hidden field) -->
            <div style="display: none;" aria-hidden="true">
              <label for="website">Ne pas remplir</label>
              <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <label for="contact-name">Nom</label>
            <input id="contact-name" type="text" name="fullname" required placeholder="Votre nom" value="<?php echo esc_attr($_POST['fullname'] ?? ''); ?>">

            <label for="contact-email">Email</label>
            <input id="contact-email" type="email" name="email" required placeholder="votre@email.fr" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">

            <label for="contact-message">Message</label>

            <!-- Bandeau projet Robin (rempli par JS si projet existant) -->
            <div id="robin-contact-project" style="display:none;"></div>
            <input type="hidden" name="robin_project" id="robin-contact-project-data" value="">

            <textarea id="contact-message" name="message" rows="6" required placeholder="Décrivez votre projet, posez votre question..."><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>

            <button type="submit">Envoyer le message</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>

<?php
get_footer();
