<?php
/**
 * Tâche 5 — Page admin « Règles de filtrage » du Conseiller.
 *
 * Permet à Robin d'éditer les règles du moteur de filtrage (cf.
 * sapi_conseiller_default_rules / sapi_conseiller_get_rules) sans toucher au
 * code. Sauvegarde dans l'option `sapi_conseiller_rules` ; le moteur la lit
 * via array_merge par-dessus les défauts. Le simulateur
 * (assets/guide-filtrage-simulateur.html) est la maquette de référence.
 *
 * Sous-menu de « Robin Conseiller » (slug parent : sapi-conseiller-sessions).
 * Sécurité : capability manage_woocommerce + nonce sur chaque écriture.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

const SAPI_RULES_OPTION = 'sapi_conseiller_rules';
const SAPI_RULES_PAGE   = 'sapi-conseiller-regles';
const SAPI_RULES_CAP    = 'manage_woocommerce';

/* ─────────────────────────────────────────────────────────────────────────
   Vocabulaires (slugs valides + libellés) — extraits du simulateur.
   Servent à la fois au rendu (options) et à la sanitization (whitelists).
   ───────────────────────────────────────────────────────────────────────── */
function sapi_rules_vocab() {
  $pieces = [];
  if (function_exists('sapi_room_choices')) {
    foreach (sapi_room_choices() as $r) { $pieces[$r['slug']] = $r['label']; }
  }
  return [
    'pieces'     => $pieces, // salon, cuisine, chambre, chambre-enfant, bureau, entree, escalier
    'sorties'    => [
      'plafond'       => 'Au plafond',
      'mur'           => 'Au mur',
      'pas-de-sortie' => 'Prise 230V',
      'ne-sais-pas'   => 'Je ne sais pas',
      ''              => '(par défaut / non précisé)',
    ],
    'ampoules'   => [
      'ampoule_degagee' => 'Dégagée',
      'semi_degagee'    => 'Semi-dégagée',
      'ampoule_entouree'=> 'Entourée',
    ],
    'cats'       => [
      'suspensions' => 'Suspensions',
      'appliques'   => 'Appliques',
      'lampadaires' => 'Lampadaires',
      'lampesaposer'=> 'Lampes à poser',
    ],
    'formats'    => [
      'boule'      => 'Boule',
      'horizontal' => 'Horizontal',
      'vertical'   => 'Vertical',
    ],
    'essences'   => [
      'peuplier' => 'Peuplier',
      'okoume'   => 'Okoumé',
      ''         => 'Aucune',
    ],
    'styles'     => [
      'moderne' => 'Moderne',
      'ancien'  => 'Ancien',
      'neutre'  => 'Pas de préférence',
    ],
    'tailles'    => [
      'petite' => 'Petite',
      'grande' => 'Grande',
    ],
    'escalier_q' => [
      'standard' => 'Escalier standard',
      'ouvert'   => 'Escalier ouvert',
    ],
    'importance' => [
      'categorie' => 'Catégorie',
      'ampoule'   => 'Ampoule',
      'format'    => 'Format',
    ],
  ];
}

/* ─────────────────────────────────────────────────────────────────────────
   Menu + enqueue (page dédiée seulement)
   ───────────────────────────────────────────────────────────────────────── */
// Priorité 11 : APRÈS l'enregistrement du menu parent « Robin Conseiller »
// (sapi_megafilter_admin_menu, priorité 10). Sinon add_submenu_page s'exécute
// avant que le parent existe → page mal enregistrée (404 au clic).
add_action('admin_menu', function () {
  add_submenu_page(
    'sapi-conseiller-sessions',
    'Règles de filtrage',
    'Règles de filtrage',
    SAPI_RULES_CAP,
    SAPI_RULES_PAGE,
    'sapi_rules_admin_render'
  );
}, 11);

/* ─────────────────────────────────────────────────────────────────────────
   Écriture : enregistrement + réinitialisation (via admin-post.php)
   ───────────────────────────────────────────────────────────────────────── */
add_action('admin_post_sapi_save_conseiller_rules', 'sapi_rules_handle_save');
add_action('admin_post_sapi_reset_conseiller_rules', 'sapi_rules_handle_reset');

function sapi_rules_redirect_back($status) {
  $url = add_query_arg(
    ['page' => SAPI_RULES_PAGE, 'sapi_rules_msg' => $status],
    admin_url('admin.php')
  );
  wp_safe_redirect($url);
  exit;
}

function sapi_rules_handle_reset() {
  if (!current_user_can(SAPI_RULES_CAP)) wp_die('Accès refusé.');
  check_admin_referer('sapi_reset_conseiller_rules');
  delete_option(SAPI_RULES_OPTION);
  sapi_rules_redirect_back('reset');
}

