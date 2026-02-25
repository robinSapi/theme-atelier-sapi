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
    <div class="advice-tip" data-tip="<?php echo esc_attr($i); ?>">
      <div class="advice-tip-flipper">
        <!-- Face avant -->
        <div class="advice-tip-front">
          <div class="advice-tip-image" style="background-image: url('<?php echo esc_url($tip['image']); ?>');">
            <div class="advice-tip-overlay"></div>
            <div class="advice-tip-content">
              <span class="advice-tip-number"><?php echo esc_html($tip['number']); ?></span>
              <h2><?php echo esc_html($tip['title']); ?></h2>
              <button class="advice-tip-btn">Voir le conseil</button>
            </div>
          </div>
        </div>
        <!-- Face arrière (mobile flip) -->
        <div class="advice-tip-back">
          <div class="advice-tip-back-inner">
            <p class="advice-tip-back-text"><?php echo esc_html($tip['summary']); ?></p>
            <div class="advice-tip-back-buttons">
              <button class="advice-tip-back-close" aria-label="Fermer">&times;</button>
              <button class="advice-tip-back-more" data-tip="<?php echo esc_attr($i); ?>">En savoir plus</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Panneau citation / détail (3 colonnes, hidden par défaut) -->
    <div class="advice-quote-panel" aria-hidden="true">
      <div class="advice-quote-panel-inner">
        <!-- Vue citation -->
        <div class="advice-panel-quote">
          <p class="advice-quote-text"></p>
          <div class="advice-quote-buttons">
            <button class="advice-quote-close" aria-label="Fermer">&times;</button>
            <button class="advice-quote-more">En savoir plus</button>
          </div>
        </div>
        <!-- Vue détail (remplace la citation) -->
        <div class="advice-panel-detail" aria-hidden="true">
          <div class="advice-panel-detail-body"></div>
          <div class="advice-panel-detail-buttons">
            <button class="advice-quote-close" aria-label="Fermer">&times;</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Overlay plein écran mobile -->
<div class="advice-overlay" aria-hidden="true">
  <div class="advice-overlay-inner">
    <button class="advice-overlay-close" aria-label="Fermer">&times;</button>
    <div class="advice-overlay-body"></div>
  </div>
</div>

<section class="advice-outro">
  <p>Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</p>
  <span class="advice-outro-signature">Robin, créateur à l'Atelier Sâpi</span>
</section>

<script>
(function() {
  'use strict';

  var grid = document.querySelector('.advice-tips-grid');
  var panel = document.querySelector('.advice-quote-panel');
  var quoteText = panel.querySelector('.advice-quote-text');
  var panelQuote = panel.querySelector('.advice-panel-quote');
  var panelDetail = panel.querySelector('.advice-panel-detail');
  var panelDetailBody = panel.querySelector('.advice-panel-detail-body');
  var activeTipIndex = null;

  /* Données des citations et contenu */
  var summaries = <?php echo json_encode(array_map(function($t) { return $t['summary']; }, $tips)); ?>;
  var contents = <?php echo json_encode(array_map(function($t) { return $t['content']; }, $tips)); ?>;

  function isMobile() {
    return window.innerWidth <= 768;
  }

  /* ========== DESKTOP : slide + panneau ========== */
  function showQuoteView() {
    panelQuote.style.display = 'flex';
    panelQuote.setAttribute('aria-hidden', 'false');
    panelDetail.style.display = 'none';
    panelDetail.setAttribute('aria-hidden', 'true');
  }

  function showDetailView() {
    panelQuote.style.display = 'none';
    panelQuote.setAttribute('aria-hidden', 'true');
    panelDetail.style.display = 'flex';
    panelDetail.setAttribute('aria-hidden', 'false');
  }

  function closeAllDesktop() {
    grid.classList.remove('is-expanded');
    panel.setAttribute('aria-hidden', 'true');
    document.querySelectorAll('.advice-tip').forEach(function(t) {
      t.classList.remove('is-active');
    });
    showQuoteView();
    activeTipIndex = null;
  }

  /* ========== MOBILE : flip card + overlay ========== */
  var overlay = document.querySelector('.advice-overlay');
  var overlayBody = document.querySelector('.advice-overlay-body');
  var overlayClose = document.querySelector('.advice-overlay-close');

  function closeFlip(tip) {
    tip.classList.remove('is-flipped');
  }

  function openOverlay(tipIndex) {
    overlayBody.innerHTML = contents[tipIndex];
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeOverlay() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  /* ========== EVENT LISTENERS ========== */

  /* Clic "Voir le conseil" */
  document.querySelectorAll('.advice-tip-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      var tipIndex = tip.getAttribute('data-tip');

      if (isMobile()) {
        /* Mobile : flip la card */
        document.querySelectorAll('.advice-tip').forEach(function(t) {
          if (t !== tip) closeFlip(t);
        });
        var backFace = tip.querySelector('.advice-tip-back');
        backFace.classList.add('no-touch');
        tip.classList.add('is-flipped');
        setTimeout(function() {
          backFace.classList.remove('no-touch');
        }, 700);
      } else {
        /* Desktop : slide + panneau */
        document.querySelectorAll('.advice-tip').forEach(function(t) {
          t.classList.remove('is-active');
        });
        tip.classList.add('is-active');
        activeTipIndex = tipIndex;
        quoteText.textContent = summaries[tipIndex];
        showQuoteView();
        grid.classList.add('is-expanded');
        panel.setAttribute('aria-hidden', 'false');
      }
    });
  });

  /* Boutons croix desktop (panneau) */
  panel.querySelectorAll('.advice-quote-close').forEach(function(btn) {
    btn.addEventListener('click', closeAllDesktop);
  });

  /* Bouton "En savoir plus" desktop (panneau) */
  panel.querySelector('.advice-quote-more').addEventListener('click', function() {
    if (activeTipIndex === null) return;
    panelDetailBody.innerHTML = contents[activeTipIndex];
    showDetailView();
  });

  /* Mobile : boutons croix (face arrière) */
  document.querySelectorAll('.advice-tip-back-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      closeFlip(tip);
    });
  });

  /* Mobile : bouton "En savoir plus" → ouvre overlay plein écran */
  document.querySelectorAll('.advice-tip-back-more').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tipIndex = this.getAttribute('data-tip');
      openOverlay(tipIndex);
    });
  });

  /* Overlay : bouton fermer */
  if (overlayClose) {
    overlayClose.addEventListener('click', closeOverlay);
  }

  /* Overlay : fermer en cliquant sur le fond */
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeOverlay();
    });
  }

})();
</script>

<?php
get_footer();
