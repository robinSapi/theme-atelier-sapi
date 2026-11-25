<?php
/**
 * Vitrine « Ça irait bien avec… » dans la zone grisée, à gauche du mini-panier
 * (desktop uniquement). Pas de remise sur le 2e luminaire (abandonnée le 15/10/2026).
 * Maquette validée par Robin le 15/10/2026 (variante A, trois cartes).
 *
 * CHOIX DES 3 PRODUITS, dans cet ordre, jamais un produit déjà au panier :
 *   1. (plus utilisé depuis le 25/11/2026 : l'ampoule a sa languette sous
 *      chaque luminaire du volet et de la page panier) l'ampoule recommandée ;
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
function sapi_suggest_for_cart($with_ampoule = true) {
  static $cache = [];
  $ck = $with_ampoule ? 'amp' : 'noamp';
  if (isset($cache[$ck])) return $cache[$ck];
  if (!function_exists('WC') || !WC()->cart) return ['items' => [], 'source' => null];
  $out = ['items' => [], 'source' => null];
  if (WC()->cart->is_empty()) return $cache[$ck] = $out;

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

    // 1. Ampoule (pas sur la page panier : elle y a sa propre languette)
    if ($with_ampoule && !$has_ampoule && function_exists('sapi_ampoule_for')) {
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
  return $cache[$ck] = $out;
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
/** $sizes : largeur affichée de la carte, pour que le navigateur choisisse la bonne image. */
function sapi_suggest_card_html($s, $index, $first, $sizes = '(min-width: 1100px) 360px, 220px', $img_size = 'woocommerce_single', $lazy = true) {
  $img_attr = ['alt' => $s['product']->get_name(), 'sizes' => $sizes];
  if ($lazy) $img_attr['loading'] = 'lazy';
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
        <span class="product-image-main"><?php echo $main_id ? wp_get_attachment_image($main_id, $img_size, false, $img_attr) : wc_placeholder_img($img_size); ?></span>
        <?php if ($hover_id) : ?>
          <span class="product-image-hover"><?php echo wp_get_attachment_image($hover_id, $img_size, false, $img_attr); ?></span>
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
  // Sans ampoule : elle a sa languette sous chaque luminaire du volet (25/11/2026)
  $data = function_exists('WC') && WC()->cart ? sapi_suggest_for_cart(false) : ['items' => [], 'source' => null];
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
  wp_enqueue_script('sapi-cart-suggestions', get_template_directory_uri() . '/assets/sapi-cart-suggestions.js', ['jquery', 'sapi-amp-picker'], filemtime($path), true);
  wp_localize_script('sapi-cart-suggestions', 'sapiSuggest', [
    'addUrl' => home_url('/?wc-ajax=sapi_add_to_cart'),
    'nonce'  => wp_create_nonce('sapi-add-to-cart'),
  ]);
});

/* ========================================================================
 * VOLET PANIER : AMPOULES RANGÉES SOUS LEUR LUMINAIRE (25/11/2026, option A)
 *
 * Une ampoule reste TOUJOURS une ligne de panier WooCommerce ordinaire (prix,
 * quantité, stock, commande). Seul son emplacement à l'affichage change :
 *  - si UN SEUL luminaire du panier l'a parmi ses ampoules (ventes croisées
 *    « accessoires » nommées « ampoule »), sa ligne s'affiche sous lui en
 *    sous-ligne, avec ses propres − / + et Retirer ;
 *  - si plusieurs luminaires la partagent, elle reste une ligne normale, avec
 *    « Pour Gaston et Olivia » ;
 *  - un luminaire sans ampoule compatible au panier garde la languette
 *    « Ampoule non incluse » avec « + Ajouter ».
 * Rien n'est synchronisé automatiquement : s'il manque des ampoules, un bouton
 * « + n » le propose. Les boutons − / + / Retirer réutilisent ceux du volet
 * (assets/menu.js) ; « + Ajouter » et « + n » : assets/sapi-cart-suggestions.js.
 * Une ampoule n'est masquée de la liste que si sa sous-ligne est affichée.
 * Le bandeau de suggestions vit aussi dans .mini-cart-body (fragment AJAX).
 * ======================================================================== */

/** Une des ampoules compatibles avec ce luminaire est-elle au panier ? */
function sapi_cart_has_ampoule_for($product_id) {
  if (!function_exists('sapi_ampoules_for') || !function_exists('WC') || !WC()->cart) return false;
  $in_cart = [];
  foreach (WC()->cart->get_cart() as $item) $in_cart[(int) $item['product_id']] = true;
  foreach (sapi_ampoules_for($product_id) as $amp) {
    if (isset($in_cart[$amp->get_id()])) return true;
  }
  return false;
}

