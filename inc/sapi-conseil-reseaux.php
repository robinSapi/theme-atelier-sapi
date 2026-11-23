<?php
/**
 * Conseil de Robin — page cachée pour les réseaux sociaux (nov. 2026)
 * ─────────────────────────────────────────────────────────────────────────
 * Une publication Insta / Facebook / Pinterest renvoie vers une page cachée
 * (template `page-conseil-robin.php`). La modale du Conseiller s'y ouvre en
 * plein écran, pose ses questions, puis, AU LIEU de montrer la sélection,
 * demande prénom + email (+ un mot facultatif). Robin envoie ensuite son
 * conseil par email.
 *
 * Ce fichier porte la partie serveur :
 *   - `sapi_is_landing_conseil()` : la page courante est-elle cette landing ?
 *   - noindex de la page ;
 *   - la table `wp_sapi_conseil_demandes` (une ligne par demande) ;
 *   - l'endpoint AJAX `sapi_conseil_lead` (enregistre, inscrit à Brevo #6,
 *     prévient Robin) ;
 *   - les deux écrans de la modale propres à la landing (formulaire, merci).
 *
 * Étape 1 du chantier. L'envoi de l'email au visiteur (brouillon préparé,
 * validation en un clic, départ auto à 24 h sans commentaire) est l'étape 2 :
 * les colonnes `mode`, `envoi_prevu` et `envoye_le` sont posées dès
 * maintenant pour elle, mais RIEN n'envoie encore de mail au visiteur.
 *
 * ⚠️ DÉCISIONS DE ROBIN (23/11/2026)
 *   - Email obligatoire avant le conseil, avec un bouton de sortie vers
 *     /mes-creations/. Pas de miniatures floutées en appât.
 *   - « Robin prendra le temps de regarder ton cas » doit être VRAI : sans
 *     commentaire ni photo, le mail préparé part seul au bout de 24 h si Robin
 *     ne l'a pas validé avant ; avec un commentaire (ou une photo, étape 3),
 *     Robin répond lui-même, rien ne part tout seul.
 *   - -10 % BIENVENUE10 conservé, dans le mail de Robin.
 *
 * ⚠️ CETTE TABLE STOCKE DES EMAILS, à l'inverse de `wp_sapi_megafilter_sessions`
 * (décision du 27/08 : plus aucune adresse dans les sessions). Ici c'est
 * nécessaire, l'email doit partir PLUS TARD. La purge (`sapi_conseil_purger`)
 * vide prénom, email et commentaire 6 mois après la demande.
 */

if (!defined('ABSPATH')) exit;

define('SAPI_CONSEIL_TEMPLATE', 'page-conseil-robin.php');
if (!defined('SAPI_CONSEIL_DB_VERSION')) define('SAPI_CONSEIL_DB_VERSION', '4');

/** La page courante est-elle la landing « Conseil de Robin » ? */
function sapi_is_landing_conseil() {
  return is_page_template(SAPI_CONSEIL_TEMPLATE);
}

/* Page cachée : jamais indexée. Yoast passe par ce filtre (WP ≥ 5.7), mais
   cocher « Non » dans « Autoriser les moteurs de recherche… » sur la page
   reste la ceinture. */
add_filter('wp_robots', function ($robots) {
  if (sapi_is_landing_conseil()) {
    $robots['noindex']  = true;
    $robots['nofollow'] = true;
  }
  return $robots;
});

/* ─────────────────────────────────────────────────────────────────────────
   Table des demandes
   ───────────────────────────────────────────────────────────────────────── */
function sapi_conseil_table() {
  global $wpdb;
  return $wpdb->prefix . 'sapi_conseil_demandes';
}

