<?php
/**
 * Centre d'assemblage — notices de montage (nov. 2026).
 *
 * - Type de contenu « notice_montage », public, sous /assemblage/<notice>/.
 *   L'archive /assemblage/ est la page d'accueil du centre (archive-notice_montage.php).
 * - Champs ACF Pro déclarés en code (répéteurs : versions et étapes).
 * - Une notice relie un ou plusieurs produits (champ « produits »). La relation
 *   vit ICI, côté WordPress : la fiche produit et le hub ne font que la lire.
 * - /montage redirige vers /assemblage/?src=qr : c'est l'URL gravée dans le
 *   QR code des colis. Redirection 302 pour pouvoir changer la cible un jour
 *   sans regraver les bois.
 */

if (!defined('ABSPATH')) exit;

/* ── Type de contenu ─────────────────────────────────────────────────────── */
function sapi_register_cpt_notice_montage() {
  register_post_type('notice_montage', [
    'labels' => [
      'name'               => 'Notices de montage',
      'singular_name'      => 'Notice de montage',
      'add_new'            => 'Ajouter une notice',
      'add_new_item'       => 'Ajouter une notice de montage',
      'edit_item'          => 'Modifier la notice',
      'new_item'           => 'Nouvelle notice',
      'view_item'          => 'Voir la notice',
      'search_items'       => 'Rechercher une notice',
      'not_found'          => 'Aucune notice trouvée',
      'not_found_in_trash' => 'Aucune notice dans la corbeille',
      'all_items'          => 'Toutes les notices',
    ],
    'public'        => true,
    'show_ui'       => true,
    'show_in_menu'  => true,
    'show_in_rest'  => true,   // lecture par le hub (API REST)
    'menu_position' => 26,
    'menu_icon'     => 'dashicons-hammer',
    'supports'      => ['title', 'revisions'],
    'has_archive'   => 'assemblage',
    'rewrite'       => ['slug' => 'assemblage', 'with_front' => false],
  ]);
}
add_action('init', 'sapi_register_cpt_notice_montage');

// Les règles de réécriture ne connaissent le nouveau type qu'après un flush.
// On le fait une seule fois par version de ce fichier, pas à chaque requête.
add_action('init', function () {
  $v = 'notice-montage-1';
  if (get_option('sapi_notice_rewrite_v') !== $v) {
    flush_rewrite_rules(false);
    update_option('sapi_notice_rewrite_v', $v, true);
  }
}, 99);

/* ── URL du QR code : /montage → /assemblage/?src=qr ─────────────────────── */
add_action('template_redirect', function () {
  $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
  $base = trim((string) parse_url(home_url(), PHP_URL_PATH), '/');
  if ($base !== '' && strpos($path, $base . '/') === 0) $path = substr($path, strlen($base) + 1);
  if (strtolower($path) === 'montage') {
    wp_redirect(home_url('/assemblage/?src=qr'), 302);
    exit;
  }
}, 1);

/* ── Champs ACF ──────────────────────────────────────────────────────────── */
function sapi_notice_symboles() {
  $s = ['■', '□', '▶', '◁', '●', '○', '▲', '△', '◆', '◇', '★', '☆', '⟳'];
  return array_combine($s, $s);
}

