<?php
/*
Template Name: Guide Luminaire
*/
get_header();

$shop_url = class_exists('WooCommerce') ? esc_url(wc_get_page_permalink('shop')) : '/nos-creations/';
$ajax_nonce = wp_create_nonce('sapi-guide-results');

// ─── Steps data ───
$guide_steps = [
  [
    'id'        => 1,
    'attribute' => 'pa_piece',
    'question'  => 'Pour quelle pièce cherchez-vous un luminaire ?',
    'choices'   => [
      ['label' => 'Salon / Séjour / Salle à manger', 'slug' => 'salon',     'icon' => 'sofa'],
      ['label' => 'Cuisine',                         'slug' => 'cuisine',   'icon' => 'dining'],
      ['label' => 'Chambre',                         'slug' => 'chambre',   'icon' => 'bed'],
      ['label' => 'Bureau / Atelier',                'slug' => 'bureau',    'icon' => 'monitor'],
      ['label' => 'Entrée / Hall',                   'slug' => 'couloir',   'icon' => 'door'],
      ['label' => 'Couloir / Montée d\'escalier',   'slug' => 'couloir-2', 'icon' => 'stairs'],
    ],
  ],
  [
    'id'        => 2,
    'attribute' => 'pa_eclairage',
    'question'  => 'Quel usage principal ?',
    'choices'   => [
      ['label' => 'Éclairage fonctionnel',  'slug' => 'fonctionnel',  'icon' => 'zap'],
      ['label' => 'Ambiance & décoration',  'slug' => 'ambiance',     'icon' => 'moon'],
      ['label' => 'Les deux à la fois',     'slug' => 'les-deux',     'icon' => 'sun'],
    ],
  ],
  [
    'id'        => 3,
    'attribute' => 'pa_taille-piece',
    'question'  => 'Quelle taille de pièce ?',
    'choices'   => [
      ['label' => 'Petite (< 10 m²)',    'slug' => 'petite',  'icon' => 'square-sm'],
      ['label' => 'Moyenne (10–20 m²)',   'slug' => 'moyenne', 'icon' => 'square-md'],
      ['label' => 'Grande (> 20 m²)',     'slug' => 'grande',  'icon' => 'square-lg'],
    ],
  ],
  [
    'id'        => 4,
    'attribute' => 'pa_type-luminaire',
    'question'  => 'Où sera placé le luminaire ?',
    'choices'   => [
      ['label' => 'Au-dessus d\'une table / îlot',  'slug' => 'au-dessus-table',  'icon' => 'pendant'],
      ['label' => 'Zone de passage',                 'slug' => 'zone-passage',     'icon' => 'compass'],
      ['label' => 'Coin / mur',                      'slug' => 'coin-mur',         'icon' => 'wall'],
    ],
  ],
  [
    'id'        => 5,
    'attribute' => 'pa_hauteur',
    'question'  => 'Hauteur sous plafond ?',
    'choices'   => [
      ['label' => 'Standard (< 2,50 m)',     'slug' => 'standard',     'icon' => 'ceiling-low'],
      ['label' => 'Confortable (2,50–3 m)',  'slug' => 'confortable',  'icon' => 'ceiling-mid'],
      ['label' => 'Haute (> 3 m)',           'slug' => 'haute',        'icon' => 'ceiling-high'],
    ],
  ],
  [
    'id'        => 6,
    'attribute' => 'pa_style',
    'question'  => 'Quel style recherché ?',
    'choices'   => [
      ['label' => 'Épuré / Minimaliste',    'slug' => 'epure',      'icon' => 'minimal'],
      ['label' => 'Chaleureux / Organique',  'slug' => 'chaleureux', 'icon' => 'organic'],
      ['label' => 'Imposant / Statement',    'slug' => 'imposant',   'icon' => 'statement'],
    ],
  ],
];

