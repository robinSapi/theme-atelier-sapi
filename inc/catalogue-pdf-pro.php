<?php
/**
 * Catalogue — export PDF TARIF PROFESSIONNEL (admin uniquement).
 *
 * ⚠️ DÉCISION D'ARCHITECTURE À NE PAS DÉFAIRE : le tarif professionnel n'existe
 * JAMAIS sur le web. Aucune page, aucun endpoint public, aucun lien secret ne
 * contient de prix remisé. Le taux est saisi par Robin dans l'admin au moment de
 * générer un PDF, et ne vit que le temps de cette génération.
 * Conséquence directe : un PDF à -12% pour un client historique et un autre à
 * -30% pour un prospect, sans que le site ait jamais à savoir qui est qui.
 *
 * Réutilise sans les redévelopper :
 *   - inc/catalogue-data.php     → produits, specs, galeries (couche SANS prix)
 *   - inc/catalogue-data-pro.php → PVP TTC par variation + conversion HT
 *   - inc/catalogue-pdf.php      → instance mPDF, CSS, images, noms produit, cache
 *
 * La route REST publique /sapi/v1/catalogue-pdf n'est PAS touchée : elle
 * continue de servir un PDF sans le moindre prix.
 */

if (!defined('ABSPATH')) exit;

if (!defined('SAPI_CATALOGUE_PRO_CAP'))        define('SAPI_CATALOGUE_PRO_CAP', 'manage_woocommerce');
if (!defined('SAPI_CATALOGUE_PRO_OPTION'))     define('SAPI_CATALOGUE_PRO_OPTION', 'sapi_catalogue_pro_settings');
if (!defined('SAPI_CATALOGUE_PRO_LOG_OPTION')) define('SAPI_CATALOGUE_PRO_LOG_OPTION', 'sapi_catalogue_pro_log');
/** Nombre d'entrées conservées dans le journal des exports. */
if (!defined('SAPI_CATALOGUE_PRO_LOG_MAX'))    define('SAPI_CATALOGUE_PRO_LOG_MAX', 20);
/** Au-delà de ce taux, l'admin demande une confirmation explicite. */
if (!defined('SAPI_CATALOGUE_PRO_RATE_WARN'))  define('SAPI_CATALOGUE_PRO_RATE_WARN', 35.0);
/** Durée de rétention des PDF pro en cache (ils portent des noms de clients). */
if (!defined('SAPI_CATALOGUE_PRO_TTL_DAYS'))   define('SAPI_CATALOGUE_PRO_TTL_DAYS', 30);

/* =========================================================================
 * 1. Réglages persistés + journal des exports
 * ========================================================================= */

/**
 * Texte par défaut des mentions légales du tarif pro.
 * Les crochets [à compléter] sont VOLONTAIRES : Robin les remplit une fois depuis
 * l'admin, la valeur est ensuite persistée et ne repasse plus jamais par le code.
 * @return string
 */
function sapi_catalogue_pro_default_mentions() {
  // ⚠️ Pas de titre « Tarifs professionnels » en tête : la page porte désormais
  // ce titre elle-même (mise en page alignée sur Histoire / Deux bois).
  return
    "Prix exprimés en euros hors taxes, TVA 20% en sus. Le prix public conseillé est indiqué à titre indicatif et n'a aucun caractère contractuel : le revendeur reste libre de fixer son prix de vente.\n\n" .
    "Éco-participation\n" .
    "Une éco-participation DEEE de 0,25 € par pièce s'ajoute aux prix indiqués, conformément à la réglementation applicable aux équipements électriques et électroniques.\n\n" .
    "Commande et livraison\n" .
    "Minimum de commande : [à compléter].\n" .
    "Frais de port : [à compléter, ou franco de port à partir de X € HT].\n" .
    "Délai de fabrication : [à compléter] semaines à réception de la commande.\n" .
    "Conditions de règlement : [à compléter].\n\n" .
    "Produits\n" .
    "Luminaires fabriqués à la main dans notre atelier lyonnais, en contreplaqué de peuplier ou d'okoumé. Le bois étant un matériau vivant, de légères variations de teinte et de veinage sont normales et ne constituent pas un défaut. Ampoules non incluses sauf mention contraire.\n\n" .
    "Validité\n" .
    "Ce tarif est valable jusqu'à la date indiquée en page de garde. Passé ce délai, il ne saurait engager Atelier Sâpi. Document confidentiel, destiné au seul destinataire mentionné.\n\n" .
    "Atelier Sâpi, SASU au capital de [à compléter], SIRET 94267058900012, TVA intracommunautaire FR59942670589, Collonges-au-Mont-d'Or.";
}

