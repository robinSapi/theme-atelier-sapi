<?php
/*
Template Name: Contact
*/
get_header();
?>

<section class="contact-hero" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Bandeau-Rouge.jpg');">
  <div class="contact-hero-overlay"></div>
  <div class="contact-hero-content">
    <h1>Prise de contact</h1>
    <p>Personnalisation, questions, service après vente, dites nous ce qui vous traverse l'ampoule !</p>
  </div>
</section>

<section class="contact-body">
  <p>Vous n'avez pas trouvé le luminaire de vos rêves ? Vous avez un doute ou n'arrivez pas à faire votre choix ? Vous avez un problème avec votre commande ? Contactez nous, nous répondrons le plus vite possible ! 🚀</p>
  <div class="contact-actions">
    <a class="button" href="mailto:contact@atelier-sapi.fr">ENVOYER UN MAIL ✉️</a>
    <a class="button button-outline" href="tel:0680435585">PASSER UN COUP DE FIL 📞</a>
    <a class="button" href="#formulaire">COMPLETER LE FORMULAIRE 📋</a>
  </div>
</section>

<section id="formulaire" class="contact-form">
  <h2>Contactez-nous</h2>
  <form action="#" method="post">
    <label for="contact-name">Nom</label>
    <input id="contact-name" type="text" name="name" required>

    <label for="contact-email">Email</label>
    <input id="contact-email" type="email" name="email" required>

    <label for="contact-message">Message</label>
    <textarea id="contact-message" name="message" rows="6" required></textarea>

    <button type="submit">Envoyer</button>
  </form>
</section>

<?php
get_footer();
