<?php
/**
 * Case « Ajouter l'ampoule recommandée » sur la fiche produit
 *
 * Étape 1 du chantier « ampoules + bundle » (octobre 2026). La suggestion
 * « même modèle, autre type » et la remise sur le 2e luminaire viendront
 * ensuite, chacune dans son propre fichier.
 *
 * CE QUE FAIT CE FICHIER
 * Au-dessus du bouton « Ajouter au panier » d'un luminaire, une case décochée
 * par défaut propose l'ampoule associée. Si elle est cochée, l'ampoule part
 * au panier avec le luminaire, en même quantité.
 *
 * QUELLE AMPOULE
 * La première vente croisée du luminaire rangée dans la catégorie
 * « accessoires », exactement comme le bouton « Ajouter une ampoule » de la
 * fiche technique (woocommerce/single-product.php). Produit simple uniquement :
 * un add_to_cart sans variation_id échouerait sur un produit variable.
 * Pas de vente croisée ampoule = pas de case.
 *
 * COMMENT L'AMPOULE ARRIVE AU PANIER
 * La case vit DANS form.cart. Le JS d'ajout AJAX de single-product.php
 * sérialise tout le formulaire, donc `sapi_add_ampoule` arrive côté serveur
 * sans toucher à ce JS. Le hook `woocommerce_add_to_cart` ajoute alors
 * l'ampoule. Couvre : bouton principal (AJAX), formulaire classique, et la
 * barre sticky des produits simples (qui lit la case, cf. single-product.php).
 * Non couvert : Apple Pay / Google Pay.
 * Si l'ampoule ne peut pas être ajoutée (stock insuffisant), le luminaire part
 * seul et WooCommerce affiche son message au chargement de page suivant.
 * La valeur envoyée par le navigateur sert de déclencheur, jamais de source :
 * on revérifie côté serveur que c'est bien l'ampoule associée au luminaire.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

/** Slugs product_cat des luminaires (canoniques + variantes historiques). */
function sapi_ampoule_luminaire_cats() {
  return ['suspensions', 'appliques', 'appliques-murales', 'lampadaires', 'lampesaposer', 'lampes-a-poser'];
}

/** Vrai si le produit (ou le parent d'une variation) est un luminaire. */
function sapi_ampoule_is_luminaire($product_id) {
  $product_id = (int) $product_id;
  if ($product_id <= 0) return false;
  $parent = wp_get_post_parent_id($product_id);
  if ($parent) $product_id = $parent;

  $terms = get_the_terms($product_id, 'product_cat');
  if (!$terms || is_wp_error($terms)) return false;
  $slugs = wp_list_pluck($terms, 'slug');
  if (array_intersect($slugs, ['accessoires', 'carte-cadeau'])) return false;
  return (bool) array_intersect($slugs, sapi_ampoule_luminaire_cats());
}

/**
 * Ampoules proposées pour un luminaire : ventes croisées de la catégorie
 * « accessoires » dont le nom contient « ampoule » (le câble, le pavillon et le
 * pied sont aussi des accessoires). Produits simples, achetables, en stock.
 * Quand il y en a plusieurs, l'ampoule Poire passe en tête : c'est elle qui est
 * présélectionnée dans la liste (choix de Robin, 15/10/2026). Sinon, l'ordre des
 * ventes croisées de la fiche.
 *
 * @return WC_Product[]
 */
function sapi_ampoules_for($product) {
  if (is_numeric($product)) $product = wc_get_product($product);
  if (!$product) return [];
  if ($product->get_parent_id()) $product = wc_get_product($product->get_parent_id());
  if (!$product || !sapi_ampoule_is_luminaire($product->get_id())) return [];

  $list = [];
  foreach ($product->get_cross_sell_ids() as $cs_id) {
    $cats = get_the_terms($cs_id, 'product_cat');
    if (!$cats || is_wp_error($cats)) continue;
    if (!in_array('accessoires', wp_list_pluck($cats, 'slug'), true)) continue;
    $amp = wc_get_product($cs_id);
    if (!$amp || stripos(remove_accents($amp->get_name()), 'ampoule') === false) continue;
    if ($amp->is_type('simple') && !$amp->is_sold_individually()
        && $amp->is_purchasable() && $amp->is_in_stock()) {
      $list[] = $amp;
    }
  }
  usort($list, function ($a, $b) {
    $pa = stripos($a->get_name(), 'poire') !== false ? 0 : 1;
    $pb = stripos($b->get_name(), 'poire') !== false ? 0 : 1;
    return $pa <=> $pb; // usort est stable depuis PHP 8 : l'ordre de la fiche est gardé sinon
  });
  return $list;
}

/** Nom court pour une ligne compacte : « Ampoule Globe » devient « Globe ». */
function sapi_ampoule_short_name($amp) {
  $name  = $amp->get_name();
  $short = trim(preg_replace('/^\s*ampoules?\s+/iu', '', $name));
  return $short !== '' ? $short : $name;
}

