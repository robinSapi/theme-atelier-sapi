<?php
/**
 * Ampoule mise en avant + bundle « 2e luminaire »
 *
 * Trois mécaniques qui forment un tout, livrées le 13/10/2026 :
 *
 * 1. CASE « AMPOULE RECOMMANDÉE » SUR LA FICHE PRODUIT
 *    Au-dessus du bouton « Ajouter au panier », une case (décochée par défaut)
 *    propose l'ampoule associée au luminaire. L'ampoule associée est la
 *    première vente croisée de la catégorie « accessoires », exactement comme
 *    le bouton « Ajouter une ampoule » de la fiche technique.
 *    La case vit DANS form.cart : le JS d'ajout AJAX de single-product.php
 *    sérialise tout le formulaire, donc `sapi_add_ampoule` arrive côté serveur
 *    sans toucher à ce JS. Le hook `woocommerce_add_to_cart` ajoute alors
 *    l'ampoule, en même quantité que le luminaire. Ça marche aussi pour
 *    « Acheter maintenant » et pour un envoi de formulaire classique.
 *
 * 2. SUGGESTION « ON VOUS CONSEILLE CE LUMINAIRE »
 *    Même modèle, autre type : Olivia suspension dans le panier -> Olivia
 *    applique ou lampadaire. Le « modèle » est le prénom, c'est-à-dire le
 *    premier mot du nom produit (même règle que product-name-formatter.js).
 *    Affichée sur la page panier et, en desktop, à gauche du mini-panier.
 *    Rien n'est affiché si le modèle n'existe qu'en un seul type.
 *
 * 3. REMISE -15 % SUR LE 2E LUMINAIRE
 *    Dès que le panier contient 2 luminaires ou plus (toutes références
 *    confondues, quantités comprises), le moins cher des luminaires prend
 *    -15 %, une seule fois par commande. Les accessoires et la carte cadeau
 *    ne comptent pas. Appliquée en frais négatif, donc visible en ligne
 *    distincte au panier, au checkout et sur la commande.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

/** Taux de remise sur le 2e luminaire. */
if (!defined('SAPI_BUNDLE_REMISE')) define('SAPI_BUNDLE_REMISE', 0.15);

/** Slugs product_cat des luminaires (canoniques + variantes historiques). */
function sapi_bundle_luminaire_cats() {
  return ['suspensions', 'appliques', 'appliques-murales', 'lampadaires', 'lampesaposer', 'lampes-a-poser'];
}

/** Libellé singulier d'un type, à partir d'un slug canonique. */
function sapi_bundle_type_label($canonical) {
  $labels = [
    'suspensions'  => 'Suspension',
    'appliques'    => 'Applique',
    'lampadaires'  => 'Lampadaire',
    'lampesaposer' => 'Lampe à poser',
  ];
  return isset($labels[$canonical]) ? $labels[$canonical] : '';
}

/**
 * Slug canonique du type de luminaire d'un produit, '' si ce n'est pas un luminaire.
 * Accepte un ID de produit parent ou de variation.
 */
function sapi_bundle_luminaire_type($product_id) {
  $product_id = (int) $product_id;
  if ($product_id <= 0) return '';
  $parent = wp_get_post_parent_id($product_id);
  if ($parent) $product_id = $parent;

  $terms = get_the_terms($product_id, 'product_cat');
  if (!$terms || is_wp_error($terms)) return '';
  foreach ($terms as $t) {
    if (in_array($t->slug, ['accessoires', 'carte-cadeau'], true)) return '';
  }
  foreach ($terms as $t) {
    if (!in_array($t->slug, sapi_bundle_luminaire_cats(), true)) continue;
    $c = function_exists('sapi_catalogue_canonical_cat') ? sapi_catalogue_canonical_cat($t->slug) : $t->slug;
    if ($c) return $c;
  }
  return '';
}

/** Prénom du modèle (premier mot du nom), normalisé pour comparaison. */
function sapi_bundle_model_key($name) {
  $name  = trim(wp_strip_all_tags((string) $name));
  $first = strtok($name, " \t-");
  return $first ? strtolower(remove_accents($first)) : '';
}

/**
 * Ampoule associée à un luminaire : première vente croisée de la catégorie
 * « accessoires », achetable et en stock. null sinon.
 */