/**
 * Valeurs par défaut du formulaire d'export.
 * @return array
 */
function sapi_catalogue_pro_defaults() {
  $year = (int) current_time('Y');
  return [
    'rate'     => 30.0,                       // en POURCENTAGE dans le formulaire
    'ids'      => [],                         // vide = tous les produits cochés
    'client'   => '',
    'date'     => current_time('Y-m-d'),
    'valid'    => sprintf('%d-12-31', $year),
    'mentions' => sapi_catalogue_pro_default_mentions(),
  ];
}

/**
 * Réglages du dernier export, complétés par les défauts.
 *
 * ⚠️ `client` repart TOUJOURS vide, quoi qu'il y ait en base : ne jamais envoyer
 * à Ankorstore un tarif encore au nom de Muse. La date d'export repart également
 * sur aujourd'hui — un tarif daté d'il y a trois semaines n'a pas de sens.
 *
 * @return array
 */
function sapi_catalogue_pro_get_settings() {
  $saved = get_option(SAPI_CATALOGUE_PRO_OPTION, []);
  if (!is_array($saved)) $saved = [];
  $out = array_merge(sapi_catalogue_pro_defaults(), $saved);
  $out['client'] = '';
  $out['date']   = current_time('Y-m-d');
  $out['ids']    = isset($out['ids']) && is_array($out['ids']) ? array_map('intval', $out['ids']) : [];
  return $out;
}

/**
 * Persiste les réglages d'un export pour le proposer au suivant.
 * `client` n'est jamais enregistré (cf. sapi_catalogue_pro_get_settings()).
 * @param array $args
 */
function sapi_catalogue_pro_save_settings($args) {
  update_option(SAPI_CATALOGUE_PRO_OPTION, [
    'rate'     => (float) $args['rate_pct'],
    'ids'      => array_map('intval', $args['ids']),
    'valid'    => (string) $args['valid'],
    'mentions' => (string) $args['mentions'],
  ], false);
}

/** @return array<int,array> journal des exports, du plus récent au plus ancien */
function sapi_catalogue_pro_get_log() {
  $log = get_option(SAPI_CATALOGUE_PRO_LOG_OPTION, []);
  return is_array($log) ? $log : [];
}

/**
 * Ajoute une entrée au journal des exports.
 * Trois lignes de code, et la réponse le jour où un revendeur affirme qu'on lui
 * avait promis autre chose.
 */
function sapi_catalogue_pro_log_export($args) {
  $log = sapi_catalogue_pro_get_log();
  array_unshift($log, [
    'when'     => current_time('mysql'),
    'rate'     => (float) $args['rate_pct'],
    'client'   => (string) $args['client'],
    'products' => count($args['ids']),
    'user'     => wp_get_current_user()->user_login,
  ]);
  update_option(SAPI_CATALOGUE_PRO_LOG_OPTION, array_slice($log, 0, SAPI_CATALOGUE_PRO_LOG_MAX), false);
}

/* =========================================================================
 * 2. Cache dédié — le PDF pro ne passe PAS par le cache public
 * ========================================================================= */

/**
 * Chemin de cache d'un export pro.
 *
 * La clé couvre TOUT ce qui change le document : IDs triés, stamp produits,
 * version du générateur, taux, client, dates, ET le texte des mentions — sans ce
 * dernier, corriger une coquille dans les mentions resservirait l'ancien fichier.
 *
 * Même dossier protégé que le PDF public (uploads/catalogues, .htaccess + clé
 * non devinable car salée par les clés secrètes WP), préfixe `pro-` pour que la
 * purge à 30 jours ne touche que ces fichiers-là.
 *
 * @param array $args
 * @return string
 */
