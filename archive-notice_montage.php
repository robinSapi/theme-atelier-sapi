<?php
/**
 * Accueil du Centre d'assemblage — /assemblage/ (cible du QR code via /montage).
 * Une vignette par PRODUIT relié (pas par notice) : le client reconnaît son
 * luminaire, pas le nom d'une notice. Recherche filtrée côté navigateur.
 */
get_header();

if (!function_exists('get_field')) {
  echo '<main class="sapi-centre"><div class="sapi-notice__inner"><p>Centre d\'assemblage momentanément indisponible. Écrivez-moi : commande@atelier-sapi.fr</p></div></main>';
  get_footer();
  return;
}

$cards = [];
$seen  = [];
$notices = get_posts(['post_type' => 'notice_montage', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
foreach ($notices as $n) {
  foreach ((array) (get_field('produits', $n->ID) ?: []) as $pid) {
    $pid = (int) $pid;
    if (isset($seen[$pid]) || get_post_status($pid) !== 'publish') continue;
    $seen[$pid] = true;
    $cats = get_the_terms($pid, 'product_cat');
    $cards[] = [
      'name' => sapi_notice_title($pid),
      'cat'  => ($cats && !is_wp_error($cats)) ? $cats[0]->name : '',
      'img'  => get_post_thumbnail_id($pid),
      'url'  => ($spec = sapi_notice_specs_for_product($pid)) ? $spec['url'] : get_permalink($n->ID), // ?version=N de ce produit
    ];
  }
}
usort($cards, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
$from_qr = (($_GET['src'] ?? '') === 'qr');
?>
<main class="sapi-centre">
  <div class="sapi-notice__inner">
    <section class="sapi-centre__hero">
      <p class="sapi-script sapi-centre__merci">Merci pour votre confiance !</p>
      <h1 class="sapi-centre__title">Centre d'assemblage</h1>
      <p class="sapi-centre__intro"><?php echo $from_qr
        ? 'Vous venez de scanner le QR code de votre colis : choisissez votre luminaire pour trouver sa notice.'
        : 'Choisissez votre luminaire pour trouver sa notice de montage, en mode énigme ou guidé pas à pas.'; ?></p>
      <label class="sapi-centre__label" for="sapi-centre-q">Rechercher un luminaire</label>
      <input id="sapi-centre-q" class="sapi-centre__search" type="search" placeholder="Olivia, Mireille, Alice…" autocomplete="off">
    </section>

    <?php if ($cards) : ?>
    <ul class="sapi-centre__grid">
      <?php foreach ($cards as $c) : ?>
      <li data-search="<?php echo esc_attr(remove_accents(mb_strtolower($c['name'] . ' ' . $c['cat']))); ?>">
        <a class="sapi-centre__card" href="<?php echo esc_url($c['url']); ?>">
          <span class="sapi-centre__img"><?php if ($c['img']) echo wp_get_attachment_image($c['img'], 'woocommerce_thumbnail', false, ['alt' => '', 'loading' => 'lazy']); ?></span>
          <span class="sapi-centre__name"><?php echo sapi_notice_format_name($c['name']); ?></span>
          <?php if ($c['cat']) : ?><span class="sapi-centre__cat"><?php echo esc_html($c['cat']); ?></span><?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="sapi-centre__empty"<?php echo $cards ? ' hidden' : ''; ?>><?php echo $cards ? 'Aucun luminaire ne correspond.' : 'Les notices arrivent bientôt.'; ?> Écrivez-moi, je vous envoie la notice.</p>

    <section class="sapi-centre__infos">
      <div class="sapi-centre__info"><h2>Avant de commencer</h2><p>Coupez le courant au disjoncteur. Assemblez le luminaire au sol, sur une surface propre, avant de l'accrocher.</p></div>
      <div class="sapi-centre__info sapi-centre__info--dark"><h2>Une question ?</h2><p><a href="mailto:commande@atelier-sapi.fr">commande@atelier-sapi.fr</a><br>06 80 43 55 85</p></div>
    </section>
  </div>
</main>
<?php
get_footer();
