<?php
/**
 * Catalogue B2B (prescripteurs) — Temps 2 : export PDF (mPDF).
 *
 * SÉ 1 — Infrastructure : autoload mPDF (généré en CI dans /vendor), tempDir
 * inscriptible sous uploads, et une route REST de self-test qui valide que mPDF
 * tourne réellement sur o2switch (mutualisé) et remonte les limites PHP.
 *
 * Réutilise les fondations du Temps 1 (inc/catalogue-data.php) : source de
 * données SANS prix, mapping caractéristiques, ACF Histoire/Bois. Ne PAS
 * redévelopper ces couches ici.
 */

if (!defined('ABSPATH')) exit;

// Version du générateur PDF : entre dans la clé de cache → à INCRÉMENTER à chaque
// évolution de la mise en page pour invalider automatiquement les PDF en cache.
// v15 (2026-08-24) : ajout du tarif professionnel (inc/catalogue-pdf-pro.php).
// v16 (2026-08-24) : gabarit resserré — marges réduites, catégorie en haut à
//                    droite (SKU retiré de l'en-tête), bande de 3 photos à
//                    hauteur fixe côté pro. Le rendu des DEUX PDF change.
// v17 (2026-08-24) : marge basse remontée de 12 à 14 mm — à 12, le pied de page
//                    se posait à 2,7 mm du bord, sous la zone non imprimable.
// La constante entre dans la clé de cache des DEUX générateurs, public et pro.
if (!defined('SAPI_CATALOGUE_PDF_VERSION')) define('SAPI_CATALOGUE_PDF_VERSION', '17');

/**
 * Charge l'autoloader Composer (mPDF) une seule fois.
 * @return bool true si la classe \Mpdf\Mpdf est disponible.
 */
function sapi_catalogue_pdf_autoload() {
  static $loaded = null;
  if ($loaded !== null) return $loaded;

  $autoload = get_template_directory() . '/vendor/autoload.php';
  if (file_exists($autoload)) {
    require_once $autoload;
    $loaded = class_exists('\\Mpdf\\Mpdf');
  } else {
    $loaded = false;
  }
  return $loaded;
}

/**
 * Dossier temporaire inscriptible pour mPDF (sous wp-content/uploads).
 * @return string chemin absolu
 */
function sapi_catalogue_pdf_tmpdir() {
  $up  = wp_upload_dir();
  $dir = trailingslashit($up['basedir']) . 'mpdf-tmp';
  if (!file_exists($dir)) {
    wp_mkdir_p($dir);
  }
  return $dir;
}

/**
 * Fabrique une instance mPDF configurée : A4, tempDir inscriptible, et les
 * polices de marque (Montserrat + Square Peg, TTF locales dans assets/pdf-fonts).
 * Source unique de config → self-test ET générateur passent par ici.
 *
 * @param array $config surcharges passées au constructeur mPDF
 * @return \Mpdf\Mpdf
 * @throws \RuntimeException si mPDF n'est pas disponible
 */
function sapi_catalogue_pdf_new_mpdf($config = []) {
  if (!sapi_catalogue_pdf_autoload()) {
    throw new \RuntimeException('mPDF indisponible (vendor/ non déployé).');
  }

  $configVars = (new \Mpdf\Config\ConfigVariables())->getDefaults();
  $fontVars   = (new \Mpdf\Config\FontVariables())->getDefaults();
  $brand_dir  = get_template_directory() . '/assets/pdf-fonts';

  $defaults = [
    'mode'              => 'utf-8',
    'format'            => 'A4',
    'tempDir'           => sapi_catalogue_pdf_tmpdir(),
    'fontDir'           => array_merge($configVars['fontDir'], [$brand_dir]),
    'fontdata'          => $fontVars['fontdata'] + [
      // Corps de texte
      'montserrat'      => ['R' => 'Montserrat-Regular.ttf', 'B' => 'Montserrat-Bold.ttf'],
      'montserratlight' => ['R' => 'Montserrat-Light.ttf'],
      'montserratblack' => ['R' => 'Montserrat-Black.ttf'],
      // Titres (surnom produit / titres de section) — signature de marque
      'squarepeg'       => ['R' => 'SquarePeg-Regular.ttf'],
    ],
    'default_font'      => 'montserrat',
    'default_font_size' => 10,
    // Marges resserrées (addendum 2026-08-24) : gagne ~8 mm en hauteur et porte
    // la largeur utile de 180 à 190 mm, pour que la fiche produit tienne sur une
    // page une fois le tableau de prix ajouté.
    'margin_left'       => 10,
    'margin_right'      => 10,
    'margin_top'        => 12,
    // ⚠️ Le pied de page vit DANS la marge basse. Mesuré sur le PDF généré :
    // la ligne de pied se pose à (margin_footer − 4,3 mm) du bord de feuille.
    // Un margin_footer à 7 la mettait à 2,7 mm — sous la zone non imprimable
    // de la plupart des imprimantes. On garde donc les 9 mm par défaut, qui
    // reposent le pied exactement où Robin l'a validé (4,7 mm), et on descend
    // la marge basse à 14 plutôt que 12 : le pied culmine vers 7,3 mm, il
    // reste 6,7 mm de dégagement avant le contenu. Deux millimètres de gain en
    // moins sur les huit visés, sur une fiche qui en récupère ~87.
    'margin_bottom'     => 14,
    'margin_footer'     => 9,
  ];

  return new \Mpdf\Mpdf(array_merge($defaults, $config));
}