function sapi_catalogue_pro_cache_path($args) {
  $ids = array_map('intval', $args['ids']);
  sort($ids);
  $key = wp_hash(implode(',', $ids)
    . '|' . sapi_catalogue_pdf_stamp()
    . '|v' . SAPI_CATALOGUE_PDF_VERSION
    . '|r' . number_format((float) $args['rate_pct'], 4, '.', '')
    . '|c' . $args['client']
    . '|d' . $args['date']
    . '|e' . $args['valid']
    . '|m' . md5((string) $args['mentions'])
  );
  return trailingslashit(sapi_catalogue_pdf_dir()) . 'pro-' . $key . '.pdf';
}

/**
 * Supprime les PDF pro de plus de SAPI_CATALOGUE_PRO_TTL_DAYS jours.
 * Ils portent des noms de clients : ils n'ont pas à s'accumuler indéfiniment.
 * @return int nombre de fichiers supprimés
 */
function sapi_catalogue_pro_purge_old() {
  $dir     = sapi_catalogue_pdf_dir();
  $cutoff  = time() - (SAPI_CATALOGUE_PRO_TTL_DAYS * DAY_IN_SECONDS);
  $deleted = 0;
  foreach ((glob(trailingslashit($dir) . 'pro-*.pdf') ?: []) as $file) {
    $mtime = @filemtime($file);
    if ($mtime && $mtime < $cutoff && @unlink($file)) $deleted++;
  }
  return $deleted;
}

/**
 * Sert le cache s'il existe, sinon génère et écrit.
 * Pas de pré-génération : chaque sélection est quasi unique, le cache ne sert
 * qu'au ré-export à l'identique après correction d'une coquille.
 *
 * @param array $args
 * @return string|null chemin du fichier
 */
function sapi_catalogue_pro_get_or_build($args) {
  $path = sapi_catalogue_pro_cache_path($args);
  if (file_exists($path) && filesize($path) > 0) return $path;

  $pdf = sapi_catalogue_pro_pdf_build($args);
  if ($pdf === '' || $pdf === null) return null;
  file_put_contents($path, $pdf);
  return $path;
}

/* =========================================================================
 * 3. Génération du document
 * ========================================================================= */

/**
 * CSS additionnelle du tarif pro (le gabarit de base vient de
 * sapi_catalogue_pdf_css()). Sous-ensemble mPDF : ni flex, ni grid.
 * @return string
 */