function sapi_register_acf_notice_montage() {
  if (!function_exists('acf_add_local_field_group')) return;

  acf_add_local_field_group([
    'key'      => 'group_notice_montage',
    'title'    => 'Notice de montage',
    'position' => 'acf_after_title',
    'style'    => 'default',
    'show_in_rest' => 1,   // champs lisibles par le hub via /wp-json/wp/v2/notice_montage
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'notice_montage']]],
    'fields'   => [

      // ── En-tête ──
      ['key' => 'field_nm_tab_entete', 'label' => 'En-tête', 'type' => 'tab'],
      [
        'key' => 'field_nm_produits', 'label' => 'Produits reliés', 'name' => 'produits',
        'type' => 'relationship', 'post_type' => ['product'], 'filters' => ['search', 'taxonomy'],
        'return_format' => 'id', 'required' => 1,
        'instructions' => 'Les produits qui se montent avec cette notice. Leur fiche affichera l\'encart vidéo.',
      ],
      [
        'key' => 'field_nm_video', 'label' => 'Vidéo YouTube', 'name' => 'video_youtube', 'type' => 'url',
        'instructions' => 'Lien de la vidéo de montage : youtube.com/watch?v=…, youtu.be/… ou youtube.com/shorts/…',
      ],
      ['key' => 'field_nm_difficulte', 'label' => 'Difficulté', 'name' => 'difficulte', 'type' => 'select',
       'choices' => ['Facile' => 'Facile', 'Moyen' => 'Moyen', 'Délicat' => 'Délicat'], 'default_value' => 'Facile', 'wrapper' => ['width' => 33]],
      ['key' => 'field_nm_duree', 'label' => 'Durée', 'name' => 'duree', 'type' => 'text', 'placeholder' => '15 à 30 min', 'wrapper' => ['width' => 33]],
      ['key' => 'field_nm_outils', 'label' => 'Outils', 'name' => 'outils', 'type' => 'text', 'placeholder' => 'Sans outil', 'wrapper' => ['width' => 33]],

      // ── Étape 1 : électricité, une ligne par version ──
      ['key' => 'field_nm_tab_elec', 'label' => '1 · Électricité', 'type' => 'tab'],
      [
        'key' => 'field_nm_versions', 'label' => 'Versions', 'name' => 'versions', 'type' => 'repeater',
        'layout' => 'block', 'button_label' => 'Ajouter une version', 'min' => 1,
        'instructions' => 'Une ligne par version (suspension, applique, lampadaire…). L\'assemblage reste commun.',
        'sub_fields' => [
          ['key' => 'field_nm_v_label', 'label' => 'Nom de la version', 'name' => 'label', 'type' => 'text', 'placeholder' => 'Suspension', 'required' => 1, 'wrapper' => ['width' => 34]],
          ['key' => 'field_nm_v_lieu', 'label' => 'Installation', 'name' => 'lieu', 'type' => 'select',
           'choices' => ['plafond' => 'Au plafond', 'mur' => 'Au mur', 'mur_plafond' => 'Au mur ou au plafond', 'sol' => 'Au sol', 'meuble' => 'Sur un meuble'], 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_courant', 'label' => 'Rappel « coupez le courant »', 'name' => 'coupure', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_vignette', 'label' => 'Vignette', 'name' => 'vignette', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail',
           'instructions' => 'Vide : photo du premier produit relié.', 'wrapper' => ['width' => 34]],
          ['key' => 'field_nm_v_image', 'label' => 'Illustration', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_texte', 'label' => 'Consignes', 'name' => 'texte', 'type' => 'textarea', 'rows' => 4, 'new_lines' => 'br', 'wrapper' => ['width' => 33]],
        ],
      ],

      // ── Étape 2 : assemblage ──
      ['key' => 'field_nm_tab_assemblage', 'label' => '2 · Assemblage', 'type' => 'tab'],
      [
        'key' => 'field_nm_etapes', 'label' => 'Étapes', 'name' => 'etapes', 'type' => 'repeater',
        'layout' => 'block', 'button_label' => 'Ajouter une étape', 'min' => 1,
        'instructions' => 'Dans l\'ordre de l\'énigme. Le mode guidé reprend les mêmes étapes avec l\'indice comme texte.',
        'sub_fields' => [
          ['key' => 'field_nm_e_a', 'label' => 'Symbole 1', 'name' => 'symbole_a', 'type' => 'select', 'choices' => sapi_notice_symboles(), 'required' => 1, 'wrapper' => ['width' => 20]],
          ['key' => 'field_nm_e_liaison', 'label' => 'Liaison', 'name' => 'liaison', 'type' => 'text', 'default_value' => 'et', 'wrapper' => ['width' => 20]],
          ['key' => 'field_nm_e_b', 'label' => 'Symbole 2', 'name' => 'symbole_b', 'type' => 'select', 'choices' => sapi_notice_symboles(), 'allow_null' => 1, 'wrapper' => ['width' => 20]],
          ['key' => 'field_nm_e_temps', 'label' => 'Moment dans la vidéo', 'name' => 'temps_video', 'type' => 'text', 'placeholder' => '1:05', 'instructions' => 'Format minutes:secondes', 'wrapper' => ['width' => 20]],
          ['key' => 'field_nm_e_photo', 'label' => 'Photo', 'name' => 'photo', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'wrapper' => ['width' => 20]],
          ['key' => 'field_nm_e_indice', 'label' => 'Indice / explication', 'name' => 'indice', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '', 'required' => 1],
        ],
      ],

      // ── Étape 3 : abat-jour ──
      ['key' => 'field_nm_tab_abatjour', 'label' => '3 · Abat-jour', 'type' => 'tab'],
      ['key' => 'field_nm_aj_texte', 'label' => 'Consignes', 'name' => 'abatjour_texte', 'type' => 'textarea', 'rows' => 4, 'new_lines' => 'br'],
      ['key' => 'field_nm_aj_image', 'label' => 'Illustration', 'name' => 'abatjour_image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium'],
    ],
  ]);
}
add_action('acf/init', 'sapi_register_acf_notice_montage');

/* ── Helpers ─────────────────────────────────────────────────────────────── */