/**
 * Répartition des ampoules du panier, calculée une fois par requête :
 *  attached[lum_key] = amp_key   ampoule rangée sous ce luminaire
 *  hidden[amp_key]   = true      ligne d'ampoule affichée en sous-ligne
 *  shared[amp_key]   = [lum_key] ampoule partagée (ligne normale)
 *  covered[lum_key]  = amp_key   luminaire servi par une ampoule partagée
 *  need[amp_key]     = n         quantité de luminaires concernés
 */
function sapi_cart_amp_groups() {
  $g = ['attached' => [], 'hidden' => [], 'shared' => [], 'covered' => [], 'need' => [], 'missing' => []];
  if (!function_exists('WC') || !WC()->cart || !function_exists('sapi_ampoules_for') || !function_exists('sapi_suggest_type')) return $g;
  $cart = WC()->cart->get_cart();
  $lum_ids = [];
  foreach ($cart as $key => $item) {
    $pid = (int) $item['product_id'];
    if (!sapi_suggest_type($pid)) continue;
    $ids = [];
    // Ampoules de LA VARIATION au panier (sa liste si elle en a une, sinon celle du produit)
    $vid = !empty($item['variation_id']) ? (int) $item['variation_id'] : $pid;
    foreach (sapi_ampoules_for($vid) as $a) $ids[] = $a->get_id();
    if ($ids) $lum_ids[$key] = $ids;
  }
  /* Chaque ampoule du panier et les luminaires qui l'acceptent. */
  $users = [];
  foreach ($cart as $key => $item) {
    if (isset($lum_ids[$key])) continue;
    $pid = (int) $item['product_id'];
    $u = [];
    foreach ($lum_ids as $lk => $ids) if (in_array($pid, $ids, true)) $u[] = $lk;
    if ($u) $users[$key] = $u;
  }
  /* Rattachement par élimination : une ampoule se range sous un luminaire quand
     il est le SEUL encore libre à l'accepter. On recommence tant que ça avance
     (ranger la Globe sous Olivia peut laisser Gaston seul pour la Poire). */
  $done = [];
  do {
    $progress = false;
    foreach ($users as $ak => $u) {
      if (isset($done[$ak])) continue;
      $free = array_values(array_filter($u, function ($lk) use ($g) { return !isset($g['attached'][$lk]); }));
      if (count($free) === 1) {
        $g['attached'][$free[0]] = $ak;
        $g['hidden'][$ak] = true;
        $g['need'][$ak] = (int) $cart[$free[0]]['quantity'];
        $done[$ak] = true;
        $progress = true;
      } elseif (!$free) {
        $done[$ak] = true; // ampoule en plus d'un luminaire déjà servi : ligne normale, sans mention
      }
    }
  } while ($progress);
  /* Le reste est partagé : ligne normale « Pour Gaston et Olivia ». Le manque se
     calcule sur l'ensemble (luminaires libres concernés contre ampoules
     partagées) et ne s'affiche qu'une fois, sur la première ligne partagée. */
  $lums = []; $have = 0; $firstShared = null;
  foreach ($users as $ak => $u) {
    if (isset($done[$ak])) continue;
    $free = array_values(array_filter($u, function ($lk) use ($g) { return !isset($g['attached'][$lk]); }));
    $g['shared'][$ak] = $free;
    foreach ($free as $lk) { $g['covered'][$lk] = $ak; $lums[$lk] = true; }
    $have += (int) $cart[$ak]['quantity'];
    if ($firstShared === null) $firstShared = $ak;
  }
  if ($firstShared !== null) {
    $total = 0;
    foreach (array_keys($lums) as $lk) $total += (int) $cart[$lk]['quantity'];
    $g['missing'][$firstShared] = ['miss' => $total - $have, 'total' => $total];
  }
  return $g;
}

/** Prénom d'un luminaire du panier (« Gaston »). */
function sapi_cart_line_first_name($cart_item) {
  $p = wc_get_product((int) $cart_item['product_id']);
  $parts = $p ? preg_split('/\s+/', trim($p->get_name())) : [''];
  return $parts[0];
}

