<?php
/**
 * Conseil de Robin (réseaux) — étape 2 : brouillon, validation, envoi.
 * ─────────────────────────────────────────────────────────────────────────
 * Chargé par inc/sapi-conseil-reseaux.php.
 *
 * Pour chaque demande :
 *   1. Un brouillon est préparé (moteur du Conseiller pour le luminaire,
 *      IA pour le « En effet… »), juste après l'envoi du formulaire.
 *   2. Robin le relit dans Robin Conseiller › Demandes réseaux, le modifie,
 *      l'envoie, ou le laisse partir.
 *   3. Sans mot du visiteur (`mode = auto`) : le cron l'envoie seul 24 h
 *      après la demande. Avec un mot (`mode = manuel`) : rien ne part seul ;
 *      au bout de 48 h sans traitement, Robin reçoit un rappel (promesse
 *      faite à l'écran : un mail sous 3 jours).
 *
 * ⚠️ Un brouillon sans luminaire (aucun modèle trouvé, IA en panne) passe en
 * `manuel` : on n'envoie jamais tout seul un conseil vide ou bancal.
 * ⚠️ Un brouillon qui contient encore « [À compléter » ne part pas, même
 * sur clic : c'est la place laissée à Robin pour répondre au mot du visiteur.
 */

if (!defined('ABSPATH')) exit;

define('SAPI_CONSEIL_MARQUEUR', '[À compléter');

/* ─────────────────────────────────────────────────────────────────────────
   Accès aux lignes
   ───────────────────────────────────────────────────────────────────────── */
function sapi_conseil_get($id) {
  global $wpdb;
  return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . sapi_conseil_table() . ' WHERE id = %d', (int) $id));
}

function sapi_conseil_maj($id, array $data) {
  global $wpdb;
  return $wpdb->update(sapi_conseil_table(), $data, ['id' => (int) $id]);
}

/* ─────────────────────────────────────────────────────────────────────────
   1. Brouillon
   ───────────────────────────────────────────────────────────────────────── */

/** Luminaires du moteur du Conseiller pour ce projet, classés. */
function sapi_conseil_produits_pour(array $answers) {
  $guide = get_template_directory() . '/inc/guide-data.php';
  if (file_exists($guide)) require_once $guide;
  if (!function_exists('sapi_guide_get_categories') || !function_exists('sapi_guide_query_products')) {
    return [[], []];
  }
  $cats = sapi_guide_get_categories($answers);
  $res  = sapi_guide_query_products($answers, $cats);
  $produits = isset($res['products']) ? $res['products'] : [];
  if (function_exists('sapi_conseiller_rank_products')) {
    $produits = sapi_conseiller_rank_products($produits, $answers);
  }
  return [$produits, isset($res['fallback_notes']) ? $res['fallback_notes'] : []];
}

function sapi_conseil_lien_produit($p) {
  return add_query_arg([
    'utm_source'   => 'conseil-reseaux',
    'utm_medium'   => 'email',
    'utm_campaign' => 'conseil-robin',
  ], $p['permalink']);
}

function sapi_conseil_decrire_produit($p) {
  $essences = [];
  $tailles  = [];
  foreach ((array) ($p['variations'] ?? []) as $v) {
    if (!empty($v['essence']) && !in_array($v['essence'], $essences, true)) $essences[] = $v['essence'];
    if (!empty($v['taille'])  && !in_array($v['taille'],  $tailles,  true)) $tailles[]  = $v['taille'];
  }
  $prix = (!empty($p['price_min_raw'])) ? number_format((float) $p['price_min_raw'], 0, ',', ' ') . ' €' : '?';
  return '- ' . $p['title']
    . ' | Type : ' . ($p['category_label'] ?? '')
    . ' | Format : ' . ($p['format'] ?? '')
    . ' | Ampoule : ' . ($p['type_ampoule'] ?? '')
    . ' | Essences : ' . ($essences ? implode(', ', $essences) : '?')
    . ' | Tailles : ' . ($tailles ? implode(', ', $tailles) : '?')
    . ' | Prix dès : ' . $prix;
}

