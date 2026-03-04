<?php
/*
Template Name: Guide Luminaire
*/
get_header();

$shop_url = class_exists('WooCommerce') ? esc_url(wc_get_page_permalink('shop')) : '/nos-creations/';
$ajax_nonce = wp_create_nonce('sapi-guide-results');

// ─── Steps data (V2 — dynamic flow) ───
$guide_steps = [
  [
    'id'         => 'piece',
    'question'   => 'Dans quelle pièce sera-t-il installé ?',
    'visibility' => 'always',
    'choices'    => [
      ['label' => 'Cuisine',              'slug' => 'cuisine',  'icon' => 'dining'],
      ['label' => 'Bureau / Atelier',    'slug' => 'bureau',   'icon' => 'monitor'],
      ['label' => 'Salon / Salle à manger', 'slug' => 'salon', 'icon' => 'sofa'],
      ['label' => 'Chambre',             'slug' => 'chambre',  'icon' => 'bed'],
      ['label' => 'Entrée / Couloir',    'slug' => 'entree',   'icon' => 'door'],
      ['label' => 'Cage d\'escalier',    'slug' => 'escalier', 'icon' => 'stairs'],
    ],
  ],
  [
    'id'         => 'taille',
    'question'   => 'Quelle est la taille de votre pièce ?',
    'visibility' => 'always',
    'choices'    => [
      ['label' => 'Petite (< 10 m²)',   'slug' => 'petite',  'icon' => 'square-sm'],
      ['label' => 'Moyenne (10–20 m²)',  'slug' => 'moyenne', 'icon' => 'square-md'],
      ['label' => 'Grande (> 20 m²)',    'slug' => 'grande',  'icon' => 'square-lg'],
    ],
  ],
  [
    'id'         => 'sortie',
    'question'   => 'Où se trouve votre sortie électrique ?',
    'visibility' => 'always',
    'choices'    => [
      ['label' => 'Au plafond',               'slug' => 'plafond',       'icon' => 'ceiling-plug'],
      ['label' => 'Au mur',                   'slug' => 'mur',           'icon' => 'wall-plug'],
      ['label' => 'Sur prise classique 230V', 'slug' => 'pas-de-sortie', 'icon' => 'no-plug'],
      ['label' => 'Je ne sais pas',           'slug' => 'ne-sais-pas',   'icon' => 'question-mark'],
    ],
  ],
  [
    'id'         => 'hauteur',
    'question'   => 'Quelle est votre hauteur sous-plafond ?',
    'visibility' => ['sortie' => ['plafond']],
    'choices'    => [
      ['label' => 'Standard (< 2,50 m)',    'slug' => 'standard',    'icon' => 'ceiling-low'],
      ['label' => 'Confortable (2,50–3 m)', 'slug' => 'confortable', 'icon' => 'ceiling-mid'],
      ['label' => 'Haute (> 3 m)',          'slug' => 'haute',       'icon' => 'ceiling-high'],
    ],
  ],
  [
    'id'         => 'table',
    'question'   => 'Sera-t-il au-dessus d\'une table ou d\'un îlot ?',
    'visibility' => ['hauteur' => ['standard']],
    'choices'    => [
      ['label' => 'Oui', 'slug' => 'oui', 'icon' => 'table-yes'],
      ['label' => 'Non', 'slug' => 'non', 'icon' => 'table-no'],
    ],
  ],
  [
    'id'         => 'style',
    'question'   => 'Quel est le style de votre intérieur ?',
    'visibility' => 'always',
    'choices'    => [
      ['label' => 'Moderne, neuf, tons clairs',        'slug' => 'moderne', 'icon' => 'minimal'],
      ['label' => 'Ancien, pierre, bois, tons chauds', 'slug' => 'ancien',  'icon' => 'organic'],
    ],
  ],
];

