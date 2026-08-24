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
// v18 (2026-08-24) : PDF pro — description AVANT le tableau de prix, et titre
//                    « Tarif » aligné sur les titres de section de la fiche.
// v19 (2026-08-24) : le titre « Tarif » passe de <caption> (que mPDF ne style
//                    PAS) à une cellule .sec — le v18 sortait centré en noir.
// v20 (2026-08-24) : bande photo refaite (schéma Robin) — 3 grands carrés
//                    + colonne détail/accessoire, recadrage carré par
//                    construction. Corrige l'étirement jusqu'à +35 %.
// v21 (2026-08-24) : le recadrage carré suit le point focal pose dans la
//                    mediatheque (extension Media Focus Point).
// v22 (2026-08-24) : bande photo « 3 grands + rangee de 5 » (addendum 2) —
//                    58,33 / 33,80 mm, types en preference avec repli, plus
//                    aucune case vide, filet sur la photo produit.
// v23 (2026-08-24) : le PDF PUBLIC passe lui aussi en bande, profil propre —
//                    2 grandes de 87,87 mm + 4 moyennes de 42,20 mm, bande de
//                    133,53 mm. Meme hauteur qu'avant pour 6 images au lieu de
//                    4, ambiances doublees en taille. Ancien gabarit retire.
// La constante entre dans la clé de cache des DEUX générateurs, public et pro.
if (!defined('SAPI_CATALOGUE_PDF_VERSION')) define('SAPI_CATALOGUE_PDF_VERSION', '23');

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
 * ⚠️ Ne recadre PAS : elle préserve le ratio d'origine. Pour un carré exact,
 * voir sapi_catalogue_pdf_square_image().
 *
 * @param int    $attachment_id
 * @param string $size 'large' (1024) ou 'medium'
 * @return array{path:string,w:int,h:int}|null
 */
