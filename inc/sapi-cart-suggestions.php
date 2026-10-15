<?php
/**
 * Vitrine « Ça irait bien avec… » dans la zone grisée, à gauche du mini-panier
 * (desktop uniquement). Pas de remise sur le 2e luminaire (abandonnée le 15/10/2026).
 * Maquette validée par Robin le 15/10/2026 (variante A, trois cartes).
 *
 * CHOIX DES 3 PRODUITS, dans cet ordre, jamais un produit déjà au panier :
 *   1. l'ampoule recommandée du dernier luminaire ajouté, s'il n'y a aucune
 *      ampoule au panier (seule carte avec ajout direct : pas d'options) ;
 *   2. le même modèle (même prénom) dans un autre type ;
 *   3. les meilleures ventes d'un type complémentaire (suspension -> lampe à
 *      poser puis applique, etc.), puis n'importe quelle meilleure vente.
 * Sans luminaire au panier : les trois meilleures ventes.
 * Les luminaires mènent à leur fiche (« Voir ») : ils ont tailles et essences.
 *
 * AFFICHAGE : rendu dans header.php juste après .mini-cart-overlay, visible via
 * `.mini-cart-overlay.is-visible ~ .sapi-cart-suggest`, centré dans la zone
 * libre à gauche du volet. Cartes au gabarit des cartes produit du site
 * (photo ambiance, photo produit au survol). Le fragment AJAX met à jour la
 * source cachée ; le JS anime les cartes qui partent et celles qui arrivent.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

/** Type canonique d'un luminaire (suspensions, appliques, lampadaires, lampesaposer), '' sinon. */
function sapi_suggest_type($product_id) {
  $product_id = (int) $product_id;
  $parent = wp_get_post_parent_id($product_id);
  if ($parent) $product_id = $parent;
  if (!function_exists('sapi_ampoule_is_luminaire') || !sapi_ampoule_is_luminaire($product_id)) return '';
  $terms = get_the_terms($product_id, 'product_cat');
  if (!$terms || is_wp_error($terms)) return '';
  foreach ($terms as $t) {
    $c = function_exists('sapi_catalogue_canonical_cat') ? sapi_catalogue_canonical_cat($t->slug) : '';
    if ($c) return $c;
  }
  return '';
}

function sapi_suggest_type_label($type) {
  $l = ['suspensions' => 'Suspension', 'appliques' => 'Applique', 'lampadaires' => 'Lampadaire', 'lampesaposer' => 'Lampe à poser'];
  return isset($l[$type]) ? $l[$type] : '';
}

/** Prénom du modèle, normalisé pour comparer (« Olivia La gardiena » -> « olivia »). */
function sapi_suggest_model_key($name) {
  $first = strtok(trim(wp_strip_all_tags((string) $name)), " \t");
  return $first ? strtolower(remove_accents($first)) : '';
}

/**
 * Index léger des luminaires publiés, trié par ventes : [id, key, type, sales].
 * En transient, purgé à chaque modification de produit ou de stock.
 */
function sapi_suggest_index() {
  static $cache = null;
  if ($cache !== null) return $cache;
  $cached = get_transient('sapi_suggest_index');
  if (is_array($cached)) return $cache = $cached;

  $cats = function_exists('sapi_ampoule_luminaire_cats') ? sapi_ampoule_luminaire_cats() : [];
  $ids = $cats ? wc_get_products(['status' => 'publish', 'limit' => -1, 'category' => $cats, 'return' => 'ids']) : [];
  $index = [];
  foreach ($ids as $id) {
    $type = sapi_suggest_type($id);
    $key  = sapi_suggest_model_key(get_the_title($id));
    if (!$type || !$key) continue;
    $index[] = ['id' => (int) $id, 'key' => $key, 'type' => $type, 'sales' => (int) get_post_meta($id, 'total_sales', true)];
  }
  usort($index, function ($a, $b) { return $b['sales'] <=> $a['sales']; });
  set_transient('sapi_suggest_index', $index, 12 * HOUR_IN_SECONDS);
  return $cache = $index;
}
function sapi_suggest_flush_index() { delete_transient('sapi_suggest_index'); }
add_action('save_post_product', 'sapi_suggest_flush_index');
add_action('woocommerce_update_product', 'sapi_suggest_flush_index');
add_action('woocommerce_product_set_stock_status', 'sapi_suggest_flush_index');
add_action('woocommerce_variation_set_stock_status', 'sapi_suggest_flush_index');

/**
 * Les suggestions du panier courant : [['product' => WC_Product, 'kind' => ampoule|modele|vente, 'type' => ...], ...]
 * plus le luminaire source. Calculées une fois par requête.
 */
