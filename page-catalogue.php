<?php
/*
Template Name: Catalogue B2B
*/

/**
 * Page /catalogue — catalogue prescripteurs B2B (Temps 1).
 *
 * ⚠️ CONTRAINTE CARDINALE : ÉTANCHÉITÉ. La page n'offre AUCUN chemin vers le
 * reste du site. Template totalement autonome :
 *   - pas de get_header() / get_footer() / wp_nav_menu()
 *   - pas de wp_head() / wp_footer() (points de fuite : link rel REST/RSS/oEmbed,
 *     canonical, og:url, JSON-LD Yoast avec URLs). <head> construit à la main.
 *   - logo = image morte (jamais de <a>)
 *   - fiches produit non cliquables (détail en modale), aucun lien sortant
 *   - AUCUN prix (ni HTML, ni JSON)
 *   - noindex, nofollow
 *
 * Source de données + mapping caractéristiques : inc/catalogue-data.php
 * (source de vérité unique, réutilisée par le Temps 2 / export PDF).
 *
 * ── VARIANTE PRIX ────────────────────────────────────────────────────────────
 * Le même template sert la page /catalogue-prix : champ ACF
 * `catalogue_affiche_prix` à true → PVP TTC affichés (carte + fiche technique)
 * et bloc de mentions légales en tête. Le flag est lu UNE SEULE FOIS ci-dessous
 * dans $show_prices ; /catalogue le garde à false et son rendu ne change pas.
 * Les prix viennent d'inc/catalogue-data-pro.php — jamais de catalogue-data.php,
 * qui reste la couche sans prix, auditable.
 */

if (!defined('ABSPATH')) exit;

// ── Contenus éditoriaux ACF (page courante) ──
$has_acf        = function_exists('get_field');

// ── Variante prix : lue UNE SEULE FOIS, pilote tout le rendu conditionnel ──
$show_prices    = $has_acf && function_exists('sapi_catalogue_get_products_priced')
                    ? (bool) get_field('catalogue_affiche_prix')
                    : false;
$mentions       = $show_prices ? (string) get_field('catalogue_mentions_legales') : '';

// ── Données produits, groupées par catégorie ──
// Sans le flag : la source SANS prix, strictement comme avant.
$catalogue      = function_exists('sapi_catalogue_get_products')
                    ? ($show_prices ? sapi_catalogue_get_products_priced() : sapi_catalogue_get_products())
                    : [];
$categories     = function_exists('sapi_catalogue_categories') ? sapi_catalogue_categories() : [];

$accroche       = $has_acf ? (string) get_field('catalogue_accroche') : '';
$histoire_titre = ($has_acf ? (string) get_field('catalogue_histoire_titre') : '') ?: 'Histoire de l’atelier';
$histoire_texte = $has_acf ? (string) get_field('catalogue_histoire_texte') : '';
$histoire_img   = $has_acf ? (int) get_field('catalogue_histoire_image') : 0;
$bois_titre     = ($has_acf ? (string) get_field('catalogue_bois_titre') : '') ?: 'Deux bois au choix';
$bois_intro     = $has_acf ? (string) get_field('catalogue_bois_intro') : '';
$peuplier_texte = ($has_acf ? (string) get_field('catalogue_bois_peuplier_texte') : '') ?: 'Finition claire.';
$peuplier_img   = $has_acf ? (int) get_field('catalogue_bois_peuplier_image') : 0;
$okoume_texte   = ($has_acf ? (string) get_field('catalogue_bois_okoume_texte') : '') ?: 'Teinte plus rosée et plus sombre.';
$okoume_img     = $has_acf ? (int) get_field('catalogue_bois_okoume_image') : 0;

// ── Logo (image morte, jamais de lien) ──
$logo_id  = get_theme_mod('custom_logo');
$logo_alt = get_bloginfo('name');

