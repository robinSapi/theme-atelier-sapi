<?php
/**
 * Page admin custom : migration repeater galerie_produit → 8 Gallery ACF.
 * S28 Phase 2 — Dual-write : copie les photos du repeater vers les Gallery,
 * le repeater reste intact en lecture et en données.
 *
 * URL : /wp-admin/tools.php?page=sapi-migrate-galerie
 * Restriction : current_user_can('manage_options')
 *
 * 2 modes :
 *  - DRY-RUN  : lecture seule, produit le rapport sans écrire en DB
 *  - RÉEL     : écrit dans les 8 Gallery via ACF update_field, idempotent
 *
 * Garde-fous :
 *  - NE PAS toucher au repeater galerie_produit (aucun delete/update sur lui)
 *  - NE PAS modifier les attachments eux-mêmes
 *  - Idempotent : 2 runs successifs en mode RÉEL = même état final
 *  - Préserve l'ordre du repeater (1re photo ambiance du repeater = 1re position de galerie_ambiance)
 *  - Types orphelins listés explicitement, NON migrés
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Mapping type_photo (string normalisé du repeater) → slug Gallery ACF cible.
 * Voir Cowork project_photos_par_piece.md pour le détail des décisions.
 */
function sapi_migrate_galerie_type_map() {
  return [
    'ambiance'         => 'galerie_ambiance',
    'detail'           => 'galerie_detail',
    'vue de dessous'   => 'galerie_vue_de_dessous',
    'taille'           => 'galerie_tailles',  // singulier dans repeater → pluriel dans Gallery
    'tailles'          => 'galerie_tailles',  // tolérance si déjà corrigé en pluriel
    'studio'           => 'galerie_packshot', // legacy "studio" remappé sur packshot
    'packshot'         => 'galerie_packshot',
    'fabrication'      => 'galerie_fabrication',
    'client'           => 'galerie_client',
    'accessoires'      => 'galerie_accessoires',
  ];
}

/**
 * Liste des 8 Gallery cibles (slugs ACF) — créés en Phase 1.
 */
function sapi_migrate_galerie_target_galleries() {
  return [
    'galerie_ambiance',
    'galerie_detail',
    'galerie_vue_de_dessous',
    'galerie_tailles',
    'galerie_packshot',
    'galerie_fabrication',
    'galerie_client',
    'galerie_accessoires',
  ];
}

/**
 * Normalise une valeur Gallery ACF (tableau d'IDs, tableau d'arrays ACF
 * ou tableau d'URLs) en tableau d'IDs entiers, sans 0.
 */
function sapi_migrate_galerie_normalize_ids($value) {
  if (empty($value) || !is_array($value)) return [];
  $ids = [];
  foreach ($value as $item) {
    $id = sapi_get_acf_image_id($item);
    if ($id) $ids[] = (int) $id;
  }
  return $ids;
}

/**
 * Construit le plan de migration pour tous les produits publiés.
 * Lit le repeater via sapi_iterate_product_photos + lit les 8 Gallery
 * existantes. NE FAIT AUCUNE ÉCRITURE.
 *
 * @return array Structure complète plan + totaux (voir clés ci-dessous).
 */
