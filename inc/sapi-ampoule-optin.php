<?php
/**
 * Case « Ajouter l'ampoule recommandée » sur la fiche produit
 *
 * Étape 1 du chantier « ampoules + bundle » (octobre 2026). La vitrine du
 * mini-panier est dans sapi-cart-suggestions.php.
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

  // 25/11/2026 : une variation peut avoir sa propre liste (champ « Ampoules
  // compatibles » de la variation, la recommandée en premier). Sans réglage,
  // elle reprend les ventes croisées du produit : rien ne change tant que
  // Robin n'a rien rempli.
  if ($product->is_type('variation')) {
    $ids = sapi_ampoules_variation_ids($product);
    if ($ids) {
      $list = [];
      foreach ($ids as $id) {
        $amp = wc_get_product((int) $id);
        if ($amp && sapi_ampoule_is_catalogue($amp) && sapi_ampoule_is_sellable($amp)) $list[] = $amp;
      }
      if ($list) return $list;
    }
  }

  if ($product->get_parent_id()) $product = wc_get_product($product->get_parent_id());
  if (!$product || !sapi_ampoule_is_luminaire($product->get_id())) return [];

  $list = [];
  foreach ($product->get_cross_sell_ids() as $cs_id) {
    $amp = wc_get_product($cs_id);
    if ($amp && sapi_ampoule_is_catalogue($amp) && sapi_ampoule_is_sellable($amp)) $list[] = $amp;
  }
  usort($list, function ($a, $b) {
    $pa = stripos($a->get_name(), 'poire') !== false ? 0 : 1;
    $pb = stripos($b->get_name(), 'poire') !== false ? 0 : 1;
    return $pa <=> $pb; // usort est stable depuis PHP 8 : l'ordre de la fiche est gardé sinon
  });
  return $list;
}

/**
 * IDs des ampoules réglées sur CETTE variation (meta _sapi_ampoules, champ
 * « Ampoules compatibles » du panneau des variations, la recommandée en
 * premier). [] si rien n'est réglé : la variation garde la liste du produit.
 */
function sapi_ampoules_variation_ids($variation) {
  if (!$variation || !$variation->is_type('variation')) return [];
  $ids = get_post_meta($variation->get_id(), '_sapi_ampoules', true);
  return is_array($ids) ? array_values(array_filter(array_map('intval', $ids))) : [];
}

/** Prix court d'une ampoule, en texte : « 10 € » plutôt que « 10,00 € », « 8,50 € » inchangé. */
function sapi_amp_price_short($amount) {
  $txt = html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
  return preg_replace('/[,.]00(?=\D*$)/u', '', $txt);
}

/** Une ampoule : produit de la catégorie « accessoires » dont le nom contient « ampoule ». */
function sapi_ampoule_is_catalogue($p) {
  if (!$p) return false;
  $cats = get_the_terms($p->get_id(), 'product_cat');
  if (!$cats || is_wp_error($cats) || !in_array('accessoires', wp_list_pluck($cats, 'slug'), true)) return false;
  return stripos(remove_accents($p->get_name()), 'ampoule') !== false;
}

/** Vendable en un clic : simple, achetable, en stock, pas « vendu à l'unité ». */
function sapi_ampoule_is_sellable($p) {
  return $p && $p->get_status() === 'publish' && $p->is_type('simple') && !$p->is_sold_individually() && $p->is_purchasable() && $p->is_in_stock();
}

/** Toutes les ampoules du catalogue (publiées), pour l'admin des variations. */
function sapi_ampoule_catalogue() {
  static $cache = null;
  if ($cache !== null) return $cache;
  $cache = [];
  foreach (wc_get_products(['status' => 'publish', 'limit' => -1, 'category' => ['accessoires']]) as $p) {
    if (sapi_ampoule_is_catalogue($p)) $cache[] = $p;
  }
  return $cache;
}

/**
 * Données d'une ampoule pour le panneau « Comparer les ampoules »
 * (assets/sapi-amp-picker.js). Tout vient de la fiche de l'ampoule :
 *  - la phrase = sa description courte ;
 *  - les caractéristiques = ses attributs cochés « Visible sur la page produit » ;
 *  - les photos = sa photo d'ambiance (si elle existe) et son image produit.
 */