/** Le « En effet… » de Robin, écrit par l'IA. Chaîne vide si échec. */
function sapi_conseil_raison_ia(array $answers, array $labels, array $choisi, array $notes) {
  if (!function_exists('sapi_megafilter_call_claude')) return '';

  $system  = function_exists('sapi_megafilter_load_v2_prompts') ? sapi_megafilter_load_v2_prompts(false) : '';
  $system .= "Tu écris À LA PLACE DE ROBIN, artisan qui dessine et fabrique des luminaires en bois à la découpe laser dans son atelier près de Lyon. ";
  $system .= "Il répond par mail à une personne qui a rempli son questionnaire. La phrase qui précède ton texte est déjà écrite : « Je pense que le meilleur luminaire pour ta pièce, c'est [modèle]. »\n\n";
  $system .= "Écris la suite : 2 à 3 phrases qui commencent par « En effet », et qui expliquent concrètement pourquoi CE modèle convient à CE projet (taille, lumière, essence de bois, emplacement).\n\n";
  $system .= "RÈGLES :\n";
  $system .= "- Première personne (Robin parle : « je », « mon atelier »), tutoiement, simple et chaleureux, jamais vendeur\n";
  $system .= "- Appuie-toi UNIQUEMENT sur les réponses du projet et les caractéristiques du modèle fournies. N'invente rien (pas de hauteur sous plafond, de meuble ou de couleur de mur qui ne sont pas dans le projet)\n";
  $system .= "- Pas d'urgence, pas de rareté, pas de superlatif invérifiable (« le plus demandé », « idéal pour tous »)\n";
  $system .= "- Pas de prix, pas de code promo, pas de salutation ni de signature (ajoutées à part)\n";
  $system .= "- Pas d'emoji, pas de markdown, pas de tiret cadratin\n";
  $system .= "- Si une réponse du projet a dû être relâchée (voir NOTES), dis-le honnêtement en une demi-phrase\n";
  $system .= "- 400 caractères maximum\n\n";
  $system .= "FORMAT : JSON strict, premier caractère `{`, dernier `}` : { \"raison\": \"En effet, ...\" }\n";

  $user  = "PROJET :\n" . sapi_megafilter_format_project_text($answers, $labels) . "\n\n";
  $user .= "MODÈLE CHOISI :\n" . sapi_conseil_decrire_produit($choisi) . "\n";
  if ($notes) $user .= "\nNOTES DU MOTEUR :\n- " . implode("\n- ", $notes) . "\n";

  $txt = sapi_megafilter_call_claude('claude-sonnet-4-6', $system, [['role' => 'user', 'content' => $user]], 512);
  if (!$txt) return '';
  $parsed = sapi_megafilter_parse_json($txt);
  if (!$parsed || empty($parsed['raison']) || !is_string($parsed['raison'])) return '';
  $raison = sanitize_textarea_field($parsed['raison']);
  if (function_exists('sapi_strip_long_dashes')) $raison = sapi_strip_long_dashes($raison);
  if (function_exists('sapi_borner_conseil')) $raison = sapi_borner_conseil($raison, 450);
  return trim($raison);
}

/**
 * Prépare (ou refait) le brouillon d'une demande. Ne touche pas aux
 * demandes déjà envoyées, traitées ou annulées.
 */
