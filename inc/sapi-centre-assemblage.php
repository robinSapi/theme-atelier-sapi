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
/** Symboles proposés dans l'admin (copier-coller). Tout autre texte court marche : « 1 », « A1 »… */
function sapi_notice_symboles_aide() {
  return '□ ○ ▷ ◁ ☆ △ ◇ ⬠ — pleins : ■ ● ▶ ◀ ★ ▲ ◆';
}

/**
 * Champs d'une étape d'assemblage, réutilisés pour l'assemblage commun et pour
 * l'assemblage propre à une version. $p préfixe les clés (uniques dans ACF).
 * Une étape est soit des paires (1 ou 2 lignes « □ et □ », répétées ×N),
 * soit une action (Complétez, Retournez ou texte libre) avec une précision.
 */
function sapi_notice_etape_fields($p) {
  $si_paires = [[['field' => $p . 'type', 'operator' => '!=', 'value' => 'action']]];
  $si_action = [[['field' => $p . 'type', 'operator' => '==', 'value' => 'action']]];
  return [
    ['key' => $p . 'type', 'label' => 'Type d\'étape', 'name' => 'type', 'type' => 'button_group',
     'choices' => ['paires' => 'Paires de symboles', 'action' => 'Action'], 'default_value' => 'paires', 'wrapper' => ['width' => 100]],
    ['key' => $p . 'a', 'label' => 'Symbole', 'name' => 'symbole_a', 'type' => 'text', 'maxlength' => 6, 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 14],
     'instructions' => sapi_notice_symboles_aide()],
    ['key' => $p . 'liaison', 'label' => 'Liaison', 'name' => 'liaison', 'type' => 'text', 'default_value' => 'et', 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 12]],
    ['key' => $p . 'b', 'label' => 'Symbole', 'name' => 'symbole_b', 'type' => 'text', 'maxlength' => 6, 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 14]],
    ['key' => $p . 'c', 'label' => '2e ligne (+)', 'name' => 'symbole_c', 'type' => 'text', 'maxlength' => 6, 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 14],
     'instructions' => 'Facultatif, comme « ● et ● + ○ et ○ »'],
    ['key' => $p . 'liaison_2', 'label' => 'Liaison', 'name' => 'liaison_2', 'type' => 'text', 'default_value' => 'et', 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 12]],
    ['key' => $p . 'd', 'label' => 'Symbole', 'name' => 'symbole_d', 'type' => 'text', 'maxlength' => 6, 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 14]],
    ['key' => $p . 'fois', 'label' => 'Répéter', 'name' => 'fois', 'type' => 'number', 'min' => 1, 'max' => 50, 'prepend' => '×', 'placeholder' => '1', 'conditional_logic' => $si_paires, 'wrapper' => ['width' => 20]],
    ['key' => $p . 'action', 'label' => 'Action', 'name' => 'action', 'type' => 'select',
     'choices' => ['completez' => 'Complétez', 'retournez' => 'Retournez', 'libre' => 'Autre (texte libre)'], 'default_value' => 'completez', 'conditional_logic' => $si_action, 'wrapper' => ['width' => 30]],
    ['key' => $p . 'action_texte', 'label' => 'Texte de l\'action', 'name' => 'action_texte', 'type' => 'text', 'required' => 1, 'placeholder' => 'Poser le cercle de verrouillage',
     'conditional_logic' => [[['field' => $p . 'type', 'operator' => '==', 'value' => 'action'], ['field' => $p . 'action', 'operator' => '==', 'value' => 'libre']]], 'wrapper' => ['width' => 35]],
    ['key' => $p . 'precision', 'label' => 'Précision', 'name' => 'precision', 'type' => 'text', 'placeholder' => 'l\'intérieur', 'conditional_logic' => $si_action, 'wrapper' => ['width' => 35]],
    ['key' => $p . 'photo', 'label' => 'Image', 'name' => 'photo', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'wrapper' => ['width' => 20],
     'instructions' => 'Facultatif : dessin d\'une pièce, photo.'],
    ['key' => $p . 'temps', 'label' => 'Moment dans la vidéo', 'name' => 'temps_video', 'type' => 'text', 'placeholder' => '1:05', 'instructions' => 'minutes:secondes', 'wrapper' => ['width' => 20]],
    ['key' => $p . 'indice', 'label' => 'Indice / explication', 'name' => 'indice', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '', 'wrapper' => ['width' => 60],
     'instructions' => 'Facultatif. En mode énigme, il s\'affiche quand on touche l\'étape.'],
  ];
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
        'bidirectional' => 1, 'bidirectional_target' => ['field_prod_notice_montage'],
        'instructions' => 'Les produits qui se montent avec cette notice. Le lien se voit aussi dans chaque produit (encadré « Notice de montage »).',
      ],
      [
        'key' => 'field_nm_video', 'label' => 'Vidéo YouTube', 'name' => 'video_youtube', 'type' => 'url',
        'instructions' => 'Vidéo de l\'assemblage commun : youtube.com/watch?v=…, youtu.be/… ou youtube.com/shorts/… Une version qui s\'assemble autrement a sa propre vidéo (onglet 1).',
      ],
      ['key' => 'field_nm_difficulte', 'label' => 'Difficulté', 'name' => 'difficulte', 'type' => 'select',
       'choices' => ['Facile' => 'Facile', 'Moyen' => 'Moyen', 'Délicat' => 'Délicat'], 'default_value' => 'Facile', 'wrapper' => ['width' => 33]],
      ['key' => 'field_nm_duree', 'label' => 'Durée', 'name' => 'duree', 'type' => 'text', 'default_value' => '15 à 30 min', 'wrapper' => ['width' => 33]],
      ['key' => 'field_nm_outils', 'label' => 'Outils requis', 'name' => 'outils', 'type' => 'text', 'default_value' => 'Aucun', 'wrapper' => ['width' => 33],
       'instructions' => 'Source unique : la fiche technique et le catalogue PDF lisent ces trois champs.'],

      // ── Étape 1 : électricité, une ligne par version ──
      ['key' => 'field_nm_tab_elec', 'label' => '1 · Versions et électricité', 'type' => 'tab'],
      [
        'key' => 'field_nm_versions', 'label' => 'Versions', 'name' => 'versions', 'type' => 'repeater',
        'layout' => 'block', 'button_label' => 'Ajouter une version', 'min' => 1,
        'instructions' => 'Une ligne par version (suspension, applique, lampadaire…). Elles partagent l\'assemblage de l\'onglet 2, sauf si « Assemblage propre » est coché.',
        'sub_fields' => [
          ['key' => 'field_nm_v_label', 'label' => 'Nom de la version', 'name' => 'label', 'type' => 'text', 'placeholder' => 'Suspension', 'required' => 1, 'wrapper' => ['width' => 34]],
          ['key' => 'field_nm_v_lieu', 'label' => 'Installation', 'name' => 'lieu', 'type' => 'select',
           'choices' => ['plafond' => 'Au plafond', 'mur' => 'Au mur', 'mur_plafond' => 'Au mur ou au plafond', 'sol' => 'Au sol', 'meuble' => 'Sur un meuble'], 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_courant', 'label' => 'Rappel « coupez le courant »', 'name' => 'coupure', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_vignette', 'label' => 'Vignette', 'name' => 'vignette', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail',
           'instructions' => 'Vide : photo du premier produit relié.', 'wrapper' => ['width' => 34]],
          ['key' => 'field_nm_v_image', 'label' => 'Illustration', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_texte', 'label' => 'Consignes', 'name' => 'texte', 'type' => 'textarea', 'rows' => 4, 'new_lines' => 'br', 'wrapper' => ['width' => 33]],
          ['key' => 'field_nm_v_propre', 'label' => 'Assemblage propre', 'name' => 'assemblage_propre', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0,
           'instructions' => 'À cocher si cette version a sa propre énigme et sa propre vidéo.'],
          ['key' => 'field_nm_v_video', 'label' => 'Vidéo YouTube de cette version', 'name' => 'video_youtube', 'type' => 'url',
           'conditional_logic' => [[['field' => 'field_nm_v_propre', 'operator' => '==', 'value' => '1']]]],
          ['key' => 'field_nm_v_etapes', 'label' => 'Étapes de cette version', 'name' => 'etapes', 'type' => 'repeater',
           'layout' => 'block', 'button_label' => 'Ajouter une étape',
           'conditional_logic' => [[['field' => 'field_nm_v_propre', 'operator' => '==', 'value' => '1']]],
           'sub_fields' => sapi_notice_etape_fields('field_nm_ve_')],
        ],
      ],

      // ── Étape 2 : assemblage ──
      ['key' => 'field_nm_tab_assemblage', 'label' => '2 · Assemblage', 'type' => 'tab'],
      [
        'key' => 'field_nm_etapes', 'label' => 'Étapes', 'name' => 'etapes', 'type' => 'repeater',
        'layout' => 'block', 'button_label' => 'Ajouter une étape',
        'instructions' => 'Assemblage commun, dans l\'ordre de la plaquette. Une version qui s\'assemble autrement a ses propres étapes (onglet 1).',
        'sub_fields' => sapi_notice_etape_fields('field_nm_e_'),
      ],

      // ── Étape 3 : abat-jour ──
      ['key' => 'field_nm_tab_abatjour', 'label' => '3 · Abat-jour', 'type' => 'tab'],
      ['key' => 'field_nm_aj_texte', 'label' => 'Consignes', 'name' => 'abatjour_texte', 'type' => 'textarea', 'rows' => 4, 'new_lines' => 'br'],
      ['key' => 'field_nm_aj_image', 'label' => 'Illustration', 'name' => 'abatjour_image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium'],
    ],
  ]);
}
add_action('acf/init', 'sapi_register_acf_notice_montage');