function sapi_catalogue_pdf_image($attachment_id, $size = 'large') {
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
  $out = trailingslashit($cache_dir) . "pdfimg-{$attachment_id}-{$size}-{$mtime}.jpg";

  if (file_exists($out)) {
    if (!$w || !$h) { $sz = @getimagesize($out); if ($sz) { $w = $sz[0]; $h = $sz[1]; } }
    return ['path' => $out, 'w' => $w, 'h' => $h];
  }

  $editor = wp_get_image_editor($path);
  if (is_wp_error($editor)) {
    return ['path' => $path, 'w' => $w, 'h' => $h]; // repli : chemin brut
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
 * Point focal d'une image, en fractions [0,1] depuis le coin haut-gauche.
 *
 * Défaut : centre géométrique. La source réelle est branchée par le filtre
 * `sapi_catalogue_focal_point` (voir l'adaptateur Media Focus Point ci-dessous).
 *
 * @param int $attachment_id
 * @return array{0:float,1:float} [x, y], chacun dans [0,1]
 */
function sapi_catalogue_pdf_focal_point($attachment_id) {
  $fp = apply_filters('sapi_catalogue_focal_point', [0.5, 0.5], (int) $attachment_id);
  if (!is_array($fp) || count($fp) < 2) return [0.5, 0.5];
  $x = (float) $fp[0];
  $y = (float) $fp[1];
  return [min(1.0, max(0.0, $x)), min(1.0, max(0.0, $y))];
}

/**
 * Adaptateur pour l'extension « Media Focus Point » (wpcompany), installée sur
 * le site : Robin place le point focal depuis la médiathèque, et le recadrage
 * carré du PDF le respecte.
 *
 * On passe par `MFP_Background($id, false)`, l'API PUBLIQUE documentée de
 * l'extension depuis sa v1.3, qui renvoie « background-position: 45% 34%;
 * background-size: cover; ». Lire directement sa méta serait plus court mais se
 * casserait à la première mise à jour ; la fonction publique, non.
 *
 * ⚠️ Sortie tamponnée : si une version de l'extension venait à `echo` au lieu de
 * retourner, l'octet parasite corromprait le flux binaire du PDF. On capture les
 * deux cas.
 *
 * Sans l'extension (ou sans point focal posé sur l'image), elle rend 50% 50% et
 * le recadrage reste centré — dégradation silencieuse, jamais d'erreur.
 *
 * @param array $fp            valeur par défaut [x, y]
 * @param int   $attachment_id
 * @return array{0:float,1:float}
 */
function sapi_catalogue_focal_point_from_mfp($fp, $attachment_id) {
  if (!function_exists('MFP_Background')) return $fp;

  ob_start();
  $returned = MFP_Background((int) $attachment_id, false);
  $echoed   = ob_get_clean();

  $style = (is_string($returned) && $returned !== '') ? $returned : (string) $echoed;
  if (!preg_match('/background-position:\s*([\d.]+)%\s+([\d.]+)%/i', $style, $m)) return $fp;

  return [((float) $m[1]) / 100, ((float) $m[2]) / 100];
}
add_filter('sapi_catalogue_focal_point', 'sapi_catalogue_focal_point_from_mfp', 10, 2);

/**
 * Version CARRÉE d'une image, recadrée autour de son point focal.
 *
 * ⚠️ N'UTILISE PAS `$editor->resize($w, $h, true)`. WordPress n'agrandit jamais :
 * `image_resize_dimensions()` renvoie `(min(cible_w, source_w), min(cible_h,
 * source_h))`, si bien qu'une source de 1024×481 demandée en 570×650 ressort en
 * 570×481 — puis se fait étirer de 35 % par le HTML. C'est le bug de la bande
 * photo du 24/08. Ici on découpe nous-même le plus grand carré possible avec
 * `crop()`, et la sortie est carrée PAR CONSTRUCTION, quelle que soit la source.
 *
 * On ne suragrandit jamais non plus : la cible est plafonnée au côté du carré
 * disponible.
 *
 * @param int $attachment_id
 * @param int $target_px côté visé en pixels (plafonné à la source)
 * @return array{path:string,w:int,h:int}|null w === h garanti
 */
function sapi_catalogue_pdf_square_image($attachment_id, $target_px = 500) {
  $attachment_id = (int) $attachment_id;
  if (!$attachment_id) return null;

  $src = wp_get_attachment_image_src($attachment_id, 'large');
  if (!$src || empty($src[0])) return null;

  $up   = wp_upload_dir();
  $path = str_replace($up['baseurl'], $up['basedir'], $src[0]);
  if (!file_exists($path)) {
    $path = get_attached_file($attachment_id);
    if (!$path || !file_exists($path)) return null;
  }

  $size = @getimagesize($path);
  if (!$size || empty($size[0]) || empty($size[1])) return null;
  $ow = (int) $size[0];
  $oh = (int) $size[1];

  // Plus grand carré inscriptible, positionné sur le point focal puis ramené
  // dans les bornes de l'image.
  $side = min($ow, $oh);
  list($fx, $fy) = sapi_catalogue_pdf_focal_point($attachment_id);
  $sx = (int) round($fx * $ow - $side / 2);
  $sy = (int) round($fy * $oh - $side / 2);
  $sx = max(0, min($sx, $ow - $side));
  $sy = max(0, min($sy, $oh - $side));

  $target = (int) min($target_px, $side); // jamais d'agrandissement

  $mtime     = @filemtime($path) ?: 0;
  $cache_dir = trailingslashit(sapi_catalogue_pdf_tmpdir()) . 'img';
  if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);
  $out = trailingslashit($cache_dir) . "pdfsq-{$attachment_id}-{$target}-{$sx}x{$sy}-{$mtime}.jpg";

  if (!file_exists($out)) {
    $editor = wp_get_image_editor($path);
    if (is_wp_error($editor)) return null;
    $cropped = $editor->crop($sx, $sy, $side, $side, $target, $target);
    if (is_wp_error($cropped)) return null;
    $editor->set_quality(82);
    $saved = $editor->save($out, 'image/jpeg');
    if (is_wp_error($saved)) return null;
  }

  // Dimensions RELUES sur le fichier produit — jamais celles qu'on a demandées.
  $final = @getimagesize($out);
  if (!$final) return null;
  return ['path' => $out, 'w' => (int) $final[0], 'h' => (int) $final[1]];
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
    /* Bande photo du PDF pro : 3 grands carres + 1 colonne de 2 petits.
       Les largeurs et les ecarts sont poses en style inline, calcules par
       sapi_catalogue_pdf_band_geometry() a partir de la largeur utile — ne rien
       imposer ici, cette regle ne porte que le cadre general. */
    .photo-band { width: 100%; margin: 0 0 1mm; }
    .photo-band td { vertical-align: top; text-align: left; }
    .prod-head { width: 100%; }
    .prod-sku { font-family: montserrat; font-size: 8.5pt; color: #937D68; text-transform: uppercase; letter-spacing: 1px; }
    /* Filets de séparation discrets */
    .head-rule { border-top: 0.25mm solid #ece2d3; margin: 0.5mm 0 2mm; }
    .specs-rule { border-top: 0.25mm solid #ece2d3; margin: 2.5mm 0 2mm; }
    .prod-desc { font-size: 8.5pt; color: #4a443d; text-align: justify; line-height: 1.34; }
    .prod-desc p { margin: 0 0 1.5mm; }

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
 *   @type string $photos            profil de bande : 'public' (2 grandes +
 *                                   4 moyennes) ou 'pro' (3 grands + 5 petits)
 *   @type string $after_description HTML inséré APRÈS la description et AVANT les
 *                                   caractéristiques (tableau de prix du tarif
 *                                   pro). La description passe donc en premier :
 *                                   on présente l'objet, puis on le chiffre.
 * }
 * @return string HTML, saut de page inclus
 */
function sapi_catalogue_pdf_product_card_html($p, $cat_label, $args = []) {
  $args = array_merge(['photos' => 'public', 'after_description' => ''], $args);

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
  // Les deux PDF utilisent désormais une bande, avec un profil différent.
  // L'ancien gabarit « grande photo + 3 vignettes » du PDF public a été retiré :
  // plus rien ne l'appelait, et git le conserve si besoin de revenir dessus.
  $html .= sapi_catalogue_pdf_photo_band_html((int) $p['id'], $args['photos']);

  // Description (essences + tailles vivent dans le tableau caractéristiques)
  if ($p['description'] !== '') {
    $html .= '<div class="prod-desc">' . wpautop($p['description']) . '</div>';
  }

  // Tableau de prix (tarif pro uniquement — chaîne vide côté public), après la
  // description et avant les caractéristiques.
  $html .= $args['after_description'];

  if ($sections) {
    $html .= '<div class="specs-rule"></div>';
    $html .= '<table class="specs-grid"><tr>';
    $html .= '<td>' . $col_a . '</td><td>' . $col_b . '</td>';
    $html .= '</tr></table>';
  }
  $html .= '</div>';

  return $html;
}

/* ── Bande photo du PDF pro (schéma Robin, 24/08) ────────────────────────────
 *
 *   +--------+ +--------+ +--------+ +----+
 *   |        | |        | |        | | D  |     3 grands carrés de côté S
 *   | PRODUIT| |AMBIANCE| |AMBIANCE| +----+     + 1 colonne de 2 petits
 *   |        | |   1    | |   2    | | A  |       carrés de côté s
 *   +--------+ +--------+ +--------+ +----+
 *
 * TOUTES les images sont carrées et de même hauteur, la bande occupe toute la
 * largeur utile. D = photo de détail, A = photo d'accessoire.
 * ─────────────────────────────────────────────────────────────────────────── */

/**
 * Profils de bande. Les DEUX PDF divergent volontairement : leurs publics ne
 * sont pas les mêmes. Le moteur, le recadrage et les règles de repli sont
 * partagés ; seuls le nombre de cases par rangée et l'ordre de remplissage
 * changent (décision Robin, 2026-08-24 — ne pas chercher à les unifier).
 *
 *   pro     3 grands + 5 petits   — le tarif montre la gamme, dense
 *   public  2 grandes + 4 moyennes — la fiche s'ouvre sur deux mises en
 *           situation en grand, et le packshot, le détail et l'accessoire
 *           restent lisibles à 42,2 mm au lieu de 33
 *
 * `head` = cases remplies dans l'ordre ; `tail` = cases réservées à la FIN de
 * la séquence affichée (pas à un rang fixe).
 *
 * ⚠️ Largeurs utiles et écarts MESURÉS par Robin sur le rendu, pas déduits :
 * 179,2 mm et 3,46 mm pour le public. Le profil pro garde 181/3, valeurs sous
 * lesquelles sa bande a été validée à l'œil — l'écart de 1,8 mm entre les deux
 * mesures reste à trancher, il n'a pas été touché volontairement.
 *
 * @param string $profile 'pro' ou 'public'
 * @return array
 */
function sapi_catalogue_pdf_band_layout($profile = 'public') {
  $profiles = [
    'pro' => [
      'top' => 3, 'bottom' => 5, 'w' => 181.0, 'gap' => 3.0,
      'head' => ['produit', 'ambiance', 'ambiance', 'ambiance', 'ambiance', 'ambiance'],
      'tail' => ['detail', 'accessoire'],
    ],
    'public' => [
      'top' => 2, 'bottom' => 4, 'w' => 179.2, 'gap' => 3.46,
      'head' => ['ambiance', 'ambiance', 'produit', 'ambiance'],
      'tail' => ['detail', 'accessoire'],
    ],
  ];
  $p = isset($profiles[$profile]) ? $profiles[$profile] : $profiles['public'];

  // La taille des carrés n'est PAS libre : elle est imposée par la largeur.
  // Pour occuper de la hauteur, on met moins d'images par rangée — jamais des
  // images plus grandes.
  $p['s_top']    = ($p['w'] - ($p['top'] - 1) * $p['gap']) / $p['top'];
  $p['s_bottom'] = ($p['w'] - ($p['bottom'] - 1) * $p['gap']) / $p['bottom'];
  $p['height']   = $p['s_top'] + $p['gap'] + $p['s_bottom'];
  return $p;
}

/**
 * Séquence des photos de la bande, jusqu'à 8, sans aucun trou.
 *
 * ⚠️ Les types ne sont PLUS des réservations d'emplacement : c'est ce qui
 * laissait une case blanche en bas à droite sur toutes les fiches, faute de
 * photo d'accessoire. Ce sont désormais des PRÉFÉRENCES avec repli, et une case
 * n'est jamais vide parce que la photo de son type manque (addendum 2).
 *
 * Construction :
 *   - une photo de détail et une d'accessoire sont MISES DE CÔTÉ pour la fin de
 *     la séquence, dans cet ordre : ce sont les deux dernières cases AFFICHÉES,
 *     pas les cases n°7 et 8. À 6 photos, la rangée basse donne donc
 *     ambiance / détail / accessoire.
 *   - le reste est rempli par la photo produit, puis les ambiances, puis les
 *     détails et accessoires excédentaires en appoint.
 *
 * ⚠️ La photo produit n'est PAS `gallery_ids[0]` : la galerie du catalogue vient
 * de sapi_get_product_photo_ids_with_fallback() et commence par une ambiance. On
 * lit donc l'image mise en avant explicitement.
 *
 * Dédoublonnage global : une même image n'apparaît jamais deux fois.
 *
 * @param int $product_id
 * @return array<int,int> 0 à 8 IDs d'attachment, dans l'ordre d'affichage
 */
function sapi_catalogue_pdf_band_photos($product_id, $layout) {
  $seen = [];

  $featured = (int) get_post_thumbnail_id($product_id);
  if ($featured) $seen[$featured] = true;

  $gallery = function ($type) use ($product_id, &$seen) {
    $out = [];
    if (!function_exists('sapi_get_product_photo_ids')) return $out;
    foreach ((array) sapi_get_product_photo_ids($product_id, $type) as $id) {
      $id = (int) $id;
      if (!$id || isset($seen[$id])) continue;
      $seen[$id] = true;
      $out[] = $id;
    }
    return $out;
  };

  // Réserves par type. `produit` n'en contient qu'une : l'image mise en avant.
  $pools = [
    'produit'    => $featured ? [$featured] : [],
    'ambiance'   => $gallery('ambiance'),
    'detail'     => $gallery('detail'),
    'accessoire' => $gallery('accessoires'),
  ];

  // Cascade de repli : le type voulu d'abord, puis l'ordre générique. Elle
  // reproduit exactement le tableau de l'addendum — ambiance se replie sur
  // détail, détail sur ambiance, accessoire sur ambiance, produit sur ambiance.
  $take = function ($wanted) use (&$pools) {
    foreach (array_unique([$wanted, 'ambiance', 'detail', 'accessoire', 'produit']) as $type) {
      if (!empty($pools[$type])) return (int) array_shift($pools[$type]);
    }
    return 0;
  };

  // Les cases de fin sont réservées EN PREMIER, sinon leur photo serait
  // consommée en appoint par une case précédente et la réserve tomberait à vide.
  // ⚠️ Mais sur leur type STRICT, sans repli : sinon, un produit sans accessoire
  // voyait le repli lui prendre la PREMIÈRE ambiance, qui se retrouvait reléguée
  // en dernière petite case au lieu d'ouvrir la fiche en grand. Les réserves non
  // servies sont repliées plus bas, une fois les cases de tête pourvues.
  $tail = [];
  $unserved = 0;
  foreach ($layout['tail'] as $wanted) {
    if (!empty($pools[$wanted])) $tail[] = (int) array_shift($pools[$wanted]);
    else $unserved++;
  }

  $head = [];
  foreach ($layout['head'] as $wanted) {
    $id = $take($wanted);
    if ($id) $head[] = $id;
  }

  // Repli des réserves restées vides : ce qui traîne encore, en fin de séquence.
  for ($i = 0; $i < $unserved; $i++) {
    $id = $take('ambiance');
    if (!$id) break;
    $tail[] = $id;
  }

  $ids = array_values(array_merge($head, $tail));

  return [
    'ids' => $ids,
    // Le filet clair suit le PACKSHOT où qu'il atterrisse : case 1 côté pro,
    // case 3 côté public, et ailleurs si des cases précédentes sont restées
    // vides et que la séquence s'est compactée.
    'framed' => $featured ? array_search($featured, $ids, true) : false,
  ];
}

/**
 * Bande photo du PDF pro. Toutes les images sont carrées PAR CONSTRUCTION
 * (sapi_catalogue_pdf_square_image), donc imposer un côté carré en mm ne peut
 * pas déformer — c'est exactement ce qui clochait dans la version précédente.
 *
 * @param int $product_id
 * @return string HTML
 */
function sapi_catalogue_pdf_photo_band_html($product_id, $profile = 'public') {
  $layout = sapi_catalogue_pdf_band_layout($profile);
  $photos = sapi_catalogue_pdf_band_photos($product_id, $layout);
  $ids    = $photos['ids'];
  if (!$ids) return '';

  $big    = $layout['s_top'];
  $small  = $layout['s_bottom'];
  $gap    = $layout['gap'];
  $framed = $photos['framed'];

  $n       = count($ids);
  $n_big   = min($layout['top'], $n);
  $n_small = $n - $n_big;

  /**
   * Vignette carrée. Le filet ne va QUE sur la photo produit : c'est un
   * détourage sur blanc posé sur le fond crème de la carte, sans cadre il se
   * lit comme un vide. L'épaisseur du filet est retranchée de l'image pour que
   * la case garde exactement son côté et que la grille reste juste.
   */
  $cell = function ($id, $side_mm, $framed = false) {
    // ~250 dpi : au-delà du besoin d'impression, sans alourdir le PDF.
    $img = sapi_catalogue_pdf_square_image($id, (int) round($side_mm / 25.4 * 250));
    if (!$img || empty($img['path'])) return '';
    if (!$framed) {
      return sprintf('<img src="%s" style="width:%.2fmm;height:%.2fmm;border-radius:2mm;">',
        esc_attr($img['path']), $side_mm, $side_mm);
    }
    $b = 0.25;
    return sprintf('<img src="%s" style="width:%.2fmm;height:%.2fmm;border:%.2fmm solid #ece2d3;border-radius:2mm;">',
      esc_attr($img['path']), $side_mm - 2 * $b, $side_mm - 2 * $b, $b);
  };

  /**
   * Une rangée, alignée à GAUCHE : les cases gardent leur côté et le blanc
   * reste à droite. Pas de recentrage, pas d'agrandissement — agrandir ferait
   * sortir la bande du budget vertical de la fiche.
   */
  $row = function ($items, $side, $offset) use ($gap, $cell, $framed) {
    $count = count($items);
    if (!$count) return '';
    $width = $count * $side + ($count - 1) * $gap;
    $html  = sprintf('<table style="width:%.2fmm;"><tr>', $width);
    foreach ($items as $i => $id) {
      $pad = ($i < $count - 1) ? $gap : 0;
      $html .= sprintf('<td style="width:%.2fmm; padding:0 %.2fmm 0 0;">', $side + $pad, $pad);
      $html .= $cell($id, $side, $framed !== false && ($offset + $i) === $framed);
      $html .= '</td>';
    }
    return $html . '</tr></table>';
  };

  $top    = $row(array_slice($ids, 0, $n_big), $big, 0);
  $bottom = $n_small ? $row(array_slice($ids, $n_big), $small, $n_big) : '';

  // Rangée basse absente (moins de 4 photos) → pas de rangée vide, et pas
  // d'espacement inutile sous la rangée haute.
  $html  = '<table class="photo-band">';
  $html .= sprintf('<tr><td style="padding:0 0 %.2fmm 0;">%s</td></tr>', $bottom ? $gap : 0, $top);
  if ($bottom) $html .= sprintf('<tr><td style="padding:0;">%s</td></tr>', $bottom);
  $html .= '</table>';
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