function sapi_bundle_get_ampoule_for($product) {
  if (is_numeric($product)) $product = wc_get_product($product);
  if (!$product) return null;
  if ($product->get_parent_id()) $product = wc_get_product($product->get_parent_id());
  if (!$product || !sapi_bundle_luminaire_type($product->get_id())) return null;

  foreach ($product->get_cross_sell_ids() as $cs_id) {
    $cats = get_the_terms($cs_id, 'product_cat');
    if (!$cats || is_wp_error($cats)) continue;
    foreach ($cats as $c) {
      if ($c->slug !== 'accessoires') continue;
      $amp = wc_get_product($cs_id);
      // Produit simple uniquement : un add_to_cart sans variation_id échouerait
      // sur un produit variable, et « vendu individuellement » refuserait qty > 1.
      if ($amp && $amp->is_type('simple') && !$amp->is_sold_individually()
          && $amp->is_purchasable() && $amp->is_in_stock()) return $amp;
      continue 2;
    }
  }
  return null;
}

/* =========================================================================
 * 1. Case « ampoule recommandée » sur la fiche produit
 * ========================================================================= */

add_action('woocommerce_before_add_to_cart_button', function () {
  global $product;
  if (!$product instanceof WC_Product) return;
  $amp = sapi_bundle_get_ampoule_for($product);
  if (!$amp) return;
  ?>
  <label class="sapi-ampoule-optin">
    <input type="checkbox" name="sapi_add_ampoule" value="<?php echo esc_attr($amp->get_id()); ?>">
    <span class="sapi-ampoule-optin__box" aria-hidden="true"></span>
    <span class="sapi-ampoule-optin__text">
      <span class="sapi-ampoule-optin__title"><?php esc_html_e('Ajouter l\'ampoule recommandée', 'theme-sapi-maison'); ?></span>
      <span class="sapi-ampoule-optin__name"><?php echo esc_html($amp->get_name()); ?></span>
    </span>
    <span class="sapi-ampoule-optin__price">+&nbsp;<?php echo wp_kses_post(wc_price(wc_get_price_to_display($amp))); ?></span>
  </label>
  <?php
});

add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity) {
  static $running = false;
  if ($running) return;
  if (empty($_POST['sapi_add_ampoule']) && empty($_REQUEST['sapi_add_ampoule'])) return;

  $requested = absint(isset($_POST['sapi_add_ampoule']) ? $_POST['sapi_add_ampoule'] : $_REQUEST['sapi_add_ampoule']);
  // Une seule fois par requête, même si add_to_cart est rejoué.
  unset($_POST['sapi_add_ampoule'], $_REQUEST['sapi_add_ampoule']);

  $amp = sapi_bundle_get_ampoule_for($product_id);
  // On n'ajoute que l'ampoule réellement associée à ce luminaire : la valeur
  // envoyée par le navigateur sert de déclencheur, jamais de source.
  if (!$amp || $amp->get_id() !== $requested) return;

  $running = true;
  WC()->cart->add_to_cart($amp->get_id(), max(1, (int) $quantity));
  $running = false;
}, 20, 3);

/* =========================================================================
 * 2. Suggestion « même modèle, autre type »
 * ========================================================================= */

/**
 * Index léger des luminaires publiés : [ ['id', 'key', 'type', 'sales'], ... ],
 * trié par ventes décroissantes. Gardé en transient (la suggestion est calculée
 * dans le header de chaque page dont le panier n'est pas vide) et purgé à chaque
 * modification de produit ou de stock.
 */
function sapi_bundle_index() {
  static $cache = null;
  if ($cache !== null) return $cache;
  $cached = get_transient('sapi_bundle_index');
  if (is_array($cached)) return $cache = $cached;

  $ids = wc_get_products([
    'status'   => 'publish',
    'limit'    => -1,
    'category' => sapi_bundle_luminaire_cats(),
    'return'   => 'ids',
  ]);
  $index = [];
  foreach ($ids as $id) {
    $type = sapi_bundle_luminaire_type($id);
    $key  = sapi_bundle_model_key(get_the_title($id));
    if (!$type || !$key) continue;
    $index[] = ['id' => (int) $id, 'key' => $key, 'type' => $type, 'sales' => (int) get_post_meta($id, 'total_sales', true)];
  }
  usort($index, function ($a, $b) { return $b['sales'] <=> $a['sales']; });
  set_transient('sapi_bundle_index', $index, 12 * HOUR_IN_SECONDS);
  return $cache = $index;
}