function sapi_conseil_preparer_brouillon($id) {
  $row = sapi_conseil_get($id);
  if (!$row || $row->statut !== 'a_traiter' || $row->email === '') return false;

  $answers = json_decode((string) $row->answers, true) ?: [];
  $labels  = json_decode((string) $row->labels, true) ?: [];
  $prenom  = $row->prenom;
  $piece   = $answers['piece'] ?? '';
  $ta_piece = function_exists('sapi_piece_possessive') ? sapi_piece_possessive($piece) : 'ta pièce';

  list($produits, $notes) = $piece !== '' ? sapi_conseil_produits_pour($answers) : [[], []];
  $choisi = $produits[0] ?? null;
  $raison = $choisi ? sapi_conseil_raison_ia($answers, $labels, $choisi, $notes) : '';

  $lignes = ['Bonjour ' . $prenom . ','];

  if ($choisi && $raison !== '') {
    $lignes[] = 'Je pense que le meilleur luminaire pour ' . $ta_piece . ', c\'est ' . $choisi['title'] . '. ' . $raison;
  } elseif ($choisi) {
    // IA indisponible : le modèle est là, l'explication est à écrire.
    $lignes[] = 'Je pense que le meilleur luminaire pour ' . $ta_piece . ', c\'est ' . $choisi['title'] . '. ' . SAPI_CONSEIL_MARQUEUR . ' : pourquoi ce modèle]';
  } else {
    $lignes[] = SAPI_CONSEIL_MARQUEUR . ' : le moteur n\'a trouvé aucun modèle pour ce projet, à rédiger]';
  }

  if (trim((string) $row->commentaire) !== '') {
    $lignes[] = SAPI_CONSEIL_MARQUEUR . ' : ta réponse à son mot « ' . trim($row->commentaire) . ' »]';
  }

  /* Le texte ne contient QUE le message de Robin. La photo, le bouton, les
     alternatives, le code et la signature sont posés par le gabarit
     (sapi-conseil-reseaux-email.php) : Robin n'a que ses mots à relire. */
  $autres = $choisi ? array_slice($produits, 1, 2) : [];

  $sujet = $prenom . ', mon conseil pour ' . $ta_piece;

  $data = [
    'produit_id'   => $choisi ? (int) $choisi['id'] : 0,
    'alternatives' => implode(',', array_map(function ($p) { return (int) $p['id']; }, $autres)),
    'sujet'        => mb_substr($sujet, 0, 200),
    'corps'        => implode("\n\n", $lignes),
    'brouillon_le' => current_time('mysql'),
  ];
  /* Part seul seulement si tout est là ET que le visiteur n'a pas laissé de
     mot. Recalculé à chaque brouillon : un « Refaire » qui réussit après une
     panne de l'IA remet la demande en automatique. */
  $auto = $choisi && $raison !== '' && trim((string) $row->commentaire) === '';
  $data['mode'] = $auto ? 'auto' : 'manuel';
  /* Jamais une échéance déjà passée : un « Refaire » au-delà des 24 h
     partirait sinon au cron suivant, avant que Robin l'ait relu. On laisse
     au moins 2 h. */
  if ($auto) {
    $prevu = max(strtotime($row->created_at) + DAY_IN_SECONDS, current_time('timestamp') + 2 * HOUR_IN_SECONDS);
    $data['envoi_prevu'] = date('Y-m-d H:i:s', $prevu);
  }

  sapi_conseil_maj($id, $data);
  return true;
}
/* L'événement ne prépare QUE si rien n'existe encore : passé en retard (peu
   de visites), il écraserait sinon un brouillon déjà corrigé par Robin. */
add_action('sapi_conseil_preparer_event', function ($id) {
  $row = sapi_conseil_get($id);
  if ($row && !$row->brouillon_le) sapi_conseil_preparer_brouillon($id);
});

/* ─────────────────────────────────────────────────────────────────────────
   2. Envoi
   ───────────────────────────────────────────────────────────────────────── */

/* Ancien rendu « texte simple », remplacé le 23/11 par le gabarit
   newsletter (sapi_conseil_email_html). Gardé pour mémoire, plus appelé. */
function sapi_conseil_corps_html_ancien($corps, $produit_id) {
  $photo = '';
  if ($produit_id) {
    $img = get_the_post_thumbnail_url($produit_id, 'large');
    if ($img) {
      $lien  = sapi_conseil_lien_produit(['permalink' => get_permalink($produit_id)]);
      $photo = '<p><a href="' . esc_url($lien) . '"><img src="' . esc_url($img) . '" alt="' . esc_attr(get_the_title($produit_id)) . '" width="520" style="max-width:100%;height:auto;border-radius:8px;display:block;"></a></p>';
    }
  }
  $html = '';
  foreach (preg_split("/\n{2,}/", str_replace("\r", '', (string) $corps)) as $para) {
    $para = trim($para);
    if ($para === '') continue;
    if ($para === '[photo]') { $html .= $photo; continue; }
    $html .= '<p>' . nl2br(make_clickable(esc_html($para))) . '</p>';
  }
  return '<div style="font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#2b2b2b;max-width:560px;">' . $html . '</div>';
}