function sapi_conseil_create_table() {
  global $wpdb;
  $table   = sapi_conseil_table();
  $charset = $wpdb->get_charset_collate();
  /* ⚠️ Aucun commentaire DANS la chaîne : dbDelta lirait chaque ligne comme
     une colonne (même piège que la table des sessions).
     `mode` : 'auto' (part seul à 24 h) ou 'manuel' (Robin répond).
     `statut` : 'a_traiter' → 'envoi' (verrou pendant l'envoi) → 'envoye',
     ou 'repondu' (Robin a répondu depuis sa boîte), ou 'annule'.
     `corps` : texte brut éditable par Robin ; la ligne [photo] devient la
     photo du luminaire `produit_id` à l'envoi. */
  $sql = "CREATE TABLE $table (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(36) NOT NULL DEFAULT '',
    prenom VARCHAR(60) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    commentaire TEXT NULL,
    piece VARCHAR(40) NOT NULL DEFAULT '',
    answers LONGTEXT NULL,
    labels LONGTEXT NULL,
    nb_photos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    mode VARCHAR(10) NOT NULL DEFAULT 'auto',
    statut VARCHAR(20) NOT NULL DEFAULT 'a_traiter',
    envoi_prevu DATETIME NULL DEFAULT NULL,
    envoye_le DATETIME NULL DEFAULT NULL,
    source_url VARCHAR(500) NOT NULL DEFAULT '',
    produit_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    sujet VARCHAR(200) NOT NULL DEFAULT '',
    corps LONGTEXT NULL,
    brouillon_le DATETIME NULL DEFAULT NULL,
    relance_le DATETIME NULL DEFAULT NULL,
    verrou_le DATETIME NULL DEFAULT NULL,
    alternatives VARCHAR(60) NOT NULL DEFAULT '',
    PRIMARY KEY  (id),
    KEY statut (statut),
    KEY envoi_prevu (envoi_prevu),
    KEY session_id (session_id)
  ) $charset;";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}

/* Sur `init` (pas `admin_init`) : la première demande peut arriver avant que
   Robin ne passe dans l'admin. Le contrôle ne coûte qu'un get_option. */
add_action('init', function () {
  $avant = get_option('sapi_conseil_db_version');
  if ($avant === SAPI_CONSEIL_DB_VERSION) return;
  sapi_conseil_create_table();
  global $wpdb;
  $table = sapi_conseil_table();
  /* L'option n'est posée que si la DERNIÈRE colonne existe vraiment : une
     table présente mais un ALTER raté laisserait l'étape 2 morte en silence
     (updates et SELECT sur des colonnes absentes renvoient faux sans bruit). */
  if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return;
  if (!$wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'alternatives'")) return;
  /* ⚠️ Les demandes nées AVANT l'étape 2 (recette de l'étape 1) n'ont pas de
     brouillon et leurs 24 h sont échues : sans ça, le premier cron les
     préparerait et les enverrait d'un coup, sans relecture. On les annule. */
  if ($avant === '1') {
    $wpdb->query("UPDATE $table SET statut = 'annule' WHERE statut = 'a_traiter'");
  }
  update_option('sapi_conseil_db_version', SAPI_CONSEIL_DB_VERSION, true);
});

/** Vide prénom, email et commentaire des demandes de plus de 6 mois. */
function sapi_conseil_purger() {
  global $wpdb;
  $table = sapi_conseil_table();
  if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return;
  $wpdb->query(
    "UPDATE $table SET prenom = '', email = '', commentaire = NULL, sujet = '', corps = NULL
      WHERE created_at < (NOW() - INTERVAL 6 MONTH) AND email <> ''"
  );
}
add_action('sapi_conseil_purge_event', 'sapi_conseil_purger');
add_action('init', function () {
  if (!wp_next_scheduled('sapi_conseil_purge_event')) {
    wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'sapi_conseil_purge_event');
  }
});

