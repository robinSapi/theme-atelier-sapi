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
    'margin_left'       => 15,
    'margin_right'      => 15,
    'margin_top'        => 16,
    'margin_bottom'     => 16,
  ];

  return new \Mpdf\Mpdf(array_merge($defaults, $config));
}

/* =========================================================================
 * Route REST self-test (SÉ 1) — diagnostic, à retirer/sécuriser après la S1.
 * GET /wp-json/sapi/v1/catalogue-pdf-selftest        → JSON diagnostic
 * GET /wp-json/sapi/v1/catalogue-pdf-selftest?download=1 → PDF « hello » inline
 * ========================================================================= */
add_action('rest_api_init', function () {
  register_rest_route('sapi/v1', '/catalogue-pdf-selftest', [
    'methods'             => 'GET',
    'permission_callback' => '__return_true',
    'callback'            => 'sapi_catalogue_pdf_selftest',
  ]);
});

function sapi_catalogue_pdf_selftest($request) {
  $tmpdir = sapi_catalogue_pdf_tmpdir();
  $diag = [
    'php_version'        => PHP_VERSION,
    'memory_limit'       => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_basedir'     => wp_upload_dir()['basedir'],
    'tmpdir'             => $tmpdir,
    'tmpdir_writable'    => wp_is_writable($tmpdir),
    'vendor_present'     => file_exists(get_template_directory() . '/vendor/autoload.php'),
    'mpdf_class'         => false,
    'mpdf_version'       => null,
    'render'             => null,
    'gd_or_imagick'      => extension_loaded('gd') ? 'gd' : (extension_loaded('imagick') ? 'imagick' : 'none'),
  ];

  if (!sapi_catalogue_pdf_autoload()) {
    return rest_ensure_response($diag);
  }
  $diag['mpdf_class'] = true;

  try {
    $t0 = microtime(true);
    $mpdf = sapi_catalogue_pdf_new_mpdf();
    $diag['mpdf_version'] = \Mpdf\Mpdf::VERSION;
    $diag['fonts_ok'] = true; // pas d'exception = fontdata chargé
    $mpdf->WriteHTML(
      '<h1 style="font-family:squarepeg; font-size:34px; color:#937D68">Atelier Sâpi — self-test</h1>' .
      '<p style="font-family:montserrat">Corps Montserrat. Accents : é è à ù ç œ €.</p>' .
      '<p style="font-family:montserratblack; font-size:16px">Montserrat Black.</p>'
    );
    $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

    $diag['render'] = [
      'ok'          => strlen($pdf) > 0,
      'bytes'       => strlen($pdf),
      'seconds'     => round(microtime(true) - $t0, 2),
      'peak_mem_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
    ];

    if ($request->get_param('download')) {
      header('Content-Type: application/pdf');
      header('Content-Disposition: inline; filename="sapi-selftest.pdf"');
      echo $pdf; // phpcs:ignore — flux binaire PDF
      exit;
    }
  } catch (\Throwable $e) {
    $diag['render'] = ['ok' => false, 'error' => $e->getMessage()];
  }

  return rest_ensure_response($diag);
}