/**
 * Envoie le brouillon d'une demande. Renvoie true ou un message d'erreur.
 * Verrou : la ligne passe de 'a_traiter' à 'envoi' en une requête ; un
 * second appelant (cron + clic simultanés) trouve 0 ligne et s'arrête.
 */
function sapi_conseil_envoyer($id) {
  $row = sapi_conseil_get($id);
  if (!$row) return 'Demande introuvable.';
  if ($row->statut !== 'a_traiter') return 'Cette demande n\'est plus à traiter.';
  if ($row->email === '') return 'Email effacé (demande purgée).';
  if (trim((string) $row->corps) === '') return 'Le brouillon est vide.';
  if (strpos($row->corps, SAPI_CONSEIL_MARQUEUR) !== false) return 'Il reste un « ' . SAPI_CONSEIL_MARQUEUR . ' » dans le texte.';

  global $wpdb;
  $verrou = $wpdb->query($wpdb->prepare(
    'UPDATE ' . sapi_conseil_table() . " SET statut = 'envoi', verrou_le = %s WHERE id = %d AND statut = 'a_traiter'",
    current_time('mysql'), (int) $id
  ));
  if ($verrou !== 1) return 'Envoi déjà en cours.';
  /* Relu APRÈS le verrou : un « Enregistrer » arrivé entre-temps doit partir,
     pas le texte lu au début. */
  $row = sapi_conseil_get($id);
  if (strpos((string) $row->corps, SAPI_CONSEIL_MARQUEUR) !== false || trim((string) $row->corps) === '') {
    sapi_conseil_maj($id, ['statut' => 'a_traiter']);
    return 'Il reste un « ' . SAPI_CONSEIL_MARQUEUR . ' » dans le texte.';
  }

  $ok = wp_mail(
    $row->email,
    $row->sujet !== '' ? $row->sujet : 'Mon conseil pour ton luminaire',
    sapi_conseil_email_html($row),
    [
      'Content-Type: text/html; charset=UTF-8',
      'From: Robin - Atelier Sâpi <robin@atelier-sapi.fr>',
      'Reply-To: robin@atelier-sapi.fr',
    ]
  );

  if (!$ok) {
    sapi_conseil_maj($id, ['statut' => 'a_traiter']);
    error_log('[sapi-conseil] wp_mail visiteur échoué, demande ' . (int) $id);
    return 'L\'envoi a échoué (voir WP Mail SMTP › Journal).';
  }
  sapi_conseil_maj($id, ['statut' => 'envoye', 'envoye_le' => current_time('mysql')]);
  return true;
}

/* ─────────────────────────────────────────────────────────────────────────
   3. Cron horaire : brouillons manquants, envois à 24 h, rappels à 48 h
   ───────────────────────────────────────────────────────────────────────── */
add_action('init', function () {
  if (!wp_next_scheduled('sapi_conseil_cron')) {
    wp_schedule_event(time() + 300, 'hourly', 'sapi_conseil_cron');
  }
});