/** Ampoule par défaut d'un luminaire (la première de la liste), ou null. */
function sapi_ampoule_for($product) {
  $list = sapi_ampoules_for($product);
  return $list ? $list[0] : null;
}

/*
 * Affichage. Rendu dans form.cart juste avant le bouton (seul hook de WooCommerce
 * qui soit DANS le formulaire et toujours présent), puis déplacé en JS
 * (assets/sapi-ampoule-optin.js) dans le bloc des add-ons, sous « Couleur du
 * câble et du pavillon », ou sous le prix sur une fiche sans add-on. Si le JS
 * ne tourne pas, la case reste au-dessus du bouton et fonctionne quand même.
 * Une seule ligne compacte (case, « Ajouter l'ampoule », modèle ou liste, prix),
 * dans une carte au fond crème comme les add-ons mais sans leurs classes
 * `wc-pao-*`, pour que le JS de Product Add-Ons ne la prenne pas pour une des siennes.
 *
 * La case reste toujours décochée par défaut : une option payante ne peut pas
 * être pré-cochée (droit de la consommation UE). Avec plusieurs ampoules, une
 * liste à côté de la case permet de choisir, Poire présélectionnée.
 * La case porte l'ID de l'ampoule choisie (le JS le tient à jour) : c'est ce que
 * lisent le bouton principal et la barre sticky. `sapi_ampoule_choice` sert de
 * secours sans JS.
 */
add_action('woocommerce_before_add_to_cart_button', function () {
  global $product;
  if (!$product instanceof WC_Product) return;
  $amps = sapi_ampoules_for($product);
  if (!$amps) return;
  $dec = wc_get_price_decimals();
  $default = $amps[0];
  $default_price = (float) wc_get_price_to_display($default);
  ?>
  <div class="sapi-ampoule-addon">
    <label class="sapi-ampoule-addon__field">
      <input type="checkbox" name="sapi_add_ampoule" value="<?php echo esc_attr($default->get_id()); ?>" data-price="<?php echo esc_attr(wc_format_decimal($default_price, $dec)); ?>">
      <span class="sapi-ampoule-addon__text"><span class="sapi-ampoule-addon__long"><?php esc_html_e("Ajouter l'ampoule", 'theme-sapi-maison'); ?></span><span class="sapi-ampoule-addon__short"><?php esc_html_e('Ampoule', 'theme-sapi-maison'); ?></span></span>
      <?php if (count($amps) === 1) : ?>
        <span class="sapi-ampoule-addon__model"><?php echo esc_html(sapi_ampoule_short_name($default)); ?></span>
      <?php endif; ?>
    </label>
    <?php if (count($amps) > 1) : ?>
      <select class="sapi-ampoule-addon__select" name="sapi_ampoule_choice" aria-label="<?php esc_attr_e("Choix de l'ampoule", 'theme-sapi-maison'); ?>">
        <?php foreach ($amps as $i => $amp) :
          $p = (float) wc_get_price_to_display($amp); ?>
          <option value="<?php echo esc_attr($amp->get_id()); ?>" data-price="<?php echo esc_attr(wc_format_decimal($p, $dec)); ?>" data-price-html="<?php echo esc_attr(wp_strip_all_tags(wc_price($p))); ?>"<?php selected($i, 0); ?>><?php echo esc_html(sapi_ampoule_short_name($amp)); ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <span class="sapi-ampoule-addon__price">+<?php echo wp_kses_post(wc_price($default_price)); ?></span>
  </div>
  <?php
});

add_action('wp_enqueue_scripts', function () {
  if (!function_exists('is_product') || !is_product()) return;
  $path = get_template_directory() . '/assets/sapi-ampoule-optin.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-ampoule-optin', get_template_directory_uri() . '/assets/sapi-ampoule-optin.js', ['jquery'], filemtime($path), true);
});

/* Ajout de l'ampoule au panier en même temps que le luminaire */
add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity) {
  static $running = false;
  if ($running) return;
  if (empty($_REQUEST['sapi_add_ampoule'])) return;

  $requested = absint($_REQUEST['sapi_add_ampoule']);
  // Sans JS, la case garde l'ID par défaut : la liste fait alors foi.
  if (!empty($_REQUEST['sapi_ampoule_choice'])) $requested = absint($_REQUEST['sapi_ampoule_choice']);
  // Une seule fois par requête, même si add_to_cart est rejoué.
  unset($_POST['sapi_add_ampoule'], $_GET['sapi_add_ampoule'], $_REQUEST['sapi_add_ampoule'],
        $_POST['sapi_ampoule_choice'], $_GET['sapi_ampoule_choice'], $_REQUEST['sapi_ampoule_choice']);

  // La valeur reçue n'est qu'un choix : elle doit figurer parmi les ampoules du luminaire.
  $amp = null;
  foreach (sapi_ampoules_for($product_id) as $candidate) {
    if ($candidate->get_id() === $requested) { $amp = $candidate; break; }
  }
  if (!$amp) return;

  $running = true;
  WC()->cart->add_to_cart($amp->get_id(), max(1, (int) $quantity));
  $running = false;
}, 20, 3);
