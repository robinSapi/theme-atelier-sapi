<?php
/**
 * Conseil de Robin (réseaux) — gabarit HTML du mail envoyé au visiteur.
 * ─────────────────────────────────────────────────────────────────────────
 * Mêmes codes que les newsletters (business/newsletters/template_base.html) :
 * fond #F5EDE4, carte 580px #FEFDFB, Square Peg pour les titres, Montserrat
 * pour le texte, orange #E35B24 pour le bouton, carte pointillée pour le code.
 *
 * Le mail est mis en page comme une lettre de Robin dans un écrin : le hero
 * annonce que Robin a choisi, puis « Le mot de Robin » reprend MOT POUR MOT
 * le texte que Robin relit et modifie dans l'admin (colonne `corps`). Tout le
 * reste (luminaire, alternatives, code) est construit ici à partir des
 * identifiants : Robin n'a que son message à relire.
 *
 * Mail transactionnel (réponse à une demande), donc pas de lien de
 * désinscription Brevo ; une ligne en pied rappelle pourquoi on le reçoit.
 */

if (!defined('ABSPATH')) exit;

/** Données d'affichage d'un luminaire, ou null s'il n'est plus en ligne. */
function sapi_conseil_email_produit($id) {
  $id = (int) $id;
  if (!$id || get_post_status($id) !== 'publish') return null;
  return [
    'titre' => get_the_title($id),
    'lien'  => sapi_conseil_lien_produit(['permalink' => get_permalink($id)]),
    'image' => get_the_post_thumbnail_url($id, 'large') ?: '',
    'vign'  => get_the_post_thumbnail_url($id, 'medium') ?: '',
  ];
}

/** Message de Robin (texte brut) → paragraphes HTML. */
function sapi_conseil_email_message($corps) {
  $html = '';
  foreach (preg_split("/\n{2,}/", str_replace("\r", '', (string) $corps)) as $para) {
    $para = trim($para);
    /* Anciens brouillons (avant ce gabarit) : la photo et les liens sont
       désormais construits par le gabarit, on ignore la ligne [photo]. */
    if ($para === '' || $para === '[photo]') continue;
    /* Brouillons préparés avant ce gabarit : leurs lignes de liens, de code
       et de signature sont maintenant affichées par le gabarit lui-même.
       On les saute pour ne pas les montrer deux fois, en URL brutes. */
    if (preg_match('/^(Sa fiche :|Si tu veux comparer,|Pour te remercier d\'avoir pris le temps|Si tu as la moindre question|Robin\s*\nAtelier Sâpi$)/u', $para)) continue;
    $html .= '<p style="margin:0 0 14px;font-family:\'Montserrat\',Arial,Helvetica,sans-serif;font-size:15px;color:#323232;line-height:26px;">'
      . nl2br(make_clickable(esc_html($para))) . '</p>';
  }
  return $html;
}

/**
 * Mail complet. `$row` : ligne de wp_sapi_conseil_demandes.
 */
