<?php
/**
 * Mise en forme des mails que le site envoie à Robin (24/11).
 *
 * Les notifications (Conseil réseaux, contact du Conseiller, sur-mesure,
 * formulaire de contact, « Mon projet », rappel à 48 h…) étaient en texte
 * brut. Plutôt que de réécrire chacune, un seul filtre les habille au
 * moment de l'envoi : même gabarit pour toutes, et toute future
 * notification en texte brut en profite sans rien faire.
 *
 * Ce que le filtre comprend dans le texte :
 *   « ── Titre ── »            → intertitre de section
 *   « Libellé : valeur »       → ligne libellé en gras / valeur
 *   « Libellé : » seul         → sous-titre, la suite en paragraphe
 *   « - élément »              → liste
 *   les adresses https://…     → liens cliquables
 * Tout le reste reste du texte, échappé.
 *
 * ⚠️ NE TOUCHE QU'AUX MAILS EN TEXTE BRUT ADRESSÉS À ROBIN. Un mail déjà en
 * HTML (WooCommerce, mails aux clients) ou envoyé à quelqu'un d'autre passe
 * tel quel.
 *
 * @package Theme_Sapi_Maison
 */

defined('ABSPATH') || exit;

function sapi_mails_robin_destinataires() {
  return ['robin@atelier-sapi.fr', 'contact@atelier-sapi.fr'];
}

add_filter('wp_mail', function ($args) {
  if (!is_array($args) || empty($args['message']) || !is_string($args['message'])) return $args;

  // 1. Destinataire : Robin uniquement.
  $to = is_array($args['to']) ? $args['to'] : explode(',', (string) $args['to']);
  $pour_robin = false;
  foreach ($to as $adresse) {
    if (preg_match('/<([^>]+)>/', $adresse, $m)) $adresse = $m[1];
    if (in_array(strtolower(trim($adresse)), sapi_mails_robin_destinataires(), true)) { $pour_robin = true; break; }
  }
  if (!$pour_robin) return $args;

  // 2. Texte brut seulement.
  $entetes = $args['headers'] ?? [];
  if (!is_array($entetes)) $entetes = $entetes === '' ? [] : preg_split("/\r\n|\n/", (string) $entetes);
  $reply_to = '';
  /* `X-Sapi-Origine` : chaque formulaire dit d'où il part, en clair. Lu ici,
     affiché en tête du mail, puis retiré (il n'a rien à faire dans l'envoi). */
  $origine = '';
  foreach ($entetes as $i => $e) {
    if (stripos($e, 'x-sapi-origine:') === 0) { $origine = trim(substr($e, 15)); unset($entetes[$i]); }
  }
  $entetes = array_values($entetes);
  $args['headers'] = $entetes;
  foreach ($entetes as $e) {
    if (stripos($e, 'content-type:') === 0 && stripos($e, 'text/plain') === false) return $args;
    if (stripos($e, 'reply-to:') === 0) {
      $v = trim(substr($e, 9));
      $reply_to = preg_match('/<([^>]+)>/', $v, $m) ? $m[1] : $v;
    }
  }
  /* Déjà en HTML : une balise fermante suffit à le reconnaître (une adresse
     « <p.dupont@…> » n'en a pas). Et un type imposé par un autre filtre. */
  if (preg_match('~</[a-z][a-z0-9]*\s*>~i', $args['message'])) return $args;
  if (!preg_grep('/^content-type:/i', $entetes) && apply_filters('wp_mail_content_type', 'text/plain') !== 'text/plain') return $args;

  $html = sapi_mails_robin_html((string) $args['message'], (string) ($args['subject'] ?? ''), sanitize_email($reply_to), $origine);

  $entetes = array_values(array_filter($entetes, function ($e) { return stripos($e, 'content-type:') !== 0; }));
  $entetes[] = 'Content-Type: text/html; charset=UTF-8';
  $args['headers'] = $entetes;
  $args['message'] = $html;
  return $args;
}, 20);

