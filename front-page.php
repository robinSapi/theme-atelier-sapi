<?php
get_header();

$hero_images = [
  'https://atelier-sapi.fr/wp-content/uploads/2025/03/IMG_8778.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/12/Bandeau-Robin-marche-de-noel-2.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/12/Vue-generale-optimisee-2-e1765284286687.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/11/A7404578.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/10/Large-2.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg',
  'https://atelier-sapi.fr/wp-content/uploads/2025/09/IMG_9752.jpg',
];

$gallery_images = [
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/IMG_9752.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/04/IMG_5711.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/03/IMG_8441.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/05/IMG_5811.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/03/IMG_8749.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/06/A7404460.jpg',
  'https://www.atelier-sapi.fr/wp-content/uploads/2025/04/A7404579-e1751893880524.jpg',
];
?>

<section class="hero" data-hero-images='<?php echo wp_json_encode($hero_images); ?>'>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <a class="hero-card" href="https://atelier-sapi.fr/nos-creations/carte-cadeau/">
      Offrez une carte cadeau ! 🎁
    </a>
    <div class="hero-text">
      <p class="hero-kicker">Luminaire en bois - Atelier Sâpi</p>
      <h1>Découvrez les luminaires en bois de Robin,<br>fabriqués avec passion à la commande</h1>
    </div>
  </div>
</section>

<section class="assurance-strip">
  <div class="assurance-item">
    <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-assembly.svg" alt="Assemblage guidé">
    <p>Assemblage guidé<br>et ludique</p>
  </div>
  <div class="assurance-item">
    <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-french.svg" alt="Fabrication artisanale">
    <p>Fabrication artisanale<br>dans le Rhône</p>
  </div>
  <div class="assurance-item">
    <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-guarantee.svg" alt="Design unique">
    <p>Design unique et<br>produits garantis</p>
  </div>
  <div class="assurance-item">
    <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-pantone.svg" alt="Adaptable">
    <p>Adaptable selon<br>vos envies</p>
  </div>
</section>

<section class="atelier-selection">
  <h2>La sélection de l'atelier</h2>
  <div class="selection-grid">
    <a class="selection-card" href="https://atelier-sapi.fr/nos-suspensions/" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/04/A7404579-e1751893880524.jpg');">
      <span>Nos suspensions</span>
    </a>
    <a class="selection-card" href="https://atelier-sapi.fr/nos-lampadaires/" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/07/Bandeau-Robin.jpg');">
      <span>Nos lampadaires</span>
    </a>
    <a class="selection-card" href="https://atelier-sapi.fr/nos-appliques/" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/04/A7404363.jpg');">
      <span>Nos appliques</span>
    </a>
    <a class="selection-card" href="https://atelier-sapi.fr/nos-lampes-a-poser/" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/03/IMG_8749.jpg');">
      <span>Nos lampes à poser</span>
    </a>
  </div>
</section>

<section class="artisan-highlight">
  <div class="artisan-media" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/07/Bandeau-Robin.jpg');"></div>
  <div class="artisan-copy">
    <h2>Lumière d'artisan</h2>
    <h3>Découvrez Robin, artisan au cœur de la lumière</h3>
    <p>Chaque luminaire Sâpi est façonné à la main avec passion, alliant techniques traditionnelles et innovation. Du choix des essences de bois à la finition, chaque détail est pensé pour sublimer vos espaces dans une logique de commerce raisonné.</p>
    <a class="button" href="https://atelier-sapi.fr/lumiere-dartisan/">En savoir +</a>
  </div>
</section>

<section class="star-product">
  <div class="star-copy">
    <h2>Olivia La gardiena</h2>
    <h3>La star de l'atelier</h3>
    <p>Notre suspension monumentale inspirée de la nature. L'équilibre parfait entre l'éclairage et la décoration. Disponible en 4 tailles et 2 essences de bois !</p>
    <a class="button" href="https://atelier-sapi.fr/nos-creations/olivia-la-gardiena/">Découvrir Olivia</a>
  </div>
  <div class="star-media">
    <img src="https://atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg" alt="Olivia La gardiena">
  </div>
</section>

<section class="advice-section" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/03/Sapi-header_idees.jpg');">
  <div class="advice-overlay"></div>
  <div class="advice-content">
    <h2>Conseils éclairés</h2>
    <h3>Astuces et inspirations</h3>
    <p>L’éclairage transforme un espace : il crée l’ambiance, met en valeur les volumes, et influence même notre bien-être. Que ce soit pour un salon cosy, une cuisine fonctionnelle, ou une chambre apaisante, chaque pièce mérite une lumière adaptée.</p>
    <a class="button button-outline" href="https://atelier-sapi.fr/conseils-eclaires/">En savoir +</a>
  </div>
</section>

<section class="inspiration-gallery">
  <div class="gallery-grid">
    <?php foreach ($gallery_images as $image_url) : ?>
      <a class="gallery-item" href="https://atelier-sapi.fr/nos-creations/" style="background-image: url('<?php echo esc_url($image_url); ?>');"></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="newsletter" style="background-image: url('https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2024/12/trame_lampe.png');">
  <div class="newsletter-inner">
    <div class="newsletter-copy">
      <h2 class="newsletter-heading">Restez informées !</h2>
      <div class="newsletter-title">
        <span class="divider"></span>
        <h2>Et notre prochain coup d'éclat ?</h2>
        <span class="divider"></span>
      </div>
      <h3>Découvrez régulièrement nos nouveautés lumineuses !</h3>
    </div>
    <form class="newsletter-form" action="#" method="post">
      <label for="newsletter-email">Renseignez votre email</label>
      <input id="newsletter-email" type="email" name="email" placeholder="E-mail" required>
      <button type="submit">S’inscrire à la newsletter 💡</button>
      <p class="newsletter-note">Votre adresse e-mail sera utilisée uniquement pour vous envoyer des actualités, vous pouvez vous désinscrire à tout moment. Consultez notre politique de confidentialité <a href="https://atelier-sapi.fr/politique-de-confidentialite/">ici</a>.</p>
    </form>
  </div>
</section>

<?php
get_footer();
