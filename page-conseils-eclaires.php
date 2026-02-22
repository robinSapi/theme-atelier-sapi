<?php
/*
Template Name: Conseils eclaires
*/
get_header();

$upload_url = esc_url(home_url("/wp-content/uploads/"));

$tips = [
  [
    'number'  => '01',
    'title'   => 'Décoration ou fonctionnalité',
    'image'   => $upload_url . '2025/03/sapi__deco_fonction_1.jpg',
    'summary' => 'Éclairage direct pour travailler, lumière douce pour l\'ambiance — ou un mix des deux !',
    'content' => '
      <h3>Éclairage fonctionnel pour travailler &amp; cuisiner</h3>
      <p>Il vous faut un éclairage direct et efficace. Nos modèles comme Dalida ou Arthus sont parfaits pour ça : ils laissent bien passer la lumière sans vous éblouir. Parce qu\'après tout, c\'est mieux de voir ce qu\'on fait plutôt que de deviner où est la lame du couteau ou la touche "Envoyer" d\'un e-mail mal inspiré.</p>
      <h3>Éclairage d\'ambiance pour une atmosphère chaleureuse</h3>
      <p>Pour une pièce dédiée à la détente et aux loisirs, misez sur une lumière douce et enveloppante. Nos luminaires Olivia et Alban habillent l\'ampoule avec élégance, histoire que votre regard ne soit pas agressé par un éclairage trop frontal. Parfait pour un moment cosy, sans avoir l\'impression d\'être en plein interrogatoire.</p>
      <h3>Un équilibre entre les deux ?</h3>
      <p>Et si vous êtes du genre à ne pas vouloir choisir entre les deux, nos modèles Suze et Gaston font le grand écart avec style : une ampoule partiellement entourée de bois pour un équilibre parfait entre clarté et ambiance feutrée. Idéal pour ceux qui hésitent entre travailler et chiller… ou faire semblant de bosser en profitant de la bonne lumière.</p>
    '
  ],
  [
    'number'  => '02',
    'title'   => 'Ampoule, puissance et couleur',
    'image'   => $upload_url . '2025/07/IMG_9065.jpeg',
    'summary' => 'Ampoules LED à filament pour des jeux d\'ombres, 8W en E27, et température chaude pour le cosy.',
    'content' => '
      <h3>Le type d\'ampoule</h3>
      <p>Les ampoules à filament LED sont les reines du bal ! Elles créent ces magnifiques jeux d\'ombres envoûtants qui transforment votre salon en scène de film d\'auteur. À l\'inverse, une LED opaque, c\'est un peu comme un jour de pluie : tout devient tristement uniforme.</p>
      <h3>Bien choisir l\'intensité lumineuse</h3>
      <p>Douilles E27 : le grand classique, compatibles avec nos suspensions. Puissance : une LED 8W (1000 lumens) suffit pour salon, cuisine ou chambre. Moins ? Préparez-vous à jouer à cache-cache avec vos meubles !</p>
      <h3>Choisir la bonne température de couleur</h3>
      <p>Chaud (jaune/orangé) : ambiance détente, idéale pour salon et chambre. Neutre : parfaite pour cuisiner, travailler ou se préparer. Froid : adaptée aux bureaux et caves (mais évitez l\'effet entrepôt !).</p>
      <h3>La forme de l\'ampoule</h3>
      <p>Une ampoule visible doit être belle ! Optez pour un modèle Edison ou Globe pour une touche vintage. Par contre, si elle doit se glisser discrètement dans un luminaire en cage comme Olivia, Vincent ou Alban (taille mini), une ampoule poire classique sera votre meilleure alliée.</p>
    '
  ],
  [
    'number'  => '03',
    'title'   => 'Dimensions et volume de l\'espace',
    'image'   => $upload_url . '2025/03/sapi__dimension_3.jpg',
    'summary' => 'Diamètre = 20% de la longueur de la pièce. Hauteur : attention aux passages !',
    'content' => '
      <p>Voici nos astuces pour éviter un faux pas lumineux.</p>
      <h3>Le diamètre : l\'équilibre entre taille et style</h3>
      <p>Un luminaire doit être proportionnel à la pièce, ni perdu, ni envahissant. Astuce : prenez 20 % de la plus grande longueur de la pièce. Par exemple, pour un salon de 6m de long, un luminaire de 1m20 de diamètre est parfait. Pour un effet wahou, mixez les tailles !</p>
      <h3>La hauteur : évitez l\'effet "obstacle suspendu"</h3>
      <p>Sous un passage : optez pour un luminaire de moins de 30 cm de haut. Trop bas = têtes cognées. Trop collé au plafond = effet sticker raté !</p>
      <p>En revanche, au-dessus d\'une table, d\'un îlot de cuisine, d\'un lit ou d\'une table basse, tout est permis ! Vous pouvez opter pour un modèle plus haut, suspendu élégamment, sans risque de bosse sur le front. En résumé : un luminaire bien dimensionné, c\'est un intérieur harmonieux et un éclairage au top.</p>
    '
  ],
  [
    'number'  => '04',
    'title'   => 'Suspension, lampadaire, applique ou lampe à poser ?',
    'image'   => $upload_url . '2025/07/Claudine.jpg',
    'summary' => 'Suspensions et spots pour le travail, appliques et lampadaires pour l\'ambiance.',
    'content' => '
      <h3>Le positionnement des luminaires</h3>
      <p>Le positionnement de vos luminaires, c\'est un peu comme celui des meubles : si c\'est mal pensé, on se cogne dedans et on finit par râler. Alors autant bien réfléchir avant d\'installer votre éclairage !</p>
      <h3>Lumières de précision : travaillez sans plisser les yeux</h3>
      <p>Bureau, cuisine, atelier → privilégiez une lumière directe. Astuce : suspensions + spots encastrés = éclairage précis et stylé !</p>
      <h3>Lumières d\'ambiance : douceurs et détente</h3>
      <p>Salon, chambre, coin lecture → préférez appliques et lampadaires. Un halo cosy, zéro éblouissement… Parfait pour lire (ou scroller discrètement) !</p>
      <p>En résumé, un bon positionnement des luminaires, c\'est le secret d\'un intérieur bien éclairé et bien décoré. Parce qu\'après tout, voir clair, c\'est bien, mais avec style, c\'est encore mieux !</p>
    '
  ]
];
?>

<section class="advice-hero" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/03/Sapi-header_idees.jpg');">
  <div class="advice-hero-overlay"></div>
  <div class="advice-hero-content">
    <h1>Conseils éclairés</h1>
    <p>Suspensions ou lampadaire ? Quelle ampoule choisir ? Retrouvez ici les infos idéales pour une décoration réussie !</p>
  </div>
</section>

<section class="advice-tips-section">
  <div class="advice-tips-grid">
    <?php foreach ($tips as $i => $tip) : ?>
    <div class="advice-tip" data-tip="<?php echo esc_attr($i); ?>" data-state="initial">
      <div class="advice-tip-image" style="background-image: url('<?php echo esc_url($tip['image']); ?>');">
        <div class="advice-tip-overlay"></div>
        <div class="advice-tip-content">
          <span class="advice-tip-number"><?php echo esc_html($tip['number']); ?></span>
          <h2><?php echo esc_html($tip['title']); ?></h2>
          <div class="advice-tip-quote" aria-hidden="true">
            <p><?php echo esc_html($tip['summary']); ?></p>
          </div>
          <button class="advice-tip-btn">Voir le conseil</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($tips as $i => $tip) : ?>
  <div class="advice-detail" data-tip="<?php echo esc_attr($i); ?>" aria-hidden="true">
    <div class="advice-detail-inner">
      <div class="advice-detail-header">
        <span class="advice-tip-number"><?php echo esc_html($tip['number']); ?></span>
        <h2><?php echo esc_html($tip['title']); ?></h2>
      </div>
      <div class="advice-detail-body">
        <?php echo $tip['content']; ?>
      </div>
      <button class="advice-btn-close">Fermer</button>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<section class="advice-outro">
  <p>Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</p>
</section>

<script>
(function() {
  'use strict';

  document.querySelectorAll('.advice-tip-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      var state = tip.getAttribute('data-state');
      var quote = tip.querySelector('.advice-tip-quote');

      if (state === 'initial') {
        /* État 1 → 2 : Montrer la citation */
        tip.setAttribute('data-state', 'quote');
        quote.setAttribute('aria-hidden', 'false');
        this.textContent = 'En savoir plus';

      } else if (state === 'quote') {
        /* État 2 → 3 : Ouvrir l'accordéon détail */
        var tipIndex = tip.getAttribute('data-tip');
        var detail = document.querySelector('.advice-detail[data-tip="' + tipIndex + '"]');

        /* Fermer les autres détails ouverts */
        document.querySelectorAll('.advice-detail').forEach(function(d) {
          if (d !== detail) {
            d.classList.remove('is-open');
            d.setAttribute('aria-hidden', 'true');
          }
        });

        detail.classList.add('is-open');
        detail.setAttribute('aria-hidden', 'false');

        setTimeout(function() {
          detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 50);
      }
    });
  });

  document.querySelectorAll('.advice-btn-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var detail = this.closest('.advice-detail');
      var tipIndex = detail.getAttribute('data-tip');
      var tip = document.querySelector('.advice-tip[data-tip="' + tipIndex + '"]');

      detail.classList.remove('is-open');
      detail.setAttribute('aria-hidden', 'true');

      setTimeout(function() {
        tip.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 50);
    });
  });
})();
</script>

<?php
get_footer();