function sapi_suggest_for_cart() {
  static $done = false, $out = null;
  if ($done) return $out;
  if (!function_exists('WC') || !WC()->cart) return ['items' => [], 'source' => null];
  $done = true;
  $out = ['items' => [], 'source' => null];
  if (WC()->cart->is_empty()) return $out;

  $in_cart = [];
  $has_ampoule = false;
  $source_id = 0;
  foreach (WC()->cart->get_cart() as $item) {
    $pid = (int) $item['product_id'];
    $in_cart[$pid] = true;
    if (stripos(remove_accents(get_the_title($pid)), 'ampoule') !== false) $has_ampoule = true;
    if (sapi_suggest_type($pid)) $source_id = $pid; // le dernier luminaire ajouté l'emporte
  }

  $picked = [];
  $add = function ($product, $kind, $type = '') use (&$picked, &$in_cart, &$out) {
    if (!$product || count($out['items']) >= 3) return;
    $id = $product->get_id();
    if (isset($in_cart[$id]) || isset($picked[$id])) return;
    if (!$product->is_purchasable() || !$product->is_in_stock() || $product->get_status() !== 'publish') return;
    $picked[$id] = true;
    $out['items'][] = ['product' => $product, 'kind' => $kind, 'type' => $type];
  };

  $index = sapi_suggest_index();
  $source_type = '';
  $source_key = '';
  if ($source_id) {
    $out['source'] = wc_get_product($source_id);
    $source_type = sapi_suggest_type($source_id);
    $source_key = sapi_suggest_model_key(get_the_title($source_id));

    // 1. Ampoule
    if (!$has_ampoule && function_exists('sapi_ampoule_for')) {
      $add(sapi_ampoule_for($source_id), 'ampoule');
    }
    // 2. Même modèle, autre type
    foreach ($index as $row) {
      if ($row['key'] === $source_key && $row['type'] !== $source_type) {
        $add(wc_get_product($row['id']), 'modele', $row['type']);
      }
    }
    // 3. Types complémentaires, meilleures ventes, un modèle différent de la source
    $compl = [
      'suspensions'  => ['lampesaposer', 'appliques', 'lampadaires'],
      'appliques'    => ['suspensions', 'lampesaposer', 'lampadaires'],
      'lampadaires'  => ['suspensions', 'lampesaposer', 'appliques'],
      'lampesaposer' => ['suspensions', 'appliques', 'lampadaires'],
    ];
    $types = isset($compl[$source_type]) ? $compl[$source_type] : [];
    foreach ($types as $type) {
      foreach ($index as $row) {
        if ($row['type'] !== $type || $row['key'] === $source_key) continue;
        $before = count($out['items']);
        $add(wc_get_product($row['id']), 'vente', $row['type']);
        if (count($out['items']) > $before) break; // un par type, pour varier
      }
    }
  }
  // Complément : meilleures ventes, tous types
  foreach ($index as $row) {
    if (count($out['items']) >= 3) break;
    if ($source_key && $row['key'] === $source_key) continue;
    $add(wc_get_product($row['id']), 'vente', $row['type']);
  }
  return $out;
}

/**
 * Rendu. Deux conteneurs :
 *  - .sapi-cart-suggest-src (caché) : les cartes calculées côté serveur, seule
 *    cible du fragment AJAX ;
 *  - .sapi-cart-suggest : la vitrine visible, construite et mise à jour par
 *    assets/sapi-cart-suggestions.js à partir de la source, ce qui permet de
 *    faire partir et arriver les cartes en douceur au lieu de tout remplacer.
 * Sans JS, rien ne s'affiche : la vitrine est un plus, le panier reste entier.
 */
function sapi_render_cart_suggestions() {
  sapi_render_cart_suggestions_src();
  echo '<div class="sapi-cart-suggest" aria-live="polite"></div>';
}

