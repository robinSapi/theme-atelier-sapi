<?php
/**
 * Catalogue — page admin « Produits > Catalogue PRO ».
 *
 * Interface de génération du tarif professionnel. Le taux de remise est saisi
 * ICI, au moment de l'export, et ne vit que le temps de la génération : il n'est
 * jamais publié, jamais associé à un client en base, jamais exposé sur le web.
 *
 * Génération, cache et route d'export : inc/catalogue-pdf-pro.php.
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
  add_submenu_page(
    'edit.php?post_type=product',
    'Catalogue PRO — tarif professionnel',
    'Catalogue PRO',
    SAPI_CATALOGUE_PRO_CAP,
    'sapi-catalogue-pro',
    'sapi_catalogue_pro_admin_render'
  );
});

/** Messages de retour après un export. */
function sapi_catalogue_pro_messages() {
  return [
    'empty'  => ['error',   'Aucun produit sélectionné : l’export a été refusé.'],
    'nompdf' => ['error',   'Export indisponible : mPDF n’est pas déployé sur ce serveur (dossier vendor/ absent).'],
    'error'  => ['error',   'Échec de la génération du PDF. Vos réglages ont été conservés.'],
  ];
}

function sapi_catalogue_pro_admin_render() {
  if (!current_user_can(SAPI_CATALOGUE_PRO_CAP)) wp_die('Accès refusé.');

  $settings  = sapi_catalogue_pro_get_settings();
  $catalogue = function_exists('sapi_catalogue_get_products') ? sapi_catalogue_get_products() : [];

  // Sélection reproposée : celle du dernier export. Au tout premier passage
  // (aucune sélection enregistrée), tout est coché.
  $saved_ids   = $settings['ids'];
  $first_run   = empty($saved_ids);
  $total       = 0;
  foreach ($catalogue as $block) $total += count($block['products']);

  $msg_code = isset($_GET['sapi_pro_msg']) ? sanitize_key(wp_unslash($_GET['sapi_pro_msg'])) : '';
  $messages = sapi_catalogue_pro_messages();
  ?>
  <div class="wrap sapi-pro">
    <h1>Catalogue PRO — tarif professionnel</h1>

    <?php if ($msg_code && isset($messages[$msg_code])) : ?>
      <div class="notice notice-<?php echo esc_attr($messages[$msg_code][0]); ?> is-dismissible">
        <p><?php echo esc_html($messages[$msg_code][1]); ?></p>
      </div>
    <?php endif; ?>

    <p class="description" style="max-width:760px">
      Génère un PDF de tarif professionnel à envoyer à un revendeur. Le taux de remise
      est appliqué à cet export uniquement : il n’apparaît nulle part dans le document,
      n’est enregistré nulle part avec le nom du client, et n’existe sur aucune page du site.
      Le revendeur voit ses prix, pas la remise qu’on lui consent par rapport à un autre.
    </p>

    <?php if (!$total) :
      // Rien à proposer : on referme .wrap et on sort, plutôt que d'afficher un
      // formulaire d'export sans un seul produit à cocher.
      echo '<div class="notice notice-warning"><p>Aucun produit trouvé dans les catégories du catalogue.</p></div>';
      echo '</div>';
      return;
    endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="sapi-pro-form">
      <input type="hidden" name="action" value="sapi_catalogue_pro_pdf">
      <?php wp_nonce_field('sapi_catalogue_pro_pdf'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="sapi-pro-rate">Taux de remise</label></th>
          <td>
            <input name="rate" id="sapi-pro-rate" type="number" step="0.1" min="0" max="95"
                   value="<?php echo esc_attr($settings['rate']); ?>" class="small-text"> %
            <p class="description">Appliqué au PVP TTC après retrait de la TVA (20 %), arrondi à l’euro inférieur.</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="sapi-pro-client">Établi pour</label></th>
          <td>
            <input name="client" id="sapi-pro-client" type="text" class="regular-text" value=""
                   placeholder="Nom du revendeur (facultatif)" autocomplete="off">
            <p class="description">
              Affiché en page de garde. <strong>Repart toujours vide</strong> : jamais un tarif envoyé
              au nom du client précédent.
            </p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="sapi-pro-date">Date d’export</label></th>
          <td><input name="date_export" id="sapi-pro-date" type="date" value="<?php echo esc_attr($settings['date']); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="sapi-pro-valid">Valable jusqu’au</label></th>
          <td><input name="date_valid" id="sapi-pro-valid" type="date" value="<?php echo esc_attr($settings['valid']); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="sapi-pro-mentions">Mentions légales</label></th>
          <td>
            <textarea name="mentions" id="sapi-pro-mentions" rows="14" class="large-text code"><?php
              echo esc_textarea($settings['mentions']);
            ?></textarea>
            <p class="description">
              Imprimées sur une page dédiée intitulée <strong>« Tarif professionnel »</strong>, après les pages
              Histoire et Deux bois. À compléter une fois (les mentions <code>[à compléter]</code>), la valeur est
              ensuite conservée d’un export à l’autre.<br>
              Inutile de commencer par un titre : la page en porte déjà un.
            </p>
          </td>
        </tr>
      </table>

      <h2>Produits à inclure</h2>
      <p class="description">
        L’ordre du PDF reste celui du site (catégorie puis ordre de la boutique), pas l’ordre de cochage.
        Une catégorie sans produit sélectionné disparaît du document.
      </p>
      <p><strong><span id="sapi-pro-count">0</span> produit(s) sélectionné(s)</strong> sur <?php echo (int) $total; ?></p>

      <div class="sapi-pro-cats">
        <?php foreach ($catalogue as $slug => $block) : ?>
          <?php if (empty($block['products'])) continue; ?>
          <div class="sapi-pro-cat" style="margin:0 0 22px; padding:14px 16px; background:#fff; border:1px solid #dcdcde;">
            <p style="margin:0 0 10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
              <strong style="font-size:14px;"><?php echo esc_html($block['label']); ?></strong>
              <button type="button" class="button button-small sapi-pro-all" data-cat="<?php echo esc_attr($slug); ?>">Tout</button>
              <button type="button" class="button button-small sapi-pro-none" data-cat="<?php echo esc_attr($slug); ?>">Rien</button>
            </p>
            <?php foreach ($block['products'] as $p) :
              $pid     = (int) $p['id'];
              $checked = $first_run || in_array($pid, $saved_ids, true);
              ?>
              <label style="display:block; padding:3px 0;">
                <?php // ⚠️ Nom UNIQUE par case (produits[<id>]), jamais produits[] :
                      // le WAF o2switch fusionne les paramètres de même nom et une
                      // seule valeur survivrait (piège rencontré en Tâche 5). ?>
                <input type="checkbox" class="sapi-pro-box" data-cat="<?php echo esc_attr($slug); ?>"
                       name="produits[<?php echo $pid; ?>]" value="1" <?php checked($checked); ?>>
                <?php echo esc_html($p['title']); ?>
                <?php if ($p['sku'] !== '') : ?>
                  <span style="color:#787c82;">— <?php echo esc_html($p['sku']); ?></span>
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <p class="submit">
        <button type="submit" class="button button-primary" id="sapi-pro-submit">Générer le PDF</button>
      </p>
    </form>

    <?php
    $log = sapi_catalogue_pro_get_log();
    if ($log) : ?>
      <h2>Journal des exports</h2>
      <table class="widefat striped" style="max-width:860px">
        <thead><tr><th>Date</th><th>Client</th><th>Taux</th><th>Produits</th><th>Par</th></tr></thead>
        <tbody>
        <?php foreach ($log as $entry) : ?>
          <tr>
            <td><?php echo esc_html(mysql2date('j M Y H:i', $entry['when'])); ?></td>
            <td><?php echo $entry['client'] !== '' ? esc_html($entry['client']) : '<span style="color:#787c82">—</span>'; ?></td>
            <td><?php echo esc_html(number_format_i18n((float) $entry['rate'], 1)); ?> %</td>
            <td><?php echo (int) $entry['products']; ?></td>
            <td><?php echo esc_html($entry['user']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <script>
  (function () {
    var form   = document.getElementById('sapi-pro-form');
    if (!form) return;
    var boxes  = Array.prototype.slice.call(form.querySelectorAll('.sapi-pro-box'));
    var count  = document.getElementById('sapi-pro-count');
    var submit = document.getElementById('sapi-pro-submit');
    var rate   = document.getElementById('sapi-pro-rate');
    var WARN   = <?php echo (float) SAPI_CATALOGUE_PRO_RATE_WARN; ?>;

    function selected() { return boxes.filter(function (b) { return b.checked; }); }
    function sync() {
      var n = selected().length;
      count.textContent = n;
      submit.disabled = (n === 0);   // export refusé à zéro produit
    }
    boxes.forEach(function (b) { b.addEventListener('change', sync); });

    form.querySelectorAll('.sapi-pro-all, .sapi-pro-none').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var on = btn.classList.contains('sapi-pro-all');
        boxes.forEach(function (b) {
          if (b.getAttribute('data-cat') === btn.getAttribute('data-cat')) b.checked = on;
        });
        sync();
      });
    });

    // Garde-fou : au-delà du seuil, confirmation explicite avant génération.
    form.addEventListener('submit', function (e) {
      var v = parseFloat((rate.value || '0').replace(',', '.'));
      if (v > WARN && !window.confirm(
        'Remise de ' + v + ' %, au-delà du seuil habituel de ' + WARN + ' %.\n\nGénérer ce tarif ?'
      )) {
        e.preventDefault();
      }
    });

    sync();
  })();
  </script>
  <?php
}
