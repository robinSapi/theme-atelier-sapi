<?php
/**
 * IMPORT PONCTUEL — à supprimer une fois passé sur test ET en prod.
 *
 * Remplit le champ « Ampoules compatibles » (meta _sapi_ampoules) des
 * variations de luminaires d'après le tableau validé par Robin le 25/11/2026
 * (Claude Sheet « Ampoules par variation »).
 *
 * URL : /wp-admin/tools.php?page=sapi-import-ampoules  (manage_options)
 * 1. L'écran montre chaque variation : modèle attendu, modèle trouvé,
 *    valeur actuelle, nouvelle valeur. Rien n'est écrit.
 * 2. « Appliquer » écrit uniquement les lignes au statut OK.
 * Idempotent : relancer donne le même résultat.
 */

if (!defined('ABSPATH')) exit;

/** variation_id => [nom du modèle, slug de taille ('' = sans taille), slug d'essence, ampoules dans l'ordre (recommandée en premier)] */
function sapi_import_ampoules_data() {
  return [
    8554 => ['Alban Le virevoltant', '25cm', 'okoume', 'Poire,Globe,Edison'],
    8555 => ['Alban Le virevoltant', '25cm', 'peuplier', 'Poire,Globe,Edison'],
    8556 => ['Alban Le virevoltant', '35cm', 'okoume', 'Poire,Globe,Edison'],
    8557 => ['Alban Le virevoltant', '35cm', 'peuplier', 'Poire,Globe,Edison'],
    8558 => ['Alban Le virevoltant', '45cm', 'okoume', 'Globe,Poire,Edison'],
    8559 => ['Alban Le virevoltant', '45cm', 'peuplier', 'Globe,Poire,Edison'],
    5899 => ['Alban Le virevoltant', '25cm', 'okoume', 'Poire,Globe,Edison'],
    5903 => ['Alban Le virevoltant', '25cm', 'peuplier', 'Poire,Globe,Edison'],
    9247 => ['Alban Le virevoltant', '25cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5900 => ['Alban Le virevoltant', '35cm', 'okoume', 'Poire,Globe,Edison'],
    5904 => ['Alban Le virevoltant', '35cm', 'peuplier', 'Poire,Globe,Edison'],
    9246 => ['Alban Le virevoltant', '35cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5901 => ['Alban Le virevoltant', '45cm', 'okoume', 'Globe,Poire,Edison'],
    5905 => ['Alban Le virevoltant', '45cm', 'peuplier', 'Globe,Poire,Edison'],
    9245 => ['Alban Le virevoltant', '45cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5902 => ['Alban Le virevoltant', '55cm', 'okoume', 'Globe,Poire,Edison'],
    5906 => ['Alban Le virevoltant', '55cm', 'peuplier', 'Globe,Poire,Edison'],
    9244 => ['Alban Le virevoltant', '55cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    10048 => ['Alice L’hélice', '20-cm-x-30-cm', 'okoume', 'Edison,Poire'],
    10047 => ['Alice L’hélice', '20-cm-x-30-cm', 'peuplier', 'Edison,Poire'],
    10050 => ['Alice L’hélice', '30-cm-x-40-cm', 'okoume', 'Edison,Poire,Globe'],
    10049 => ['Alice L’hélice', '30-cm-x-40-cm', 'peuplier', 'Edison,Poire,Globe'],
    9982 => ['Ariel La perle', '45cm', 'okoume', 'Poire'],
    9983 => ['Ariel La perle', '45cm', 'peuplier', 'Poire'],
    5689 => ['Arthus Le lotus', '55cm', 'okoume', 'Poire,Globe,Edison'],
    5692 => ['Arthus Le lotus', '55cm', 'peuplier', 'Poire,Globe,Edison'],
    5690 => ['Arthus Le lotus', '80-cm', 'okoume', 'Globe,Poire,Edison'],
    5693 => ['Arthus Le lotus', '80-cm', 'peuplier', 'Globe,Poire,Edison'],
    5688 => ['Arthus Le lotus', '110-cm', 'okoume', 'Globe,Poire,Edison'],
    5691 => ['Arthus Le lotus', '110-cm', 'peuplier', 'Globe,Poire,Edison'],
    7118 => ['Bertrand Le volcan', '20-cm-x-25-cm', 'okoume', 'Poire,Edison'],
    7119 => ['Bertrand Le volcan', '20-cm-x-25-cm', 'peuplier', 'Poire,Edison'],
    7972 => ['Bertrand Le volcan', '25-cm-x-35-cm', 'okoume', 'Globe,Poire,Edison'],
    7971 => ['Bertrand Le volcan', '25-cm-x-35-cm', 'peuplier', 'Globe,Poire,Edison'],
    6013 => ['Charlie Le pissenlit', '65-cm', 'okoume', 'Poire,Globe'],
    6015 => ['Charlie Le pissenlit', '65-cm', 'peuplier', 'Poire,Globe'],
    6014 => ['Charlie Le pissenlit', '80-cm', 'okoume', 'Globe,Poire'],
    6016 => ['Charlie Le pissenlit', '80-cm', 'peuplier', 'Globe,Poire'],
    5618 => ['Claudine La turbine', '65-cm', 'okoume', 'Poire,Globe'],
    5621 => ['Claudine La turbine', '65-cm', 'peuplier', 'Poire,Globe'],
    5617 => ['Claudine La turbine', '85-cm', 'okoume', 'Globe,Poire'],
    5620 => ['Claudine La turbine', '85-cm', 'peuplier', 'Globe,Poire'],
    9384 => ['Claudine La turbine', '50-cm', 'okoume', 'Poire'],
    9386 => ['Claudine La turbine', '50-cm', 'peuplier', 'Poire'],
    9385 => ['Claudine La turbine', '70cm', 'okoume', 'Globe,Poire'],
    9387 => ['Claudine La turbine', '70cm', 'peuplier', 'Globe,Poire'],
    5055 => ['Dalida Le dahlia', '', 'okoume', 'Poire,Globe'],
    5054 => ['Dalida Le dahlia', '', 'peuplier', 'Poire,Globe'],
    5928 => ['Dalida Le dahlia', '50-cm', 'okoume', 'Poire,Globe,Edison'],
    5932 => ['Dalida Le dahlia', '50-cm', 'peuplier', 'Poire,Globe,Edison'],
    9240 => ['Dalida Le dahlia', '50-cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5929 => ['Dalida Le dahlia', '70cm', 'okoume', 'Globe,Poire,Edison'],
    5933 => ['Dalida Le dahlia', '70cm', 'peuplier', 'Globe,Poire,Edison'],
    9241 => ['Dalida Le dahlia', '70cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5930 => ['Dalida Le dahlia', '90-cm', 'okoume', 'Globe,Poire,Edison'],
    5934 => ['Dalida Le dahlia', '90-cm', 'peuplier', 'Globe,Poire,Edison'],
    9242 => ['Dalida Le dahlia', '90-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5931 => ['Dalida Le dahlia', '110-cm', 'okoume', 'Globe,Poire,Edison'],
    5935 => ['Dalida Le dahlia', '110-cm', 'peuplier', 'Globe,Poire,Edison'],
    9243 => ['Dalida Le dahlia', '110-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5948 => ['Gaston Le chardon', '50-cm', 'okoume', 'Poire,Globe,Edison'],
    5952 => ['Gaston Le chardon', '50-cm', 'peuplier', 'Poire,Globe,Edison'],
    9248 => ['Gaston Le chardon', '50-cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5949 => ['Gaston Le chardon', '70cm', 'okoume', 'Poire,Globe,Edison'],
    5953 => ['Gaston Le chardon', '70cm', 'peuplier', 'Poire,Globe,Edison'],
    9249 => ['Gaston Le chardon', '70cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5950 => ['Gaston Le chardon', '90-cm', 'okoume', 'Globe,Poire,Edison'],
    5954 => ['Gaston Le chardon', '90-cm', 'peuplier', 'Globe,Poire,Edison'],
    9250 => ['Gaston Le chardon', '90-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5951 => ['Gaston Le chardon', '110-cm', 'okoume', 'Globe,Poire,Edison'],
    5955 => ['Gaston Le chardon', '110-cm', 'peuplier', 'Globe,Poire,Edison'],
    9251 => ['Gaston Le chardon', '110-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5910 => ['Irène La reine', '25-cm-x-30-cm', 'okoume', 'Edison,Poire'],
    5913 => ['Irène La reine', '25-cm-x-30-cm', 'peuplier', 'Edison,Poire'],
    9201 => ['Irène La reine', '25-cm-x-30-cm', 'peuplier-noir', 'Edison,Poire'],
    5911 => ['Irène La reine', '30-cm-x-40-cm', 'okoume', 'Edison,Poire'],
    5914 => ['Irène La reine', '30-cm-x-40-cm', 'peuplier', 'Edison,Poire'],
    9202 => ['Irène La reine', '30-cm-x-40-cm', 'peuplier-noir', 'Edison,Poire'],
    5912 => ['Irène La reine', '40-cm-x-30-cm', 'okoume', 'Globe,Poire,Edison'],
    5915 => ['Irène La reine', '40-cm-x-30-cm', 'peuplier', 'Globe,Poire,Edison'],
    9203 => ['Irène La reine', '40-cm-x-30-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    9445 => ['Léon L’accordéon', '33-cm-x-30-cm', 'okoume', 'Edison,Poire'],
    9447 => ['Léon L’accordéon', '33-cm-x-30-cm', 'peuplier', 'Edison,Poire'],
    9446 => ['Léon L’accordéon', '33-cm-x-40-cm', 'okoume', 'Edison,Poire'],
    9448 => ['Léon L’accordéon', '33-cm-x-40-cm', 'peuplier', 'Edison,Poire'],
    9461 => ['Léon L’accordéon', '40-cm-x-30-cm', 'okoume', 'Globe,Poire,Edison'],
    9460 => ['Léon L’accordéon', '40-cm-x-30-cm', 'peuplier', 'Globe,Poire,Edison'],
    9926 => ['Léon L’accordéon', '33-cm-x-30-cm', 'okoume', 'Edison,Poire'],
    9929 => ['Léon L’accordéon', '33-cm-x-30-cm', 'peuplier', 'Edison,Poire'],
    9927 => ['Léon L’accordéon', '33-cm-x-40-cm', 'okoume', 'Edison,Poire'],
    9930 => ['Léon L’accordéon', '33-cm-x-40-cm', 'peuplier', 'Edison,Poire'],
    9928 => ['Léon L’accordéon', '40-cm-x-30-cm', 'okoume', 'Globe,Poire,Edison'],
    9931 => ['Léon L’accordéon', '40-cm-x-30-cm', 'peuplier', 'Globe,Poire,Edison'],
    10125 => ['Mireille Le soleil', '85x40cm', 'okoume', 'Globe,Poire,Edison'],
    10126 => ['Mireille Le soleil', '85x40cm', 'peuplier', 'Globe,Poire,Edison'],
    6871 => ['Morane La gogane', '35-cm-x-30-cm', 'okoume', 'Poire'],
    6870 => ['Morane La gogane', '35-cm-x-30-cm', 'peuplier', 'Poire'],
    9366 => ['Morane La gogane', '45-x-20-cm', 'okoume', 'Globe,Poire'],
    9367 => ['Morane La gogane', '45-x-20-cm', 'peuplier', 'Globe,Poire'],
    6885 => ['Morane La gogane', '50-cm-x-35-cm', 'okoume', 'Globe,Poire'],
    6884 => ['Morane La gogane', '50-cm-x-35-cm', 'peuplier', 'Globe,Poire'],
    6857 => ['Odette La planète', '30-cm', 'okoume', 'Poire,Edison'],
    6858 => ['Odette La planète', '30-cm', 'peuplier', 'Poire,Edison'],
    6017 => ['Olivia La gardiena', '75-cm', 'okoume', 'Poire,Globe'],
    6019 => ['Olivia La gardiena', '75-cm', 'peuplier', 'Poire,Globe'],
    6018 => ['Olivia La gardiena', '100-cm', 'okoume', 'Globe,Poire'],
    6020 => ['Olivia La gardiena', '100-cm', 'peuplier', 'Globe,Poire'],
    5889 => ['Olivia La gardiena', '50-cm', 'okoume', 'Poire'],
    5893 => ['Olivia La gardiena', '50-cm', 'peuplier', 'Poire'],
    9253 => ['Olivia La gardiena', '50-cm', 'peuplier-noir', 'Poire'],
    5890 => ['Olivia La gardiena', '70cm', 'okoume', 'Poire'],
    5894 => ['Olivia La gardiena', '70cm', 'peuplier', 'Poire'],
    9254 => ['Olivia La gardiena', '70cm', 'peuplier-noir', 'Poire'],
    5891 => ['Olivia La gardiena', '90-cm', 'okoume', 'Poire'],
    5895 => ['Olivia La gardiena', '90-cm', 'peuplier', 'Poire'],
    9255 => ['Olivia La gardiena', '90-cm', 'peuplier-noir', 'Poire'],
    10146 => ['Olivia La gardiena', '110-cm', 'okoume', 'Globe,Poire'],
    10144 => ['Olivia La gardiena', '110-cm', 'peuplier', 'Globe,Poire'],
    10145 => ['Olivia La gardiena', '110-cm', 'peuplier-noir', 'Globe,Poire'],
    5892 => ['Olivia La gardiena', '130cm', 'okoume', 'Globe,Poire'],
    5896 => ['Olivia La gardiena', '130cm', 'peuplier', 'Globe,Poire'],
    9252 => ['Olivia La gardiena', '130cm', 'peuplier-noir', 'Globe,Poire'],
    5938 => ['Suze La méduse', '40-cm', 'okoume', 'Poire,Globe,Edison'],
    5942 => ['Suze La méduse', '40-cm', 'peuplier', 'Poire,Globe,Edison'],
    9239 => ['Suze La méduse', '40-cm', 'peuplier-noir', 'Poire,Globe,Edison'],
    5939 => ['Suze La méduse', '50-cm', 'okoume', 'Globe,Poire,Edison'],
    5943 => ['Suze La méduse', '50-cm', 'peuplier', 'Globe,Poire,Edison'],
    9238 => ['Suze La méduse', '50-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5940 => ['Suze La méduse', '60-cm', 'okoume', 'Globe,Poire,Edison'],
    5944 => ['Suze La méduse', '60-cm', 'peuplier', 'Globe,Poire,Edison'],
    9237 => ['Suze La méduse', '60-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5941 => ['Suze La méduse', '70cm', 'okoume', 'Globe,Poire,Edison'],
    5945 => ['Suze La méduse', '70cm', 'peuplier', 'Globe,Poire,Edison'],
    9236 => ['Suze La méduse', '70cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    9969 => ['Sébastien Le marin', '50-cm', 'okoume', 'Poire'],
    9970 => ['Sébastien Le marin', '50-cm', 'peuplier', 'Poire'],
    9971 => ['Sébastien Le marin', '70cm', 'okoume', 'Poire'],
    9972 => ['Sébastien Le marin', '70cm', 'peuplier', 'Poire'],
    10245 => ['Sébastien Le marin', '90-cm', 'okoume', 'Poire'],
    10244 => ['Sébastien Le marin', '90-cm', 'peuplier', 'Poire'],
    7921 => ['Timothée L’araignée', '50-cm', 'peuplier', 'Poire,Edison'],
    7922 => ['Timothée L’araignée', '70cm', 'peuplier', 'Poire,Edison'],
    7923 => ['Timothée L’araignée', '90-cm', 'peuplier', 'Globe,Poire,Edison'],
    7245 => ['Vincent L’incandescent', '18-cm-x-33cm', 'okoume', 'Poire,Globe'],
    7248 => ['Vincent L’incandescent', '18-cm-x-33cm', 'peuplier', 'Poire,Globe'],
    7246 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'okoume', 'Globe,Poire'],
    7249 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'peuplier', 'Globe,Poire'],
    7247 => ['Vincent L’incandescent', '32-cm-x-33-cm', 'okoume', 'Globe,Poire'],
    7250 => ['Vincent L’incandescent', '32-cm-x-33-cm', 'peuplier', 'Globe,Poire'],
    7144 => ['Vincent L’incandescent', '18-cm-x-33cm', 'okoume', 'Edison,Poire'],
    7146 => ['Vincent L’incandescent', '18-cm-x-33cm', 'peuplier', 'Edison,Poire'],
    7145 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'okoume', 'Globe,Poire,Edison'],
    7147 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'peuplier', 'Globe,Poire,Edison'],
    5919 => ['Vincent L’incandescent', '18-cm-x-33cm', 'okoume', 'Edison,Poire,Globe'],
    5922 => ['Vincent L’incandescent', '18-cm-x-33cm', 'peuplier', 'Edison,Poire,Globe'],
    9256 => ['Vincent L’incandescent', '18-cm-x-33cm', 'peuplier-noir', 'Edison,Poire,Globe'],
    5920 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'okoume', 'Globe,Poire,Edison'],
    5923 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'peuplier', 'Globe,Poire,Edison'],
    9257 => ['Vincent L’incandescent', '25-cm-x-40-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
    5921 => ['Vincent L’incandescent', '32-cm-x-33-cm', 'okoume', 'Globe,Poire,Edison'],
    5924 => ['Vincent L’incandescent', '32-cm-x-33-cm', 'peuplier', 'Globe,Poire,Edison'],
    9258 => ['Vincent L’incandescent', '32-cm-x-33-cm', 'peuplier-noir', 'Globe,Poire,Edison'],
  ];
}

function sapi_import_ampoules_norm($s) {
  // Entités et apostrophes typographiques ramenées à une forme unique (« L’hélice » = « L'hélice »).
  $s = html_entity_decode(wp_strip_all_tags((string) $s), ENT_QUOTES, 'UTF-8');
  $s = str_replace(['’', '‘', '`'], "'", $s);
  return trim(preg_replace('/\s+/u', ' ', strtolower(remove_accents($s))));
}

/**
 * Nom du tableau (Poire/Globe/Edison) => ID du produit ampoule de CE site.
 * Retourne [map, erreurs]. Une ampoule introuvable ou ambiguë bloque l'import.
 */
function sapi_import_ampoules_map() {
  $map = []; $err = [];
  $catalogue = function_exists('sapi_ampoule_catalogue') ? sapi_ampoule_catalogue() : [];
  foreach (['Poire', 'Globe', 'Edison'] as $label) {
    $hits = [];
    foreach ($catalogue as $amp) {
      if (strpos(sapi_import_ampoules_norm($amp->get_name()), strtolower($label)) !== false) $hits[] = $amp;
    }
    if (count($hits) === 1) {
      $map[$label] = $hits[0];
    } else {
      $err[] = sprintf('Ampoule « %s » : %d produit(s) trouvé(s) dans le catalogue des ampoules (il en faut exactement 1).', $label, count($hits));
    }
  }
  // Un même produit ne doit répondre qu'à un seul libellé (ex. « Ampoule globe Edison »).
  $ids = array_map(function ($a) { return $a->get_id(); }, $map);
  if (count(array_unique($ids)) !== count($ids)) {
    $err[] = 'Un même produit ampoule correspond à plusieurs libellés (Poire / Globe / Edison) : renommer les ampoules pour qu\'elles se distinguent.';
  }
  return [$map, $err];
}

/** Plan : une entrée par variation, avec son statut. Ne lit que. */
function sapi_import_ampoules_plan($map) {
  $plan = [];
  foreach (sapi_import_ampoules_data() as $vid => $row) {
    list($modele, $taille, $essence, $labels) = $row;
    $e = ['id' => $vid, 'modele' => $modele, 'taille' => $taille, 'essence' => $essence, 'trouve' => '—', 'avant' => [], 'apres' => [], 'statut' => 'ok'];
    $labels = array_filter(array_map('trim', explode(',', $labels)));
    foreach ($labels as $l) {
      if (isset($map[$l])) $e['apres'][] = $map[$l]->get_id();
    }
    $e['apres'] = array_values(array_unique($e['apres']));
    $v = wc_get_product($vid);
    if (!$v || !$v->is_type('variation')) {
      $e['statut'] = 'introuvable';
    } else {
      $parent = wc_get_product($v->get_parent_id());
      $e['trouve'] = $parent ? $parent->get_name() . ' — ' . wc_get_formatted_variation($v, true, false, false) : '?';
      // Même produit (nom complet), même taille et même essence que dans le tableau.
      $attrs = array_map('strval', array_values($v->get_attributes()));
      if (!$parent || sapi_import_ampoules_norm($parent->get_name()) !== sapi_import_ampoules_norm($modele)
          || ($taille !== '' && !in_array($taille, $attrs, true))
          || !in_array($essence, $attrs, true)) {
        $e['statut'] = 'modele';
      }
      $saved = get_post_meta($vid, '_sapi_ampoules', true);
      $e['avant'] = is_array($saved) ? array_values(array_map('intval', $saved)) : [];
      if ($e['statut'] === 'ok' && count($e['apres']) !== count($labels)) $e['statut'] = 'ampoule';
      // « Déjà à jour » seulement si la valeur stockée est exactement le tableau d'entiers attendu.
      if ($e['statut'] === 'ok' && is_array($saved) && array_values($saved) === $e['apres']) $e['statut'] = 'deja';
    }
    $plan[] = $e;
  }
  return $plan;
}

add_action('admin_menu', function () {
  add_management_page('Import ampoules', 'Import ampoules', 'manage_options', 'sapi-import-ampoules', 'sapi_import_ampoules_page');
});

function sapi_import_ampoules_page() {
  if (!current_user_can('manage_options')) wp_die('Accès refusé.');
  list($map, $err) = sapi_import_ampoules_map();

  $ecrites = null;
  if (!$err && isset($_POST['sapi_import_ampoules']) && check_admin_referer('sapi_import_ampoules')) {
    $ecrites = 0; $parents = [];
    foreach (sapi_import_ampoules_plan($map) as $e) {
      if ($e['statut'] !== 'ok') continue;
      update_post_meta($e['id'], '_sapi_ampoules', $e['apres']);
      $parents[wp_get_post_parent_id($e['id'])] = true;
      $ecrites++;
    }
    foreach (array_keys($parents) as $pid) wc_delete_product_transients($pid);
  }

  $plan = $err ? [] : sapi_import_ampoules_plan($map);
  $compte = array_count_values(wp_list_pluck($plan, 'statut'));
  $nom = function ($ids) use ($map) {
    if (!$ids) return '<em>vide (ventes croisées)</em>';
    $flip = [];
    foreach ($map as $label => $amp) $flip[$amp->get_id()] = $label;
    return esc_html(implode(' › ', array_map(function ($id) use ($flip) { return $flip[$id] ?? ('#' . $id); }, $ids)));
  };
  $libelles = [
    'ok'          => ['À écrire', '#2271b1'],
    'deja'        => ['Déjà à jour', '#00a32a'],
    'modele'      => ['Modèle, taille ou essence différent — ignorée', '#d63638'],
    'introuvable' => ['Variation introuvable — ignorée', '#d63638'],
    'ampoule'     => ['Ampoule manquante — ignorée', '#d63638'],
  ];
  ?>
  <div class="wrap">
    <h1>Import des ampoules par variation</h1>
    <p>Outil ponctuel : remplit « Ampoules compatibles » sur les <?php echo count(sapi_import_ampoules_data()); ?> variations du tableau validé le 25/11/2026. Seules les lignes « À écrire » sont modifiées.</p>

    <?php if ($err) : ?>
      <div class="notice notice-error"><p><strong>Import bloqué.</strong><br><?php echo implode('<br>', array_map('esc_html', $err)); ?></p></div>
    <?php else : ?>
      <p><strong>Ampoules de ce site :</strong>
        <?php foreach ($map as $label => $amp) echo esc_html($label . ' = ' . $amp->get_name() . ' (#' . $amp->get_id() . ')') . ' &nbsp; '; ?></p>
    <?php endif; ?>

    <?php if ($ecrites !== null) : ?>
      <div class="notice notice-success"><p><strong><?php echo (int) $ecrites; ?> variation(s) écrite(s).</strong> Le tableau ci-dessous montre l'état après import.</p></div>
    <?php endif; ?>

    <?php if ($plan) : ?>
      <p><?php foreach ($libelles as $k => $l) echo '<span style="color:' . $l[1] . '">' . esc_html($l[0]) . ' : <strong>' . (int) ($compte[$k] ?? 0) . '</strong></span> &nbsp; '; ?></p>
      <?php if (!empty($compte['ok'])) : ?>
        <form method="post" style="margin:12px 0">
          <?php wp_nonce_field('sapi_import_ampoules'); ?>
          <button class="button button-primary" name="sapi_import_ampoules" value="1">Appliquer aux <?php echo (int) $compte['ok']; ?> variations « À écrire »</button>
        </form>
      <?php endif; ?>
      <table class="widefat striped">
        <thead><tr><th>ID</th><th>Attendu</th><th>Variation trouvée</th><th>Avant</th><th>Après</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($plan as $e) : $l = $libelles[$e['statut']]; ?>
          <tr>
            <td><a href="<?php echo esc_url(get_edit_post_link(wp_get_post_parent_id($e['id']) ?: $e['id'])); ?>">#<?php echo (int) $e['id']; ?></a></td>
            <td><?php echo esc_html($e['modele'] . ' — ' . ($e['taille'] ?: 'sans taille') . ', ' . $e['essence']); ?></td>
            <td><?php echo esc_html($e['trouve']); ?></td>
            <td><?php echo $nom($e['avant']); ?></td>
            <td><?php echo $nom($e['apres']); ?></td>
            <td style="color:<?php echo $l[1]; ?>"><?php echo esc_html($l[0]); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php
}