function sapi_bundle_flush_index() { delete_transient('sapi_bundle_index'); }
add_action('save_post_product', 'sapi_bundle_flush_index');
add_action('woocommerce_update_product', 'sapi_bundle_flush_index');
add_action('woocommerce_product_set_stock_status', 'sapi_bundle_flush_index');
add_action('woocommerce_variation_set_stock_status', 'sapi_bundle_flush_index');
add_action('set_object_terms', function ($object_id, $terms, $tt_ids, $taxonomy) {
  if ($taxonomy === 'product_cat') sapi_bundle_flush_index();
}, 10, 4);

/**
 * Luminaire à suggérer pour le panier courant, ou null.
 * On part du dernier luminaire ajouté, on cherche le même prénom dans un autre
 * type, absent du panier. À égalité, la meilleure vente l'emporte.
 */
function sapi_bundle_get_suggestion() {
  static $done = false, $result = null;
  if ($done) return $result;
  if (!function_exists('WC') || !WC()->cart) return null;
  $done = true;
  if (WC()->cart->is_empty()) return null;

  $in_cart = [];
  $sources = [];
  foreach (WC()->cart->get_cart() as $item) {
    $pid = (int) $item['product_id'];
    $in_cart[$pid] = true;
    $type = sapi_bundle_luminaire_type($pid);
    if ($type) $sources[] = ['id' => $pid, 'type' => $type];
  }
  if (!$sources) return null;

  $index = sapi_bundle_index();
  foreach (array_reverse($sources) as $src) {
    $key = sapi_bundle_model_key(get_the_title($src['id']));
    if (!$key) continue;
    foreach ($index as $row) {
      if ($row['key'] !== $key || $row['type'] === $src['type'] || isset($in_cart[$row['id']])) continue;
      $cand = wc_get_product($row['id']);
      if (!$cand || !$cand->is_purchasable() || !$cand->is_in_stock()) continue;
      $result = ['product' => $cand, 'type' => $row['type']];
      return $result;
    }
  }
  return null;
}

/** Nombre d'unités de luminaires dans le panier. */
function sapi_bundle_luminaire_count() {
  if (!function_exists('WC') || !WC()->cart) return 0;
  $n = 0;
  foreach (WC()->cart->get_cart() as $item) {
    if (sapi_bundle_luminaire_type($item['product_id'])) $n += (int) $item['quantity'];
  }
  return $n;
}

/**
 * Carte de suggestion. $context = 'cart' | 'aside'.
 * Renvoie '' quand il n'y a rien à suggérer.
 */
function sapi_bundle_card_html($context = 'cart') {
  $s = sapi_bundle_get_suggestion();
  if (!$s) return '';
  $p     = $s['product'];
  $img   = $p->get_image('woocommerce_thumbnail', ['class' => 'sapi-bundle-card__img', 'loading' => 'lazy']);
  $label = sapi_bundle_type_label($s['type']);
  $pct   = (int) round(SAPI_BUNDLE_REMISE * 100);
  $remise_active = sapi_bundle_luminaire_count() >= 2;

  ob_start();
  ?>
  <div class="sapi-bundle-card sapi-bundle-card--<?php echo esc_attr($context); ?>">
    <p class="sapi-bundle-card__eyebrow"><?php esc_html_e('On vous conseille ce luminaire pour compléter votre commande', 'theme-sapi-maison'); ?></p>
    <a class="sapi-bundle-card__media" href="<?php echo esc_url($p->get_permalink()); ?>"><?php echo $img; ?></a>
    <div class="sapi-bundle-card__body">
      <p class="sapi-bundle-card__name"><a href="<?php echo esc_url($p->get_permalink()); ?>"><?php echo esc_html($p->get_name()); ?></a></p>
      <?php if ($label) : ?><p class="sapi-bundle-card__type"><?php echo esc_html($label); ?></p><?php endif; ?>
      <p class="sapi-bundle-card__price"><?php echo wp_kses_post($p->get_price_html()); ?></p>
      <p class="sapi-bundle-card__deal">
        <?php if ($remise_active) : ?>
          <?php printf(esc_html__('Votre remise de %d %% sur le 2e luminaire est déjà appliquée.', 'theme-sapi-maison'), $pct); ?>
        <?php else : ?>
          <?php printf(esc_html__('-%d %% sur le 2e luminaire : la remise s\'applique au moins cher des deux.', 'theme-sapi-maison'), $pct); ?>
        <?php endif; ?>
      </p>
      <a class="sapi-bundle-card__cta" href="<?php echo esc_url($p->get_permalink()); ?>"><?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?></a>
    </div>
  </div>
  <?php
  return ob_get_clean();
}