/** Texte brut → corps HTML (blocs). */
function sapi_mails_robin_corps($texte) {
  /* Certains corps sont déjà passés par esc_html() : on décode d'abord pour
     ne pas afficher « l&#039;atelier ». On ré-échappe tout ensuite. */
  $texte  = html_entity_decode(str_replace("\r", '', $texte), ENT_QUOTES, 'UTF-8');
  $lignes = explode("\n", $texte);
  $f      = "font-family:'Montserrat',Arial,Helvetica,sans-serif;";
  $out    = '';
  $liste  = false;

  $lien = function ($s) {
    $s = esc_html($s);
    return preg_replace('~(https?://[^\s<]+?)([).,;!?]*)(?=\s|$)~', '<a href="$1" style="color:#C94D1E;word-break:break-all;">$1</a>$2', $s);
  };
  $fermer_liste = function () use (&$liste, &$out) {
    if ($liste) { $out .= '</ul>'; $liste = false; }
  };

  foreach ($lignes as $ligne) {
    $l = rtrim($ligne);
    if (trim($l) === '' || preg_match('/^[=\-─]{3,}$/u', trim($l))) { $fermer_liste(); continue; }

    if (preg_match('/^─+\s*(.+?)\s*─+$/u', trim($l), $m)) {
      $fermer_liste();
      $out .= '<p style="margin:22px 0 6px;' . $f . 'font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#937D68;">' . esc_html($m[1]) . '</p>';
      continue;
    }
    if (preg_match('/^\s*[-•]\s+(.*)$/u', $l, $m)) {
      if (!$liste) { $out .= '<ul style="margin:4px 0 8px;padding-left:20px;' . $f . 'font-size:14px;line-height:1.6;color:#4A3F35;">'; $liste = true; }
      $out .= '<li>' . $lien($m[1]) . '</li>';
      continue;
    }
    $fermer_liste();
    /* Un libellé, pas une phrase : 4 mots au plus avant les deux-points. */
    if (preg_match('/^([\p{Lu}][\p{L}°\'’ ()\/-]{0,40}?)\s?:\s*(.*)$/u', trim($l), $m) && stripos($m[1], 'http') !== 0
        && count(preg_split('/\s+/', trim($m[1]))) <= 4) {
      if ($m[2] === '') {
        $out .= '<p style="margin:16px 0 4px;' . $f . 'font-size:13px;font-weight:700;color:#4A3F35;">' . esc_html($m[1]) . '</p>';
      } else {
        $out .= '<p style="margin:3px 0;' . $f . 'font-size:14px;line-height:1.55;color:#4A3F35;"><strong style="color:#937D68;font-weight:600;">' . esc_html($m[1]) . ' :</strong> ' . $lien($m[2]) . '</p>';
      }
      continue;
    }
    $out .= '<p style="margin:3px 0;' . $f . 'font-size:14px;line-height:1.6;color:#4A3F35;">' . $lien($l) . '</p>';
  }
  $fermer_liste();
  return $out;
}

function sapi_mails_robin_html($texte, $sujet, $reply_to, $origine = '') {
  $f     = "font-family:'Montserrat',Arial,Helvetica,sans-serif;";
  $logo  = 'https://atelier-sapi.fr/wp-content/uploads/2024/12/logo_sapi.png';
  $badge = '';
  $titre = $sujet;
  if (preg_match('/^\[([^\]]+)\]\s*(.*)$/u', $sujet, $m)) { $badge = $m[1]; $titre = $m[2]; }

  $bouton = '';
  if ($reply_to && is_email($reply_to)) {
    $bouton = '<tr><td align="center" style="padding:6px 32px 28px;"><a href="mailto:' . esc_attr($reply_to) . '" style="display:inline-block;background-color:#E35B24;color:#ffffff;text-decoration:none;'
      . $f . 'font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:13px 26px;border-radius:40px;">Répondre à ' . esc_html($reply_to) . '</a></td></tr>';
  }

  return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . esc_html($sujet) . '</title></head>'
    . '<body style="margin:0;padding:0;background-color:#F5EDE4;' . $f . '">'
    . '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5EDE4;"><tr><td align="center" style="padding:16px 8px;">'
    . '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;background-color:#FEFDFB;border-radius:12px;">'
    . '<tr><td align="center" style="padding:24px 24px 8px;"><img src="' . esc_url($logo) . '" alt="Atelier Sâpi" width="70" style="display:block;width:70px;height:auto;"></td></tr>'
    . ($badge !== '' ? '<tr><td align="center" style="padding:6px 24px 0;"><span style="display:inline-block;background-color:#F3EADC;color:#937D68;' . $f . 'font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:5px 12px;border-radius:20px;">' . esc_html($badge) . '</span></td></tr>' : '')
    . '<tr><td align="center" style="padding:12px 32px 4px;' . $f . 'font-size:20px;font-weight:700;line-height:1.3;color:#4A3F35;">' . esc_html($titre) . '</td></tr>'
    . ($origine !== '' ? '<tr><td align="center" style="padding:10px 32px 4px;"><table cellpadding="0" cellspacing="0" border="0" style="background-color:#FBF3E6;border:1px solid #EAD9BE;border-radius:10px;"><tr><td style="padding:10px 16px;' . $f . 'font-size:13px;line-height:1.5;color:#4A3F35;text-align:center;"><span style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#937D68;">Envoyé depuis</span><br><strong>' . esc_html($origine) . '</strong></td></tr></table></td></tr>' : '')
    . '<tr><td style="padding:8px 32px 20px;">' . sapi_mails_robin_corps($texte) . '</td></tr>'
    . $bouton
    . '</table>'
    . '<p style="margin:12px 0 0;' . $f . 'font-size:11px;color:#A8957F;">Notification du site atelier-sapi.fr</p>'
    . '</td></tr></table></body></html>';
}