function sapi_rules_handle_save() {
  if (!current_user_can(SAPI_RULES_CAP)) wp_die('Accès refusé.');
  check_admin_referer('sapi_save_conseiller_rules');

  $posted = (isset($_POST['rules']) && is_array($_POST['rules'])) ? wp_unslash($_POST['rules']) : [];
  list($clean, $errors) = sapi_rules_sanitize($posted);

  if ($errors) {
    set_transient('sapi_rules_errors_' . get_current_user_id(), $errors, 60);
    sapi_rules_redirect_back('error');
  }

  update_option(SAPI_RULES_OPTION, $clean);
  sapi_rules_redirect_back('saved');
}

/* ─────────────────────────────────────────────────────────────────────────
   Sanitization (whitelist-driven) + garde-fous de cohérence.
   On reconstruit une config COMPLÈTE depuis les défauts : on n'écrit que des
   valeurs validées, jamais la structure brute de $_POST.
   Retour : [config_propre, erreurs[]].
   ───────────────────────────────────────────────────────────────────────── */
function sapi_rules_sanitize($posted) {
  $V = sapi_rules_vocab();
  $D = sapi_conseiller_default_rules();
  $errors = [];
  $c = [];

  $keys = function ($assoc) { return array_keys($assoc); };
  // Helper : garde uniquement les valeurs présentes dans la whitelist.
  $filter_list = function ($raw, $allowed) {
    $raw = is_array($raw) ? $raw : [];
    $out = [];
    foreach ($raw as $v) {
      $v = is_string($v) ? $v : '';
      if (in_array($v, $allowed, true) && !in_array($v, $out, true)) $out[] = $v;
    }
    return $out;
  };
  $pick = function ($raw, $allowed, $fallback) {
    $raw = is_string($raw) ? $raw : '';
    return in_array($raw, $allowed, true) ? $raw : $fallback;
  };

  // ── Booléens ──
  foreach (['prio', 'vertical_haute', 'vertical_entree_confort', 'vertical_petite_confort',
            'horizontal_petite_haute', 'grande_exclut_2_tailles'] as $bk) {
    $c[$bk] = !empty($posted[$bk]);
  }

  // ── prio_mode ──
  $c['prio_mode'] = $pick(isset($posted['prio_mode']) ? $posted['prio_mode'] : '', ['souple', 'strict'], 'souple');

  // ── importance : permutation de [categorie, ampoule, format] ──
  $imp_allowed = $keys($V['importance']);
  $imp_in = isset($posted['importance']) && is_array($posted['importance']) ? array_values($posted['importance']) : [];
  $imp = [];
  foreach ($imp_in as $v) { if (in_array($v, $imp_allowed, true) && !in_array($v, $imp, true)) $imp[] = $v; }
  // Complète avec les manquants (dans l'ordre par défaut) pour rester une permutation complète.
  foreach ($imp_allowed as $v) { if (!in_array($v, $imp, true)) $imp[] = $v; }
  $c['importance'] = $imp;

  // ── ampoule_by_piece : map pièce → liste d'ampoules (vide = aucun filtre / null) ──
  $amp_allowed = $keys($V['ampoules']);
  $c['ampoule_by_piece'] = [];
  foreach ($keys($V['pieces']) as $p) {
    $list = $filter_list(isset($posted['ampoule_by_piece'][$p]) ? $posted['ampoule_by_piece'][$p] : [], $amp_allowed);
    $c['ampoule_by_piece'][$p] = $list ? $list : null; // null = pas de filtre ampoule (ex. escalier)
  }

  // ── ampoule_skip_when_grande : liste de pièces ──
  $c['ampoule_skip_when_grande'] = $filter_list(isset($posted['ampoule_skip_when_grande']) ? $posted['ampoule_skip_when_grande'] : [], $keys($V['pieces']));

  // ── cats_by_sortie + cats_secondaire_by_sortie : map sortie → liste de cats ──
  $cat_allowed = $keys($V['cats']);
  foreach (['cats_by_sortie', 'cats_secondaire_by_sortie'] as $mk) {
    $c[$mk] = [];
    foreach ($keys($V['sorties']) as $s) {
      $fk = sapi_rules_fkey($s); // '' encodé en '__empty' côté formulaire
      $c[$mk][$s] = $filter_list(isset($posted[$mk][$fk]) ? $posted[$mk][$fk] : [], $cat_allowed);
    }
  }

  // ── cuisine_remove : liste de cats ──
  $c['cuisine_remove'] = $filter_list(isset($posted['cuisine_remove']) ? $posted['cuisine_remove'] : [], $cat_allowed);

  // ── ampoule_pref_by_piece : map pièce → ampoule | null ──
  $c['ampoule_pref_by_piece'] = [];
  foreach ($keys($V['pieces']) as $p) {
    $raw = isset($posted['ampoule_pref_by_piece'][$p]) ? $posted['ampoule_pref_by_piece'][$p] : '';
    $c['ampoule_pref_by_piece'][$p] = ($raw === '' || $raw === '__none') ? null : $pick($raw, $amp_allowed, null);
  }

  // ── format_pref_by_piece : map pièce → format | '' ──
  $fmt_allowed = $keys($V['formats']);
  $c['format_pref_by_piece'] = [];
  foreach ($keys($V['pieces']) as $p) {
    $c['format_pref_by_piece'][$p] = $pick(isset($posted['format_pref_by_piece'][$p]) ? $posted['format_pref_by_piece'][$p] : '', $fmt_allowed, '');
  }

  // ── cat_priority_by_sortie : map sortie → cat | '' ──
  $c['cat_priority_by_sortie'] = [];
  foreach ($keys($V['sorties']) as $s) {
    $fk = sapi_rules_fkey($s);
    $c['cat_priority_by_sortie'][$s] = $pick(isset($posted['cat_priority_by_sortie'][$fk]) ? $posted['cat_priority_by_sortie'][$fk] : '', $cat_allowed, '');
  }

  // ── style_essence : map style → essence (peuplier|okoume|'') ──
  $ess_allowed = $keys($V['essences']); // inclut ''
  $c['style_essence'] = [];
  foreach ($keys($V['styles']) as $st) {
    $c['style_essence'][$st] = $pick(isset($posted['style_essence'][$st]) ? $posted['style_essence'][$st] : '', $ess_allowed, '');
  }

  // ── escalier_map : map question → taille ──
  $taille_allowed = $keys($V['tailles']);
  $c['escalier_map'] = [];
  foreach ($keys($V['escalier_q']) as $q) {
    $def = isset($D['escalier_map'][$q]) ? $D['escalier_map'][$q] : 'petite';
    $c['escalier_map'][$q] = $pick(isset($posted['escalier_map'][$q]) ? $posted['escalier_map'][$q] : '', $taille_allowed, $def);
  }

  // ── Clés non éditées dans l'UI : on garde les défauts (extras_slugs). ──
  $c['extras_slugs'] = isset($D['extras_slugs']) ? $D['extras_slugs'] : [];

  /* ── Garde-fous de cohérence ── */
  // 1. Chaque sortie « réelle » (hors '' et 'ne-sais-pas') doit avoir ≥1 catégorie.
  foreach (['plafond', 'mur', 'pas-de-sortie'] as $s) {
    if (empty($c['cats_by_sortie'][$s])) {
      $errors[] = sprintf('La sortie « %s » doit avoir au moins une catégorie.', $V['sorties'][$s]);
    }
  }
  // 2. La catégorie prioritaire d'une sortie doit faire partie de ses catégories acceptées.
  foreach ($keys($V['sorties']) as $s) {
    $prio_cat = $c['cat_priority_by_sortie'][$s];
    if ($prio_cat !== '' && !in_array($prio_cat, $c['cats_by_sortie'][$s], true)) {
      $errors[] = sprintf('Sortie « %s » : la catégorie prioritaire (%s) doit être dans les catégories acceptées.', $V['sorties'][$s], $V['cats'][$prio_cat]);
    }
  }
  // 3. L'ampoule préférée d'une pièce doit faire partie de ses ampoules acceptées (si filtre).
  foreach ($keys($V['pieces']) as $p) {
    $pref = $c['ampoule_pref_by_piece'][$p];
    $acc  = $c['ampoule_by_piece'][$p];
    if ($pref !== null && is_array($acc) && !in_array($pref, $acc, true)) {
      $errors[] = sprintf('Pièce « %s » : l\'ampoule préférée (%s) doit être dans les ampoules acceptées.', $V['pieces'][$p], $V['ampoules'][$pref]);
    }
  }

  return [$c, $errors];
}