/* ─────────────────────────────────────────────────────────────────────────
   Endpoint : enregistrement d'une demande
   ───────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_sapi_conseil_lead', 'sapi_ajax_conseil_lead');
add_action('wp_ajax_nopriv_sapi_conseil_lead', 'sapi_ajax_conseil_lead');

function sapi_ajax_conseil_lead() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-megafilter')) {
    wp_send_json_error(['message' => 'nonce', 'fallback' => 'La page a expiré. Recharge-la et réessaie, tes réponses sont gardées.']);
  }

  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_error([
      'message'  => 'rate_limit',
      'fallback' => 'Trop de demandes pour le moment. Réessaie dans une heure ou écris directement à robin@atelier-sapi.fr.',
    ]);
  }

  $prenom      = isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '';
  $email       = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
  $commentaire = isset($_POST['commentaire']) ? sanitize_textarea_field(wp_unslash($_POST['commentaire'])) : '';
  $prenom      = trim(mb_substr($prenom, 0, 60));

  /* ⚠️ Le prénom est recopié dans l'objet et la première ligne du mail que
     Robin envoie. Sans ce filtre, un bot pourrait y glisser un lien ou une
     arnaque et faire envoyer son message, par robin@, à l'adresse d'un tiers.
     Un prénom n'a ni chiffre, ni @, ni « www », ni « mot.domaine ». */
  if ($prenom === '' || preg_match('~[0-9@/:]|www|\.\p{L}{2,}~iu', $prenom)) {
    wp_send_json_error(['message' => 'prenom', 'fallback' => 'Indique ton prénom.']);
  }
  if (!is_email($email)) {
    wp_send_json_error(['message' => 'email', 'fallback' => 'Cet email ne semble pas valide.']);
  }
  if (mb_strlen($commentaire) > 1500) {
    wp_send_json_error(['message' => 'long', 'fallback' => 'Ton message est un peu long (1500 caractères maximum).']);
  }

  /* Honeypot, time-trap, anti-junk : mêmes couches que les autres contacts,
     rejet SILENCIEUX (succès sans rien faire) pour ne rien apprendre au bot. */
  if (!empty($_POST['website'])) {
    wp_send_json_success(['prenom' => $prenom]);
  }
  if (!sapi_time_trap_valid($_POST['sapi_ts'] ?? 0, $_POST['sapi_tsig'] ?? '')) {
    wp_send_json_success(['prenom' => $prenom]);
  }
  /* ⚠️ Le test anti-junk porte sur l'EMAIL seulement. Avec le commentaire,
     un mot court (« top ! », « 3m ») faisait jeter la demande entière en
     silence : le visiteur voyait « Merci » et ne recevait jamais rien. */
  if (sapi_is_junk_contact($email, '', false)) {
    wp_send_json_success(['prenom' => $prenom]);
  }

  // Projet : confronté au questionnaire, comme partout ailleurs.
  $answers = [];
  $labels  = [];
  if (isset($_POST['project'])) {
    $project_raw = json_decode(wp_unslash($_POST['project']), true);
    if (is_array($project_raw)) {
      list($answers, $labels) = sapi_megafilter_sanitize_project(
        $project_raw['answers'] ?? [],
        $project_raw['labels'] ?? []
      );
    }
  }
  $piece = $answers['piece'] ?? '';

  $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
  $session_id = mb_substr($session_id, 0, 36);
  $source_url = isset($_POST['source_url']) ? esc_url_raw(wp_unslash($_POST['source_url'])) : '';

  /* Mode : un commentaire = Robin répond lui-même, rien ne part seul.
     (Étape 3 : une photo aura le même effet.) */
  $mode        = ($commentaire !== '') ? 'manuel' : 'auto';
  $envoi_prevu = ($mode === 'auto')
    ? gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS))
    : null;

  global $wpdb;
  $inserted = $wpdb->insert(sapi_conseil_table(), [
    'created_at'  => current_time('mysql'),
    'session_id'  => $session_id,
    'prenom'      => $prenom,
    'email'       => $email,
    'commentaire' => $commentaire,
    'piece'       => $piece,
    'answers'     => wp_json_encode($answers),
    'labels'      => wp_json_encode($labels),
    'mode'        => $mode,
    'statut'      => 'a_traiter',
    'envoi_prevu' => $envoi_prevu,
    'source_url'  => mb_substr($source_url, 0, 500),
  ]);
  $demande_id = $inserted ? (int) $wpdb->insert_id : 0;
  if (!$inserted) {
    /* On ne perd pas la demande pour autant : le mail à Robin part quand même
       et contient tout. Mais l'étape 2 ne pourra pas la traiter seule. */
    error_log('[sapi-conseil] Insert échoué (session ' . $session_id . ') : ' . $wpdb->last_error);
  }

  /* Brevo #6, SOURCE=reseaux. On n'envoie QUE `SOURCE` (tout autre attribut
     inconnu fait rejeter l'ajout entier en 400).
     ⚠️ `reseaux` NE DOIT PAS entrer dans la branche A de l'automatisation
     d'accueil : son E1 (code BIENVENUE10) arriverait AVANT le mail de Robin
     et trahirait l'automatique. Non bloquant si Brevo échoue. */
  $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
  if ($api_key) {
    $brevo = wp_remote_post('https://api.brevo.com/v3/contacts', [
      'headers' => [
        'api-key'      => $api_key,
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
      ],
      'body'    => wp_json_encode([
        'email'         => $email,
        'listIds'       => [6],
        'attributes'    => ['SOURCE' => 'reseaux'],
        'updateEnabled' => true,
      ]),
      'timeout' => 15,
    ]);
    if (is_wp_error($brevo)) {
      error_log('[sapi-conseil] Brevo HTTP (demande ' . $demande_id . ') : ' . $brevo->get_error_message());
    } else {
      $code = wp_remote_retrieve_response_code($brevo);
      if ($code < 200 || $code >= 300) {
        error_log('[sapi-conseil] Brevo ' . $code . ' (demande ' . $demande_id . ') : ' . wp_remote_retrieve_body($brevo));
      }
    }
  }

  // Mail à Robin
  $piece_label = $labels['piece'] ?? '';
  $subject = ($mode === 'manuel' ? '[Conseil réseaux · à toi de répondre] ' : '[Conseil réseaux] ')
    . $prenom . ($piece_label !== '' ? ' · ' . $piece_label : '');

  $body  = "Nouvelle demande depuis la page Conseil de Robin (réseaux sociaux).\n\n";
  $body .= "── Visiteur ──\n" . $prenom . ' <' . $email . ">\n\n";
  $body .= "── Projet ──\n" . (!empty($answers) ? sapi_megafilter_format_project_text($answers, $labels) : '(aucune réponse transmise)') . "\n\n";
  $body .= "── Son mot ──\n" . ($commentaire !== '' ? $commentaire : '(aucun)') . "\n\n";
  if ($mode === 'manuel') {
    $body .= "── À faire ──\nIl ou elle a laissé un mot : réponds toi-même, sous 3 jours (promis à l'écran).\n\n";
  } else {
    $body .= "── À faire ──\nPas de mot : le conseil préparé partira seul dans 24 h. Tu peux le relire, le modifier ou l'envoyer avant.\n\n";
  }
  if ($demande_id) {
    $body .= "── Brouillon et envoi ──\n" . admin_url('admin.php?page=sapi-conseil-reseaux&demande=' . $demande_id) . "\n\n";
  }
  $body .= "── Source ──\n";
  $body .= 'Page : ' . ($source_url !== '' ? $source_url : '(non transmise)') . "\n";
  $body .= 'Demande n° : ' . ($demande_id ?: '(non enregistrée)') . "\n";
  $body .= 'Session : ' . ($session_id !== '' ? $session_id : '(non transmise)') . "\n";
  $body .= 'Date : ' . current_time('mysql') . "\n";

  $sent = wp_mail('robin@atelier-sapi.fr', $subject, $body, [
    'Content-Type: text/plain; charset=UTF-8',
    // Adresse seule : un prénom avec virgule casserait l'en-tête.
    'Reply-To: ' . $email,
  ]);
  if (!$sent) {
    error_log('[sapi-conseil] wp_mail vers Robin échoué pour la demande ' . $demande_id);
  }

  /* Ni l'insert ni le mail : la demande est perdue, on le dit au visiteur
     plutôt que de lui promettre un mail qui ne viendra jamais. */
  if (!$inserted && !$sent) {
    wp_send_json_error([
      'message'  => 'save_failed',
      'fallback' => 'Petit souci de mon côté. Tu peux réessayer ou écrire directement à robin@atelier-sapi.fr.',
    ]);
  }

  /* Brouillon préparé hors de la requête du visiteur : l'appel IA prend
     plusieurs secondes et il n'a pas à les attendre. Le cron horaire et la
     page admin rattrapent si cet événement ne passe pas. */
  if ($demande_id) {
    wp_schedule_single_event(time(), 'sapi_conseil_preparer_event', [$demande_id]);
  }

  if (function_exists('sapi_megafilter_marquer_contact_envoye')) {
    sapi_megafilter_marquer_contact_envoye($session_id);
  }

  wp_send_json_success(['prenom' => $prenom]);
}

