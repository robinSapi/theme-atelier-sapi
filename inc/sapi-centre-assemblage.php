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
    // Pas d'archive : /assemblage/ est une vraie page WordPress, éditable dans
    // Gutenberg, qui affiche la liste avec le bloc « Code court » [centre_assemblage].
    'has_archive'   => false,
    'rewrite'       => ['slug' => 'assemblage', 'with_front' => false],
  ]);
}
add_action('init', 'sapi_register_cpt_notice_montage');

// Les règles de réécriture ne connaissent le nouveau type qu'après un flush.
// On le fait une seule fois par version de ce fichier, pas à chaque requête.
add_action('init', function () {
  $v = 'notice-montage-2';
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
    wp_redirect(add_query_arg('src', 'qr', sapi_centre_url()), 302);
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
    ['key' => $p . 'action_texte', 'label' => 'Texte de l\'action', 'name' => 'action_texte', 'type' => 'text', 'placeholder' => 'Poser le cercle de verrouillage',
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
    if (!function_exists('get_field')) { echo '<p>ACF est désactivé : résumé indisponible.</p>'; return; }
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
  static $cache = [];
  if (isset($cache[$nid])) return $cache[$nid];
  return $cache[$nid] = sapi_notice_assemblages_calc($nid);
}
function sapi_notice_assemblages_calc($nid) {
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
  if ($a === 'libre') {
    // Pas de « required » ACF : l'API REST l'exigerait aussi sur les étapes « paires ».
    // Un texte oublié se voit au lieu de laisser une tuile vide.
    $t = trim((string) ($e['action_texte'] ?? ''));
    return [$t !== '' ? $t : 'À compléter', ''];
  }
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
    <span class="sapi-install-video__label"><?php echo esc_html(sapi_notice_txt('fp_video')); ?></span>
  </button>
  <?php endif;
}

function sapi_notice_render_install_link($spec) {
  if (!$spec) return; ?>
  <a class="sapi-install-link" href="<?php echo esc_url($spec['url']); ?>"><?php echo esc_html(sapi_notice_txt('fp_lien')); ?></a>
  <?php
}

/** « Facile · 15 à 30 min · Sans outil » pour l'en-tête de la page de montage. */
function sapi_notice_meta_line($nid) {
  $outils = trim((string) get_field('outils', $nid));
  $o = ($outils === '' ) ? '' : (preg_match('/^(aucun|sans)/iu', $outils) ? 'Sans outil' : 'Outils : ' . $outils);
  return implode(' · ', array_filter([trim((string) get_field('difficulte', $nid)), trim((string) get_field('duree', $nid)), $o]));
}

/* ── Textes communs à toutes les notices (Notices de montage › Textes communs) ── */
/**
 * Clé => [onglet, libellé, texte par défaut, type]. Le défaut s'affiche tant
 * que le champ n'a jamais été enregistré ; un champ vidé revient au défaut
 * (on ne laisse jamais un bouton ou un titre vide sur la page).
 */
function sapi_notice_textes_def() {
  return [
    'retour'        => ['En-tête', 'Lien retour', '← Centre d\'assemblage', 'text'],
    'e1_titre'      => ['Étape 1', 'Titre', 'Installer le système électrique', 'text'],
    'e1_courant'    => ['Étape 1', 'Rappel sécurité', 'Coupez le courant au disjoncteur avant de toucher aux fils.', 'text'],
    'e1_bouton'     => ['Étape 1', 'Bouton', "C'est installé", 'text'],
    'e2_titre'      => ['Étape 2', 'Titre', 'Assembler le luminaire', 'text'],
    'e2_enigme'     => ['Étape 2', 'Onglet énigme', 'Mode énigme', 'text'],
    'e2_enigme_s'   => ['Étape 2', 'Onglet énigme, mention manuscrite', 'à vous de chercher', 'text'],
    'e2_guide'      => ['Étape 2', 'Onglet guidé', 'Mode guidé', 'text'],
    'e2_guide_s'    => ['Étape 2', 'Onglet guidé, mention manuscrite', 'le tuto vidéo', 'text'],
    'e2_intro'      => ['Étape 2', 'Consigne du mode énigme', "Chaque pièce porte un symbole : suivez la plaquette dans l'ordre et trouvez les paires.", 'textarea'],
    'e2_indice'     => ['Étape 2', 'Ajout quand des indices existent', 'Touchez une étape pour voir son indice.', 'text'],
    'e2_sans_enigme'=> ['Étape 2', 'Version sans énigme mais avec vidéo', "L'énigme de cette version arrive bientôt : suivez la vidéo en mode guidé.", 'text'],
    'e2_sans_rien'  => ['Étape 2', 'Version sans énigme ni vidéo', 'Le montage de cette version arrive bientôt. En attendant, écrivez-moi : je vous guide.', 'text'],
    'e2_sans_video' => ['Étape 2', 'Mode guidé sans vidéo', 'La vidéo de cette version arrive bientôt.', 'text'],
    'e2_indice_lbl' => ['Étape 2', 'Étiquette d\'un indice (suivie du numéro)', 'Indice', 'text'],
    'e2_voir_a'     => ['Étape 2', 'Bouton vers un moment de la vidéo (suivi du temps)', 'voir à', 'text'],
    'e2_bouton'     => ['Étape 2', 'Bouton', 'Le luminaire est assemblé', 'text'],
    'e3_titre'      => ['Étape 3', 'Titre', "Fixer l'abat-jour", 'text'],
    'e3_bouton'     => ['Étape 3', 'Bouton', "C'est fixé, j'allume", 'text'],
    'fin_slogan'    => ['Fin', 'Slogan (manuscrit)', 'Assemblez, Éclairez, Admirez !', 'text'],
    'fin_texte'     => ['Fin', 'Texte', "Il est allumé ? Envoyez-moi une photo, ou laissez un avis : ça aide d'autres personnes à choisir.", 'textarea'],
    'fin_photo'     => ['Fin', 'Bouton photo', 'Envoyer une photo', 'text'],
    'fin_atelier'   => ['Fin', 'Bouton quand aucun produit n\'est relié', 'Découvrir l\'atelier', 'text'],
    'fin_produit'   => ['Fin', 'Bouton vers le produit', 'Revoir la fiche du luminaire', 'text'],
    'aide'          => ['Aide', 'Phrase avant le contact', 'Ça coince ? Écrivez-moi :', 'text'],
    'email'         => ['Aide', 'E-mail', 'commande@atelier-sapi.fr', 'email'],
    'telephone'     => ['Aide', 'Téléphone', '06 80 43 55 85', 'text'],
    'fp_video'      => ['Fiche produit', 'Sous la vignette vidéo', 'Le montage en vidéo', 'text'],
    'fp_lien'       => ['Fiche produit', 'Lien vers la notice', 'Voir la notice de montage →', 'text'],
    'c_qr'          => ['Page Centre', 'Bandeau après le scan du QR code', 'Vous venez de scanner le QR code de votre colis : choisissez votre luminaire.', 'text'],
    'c_recherche'   => ['Page Centre', 'Libellé de la recherche', 'Rechercher un luminaire', 'text'],
    'c_exemples'    => ['Page Centre', 'Exemples dans la recherche', 'Olivia, Mireille, Alice…', 'text'],
    'c_aucun'       => ['Page Centre', 'Aucun résultat', 'Aucun luminaire ne correspond. Écrivez-moi, je vous envoie la notice.', 'text'],
    'c_vide'        => ['Page Centre', 'Aucune notice publiée', 'Les notices arrivent bientôt. Écrivez-moi, je vous envoie la notice.', 'text'],
  ];
}

/** Numéro pour un lien tel: international (06… → +336…). */
function sapi_notice_tel($t) {
  $n = preg_replace('/[^0-9+]/', '', (string) $t);
  if (preg_match('/^0(\d{9})$/', $n, $m)) $n = '+33' . $m[1];
  return $n;
}

/** Texte commun, ou son défaut si le champ est vide ou ACF absent. */
function sapi_notice_txt($key) {
  static $cache = [];
  if (isset($cache[$key])) return $cache[$key];
  $def = sapi_notice_textes_def()[$key][2] ?? '';
  $v = function_exists('get_field') ? trim((string) get_field('sapi_nt_' . $key, 'option')) : '';
  return $cache[$key] = ($v !== '' ? $v : $def);
}

add_action('acf/init', function () {
  if (!function_exists('acf_add_options_sub_page') || !function_exists('acf_add_local_field_group')) return;
  acf_add_options_sub_page([
    'page_title'  => 'Textes communs des notices',
    'menu_title'  => 'Textes communs',
    'menu_slug'   => 'notices-textes-communs',
    'parent_slug' => 'edit.php?post_type=notice_montage',
    'capability'  => 'edit_posts',
  ]);
  $fields = [];
  $tab = '';
  foreach (sapi_notice_textes_def() as $k => $d) {
    if ($d[0] !== $tab) {
      $tab = $d[0];
      $fields[] = ['key' => 'field_nt_tab_' . sanitize_key($tab), 'label' => $tab, 'type' => 'tab'];
    }
    $f = ['key' => 'field_nt_' . $k, 'label' => $d[1], 'name' => 'sapi_nt_' . $k, 'type' => $d[3],
          'placeholder' => $d[2], 'instructions' => 'Vide = texte par défaut affiché en gris.'];
    if ($d[3] === 'textarea') { $f['rows'] = 2; $f['new_lines'] = ''; }
    $fields[] = $f;
  }
  acf_add_local_field_group([
    'key'      => 'group_notice_textes',
    'title'    => 'Textes affichés sur toutes les pages de montage',
    'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => 'notices-textes-communs']]],
    'fields'   => $fields,
  ]);
});

/* ── Page « Centre d'assemblage » ─────────────────────────────────────────── */
/** Adresse de l'accueil du centre : la page /assemblage/ (repli sur l'URL brute). */
function sapi_centre_url() {
  $page = get_page_by_path('assemblage');
  return ($page && $page->post_status === 'publish') ? get_permalink($page) : home_url('/assemblage/');
}

/**
 * [centre_assemblage] : recherche + une vignette par PRODUIT relié (le client
 * reconnaît son luminaire, pas le nom d'une notice). Le reste de la page
 * (titre, textes, encadrés) se rédige dans l'éditeur.
 */
add_shortcode('centre_assemblage', function () {
  if (!function_exists('get_field')) return '<p>Notices momentanément indisponibles. Écrivez-moi : commande@atelier-sapi.fr</p>';
  $cards = [];
  $seen  = [];
  $notices = get_posts(['post_type' => 'notice_montage', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
  foreach ($notices as $n) {
    foreach ((array) (get_field('produits', $n->ID) ?: []) as $pid) {
      $pid = (int) $pid;
      if (isset($seen[$pid]) || get_post_status($pid) !== 'publish') continue;
      $seen[$pid] = true;
      $cats = get_the_terms($pid, 'product_cat');
      $spec = sapi_notice_specs_for_product($pid);
      $cards[] = [
        'name' => sapi_notice_title($pid),
        'cat'  => ($cats && !is_wp_error($cats)) ? $cats[0]->name : '',
        'img'  => get_post_thumbnail_id($pid),
        'url'  => $spec ? $spec['url'] : get_permalink($n->ID), // ?version=N de ce produit
      ];
    }
  }
  usort($cards, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
  $from_qr = (($_GET['src'] ?? '') === 'qr');
  ob_start(); ?>
  <div class="sapi-centre-liste">
    <?php if ($from_qr) : ?><p class="sapi-centre__qr"><?php echo esc_html(sapi_notice_txt('c_qr')); ?></p><?php endif; ?>
    <label class="sapi-centre__label" for="sapi-centre-q"><?php echo esc_html(sapi_notice_txt('c_recherche')); ?></label>
    <input id="sapi-centre-q" class="sapi-centre__search" type="search" placeholder="<?php echo esc_attr(sapi_notice_txt('c_exemples')); ?>" autocomplete="off">
    <?php if ($cards) : ?>
    <ul class="sapi-centre__grid">
      <?php foreach ($cards as $c) : ?>
      <li data-search="<?php echo esc_attr(remove_accents(mb_strtolower($c['name'] . ' ' . $c['cat']))); ?>">
        <a class="sapi-centre__card" href="<?php echo esc_url($c['url']); ?>">
          <span class="sapi-centre__img"><?php if ($c['img']) echo wp_get_attachment_image($c['img'], 'woocommerce_thumbnail', false, ['alt' => '', 'loading' => 'lazy']); ?></span>
          <span class="sapi-centre__name"><?php echo sapi_notice_format_name($c['name']); ?></span>
          <?php if ($c['cat']) : ?><span class="sapi-centre__cat"><?php echo esc_html($c['cat']); ?></span><?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="sapi-centre__empty"<?php echo $cards ? ' hidden' : ''; ?>><?php echo esc_html(sapi_notice_txt($cards ? 'c_aucun' : 'c_vide')); ?></p>
  </div>
  <?php
  return ob_get_clean();
});

/** La page qui contient le code court charge le CSS et le JS du centre. */
function sapi_centre_is_page() {
  if (!is_page()) return false;
  $post = get_post();
  return $post && has_shortcode((string) $post->post_content, 'centre_assemblage');
}

/* ── SEO ─────────────────────────────────────────────────────────────────── */
/**
 * Produit publié de chaque version : [index de version => id produit].
 * Notice à une seule version : [0 => premier produit publié].
 */
function sapi_notice_product_links($nid) {
  $out = [];
  $repli = 0;
  $versions = (array) (get_field('versions', $nid) ?: []);
  foreach ((array) (get_field('produits', $nid) ?: []) as $pid) {
    $pid = (int) $pid;
    if (get_post_status($pid) !== 'publish') continue;
    if (!$repli) $repli = $pid;
    $vi = count($versions) > 1 ? sapi_notice_version_for_product($nid, $pid) : 0;
    if ($vi !== null && !isset($out[$vi])) $out[$vi] = $pid;
  }
  if (!$repli) return [];
  // Chaque version garde un bouton : sans produit à elle, celui du premier produit publié.
  foreach (array_keys($versions ?: [0 => null]) as $vi) if (!isset($out[$vi])) $out[$vi] = $repli;
  ksort($out);
  return $out;
}

/**
 * Une notice est complète quand chaque version a une énigme ou une vidéo et
 * qu'aucun texte ne porte encore [À COMPLÉTER], [À VÉRIFIER], [MÉTHODE À PRÉCISER]…
 */
function sapi_notice_is_complete($nid) {
  static $cache = [];
  if (isset($cache[$nid])) return $cache[$nid];
  $textes = (string) get_field('abatjour_texte', $nid);
  foreach ((array) (get_field('versions', $nid) ?: []) as $v) $textes .= ' ' . ($v['texte'] ?? '');
  foreach (sapi_notice_assemblages($nid) as $a) {
    if (!$a['etapes'] && $a['yt'] === '') return $cache[$nid] = false;
    foreach ($a['etapes'] as $e) {
      if (($e['type'] ?? '') === 'action' && ($e['action'] ?? '') === 'libre' && trim((string) ($e['action_texte'] ?? '')) === '') return $cache[$nid] = false;
      $textes .= ' ' . ($e['indice'] ?? '') . ' ' . ($e['precision'] ?? '') . ' ' . ($e['action_texte'] ?? '');
    }
  }
  return $cache[$nid] = !sapi_notice_a_completer($textes);
}

/** Marqueurs de texte provisoire : [À COMPLÉTER], [A VERIFIER], [MÉTHODE À PRÉCISER]… */
function sapi_notice_a_completer($texte) {
  return (bool) preg_match('/\[(à|a) (compl[ée]ter|v[ée]rifier|pr[ée]ciser)|\[m[ée]thode/iu', (string) $texte);
}

/** Notice incomplète : hors des moteurs de recherche (Yoast), jusqu'à ce qu'elle soit finie. */
add_filter('wpseo_robots', function ($robots) {
  if (is_singular('notice_montage') && function_exists('get_field') && !sapi_notice_is_complete(get_queried_object_id())) {
    return 'noindex, follow';
  }
  return $robots;
});
/** …et hors du sitemap Yoast. */
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
  static $exclus = null;
  if (!function_exists('get_field')) return $ids;
  if ($exclus !== null) return array_merge((array) $ids, $exclus);
  $exclus = [];
  foreach (get_posts(['post_type' => 'notice_montage', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true]) as $nid) {
    if (!sapi_notice_is_complete($nid)) $exclus[] = $nid;
  }
  return array_merge((array) $ids, $exclus);
});

/* ── Hub (taches.atelier-sapi.fr) ─────────────────────────────────────────── */
/**
 * État de la notice d'un produit, pour le hub : ajouté à la réponse
 * /wp-json/wc/v3/products que le hub lit déjà avec sa clé WooCommerce.
 * null si aucune notice n'est reliée (même en brouillon).
 */
function sapi_notice_hub_summary($product_id) {
  if (!function_exists('get_field')) return null;
  $nid = (int) get_post_meta($product_id, 'notice_montage', true);
  if (!$nid || get_post_type($nid) !== 'notice_montage' || get_post_status($nid) === 'trash') $nid = 0;
  if (!$nid) {
    // Notice publiée reliée côté notice seulement (ancienne donnée) : on la retrouve quand même.
    $nid = sapi_notice_for_product($product_id);
    if (!$nid) return null;
  }
  $versions = (array) (get_field('versions', $nid) ?: []);
  $vi  = sapi_notice_version_for_product($nid, $product_id);
  $asm = sapi_notice_assemblages($nid);
  $a   = $vi === null
    ? ['yt' => sapi_notice_youtube_id(get_field('video_youtube', $nid)), 'etapes' => (array) (get_field('etapes', $nid) ?: []), 'propre' => false]
    : ($asm[$vi] ?? $asm[0]);
  $elec = $vi === null ? '' : trim((string) ($versions[$vi]['texte'] ?? ''));
  return [
    'id'        => $nid,
    'title'     => sapi_notice_title($nid),
    'status'    => get_post_status($nid),                 // publish, draft, pending…
    'url'       => ($vi !== null && count($asm) > 1) ? add_query_arg('version', $vi, get_permalink($nid)) : get_permalink($nid),
    'edit_url'  => admin_url('post.php?post=' . $nid . '&action=edit'),
    'version'   => $vi === null ? null : (string) ($versions[$vi]['label'] ?? ''),
    'versions'  => count($versions),
    'video'     => $a['yt'] !== '',
    'etapes'    => count($a['etapes']),
    'propre'    => !empty($a['propre']),
    'a_completer' => sapi_notice_a_completer($elec . ' ' . (string) get_field('abatjour_texte', $nid)),
    'complete'  => sapi_notice_is_complete($nid),   // même règle que le noindex
    'produits'  => array_values(array_map('intval', (array) (get_field('produits', $nid) ?: []))),
  ];
}
add_filter('woocommerce_rest_prepare_product_object', function ($response, $product, $request) {
  // Calculé seulement si l'appelant le demande (&sapi_notice=1) : les autres
  // intégrations qui lisent l'API produits ne paient pas ce coût.
  if (!$request || !$request->get_param('sapi_notice')) return $response;
  if ($response instanceof WP_REST_Response) {
    $data = $response->get_data();
    $data['sapi_notice'] = sapi_notice_hub_summary($product->get_id());
    $response->set_data($data);
  }
  return $response;
}, 10, 3);

/* ── Assets ──────────────────────────────────────────────────────────────── */
add_action('wp_enqueue_scripts', function () {
  $on_centre = is_singular('notice_montage') || sapi_centre_is_page();
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