/* =========================================================================
 * SÉ 3 — Générateur PDF. Réutilise sapi_catalogue_get_products() (Temps 1,
 * SANS prix) + le mapping caractéristiques + les ACF Histoire/Bois.
 * ========================================================================= */

/**
 * ID de la page SANS PRIX portant le template page-catalogue.php (pour lire les
 * ACF Histoire / Bois du PDF public).
 *
 * ⚠️ Depuis /catalogue-prix, DEUX pages partagent ce template. get_posts() sans
 * `orderby` retombe sur date DESC : la page la plus RÉCENTE gagnait, donc le PDF
 * public se serait mis à lire les ACF de la page prix. Deux gardes :
 *   1. exclusion des pages dont `catalogue_affiche_prix` vaut 1. La branche
 *      NOT EXISTS est indispensable : la page /catalogue a été créée AVANT
 *      l'existence du champ, elle n'a donc AUCUNE ligne de meta pour cette clé,
 *      et un `!=` seul ne matche pas une meta absente en SQL — la fonction
 *      renverrait 0 et le PDF public perdrait ses contenus éditoriaux.
 *   2. `ID ASC` : à égalité, la page historique (créée en premier) l'emporte.
 *
 * @return int 0 si introuvable
 */
function sapi_catalogue_page_id() {
  static $id = null;
  if ($id !== null) return $id;
  $pages = get_posts([
    'post_type'   => 'page',
    'post_status' => 'publish',
    'numberposts' => 1,
    'fields'      => 'ids',
    'meta_query'  => [
      [
        'key'   => '_wp_page_template',
        'value' => 'page-catalogue.php',
      ],
      [
        'relation' => 'OR',
        ['key' => 'catalogue_affiche_prix', 'compare' => 'NOT EXISTS'],
        ['key' => 'catalogue_affiche_prix', 'value' => '1', 'compare' => '!='],
      ],
    ],
    'orderby'     => 'ID',
    'order'       => 'ASC',
  ]);
  $id = $pages ? (int) $pages[0] : 0;
  return $id;
}

/**
 * Prépare une image pour le PDF : jamais l'original — la taille WP demandée,
 * recompressée en JPEG ~80, mise en cache dans tmpdir/img (clé = id+taille+mtime).
 *
 * @param int         $attachment_id
 * @param string      $size 'large' (1024) ou 'medium'
 * @param array|null  $crop ['w' => px, 'h' => px] → RECADRAGE au ratio exact
 *                          (et non simple redimensionnement). Indispensable à la
 *                          bande de photos à hauteur fixe : sans lui, une image
 *                          portrait et une paysage sortent de largeurs
 *                          différentes et la bande part en dents de scie.
 *                          null = comportement historique, inchangé.
 * @return array{path:string,w:int,h:int}|null
 */
function sapi_catalogue_pdf_image($attachment_id, $size = 'large', $crop = null) {
  $attachment_id = (int) $attachment_id;
  if (!$attachment_id) return null;

  $src = wp_get_attachment_image_src($attachment_id, $size);
  if (!$src || empty($src[0])) return null;

  $up   = wp_upload_dir();
  $path = str_replace($up['baseurl'], $up['basedir'], $src[0]);
  if (!file_exists($path)) {
    $path = get_attached_file($attachment_id);
    if (!$path || !file_exists($path)) return null;
  }

  $w = (int) $src[1];
  $h = (int) $src[2];

  $mtime     = @filemtime($path) ?: 0;
  $cache_dir = trailingslashit(sapi_catalogue_pdf_tmpdir()) . 'img';
  if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);
  // Le recadrage entre dans le nom du cache : une même image sert au format
  // « contain » du PDF public ET à la bande recadrée du PDF pro.
  $suffix = $crop ? "-c{$crop['w']}x{$crop['h']}" : '';
  $out = trailingslashit($cache_dir) . "pdfimg-{$attachment_id}-{$size}{$suffix}-{$mtime}.jpg";

  if (file_exists($out)) {
    if ($crop) return ['path' => $out, 'w' => (int) $crop['w'], 'h' => (int) $crop['h']];
    if (!$w || !$h) { $sz = @getimagesize($out); if ($sz) { $w = $sz[0]; $h = $sz[1]; } }
    return ['path' => $out, 'w' => $w, 'h' => $h];
  }

  $editor = wp_get_image_editor($path);
  if (is_wp_error($editor)) {
    return ['path' => $path, 'w' => $w, 'h' => $h]; // repli : chemin brut
  }
  if ($crop) {
    // 3e argument à true = recadrage centré aux dimensions exactes.
    $editor->resize((int) $crop['w'], (int) $crop['h'], true);
    $w = (int) $crop['w'];
    $h = (int) $crop['h'];
  }
  $editor->set_quality(80);
  $saved = $editor->save($out, 'image/jpeg');
  if (is_wp_error($saved)) {
    return ['path' => $path, 'w' => $w, 'h' => $h];
  }
  $final = isset($saved['path']) ? $saved['path'] : $out;
  if (!$w || !$h) { $sz = @getimagesize($final); if ($sz) { $w = $sz[0]; $h = $sz[1]; } }
  return ['path' => $final, 'w' => $w, 'h' => $h];
}

