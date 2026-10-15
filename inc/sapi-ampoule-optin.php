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

/** Ampoule associée à un luminaire, ou null. */
function sapi_ampoule_for($product) {
  if (is_numeric($product)) $product = wc_get_product($product);
  if (!$product) return null;
  if ($product->get_parent_id()) $product = wc_get_product($product->get_parent_id());
  if (!$product || !sapi_ampoule_is_luminaire($product->get_id())) return null;

  foreach ($product->get_cross_sell_ids() as $cs_id) {
    $cats = get_the_terms($cs_id, 'product_cat');
    if (!$cats || is_wp_error($cats)) continue;
    if (!in_array('accessoires', wp_list_pluck($cats, 'slug'), true)) continue;
    $amp = wc_get_product($cs_id);
    if ($amp && $amp->is_type('simple') && !$amp->is_sold_individually()
        && $amp->is_purchasable() && $amp->is_in_stock()) {
      return $amp;
    }
  }
  return null;
}

/*
 * Affichage. Rendu dans form.cart juste avant le bouton (seul hook de WooCommerce
 * qui soit DANS le formulaire et toujours présent), puis déplacé en JS
 * (assets/sapi-ampoule-optin.js) dans le bloc des add-ons, sous « Couleur du
 * câble et du pavillon », pour se lire comme une option de plus et passer
 * au-dessus du sous-total. Si le JS ne tourne pas, la case reste au-dessus du
 * bouton et fonctionne quand même.
 * Le bloc reprend la présentation des add-ons (fond crème, titre majuscule à
 * pastille orange, champ blanc) sans leurs classes `wc-pao-*`, pour que le JS
 * de Product Add-Ons ne le prenne pas pour un des siens.
 */
add_action('woocommerce_before_add_to_cart_button', function () {
  global $product;
  if (!$product instanceof WC_Product) return;
  $amp = sapi_ampoule_for($product);
  if (!$amp) return;
  $price = (float) wc_get_price_to_display($amp);
  ?>
  <div class="sapi-ampoule-addon">
    <p class="sapi-ampoule-addon__name"><?php esc_html_e('Ampoule', 'theme-sapi-maison'); ?></p>
    <label class="sapi-ampoule-addon__field">
      <input type="checkbox" name="sapi_add_ampoule" value="<?php echo esc_attr($amp->get_id()); ?>" data-price="<?php echo esc_attr(wc_format_decimal($price, wc_get_price_decimals())); ?>">
      <span class="sapi-ampoule-addon__text">
        <?php
        /* translators: %s : nom de l'ampoule */
        printf(esc_html__("Ajouter l'ampoule recommandée : %s", 'theme-sapi-maison'), esc_html($amp->get_name()));
        ?>
        <span class="sapi-ampoule-addon__price">(+<?php echo wp_kses_post(wc_price($price)); ?>)</span>
      </span>
    </label>
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
  // Une seule fois par requête, même si add_to_cart est rejoué.
  unset($_POST['sapi_add_ampoule'], $_GET['sapi_add_ampoule'], $_REQUEST['sapi_add_ampoule']);

  $amp = sapi_ampoule_for($product_id);
  if (!$amp || $amp->get_id() !== $requested) return;

  $running = true;
  WC()->cart->add_to_cart($amp->get_id(), max(1, (int) $quantity));
  $running = false;
}, 20, 3);