/** « Il manque 1 ampoule pour vos 2 luminaires. » + bouton « Ajouter 1 ampoule ». */
function sapi_amp_missing_html($amp_item, $miss, $total) {
  $miss = (int) $miss;
  if ($miss <= 0) return '';
  $msg = sprintf(_n('Il manque %1$d ampoule pour vos %2$d luminaires.', 'Il manque %1$d ampoules pour vos %2$d luminaires.', $miss, 'theme-sapi-maison'), $miss, (int) $total);
  $btn = sprintf(_n('Ajouter %d ampoule', 'Ajouter %d ampoules', $miss, 'theme-sapi-maison'), $miss);
  return '<span class="sapi-amp-hint">' . esc_html($msg) . ' <button type="button" class="sapi-mc-amp__add" data-product-id="' . esc_attr($amp_item['product_id']) . '" data-qty="' . esc_attr($miss) . '">' . esc_html($btn) . '</button></span>';
}

/** Ce qui s'affiche sous une ligne de luminaire du volet. */
function sapi_mini_cart_under_luminaire($cart_item_key, $cart_item, $g) {
  $cart = WC()->cart->get_cart();
  if (isset($g['attached'][$cart_item_key])) {
    $ak = $g['attached'][$cart_item_key];
    $a = $cart[$ak];
    $ap = $a['data'];
    ?>
    <div class="sapi-amp-sub sapi-amp-sub--mini">
      <span class="sapi-amp-sub__check" aria-hidden="true">✓</span>
      <p class="sapi-amp-sub__text"><strong><?php echo esc_html($ap->get_name()); ?></strong><?php echo sapi_amp_missing_html($a, $g['need'][$ak] - (int) $a['quantity'], $g['need'][$ak]); ?></p>
      <div class="mini-cart-qty-selector sapi-amp-sub__qty" data-cart-item-key="<?php echo esc_attr($ak); ?>">
        <button class="mini-cart-qty-btn mini-cart-qty-minus" aria-label="<?php esc_attr_e('Diminuer la quantité', 'theme-sapi-maison'); ?>">−</button>
        <span class="mini-cart-qty-value"><?php echo esc_html($a['quantity']); ?></span>
        <button class="mini-cart-qty-btn mini-cart-qty-plus" aria-label="<?php esc_attr_e('Augmenter la quantité', 'theme-sapi-maison'); ?>">+</button>
      </div>
      <span class="sapi-amp-sub__price"><?php echo WC()->cart->get_product_price($ap); // prix unitaire, comme les lignes du volet ?></span>
      <button class="mini-cart-item-remove sapi-amp-sub__remove" data-cart-item-key="<?php echo esc_attr($ak); ?>"><?php esc_html_e('Retirer', 'theme-sapi-maison'); ?></button>
    </div>
    <?php
    return;
  }
  if (isset($g['covered'][$cart_item_key])) return; // ampoule partagée, sur sa propre ligne
  sapi_mini_cart_amp_tab($cart_item);
}

/** Sous le nom d'une ampoule partagée : pour qui, et s'il en manque. */
function sapi_mini_cart_shared_note($cart_item_key, $g) {
  if (!isset($g['shared'][$cart_item_key])) return;
  $cart = WC()->cart->get_cart();
  $names = [];
  foreach ($g['shared'][$cart_item_key] as $lk) $names[] = sapi_cart_line_first_name($cart[$lk]);
  $names = array_values(array_unique($names)); // deux tailles du même modèle : « Pour Gaston »
  echo '<span class="sapi-amp-shared">' . esc_html(sprintf(__('Pour %s', 'theme-sapi-maison'), wp_sprintf('%l', $names))) . '</span>';
  if (isset($g['missing'][$cart_item_key])) {
    $m = $g['missing'][$cart_item_key];
    echo sapi_amp_missing_html($cart[$cart_item_key], $m['miss'], $m['total']);
  }
}