function sapi_ampoule_card_data($amp) {
  $specs = [];
  foreach ($amp->get_attributes() as $attr) {
    if (!$attr->get_visible()) continue;
    $values = $attr->is_taxonomy()
      ? wc_get_product_terms($amp->get_id(), $attr->get_name(), ['fields' => 'names'])
      : $attr->get_options();
    if (!$values) continue;
    $specs[] = [wc_attribute_label($attr->get_name(), $amp), implode(', ', $values)];
  }
  $amb = function_exists('sapi_get_product_photo_ids') ? sapi_get_product_photo_ids($amp->get_id(), 'ambiance', 1) : [];
  $price = (float) wc_get_price_to_display($amp);
  return [
    'id'        => $amp->get_id(),
    'name'      => html_entity_decode(sapi_ampoule_short_name($amp), ENT_QUOTES, 'UTF-8'),
    'price'     => $price,
    'priceHtml' => html_entity_decode(wp_strip_all_tags(wc_price($price)), ENT_QUOTES, 'UTF-8'),
    'img'       => $amp->get_image_id() ? (string) wp_get_attachment_image_url($amp->get_image_id(), 'woocommerce_thumbnail') : '',
    'amb'       => !empty($amb) ? (string) wp_get_attachment_image_url((int) $amb[0], 'woocommerce_thumbnail') : '',
    'why'       => trim(html_entity_decode(wp_strip_all_tags($amp->get_short_description()), ENT_QUOTES, 'UTF-8')),
    'specs'     => $specs,
  ];
}

/** Toutes les ampoules vendables, au format du panneau, indexées par ID. */
function sapi_ampoule_cards() {
  static $cache = null;
  if ($cache !== null) return $cache;
  /* Chargé sur toutes les pages (le volet y est) : mis en transient, vidé à
     chaque modification de produit ou de stock (une ampoule ou une autre). */
  $stored = get_transient('sapi_ampoule_cards');
  if (is_array($stored)) return $cache = $stored;
  $cache = [];
  foreach (sapi_ampoule_catalogue() as $amp) {
    if (sapi_ampoule_is_sellable($amp)) $cache[$amp->get_id()] = sapi_ampoule_card_data($amp);
  }
  set_transient('sapi_ampoule_cards', $cache, 12 * HOUR_IN_SECONDS);
  return $cache;
}
function sapi_ampoule_cards_flush() { delete_transient('sapi_ampoule_cards'); }
add_action('save_post_product', 'sapi_ampoule_cards_flush');
add_action('woocommerce_update_product', 'sapi_ampoule_cards_flush');
add_action('woocommerce_product_set_stock_status', 'sapi_ampoule_cards_flush');
add_action('edited_term', 'sapi_ampoule_cards_flush');


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
 * Une ligne « AMPOULE » + pilule à cocher (« Ajouter Globe +10 € »), même modèle
 * que les autres options. assets/sapi-product-options.js la range dans la carte
 * des options. Pas de classes `wc-pao-*`, pour que le JS de Product Add-Ons ne
 * la prenne pas pour une des siennes.
 *
 * La case reste toujours décochée par défaut : une option payante ne peut pas
 * être pré-cochée (droit de la consommation UE). Avec plusieurs ampoules, le nom
 * de l'ampoule devient un bouton qui ouvre le panneau de comparaison, la
 * recommandée présélectionnée (sans JS : une liste).
 * La case porte l'ID de l'ampoule choisie (le JS le tient à jour) : c'est ce que
 * lisent le bouton principal et la barre sticky. `sapi_ampoule_choice` sert de
 * secours sans JS.
 */