function sapi_conseil_email_html($row) {
  $answers  = json_decode((string) $row->answers, true) ?: [];
  $piece    = $answers['piece'] ?? '';
  $ta_piece = function_exists('sapi_piece_possessive') ? sapi_piece_possessive($piece) : 'ta pièce';
  $labels   = json_decode((string) $row->labels, true) ?: [];
  $piece_label = $labels['piece'] ?? '';
  $produit  = sapi_conseil_email_produit($row->produit_id);
  $autres   = [];
  foreach (array_filter(array_map('intval', explode(',', (string) ($row->alternatives ?? '')))) as $aid) {
    $p = sapi_conseil_email_produit($aid);
    if ($p) $autres[] = $p;
  }
  $avatar = home_url('/wp-content/uploads/2026/03/Robin-face-avec-Alice-lhelice.jpg');
  $logo   = 'https://atelier-sapi.fr/wp-content/uploads/2024/12/logo_sapi.svg';
  $site   = home_url('/');
  $f      = "font-family:'Montserrat',Arial,Helvetica,sans-serif;";
  $sp     = "font-family:'Square Peg',Georgia,serif;";
  $sep    = '<tr><td align="center" style="padding:0 32px;"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:1px;background-color:#E8DCC4;font-size:0;line-height:0;">&nbsp;</td></tr></table></td></tr>';

  ob_start();
  ?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo esc_html($row->sujet); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&family=Square+Peg&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#F5EDE4;<?php echo $f; ?>-webkit-text-size-adjust:100%;">
  <div style="display:none;max-height:0;overflow:hidden;"><?php echo esc_html($produit ? 'Mon conseil pour ' . $ta_piece . ' : ' . $produit['titre'] . '.' : 'Mon conseil pour ' . $ta_piece . '.'); ?></div>
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5EDE4;">
    <tr>
      <td align="center" style="padding:16px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;background-color:#FEFDFB;">
          <tr><td style="height:8px;font-size:0;line-height:0;">&nbsp;</td></tr>

          <!-- Logo -->
          <tr>
            <td align="center" style="padding:28px 24px 20px;">
              <a href="<?php echo esc_url($site); ?>" target="_blank" style="text-decoration:none;">
                <img src="<?php echo esc_url($logo); ?>" alt="Atelier Sâpi" width="130" style="display:block;max-width:130px;height:auto;">
              </a>
            </td>
          </tr>

          <!-- Hero -->
          <tr>
            <td align="center" style="padding:20px 24px 6px;">
              <h1 style="margin:0;<?php echo $sp; ?>font-size:44px;font-weight:400;color:#937D68;line-height:50px;">Robin a trouvé ton luminaire</h1>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:0 24px 12px;">
              <p style="margin:0;<?php echo $f; ?>font-size:13px;font-weight:600;color:#937D68;text-transform:uppercase;letter-spacing:2px;">Ton projet · <?php echo esc_html($piece_label !== '' ? $piece_label : 'ta pièce'); ?></p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:8px 32px 28px;">
              <p style="margin:0;<?php echo $f; ?>font-size:15px;font-weight:300;color:#717171;line-height:24px;">Voici son message.</p>
            </td>
          </tr>

          <!-- Le mot de Robin -->
          <tr>
            <td style="background-color:#FAF6F0;padding:28px 28px 22px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:0 0 16px;">
                    <table cellpadding="0" cellspacing="0" border="0"><tr>
                      <td valign="middle" style="padding-right:12px;"><img src="<?php echo esc_url($avatar); ?>" alt="Robin" width="52" height="52" style="display:block;width:52px;height:52px;border-radius:26px;object-fit:cover;"></td>
                      <td valign="middle">
                        <p style="margin:0;<?php echo $f; ?>font-size:12px;font-weight:600;color:#937D68;text-transform:uppercase;letter-spacing:2px;">Le mot de Robin</p>
                        <p style="margin:2px 0 0;<?php echo $f; ?>font-size:12px;font-weight:300;color:#717171;">Artisan, Atelier Sâpi</p>
                      </td>
                    </tr></table>
                  </td>
                </tr>
                <tr>
                  <td style="border-left:3px solid #E8DCC4;padding:2px 0 2px 18px;">
                    <?php echo sapi_conseil_email_message($row->corps); // phpcs:ignore -- échappé dans la fonction ?>
                    <p style="margin:4px 0 0;<?php echo $sp; ?>font-size:34px;color:#937D68;line-height:34px;">Robin</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

<?php if ($produit) : ?>
          <!-- Le luminaire conseillé -->
          <tr>
            <td align="center" style="padding:32px 24px 8px;">
              <p style="margin:0 0 4px;<?php echo $f; ?>font-size:13px;font-weight:600;color:#937D68;text-transform:uppercase;letter-spacing:2px;">Le luminaire qu'il te conseille</p>
              <h2 style="margin:0;<?php echo $sp; ?>font-size:36px;font-weight:400;color:#937D68;line-height:40px;"><?php echo esc_html($produit['titre']); ?></h2>
            </td>
          </tr>
<?php if ($produit['image']) : ?>
          <tr>
            <td align="center" style="padding:12px 24px 8px;">
              <a href="<?php echo esc_url($produit['lien']); ?>" target="_blank"><img src="<?php echo esc_url($produit['image']); ?>" alt="<?php echo esc_attr($produit['titre']); ?>" width="532" style="display:block;width:100%;max-width:532px;height:auto;border-radius:12px;"></a>
            </td>
          </tr>
<?php endif; ?>
          <tr>
            <td align="center" style="padding:18px 24px 32px;">
              <!--[if !mso]><!-->
              <a href="<?php echo esc_url($produit['lien']); ?>" target="_blank" style="display:inline-block;background-color:#E35B24;color:#FFFFFF;padding:14px 32px;<?php echo $f; ?>font-size:15px;font-weight:700;border-radius:50px;text-decoration:none;box-shadow:0 3px 12px rgba(227,91,36,0.25);">Voir <?php echo esc_html($produit['titre']); ?></a>
              <!--<![endif]-->
              <!--[if mso]><a href="<?php echo esc_url($produit['lien']); ?>" style="color:#E35B24;font-weight:700;">Voir <?php echo esc_html($produit['titre']); ?></a><![endif]-->
            </td>
          </tr>
<?php endif; ?>

<?php if ($autres) : ?>
          <!-- Pour comparer -->
          <?php echo $sep; // phpcs:ignore ?>
          <tr>
            <td align="center" style="padding:24px 24px 14px;">
              <h2 style="margin:0;<?php echo $sp; ?>font-size:30px;font-weight:400;color:#937D68;line-height:34px;">Si tu veux comparer</h2>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:0 16px 28px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<?php foreach ($autres as $a) : ?>
                <td width="<?php echo count($autres) > 1 ? '50%' : '100%'; ?>" align="center" valign="top" style="padding:0 8px;">
                  <a href="<?php echo esc_url($a['lien']); ?>" target="_blank" style="text-decoration:none;">
<?php if ($a['vign']) : ?>
                    <img src="<?php echo esc_url($a['vign']); ?>" alt="<?php echo esc_attr($a['titre']); ?>" width="250" style="display:block;width:100%;max-width:250px;height:auto;border-radius:10px;margin:0 auto;">
<?php endif; ?>
                    <p style="margin:10px 0 0;<?php echo $sp; ?>font-size:26px;color:#937D68;line-height:28px;"><?php echo esc_html($a['titre']); ?></p>
                  </a>
                </td>
<?php endforeach; ?>
              </tr></table>
            </td>
          </tr>
<?php endif; ?>

          <!-- Code -->
          <tr>
            <td style="background-color:#FAF6F0;padding:28px 32px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:2px dashed #937D68;background-color:#FBF6EA;border-radius:12px;">
                <tr>
                  <td align="center" style="padding:20px 16px;">
                    <p style="margin:0;<?php echo $f; ?>font-size:12px;font-weight:600;color:#937D68;text-transform:uppercase;letter-spacing:2px;">Pour te remercier d'avoir répondu</p>
                    <p style="margin:8px 0 4px;<?php echo $f; ?>font-size:26px;font-weight:800;color:#E35B24;letter-spacing:3px;">BIENVENUE10</p>
                    <p style="margin:0;<?php echo $f; ?>font-size:15px;font-weight:700;color:#323232;">-10 % sur ta commande</p>
                    <p style="margin:6px 0 0;<?php echo $f; ?>font-size:12px;font-style:italic;color:#717171;">À saisir au moment de payer sur atelier-sapi.fr</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Pied -->
          <tr>
            <td style="background-color:#FEFDFB;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" style="padding:24px 24px 6px;">
                    <p style="margin:0;<?php echo $f; ?>font-size:13px;color:#717171;">Une question ? Réponds simplement à ce mail, c'est Robin qui le lira.</p>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:12px 24px 12px;">
                    <a href="https://www.instagram.com/atelier_sapi/" target="_blank" style="<?php echo $f; ?>font-size:11px;font-weight:600;color:#937D68;text-decoration:none;text-transform:uppercase;letter-spacing:1px;">Instagram</a>
                    <span style="color:#E8DCC4;">&nbsp;&nbsp;&#8226;&nbsp;&nbsp;</span>
                    <a href="https://www.facebook.com/ateliersapi" target="_blank" style="<?php echo $f; ?>font-size:11px;font-weight:600;color:#937D68;text-decoration:none;text-transform:uppercase;letter-spacing:1px;">Facebook</a>
                    <span style="color:#E8DCC4;">&nbsp;&nbsp;&#8226;&nbsp;&nbsp;</span>
                    <a href="https://www.pinterest.fr/ateliersapi/" target="_blank" style="<?php echo $f; ?>font-size:11px;font-weight:600;color:#937D68;text-decoration:none;text-transform:uppercase;letter-spacing:1px;">Pinterest</a>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:0 24px 8px;">
                    <p style="margin:0;<?php echo $f; ?>font-size:12px;font-weight:300;color:#717171;">Assemblez, Éclairez, Admirez ! · Luminaires artisanaux en bois · Lyon</p>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:10px 32px 24px;">
                    <p style="margin:0;<?php echo $f; ?>font-size:10px;color:#bbbbbb;line-height:15px;">Tu reçois ce mail parce que tu as demandé conseil à Robin sur atelier-sapi.fr. © <?php echo esc_html(date('Y')); ?> Atelier Sâpi</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
<?php
  return ob_get_clean();
}