/** Languette « Ampoule non incluse » sous une ligne du volet. */
function sapi_mini_cart_amp_tab($cart_item) {
  if (!function_exists('sapi_ampoule_for') || !function_exists('sapi_suggest_type')) return;
  $pid = (int) $cart_item['product_id'];
  if (!sapi_suggest_type($pid)) return;
  $vid = !empty($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : $pid;
  $amps = sapi_ampoules_for($vid);
  $amp = $amps ? $amps[0] : null;
  if (!$amp || sapi_cart_has_ampoule_for($vid)) return;
  $ids = array_map(function ($a) { return $a->get_id(); }, $amps);
  $img = $amp->get_image_id() ? wp_get_attachment_image_url($amp->get_image_id(), 'thumbnail') : '';
  $price = function_exists('sapi_amp_price_short') ? sapi_amp_price_short(wc_get_price_to_display($amp)) : html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($amp))), ENT_QUOTES, 'UTF-8');
  $qty = max(1, (int) $cart_item['quantity']);
  ?>
  <div class="sapi-amp-tab sapi-amp-tab--mini">
    <?php if ($img) : ?><img class="sapi-amp-tab__img" src="<?php echo esc_url($img); ?>" alt="" width="40" height="40" loading="lazy"><?php endif; ?>
    <strong class="sapi-amp-tab__title"><?php esc_html_e('Ampoule non incluse', 'theme-sapi-maison'); ?></strong>
    <?php /* Bouton « + Ajouter l'ampoule » avec la bulle de choix dedans. Choisir
       dans le panneau change la bulle et l'ampoule que le bouton ajoutera. */ ?>
    <span class="sapi-amp-split">
      <button type="button" class="sapi-amp-tab__add sapi-mc-amp__add" data-product-id="<?php echo esc_attr($amp->get_id()); ?>" data-qty="<?php echo esc_attr($qty); ?>"><?php esc_html_e("+ Ajouter l'ampoule", 'theme-sapi-maison'); ?></button>
      <button type="button" class="sapi-amp-bubble is-reco<?php echo count($ids) > 1 ? '' : ' is-solo'; ?>" data-ids="<?php echo esc_attr(implode(',', $ids)); ?>" data-reco="<?php echo esc_attr($amp->get_id()); ?>" data-first="<?php echo esc_attr(sapi_cart_line_first_name($cart_item)); ?>"<?php echo count($ids) > 1 ? ' aria-haspopup="dialog"' : ' tabindex="-1"'; ?>>
        <span class="sapi-amp-bubble__name"><?php echo esc_html(sapi_ampoule_short_name($amp)); ?></span>
        <span class="sapi-amp-bubble__price">· <?php echo esc_html($price); ?></span>
        <svg class="sapi-amp-bubble__chev" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
      </button>
    </span>
  </div>
  <?php
}

/** Bandeau de suggestions sous les articles du volet (affiché sous 1100 px). */
function sapi_render_mini_cart_suggest() {
  if (!function_exists('WC') || !WC()->cart) return;
  $data = sapi_suggest_for_cart(false);
  if (empty($data['items'])) return;
  $source = $data['source'];
  $first = $source ? preg_split('/\s+/', trim($source->get_name()))[0] : '';
  ?>
  <div class="mini-cart-suggest">
    <p class="sapi-suggest__eyebrow"><?php esc_html_e('Pour compléter votre commande', 'theme-sapi-maison'); ?></p>
    <p class="mini-cart-suggest__title"><?php echo $first
      ? esc_html(sprintf(__('Ça irait bien avec %s', 'theme-sapi-maison'), $first))
      : esc_html__('Nos meilleures ventes', 'theme-sapi-maison'); ?></p>
    <ul class="mini-cart-suggest__list">
      <?php foreach ($data['items'] as $s) :
        $p = $s['product'];
        $id = $p->get_id();
        $amb = function_exists('sapi_get_product_photo_ids') ? sapi_get_product_photo_ids($id, 'ambiance', 1) : [];
        $img_id = !empty($amb) ? (int) $amb[0] : $p->get_image_id();
        $from = $p->is_type('variable');
        $price = wc_price($from ? $p->get_variation_price('min', true) : wc_get_price_to_display($p));
      ?>
        <li data-product-cat="<?php echo esc_attr($s['type']); ?>">
          <a class="mini-cart-suggest__card" href="<?php echo esc_url($p->get_permalink()); ?>">
            <span class="mini-cart-suggest__img"><?php echo $img_id ? wp_get_attachment_image($img_id, 'woocommerce_thumbnail', false, ['alt' => $p->get_name(), 'loading' => 'lazy', 'sizes' => '150px']) : wc_placeholder_img('woocommerce_thumbnail'); ?></span>
            <span class="mini-cart-suggest__name"><?php echo esc_html($p->get_name()); ?></span>
            <span class="mini-cart-suggest__meta"><?php echo esc_html(sapi_suggest_type_label($s['type'])); ?></span>
            <span class="mini-cart-suggest__price"><?php if ($from) esc_html_e('dès ', 'theme-sapi-maison'); echo wp_kses_post($price); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php
}