/**
 * Balise <img> ajustée (contain) dans une boîte en mm, aspect préservé.
 * @param array|null $img       retour de sapi_catalogue_pdf_image()
 * @param float      $radius_mm rayon des coins arrondis (0 = angles droits)
 */
function sapi_catalogue_pdf_img_tag($img, $box_w_mm, $box_h_mm, $radius_mm = 0) {
  if (!$img || empty($img['path'])) return '';
  $w = max(1, (int) $img['w']);
  $h = max(1, (int) $img['h']);
  $aspect = $w / $h;
  if (($box_w_mm / $box_h_mm) > $aspect) {
    $disp_h = $box_h_mm; $disp_w = $box_h_mm * $aspect;
  } else {
    $disp_w = $box_w_mm; $disp_h = $box_w_mm / $aspect;
  }
  $style = sprintf('width:%.1fmm;height:%.1fmm;', $disp_w, $disp_h);
  if ($radius_mm > 0) $style .= sprintf('border-radius:%.1fmm;', $radius_mm);
  return '<img src="' . esc_attr($img['path']) . '" style="' . $style . '">';
}

/**
 * Feuille de style PDF (sous-ensemble CSS supporté par mPDF : pas de flex/grid).
 * @return string bloc <style>
 */
function sapi_catalogue_pdf_css() {
  return '<style>
    body { font-family: montserrat; font-size: 10pt; color: #323232; }
    h1, h2, h3 { margin: 0; }
    .center { text-align: center; }

    /* Découpage nom : prénom (Montserrat gras) + surnom (Square Peg), en NOIR */
    .pf { font-family: montserrat; font-weight: bold; text-transform: uppercase; color: #1a1a1a; }
    .pr { font-family: squarepeg; color: #1a1a1a; }

    .cover { text-align: center; }
    .cover .brand { font-family: squarepeg; font-size: 54pt; color: #937D68; }
    .cover .subtitle { font-family: montserrat; font-size: 22pt; letter-spacing: 2px; text-transform: uppercase; color: #323232; margin-top: 6mm; }
    .cover .cats { font-size: 11pt; color: #6a6055; margin-top: 10mm; }
    .cover .date { font-size: 9pt; color: #937D68; margin-top: 4mm; }

    .section-title { font-family: squarepeg; font-size: 30pt; color: #937D68; margin-bottom: 4mm; }
    .lead { font-size: 10.5pt; color: #4a443d; }
    .bois-name { font-family: squarepeg; font-size: 22pt; color: #937D68; }

    /* Carte produit (esthétique du site) */
    .prod-card { border: 0.3mm solid #ece2d3; border-radius: 4mm; background: #fffdfb; padding: 3mm 4mm; }
    /* Catégorie : désormais en HAUT À DROITE, sur la ligne du nom (plus de div
       au-dessus, qui coûtait ~4 mm). Le SKU quitte le haut de la carte : il
       reste lisible en pied de page, aucune information perdue.
       ⚠️ Cette feuille est une chaîne PHP en quotes simples : PAS d apostrophe
       dans ce bloc, elle fermerait la chaîne et casserait le fichier. */
    .cat-tag { font-family: montserrat; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 1.5px; color: #E35B24; }
    /* Bande de 3 photos à hauteur fixe (PDF pro) — images recadrées au même
       ratio en amont, sans quoi la bande partirait en dents de scie. */
    .photo-band { width: 100%; margin: 0 0 1mm; }
    .photo-band td { width: 33.33%; text-align: center; vertical-align: top; padding: 0; }
    .prod-head { width: 100%; }
    .prod-sku { font-family: montserrat; font-size: 8.5pt; color: #937D68; text-transform: uppercase; letter-spacing: 1px; }
    /* Filets de séparation discrets */
    .head-rule { border-top: 0.25mm solid #ece2d3; margin: 0.5mm 0 2mm; }
    .specs-rule { border-top: 0.25mm solid #ece2d3; margin: 2.5mm 0 2mm; }
    .prod-hero { text-align: left; margin: 0 0 2mm; }
    .prod-desc { font-size: 8.5pt; color: #4a443d; text-align: justify; line-height: 1.34; }
    .prod-desc p { margin: 0 0 1.5mm; }
    .thumb-row { margin: 0; }
    .thumb-row td { padding: 0 3.6mm 4mm 0; text-align: left; vertical-align: top; }

    /* Tableau caractéristiques en 2 colonnes de sections (compact = 1 page) */
    .specs-grid { width: 100%; }
    .specs-grid > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 3mm; }
    .spec-block { width: 100%; border-collapse: collapse; margin-bottom: 1.5mm; }
    .spec-block .sec { font-family: montserrat; font-weight: bold; font-size: 8pt; text-transform: uppercase; letter-spacing: .5px; color: #E35B24; padding: 0.4mm 0; }
    .spec-block th { text-align: left; width: 46%; font-weight: bold; color: #6a6055; padding: 0.45mm 1.5mm; border-bottom: 0.2mm solid #efe7da; font-size: 7pt; }
    .spec-block td { text-align: left; color: #323232; padding: 0.45mm 1.5mm; border-bottom: 0.2mm solid #efe7da; font-size: 7pt; }

    .contact { text-align: center; }
    .contact .h { font-family: squarepeg; font-size: 30pt; color: #937D68; margin-bottom: 6mm; }
    .contact .line { font-size: 11pt; color: #323232; margin: 2mm 0; }
    .muted { color: #6a6055; font-size: 8pt; }
  </style>';
}

/**
 * Découpe un nom produit en prénom (Montserrat gras) + surnom (Square Peg),
 * en reproduisant la logique de assets/product-name-formatter.js. Rendu NOIR.
 * @param string $title
 * @return string HTML
 */
function sapi_catalogue_pdf_name_html($title) {
  $title = trim((string) $title);
  if ($title === '') return '';
  // ⚠️ Taille EN STYLE INLINE : mPDF n'applique pas les sélecteurs descendants
  // (ex. « .prod-name .pr { font-size } »), donc la taille doit être portée
  // directement par le span, sinon le surnom retombe à la taille par défaut.
  $pf = 'font-size:13pt;';
  $pr = 'font-size:34pt;';
  $words = preg_split('/\s+/', $title);
  if (count($words) < 2) {
    return '<span class="pf" style="' . $pf . '">' . esc_html($title) . '</span>';
  }
  // Nom commençant par un article (La, Le, Les, L\') → tout en surnom
  if (preg_match('/^(la|le|les)$/i', $words[0]) || preg_match("/^l['\x{2019}]/iu", $words[0])) {
    return '<span class="pr" style="' . $pr . '">' . esc_html($title) . '</span>';
  }
  $first = array_shift($words);
  $rest  = implode(' ', $words);
  return '<span class="pf" style="' . $pf . '">' . esc_html($first) . '</span> <span class="pr" style="' . $pr . '">' . esc_html($rest) . '</span>';
}

/* =========================================================================
 * Pages d'introduction (Histoire de l'atelier / Deux bois au choix).
 * Extraites en helpers pour être partagées par les DEUX générateurs — public
 * (ci-dessous) et tarif professionnel (inc/catalogue-pdf-pro.php) — sans
 * recopier le gabarit. Le rendu du PDF public est inchangé au caractère près.
 * ========================================================================= */

/**
 * Contenus éditoriaux ACF lus sur la page catalogue (celle SANS prix, cf.
 * sapi_catalogue_page_id()). Les deux PDF présentent donc le même atelier.
 *
 * @return array<string,mixed>
 */
function sapi_catalogue_pdf_intro_fields() {
  $page_id = sapi_catalogue_page_id();
  $g = function ($key, $default = '') use ($page_id) {
    if (!$page_id || !function_exists('get_field')) return $default;
    $v = get_field($key, $page_id);
    return ($v === null || $v === '') ? $default : $v;
  };
  return [
    'histoire_titre' => (string) $g('catalogue_histoire_titre', 'Histoire de l’atelier'),
    'histoire_texte' => (string) $g('catalogue_histoire_texte', ''),
    'histoire_img'   => (int)    $g('catalogue_histoire_image', 0),
    'bois_titre'     => (string) $g('catalogue_bois_titre', 'Deux bois au choix'),
    'bois_intro'     => (string) $g('catalogue_bois_intro', ''),
    'peuplier_texte' => (string) $g('catalogue_bois_peuplier_texte', 'Finition claire.'),
    'peuplier_img'   => (int)    $g('catalogue_bois_peuplier_image', 0),
    'okoume_texte'   => (string) $g('catalogue_bois_okoume_texte', 'Teinte plus rosée et plus sombre.'),
    'okoume_img'     => (int)    $g('catalogue_bois_okoume_image', 0),
  ];
}

/**
 * Page « Histoire de l'atelier ». Inclut son propre saut de page.
 * @param array $f retour de sapi_catalogue_pdf_intro_fields()
 * @return string HTML
 */
function sapi_catalogue_pdf_histoire_html($f) {
  $h  = '<pagebreak />';
  $h .= '<h2 class="section-title">' . esc_html($f['histoire_titre']) . '</h2>';
  if ($f['histoire_img']) {
    $img = sapi_catalogue_pdf_image($f['histoire_img'], 'large');
    $h  .= '<div class="center" style="margin:4mm 0;">' . sapi_catalogue_pdf_img_tag($img, 170, 95) . '</div>';
  }
  if ($f['histoire_texte'] !== '') {
    $h .= '<div class="lead">' . wpautop(sapi_catalogue_safe_html($f['histoire_texte'])) . '</div>';
  }
  return $h;
}

/**
 * Page « Deux bois au choix ». Inclut son propre saut de page.
 * @param array $f retour de sapi_catalogue_pdf_intro_fields()
 * @return string HTML
 */
function sapi_catalogue_pdf_bois_html($f) {
  $peuplier_tag = $f['peuplier_img'] ? sapi_catalogue_pdf_img_tag(sapi_catalogue_pdf_image($f['peuplier_img'], 'large'), 80, 60) : '';
  $okoume_tag   = $f['okoume_img']   ? sapi_catalogue_pdf_img_tag(sapi_catalogue_pdf_image($f['okoume_img'], 'large'), 80, 60)   : '';

  $b  = '<pagebreak />';
  $b .= '<h2 class="section-title">' . esc_html($f['bois_titre']) . '</h2>';
  if ($f['bois_intro'] !== '') $b .= '<p class="lead">' . esc_html($f['bois_intro']) . '</p>';
  $b .= '<table style="width:100%; margin-top:4mm;"><tr>';
  $b .= '<td style="width:50%; text-align:center; vertical-align:top; padding:0 4mm;">' . $peuplier_tag . '<div class="bois-name">Peuplier</div><p class="lead">' . esc_html($f['peuplier_texte']) . '</p></td>';
  $b .= '<td style="width:50%; text-align:center; vertical-align:top; padding:0 4mm;">' . $okoume_tag . '<div class="bois-name">Okoumé</div><p class="lead">' . esc_html($f['okoume_texte']) . '</p></td>';
  $b .= '</tr></table>';
  return $b;
}

/**
 * Carte produit d'une page de PDF — gabarit PARTAGÉ par les deux générateurs.
 *
 * En-tête sur une seule ligne : nom à gauche, catégorie en haut à droite. Le SKU
 * n'y figure plus — il est déjà en pied de page de chaque fiche, l'y répéter
 * coûtait une cellule et une ligne (addendum 2026-08-24).
 *
 * @param array  $p         produit normalisé (sapi_catalogue_normalize_product)
 * @param string $cat_label libellé de catégorie affiché en haut à droite
 * @param array  $args {
 *   @type string $photos       'hero' = grande photo + vignettes (PDF public),
 *                              'band' = bande de 3 photos à hauteur fixe (pro)
 *   @type string $after_photos HTML inséré juste après le bloc photo
 *                              (tableau de prix du tarif pro)
 * }
 * @return string HTML, saut de page inclus
 */
function sapi_catalogue_pdf_product_card_html($p, $cat_label, $args = []) {
  $args = array_merge(['photos' => 'hero', 'after_photos' => ''], $args);

  // ── Caractéristiques en 2 colonnes, équilibrées par nombre de lignes ──
  $sections = !empty($p['specs']) ? $p['specs'] : [];
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

  $html  = '<pagebreak />';
  $html .= '<div class="prod-card">';

  // En-tête : nom à gauche, catégorie à droite, sur la même ligne
  $html .= '<table class="prod-head"><tr>';
  $html .= '<td style="vertical-align:bottom;"><span class="prod-name">' . sapi_catalogue_pdf_name_html($p['title']) . '</span></td>';
  $html .= '<td style="text-align:right; vertical-align:bottom; width:42mm;"><span class="cat-tag">' . esc_html($cat_label) . '</span></td>';
  $html .= '</tr></table>';
  $html .= '<div class="head-rule"></div>';

  // ── Bloc photo ──
  if ($args['photos'] === 'band') {
    $html .= sapi_catalogue_pdf_photo_band_html((int) $p['id']);
  } else {
    $gids   = array_values(array_filter(array_map('intval', $p['gallery_ids'])));
    $main   = !empty($gids) ? sapi_catalogue_pdf_image($gids[0], 'large') : null;
    $thumbs = array_slice($gids, 1, 3);
    if ($main) {
      $html .= '<div class="prod-hero">' . sapi_catalogue_pdf_img_tag($main, 167, 100) . '</div>';
    }
    if (!empty($thumbs)) {
      $html .= '<table class="thumb-row"><tr>';
      foreach ($thumbs as $tid) {
        $html .= '<td>' . sapi_catalogue_pdf_img_tag(sapi_catalogue_pdf_image($tid, 'medium'), 50, 33) . '</td>';
      }
      $html .= '</tr></table>';
    }
  }

  // Tableau de prix (tarif pro uniquement — chaîne vide côté public)
  $html .= $args['after_photos'];

  // Description (essences + tailles vivent dans le tableau caractéristiques)
  if ($p['description'] !== '') {
    $html .= '<div class="prod-desc">' . wpautop($p['description']) . '</div>';
  }

  if ($sections) {
    $html .= '<div class="specs-rule"></div>';
    $html .= '<table class="specs-grid"><tr>';
    $html .= '<td>' . $col_a . '</td><td>' . $col_b . '</td>';
    $html .= '</tr></table>';
  }
  $html .= '</div>';

  return $html;
}

/* ── Bande de 3 photos à hauteur fixe (PDF pro) ─────────────────────────── */

/** Largeur d'un emplacement de la bande, en mm. */
if (!defined('SAPI_CATALOGUE_BAND_W')) define('SAPI_CATALOGUE_BAND_W', 57);
/** Hauteur de la bande, en mm (budget vertical de l'addendum). */
if (!defined('SAPI_CATALOGUE_BAND_H')) define('SAPI_CATALOGUE_BAND_H', 65);

/**
 * Les 3 images de la bande, dans l'ordre imposé :
 *   1. l'image mise en avant WooCommerce (la photo PRODUIT)
 *   2. la 1ʳᵉ photo d'ambiance
 *   3. la 2ᵉ photo d'ambiance
 *
 * ⚠️ La photo produit n'est PAS `gallery_ids[0]` : la galerie du catalogue vient
 * de sapi_get_product_photo_ids_with_fallback() et commence par une ambiance. On
 * va donc chercher la featured image explicitement, et on la dédoublonne de la
 * suite si elle figure aussi dans les galeries.
 *
 * Repli : moins de deux ambiances → complété par des photos de détail. Si le
 * compte n'y est toujours pas, l'emplacement reste VIDE — on ne répète jamais
 * une image pour boucher un trou.
 *
 * @param int $product_id
 * @return array<int,int> 0 à 3 IDs d'attachment, sans doublon
 */
function sapi_catalogue_pdf_band_ids($product_id) {
  $ids = [];

  $featured = (int) get_post_thumbnail_id($product_id);
  if ($featured) $ids[] = $featured;

  $push = function ($candidates) use (&$ids) {
    foreach ((array) $candidates as $id) {
      if (count($ids) >= 3) return;
      $id = (int) $id;
      if ($id && !in_array($id, $ids, true)) $ids[] = $id;
    }
  };

  if (function_exists('sapi_get_product_photo_ids')) {
    $push(sapi_get_product_photo_ids($product_id, 'ambiance'));
    if (count($ids) < 3) $push(sapi_get_product_photo_ids($product_id, 'detail'));
  }

  return $ids;
}

/**
 * Bande horizontale de 3 photos de taille identique, pleine largeur de carte.
 * Les images sont RECADRÉES au ratio de l'emplacement en amont : à hauteur fixe,
 * un simple « contain » donnerait des largeurs inégales et une bande en dents de
 * scie. Un emplacement sans image reste vide, la grille ne bouge pas.
 *
 * @param int $product_id
 * @return string HTML
 */
function sapi_catalogue_pdf_photo_band_html($product_id) {
  $ids = sapi_catalogue_pdf_band_ids($product_id);
  if (!$ids) return '';

  // Ratio de l'emplacement, converti en pixels de recadrage (~250 dpi).
  $crop = ['w' => SAPI_CATALOGUE_BAND_W * 10, 'h' => SAPI_CATALOGUE_BAND_H * 10];
  $style = sprintf('width:%dmm;height:%dmm;border-radius:2mm;', SAPI_CATALOGUE_BAND_W, SAPI_CATALOGUE_BAND_H);

  $html = '<table class="photo-band"><tr>';
  for ($i = 0; $i < 3; $i++) {
    $html .= '<td>';
    if (isset($ids[$i])) {
      $img = sapi_catalogue_pdf_image($ids[$i], 'large', $crop);
      if ($img && !empty($img['path'])) {
        $html .= '<img src="' . esc_attr($img['path']) . '" style="' . $style . '">';
      }
    }
    $html .= '</td>';
  }
  $html .= '</tr></table>';
  return $html;
}

/**
 * Construit le PDF combiné pour les catégories données (SANS prix, SANS lien).
 *
 * @param array<string>|null $cats slugs canoniques (null = toutes)
 * @return string octets du PDF
 * @throws \RuntimeException si mPDF indisponible
 */
function sapi_catalogue_pdf_build($cats = null) {
  $all_cats = sapi_catalogue_categories();
  $slugs    = $cats ? array_values(array_intersect(array_keys($all_cats), $cats)) : array_keys($all_cats);
  if (!$slugs) $slugs = array_keys($all_cats);

  $catalogue = sapi_catalogue_get_products($slugs);

  // ── Contenus ACF (Histoire / Bois) lus sur la page catalogue ──
  $intro = sapi_catalogue_pdf_intro_fields();

  $mpdf = sapi_catalogue_pdf_new_mpdf();
  $mpdf->SetTitle('Catalogue — Atelier Sâpi');
  $mpdf->SetAuthor('Atelier Sâpi');

  $mention = 'Document non contractuel remis à titre de présentation.';
  $footer_global = '<div style="text-align:center; font-family:montserrat; font-size:7.5pt; color:#937D68;">Atelier Sâpi &nbsp;·&nbsp; ' . esc_html($mention) . ' &nbsp;·&nbsp; {PAGENO}</div>';
  $mpdf->SetHTMLFooter($footer_global);

  // CSS (persiste pour tout le document)
  $mpdf->WriteHTML(sapi_catalogue_pdf_css());

  // ── 1. Page de garde ──
  $selected_labels = [];
  foreach ($slugs as $s) $selected_labels[] = $all_cats[$s];
  $logo_path = get_template_directory() . '/assets/pdf-logo.png';
  $logo_html = file_exists($logo_path)
    ? '<img src="' . esc_attr($logo_path) . '" style="width:46mm;"><br><br>'
    : '';
  $date_str = function_exists('date_i18n') ? date_i18n('j F Y') : date('Y-m-d');

  $cover  = '<div class="cover" style="margin-top:60mm;">';
  $cover .= $logo_html;
  $cover .= '<div class="brand">Atelier Sâpi</div>';
  $cover .= '<div class="subtitle">Catalogue</div>';
  $cover .= '<div class="cats">' . esc_html(implode(' · ', $selected_labels)) . '</div>';
  $cover .= '<div class="date">Édition du ' . esc_html($date_str) . '</div>';
  $cover .= '</div>';
  $mpdf->WriteHTML($cover);

  // ── 2. Histoire de l'atelier ──
  $mpdf->WriteHTML(sapi_catalogue_pdf_histoire_html($intro));

  // ── 3. Deux bois au choix ──
  $mpdf->WriteHTML(sapi_catalogue_pdf_bois_html($intro));

  // ── 4. Sections catégories → une carte produit par page ──
  foreach ($slugs as $slug) {
    $block = isset($catalogue[$slug]) ? $catalogue[$slug] : null;
    if (!$block || empty($block['products'])) continue;
    foreach ($block['products'] as $p) {
      // Pied de page : référence produit + mention + n° de page
      $ref = $p['sku'] !== '' ? 'Réf. ' . $p['sku'] . '  ·  ' : '';
      $mpdf->SetHTMLFooter('<div style="font-family:montserrat; font-size:7.5pt; color:#937D68;"><table style="width:100%"><tr><td style="text-align:left;">' . esc_html($ref) . esc_html($mention) . '</td><td style="text-align:right;">{PAGENO}</td></tr></table></div>');

      $mpdf->WriteHTML(sapi_catalogue_pdf_product_card_html($p, $block['label']));
    }
  }

  // ── 5. Page contact (texte NON cliquable) ──
  $mpdf->SetHTMLFooter($footer_global);
  $c  = '<pagebreak />';
  $c .= '<div class="contact" style="margin-top:70mm;">';
  $c .= '<div class="h">Nous contacter</div>';
  $c .= '<div class="line">Atelier Sâpi — Luminaires artisanaux en bois</div>';
  $c .= '<div class="line">contact@atelier-sapi.fr</div>';
  $c .= '<div class="line">atelier-sapi.fr</div>';
  $c .= '<br><div class="muted">' . esc_html($mention) . '</div>';
  $c .= '</div>';
  $mpdf->WriteHTML($c);

  return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
}

/* =========================================================================
 * SÉ 4 — Cache fichier + invalidation. Dossier uploads/catalogues protégé.
 * ========================================================================= */

/**
 * Dossier de cache des PDF (uploads/catalogues), protégé de l'accès direct
 * et de l'indexation. Les fichiers sont servis par l'endpoint REST, pas en direct.
 * @return string
 */
function sapi_catalogue_pdf_dir() {
  $up  = wp_upload_dir();
  $dir = trailingslashit($up['basedir']) . 'catalogues';
  if (!file_exists($dir)) wp_mkdir_p($dir);

  $ht = trailingslashit($dir) . '.htaccess';
  if (!file_exists($ht)) {
    @file_put_contents($ht,
      "Options -Indexes\n" .
      "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n" .
      "<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n"
    );
  }
  $idx = trailingslashit($dir) . 'index.html';
  if (!file_exists($idx)) @file_put_contents($idx, '');

  return $dir;
}

/** Timestamp global d'invalidation (bumpé à chaque modif produit/ACF). */
function sapi_catalogue_pdf_stamp() {
  $s = get_option('sapi_catalogue_pdf_stamp');
  if (!$s) {
    $s = time();
    update_option('sapi_catalogue_pdf_stamp', $s, false);
  }
  return (int) $s;
}

/** Normalise + trie les slugs de catégorie (clé de cache stable). */
function sapi_catalogue_pdf_normalize_slugs($cats) {
  $all   = array_keys(sapi_catalogue_categories());
  $slugs = $cats ? array_values(array_intersect($all, $cats)) : $all;
  if (!$slugs) $slugs = $all;
  sort($slugs);
  return $slugs;
}

/**
 * Chemin de cache pour une sélection.
 * Clé = wp_hash(cats + stamp) → NON devinable (salée par les clés secrètes WP).
 * La sécurité ne dépend donc pas d'AllowOverride/.htaccess : sans connaître le
 * nom exact, un fichier ne peut pas être atteint en accès direct.
 */
function sapi_catalogue_pdf_cache_path($slugs) {
  $key = wp_hash(implode(',', $slugs) . '|' . sapi_catalogue_pdf_stamp() . '|v' . SAPI_CATALOGUE_PDF_VERSION);
  return trailingslashit(sapi_catalogue_pdf_dir()) . 'catalogue-' . $key . '.pdf';
}

/** Sert le cache si présent, sinon (re)génère et écrit le fichier. */
function sapi_catalogue_pdf_get_or_build($cats, $allow_build = true) {
  $slugs = sapi_catalogue_pdf_normalize_slugs($cats);
  $path  = sapi_catalogue_pdf_cache_path($slugs);
  if (file_exists($path) && filesize($path) > 0) return $path;
  if (!$allow_build) return null;

  $pdf = sapi_catalogue_pdf_build($slugs);
  if ($pdf === '' || $pdf === null) return null;
  file_put_contents($path, $pdf);
  return $path;
}

/** Supprime tous les PDF en cache (les clés changent au bump, mais on nettoie). */
function sapi_catalogue_pdf_clear_cache() {
  $dir = sapi_catalogue_pdf_dir();
  foreach ((glob(trailingslashit($dir) . '*.pdf') ?: []) as $f) @unlink($f);
}

/**
 * Invalide le cache : bumpe le stamp, purge, et planifie une pré-génération
 * DIFFÉRÉE (hors de la requête de sauvegarde) des combinaisons courantes.
 */
function sapi_catalogue_pdf_bump_stamp() {
  update_option('sapi_catalogue_pdf_stamp', time(), false);
  sapi_catalogue_pdf_clear_cache();
  if (!wp_next_scheduled('sapi_catalogue_pregen_event')) {
    wp_schedule_single_event(time() + 60, 'sapi_catalogue_pregen_event');
  }
}

// ── Hooks d'invalidation ──
add_action('save_post_product', function ($post_id) {
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
  sapi_catalogue_pdf_bump_stamp();
});
add_action('deleted_post', function ($post_id) {
  if (get_post_type($post_id) === 'product') sapi_catalogue_pdf_bump_stamp();
});
add_action('add_attachment',    'sapi_catalogue_pdf_bump_stamp');
add_action('edit_attachment',   'sapi_catalogue_pdf_bump_stamp');
add_action('delete_attachment', 'sapi_catalogue_pdf_bump_stamp');

// ACF / meta : sur un produit OU sur la page catalogue (Histoire/Bois).
function sapi_catalogue_pdf_meta_hook($meta_id, $post_id, $meta_key = '', $meta_value = '') {
  $pt = get_post_type($post_id);
  if ($pt === 'product' || (int) $post_id === sapi_catalogue_page_id()) {
    sapi_catalogue_pdf_bump_stamp();
  }
}
add_action('updated_post_meta', 'sapi_catalogue_pdf_meta_hook', 10, 4);
add_action('added_post_meta',   'sapi_catalogue_pdf_meta_hook', 10, 4);
add_action('deleted_post_meta', 'sapi_catalogue_pdf_meta_hook', 10, 4);

/* =========================================================================
 * SÉ 6 — Pré-génération des combinaisons courantes (toutes + 4 mono-catégorie).
 * ========================================================================= */
add_action('sapi_catalogue_pregen_event', 'sapi_catalogue_pdf_pregenerate');
function sapi_catalogue_pdf_pregenerate() {
  if (!sapi_catalogue_pdf_autoload()) return;
  $all    = array_keys(sapi_catalogue_categories());
  $combos = [$all];
  foreach ($all as $c) $combos[] = [$c];
  foreach ($combos as $combo) {
    try { sapi_catalogue_pdf_get_or_build($combo, true); }
    catch (\Throwable $e) { /* on continue les autres combos */ }
  }
}

/* =========================================================================
 * SÉ 5 — Endpoint REST public de téléchargement.
 * GET /wp-json/sapi/v1/catalogue-pdf?cats=suspensions,appliques
 * ========================================================================= */
function sapi_catalogue_pdf_endpoint($request) {
  if (!sapi_catalogue_pdf_autoload()) {
    return new WP_Error('mpdf_absent', 'Export PDF momentanément indisponible.', ['status' => 503]);
  }
  $raw  = (string) $request->get_param('cats');
  $cats = $raw !== '' ? array_filter(array_map('sanitize_key', explode(',', $raw))) : null;
  $slugs = sapi_catalogue_pdf_normalize_slugs($cats);

  try {
    $path = sapi_catalogue_pdf_get_or_build($slugs, true);
  } catch (\Throwable $e) {
    return new WP_Error('pdf_error', 'Échec de génération du PDF.', ['status' => 500]);
  }
  if (!$path || !file_exists($path)) {
    return new WP_Error('pdf_error', 'Échec de génération du PDF.', ['status' => 500]);
  }

  $all      = sapi_catalogue_categories();
  $suffix   = (count($slugs) < count($all)) ? '-' . implode('-', $slugs) : '';
  $filename = 'catalogue-atelier-sapi' . $suffix . '.pdf';

  nocache_headers();
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . filesize($path));
  header('X-Robots-Tag: noindex, nofollow');
  readfile($path); // phpcs:ignore — flux binaire
  exit;
}

/* =========================================================================
 * Route REST publique de téléchargement.
 * GET /wp-json/sapi/v1/catalogue-pdf?cats=suspensions,appliques
 * ========================================================================= */
add_action('rest_api_init', function () {
  register_rest_route('sapi/v1', '/catalogue-pdf', [
    'methods'             => 'GET',
    'permission_callback' => '__return_true',
    'callback'            => 'sapi_catalogue_pdf_endpoint',
  ]);
});