/* ─────────────────────────────────────────────────────────────────────────
   Écrans de la modale propres à la landing
   Appelé depuis sapi_render_conseiller_modal(), uniquement sur la landing.
   ───────────────────────────────────────────────────────────────────────── */
function sapi_render_conseil_lead_screens($pencil_svg) {
  $avatar = sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg', 'thumbnail', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']);
  ?>
      <!-- ═══ Landing réseaux — prénom + email + un mot ═══════ -->
      <section class="modal__screen" data-screen="s-lead" hidden>
        <header class="modal__head">
          <div class="conseiller-sig conseiller-sig--v1">
            <span class="conseiller-sig__avatar"><?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="conseiller-sig__text"><span class="conseiller-sig__who">Le conseil de Robin</span><span class="conseiller-sig__hook">Je regarde ton projet</span></span>
          </div>
          <span class="badge">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php esc_html_e('Ton projet', 'theme-sapi-maison'); ?>
          </span>
        </header>

        <div class="modal__body">
          <div class="modal__body-content">
            <h2 class="h2"><?php esc_html_e('Merci, j\'ai tout ce qu\'il me faut', 'theme-sapi-maison'); ?></h2>
            <p class="subtitle"><?php esc_html_e('Robin prendra le temps de regarder ton cas pour te proposer le meilleur luminaire. Il t\'envoie un mail sous 3 jours.', 'theme-sapi-maison'); ?></p>

            <form class="contact-form" data-lead-form novalidate>
              <input type="text" name="prenom" required maxlength="60" autocomplete="given-name"
                     placeholder="<?php esc_attr_e('Ton prénom', 'theme-sapi-maison'); ?>"
                     aria-label="<?php esc_attr_e('Ton prénom', 'theme-sapi-maison'); ?>">
              <input type="email" name="email" required autocomplete="email" inputmode="email"
                     placeholder="<?php esc_attr_e('Ton email', 'theme-sapi-maison'); ?>"
                     aria-label="<?php esc_attr_e('Ton email', 'theme-sapi-maison'); ?>">
              <textarea name="commentaire" rows="3" maxlength="1500"
                        placeholder="<?php esc_attr_e('Un petit mot pour Robin ? (facultatif)', 'theme-sapi-maison'); ?>"
                        aria-label="<?php esc_attr_e('Un petit mot pour Robin, facultatif', 'theme-sapi-maison'); ?>"></textarea>
              <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;">
              <button type="submit" data-lead-submit>
                <?php esc_html_e('Envoyer à Robin', 'theme-sapi-maison'); ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </button>
              <p class="contact-disclaimer"><?php esc_html_e('En envoyant ta demande, tu acceptes de recevoir occasionnellement des nouvelles de l\'Atelier Sâpi. Désinscription possible à tout moment.', 'theme-sapi-maison'); ?></p>
            </form>
          </div>
        </div>

        <footer class="modal__foot">
          <button type="button" class="footer-link" data-action="lead-exit">
            <?php esc_html_e('Non merci, voir toutes les créations', 'theme-sapi-maison'); ?>
          </button>
        </footer>
      </section>

      <!-- ═══ Landing réseaux — merci ═══════ -->
      <section class="modal__screen" data-screen="s-lead-merci" hidden>
        <header class="modal__head">
          <div class="conseiller-sig conseiller-sig--v1">
            <span class="conseiller-sig__avatar"><?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="conseiller-sig__text"><span class="conseiller-sig__who">Le conseil de Robin</span><span class="conseiller-sig__hook">À très vite</span></span>
          </div>
          <span class="badge">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php esc_html_e('Demande envoyée', 'theme-sapi-maison'); ?>
          </span>
        </header>
        <div class="modal__body">
          <div class="modal__body-content">
            <h2 class="h2" data-lead-merci-titre><?php esc_html_e('Merci', 'theme-sapi-maison'); ?></h2>
            <p class="subtitle"><?php esc_html_e('Robin regarde tes réponses et t\'écrit sous 3 jours avec le luminaire qu\'il te conseille.', 'theme-sapi-maison'); ?></p>
          </div>
        </div>
        <footer class="modal__foot">
          <button type="button" class="action-btn action-btn--secondary" data-action="lead-exit">
            <?php esc_html_e('En attendant, voir les créations', 'theme-sapi-maison'); ?>
          </button>
        </footer>
      </section>
  <?php
}

/* Étape 2 : brouillon, page admin « Demandes réseaux », envoi et cron. */
require_once __DIR__ . '/sapi-conseil-reseaux-envoi.php';
require_once __DIR__ . '/sapi-conseil-reseaux-email.php';