add_action('sapi_conseil_cron', function () {
  global $wpdb;
  $t   = sapi_conseil_table();
  $now = current_time('mysql');

  // Brouillons qui n'ont pas été préparés (événement manqué, IA en panne).
  $ids = $wpdb->get_col("SELECT id FROM $t WHERE statut = 'a_traiter' AND brouillon_le IS NULL AND email <> '' ORDER BY id LIMIT 3");
  foreach ($ids as $id) sapi_conseil_preparer_brouillon((int) $id);

  /* Envoi interrompu (PHP coupé entre le verrou et la fin) : on ne sait pas
     si le mail est parti. On ne renvoie pas à l'aveugle : la demande repasse
     en manuel, et le rappel ci-dessous la signale à Robin. */
  $wpdb->query($wpdb->prepare(
    "UPDATE $t SET statut = 'a_traiter', mode = 'manuel' WHERE statut = 'envoi' AND verrou_le <= %s",
    date('Y-m-d H:i:s', current_time('timestamp') - 15 * MINUTE_IN_SECONDS)
  ));

  /* Envois automatiques arrivés à échéance. Le brouillon doit exister depuis
     au moins 1 h : un brouillon préparé à l'instant (dans ce même passage)
     ne part pas sans que Robin ait eu une chance de le voir. */
  $ids = $wpdb->get_col($wpdb->prepare(
    "SELECT id FROM $t WHERE statut = 'a_traiter' AND mode = 'auto' AND brouillon_le IS NOT NULL
       AND brouillon_le <= %s AND envoi_prevu IS NOT NULL AND envoi_prevu <= %s ORDER BY id LIMIT 10",
    date('Y-m-d H:i:s', current_time('timestamp') - HOUR_IN_SECONDS), $now
  ));
  foreach ($ids as $id) {
    $r = sapi_conseil_envoyer((int) $id);
    if ($r !== true) error_log('[sapi-conseil] envoi auto ' . (int) $id . ' : ' . $r);
  }

  /* Rappel à Robin : toute demande pas traitée après 48 h, QUEL QUE SOIT le
     mode. Une demande auto ne devrait jamais en arriver là ; si elle y est,
     c'est qu'elle est bloquée (texte à compléter, envoi en échec) et le
     visiteur attend toujours. */
  $limite = date('Y-m-d H:i:s', current_time('timestamp') - 2 * DAY_IN_SECONDS);
  $rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, prenom, piece, created_at FROM $t WHERE statut = 'a_traiter'
       AND relance_le IS NULL AND created_at <= %s ORDER BY id",
    $limite
  ));
  if ($rows) {
    $body = "Ces demandes attendent ta réponse depuis plus de 48 h (promis à l'écran : sous 3 jours).\n\n";
    foreach ($rows as $r) {
      $body .= '- ' . $r->prenom . ($r->piece ? ' (' . $r->piece . ')' : '') . ', le ' . $r->created_at . ' : '
        . admin_url('admin.php?page=sapi-conseil-reseaux&demande=' . (int) $r->id) . "\n";
    }
    $body .= "\nSi tu as déjà répondu depuis ta boîte mail, clique « Marquer comme répondu » pour ne plus recevoir ce rappel.\n";
    if (wp_mail('robin@atelier-sapi.fr', '[Conseil réseaux] ' . count($rows) . ' demande(s) à traiter', $body)) {
      foreach ($rows as $r) sapi_conseil_maj((int) $r->id, ['relance_le' => $now]);
    }
  }
});

/* ─────────────────────────────────────────────────────────────────────────
   4. Admin : Robin Conseiller › Demandes réseaux
   ───────────────────────────────────────────────────────────────────────── */
add_action('admin_menu', function () {
  add_submenu_page(
    'sapi-conseiller-sessions',
    'Demandes réseaux',
    'Demandes réseaux',
    'manage_woocommerce',
    'sapi-conseil-reseaux',
    'sapi_conseil_admin_page'
  );
}, 11);

function sapi_conseil_statut_label($row) {
  switch ($row->statut) {
    case 'envoye':  return 'Envoyé le ' . $row->envoye_le;
    case 'repondu': return 'Répondu depuis ta boîte';
    case 'annule':  return 'Annulé';
    case 'envoi':   return 'Envoi en cours…';
  }
  if (!$row->brouillon_le) return 'Brouillon en préparation';
  return $row->mode === 'auto' ? 'Part seul le ' . $row->envoi_prevu : 'À toi de répondre';
}