add_action('woocommerce_before_add_to_cart_button', function () {
  global $product;
  if (!$product instanceof WC_Product) return;
  $amps = sapi_ampoules_for($product);

  /* 25/11/2026 : la liste suit la variation choisie. Le JS reçoit la liste de
     chaque variation qui a la sienne (data-by-var) et la liste du produit
     (data-default), et reconstruit le choix à chaque found_variation. */
  $by_var = [];
  $ids_all = array_map(function ($a) { return $a->get_id(); }, $amps);
  if ($product->is_type('variable')) {
    foreach ($product->get_children() as $vid) {
      $variation = wc_get_product($vid);
      if (!$variation) continue;
      $v_amps = sapi_ampoules_for($variation);
      if (!$v_amps) continue;
      $v_ids = array_map(function ($a) { return $a->get_id(); }, $v_amps);
      if ($v_ids === array_map(function ($a) { return $a->get_id(); }, $amps)) continue; // même liste que le produit
      $by_var[$vid] = $v_ids;
      $ids_all = array_merge($ids_all, $by_var[$vid]);
    }
  }
  if (!$ids_all) return;
  $hidden_by_default = !$amps; // seules certaines variations ont une ampoule

  $dec = wc_get_price_decimals();
  $default = $amps ? $amps[0] : wc_get_product($ids_all[0]);
  $default_price = (float) wc_get_price_to_display($default);
  $first = preg_split('/\s+/', trim($product->get_name()))[0];
  ?>
  <div class="sapi-ampoule-addon"<?php echo $hidden_by_default ? ' hidden' : ''; ?>
       data-default="<?php echo esc_attr(wp_json_encode(array_map(function ($a) { return $a->get_id(); }, $amps))); ?>"
       data-by-var="<?php echo esc_attr(wp_json_encode((object) $by_var)); ?>"
       data-first="<?php echo esc_attr($first); ?>">
    <span class="sapi-ampoule-addon__name"><?php esc_html_e('Ampoule', 'theme-sapi-maison'); ?></span>
    <label class="sapi-ampoule-addon__pill">
      <input type="checkbox" class="sapi-ampoule-addon__input" name="sapi_add_ampoule" value="<?php echo esc_attr($default->get_id()); ?>" data-price="<?php echo esc_attr(wc_format_decimal($default_price, $dec)); ?>">
      <span class="sapi-ampoule-addon__box" aria-hidden="true"></span>
      <span class="sapi-ampoule-addon__text"><?php esc_html_e('Ajouter', 'theme-sapi-maison'); ?></span>
      <span class="sapi-ampoule-addon__model"<?php echo count($amps) > 1 ? ' hidden' : ''; ?>><?php echo esc_html(sapi_ampoule_short_name($default)); ?></span>
      <span class="sapi-amp-reco" hidden><?php esc_html_e('Reco', 'theme-sapi-maison'); ?></span>
      <?php /* Sans JS : la liste permet de choisir. Avec JS, elle est masquée et
         le choix se fait dans le panneau « Comparer ». */ ?>
      <select class="sapi-ampoule-addon__select" name="sapi_ampoule_choice" aria-label="<?php esc_attr_e("Choix de l'ampoule", 'theme-sapi-maison'); ?>"<?php echo count($amps) > 1 ? '' : ' hidden'; ?>>
        <?php foreach ($amps as $i => $amp) :
          $p = (float) wc_get_price_to_display($amp); ?>
          <option value="<?php echo esc_attr($amp->get_id()); ?>" data-price="<?php echo esc_attr(wc_format_decimal($p, $dec)); ?>" data-price-html="<?php echo esc_attr(sapi_amp_price_short($p)); ?>"<?php selected($i, 0); ?>><?php echo esc_html(sapi_ampoule_short_name($amp)); ?></option>
        <?php endforeach; ?>
      </select>
      <span class="sapi-ampoule-addon__price">+<?php echo esc_html(sapi_amp_price_short($default_price)); ?></span>
    </label>
    <button type="button" class="sapi-amp-compare sapi-ampoule-addon__compare" hidden aria-haspopup="dialog"><svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 5h10l-3-3M14 11H4l3 3"/></svg><?php esc_html_e('Comparer', 'theme-sapi-maison'); ?></button>
  </div>
  <?php
});

/* ─── Admin : « Ampoules compatibles » dans le panneau de chaque variation ───
   Une liste « Ampoule recommandée » + des cases « Autres compatibles ». Vide =
   les ventes croisées du produit. Les choix proposés sont les ampoules du
   catalogue (catégorie accessoires, nom contenant « ampoule »). Enregistré
   dans la meta _sapi_ampoules, la recommandée en premier. */
add_action('woocommerce_product_after_variable_attributes', function ($loop, $variation_data, $variation) {
  if (!sapi_ampoule_is_luminaire($variation->post_parent)) return;
  $catalogue = sapi_ampoule_catalogue();
  if (!$catalogue) return;
  $saved = get_post_meta($variation->ID, '_sapi_ampoules', true);
  $saved = is_array($saved) ? array_map('intval', $saved) : [];
  $reco = $saved ? $saved[0] : 0;
  ?>
  <div class="form-row form-row-full sapi-var-ampoules" style="border-top:1px solid #eee;padding-top:10px;margin-top:6px">
    <p style="margin:0 0 6px"><strong><?php esc_html_e('Ampoules compatibles', 'theme-sapi-maison'); ?></strong>
      <span style="color:#777"><?php esc_html_e('(vide = celles des ventes croisées du produit)', 'theme-sapi-maison'); ?></span></p>
    <label style="display:block;margin-bottom:6px"><?php esc_html_e('Ampoule recommandée', 'theme-sapi-maison'); ?>
      <select name="sapi_amp_reco[<?php echo esc_attr($loop); ?>]" style="margin-left:6px">
        <option value="0"><?php esc_html_e('— Celles du produit —', 'theme-sapi-maison'); ?></option>
        <?php foreach ($catalogue as $amp) : ?>
          <option value="<?php echo esc_attr($amp->get_id()); ?>"<?php selected($reco, $amp->get_id()); ?>><?php echo esc_html($amp->get_name()); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <span><?php esc_html_e('Autres ampoules compatibles :', 'theme-sapi-maison'); ?></span>
    <?php foreach ($catalogue as $amp) : $id = $amp->get_id(); ?>
      <label style="margin-left:10px"><input type="checkbox" name="sapi_amp_autres[<?php echo esc_attr($loop); ?>][]" value="<?php echo esc_attr($id); ?>"<?php checked(in_array($id, array_slice($saved, 1), true)); ?>> <?php echo esc_html(sapi_ampoule_short_name($amp)); ?></label>
    <?php endforeach; ?>
  </div>
  <?php
}, 20, 3);