function sapi_migrate_galerie_build_plan() {
  $map = sapi_migrate_galerie_type_map();
  $targets = sapi_migrate_galerie_target_galleries();

  // Volume contrôlé (~24-30 produits, cf. CLAUDE.md "24 fiches").
  // posts_per_page=-1 acceptable ici car action admin one-shot, jamais en front.
  $product_ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'orderby'        => 'title',
    'order'          => 'ASC',
  ]);

  $products = [];
  $totals = [
    'products_scanned'            => 0,
    'products_with_repeater'      => 0,
    'total_photos_repeater'       => 0,
    'galleries_already_populated' => 0,
    'photos_to_migrate'           => 0,
    'orphan_count'                => 0,
    'orphan_types'                => [], // label => count global
  ];

  foreach ($product_ids as $pid) {
    $totals['products_scanned']++;

    // Toujours lire l'état actuel des 8 Gallery (même si repeater vide),
    // pour compter correctement la snapshot "État actuel".
    $existing = [];
    foreach ($targets as $gallery) {
      $val = function_exists('get_field') ? get_field($gallery, $pid) : null;
      $existing_ids = sapi_migrate_galerie_normalize_ids($val);
      $existing[$gallery] = $existing_ids;
      if (!empty($existing_ids)) {
        $totals['galleries_already_populated']++;
      }
    }

    $rows = sapi_iterate_product_photos($pid);
    if (empty($rows)) {
      continue;
    }
    $totals['products_with_repeater']++;
    $totals['total_photos_repeater'] += count($rows);

    $plan = [];
    $orphans = [];
    foreach ($rows as $row) {
      $type = $row['type'];
      $img_id = $row['image_id'];
      if (isset($map[$type])) {
        $gallery = $map[$type];
        if (!isset($plan[$gallery])) $plan[$gallery] = [];
        $plan[$gallery][] = $img_id;
        $totals['photos_to_migrate']++;
      } else {
        $label = $type === '' ? '(type vide)' : $type;
        if (!isset($orphans[$label])) $orphans[$label] = [];
        $orphans[$label][] = $img_id;
        $totals['orphan_count']++;
        $totals['orphan_types'][$label] = (isset($totals['orphan_types'][$label]) ? $totals['orphan_types'][$label] : 0) + 1;
      }
    }

    $products[$pid] = [
      'title'          => get_the_title($pid),
      'repeater_count' => count($rows),
      'plan'           => $plan,
      'existing'       => $existing,
      'orphans'        => $orphans,
    ];
  }

  return ['products' => $products, 'totals' => $totals];
}

/**
 * Exécute le mode RÉEL : pour chaque produit, fusionne plan + existing
 * de manière idempotente (array_unique préserve l'ordre) et écrit via
 * ACF update_field (PAS update_post_meta direct).
 *
 * Le repeater galerie_produit n'est JAMAIS touché.
 *
 * @return array [$product_id => [$gallery => 'OK'|'SKIPPED'|'ERROR']]
 */
function sapi_migrate_galerie_execute($plan_data) {
  $results = [];
  foreach ($plan_data['products'] as $pid => $info) {
    $results[$pid] = [];
    foreach ($info['plan'] as $gallery => $plan_ids) {
      $existing_ids = isset($info['existing'][$gallery]) ? $info['existing'][$gallery] : [];
      // Diff idempotente : préserve l'ordre + déduplique.
      // existing en premier → si on relance, les IDs déjà migrés sont
      // déjà dans existing, le merge ne change rien.
      $merged = array_values(array_unique(array_merge($existing_ids, $plan_ids)));
      if ($merged === $existing_ids) {
        $results[$pid][$gallery] = 'SKIPPED';
        continue;
      }
      if (!function_exists('update_field')) {
        $results[$pid][$gallery] = 'ERROR';
        continue;
      }
      $ok = update_field($gallery, $merged, $pid);
      $results[$pid][$gallery] = $ok ? 'OK' : 'ERROR';
    }
  }
  return $results;
}

/**
 * Ajoute le sous-menu Outils → Migration galerie.
 */
function sapi_migrate_galerie_admin_menu() {
  add_submenu_page(
    'tools.php',
    'Migration galerie produit',
    'Migration galerie',
    'manage_options',
    'sapi-migrate-galerie',
    'sapi_migrate_galerie_render_page'
  );
}
add_action('admin_menu', 'sapi_migrate_galerie_admin_menu');

/**
 * Render de la page admin (UI + handling POST).
 */