function sapi_conseil_admin_page() {
  if (!current_user_can('manage_woocommerce')) return;
  global $wpdb;
  $t  = sapi_conseil_table();
  $id = isset($_GET['demande']) ? (int) $_GET['demande'] : 0;

  echo '<div class="wrap"><h1>Demandes réseaux</h1>';
  if (!empty($_GET['msg'])) {
    $ok = isset($_GET['ok']) && $_GET['ok'] === '1';
    echo '<div class="notice ' . ($ok ? 'notice-success' : 'notice-error') . ' is-dismissible"><p>' . esc_html(wp_unslash($_GET['msg'])) . '</p></div>';
  }

  if (!$id) {
    $rows = $wpdb->get_results("SELECT * FROM $t ORDER BY id DESC LIMIT 200");
    echo '<table class="widefat striped"><thead><tr><th>Date</th><th>Prénom</th><th>Pièce</th><th>Mot</th><th>Statut</th><th></th></tr></thead><tbody>';
    if (!$rows) echo '<tr><td colspan="6">Aucune demande pour l\'instant.</td></tr>';
    foreach ($rows as $r) {
      $url = admin_url('admin.php?page=sapi-conseil-reseaux&demande=' . (int) $r->id);
      echo '<tr><td>' . esc_html($r->created_at) . '</td><td>' . esc_html($r->prenom ?: '(effacé)') . '</td><td>' . esc_html($r->piece)
        . '</td><td>' . (trim((string) $r->commentaire) !== '' ? 'Oui' : '') . '</td><td>' . esc_html(sapi_conseil_statut_label($r))
        . '</td><td><a href="' . esc_url($url) . '">Ouvrir</a></td></tr>';
    }
    echo '</tbody></table></div>';
    return;
  }

  $row = sapi_conseil_get($id);
  if (!$row) { echo '<p>Demande introuvable.</p></div>'; return; }

  /* Brouillon pas encore là (événement pas encore passé) : on le prépare
     maintenant, Robin n'a pas à attendre le cron. */
  if ($row->statut === 'a_traiter' && !$row->brouillon_le) {
    sapi_conseil_preparer_brouillon($id);
    $row = sapi_conseil_get($id);
  }

  $answers = json_decode((string) $row->answers, true) ?: [];
  $labels  = json_decode((string) $row->labels, true) ?: [];
  $editable = ($row->statut === 'a_traiter');

  echo '<p><a href="' . esc_url(admin_url('admin.php?page=sapi-conseil-reseaux')) . '">← Toutes les demandes</a></p>';
  echo '<h2>' . esc_html($row->prenom) . ' &lt;' . esc_html($row->email) . '&gt;</h2>';
  echo '<p><strong>Statut :</strong> ' . esc_html(sapi_conseil_statut_label($row)) . ' · demandé le ' . esc_html($row->created_at) . '</p>';
  echo '<h3>Projet</h3><pre style="white-space:pre-wrap;background:#fff;padding:12px;border:1px solid #ddd;">'
    . esc_html($answers ? sapi_megafilter_format_project_text($answers, $labels) : '(aucune réponse)') . '</pre>';
  if (trim((string) $row->commentaire) !== '') {
    echo '<h3>Son mot</h3><blockquote style="background:#fff8e5;padding:12px;border-left:4px solid #E35B24;">' . nl2br(esc_html($row->commentaire)) . '</blockquote>';
  }

  $action = esc_url(admin_url('admin-post.php'));
  $nonce  = wp_nonce_field('sapi_conseil_' . $id, '_wpnonce', true, false);

  echo '<h3>Mail</h3>';
  echo '<form method="post" action="' . $action . '">' . $nonce;
  echo '<input type="hidden" name="action" value="sapi_conseil_admin"><input type="hidden" name="demande" value="' . (int) $id . '">';
  echo '<p><label>Objet<br><input type="text" name="sujet" class="large-text" value="' . esc_attr($row->sujet) . '"' . ($editable ? '' : ' readonly') . '></label></p>';
  echo '<p><label>Ton message (il apparaît dans le bloc « Le mot de Robin » ; le luminaire, les alternatives, le code et ta signature sont ajoutés autour. Remplace chaque « ' . esc_html(SAPI_CONSEIL_MARQUEUR) . ' … ] » avant d\'envoyer)<br>';
  echo '<textarea name="corps" rows="18" class="large-text" style="font-size:14px;line-height:1.5;"' . ($editable ? '' : ' readonly') . '>' . esc_textarea($row->corps) . '</textarea></label></p>';
  if ($editable) {
    echo '<p>';
    echo '<button class="button" name="op" value="enregistrer">Enregistrer</button> ';
    echo '<button class="button button-primary" name="op" value="envoyer" onclick="return confirm(\'Envoyer ce mail à ' . esc_js($row->email) . ' ?\');">Enregistrer et envoyer maintenant</button> ';
    echo '<button class="button" name="op" value="regenerer" onclick="return confirm(\'Remplacer le texte par un nouveau brouillon ?\');">Refaire le brouillon</button> ';
    echo '<button class="button" name="op" value="repondu">Marquer comme répondu</button> ';
    echo '<button class="button-link-delete" name="op" value="annuler" onclick="return confirm(\'Annuler : aucun mail ne partira.\');">Annuler</button>';
    echo '</p>';
  }
  echo '</form>';

  /* Aperçu du VRAI mail, dans un cadre isolé : les styles de l'admin ne le
     déforment pas, et ce qu'on voit est ce qui part. */
  echo '<h3>Aperçu du mail (après « Enregistrer »)</h3>';
  echo '<iframe title="Aperçu du mail" sandbox="" style="width:100%;max-width:640px;height:1500px;border:1px solid #ddd;background:#F5EDE4;" srcdoc="' . esc_attr(sapi_conseil_email_html($row)) . '"></iframe>';
  echo '</div>';
}