// ─── SVG icons map ───
$icons = [
  // Sortie électrique (new)
  'ceiling-plug' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18"/><path d="M12 3v4"/><circle cx="12" cy="10" r="3"/><path d="M10 13v2m4-2v2"/></svg>',
  'wall-plug'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3v18"/><rect x="8" y="8" width="8" height="8" rx="2"/><path d="M11 11v2m2-2v2"/></svg>',
  'no-plug'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="12" height="12" rx="2"/><path d="M10 10v4m4-4v4"/></svg>',
  'question-mark' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
  'table-yes'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M6 12v6m12-6v6"/><path d="M12 3v4"/><path d="M9 7h6l-1 5H10L9 7z"/></svg>',
  'table-no'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v6"/><path d="M9 9h6l-1 4h-4l-1-4z"/><path d="M3 21h18"/><path d="M7 21v-4m10 4v-4"/></svg>',
  // Hauteur plafond (existing)
  'ceiling-low'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18"/><path d="M12 3v6"/><path d="M9 9h6l-1 4h-4l-1-4z"/><path d="M3 21h18"/><path d="M7 21v-4m10 4v-4"/></svg>',
  'ceiling-mid'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18"/><path d="M12 2v8"/><path d="M9 10h6l-1 4h-4l-1-4z"/><path d="M3 22h18"/><path d="M7 22v-4m10 4v-4"/></svg>',
  'ceiling-high' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 1h18"/><path d="M12 1v10"/><path d="M9 11h6l-1 4h-4l-1-4z"/><path d="M3 23h18"/><path d="M7 23v-4m10 4v-4"/></svg>',
  // Taille pièce (existing)
  'square-sm'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
  'square-md'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1"/></svg>',
  'square-lg'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>',
  // Pièce (existing)
  'sofa'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
  'dining'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"/><path d="M20 12v8h-2"/><path d="M4 12v8h2"/><path d="M12 2a4 4 0 0 0-4 4v6h8V6a4 4 0 0 0-4-4z"/><path d="M12 12v4"/></svg>',
  'bed'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'monitor'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
  'door'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
  'stairs'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21h4v-4h4v-4h4v-4h4V5"/></svg>',
  // Style (existing)
  'minimal'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><line x1="12" y1="8" x2="12" y2="16"/></svg>',
  'organic'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-3 0-6 2-7 5s0 7 2 9 6 3 9 2 5-4 5-7-1-6-3-8-3-1-6-1z"/><circle cx="12" cy="13" r="2.5"/></svg>',
];
?>

