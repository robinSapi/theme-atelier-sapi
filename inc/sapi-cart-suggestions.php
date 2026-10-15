<?php
/**
 * Vitrine « Ça irait bien avec… » dans la zone grisée, à gauche du mini-panier
 * (desktop uniquement), et remise -10 % sur le 2e luminaire.
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
 * REMISE : dès 2 luminaires au panier (toutes références, quantités comprises),
 * le moins cher prend -10 %, une fois par commande, en frais négatif visible
 * au panier, au checkout et sur la commande. Calculée après coupon.
 *
 * AFFICHAGE : rendu dans header.php juste après .mini-cart-overlay, visible via
 * `.mini-cart-overlay.is-visible ~ .sapi-cart-suggest` ; rafraîchi par le
 * fragment AJAX `.sapi-cart-suggest` à chaque changement du panier.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

if (!defined('SAPI_REMISE_2E_LUMINAIRE')) define('SAPI_REMISE_2E_LUMINAIRE', 0.10);

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
  $out['luminaires'] = 0;
  foreach (WC()->cart->get_cart() as $item) {
    $pid = (int) $item['product_id'];
    $in_cart[$pid] = true;
    if (sapi_suggest_type($pid)) $out['luminaires'] += (int) $item['quantity'];
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

/** Nom en deux parties pour l'affichage maison : prénom en gras, reste en script. */
function sapi_suggest_name_html($name) {
  $name = trim((string) $name);
  $parts = preg_split('/\s+/', $name, 2);
  $rest = isset($parts[1]) ? $parts[1] : '';
  return '<span class="sapi-suggest__first">' . esc_html($parts[0]) . '</span>' . ($rest !== '' ? '<span class="sapi-suggest__rest">' . esc_html(ucfirst($rest)) . '</span>' : '');
}

function sapi_render_cart_suggestions() {
  $data = function_exists('WC') && WC()->cart ? sapi_suggest_for_cart() : ['items' => [], 'source' => null];
  echo '<div class="sapi-cart-suggest" aria-label="' . esc_attr__('Suggestions pour compléter votre commande', 'theme-sapi-maison') . '">';
  if (!empty($data['items'])) {
    $source = $data['source'];
    $first = $source ? preg_split('/\s+/', trim($source->get_name()))[0] : '';
    $pct = (int) round(SAPI_REMISE_2E_LUMINAIRE * 100);
    ?>
    <p class="sapi-suggest__eyebrow"><?php esc_html_e('Pour compléter votre commande', 'theme-sapi-maison'); ?></p>
    <p class="sapi-suggest__title"><?php
      echo $first
        ? esc_html(sprintf(__('Ça irait bien avec %s', 'theme-sapi-maison'), $first))
        : esc_html__('Nos meilleures ventes', 'theme-sapi-maison');
    ?></p>
    <div class="sapi-suggest__cards">
      <?php foreach ($data['items'] as $s) :
        $p = $s['product'];
        $is_amp = $s['kind'] === 'ampoule';
        if ($is_amp) {
          $why = $first ? sprintf(__('Celle que je recommande pour %s', 'theme-sapi-maison'), $first) : __('Ampoule recommandée', 'theme-sapi-maison');
          $price = wc_price(wc_get_price_to_display($p));
        } else {
          $label = sapi_suggest_type_label($s['type']);
          $why = $label . ' · ' . ($s['kind'] === 'modele' ? __('même modèle', 'theme-sapi-maison') : __('meilleure vente', 'theme-sapi-maison'));
          $min = $p->is_type('variable') ? $p->get_variation_price('min', true) : wc_get_price_to_display($p);
          $price = ($p->is_type('variable') ? __('dès', 'theme-sapi-maison') . ' ' : '') . wc_price($min);
        }
        ?>
        <div class="sapi-suggest__card">
          <a class="sapi-suggest__media" href="<?php echo esc_url($p->get_permalink()); ?>" tabindex="-1"><?php echo $p->get_image('woocommerce_thumbnail', ['loading' => 'lazy']); ?></a>
          <div class="sapi-suggest__body">
            <?php
            // Le badge ne dit vrai que si le panier contient exactement 1 luminaire :
            // la carte serait alors le 2e. Avec 0 ou 2+, on ne promet rien.
            if (!$is_amp && isset($data['luminaires']) && $data['luminaires'] === 1) : ?><span class="sapi-suggest__tag"><?php echo esc_html(sprintf(__('−%d %% en 2e luminaire', 'theme-sapi-maison'), $pct)); ?></span><?php endif; ?>
            <a class="sapi-suggest__name" href="<?php echo esc_url($p->get_permalink()); ?>"><?php echo sapi_suggest_name_html($p->get_name()); ?></a>
            <span class="sapi-suggest__why"><?php echo esc_html($why); ?></span>
            <span class="sapi-suggest__row">
              <span class="sapi-suggest__price"><?php echo wp_kses_post($price); ?></span>
              <?php if ($is_amp) : ?>
                <button type="button" class="sapi-suggest__go sapi-suggest__go--add" data-product-id="<?php echo esc_attr($p->get_id()); ?>"><?php esc_html_e('+ Ajouter', 'theme-sapi-maison'); ?></button>
              <?php else : ?>
                <a class="sapi-suggest__go" href="<?php echo esc_url($p->get_permalink()); ?>"><?php esc_html_e('Voir', 'theme-sapi-maison'); ?></a>
              <?php endif; ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="sapi-suggest__close"><?php esc_html_e('Non merci', 'theme-sapi-maison'); ?></button>
    <?php
  }
  echo '</div>';
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
  ob_start();
  sapi_render_cart_suggestions();
  $fragments['.sapi-cart-suggest'] = ob_get_clean();
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

/* ---------- Remise -10 % sur le 2e luminaire ---------- */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
  if (is_admin() && !wp_doing_ajax()) return;
  $units = 0;
  $cheapest = null;
  foreach ($cart->get_cart() as $item) {
    if (!sapi_suggest_type($item['product_id'])) continue;
    $qty = (int) $item['quantity'];
    if ($qty <= 0) continue;
    $units += $qty;
    // line_total : HT, après coupons (BIENVENUE10 et cette remise ne s'additionnent pas, ils se cumulent).
    $unit_ht = (float) $item['line_total'] / $qty;
    if ($cheapest === null || $unit_ht < $cheapest['unit_ht']) {
      $cheapest = ['unit_ht' => $unit_ht, 'tax_class' => $item['data']->get_tax_class()];
    }
  }
  if ($units < 2 || !$cheapest || $cheapest['unit_ht'] <= 0) return;
  $pct = (int) round(SAPI_REMISE_2E_LUMINAIRE * 100);
  // Frais négatif taxable, non arrondi : WooCommerce retire la TVA correspondante et arrondit à la fin.
  $cart->add_fee(sprintf(__('Remise 2e luminaire (-%d %%)', 'theme-sapi-maison'), $pct), -($cheapest['unit_ht'] * SAPI_REMISE_2E_LUMINAIRE), wc_tax_enabled(), $cheapest['tax_class']);
}, 20);