add_action('admin_post_sapi_conseil_admin', function () {
  if (!current_user_can('manage_woocommerce')) wp_die('Accès refusé');
  $id = isset($_POST['demande']) ? (int) $_POST['demande'] : 0;
  check_admin_referer('sapi_conseil_' . $id);
  $op  = isset($_POST['op']) ? sanitize_key($_POST['op']) : '';
  $row = sapi_conseil_get($id);
  $ok  = false;
  $msg = 'Rien fait.';

  if (!$row || $row->statut !== 'a_traiter') {
    $msg = 'Cette demande n\'est plus modifiable.';
  } else {
    if (in_array($op, ['enregistrer', 'envoyer'], true)) {
      sapi_conseil_maj($id, [
        'sujet' => mb_substr(sanitize_text_field(wp_unslash($_POST['sujet'] ?? '')), 0, 200),
        'corps' => sanitize_textarea_field(wp_unslash($_POST['corps'] ?? '')),
      ]);
      $ok = true; $msg = 'Brouillon enregistré.';
    }
    if ($op === 'envoyer') {
      $r = sapi_conseil_envoyer($id);
      $ok  = ($r === true);
      $msg = $ok ? 'Mail envoyé.' : $r;
    } elseif ($op === 'regenerer') {
      sapi_conseil_maj($id, ['brouillon_le' => null]);
      $ok  = sapi_conseil_preparer_brouillon($id);
      $msg = $ok ? 'Nouveau brouillon prêt. Relis-le : s\'il part seul, ce sera au plus tôt dans 2 h.' : 'Échec du brouillon.';
    } elseif ($op === 'repondu' || $op === 'annuler') {
      /* Conditionnel, comme le verrou d'envoi : si le cron a pris la demande
         entre-temps, on ne réécrit pas « envoyé » par « annulé ». */
      global $wpdb;
      $n = $wpdb->query($wpdb->prepare(
        'UPDATE ' . sapi_conseil_table() . " SET statut = %s WHERE id = %d AND statut = 'a_traiter'",
        $op === 'repondu' ? 'repondu' : 'annule', $id
      ));
      $ok  = ($n === 1);
      $msg = !$ok ? 'Trop tard : la demande vient d\'être envoyée.'
           : ($op === 'repondu' ? 'Marqué comme répondu.' : 'Demande annulée, aucun mail ne partira.');
    }
  }

  wp_safe_redirect(add_query_arg([
    'page'    => 'sapi-conseil-reseaux',
    'demande' => $id,
    'ok'      => $ok ? '1' : '0',
    'msg'     => rawurlencode($msg),
  ], admin_url('admin.php')));
  exit;
});
