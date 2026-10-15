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

/* Affichage de la case, dans form.cart, juste avant le bouton */
add_action('woocommerce_before_add_to_cart_button', function () {
  global $product;
  if (!$product instanceof WC_Product) return;
  $amp = sapi_ampoule_for($product);
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