/* ── Côté produit : choix de la notice (relation bidirectionnelle ACF) ───── */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) return;
  acf_add_local_field_group([
    'key'      => 'group_produit_notice_montage',
    'title'    => 'Notice de montage',
    'position' => 'side',
    'style'    => 'default',
    'menu_order' => 0,
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
    'fields'   => [[
      'key' => 'field_prod_notice_montage', 'label' => 'Notice', 'name' => 'notice_montage',
      'type' => 'post_object', 'post_type' => ['notice_montage'], 'return_format' => 'id',
      'allow_null' => 1, 'multiple' => 0, 'ui' => 1,
      'bidirectional' => 1, 'bidirectional_target' => ['field_nm_produits'],
      'instructions' => 'Une notice peut servir à plusieurs produits.',
    ]],
  ]);
});

/** Résumé de la notice sous le champ, dans l'encadré produit. */
add_action('add_meta_boxes_product', function () {
  add_meta_box('sapi-notice-resume', 'Résumé de la notice de montage', function ($post) {
    $nid = (int) get_post_meta($post->ID, 'notice_montage', true);
    if (!$nid || get_post_type($nid) !== 'notice_montage' || get_post_status($nid) === 'trash') $nid = sapi_notice_for_product($post->ID);
    if (!$nid) {
      echo '<p style="margin:0 0 10px;padding:8px 10px;border-left:4px solid #dba617;background:#fcf9e8">Aucune notice reliée : la fiche produit n\'affiche ni vidéo, ni difficulté, ni outils.</p>';
      $url = admin_url('post-new.php?post_type=notice_montage&sapi_produit=' . (int) $post->ID);
      echo '<a class="button" href="' . esc_url($url) . '">+ Créer une notice pour ce produit</a>';
      return;
    }
    $vers = (array) (get_field('versions', $nid) ?: []);
    $asm  = sapi_notice_assemblages($nid);
    $vi   = sapi_notice_version_for_product($nid, $post->ID);
    $inconnue = $vi === null;
    if ($inconnue) $vi = 0;
    $yt   = $inconnue ? sapi_notice_youtube_id(get_field('video_youtube', $nid)) : ($asm[$vi]['yt'] ?? '');
    $et   = $inconnue ? (array) (get_field('etapes', $nid) ?: []) : ($asm[$vi]['etapes'] ?? []);
    echo '<p style="margin:0 0 6px"><strong>' . esc_html(sapi_notice_title($nid)) . '</strong>' . (get_post_status($nid) !== 'publish' ? ' <em>(non publiée)</em>' : '') . '</p>';
    if ($yt) echo '<img src="' . esc_url('https://i.ytimg.com/vi/' . $yt . '/mqdefault.jpg') . '" alt="" style="width:100%;height:auto;border-radius:4px;margin-bottom:6px">';
    echo '<p style="margin:0 0 6px;line-height:1.6">' . ($yt ? '✓ Vidéo YouTube' : '✗ Pas de vidéo') . '<br>'
      . count($vers) . ' version' . (count($vers) > 1 ? 's' : '') . ($vers ? ' : ' . esc_html(implode(', ', wp_list_pluck($vers, 'label'))) : '') . '<br>'
      . count($et) . ' étape' . (count($et) > 1 ? 's' : '') . ' d\'assemblage'
      . ($inconnue && count($vers) > 1 ? '<br><span style="color:#b32d2e">Version non déterminée : la catégorie du produit ne correspond à aucune version.</span>' : '')
      . (!$inconnue && count($vers) > 1 ? ' (version « ' . esc_html($vers[$vi]['label'] ?? '') . ' »' . (!empty($asm[$vi]['propre']) ? ', assemblage propre' : '') . ')' : '') . '</p>';
    $ligne = sapi_notice_meta_line($nid);
    if ($ligne) echo '<p style="margin:0 0 6px"><strong>' . esc_html($ligne) . '</strong></p>';
    $autres = array_diff(array_map('intval', (array) (get_field('produits', $nid) ?: [])), [(int) $post->ID]);
    if ($autres) {
      $liens = array_map(function ($id) { return '<a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html(sapi_notice_title($id) . sapi_notice_cat_suffix($id)) . '</a>'; }, $autres);
      echo '<p style="margin:0 0 8px;color:#50575e">Partagée avec : ' . implode(', ', $liens) . '</p>';
    }
    echo '<p style="margin:0"><a class="button button-primary" href="' . esc_url(get_edit_post_link($nid)) . '">Modifier la notice</a> <a class="button" href="' . esc_url(get_permalink($nid)) . '" target="_blank">Voir la page</a></p>';
  }, 'product', 'side', 'default');
});

/**
 * Un produit n'a qu'UNE notice. Changer de notice (côté produit ou côté notice)
 * retire le produit de l'ancienne. Écritures directes en postmeta pour ne pas
 * relancer la synchro bidirectionnelle d'ACF.
 */
function sapi_notice_retirer_produit($notice_id, $product_id) {
  $list = get_post_meta($notice_id, 'produits', true);
  if (!is_array($list)) return;
  $new = array_values(array_filter($list, function ($id) use ($product_id) { return (int) $id !== (int) $product_id; }));
  if (count($new) !== count($list)) update_post_meta($notice_id, 'produits', $new);
}
add_filter('acf/update_value/key=field_prod_notice_montage', function ($value, $product_id) {
  $old = (int) get_post_meta($product_id, 'notice_montage', true);
  if ($old && $old !== (int) $value) sapi_notice_retirer_produit($old, $product_id);
  return $value;
}, 5, 2);
add_filter('acf/update_value/key=field_nm_produits', function ($value, $notice_id) {
  foreach ((array) $value as $pid) {
    $old = (int) get_post_meta((int) $pid, 'notice_montage', true);
    if ($old && $old !== (int) $notice_id) sapi_notice_retirer_produit($old, (int) $pid);
  }
  return $value;
}, 5, 2);
// Notices enregistrées avant la relation bidirectionnelle : on écrit une fois
// la meta produit manquante, pour que le champ « Notice » du produit soit juste.
add_action('admin_init', function () {
  if (get_option('sapi_notice_sync_v') === '1' || !current_user_can('edit_products')) return;
  foreach (get_posts(['post_type' => 'notice_montage', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']) as $nid) {
    foreach ((array) get_post_meta($nid, 'produits', true) as $pid) {
      $pid = (int) $pid;
      if ($pid && !get_post_meta($pid, 'notice_montage', true)) {
        update_post_meta($pid, 'notice_montage', $nid);
        update_post_meta($pid, '_notice_montage', 'field_prod_notice_montage');
      }
    }
  }
  update_option('sapi_notice_sync_v', '1', false);
});

/** « Créer une notice pour ce produit » : le produit est pré-coché dans la nouvelle notice. */
add_filter('acf/load_value/key=field_nm_produits', function ($value, $post_id) {
  if (!empty($value) || empty($_GET['sapi_produit']) || get_post_status($post_id) !== 'auto-draft') return $value;
  $pid = (int) $_GET['sapi_produit'];
  return get_post_type($pid) === 'product' ? [(string) $pid] : $value;
}, 10, 2);

/* ── Nettoyage des anciens champs produit (assemblage, difficulté, outils) ──
   Ces trois infos vivent désormais dans la notice. Une fois les 3 champs
   retirés du groupe ACF « Fiche technique », un bouton sur la liste des notices
   supprime leurs valeurs restées en base. Admin seulement, avec nonce. */
function sapi_notice_old_meta_keys($with_refs = true) {
  $k = ['assemblage', 'installation_difficulte', 'assemblage_outils'];
  return $with_refs ? array_merge($k, ['_assemblage', '_installation_difficulte', '_assemblage_outils']) : $k;
}
add_action('admin_notices', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->id !== 'edit-notice_montage' || !current_user_can('manage_options')) return;
  global $wpdb;
  $keys = sapi_notice_old_meta_keys(false); // compte les valeurs, pas leurs références ACF
  $in   = implode(',', array_fill(0, count($keys), '%s'));
  if (!empty($_GET['sapi_nettoye'])) {
    echo '<div class="notice notice-success is-dismissible"><p>Anciens champs produit supprimés (' . (int) $_GET['sapi_nettoye'] . ' valeurs).</p></div>';
    return;
  }
  $n = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type IN ('product','product_variation') AND pm.meta_key IN ($in)", $keys));
  if (!$n) return;
  $url = wp_nonce_url(admin_url('admin-post.php?action=sapi_notice_nettoyage'), 'sapi_notice_nettoyage');
  echo '<div class="notice notice-warning"><p><strong>Nettoyage :</strong> ' . $n . ' anciennes valeurs « Assemblage / Difficulté / Outils requis » restent sur les produits. '
    . 'À faire seulement après avoir retiré ces 3 champs du groupe ACF « Fiche technique ». '
    . '<a class="button" href="' . esc_url($url) . '">Supprimer ces anciennes valeurs</a></p></div>';
});
add_action('admin_post_sapi_notice_nettoyage', function () {
  if (!current_user_can('manage_options')) wp_die('Accès refusé');
  check_admin_referer('sapi_notice_nettoyage');
  global $wpdb;
  $keys = sapi_notice_old_meta_keys();
  $in   = implode(',', array_fill(0, count($keys), '%s'));
  $n = (int) $wpdb->query($wpdb->prepare("DELETE pm FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type IN ('product','product_variation') AND pm.meta_key IN ($in)", $keys));
  wp_cache_flush();
  wp_safe_redirect(admin_url('edit.php?post_type=notice_montage&sapi_nettoye=' . max(1, (int) ($n / 2))));
  exit;
});

/* ── Helpers ─────────────────────────────────────────────────────────────── */

/** Notice publiée reliée à un produit, ou 0. */
function sapi_notice_for_product($product_id) {
  $product_id = (int) $product_id;
  if (!$product_id) return 0;
  $direct = (int) get_post_meta($product_id, 'notice_montage', true);
  if ($direct && get_post_type($direct) === 'notice_montage' && get_post_status($direct) === 'publish') return $direct;
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
  if (!preg_match('/^\d+(:\d{1,2}){0,2}$/', $t)) return '';
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

/**
 * Infos de montage d'un produit, lues dans SA notice (source unique) :
 * ['notice_id','url','yt','difficulte' => 'Facile · 15 à 30 min','outils' => 'Aucun'].
 * null si aucune notice publiée : la fiche n'affiche alors rien de faux.
 */
function sapi_notice_specs_for_product($product_id) {
  $nid = sapi_notice_for_product($product_id);
  if (!$nid || !function_exists('get_field')) return null;
  $diff  = trim((string) get_field('difficulte', $nid));
  $duree = trim((string) get_field('duree', $nid));
  $vi    = sapi_notice_version_for_product($nid, $product_id);
  $url   = get_permalink($nid);
  if ($vi === null) {
    $yt = sapi_notice_youtube_id(get_field('video_youtube', $nid)); // version inconnue : assemblage commun
  } else {
    $asm = sapi_notice_assemblages($nid);
    $yt  = $asm[$vi]['yt'] ?? '';
    if (count($asm) > 1) $url = add_query_arg('version', $vi, $url);
  }
  return [
    'notice_id'  => $nid,
    'url'        => $url,
    'yt'         => $yt,
    'difficulte' => implode(' · ', array_filter([$diff, $duree])),
    'outils'     => trim((string) get_field('outils', $nid)),
  ];
}

/** « (applique) » d'après la 1re catégorie, pour distinguer les homonymes. */
function sapi_notice_cat_suffix($product_id) {
  $cats = get_the_terms($product_id, 'product_cat');
  return ($cats && !is_wp_error($cats)) ? ' (' . mb_strtolower($cats[0]->name) . ')' : '';
}

/**
 * Assemblage de chaque version (index = index de la version) :
 * ['yt' => id YouTube, 'etapes' => [...], 'propre' => bool].
 * Sans assemblage propre, une version prend l'assemblage commun de la notice.
 */
function sapi_notice_assemblages($nid) {
  $commun = [
    'yt'     => sapi_notice_youtube_id(get_field('video_youtube', $nid)),
    'etapes' => (array) (get_field('etapes', $nid) ?: []),
    'propre' => false,
  ];
  $versions = (array) (get_field('versions', $nid) ?: []);
  if (!$versions) return [$commun];
  $out = [];
  foreach ($versions as $i => $v) {
    $out[$i] = empty($v['assemblage_propre']) ? $commun : [
      'yt'     => sapi_notice_youtube_id($v['video_youtube'] ?? ''), // pas de repli sur la vidéo commune : ce serait le mauvais montage
      'etapes' => (array) (($v['etapes'] ?? null) ?: []),
      'propre' => true,
    ];
  }
  return $out;
}

/**
 * Version de la notice qui correspond à un produit, d'après le slug de sa
 * catégorie (mêmes slugs que single-product.php), lieux par ordre de priorité.
 * null si rien ne correspond : l'appelant prend alors l'assemblage commun.
 */
function sapi_notice_version_for_product($nid, $product_id) {
  $versions = (array) (get_field('versions', $nid) ?: []);
  if (count($versions) < 2) return $versions ? 0 : null;
  $cats  = get_the_terms((int) $product_id, 'product_cat');
  $slugs = ($cats && !is_wp_error($cats)) ? wp_list_pluck($cats, 'slug') : [];
  $regles = [
    'suspensions'       => ['plafond', 'mur_plafond'],
    'appliques'         => ['mur', 'mur_plafond'],
    'appliques-murales' => ['mur', 'mur_plafond'],
    'lampadaires'       => ['sol'],
    'lampesaposer'      => ['meuble'],
    'lampes-a-poser'    => ['meuble'],
  ];
  foreach ($regles as $slug => $lieux) {
    if (!in_array($slug, $slugs, true)) continue;
    foreach ($lieux as $l) foreach ($versions as $i => $v) if (($v['lieu'] ?? '') === $l) return (int) $i;
  }
  return null;
}

/** Lignes de paires d'une étape : [[a, liaison, b], …] (1 ou 2 lignes). */
function sapi_notice_step_rows($e) {
  $rows = [];
  foreach ([['symbole_a', 'liaison', 'symbole_b'], ['symbole_c', 'liaison_2', 'symbole_d']] as $k) {
    $a = trim((string) ($e[$k[0]] ?? ''));
    $b = trim((string) ($e[$k[2]] ?? ''));
    if ($a === '' && $b === '') continue;
    if ($a === '') { $a = $b; $b = ''; } // symbole seul saisi à droite
    $rows[] = [$a, trim((string) ($e[$k[1]] ?? '')), $b];
  }
  return $rows;
}

/** Libellé d'une étape action : Complétez / Retournez / texte libre. */
function sapi_notice_step_action($e) {
  $a = $e['action'] ?? 'completez';
  if ($a === 'retournez') return ['Retournez', '⇅'];
  if ($a === 'libre') return [trim((string) ($e['action_texte'] ?? '')), ''];
  return ['Complétez', '⟳'];
}

/** Étape en texte simple : « □ et □ + ○ et ○ ×3 », « Complétez l'intérieur ». */
function sapi_notice_step_text($e) {
  if (($e['type'] ?? '') === 'action') {
    list($label) = sapi_notice_step_action($e);
    return trim($label . ' ' . trim((string) ($e['precision'] ?? '')));
  }
  $parts = [];
  foreach (sapi_notice_step_rows($e) as $r) {
    $parts[] = $r[2] !== '' ? $r[0] . ' ' . ($r[1] ?: 'et') . ' ' . $r[2] : trim($r[0] . ' ' . ($r[1] !== 'et' ? $r[1] : ''));
  }
  $fois = (int) ($e['fois'] ?? 0);
  return implode(' + ', $parts) . ($fois > 1 ? ' ×' . $fois : '');
}

/** Étape dessinée pour le mode énigme (même lecture que la plaquette). */
function sapi_notice_step_html($e) {
  if (($e['type'] ?? '') === 'action') {
    list($label, $icon) = sapi_notice_step_action($e);
    $prec = trim((string) ($e['precision'] ?? ''));
    return '<span class="sapi-step sapi-step--action">'
      . ($icon ? '<span class="sapi-step__icon" aria-hidden="true">' . esc_html($icon) . '</span>' : '')
      . '<span class="sapi-step__verb">' . esc_html($label) . '</span>'
      . ($prec !== '' ? '<span class="sapi-step__prec">' . esc_html($prec) . '</span>' : '')
      . '</span>';
  }
  $rows = [];
  foreach (sapi_notice_step_rows($e) as $r) {
    $h = '<span class="sapi-step__row"><span class="sapi-enigme__big">' . esc_html($r[0]) . '</span>';
    if ($r[2] !== '') $h .= '<span class="sapi-enigme__join">' . esc_html($r[1] ?: 'et') . '</span><span class="sapi-enigme__big">' . esc_html($r[2]) . '</span>';
    elseif ($r[1] !== '' && $r[1] !== 'et') $h .= '<span class="sapi-enigme__join">' . esc_html($r[1]) . '</span>';
    $rows[] = $h . '</span>';
  }
  $fois = (int) ($e['fois'] ?? 0);
  return '<span class="sapi-step' . (count($rows) > 1 ? ' sapi-step--double' : '') . '">'
    . implode('<span class="sapi-step__plus" aria-hidden="true">+</span>', $rows)
    . ($fois > 1 ? '<span class="sapi-step__fois">×' . $fois . '</span>' : '')
    . '</span>';
}

/* ── Encart sur la fiche produit ─────────────────────────────────────────── */
/**
 * Vignette vidéo + lien notice, en tête de la colonne « Installation » de la
 * fiche technique. La vignette ouvre la vidéo dans une fenêtre (JS), le lien
 * ouvre la notice. Sans vidéo, seule la vignette disparaît.
 */
function sapi_notice_render_install_video($spec) {
  if (!$spec) return;
  if ($spec['yt']) : ?>
  <button type="button" class="sapi-install-video" data-yt="<?php echo esc_attr($spec['yt']); ?>" aria-label="Voir la vidéo de montage">
    <span class="sapi-install-video__thumb">
      <img src="<?php echo esc_url('https://i.ytimg.com/vi/' . $spec['yt'] . '/hqdefault.jpg'); ?>" alt="" loading="lazy" width="320" height="180">
      <svg class="sapi-notice-encart__play" viewBox="0 0 76 76" aria-hidden="true"><circle cx="38" cy="38" r="37"/><path d="M31 25 L53 38 L31 51 Z"/></svg>
    </span>
    <span class="sapi-install-video__label">Le montage en vidéo</span>
  </button>
  <?php endif;
}

function sapi_notice_render_install_link($spec) {
  if (!$spec) return; ?>
  <a class="sapi-install-link" href="<?php echo esc_url($spec['url']); ?>">Voir la notice de montage →</a>
  <?php
}

/** « Facile · 15 à 30 min · Sans outil » pour l'en-tête de la page de montage. */
function sapi_notice_meta_line($nid) {
  $outils = trim((string) get_field('outils', $nid));
  $o = ($outils === '' ) ? '' : (preg_match('/^(aucun|sans)/iu', $outils) ? 'Sans outil' : 'Outils : ' . $outils);
  return implode(' · ', array_filter([trim((string) get_field('difficulte', $nid)), trim((string) get_field('duree', $nid)), $o]));
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
  if ($on_centre || $on_product) {
    $js = get_template_directory() . '/assets/centre-assemblage.js';
    if (file_exists($js)) {
      wp_enqueue_script('sapi-centre-assemblage', get_template_directory_uri() . '/assets/centre-assemblage.js', [], filemtime($js), true);
    }
  }
}, 20);
