<?php
/**
 * Page de montage d'une notice — Centre d'assemblage.
 * 3 étapes empilées (électricité, assemblage, abat-jour) : celle en cours est
 * ouverte, « Valider » la coche et ouvre la suivante, on peut tout rouvrir.
 * Sans JS, les 3 étapes restent dépliées (mode énigme, 1re version).
 */
get_header();

if (!function_exists('get_field')) {
  echo '<main class="sapi-notice"><div class="sapi-notice__inner"><p>Notice momentanément indisponible. Écrivez-moi : commande@atelier-sapi.fr</p></div></main>';
  get_footer();
  return;
}

while (have_posts()) : the_post();
  $nid      = get_the_ID();
  $produits = (array) (get_field('produits', $nid) ?: []);
  $versions = (array) (get_field('versions', $nid) ?: []);
  $asm      = sapi_notice_assemblages($nid);
  // Une seule énigme si aucune version n'a d'assemblage propre ; sinon une par version.
  $par_version = count($asm) > 1 && in_array(true, wp_list_pluck($asm, 'propre'), true);
  $blocs    = $par_version ? $asm : [0 => $asm[0]];
  $a_indice = false;
  foreach ($blocs as $b) foreach ($b['etapes'] as $e) if (trim((string) ($e['indice'] ?? '')) !== '') $a_indice = true;
  $meta_line = sapi_notice_meta_line($nid);
  $aj_texte = get_field('abatjour_texte', $nid);
  $aj_image = (int) get_field('abatjour_image', $nid);
  $first_thumb = $produits ? get_post_thumbnail_id($produits[0]) : 0;