add_action('woocommerce_save_product_variation', function ($variation_id, $i) {
  if (!isset($_POST['sapi_amp_reco'][$i]) && !isset($_POST['sapi_amp_autres'][$i])) return; // champ absent : on ne touche à rien
  $valid = array_map(function ($p) { return $p->get_id(); }, sapi_ampoule_catalogue());
  $reco = isset($_POST['sapi_amp_reco'][$i]) ? absint($_POST['sapi_amp_reco'][$i]) : 0;
  $autres = isset($_POST['sapi_amp_autres'][$i]) ? array_map('absint', (array) wp_unslash($_POST['sapi_amp_autres'][$i])) : [];
  if (!$reco && $autres) $reco = array_shift($autres); // des cases sans recommandée : la première fait office
  $list = $reco ? array_values(array_unique(array_merge([$reco], $autres))) : [];
  $list = array_values(array_filter($list, function ($id) use ($valid) { return in_array($id, $valid, true); }));
  if ($list) update_post_meta($variation_id, '_sapi_ampoules', $list);
  else delete_post_meta($variation_id, '_sapi_ampoules');
}, 10, 2);

/* Panneau « Comparer les ampoules » : sur toutes les pages (le volet panier y est),
   avec les données des ampoules. Les scripts qui l'ouvrent en dépendent. */
add_action('wp_enqueue_scripts', function () {
  if (is_admin() || !function_exists('WC')) return;
  $path = get_template_directory() . '/assets/sapi-amp-picker.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-amp-picker', get_template_directory_uri() . '/assets/sapi-amp-picker.js', [], filemtime($path), true);
  wp_localize_script('sapi-amp-picker', 'SAPI_AMPOULES', (object) sapi_ampoule_cards());
}, 5);

add_action('wp_enqueue_scripts', function () {
  if (!function_exists('is_product') || !is_product()) return;
  $path = get_template_directory() . '/assets/sapi-ampoule-optin.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-ampoule-optin', get_template_directory_uri() . '/assets/sapi-ampoule-optin.js', ['jquery', 'sapi-amp-picker'], filemtime($path), true);
});

/* Ajout de l'ampoule au panier en même temps que le luminaire */
add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity, $variation_id = 0) {
  static $running = false;
  if ($running) return;
  if (empty($_REQUEST['sapi_add_ampoule'])) return;

  $requested = absint($_REQUEST['sapi_add_ampoule']);
  // Sans JS, la case garde l'ID par défaut : la liste fait alors foi.
  if (!empty($_REQUEST['sapi_ampoule_choice'])) $requested = absint($_REQUEST['sapi_ampoule_choice']);
  // Une seule fois par requête, même si add_to_cart est rejoué.
  unset($_POST['sapi_add_ampoule'], $_GET['sapi_add_ampoule'], $_REQUEST['sapi_add_ampoule'],
        $_POST['sapi_ampoule_choice'], $_GET['sapi_ampoule_choice'], $_REQUEST['sapi_ampoule_choice']);

  // La valeur reçue n'est qu'un choix : elle doit figurer parmi les ampoules du
  // luminaire, celles de SA variation quand elle en a (sinon celles du produit).
  $amp = null;
  foreach (sapi_ampoules_for($variation_id ? (int) $variation_id : $product_id) as $candidate) {
    if ($candidate->get_id() === $requested) { $amp = $candidate; break; }
  }
  if (!$amp) return;

  $running = true;
  WC()->cart->add_to_cart($amp->get_id(), max(1, (int) $quantity));
  $running = false;
}, 20, 4);