function sapi_catalogue_pro_pdf_css() {
  return '<style>
    .cover .subtitle-pro { font-family: montserrat; font-size: 20pt; letter-spacing: 2px; text-transform: uppercase; color: #323232; margin-top: 6mm; }
    .cover .client { font-family: montserrat; font-size: 13pt; color: #323232; margin-top: 12mm; }
    .cover .client .label { font-size: 9pt; text-transform: uppercase; letter-spacing: 1.5px; color: #937D68; }
    .cover .dates { font-size: 9.5pt; color: #6a6055; margin-top: 8mm; }

    /* Le titre reprend .section-title (Histoire / Bois) — même famille, même
       corps, même couleur : les trois pages d’introduction sont identiques. */
    .mentions .body { font-size: 9pt; color: #4a443d; line-height: 1.45; text-align: justify; }
    .mentions .body p { margin: 0 0 2.2mm; }

    /* Tableau de prix pleine largeur, sous les vignettes, avant les caractéristiques */
    .pro-prices { width: 100%; border-collapse: collapse; margin: 1mm 0 0; }
    .pro-prices caption { font-family: montserrat; font-weight: bold; font-size: 8pt; text-transform: uppercase; letter-spacing: 1px; color: #E35B24; text-align: left; padding-bottom: 1mm; }
    .pro-prices th.h { font-family: montserrat; font-weight: bold; font-size: 7pt; text-transform: uppercase; letter-spacing: .5px; color: #937D68; border-bottom: 0.35mm solid #ece2d3; padding: 1mm 1.5mm; text-align: left; }
    .pro-prices th.h.num, .pro-prices td.num { text-align: right; }
    .pro-prices td { font-size: 8pt; color: #323232; border-bottom: 0.2mm solid #efe7da; padding: 1mm 1.5mm; }
    .pro-prices td.ht { font-weight: bold; color: #1a1a1a; }
    .pro-prices td.pvp { color: #6a6055; }
  </style>';
}

/**
 * Construit le PDF du tarif professionnel.
 *
 * @param array $args {
 *   @type array<int> ids      IDs produit retenus
 *   @type float      rate_pct taux de remise EN POURCENTAGE (30 = -30%)
 *   @type string     client   « Établi pour » ('' = bloc masqué)
 *   @type string     date     date d'export, Y-m-d
 *   @type string     valid    date de validité, Y-m-d
 *   @type string     mentions texte libre des mentions légales
 * }
 * @return string octets du PDF
 * @throws \RuntimeException si mPDF est indisponible
 */
function sapi_catalogue_pro_pdf_build($args) {
  $ids  = array_values(array_filter(array_map('intval', $args['ids'])));
  $rate = ((float) $args['rate_pct']) / 100.0;

  // Même source, même groupement, même ordre que le site : catégorie puis
  // menu_order. L'ordre de cochage dans l'admin n'a aucune influence.
  $catalogue = sapi_catalogue_get_products_priced(null, $ids);

  $mpdf = sapi_catalogue_pdf_new_mpdf();
  $mpdf->SetTitle('Tarif professionnel — Atelier Sâpi');
  $mpdf->SetAuthor('Atelier Sâpi');

  $mpdf->WriteHTML(sapi_catalogue_pdf_css());
  $mpdf->WriteHTML(sapi_catalogue_pro_pdf_css());

  $fmt = function ($ymd) {
    $ts = strtotime($ymd);
    if (!$ts) return '';
    return function_exists('date_i18n') ? date_i18n('j F Y', $ts) : date('Y-m-d', $ts);
  };

  // ── 1. Page de garde ──
  // ⚠️ Le TAUX n'apparaît nulle part dans le document : le revendeur voit ses
  // prix, pas la remise qu'on lui consent par rapport à un autre.
  $logo_path = get_template_directory() . '/assets/pdf-logo.png';
  $logo_html = file_exists($logo_path) ? '<img src="' . esc_attr($logo_path) . '" style="width:46mm;"><br><br>' : '';

  $cover  = '<div class="cover" style="margin-top:52mm;">';
  $cover .= $logo_html;
  $cover .= '<div class="brand">Atelier Sâpi</div>';
  $cover .= '<div class="subtitle-pro">Tarif professionnel</div>';
  if ($args['client'] !== '') {
    $cover .= '<div class="client"><span class="label">Établi pour</span><br>' . esc_html($args['client']) . '</div>';
  }
  $cover .= '<div class="dates">Édition du ' . esc_html($fmt($args['date'])) . '<br>';
  $cover .= 'Valable jusqu’au ' . esc_html($fmt($args['valid'])) . '</div>';
  $cover .= '</div>';

  $footer = '<div style="text-align:center; font-family:montserrat; font-size:7.5pt; color:#937D68;">Atelier Sâpi &nbsp;·&nbsp; Tarif professionnel — confidentiel &nbsp;·&nbsp; {PAGENO}</div>';
  $mpdf->SetHTMLFooter($footer);
  $mpdf->WriteHTML($cover);

  // ── 2. Pages d'introduction, identiques au catalogue public ──
  // Mêmes helpers, mêmes ACF, même gabarit : le revendeur reçoit la même
  // présentation de l'atelier que le prescripteur, seul le tarif s'ajoute.
  $intro = sapi_catalogue_pdf_intro_fields();
  $mpdf->WriteHTML(sapi_catalogue_pdf_histoire_html($intro));
  $mpdf->WriteHTML(sapi_catalogue_pdf_bois_html($intro));

  // ── 3. Mentions légales, mises en page comme les deux pages d'intro ──
  $mentions_html = sapi_catalogue_safe_html(wpautop((string) $args['mentions']));
  if ($mentions_html !== '') {
    $m  = '<pagebreak /><div class="mentions">';
    $m .= '<h2 class="section-title">Tarif professionnel</h2>';
    $m .= '<div class="body">' . $mentions_html . '</div>';
    $m .= '</div>';
    $mpdf->WriteHTML($m);
  }

  // ── 3. Une page par produit ──
  // Filigrane actif à partir d'ici seulement (pas sur la garde ni les mentions).
  $mpdf->SetWatermarkText('Tarif professionnel — confidentiel');
  $mpdf->watermark_font     = 'montserrat';
  $mpdf->watermarkTextAlpha = 0.06;
  $mpdf->showWatermarkText  = true;

  foreach ($catalogue as $block) {
    if (empty($block['products'])) continue; // catégorie sans produit sélectionné → absente
    foreach ($block['products'] as $p) {
      $ref = $p['sku'] !== '' ? 'Réf. ' . $p['sku'] . '  ·  ' : '';
      $mpdf->SetHTMLFooter(
        '<div style="font-family:montserrat; font-size:7.5pt; color:#937D68;"><table style="width:100%"><tr>'
        . '<td style="text-align:left;">' . esc_html($ref) . 'Tarif professionnel — confidentiel</td>'
        . '<td style="text-align:right;">{PAGENO}</td></tr></table></div>'
      );

      $gids   = array_values(array_filter(array_map('intval', $p['gallery_ids'])));
      $main   = !empty($gids) ? sapi_catalogue_pdf_image($gids[0], 'large') : null;
      $thumbs = array_slice($gids, 1, 3);

      $html  = '<pagebreak /><div class="prod-card">';
      $html .= '<div class="cat-tag">' . esc_html($block['label']) . '</div>';
      $html .= '<table class="prod-head"><tr>';
      $html .= '<td style="vertical-align:bottom;"><span class="prod-name">' . sapi_catalogue_pdf_name_html($p['title']) . '</span></td>';
      if ($p['sku'] !== '') {
        $html .= '<td style="text-align:right; vertical-align:bottom; width:32mm;"><span class="prod-sku">Réf. ' . esc_html($p['sku']) . '</span></td>';
      }
      $html .= '</tr></table><div class="head-rule"></div>';

      if ($main)   $html .= '<div class="prod-hero">' . sapi_catalogue_pdf_img_tag($main, 167, 100) . '</div>';
      if ($thumbs) {
        $html .= '<table class="thumb-row"><tr>';
        foreach ($thumbs as $tid) {
          $html .= '<td>' . sapi_catalogue_pdf_img_tag(sapi_catalogue_pdf_image($tid, 'medium'), 50, 33) . '</td>';
        }
        $html .= '</tr></table>';
      }

      // Tableau de prix pleine largeur, AVANT les caractéristiques
      $html .= sapi_catalogue_pro_price_table_html($p['pricing'], $rate);

      if ($p['description'] !== '') {
        $html .= '<div class="specs-rule"></div>';
        $html .= '<div class="prod-desc">' . wpautop($p['description']) . '</div>';
      }

      // Caractéristiques en 2 colonnes équilibrées (même logique que le PDF public)
      $sections = !empty($p['specs']) ? $p['specs'] : [];
      if ($sections) {
        $col_a = ''; $col_b = ''; $ha = 0; $hb = 0;
        foreach ($sections as $section) {
          $blk = '<table class="spec-block"><tr><td class="sec" colspan="2">' . esc_html($section['title']) . '</td></tr>';
          foreach ($section['items'] as $item) {
            $blk .= '<tr><th>' . esc_html($item['label']) . '</th><td>' . esc_html($item['value']) . '</td></tr>';
          }
          $blk .= '</table>';
          $cost = 1 + count($section['items']);
          if ($ha <= $hb) { $col_a .= $blk; $ha += $cost; }
          else            { $col_b .= $blk; $hb += $cost; }
        }
        $html .= '<div class="specs-rule"></div>';
        $html .= '<table class="specs-grid"><tr><td>' . $col_a . '</td><td>' . $col_b . '</td></tr></table>';
      }

      $html .= '</div>';
      $mpdf->WriteHTML($html);
    }
  }

  return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

/**
 * Tableau de prix d'un produit pour le PDF pro.
 * Colonnes : Variation / Prix pro HT / PVP conseillé TTC.
 *
 * Le HT est calculé ICI, au rendu, avec le taux du moment — jamais stocké,
 * jamais exposé ailleurs.
 *
 * @param array $pricing issu de sapi_catalogue_product_pricing()
 * @param float $rate    taux en FRACTION (0.30)
 * @return string HTML
 */
function sapi_catalogue_pro_price_table_html($pricing, $rate) {
  if (empty($pricing['rows'])) return '';

  $html  = '<div class="specs-rule"></div>';
  $html .= '<table class="pro-prices"><caption>Tarif</caption><tr>';
  $html .= '<th class="h">Variation</th>';
  $html .= '<th class="h num">Prix pro HT</th>';
  $html .= '<th class="h num">PVP conseillé TTC</th>';
  $html .= '</tr>';
  foreach ($pricing['rows'] as $row) {
    $ht = sapi_catalogue_ht_from_ttc($row['ttc'], $rate);
    $html .= '<tr>';
    $html .= '<td>' . esc_html($row['label']) . '</td>';
    $html .= '<td class="num ht">' . esc_html(sapi_catalogue_format_price($ht)) . '</td>';
    $html .= '<td class="num pvp">' . esc_html(sapi_catalogue_format_price($row['ttc'])) . '</td>';
    $html .= '</tr>';
  }
  $html .= '</table>';
  return $html;
}

/* =========================================================================
 * 4. Route d'export — admin_post, PAS de route REST publique
 * ========================================================================= */

add_action('admin_post_sapi_catalogue_pro_pdf', 'sapi_catalogue_pro_handle_export');

/** Redirige vers la page admin avec un code de message. */
function sapi_catalogue_pro_redirect($code) {
  wp_safe_redirect(add_query_arg(
    ['post_type' => 'product', 'page' => 'sapi-catalogue-pro', 'sapi_pro_msg' => $code],
    admin_url('edit.php')
  ));
  exit;
}

/**
 * Handler d'export : capacité + nonce, assainissement, persistance, journal,
 * puis génération et envoi du fichier.
 */
function sapi_catalogue_pro_handle_export() {
  if (!current_user_can(SAPI_CATALOGUE_PRO_CAP)) wp_die('Accès refusé.', 'Accès refusé', ['response' => 403]);
  check_admin_referer('sapi_catalogue_pro_pdf');

  // ⚠️ PIÈGE o2switch (déjà rencontré en Tâche 5) : des cases nommées
  // `produits[]` sont fusionnées par le WAF et une seule valeur survit. D'où un
  // nom UNIQUE par case (`produits[<id>]=1`) : la sélection = les clés cochées.
  $raw_ids = (isset($_POST['produits']) && is_array($_POST['produits'])) ? wp_unslash($_POST['produits']) : [];
  $ids = [];
  foreach ($raw_ids as $id => $on) {
    if (!empty($on)) $ids[] = (int) $id;
  }
  $ids = array_values(array_unique(array_filter($ids)));
  if (!$ids) sapi_catalogue_pro_redirect('empty');

  $rate_pct = isset($_POST['rate']) ? (float) str_replace(',', '.', wp_unslash($_POST['rate'])) : 30.0;
  if ($rate_pct < 0)  $rate_pct = 0.0;
  if ($rate_pct > 95) $rate_pct = 95.0;

  $today = current_time('Y-m-d');
  $date  = isset($_POST['date_export']) ? sanitize_text_field(wp_unslash($_POST['date_export'])) : $today;
  $valid = isset($_POST['date_valid'])  ? sanitize_text_field(wp_unslash($_POST['date_valid']))  : '';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))  $date  = $today;
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid)) $valid = sprintf('%d-12-31', (int) current_time('Y'));

  $args = [
    'ids'      => $ids,
    'rate_pct' => $rate_pct,
    'client'   => isset($_POST['client']) ? sanitize_text_field(wp_unslash($_POST['client'])) : '',
    'date'     => $date,
    'valid'    => $valid,
    'mentions' => isset($_POST['mentions']) ? wp_kses_post(wp_unslash($_POST['mentions'])) : '',
  ];

  // Persistance AVANT génération : si mPDF échoue, la saisie n'est pas perdue.
  sapi_catalogue_pro_save_settings($args);

  if (!sapi_catalogue_pdf_autoload()) sapi_catalogue_pro_redirect('nompdf');

  sapi_catalogue_pro_purge_old();

  try {
    $path = sapi_catalogue_pro_get_or_build($args);
  } catch (\Throwable $e) {
    sapi_catalogue_pro_redirect('error');
  }
  if (!$path || !file_exists($path)) sapi_catalogue_pro_redirect('error');

  sapi_catalogue_pro_log_export($args);

  $slug     = $args['client'] !== '' ? '-' . sanitize_title($args['client']) : '';
  $filename = 'tarif-pro-atelier-sapi' . $slug . '-' . $args['date'] . '.pdf';

  nocache_headers();
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . filesize($path));
  header('X-Robots-Tag: noindex, nofollow');
  readfile($path); // phpcs:ignore — flux binaire
  exit;
}