?>
<main class="sapi-notice" data-notice="<?php echo (int) $nid; ?>">
  <div class="sapi-notice__inner">

    <p class="sapi-notice__back"><a href="<?php echo esc_url(get_post_type_archive_link('notice_montage')); ?>">← Centre d'assemblage</a></p>

    <header class="sapi-notice__head">
      <div>
        <p class="sapi-notice__kicker">Notice de montage</p>
        <h1 class="sapi-notice__title"><?php echo sapi_notice_format_name(get_the_title()); ?></h1>
        <?php if ($meta_line) : ?><p class="sapi-notice__meta"><?php echo esc_html($meta_line); ?></p><?php endif; ?>
      </div>

      <?php if (count($versions) > 1) : ?>
      <div class="sapi-notice__versions">
        <p class="sapi-notice__label" id="sapi-notice-versions-label">Votre version</p>
        <div class="sapi-notice__pills" role="group" aria-labelledby="sapi-notice-versions-label">
          <?php foreach ($versions as $i => $v) :
            $vign = (int) ($v['vignette'] ?? 0) ?: $first_thumb; ?>
            <button type="button" class="sapi-notice__pill<?php echo $i === 0 ? ' is-active' : ''; ?>" data-version="<?php echo (int) $i; ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>">
              <?php if ($vign) echo wp_get_attachment_image($vign, 'thumbnail', false, ['alt' => '', 'loading' => 'lazy']); ?>
              <span><?php echo esc_html($v['label']); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </header>

    <ol class="sapi-notice__phases">

      <?php /* ── 1 · Électricité ── */ ?>
      <li class="sapi-phase is-open" data-phase="1">
        <button type="button" class="sapi-phase__head" aria-expanded="true" aria-controls="sapi-phase-1">
          <span class="sapi-phase__badge">1</span>
          <span class="sapi-phase__titles">
            <span class="sapi-phase__title">Installer le système électrique</span>
            <span class="sapi-phase__sub" data-version-lieu><?php echo esc_html(sapi_notice_lieu_label($versions[0]['lieu'] ?? '')); ?></span>
          </span>
          <span class="sapi-phase__action"></span>
        </button>
        <div class="sapi-phase__panel" id="sapi-phase-1">
          <?php foreach ($versions as $i => $v) : ?>
          <div class="sapi-phase__split" data-version-panel="<?php echo (int) $i; ?>" data-lieu="<?php echo esc_attr(sapi_notice_lieu_label($v['lieu'] ?? '')); ?>"<?php echo $i ? ' hidden' : ''; ?>>
            <?php if (!empty($v['image'])) : ?>
              <div class="sapi-phase__visual"><?php echo wp_get_attachment_image((int) $v['image'], 'medium_large', false, ['loading' => 'lazy']); ?></div>
            <?php endif; ?>
            <div class="sapi-phase__text">
              <?php if (!empty($v['coupure'])) : ?>
                <p class="sapi-phase__warn"><strong>Coupez le courant au disjoncteur</strong> avant de toucher aux fils.</p>
              <?php endif; ?>
              <p><?php echo wp_kses($v['texte'] ?? '', ['br' => []]); ?></p>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="sapi-phase__foot"><button type="button" class="sapi-btn-cta" data-validate>C'est installé</button></div>
        </div>
      </li>

      <?php /* ── 2 · Assemblage ── */ ?>
      <li class="sapi-phase" data-phase="2">
        <button type="button" class="sapi-phase__head" aria-expanded="false" aria-controls="sapi-phase-2">
          <span class="sapi-phase__badge">2</span>
          <span class="sapi-phase__titles">
            <span class="sapi-phase__title">Assembler le luminaire</span>
            <span class="sapi-phase__sub">En mode énigme ou guidé</span>
          </span>
          <span class="sapi-phase__action"></span>
        </button>
        <div class="sapi-phase__panel" id="sapi-phase-2">
          <div class="sapi-tabs" role="tablist" aria-label="Mode d'assemblage">
            <button type="button" role="tab" id="sapi-tab-enigme" aria-controls="sapi-mode-enigme" aria-selected="true" class="is-active">Mode énigme <span class="sapi-script">à vous de chercher</span></button>
            <button type="button" role="tab" id="sapi-tab-guide" aria-controls="sapi-mode-guide" aria-selected="false">Mode guidé <span class="sapi-script">le tuto vidéo</span></button>
          </div>

          <div id="sapi-mode-enigme" role="tabpanel" aria-labelledby="sapi-tab-enigme">
            <p>Chaque pièce porte un symbole : suivez la plaquette dans l'ordre et trouvez les paires.<?php echo $a_indice ? ' Touchez une étape pour voir son indice.' : ''; ?></p>
            <?php foreach ($blocs as $bi => $b) : ?>
            <div class="sapi-enigme-bloc"<?php if ($par_version) echo ' data-version-panel="' . (int) $bi . '"' . ($bi ? ' hidden' : ''); ?>>
              <?php if (!$b['etapes']) : ?>
                <p class="sapi-enigme__vide">L'énigme de cette version arrive bientôt : suivez la vidéo en mode guidé.</p>
              <?php else : ?>
              <div class="sapi-enigme">
                <?php foreach ($b['etapes'] as $k => $e) :
                  $hk = $bi . '-' . $k;
                  $indice = trim((string) ($e['indice'] ?? ''));
                  $tag = $indice !== '' ? 'button' : 'div';
                  $is_action = ($e['type'] ?? '') === 'action'; ?>
                <<?php echo $tag; ?> class="sapi-enigme__tile<?php echo $is_action ? ' sapi-enigme__tile--action' : ''; ?>"<?php if ($indice !== '') echo ' type="button" data-hint="' . esc_attr($hk) . '" aria-expanded="false"'; ?>>
                  <span class="sapi-enigme__n"><?php echo (int) $k + 1; ?></span>
                  <?php if (!empty($e['photo'])) echo wp_get_attachment_image((int) $e['photo'], 'thumbnail', false, ['loading' => 'lazy', 'class' => 'sapi-enigme__img', 'alt' => '']); ?>
                  <span class="sapi-enigme__sym" role="img" aria-label="<?php echo esc_attr(sapi_notice_step_text($e)); ?>"><?php echo sapi_notice_step_html($e); ?></span>
                </<?php echo $tag; ?>>
                <?php endforeach; ?>
              </div>
              <?php foreach ($b['etapes'] as $k => $e) :
                $indice = trim((string) ($e['indice'] ?? ''));
                if ($indice === '') continue; ?>
                <p class="sapi-enigme__hint" data-hint-text="<?php echo esc_attr($bi . '-' . $k); ?>" hidden><strong>Indice <?php echo (int) $k + 1; ?> :</strong> <?php echo esc_html($indice); ?></p>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <div id="sapi-mode-guide" role="tabpanel" aria-labelledby="sapi-tab-guide" hidden>
            <?php foreach ($blocs as $bi => $b) : $yt = $b['yt']; ?>
            <div class="sapi-guide"<?php if ($par_version) echo ' data-version-panel="' . (int) $bi . '"' . ($bi ? ' hidden' : ''); ?>>
              <?php if ($yt) : ?>
              <div class="sapi-video" data-yt="<?php echo esc_attr($yt); ?>">
                <button type="button" class="sapi-video__poster" aria-label="Lire la vidéo de montage">
                  <img src="<?php echo esc_url('https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg'); ?>" alt="" loading="lazy">
                  <svg viewBox="0 0 76 76" aria-hidden="true"><circle cx="38" cy="38" r="37"/><path d="M31 25 L53 38 L31 51 Z"/></svg>
                </button>
              </div>
              <?php else : ?>
                <p class="sapi-enigme__vide">La vidéo de cette version arrive bientôt.</p>
              <?php endif; ?>
              <?php if ($b['etapes']) : ?>
              <ol class="sapi-guide__steps">
                <?php foreach ($b['etapes'] as $k => $e) :
                  $sec = sapi_notice_seconds($e['temps_video'] ?? '');
                  $indice = trim((string) ($e['indice'] ?? '')); ?>
                <li>
                  <?php if (!empty($e['photo'])) echo wp_get_attachment_image((int) $e['photo'], 'thumbnail', false, ['loading' => 'lazy', 'class' => 'sapi-guide__photo']); ?>
                  <span>
                    <strong class="sapi-guide__sym"><?php echo esc_html(sapi_notice_step_text($e)); ?></strong>
                    <?php if ($indice !== '') echo ' ' . esc_html($indice); ?>
                    <?php if ($yt && $sec !== '') : ?> <button type="button" class="sapi-guide__seek" data-seek="<?php echo (int) $sec; ?>">voir à <?php echo esc_html($e['temps_video']); ?></button><?php endif; ?>
                  </span>
                </li>
                <?php endforeach; ?>
              </ol>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="sapi-phase__foot"><button type="button" class="sapi-btn-cta" data-validate>Le luminaire est assemblé</button></div>
        </div>
      </li>

      <?php /* ── 3 · Abat-jour ── */ ?>
      <li class="sapi-phase" data-phase="3">
        <button type="button" class="sapi-phase__head" aria-expanded="false" aria-controls="sapi-phase-3">
          <span class="sapi-phase__badge">3</span>
          <span class="sapi-phase__titles">
            <span class="sapi-phase__title">Fixer l'abat-jour</span>
            <span class="sapi-phase__sub">Sur le système électrique</span>
          </span>
          <span class="sapi-phase__action"></span>
        </button>
        <div class="sapi-phase__panel" id="sapi-phase-3">
          <div class="sapi-phase__split">
            <?php if ($aj_image) : ?><div class="sapi-phase__visual"><?php echo wp_get_attachment_image($aj_image, 'medium_large', false, ['loading' => 'lazy']); ?></div><?php endif; ?>
            <div class="sapi-phase__text"><p><?php echo wp_kses((string) $aj_texte, ['br' => []]); ?></p></div>
          </div>
          <div class="sapi-phase__foot"><button type="button" class="sapi-btn-cta" data-validate>C'est fixé, j'allume</button></div>
        </div>
      </li>
    </ol>

    <section class="sapi-notice__done" hidden>
      <?php if ($first_thumb) echo wp_get_attachment_image($first_thumb, 'medium_large', false, ['loading' => 'lazy', 'class' => 'sapi-notice__done-img']); ?>
      <div class="sapi-notice__done-body">
        <p class="sapi-script sapi-notice__slogan">Assemblez, Éclairez, Admirez !</p>
        <p>Il est allumé ? Envoyez-moi une photo, ou laissez un avis : ça aide d'autres personnes à choisir.</p>
        <div class="sapi-notice__done-links">
          <a class="sapi-btn-light" href="mailto:commande@atelier-sapi.fr?subject=<?php echo rawurlencode('Photo de mon ' . sapi_notice_title($nid)); ?>">Envoyer une photo</a>
          <a class="sapi-btn-outline" href="<?php echo esc_url(home_url('/mes-creations/')); ?>">Découvrir l'atelier</a>
        </div>
      </div>
    </section>

    <p class="sapi-notice__help">Ça coince ? Écrivez-moi : <a href="mailto:commande@atelier-sapi.fr">commande@atelier-sapi.fr</a> · 06 80 43 55 85</p>
  </div>
</main>
<?php
endwhile;
get_footer();