/* ─────────────────────────────────────────────────────────────────────────
   Rendu de la page
   ───────────────────────────────────────────────────────────────────────── */
function sapi_rules_admin_render() {
  if (!current_user_can(SAPI_RULES_CAP)) wp_die('Accès refusé.');
  require_once get_template_directory() . '/inc/guide-data.php'; // sapi_guide_get_steps (pickers aperçu)
  $V = sapi_rules_vocab();
  $R = sapi_conseiller_get_rules(); // effectif (défauts + option)
  $is_custom = (get_option(SAPI_RULES_OPTION, null) !== null);

  // Notices
  $msg = isset($_GET['sapi_rules_msg']) ? sanitize_key($_GET['sapi_rules_msg']) : '';
  ?>
  <div class="wrap sapi-rules-admin">
    <h1>Règles de filtrage — Conseiller</h1>
    <p class="description">
      Édite ici les règles que le Conseiller applique pour sélectionner les luminaires.
      Source de vérité unique : ces réglages s'appliquent au site en direct.
      <?php echo $is_custom
        ? '<strong>Réglages personnalisés actifs.</strong>'
        : '<strong>Réglages par défaut (aucune personnalisation).</strong>'; ?>
    </p>

    <?php if ($msg === 'saved') : ?>
      <div class="notice notice-success is-dismissible"><p>Règles enregistrées. Elles s'appliquent immédiatement.</p></div>
    <?php elseif ($msg === 'reset') : ?>
      <div class="notice notice-success is-dismissible"><p>Règles réinitialisées aux valeurs par défaut.</p></div>
    <?php elseif ($msg === 'error') :
      $errs = get_transient('sapi_rules_errors_' . get_current_user_id());
      delete_transient('sapi_rules_errors_' . get_current_user_id());
      ?>
      <div class="notice notice-error"><p><strong>Enregistrement refusé — incohérences :</strong></p>
        <ul style="list-style:disc;margin-left:20px">
          <?php foreach ((array) $errs as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <style>
      .sapi-rules-admin .card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 20px;margin:0 0 18px;max-width:1100px}
      .sapi-rules-admin .card h2{margin-top:0;font-size:15px;border-bottom:1px solid #f0f0f1;padding-bottom:8px}
      .sapi-rules-admin .card .hint{color:#646970;font-size:12px;margin:-4px 0 12px}
      .sapi-rules-admin table.rt{border-collapse:collapse;width:100%}
      .sapi-rules-admin table.rt th,.sapi-rules-admin table.rt td{padding:7px 10px;border-bottom:1px solid #f0ece5;text-align:left;vertical-align:middle;font-size:13px}
      .sapi-rules-admin table.rt th{background:#fafafa;font-weight:600}
      .sapi-rules-admin .rowlabel{font-weight:600;white-space:nowrap}
      .sapi-rules-admin label.chk{display:inline-flex;align-items:center;gap:5px;margin-right:14px;white-space:nowrap}
      .sapi-rules-admin .bools label.chk{display:flex;margin:6px 0}
      .sapi-rules-admin .actions{position:sticky;bottom:0;background:#f6f7f7;padding:14px 0;border-top:1px solid #dcdcde;margin-top:10px}
      .sapi-rules-admin .actions .button-primary{margin-right:10px}
    </style>

    <form method="post" id="sapi-rules-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="sapi_save_conseiller_rules">
      <?php wp_nonce_field('sapi_save_conseiller_rules'); ?>

      <?php
      // ===== Ampoules acceptées par pièce (filtre dur) =====
      sapi_rules_card('Ampoules acceptées par pièce', 'Filtre dur. Une pièce sans case cochée = aucun filtre d\'ampoule (ex. cage d\'escalier).');
      sapi_rules_table_map_multi('ampoule_by_piece', $V['pieces'], $V['ampoules'], $R['ampoule_by_piece']);
      echo '</div>';

      // ===== Ampoule préférée par pièce (priorité) =====
      sapi_rules_card('Ampoule préférée par pièce', 'Couche priorité : classe la pièce vers cette ampoule (ne l\'exclut pas). « Aucune » = pas de préférence.');
      sapi_rules_table_map_single('ampoule_pref_by_piece', $V['pieces'], $V['ampoules'], $R['ampoule_pref_by_piece'], true);
      echo '</div>';

      // ===== Sauter le filtre ampoule en grande pièce =====
      sapi_rules_card('Ignorer le filtre ampoule en grande pièce', 'Pour ces pièces, en taille « grande », le filtre ampoule n\'est pas appliqué.');
      sapi_rules_flat_multi('ampoule_skip_when_grande', $V['pieces'], (array) $R['ampoule_skip_when_grande']);
      echo '</div>';

      // ===== Catégories par sortie — éclairage PRINCIPAL =====
      sapi_rules_card('Catégories — éclairage principal (par sortie)', 'Filtre dur. Types de luminaires montrés quand ce sera la LUMIÈRE PRINCIPALE de la pièce (= cas par défaut). La question « est-ce l\'éclairage principal ? » n\'est posée qu\'en grande pièce.');
      sapi_rules_table_map_multi('cats_by_sortie', $V['sorties'], $V['cats'], $R['cats_by_sortie']);
      echo '</div>';

      // ===== Catégories par sortie — éclairage d'APPOINT =====
      sapi_rules_card('Catégories — éclairage d\'appoint (par sortie)', 'Filtre dur. Types de luminaires montrés quand le visiteur a DÉJÀ d\'autres sources (luminaire d\'appoint / déco) — uniquement si, en grande pièce, il répond « j\'ai d\'autres sources ». Jamais mélangé avec l\'éclairage principal.');
      sapi_rules_table_map_multi('cats_secondaire_by_sortie', $V['sorties'], $V['cats'], $R['cats_secondaire_by_sortie']);
      echo '</div>';

      // ===== Catégorie prioritaire par sortie =====
      sapi_rules_card('Catégorie prioritaire par sortie', 'Couche priorité (ne filtre rien). Utile UNIQUEMENT quand une sélection mélange plusieurs catégories en même temps (ex. Prise 230V) : met celle-ci devant. « Aucune » = ordre WooCommerce par défaut.');
      sapi_rules_table_map_single('cat_priority_by_sortie', $V['sorties'], $V['cats'], $R['cat_priority_by_sortie'], true);
      echo '</div>';

      // ===== Exclusions cuisine =====
      sapi_rules_card('Exclusions — Cuisine', 'Catégories retirées en cuisine (filtre dur).');
      sapi_rules_flat_multi('cuisine_remove', $V['cats'], (array) $R['cuisine_remove']);
      echo '</div>';

      // ===== Format préféré par pièce =====
      sapi_rules_card('Format de suspension préféré par pièce', 'Couche priorité : met ce format devant. « Aucun » = pas de préférence.');
      sapi_rules_table_map_single('format_pref_by_piece', $V['pieces'], $V['formats'], $R['format_pref_by_piece'], true, 'Aucun');
      echo '</div>';

      // ===== Style → essence =====
      sapi_rules_card('Style → essence de bois', 'Associe un style à une essence préférée.');
      sapi_rules_table_map_single('style_essence', $V['styles'], $V['essences'], $R['style_essence'], false);
      echo '</div>';

      // ===== Escalier =====
      sapi_rules_card('Cage d\'escalier → taille', 'Convertit le type d\'escalier en taille de luminaire.');
      sapi_rules_table_map_single('escalier_map', $V['escalier_q'], $V['tailles'], $R['escalier_map'], false);
      echo '</div>';

      // ===== Priorité : activation, mode, ordre d'importance =====
      sapi_rules_card('Priorité (classement)', 'La couche priorité classe les produits sans jamais les exclure (sauf mode strict).');
      ?>
      <div class="bools">
        <label class="chk"><input type="checkbox" name="rules[prio]" value="1" <?php checked(!empty($R['prio'])); ?>> Activer le classement par priorité</label>
        <p style="margin:8px 0 4px"><strong>Mode :</strong>
          <label class="chk"><input type="radio" name="rules[prio_mode]" value="souple" <?php checked($R['prio_mode'], 'souple'); ?>> Souple (classe seulement)</label>
          <label class="chk"><input type="radio" name="rules[prio_mode]" value="strict" <?php checked($R['prio_mode'], 'strict'); ?>> Strict (ne garde que le meilleur score)</label>
        </p>
        <p style="margin:8px 0 4px"><strong>Ordre d'importance</strong> (1 = le plus important) :</p>
        <?php
        $imp = array_values((array) $R['importance']);
        for ($i = 0; $i < 3; $i++) {
          $cur = isset($imp[$i]) ? $imp[$i] : '';
          echo '<select name="rules[importance][' . $i . ']" style="margin-right:8px">';
          foreach ($V['importance'] as $slug => $lab) {
            echo '<option value="' . esc_attr($slug) . '" ' . selected($cur, $slug, false) . '>' . ($i + 1) . '. ' . esc_html($lab) . '</option>';
          }
          echo '</select>';
        }
        ?>
        <p class="hint">Astuce : choisis des critères différents dans chaque liste (les doublons sont corrigés à l'enregistrement).</p>
      </div>
      </div>

      <?php
      // ===== Règles de format (suspensions) + taille =====
      sapi_rules_card('Règles de format & taille', 'Filtres durs sur le format des suspensions et la taille.');
      foreach ([
        'vertical_haute'           => 'Autoriser le vertical dès que le plafond est haut',
        'vertical_entree_confort'  => 'Autoriser le vertical en entrée (hauteur confort)',
        'vertical_petite_confort'  => 'Autoriser le vertical en petite pièce (hauteur confort)',
        'horizontal_petite_haute'  => 'Autoriser l\'horizontal en petite pièce sous plafond haut',
        'grande_exclut_2_tailles'  => 'En grande pièce, exclure les 2 plus petites tailles',
      ] as $bk => $lab) {
        echo '<label class="chk" style="display:flex;margin:6px 0"><input type="checkbox" name="rules[' . esc_attr($bk) . ']" value="1" ' . checked(!empty($R[$bk]), true, false) . '> ' . esc_html($lab) . '</label>';
      }
      echo '</div>';
      ?>

      <div class="actions">
        <button type="submit" class="button button-primary button-hero">Enregistrer les règles</button>
        <span class="description">S'applique immédiatement sur le site.</span>
      </div>
    </form>

    <div class="card" id="sapi-preview">
      <h2>Aperçu live</h2>
      <p class="hint">Choisis une situation de visiteur : tu vois la sélection que le moteur renverrait <strong>avec les règles ci-dessus, même non enregistrées</strong> (c'est le vrai moteur du site, pas une copie).</p>
      <div style="display:flex;flex-wrap:wrap;align-items:flex-end">
        <?php
        sapi_rules_preview_select('piece', 'Pièce', sapi_rules_step_choices('piece'));
        sapi_rules_preview_select('taille', 'Taille', sapi_rules_step_choices('taille'));
        sapi_rules_preview_select('sortie', 'Sortie', sapi_rules_step_choices('sortie'));
        sapi_rules_preview_select('hauteur', 'Hauteur', sapi_rules_step_choices('hauteur'));
        sapi_rules_preview_select('eclairage', 'Éclairage', sapi_rules_step_choices('eclairage'));
        sapi_rules_preview_select('style', 'Style', sapi_rules_step_choices('style'));
        ?>
      </div>
      <button type="button" class="button button-primary" id="sapi-preview-run">Simuler la sélection</button>
      <div id="sapi-preview-out" style="margin-top:14px"></div>
    </div>

    <script>
    (function(){
      var btn=document.getElementById('sapi-preview-run');
      var form=document.getElementById('sapi-rules-form');
      var out=document.getElementById('sapi-preview-out');
      if(!btn||!form||!out)return;
      var CFG={url:<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,nonce:<?php echo wp_json_encode(wp_create_nonce('sapi_rules_preview')); ?>};
      function esc(s){var d=document.createElement('div');d.textContent=(s==null?'':String(s));return d.innerHTML;}
      btn.addEventListener('click',function(){
        out.innerHTML='<em>Calcul…</em>';
        var fd=new FormData(form);
        fd.set('action','sapi_admin_filter_preview');
        fd.set('nonce',CFG.nonce);
        document.querySelectorAll('#sapi-preview [data-pa]').forEach(function(sel){
          fd.append('pa['+sel.getAttribute('data-pa')+']',sel.value);
        });
        fetch(CFG.url,{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(j){
            if(!j||!j.success){out.innerHTML='<span style="color:#b32d2e">Erreur de calcul.</span>';return;}
            var d=j.data,h='';
            if(d.errors&&d.errors.length){h+='<div class="notice notice-warning inline" style="margin:0 0 10px"><p><strong>Règles incohérentes (l\'aperçu applique quand même les valeurs corrigées) :</strong><br>'+d.errors.map(esc).join('<br>')+'</p></div>';}
            if(!d.answers||!d.answers.piece){out.innerHTML=h+'<p><em>Choisis au moins une pièce.</em></p>';return;}
            h+='<p><strong>'+d.count+' produit(s)</strong> · catégories interrogées : '+(d.cats&&d.cats.length?d.cats.map(esc).join(', '):'—')+'</p>';
            if(d.notes&&d.notes.length){h+='<p style="color:#996800;font-size:12px">⚠ '+d.notes.map(esc).join('<br>⚠ ')+'</p>';}
            if(d.products&&d.products.length){
              h+='<div style="display:flex;flex-wrap:wrap;gap:12px">';
              d.products.forEach(function(p,i){
                h+='<div style="width:130px;font-size:12px;text-align:center">'+
                   (p.image?'<img src="'+esc(p.image)+'" style="width:100%;height:120px;object-fit:cover;border-radius:8px;border:1px solid #dcdcde">':'<div style="height:120px;background:#f0ece5;border-radius:8px"></div>')+
                   '<div style="font-weight:600;margin-top:4px">'+(i+1)+'. '+esc(p.title)+'</div>'+
                   '<div style="color:#646970">'+esc(p.cat)+(p.format?' · '+esc(p.format):'')+'</div>'+
                   (p.ampoule?'<div style="color:#646970">'+esc(p.ampoule)+'</div>':'')+
                   '</div>';
              });
              h+='</div>';
            }else{h+='<p><em>Aucun produit pour cette situation.</em></p>';}
            if(d.debug){h+='<details style="margin-top:12px"><summary style="cursor:pointer;color:#646970">Diagnostic</summary><pre style="font-size:11px;background:#f6f7f7;padding:10px;overflow:auto;max-height:300px">'+esc(JSON.stringify(d.debug,null,2))+'</pre></details>';}
            out.innerHTML=h;
          })
          .catch(function(){out.innerHTML='<span style="color:#b32d2e">Erreur réseau.</span>';});
      });
    })();
    </script>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px"
          onsubmit="return confirm('Réinitialiser toutes les règles aux valeurs par défaut ?');">
      <input type="hidden" name="action" value="sapi_reset_conseiller_rules">
      <?php wp_nonce_field('sapi_reset_conseiller_rules'); ?>
      <button type="submit" class="button button-secondary">Réinitialiser aux valeurs par défaut</button>
    </form>
  </div>
  <?php
}

/* ── Helpers de rendu ── */
function sapi_rules_card($title, $hint = '') {
  echo '<div class="card"><h2>' . esc_html($title) . '</h2>';
  if ($hint) echo '<p class="hint">' . esc_html($hint) . '</p>';
}

// Clé de formulaire d'une ligne : '' (ex. sortie par défaut) deviendrait un
// crochet VIDE rules[map][][] → PHP l'interprète comme un index numérique et
// PERD la valeur. On encode '' par un sentinel, décodé dans sapi_rules_sanitize.
function sapi_rules_fkey($rslug) {
  return ($rslug === '') ? '__empty' : $rslug;
}

// Map rows → cases à cocher multiples. $current = map row => liste (ou null).
function sapi_rules_table_map_multi($name, $rows, $options, $current) {
  echo '<table class="rt"><thead><tr><th>&nbsp;</th>';
  foreach ($options as $oslug => $olab) echo '<th>' . esc_html($olab) . '</th>';
  echo '</tr></thead><tbody>';
  foreach ($rows as $rslug => $rlab) {
    $fkey = sapi_rules_fkey($rslug);
    $sel = isset($current[$rslug]) && is_array($current[$rslug]) ? $current[$rslug] : [];
    echo '<tr><td class="rowlabel">' . esc_html($rlab) . '</td>';
    foreach ($options as $oslug => $olab) {
      $checked = in_array($oslug, $sel, true) ? ' checked' : '';
      echo '<td><input type="checkbox" name="rules[' . esc_attr($name) . '][' . esc_attr($fkey) . '][]" value="' . esc_attr($oslug) . '"' . $checked . '></td>';
    }
    echo '</tr>';
  }
  echo '</tbody></table>';
}

// Map rows → un select unique. $with_none ajoute une option vide. $none_label libellé.
function sapi_rules_table_map_single($name, $rows, $options, $current, $with_none = true, $none_label = 'Aucune') {
  echo '<table class="rt"><tbody>';
  foreach ($rows as $rslug => $rlab) {
    $fkey = sapi_rules_fkey($rslug);
    $cur = isset($current[$rslug]) ? $current[$rslug] : '';
    if ($cur === null) $cur = '';
    echo '<tr><td class="rowlabel">' . esc_html($rlab) . '</td><td>';
    echo '<select name="rules[' . esc_attr($name) . '][' . esc_attr($fkey) . ']">';
    if ($with_none) echo '<option value="">' . esc_html($none_label) . '</option>';
    foreach ($options as $oslug => $olab) {
      if ($oslug === '' && $with_none) continue; // évite doublon avec l'option « Aucune »
      echo '<option value="' . esc_attr($oslug) . '" ' . selected($cur, $oslug, false) . '>' . esc_html($olab) . '</option>';
    }
    echo '</select></td></tr>';
  }
  echo '</tbody></table>';
}

// Liste plate de cases à cocher. $current = liste de slugs sélectionnés.
function sapi_rules_flat_multi($name, $options, $current) {
  echo '<div>';
  foreach ($options as $oslug => $olab) {
    $checked = in_array($oslug, $current, true) ? ' checked' : '';
    echo '<label class="chk"><input type="checkbox" name="rules[' . esc_attr($name) . '][]" value="' . esc_attr($oslug) . '"' . $checked . '> ' . esc_html($olab) . '</label>';
  }
  echo '</div>';
}

// Select d'une réponse simulée (aperçu live). data-pa = clé de réponse.
function sapi_rules_preview_select($key, $label, $choices) {
  echo '<label style="display:inline-flex;flex-direction:column;font-size:12px;font-weight:600;margin:0 14px 10px 0">' . esc_html($label);
  echo '<select data-pa="' . esc_attr($key) . '" style="margin-top:3px;font-weight:400;min-width:150px">';
  echo '<option value="">(non précisé)</option>';
  foreach ((array) $choices as $slug => $lab) {
    echo '<option value="' . esc_attr($slug) . '">' . esc_html($lab) . '</option>';
  }
  echo '</select></label>';
}

// Choix (slug => label) d'une étape du questionnaire (pour les pickers aperçu).
function sapi_rules_step_choices($step_id) {
  $out = [];
  if (!function_exists('sapi_guide_get_steps')) return $out;
  foreach (sapi_guide_get_steps() as $s) {
    if (isset($s['id'], $s['choices']) && $s['id'] === $step_id && is_array($s['choices'])) {
      foreach ($s['choices'] as $c) {
        if (isset($c['slug'])) $out[$c['slug']] = isset($c['label']) ? $c['label'] : $c['slug'];
      }
      return $out;
    }
  }
  return $out;
}

/* ─────────────────────────────────────────────────────────────────────────
   Aperçu live (5.4) — endpoint AJAX. Exécute le VRAI moteur PHP avec les
   règles en cours d'édition (draft, NON sauvegardées) injectées via le filtre
   `sapi_conseiller_rules` le temps de la requête → l'aperçu == le site.
   ───────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_sapi_admin_filter_preview', 'sapi_rules_ajax_preview');
function sapi_rules_ajax_preview() {
  if (!current_user_can(SAPI_RULES_CAP)) wp_send_json_error(['msg' => 'forbidden'], 403);
  check_ajax_referer('sapi_rules_preview', 'nonce');

  // Règles « draft » = état du formulaire, sanitizé comme à l'enregistrement.
  $posted_rules = (isset($_POST['rules']) && is_array($_POST['rules'])) ? wp_unslash($_POST['rules']) : [];
  list($draft, $errors) = sapi_rules_sanitize($posted_rules);

  // Injection le temps de la requête : tout le moteur lit ces règles.
  $override = function ($rules) use ($draft) { return array_merge($rules, $draft); };
  add_filter('sapi_conseiller_rules', $override, 99);

  $answers = sapi_rules_preview_answers($_POST);
  $out = ['count' => 0, 'products' => [], 'cats' => [], 'answers' => $answers, 'notes' => [], 'errors' => $errors];

  if (!empty($answers['piece']) && function_exists('sapi_guide_get_categories')) {
    $cats = sapi_guide_get_categories($answers);
    $res  = function_exists('sapi_guide_query_products') ? sapi_guide_query_products($answers, $cats) : ['products' => []];
    $products = isset($res['products']) ? $res['products'] : [];
    if (function_exists('sapi_conseiller_rank_products')) {
      $products = sapi_conseiller_rank_products($products, $answers);
    }
    $out['cats']  = $cats;
    $out['notes'] = isset($res['fallback_notes']) ? $res['fallback_notes'] : [];
    $out['count'] = count($products);
    foreach ($products as $p) {
      $out['products'][] = [
        'id'      => isset($p['id']) ? (int) $p['id'] : 0,
        'title'   => isset($p['title']) ? $p['title'] : '',
        'image'   => isset($p['image']) ? $p['image'] : '',
        'cat'     => isset($p['category_label']) ? $p['category_label'] : '',
        'format'  => isset($p['format']) ? $p['format'] : '',
        'ampoule' => isset($p['type_ampoule']) ? $p['type_ampoule'] : '',
      ];
    }
  }

  // DEBUG temporaire (Tâche 5) — à retirer une fois l'aperçu validé.
  $out['debug'] = [
    'posted_has_rules'      => isset($_POST['rules']),
    'posted_rules_keys'     => array_keys($posted_rules),
    'posted_cats_keys'      => isset($posted_rules['cats_by_sortie']) ? array_keys($posted_rules['cats_by_sortie']) : 'MISSING',
    'draft_cats_by_sortie'  => $draft['cats_by_sortie'],
    'merged_cats_by_sortie' => sapi_conseiller_get_rules()['cats_by_sortie'],
    'answers'               => $answers,
  ];

  remove_filter('sapi_conseiller_rules', $override, 99);
  wp_send_json_success($out);
}

// Construit les réponses du visiteur simulé depuis $_POST['pa']. Les pickers
// fournissent les VRAIS slugs (dérivés des steps du questionnaire) → on passe
// la valeur sanitizée telle quelle au moteur (qui gère/ignore les inconnues).
function sapi_rules_preview_answers($post) {
  $pa = (isset($post['pa']) && is_array($post['pa'])) ? wp_unslash($post['pa']) : [];
  $V  = sapi_rules_vocab();
  $a = [];
  $piece = isset($pa['piece']) ? sanitize_key($pa['piece']) : '';
  if ($piece !== '' && isset($V['pieces'][$piece])) $a['piece'] = $piece;
  foreach (['sortie', 'taille', 'hauteur', 'eclairage', 'style'] as $k) {
    $v = isset($pa[$k]) ? sanitize_key($pa[$k]) : '';
    if ($v !== '') $a[$k] = $v;
  }
  return $a;
}