// ─── SVG icons map ───
$icons = [
  'sofa'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
  'dining'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"/><path d="M20 12v8h-2"/><path d="M4 12v8h2"/><path d="M12 2a4 4 0 0 0-4 4v6h8V6a4 4 0 0 0-4-4z"/><path d="M12 12v4"/></svg>',
  'bed'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'monitor'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
  'door'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
  'zap'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
  'moon'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
  'sun'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"/></svg>',
  'square-sm'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
  'square-md'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="1"/></svg>',
  'square-lg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>',
  'pendant'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v5"/><path d="M9 7h6l-2 8H11L9 7z"/><path d="M5 21h14"/><circle cx="12" cy="16" r="1"/></svg>',
  'compass'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"/></svg>',
  'wall'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3v18"/><path d="M4 12h6"/><path d="M10 8v8"/><circle cx="14" cy="12" r="3"/><path d="M17 12h4"/></svg>',
  'ceiling-low' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18"/><path d="M12 3v6"/><path d="M9 9h6l-1 4h-4l-1-4z"/><path d="M3 21h18"/><path d="M7 21v-4m10 4v-4"/></svg>',
  'ceiling-mid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18"/><path d="M12 2v8"/><path d="M9 10h6l-1 4h-4l-1-4z"/><path d="M3 22h18"/><path d="M7 22v-4m10 4v-4"/></svg>',
  'ceiling-high'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 1h18"/><path d="M12 1v10"/><path d="M9 11h6l-1 4h-4l-1-4z"/><path d="M3 23h18"/><path d="M7 23v-4m10 4v-4"/></svg>',
  'minimal'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><line x1="12" y1="8" x2="12" y2="16"/></svg>',
  'organic'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-3 0-6 2-7 5s0 7 2 9 6 3 9 2 5-4 5-7-1-6-3-8-3-1-6-1z"/><circle cx="12" cy="13" r="2.5"/></svg>',
  'statement'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 22 12 12 22 2 12z"/></svg>',
  'stairs'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20V8"/><path d="M20 20V8"/></svg>',
];

$total_steps = count($guide_steps);
?>

<div class="guide-luminaire-page">

  <!-- ─── Intro Screen ─── -->
  <section class="guide-intro" id="guide-intro">
    <div class="guide-intro-inner">
      <p class="guide-intro-badge">Guide interactif</p>
      <h1 class="guide-intro-title">Trouvez votre luminaire idéal</h1>
      <p class="guide-intro-subtitle">6 questions pour vous guider vers la création parfaite pour votre intérieur</p>
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
      <?php foreach ($guide_steps as $index => $step) :
        $step_num = $step['id'];
        $choice_count = count($step['choices']);
      ?>
        <div class="guide-step" data-step="<?php echo esc_attr($step_num); ?>" role="group" aria-labelledby="guide-q-<?php echo esc_attr($step_num); ?>">
          <div class="guide-step-inner">
            <p class="guide-step-number" aria-hidden="true"><?php echo esc_html(str_pad($step_num, 2, '0', STR_PAD_LEFT) . ' / ' . str_pad($total_steps, 2, '0', STR_PAD_LEFT)); ?></p>
            <h2 class="guide-step-question" id="guide-q-<?php echo esc_attr($step_num); ?>"><?php echo esc_html($step['question']); ?></h2>
            <div class="guide-choices-grid" data-count="<?php echo esc_attr($choice_count); ?>" role="list">
              <?php foreach ($step['choices'] as $choice) :
                $icon_key = $choice['icon'];
                $icon_svg = isset($icons[$icon_key]) ? $icons[$icon_key] : '';
              ?>
                <button class="guide-choice-card" role="listitem"
                        data-attribute="<?php echo esc_attr($step['attribute']); ?>"
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
      <h2 class="guide-results-title">Vos luminaires idéaux</h2>
      <p class="guide-results-subtitle" id="guide-results-subtitle"></p>
    </div>

    <div class="guide-results-loading" id="guide-results-loading" aria-hidden="true">
      <svg class="guide-spinner" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg>
      <p>Nous cherchons vos luminaires...</p>
    </div>

    <ul class="products guide-results-grid" id="guide-results-grid"></ul>

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
]); ?>;
</script>

<?php get_footer(); ?>