// ── Assets étanches : enregistrés + imprimés à la main (pas de wp_head) ──
$css_path = get_template_directory() . '/assets/catalogue.css';
$js_path  = get_template_directory() . '/assets/catalogue.js';
$fmt_path = get_template_directory() . '/assets/product-name-formatter.js';
wp_register_style('sapi-catalogue', get_template_directory_uri() . '/assets/catalogue.css', [], file_exists($css_path) ? filemtime($css_path) : null);
wp_register_script('sapi-catalogue', get_template_directory_uri() . '/assets/catalogue.js', [], file_exists($js_path) ? filemtime($js_path) : null, true);
// Formatter des noms produit (prénom Montserrat / surnom Square Peg) — cohérence avec le reste du site.
wp_register_script('sapi-catalogue-formatter', get_template_directory_uri() . '/assets/product-name-formatter.js', [], file_exists($fmt_path) ? filemtime($fmt_path) : null, true);

// Adresse de contact (mailto — pas de lien vers /contact)
$contact_email   = 'contact@atelier-sapi.fr';
$contact_subject = rawurlencode('Demande — Catalogue');

/**
 * Rendu d'une galerie d'images produit (composant réutilisable card + modale).
 * @param array<int> $ids   IDs d'attachment
 * @param string     $title titre produit (pour l'alt)
 */
function sapi_catalogue_render_gallery($ids, $title) {
  $ids = array_slice(array_values(array_filter(array_map('intval', $ids))), 0, 8);
  if (!$ids) {
    echo '<div class="cat-gallery cat-gallery--empty" aria-hidden="true"></div>';
    return;
  }
  $multi = count($ids) > 1;
  echo '<div class="cat-gallery' . ($multi ? ' is-multi' : '') . '">';
  echo '<div class="cat-gallery__track">';
  foreach ($ids as $i => $id) {
    $img = wp_get_attachment_image($id, 'large', false, [
      'class'   => 'cat-gallery__img',
      'loading' => 'lazy',
      'alt'     => esc_attr($title),
    ]);
    echo '<figure class="cat-gallery__slide' . ($i === 0 ? ' is-active' : '') . '" data-index="' . $i . '">' . $img . '</figure>';
  }
  echo '</div>';
  if ($multi) {
    echo '<button type="button" class="cat-gallery__nav cat-gallery__nav--prev" aria-label="Image précédente">&#8249;</button>';
    echo '<button type="button" class="cat-gallery__nav cat-gallery__nav--next" aria-label="Image suivante">&#8250;</button>';
    echo '<div class="cat-gallery__dots" aria-hidden="true">';
    foreach ($ids as $i => $id) {
      echo '<span class="cat-gallery__dot' . ($i === 0 ? ' is-active' : '') . '" data-go="' . $i . '"></span>';
    }
    echo '</div>';
  }
  echo '</div>';
}

/**
 * Rendu du tableau de prix d'un produit (variante /catalogue-prix uniquement).
 * TABLEAU CROISÉ : essences en lignes, dimensions en colonnes. PVP TTC.
 * Aucun prix HT, aucun taux : ces notions n'existent que dans le PDF tarif pro,
 * généré depuis l'admin.
 *
 * @param array $pricing issu de sapi_catalogue_product_pricing()
 */