/** Conteneur desktop à gauche du mini-panier. Toujours rendu, même vide, pour servir de cible au fragment. */
function sapi_render_bundle_aside() {
  echo '<div class="sapi-bundle-aside">' . sapi_bundle_card_html('aside') . '</div>';
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
  ob_start();
  sapi_render_bundle_aside();
  $fragments['.sapi-bundle-aside'] = ob_get_clean();
  $fragments['.sapi-bundle-cart'] = sapi_bundle_cart_wrapper_html();
  return $fragments;
}, 20);

// Page panier : la carte se place sous le bloc panier, dans le wrapper .sapi-cart-outer
// (le filtre de functions.php, priorité 10, enveloppe ce qu'on renvoie ici).
add_filter('render_block', function ($content, $block) {
  if (empty($block['blockName']) || $block['blockName'] !== 'woocommerce/cart') return $content;
  return $content . sapi_bundle_cart_wrapper_html();
}, 5, 2);

/** Conteneur de la page panier, toujours rendu (vide au besoin) pour servir de cible au fragment. */
function sapi_bundle_cart_wrapper_html() {
  return '<div class="sapi-bundle-cart">' . sapi_bundle_card_html('cart') . '</div>';
}

/*
 * Le panier est un bloc React : quand le client retire un article ou change une
 * quantité, la carte rendue côté serveur ne bougerait pas. On écoute le store
 * du panier et on redemande les fragments à chaque changement de contenu, ce
 * qui remplace .sapi-bundle-cart (et l'aside) par leur version à jour.
 */
add_action('wp_footer', function () {
  if (!function_exists('is_cart') || !is_cart()) return;
  ?>
  <script>
  (function () {
    if (!window.wp || !wp.data || typeof jQuery === 'undefined') return;
    var last = null;
    wp.data.subscribe(function () {
      var store = wp.data.select('wc/store/cart');
      if (!store || !store.getCartData) return;
      var items = store.getCartData().items || [];
      var sig = items.map(function (i) { return i.key + ':' + i.quantity; }).join('|');
      if (last === null) { last = sig; return; }
      if (sig === last) return;
      last = sig;
      jQuery(document.body).trigger('wc_fragment_refresh');
    });
  })();
  </script>
  <?php
}, 30);

/* =========================================================================
 * 3. Remise -15 % sur le 2e luminaire
 * ========================================================================= */

add_action('woocommerce_cart_calculate_fees', function ($cart) {
  if (is_admin() && !wp_doing_ajax()) return;

  $units    = 0;
  $cheapest = null; // ['unit_ht' => float, 'tax_class' => string]
  foreach ($cart->get_cart() as $item) {
    if (!sapi_bundle_luminaire_type($item['product_id'])) continue;
    $qty = (int) $item['quantity'];
    if ($qty <= 0) continue;
    $units += $qty;
    // line_total = HT après coupons, quel que soit le réglage « prix TTC ».
    // Après coupon : avec BIENVENUE10, la remise porte sur le prix déjà remisé
    // (-10 % puis -15 %, soit -23,5 % sur ce luminaire) au lieu de s'additionner.
    $unit_ht = (float) $item['line_total'] / $qty;
    if ($cheapest === null || $unit_ht < $cheapest['unit_ht']) {
      $cheapest = ['unit_ht' => $unit_ht, 'tax_class' => $item['data']->get_tax_class()];
    }
  }
  if ($units < 2 || !$cheapest || $cheapest['unit_ht'] <= 0) return;

  $pct    = (int) round(SAPI_BUNDLE_REMISE * 100);
  // Pas d'arrondi ici : WooCommerce arrondit après avoir calculé la TVA,
  // ce qui évite l'écart d'un centime sur le TTC.
  $amount = $cheapest['unit_ht'] * SAPI_BUNDLE_REMISE;
  // Frais négatif taxable : WooCommerce retire aussi la TVA correspondante,
  // donc la remise TTC vaut bien 15 % du prix TTC du luminaire.
  $cart->add_fee(sprintf(__('Remise 2e luminaire (-%d %%)', 'theme-sapi-maison'), $pct), -$amount, wc_tax_enabled(), $cheapest['tax_class']);
}, 20);