/** Notice publiée reliée à un produit, ou 0. */
function sapi_notice_for_product($product_id) {
  $product_id = (int) $product_id;
  if (!$product_id) return 0;
  $q = get_posts([
    'post_type'      => 'notice_montage',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'meta_query'     => [['key' => 'produits', 'value' => '"' . $product_id . '"', 'compare' => 'LIKE']],
  ]);
  return $q ? (int) $q[0] : 0;
}

/** Id YouTube depuis une URL watch, youtu.be, shorts ou embed. */
function sapi_notice_youtube_id($url) {
  if (!$url) return '';
  if (preg_match('~(?:youtu\.be/|v=|/embed/|/shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
  return '';
}

/** « 1:05 » → 65 ; vide si illisible. */
function sapi_notice_seconds($t) {
  $t = trim((string) $t);
  if ($t === '') return '';
  $parts = array_map('intval', explode(':', $t));
  $s = 0;
  foreach ($parts as $p) $s = $s * 60 + $p;
  return $s;
}

/** Même découpe que product-name-formatter.js : prénom Montserrat + reste Square Peg. */
function sapi_notice_format_name($name) {
  $name = trim(html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8'));
  if ($name === '') return '';
  $words = preg_split('/\s+/', $name, 2);
  if (count($words) < 2) return '<span class="product-firstname">' . esc_html($name) . '</span>';
  if (preg_match("/^(la|le|les)$/i", $words[0]) || preg_match("/^l['’]/i", $words[0])) {
    return '<span class="product-restname">' . esc_html($name) . '</span>';
  }
  return '<span class="product-firstname">' . esc_html($words[0]) . '</span> <span class="product-restname">' . esc_html($words[1]) . '</span>';
}

/** Titre brut (sans les entités typographiques de wptexturize). */
function sapi_notice_title($post_id) {
  return html_entity_decode(get_the_title($post_id), ENT_QUOTES, 'UTF-8');
}

/** Libellé lisible d'un lieu d'installation. */
function sapi_notice_lieu_label($lieu) {
  $l = ['plafond' => 'Au plafond', 'mur' => 'Au mur', 'mur_plafond' => 'Au mur ou au plafond', 'sol' => 'Au sol', 'meuble' => 'Sur un meuble'];
  return $l[$lieu] ?? '';
}

/* ── Encart sur la fiche produit ─────────────────────────────────────────── */
function sapi_notice_render_encart($product_id) {
  $notice_id = sapi_notice_for_product($product_id);
  if (!$notice_id || !function_exists('get_field')) return;

  $yt    = sapi_notice_youtube_id(get_field('video_youtube', $notice_id));
  $meta  = array_filter([get_field('difficulte', $notice_id), get_field('duree', $notice_id), get_field('outils', $notice_id)]);
  $url   = get_permalink($notice_id);
  $thumb = $yt ? 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' : get_the_post_thumbnail_url($product_id, 'medium');
  ?>
  <a class="sapi-notice-encart" href="<?php echo esc_url($url); ?>">
    <span class="sapi-notice-encart__thumb">
      <?php if ($thumb) : ?><img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" width="200" height="112"><?php endif; ?>
      <?php if ($yt) : ?><svg class="sapi-notice-encart__play" viewBox="0 0 76 76" aria-hidden="true"><circle cx="38" cy="38" r="37"/><path d="M31 25 L53 38 L31 51 Z"/></svg><?php endif; ?>
    </span>
    <span class="sapi-notice-encart__body">
      <span class="sapi-notice-encart__title"><?php echo $yt ? 'Le montage en vidéo' : 'La notice de montage'; ?></span>
      <?php if ($meta) : ?><span class="sapi-notice-encart__meta"><?php echo esc_html(implode(' · ', $meta)); ?></span><?php endif; ?>
      <span class="sapi-notice-encart__link">Voir la notice de montage →</span>
    </span>
  </a>
  <?php
}

/* ── Assets ──────────────────────────────────────────────────────────────── */
add_action('wp_enqueue_scripts', function () {
  $on_centre = is_singular('notice_montage') || is_post_type_archive('notice_montage');
  $on_product = function_exists('is_product') && is_product();
  if (!$on_centre && !$on_product) return;

  $css = get_template_directory() . '/assets/centre-assemblage.css';
  if (file_exists($css)) {
    wp_enqueue_style('sapi-centre-assemblage', get_template_directory_uri() . '/assets/centre-assemblage.css', ['sapi-maison-style'], filemtime($css));
  }
  if ($on_centre) {
    $js = get_template_directory() . '/assets/centre-assemblage.js';
    if (file_exists($js)) {
      wp_enqueue_script('sapi-centre-assemblage', get_template_directory_uri() . '/assets/centre-assemblage.js', [], filemtime($js), true);
    }
  }
}, 20);