/** Une carte, au même gabarit que les cartes produit du site (woocommerce/content-product.php). */
function sapi_suggest_card_html($s, $index, $first) {
  $p = $s['product'];
  $id = $p->get_id();
  $is_amp = $s['kind'] === 'ampoule';

  // Photo : ambiance en principal, photo produit au survol (comme sur le site).
  $amb = function_exists('sapi_get_product_photo_ids') ? sapi_get_product_photo_ids($id, 'ambiance', 1) : [];
  $thumb_id = $p->get_image_id();
  $main_id = !empty($amb) ? (int) $amb[0] : $thumb_id;
  $hover_id = (!empty($amb) && $thumb_id && $thumb_id !== $main_id) ? $thumb_id : 0;

  if ($is_amp) {
    $category = $first ? sprintf(__('Recommandée pour %s', 'theme-sapi-maison'), $first) : __('Ampoule recommandée', 'theme-sapi-maison');
    $price = wc_price(wc_get_price_to_display($p));
    $from = false;
  } else {
    $category = sapi_suggest_type_label($s['type']);
    $from = $p->is_type('variable');
    $price = wc_price($from ? $p->get_variation_price('min', true) : wc_get_price_to_display($p));
  }

  ob_start();
  $tag_open  = $is_amp ? '<div class="product-card-link">' : '<a class="product-card-link" href="' . esc_url($p->get_permalink()) . '">';
  $tag_close = $is_amp ? '</div>' : '</a>';
  ?>
  <li class="product-card-cinetique sapi-suggest__card" data-product-id="<?php echo esc_attr($id); ?>" data-product-cat="<?php echo esc_attr($is_amp ? 'accessoires' : $s['type']); ?>">
    <?php echo $tag_open; ?>
      <div class="product-media<?php echo $hover_id ? ' has-hover-image' : ''; ?>">
        <span class="product-image-main"><?php echo $main_id ? wp_get_attachment_image($main_id, 'woocommerce_thumbnail', false, ['alt' => $p->get_name(), 'loading' => 'lazy']) : wc_placeholder_img('woocommerce_thumbnail'); ?></span>
        <?php if ($hover_id) : ?>
          <span class="product-image-hover"><?php echo wp_get_attachment_image($hover_id, 'woocommerce_thumbnail', false, ['alt' => $p->get_name(), 'loading' => 'lazy']); ?></span>
        <?php endif; ?>
      </div>
      <div class="product-info">
        <h3 class="product-name"><?php echo esc_html($p->get_name()); ?></h3>
        <?php if ($category) : ?><p class="product-category"><?php echo esc_html($category); ?></p><?php endif; ?>
        <div class="product-price">
          <?php if ($from) : ?><span class="price-from"><?php esc_html_e('À partir de', 'theme-sapi-maison'); ?></span><?php endif; ?>
          <span class="price-value"><?php echo wp_kses_post($price); ?></span>
        </div>
      </div>
      <div class="product-actions">
        <?php if ($is_amp) : ?>
          <button type="button" class="btn-view sapi-suggest__add" data-product-id="<?php echo esc_attr($id); ?>"><?php esc_html_e('+ Ajouter', 'theme-sapi-maison'); ?></button>
        <?php else : ?>
          <span class="btn-view"><?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> ⇾</span>
        <?php endif; ?>
      </div>
    <?php echo $tag_close; ?>
  </li>
  <?php
  return ob_get_clean();
}

function sapi_render_cart_suggestions_src() {
  $data = function_exists('WC') && WC()->cart ? sapi_suggest_for_cart() : ['items' => [], 'source' => null];
  echo '<div class="sapi-cart-suggest-src" hidden>';
  if (!empty($data['items'])) {
    $source = $data['source'];
    $first = $source ? preg_split('/\s+/', trim($source->get_name()))[0] : '';
    echo '<p class="sapi-suggest__eyebrow">' . esc_html__('Pour compléter votre commande', 'theme-sapi-maison') . '</p>';
    echo '<p class="sapi-suggest__title">' . ($first
      ? esc_html(sprintf(__('Ça irait bien avec %s', 'theme-sapi-maison'), $first))
      : esc_html__('Nos meilleures ventes', 'theme-sapi-maison')) . '</p>';
    echo '<ul class="sapi-suggest__cards">';
    foreach ($data['items'] as $i => $s) echo sapi_suggest_card_html($s, $i, $first);
    echo '</ul>';
    echo '<button type="button" class="sapi-suggest__close">' . esc_html__('Non merci', 'theme-sapi-maison') . '</button>';
  }
  echo '</div>';
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
  ob_start();
  sapi_render_cart_suggestions_src();
  $fragments['.sapi-cart-suggest-src'] = ob_get_clean();
  return $fragments;
}, 30);

add_action('wp_enqueue_scripts', function () {
  $path = get_template_directory() . '/assets/sapi-cart-suggestions.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-cart-suggestions', get_template_directory_uri() . '/assets/sapi-cart-suggestions.js', ['jquery'], filemtime($path), true);
  wp_localize_script('sapi-cart-suggestions', 'sapiSuggest', [
    'addUrl' => home_url('/?wc-ajax=sapi_add_to_cart'),
    'nonce'  => wp_create_nonce('sapi-add-to-cart'),
  ]);
});
