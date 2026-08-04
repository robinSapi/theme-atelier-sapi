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
    $mpdf = new \Mpdf\Mpdf([
      'mode'    => 'utf-8',
      'format'  => 'A4',
      'tempDir' => $tmpdir,
    ]);
    $diag['mpdf_version'] = \Mpdf\Mpdf::VERSION;
    $mpdf->WriteHTML('<h1 style="font-family:sans-serif">Atelier Sâpi — self-test mPDF</h1><p style="font-family:sans-serif">Rendu accentué : é è à ù ç œ €. Page A4 unique.</p>');
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