function sapi_catalogue_render_prices($pricing) {
  if (empty($pricing['rows'])) return;

  $matrix   = sapi_catalogue_pricing_matrix($pricing);
  $sizes    = $matrix['sizes'];
  $essences = $matrix['essences'];
  $cells    = $matrix['cells'];
  if (!$sizes || !$essences) return;

  echo '<div class="cat-price-block">';
  echo '<h4 class="cat-price-block__title">Prix TTC</h4>';

  // Une seule combinaison (produit simple, ou taille unique en bois unique) :
  // un tableau croisé 1×1 serait grotesque, une ligne suffit.
  if (count($sizes) === 1 && count($essences) === 1) {
    $only  = isset($cells[$essences[0]][$sizes[0]]) ? $cells[$essences[0]][$sizes[0]] : null;
    $label = trim($sizes[0] . ' ' . $essences[0]);
    if ($only !== null) {
      $poids = sapi_catalogue_format_weight($only['weight']);
      echo '<p class="cat-price-single">';
      if ($label !== '') echo '<span class="cat-price-single__label">' . esc_html($label) . '</span> ';
      echo '<span class="cat-price-single__amount">' . esc_html(sapi_catalogue_format_price($only['ttc'])) . '</span>';
      if ($poids !== '') echo ' <span class="cat-price-weight">' . esc_html($poids) . '</span>';
      echo '</p>';
    }
    echo '</div>';
    return;
  }

  // Conteneur de défilement : une gamme à 4-5 tailles déborde sur mobile, et
  // c'est le tableau qui doit défiler, jamais la page.
  echo '<div class="cat-price-scroll">';
  echo '<table class="cat-price-table"><thead><tr>';
  echo '<td class="cat-price-table__corner"></td>';
  foreach ($sizes as $size) {
    echo '<th scope="col">' . esc_html($size !== '' ? $size : 'Prix') . '</th>';
  }
  echo '</tr></thead><tbody>';
  foreach ($essences as $essence) {
    echo '<tr><th scope="row">' . esc_html($essence !== '' ? $essence : 'Prix') . '</th>';
    foreach ($sizes as $size) {
      $value = isset($cells[$essence][$size]) ? $cells[$essence][$size] : null;
      if ($value === null) {
        echo '<td><span class="cat-price-table__na" title="Combinaison non disponible">—</span></td>';
        continue;
      }
      // Prix et poids partagent EXACTEMENT les mêmes axes (taille × essence) :
      // la matrice de prix est donc le domicile naturel du poids, sans tableau
      // supplémentaire ni fourchette approximative.
      $poids = sapi_catalogue_format_weight($value['weight']);
      echo '<td><span class="cat-price-table__amount">' . esc_html(sapi_catalogue_format_price($value['ttc'])) . '</span>';
      if ($poids !== '') echo '<span class="cat-price-weight">' . esc_html($poids) . '</span>';
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</tbody></table>';
  echo '</div></div>';
}

/**
 * Rendu du tableau de caractéristiques (sections).
 * @param array $sections issu de sapi_catalogue_get_product_specs()
 */
function sapi_catalogue_render_specs($sections) {
  if (!$sections) return;
  echo '<div class="fiche-specs">';
  foreach ($sections as $section) {
    echo '<div class="fiche-specs__section">';
    echo '<h4 class="fiche-specs__title">' . esc_html($section['title']) . '</h4>';
    echo '<table class="fiche-specs__table"><tbody>';
    foreach ($section['items'] as $item) {
      echo '<tr><th scope="row">' . esc_html($item['label']) . '</th><td>' . esc_html($item['value']) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
  }
  echo '</div>';
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Catalogue — <?php echo esc_html(get_bloginfo('name')); ?></title>
<?php // Montserrat via Google Fonts (comme le reste du site ; Square Peg reste local — fix Safari).
      // Ressources de police EXTERNES : ne créent aucun chemin vers le site (étanchéité préservée). ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;900&display=swap">
<?php wp_print_styles(['sapi-catalogue']); ?>
</head>
<body class="catalogue-b2b<?php echo $show_prices ? ' has-prices' : ''; ?>">

<!-- EN-TÊTE : logo image morte + titre + accroche -->
<header class="cat-header">
  <div class="cat-header__inner">
    <?php
    if ($logo_id) {
      echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'cat-header__logo', 'alt' => esc_attr($logo_alt)]);
    } else {
      // Repli : logo par défaut du thème, en image morte
      echo '<img class="cat-header__logo" src="' . esc_url(home_url('/wp-content/uploads/2024/12/logo_sapi.svg')) . '" alt="' . esc_attr($logo_alt) . '">';
    }
    ?>
    <h1 class="cat-header__title">Catalogue</h1>
    <?php if ($accroche !== '') : ?>
      <p class="cat-header__accroche"><?php echo esc_html($accroche); ?></p>
    <?php endif; ?>
  </div>
</header>

<main class="cat-main">

  <?php if ($show_prices && $mentions !== '') : ?>
  <!-- MENTIONS LÉGALES (page prix uniquement) — en tête, sous l'accroche -->
  <section class="cat-price-legal">
    <div class="cat-price-legal__inner">
      <div class="cat-price-legal__body"><?php echo wp_kses_post(wpautop($mentions)); ?></div>
    </div>
  </section>
  <?php endif; ?>

  <!-- SECTION : Histoire de l'atelier (ACF) -->
  <section class="cat-histoire">
    <div class="cat-section__inner cat-histoire__inner">
      <?php if ($histoire_img) : ?>
        <div class="cat-histoire__media">
          <?php echo wp_get_attachment_image($histoire_img, 'large', false, ['class' => 'cat-histoire__img', 'loading' => 'lazy', 'alt' => '']); ?>
        </div>
      <?php endif; ?>
      <div class="cat-histoire__text">
        <h2 class="cat-section__title"><?php echo esc_html($histoire_titre); ?></h2>
        <?php if ($histoire_texte !== '') : ?>
          <div class="cat-histoire__body"><?php echo wp_kses_post($histoire_texte); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- SECTION : Deux bois au choix (ACF) -->
  <section class="cat-bois">
    <div class="cat-section__inner">
      <h2 class="cat-section__title cat-section__title--center"><?php echo esc_html($bois_titre); ?></h2>
      <?php if ($bois_intro !== '') : ?>
        <p class="cat-bois__intro"><?php echo esc_html($bois_intro); ?></p>
      <?php endif; ?>
      <div class="cat-bois__grid">
        <div class="cat-bois__card">
          <?php if ($peuplier_img) : ?>
            <?php echo wp_get_attachment_image($peuplier_img, 'medium_large', false, ['class' => 'cat-bois__img', 'loading' => 'lazy', 'alt' => 'Peuplier']); ?>
          <?php else : ?>
            <div class="cat-bois__swatch cat-bois__swatch--peuplier" aria-hidden="true"></div>
          <?php endif; ?>
          <h3 class="cat-bois__name">Peuplier</h3>
          <p class="cat-bois__desc"><?php echo esc_html($peuplier_texte); ?></p>
        </div>
        <div class="cat-bois__card">
          <?php if ($okoume_img) : ?>
            <?php echo wp_get_attachment_image($okoume_img, 'medium_large', false, ['class' => 'cat-bois__img', 'loading' => 'lazy', 'alt' => 'Okoumé']); ?>
          <?php else : ?>
            <div class="cat-bois__swatch cat-bois__swatch--okoume" aria-hidden="true"></div>
          <?php endif; ?>
          <h3 class="cat-bois__name">Okoumé</h3>
          <p class="cat-bois__desc"><?php echo esc_html($okoume_texte); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- BARRE DE FILTRES CATÉGORIES (affichage écran uniquement) -->
  <nav class="cat-filters" aria-label="Filtrer par catégorie">
    <button type="button" class="cat-filter is-active" data-filter="all">Tout</button>
    <?php foreach ($categories as $slug => $label) : ?>
      <button type="button" class="cat-filter" data-filter="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></button>
    <?php endforeach; ?>
  </nav>

  <!-- GRILLE PRODUITS -->
  <section class="cat-grid" id="cat-grid">
    <?php
    foreach ($catalogue as $slug => $block) :
      foreach ($block['products'] as $p) :
        $pid   = (int) $p['id'];
        $title = (string) $p['title'];
        ?>
        <article class="cat-card" data-cat="<?php echo esc_attr($slug); ?>" data-product-cat="<?php echo esc_attr($slug); ?>" data-product-id="<?php echo $pid; ?>">
          <div class="cat-card__media">
            <?php sapi_catalogue_render_gallery($p['gallery_ids'], $title); ?>
          </div>
          <div class="cat-card__body">
            <h3 class="cat-card__title product-name"><?php echo esc_html($title); ?></h3>
            <?php if ($p['short_desc'] !== '') : ?>
              <div class="cat-card__desc"><?php echo wp_kses_post($p['short_desc']); ?></div>
            <?php endif; ?>
            <?php if ($show_prices && isset($p['pricing']['min_ttc']) && $p['pricing']['min_ttc'] !== null) : ?>
              <p class="cat-price-from">À partir de
                <span class="cat-price-from__amount"><?php echo esc_html(sapi_catalogue_format_price($p['pricing']['min_ttc'])); ?></span>
                <span class="cat-price-from__tax">TTC</span>
              </p>
            <?php endif; ?>
            <button type="button" class="cat-card__more" data-open-fiche="<?php echo $pid; ?>">Fiche technique</button>
          </div>

          <!-- Contenu de la fiche technique (cloné dans la modale à l'ouverture) -->
          <template class="cat-card__fiche">
            <div class="fiche" data-fiche-title="<?php echo esc_attr($title); ?>">
              <?php if ($p['description'] !== '') : ?>
                <div class="fiche__desc"><?php echo wp_kses_post(wpautop($p['description'])); ?></div>
              <?php endif; ?>
              <?php if ($show_prices && !empty($p['pricing'])) sapi_catalogue_render_prices($p['pricing']); ?>
              <?php sapi_catalogue_render_specs($p['specs']); ?>
            </div>
          </template>
        </article>
        <?php
      endforeach;
    endforeach;
    ?>
    <p class="cat-grid__empty" hidden>Aucun produit dans cette catégorie.</p>
  </section>

  <?php if (!$show_prices) : ?>
  <!-- BLOC EXPORT PDF (Temps 2) — sélection INDÉPENDANTE du filtre d'affichage du haut -->
  <!-- Masqué sur /catalogue-prix : l'endpoint public produit un PDF SANS prix,
       incohérent sur une page qui en affiche. Un PDF prix public reste possible
       plus tard — même gabarit que le tarif pro, colonne HT retirée. -->
  <section class="cat-pdf" data-endpoint="<?php echo esc_url(rest_url('sapi/v1/catalogue-pdf')); ?>">
    <h2 class="cat-section__title cat-section__title--center">Télécharger le catalogue en PDF</h2>
    <p class="cat-pdf__hint">Choisissez les catégories à inclure dans le PDF.</p>
    <div class="cat-pdf__choices">
      <?php foreach ($categories as $slug => $label) : ?>
        <label class="cat-pdf__choice"><input type="checkbox" value="<?php echo esc_attr($slug); ?>" checked> <?php echo esc_html($label); ?></label>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cat-pdf__btn">Télécharger en PDF</button>
    <p class="cat-pdf__status" role="status" aria-live="polite" hidden></p>
  </section>
  <?php endif; ?>

</main>

<!-- BOUTON CONTACT permanent (mailto — aucun lien vers le site) -->
<a class="cat-contact-fab" href="mailto:<?php echo esc_attr($contact_email); ?>?subject=<?php echo $contact_subject; ?>">Nous contacter</a>

<!-- MODALE FICHE TECHNIQUE -->
<div class="cat-modal" id="cat-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cat-modal-title">
  <div class="cat-modal__overlay" data-close></div>
  <div class="cat-modal__dialog" role="document">
    <button type="button" class="cat-modal__close" data-close aria-label="Fermer">&times;</button>
    <div class="cat-modal__grid">
      <div class="cat-modal__media" id="cat-modal-media"></div>
      <div class="cat-modal__content">
        <h2 class="cat-modal__title product-name" id="cat-modal-title"></h2>
        <div class="cat-modal__body" id="cat-modal-body"></div>
      </div>
    </div>
  </div>
</div>

<?php wp_print_scripts(['sapi-catalogue-formatter', 'sapi-catalogue']); ?>
</body>
</html>