<div class="guide-luminaire-page">

  <!-- ─── Intro Screen ─── -->
  <section class="guide-intro" id="guide-intro">
    <div class="guide-intro-inner">
      <p class="guide-intro-badge">Guide interactif</p>
      <h1 class="guide-intro-title">Trouvez votre luminaire idéal</h1>
      <p class="guide-intro-subtitle">Quelques questions pour vous guider vers la création parfaite pour votre intérieur</p>
      <button class="guide-intro-cta" id="guide-start-btn" type="button">
        Commencer
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
      <p class="guide-intro-duration">Environ 1 minute</p>
    </div>
  </section>

  <!-- ─── Quiz Section ─── -->
  <section class="guide-quiz-section" id="guide-quiz" aria-hidden="true">
    <div class="guide-steps-wrapper">
      <?php foreach ($guide_steps as $step) :
        $choice_count = count($step['choices']);
      ?>
        <div class="guide-step" data-step="<?php echo esc_attr($step['id']); ?>" role="group" aria-labelledby="guide-q-<?php echo esc_attr($step['id']); ?>">
          <div class="guide-step-inner">
            <p class="guide-step-number" aria-hidden="true" data-step-counter></p>
            <h2 class="guide-step-question" id="guide-q-<?php echo esc_attr($step['id']); ?>"><?php echo esc_html($step['question']); ?></h2>
            <div class="guide-choices-grid" data-count="<?php echo esc_attr($choice_count); ?>" role="list">
              <?php foreach ($step['choices'] as $choice) :
                $icon_key = $choice['icon'];
                $icon_svg = isset($icons[$icon_key]) ? $icons[$icon_key] : '';
              ?>
                <button class="guide-choice-card" role="listitem"
                        data-step="<?php echo esc_attr($step['id']); ?>"
                        data-slug="<?php echo esc_attr($choice['slug']); ?>"
                        data-label="<?php echo esc_attr($choice['label']); ?>"
                        type="button"
                        aria-label="<?php echo esc_attr($choice['label']); ?>">
                  <span class="guide-choice-icon" aria-hidden="true">
                    <?php echo $icon_svg; // SVGs are hardcoded above, safe ?>
                  </span>
                  <span class="guide-choice-label"><?php echo esc_html($choice['label']); ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Back button -->
    <button class="guide-back-btn" id="guide-back" type="button" aria-label="Retour à l'étape précédente" hidden>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Retour
    </button>

  </section>

  <!-- ─── Results Section ─── -->
  <section class="guide-results-section" id="guide-results" aria-hidden="true">
    <div class="guide-results-header">
      <div class="guide-results-tags" id="guide-results-tags"></div>
      <h2 class="guide-results-title">Notre recommandation pour vous</h2>
    </div>

    <!-- Loading with progressive steps -->
    <div class="guide-results-loading" id="guide-results-loading" aria-hidden="true">
      <svg class="guide-spinner" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg>
      <div class="guide-loading-steps">
        <p class="guide-loading-step is-active" data-load-step="1">Analyse de vos préférences...</p>
        <p class="guide-loading-step" data-load-step="2">Sélection dans notre catalogue...</p>
        <p class="guide-loading-step" data-load-step="3">Rédaction de votre conseil personnalisé...</p>
      </div>
    </div>

    <!-- AI Recommendation Text -->
    <div class="guide-ai-recommendation" id="guide-ai-text" style="display:none;">
      <span class="guide-ai-quote guide-ai-quote-open">&laquo;</span>
      <div class="guide-ai-text-content" id="guide-ai-text-content"></div>
      <span class="guide-ai-quote guide-ai-quote-close">&raquo;</span>
      <div class="guide-ai-signature">&mdash; Robin</div>
    </div>

    <!-- Products row: main + optional complement -->
    <div class="guide-result-products-row" id="guide-result-products-row" style="display:none;">

      <!-- Main product -->
      <div class="guide-result-product" id="guide-result-product-main">
        <div class="guide-result-badge">Notre recommandation</div>
        <div class="guide-result-image-wrap">
          <img class="guide-result-image" id="guide-result-image" src="" alt="" />
        </div>
        <h3 class="guide-result-name" id="guide-result-name"></h3>
        <p class="guide-result-price" id="guide-result-price"></p>
        <p class="guide-result-variation" id="guide-result-variation" style="display:none;"></p>
        <a class="guide-result-cta" id="guide-result-cta" href="#">
          Voir ce luminaire
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>

      <!-- Complement product (grande pièce only) -->
      <div class="guide-result-product guide-complement" id="guide-result-product-complement" style="display:none;">
        <div class="guide-result-badge guide-complement-badge">Pour compléter</div>
        <div class="guide-result-image-wrap">
          <img class="guide-result-image" id="guide-complement-image" src="" alt="" />
        </div>
        <h3 class="guide-result-name" id="guide-complement-name"></h3>
        <p class="guide-result-price" id="guide-complement-price"></p>
        <a class="guide-result-cta guide-result-cta-secondary" id="guide-complement-cta" href="#">
          Voir ce luminaire
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>

    </div>

    <!-- Follow-up buttons (AI-generated, display only in Phase A) -->
    <div class="guide-followup-buttons" id="guide-followup-buttons" style="display:none;"></div>

    <!-- Next proposal + counter -->
    <div class="guide-next-proposal" id="guide-next-proposal" style="display:none;">
      <button class="guide-next-btn" id="guide-next-btn" type="button">
        Autre recommandation
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
      <p class="guide-proposal-counter" id="guide-proposal-counter"></p>
    </div>

    <!-- Error -->
    <div class="guide-result-error" id="guide-result-error" style="display:none;">
      <p>Impossible de charger les résultats.
        <a href="<?php echo esc_url($shop_url); ?>">Voir toute la collection</a>.
      </p>
    </div>

    <div class="guide-restart-wrap" id="guide-restart-wrap">
      <button class="guide-restart-btn" id="guide-restart" type="button">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        Recommencer le guide
      </button>
      <a href="<?php echo esc_url($shop_url); ?>" class="guide-shop-link">
        Voir toute la collection
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>
  </section>

</div><!-- .guide-luminaire-page -->

<script>
var sapiGuide = <?php echo wp_json_encode([
  'ajaxUrl' => admin_url('admin-ajax.php'),
  'nonce'   => $ajax_nonce,
  'shopUrl' => $shop_url,
  'steps'   => $guide_steps,
]); ?>;
</script>

<?php get_footer(); ?>