function sapi_migrate_galerie_render_page() {
  if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Accès refusé.', 'theme-sapi-maison'));
  }

  // Détection action POST + vérification nonce CSRF.
  $action = '';
  $action_result = null;
  if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sapi_action'])) {
    $requested = sanitize_key(wp_unslash($_POST['sapi_action']));
    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
    if ($requested === 'dryrun' && wp_verify_nonce($nonce, 'sapi_migrate_galerie_dryrun')) {
      $action = 'dryrun';
    } elseif ($requested === 'real' && wp_verify_nonce($nonce, 'sapi_migrate_galerie_real')) {
      $action = 'real';
    }
  }

  // Toujours construire le plan : pour la snapshot "État actuel" et pour
  // l'action si demandée.
  $plan_data = sapi_migrate_galerie_build_plan();

  if ($action === 'real') {
    $action_result = sapi_migrate_galerie_execute($plan_data);
    // Après écriture, l'état actuel a changé : re-snapshot pour afficher
    // la nouvelle situation des Gallery.
    $plan_data = sapi_migrate_galerie_build_plan();
  }

  $totals = $plan_data['totals'];
  $targets = sapi_migrate_galerie_target_galleries();

  ?>
  <div class="wrap">
    <h1>Migration repeater <code>galerie_produit</code> → 8 Gallery ACF</h1>

    <p>
      Phase 2 du chantier <em>Photos par pièce</em> (S28). Copie les photos
      classées dans le repeater <code>galerie_produit</code> vers les 8
      Gallery ACF cibles selon le <code>type_photo</code> de chaque row.
      Le repeater reste intact (dual-write). L'opération est idempotente :
      relancer ne duplique rien.
    </p>

    <h2>État actuel</h2>
    <ul style="line-height:1.7;">
      <li>Produits scannés : <strong><?php echo (int) $totals['products_scanned']; ?></strong></li>
      <li>Produits avec repeater non vide : <strong><?php echo (int) $totals['products_with_repeater']; ?></strong></li>
      <li>Total photos dans les repeaters : <strong><?php echo (int) $totals['total_photos_repeater']; ?></strong></li>
      <li>Couples (produit × Gallery) déjà peuplés : <strong><?php echo (int) $totals['galleries_already_populated']; ?></strong></li>
      <li>Photos prêtes à migrer (mapping connu) : <strong><?php echo (int) $totals['photos_to_migrate']; ?></strong></li>
      <li>Types orphelins (non migrés) : <strong><?php echo (int) $totals['orphan_count']; ?></strong>
        <?php if (!empty($totals['orphan_types'])) : ?>
          — détail :
          <?php
          $bits = [];
          foreach ($totals['orphan_types'] as $label => $n) {
            $bits[] = '<code>' . esc_html($label) . '</code> ×' . (int) $n;
          }
          echo wp_kses(implode(', ', $bits), ['code' => []]);
          ?>
        <?php endif; ?>
      </li>
    </ul>

    <h2>Actions</h2>

    <form method="post" style="display:inline-block; margin-right:16px; vertical-align:top;">
      <?php wp_nonce_field('sapi_migrate_galerie_dryrun'); ?>
      <input type="hidden" name="sapi_action" value="dryrun">
      <button type="submit" class="button button-primary">Lancer en DRY-RUN</button>
      <p class="description" style="margin-top:4px; max-width:280px;">Lecture seule. Aucune écriture en base.</p>
    </form>

    <form method="post" style="display:inline-block; vertical-align:top;"
          onsubmit="return confirm('Lancer la migration en mode RÉEL ?\n\nLe repeater galerie_produit ne sera pas touché.\nLes 8 Gallery ACF cibles seront écrites via update_field.\nLa migration est idempotente : pas de doublons si relancée.');">
      <?php wp_nonce_field('sapi_migrate_galerie_real'); ?>
      <input type="hidden" name="sapi_action" value="real">
      <button type="submit" class="button" style="background:#E35B24; border-color:#E35B24; color:#fff;">Lancer en mode RÉEL</button>
      <p class="description" style="margin-top:4px; max-width:280px;">Écrit dans les 8 Gallery via <code>update_field()</code>. Dual-write : repeater intact.</p>
    </form>

    <?php if ($action === 'dryrun' || $action === 'real') : ?>
      <h2>Rapport — <?php echo $action === 'dryrun' ? 'DRY-RUN' : 'Mode RÉEL'; ?></h2>
      <?php sapi_migrate_galerie_render_report($plan_data, $action, $action_result, $targets); ?>
    <?php endif; ?>
  </div>
  <?php
}

/**
 * Rendu du tableau récapitulatif de rapport.
 */
function sapi_migrate_galerie_render_report($plan_data, $action, $action_result, $targets) {
  $products = $plan_data['products'];
  if (empty($products)) {
    echo '<p>Aucun produit avec repeater non vide à traiter.</p>';
    return;
  }

  echo '<table class="widefat striped" style="margin-top:12px;">';
  echo '<thead><tr>';
  echo '<th>Produit</th>';
  echo '<th>Repeater</th>';
  foreach ($targets as $g) {
    $short = str_replace('galerie_', '', $g);
    echo '<th title="' . esc_attr($g) . '">' . esc_html($short) . '</th>';
  }
  if ($action === 'real') {
    echo '<th>Résultat</th>';
  }
  echo '<th>Orphelins</th>';
  echo '</tr></thead><tbody>';

  foreach ($products as $pid => $info) {
    echo '<tr>';
    $edit_link = get_edit_post_link($pid);
    $title = $info['title'] !== '' ? $info['title'] : ('#' . $pid);
    if ($edit_link) {
      echo '<td><a href="' . esc_url($edit_link) . '" target="_blank">' . esc_html($title) . '</a> <small>(#' . (int) $pid . ')</small></td>';
    } else {
      echo '<td>' . esc_html($title) . ' <small>(#' . (int) $pid . ')</small></td>';
    }
    echo '<td>' . (int) $info['repeater_count'] . ' photos</td>';

    foreach ($targets as $g) {
      $plan_ids = isset($info['plan'][$g]) ? $info['plan'][$g] : [];
      $existing_ids = isset($info['existing'][$g]) ? $info['existing'][$g] : [];
      $cell = '';
      if (!empty($plan_ids)) {
        $cell .= '<strong>+' . count($plan_ids) . '</strong>';
      }
      if (!empty($existing_ids)) {
        $cell .= ' <small style="color:#777;">(déjà ' . count($existing_ids) . ')</small>';
      }
      if ($cell === '') $cell = '—';
      echo '<td>' . wp_kses($cell, ['strong' => [], 'small' => ['style' => []]]) . '</td>';
    }

    if ($action === 'real') {
      $results = isset($action_result[$pid]) ? $action_result[$pid] : [];
      if (empty($results)) {
        echo '<td><em>rien à faire</em></td>';
      } else {
        $parts = [];
        foreach ($results as $g => $status) {
          $short = str_replace('galerie_', '', $g);
          $color = '#777';
          if ($status === 'OK')        $color = '#2e7d32';
          elseif ($status === 'ERROR') $color = '#c62828';
          $parts[] = '<span style="color:' . esc_attr($color) . ';">' . esc_html($short) . ' : ' . esc_html($status) . '</span>';
        }
        echo '<td>' . wp_kses(implode('<br>', $parts), ['span' => ['style' => []], 'br' => []]) . '</td>';
      }
    }

    if (!empty($info['orphans'])) {
      $orphan_parts = [];
      foreach ($info['orphans'] as $label => $ids) {
        $orphan_parts[] = '<strong>' . esc_html($label) . '</strong> ×' . count($ids);
      }
      echo '<td style="color:#c62828;">' . wp_kses(implode('<br>', $orphan_parts), ['strong' => [], 'br' => []]) . '</td>';
    } else {
      echo '<td>—</td>';
    }

    echo '</tr>';
  }
  echo '</tbody></table>';

  $totals = $plan_data['totals'];
  echo '<p style="margin-top:12px;">';
  echo '<strong>Total :</strong> ' . (int) count($products) . ' produits avec repeater non vide, ';
  echo (int) $totals['photos_to_migrate'] . ' photos ' . ($action === 'real' ? 'traitées' : 'planifiées') . ', ';
  echo (int) $totals['orphan_count'] . ' photos en types orphelins (non migrées).';
  echo '</p>';

  if ($totals['orphan_count'] > 0) {
    echo '<p style="color:#c62828;"><strong>⚠ Types orphelins détectés.</strong> Ces photos ne sont migrées dans aucune Gallery. À clarifier avec Robin avant de relancer en mode RÉEL ou avant d\'envisager la suite (Phase 3 dual-read).</p>';
  }
}
