# Harmonisation Conseiller — PHASE 0 : spec de référence + audit des écarts
*10 juin 2026 — lecture seule, aucune modif de code. Référence = room-picker HOME + pill Robin V1 (`76a468f`).*

Voir le plan d'ensemble : `mockups/PLAN-HARMONISATION-CONSEILLER.md`.

---

## A. Spec de référence (la HOME, telle qu'elle est dans le code)

### A.1 — La bande
`.home-projet-section` (`style.css:7938`) :
- `background: var(--color-warm)` ; `padding: 6rem 2rem 3.5rem` (mobile ≤768 : `3.5rem 14px 2.5rem`) ; pleine largeur, `margin:0`.
- `position:relative` + **grain bois** `::before` : `repeating-linear-gradient(92deg, rgba(139,115,85,.045) 0, …1px, transparent 1px, transparent 7px)`, `z-index:0`, `pointer-events:none`.
- ⚠️ **Pas de `border-top`** aujourd'hui (le plan mentionnait un « ourlet border-top » → ce serait un AJOUT, pas l'existant).

`.home-projet` (`style.css:7943`) : `max-width:1280px`, `margin:0 auto`, `text-align:center`, `position:relative`, `z-index:1`. Attribut `data-room-picker`.

### A.2 — Pill Robin V1 (commit `76a468f`, scopé `.home-projet`)
Markup (`front-page.php:553`) : `.conseiller-sig` > `__avatar`(img Robin) + `__text`(`__who` + `__hook`).
- `.home-projet .conseiller-sig` : `inline-flex`, `gap:12px`, `background:var(--color-wood-dark)`, `border-radius:60px`, `padding:6px 24px 6px 6px`, `margin:0 0 16px`. (centrée car `.room-picker-inner` est `flex`/`align-items:center`).
- `__avatar` : `34×34`, `border:none`, `box-shadow:none` (rond via la base `border-radius:50%;overflow:hidden`).
- `__who` : `display:none` (« Le conseil de Robin » retiré → identité par la photo).
- `__hook` : `color:#fff`, **Square Peg** (hérité de la base `--font-display`), `font-size:24px`, `line-height:1`. Texte home = « Mon regard d'artisan sur ton projet ».
- Mobile ≤600 : hook 21px, `max-width:100%`.
- ⚠️ La **base** `.conseiller-sig` (`style.css:7984`) reste l'ANCIEN style (avatar 72px bordé blanc, label visible, hook 32px). C'est ce que voit encore la **modale**. Seul `.home-projet` est en V1.

### A.3 — Le room-picker partagé (famille `.room-picker-*`, `style.css:10722-10931`)
| Élément | Valeurs |
|---|---|
| `.room-picker-inner` | `flex` column, `align-items:center`, `gap:18px`, `padding:36px 32px 40px` |
| `.room-picker-title` | Montserrat **700**, `clamp(20px,3vw,32px)`, **uppercase**, `letter-spacing:.02em`, `color:--color-wood-dark`, centré, `margin:0`. Home : `white-space:nowrap` ≥769. Texte home = **« Pour quelle pièce cherches-tu un luminaire ? »** (tutoiement, balise `<h2>`) |
| `.room-picker-cards` | `flex`, centré, `gap:1.25rem` (home/advice : + `flex-wrap:wrap`) |
| `.room-card` (+`button.room-card`) | `flex:1`, `max-width:200px` (**home override 340px**), column, `gap:.75rem`, `padding:22px 14px 18px`, `bg:white`, `border:1px solid --color-line`, `radius:12`, `shadow-card`. Hover (global) : `translateY(-2px)` + `shadow-card-hover` + **border wood**. **Home override : border ORANGE** |
| `.room-card-icon` | `60×60`, `bg:--color-warm`, `radius:12`, `color:wood` ; svg `30×30` ; hover → `color:wood-dark` |
| `.room-card-label` | Montserrat **700**, `14px`, `letter-spacing:.08em`, uppercase, wood-dark |
| `.room-picker-or` (+`__text`) | filets `rgba(139,115,85,.25)` ; `__text` `10.5px`/700/uppercase, bg white, pill bordé |
| `.room-picker-freetext` | `max-width:480px` ; `__input` pill `13px 52px 13px 20px`, border `1.5px --color-line`, `radius:50`, `14.5px`, focus border wood ; `__submit` rond `38px` orange, flèche blanche, hover `orange-hover`. Placeholder = **« Décris ton projet en quelques mots… »** (tutoiement) |

### A.4 — Comportement (câblage à NE JAMAIS casser)
`data-room-picker`, `data-piece`, `data-piece-label`, `data-room-picker-freetext`. JS : `sapi-room-picker.js`.

---

## B. Audit par emplacement — écarts vs la référence

### B1. HOME — `front-page.php` `.home-projet` ✅ RÉFÉRENCE (rien à faire)

### B2. CONSEILS — `page-conseils-eclaires.php` `.advice-room-picker` (Phase 1)
**Markup intérieur identique** (même `.room-picker-inner` / `.room-card`[`<a>`] / `or` / `freetext`, **même placeholder tutoiement**). Écarts :
| # | Écart | Référence |
|---|---|---|
| 1 | Cadre **crème + bordure dashed inset** (`.advice-room-picker` : `bg warm`, `radius:16`, `padding:2.5rem`, `::before` dashed `1.5px rgba(147,125,104,.35)` inset 12px), wrapper contenu `max-width:1400` | Bande pleine largeur warm + **grain bois**, sans cadre dashed |
| 2 | **Pas de pill Robin** (aucun `.conseiller-sig`) | Pill V1 en tête |
| 3 | Titre en **`<h3>`** + **vouvoiement** « cherchez-**vous** » | `<h2>` + tutoiement « cherches-**tu** » |
| 4 | Hover room-card = **border wood** (global) | Home = border **orange** |
| 5 | Pas de grain `::before` | Grain bois |

### B3. MODALE Conseiller S0 — `functions.php:3689` (⚠️ Phase 4, NE PAS toucher en Phase 1)
**Découverte importante : la modale S0 N'UTILISE PAS la famille `.room-picker-*`.** Markup à part :
`.modal__head` / `.modal__body` / `.choices` (`data-s0-choices`, peuplé par JS) / `.separator-or` (≠ `.room-picker-or`) / `.text-input-wrap`+`.text-input`+`.text-submit` (≠ `.room-picker-freetext`). La signature `.conseiller-sig` y est en **style de base ANCIEN** (avatar 72px bordé blanc, label visible, hook 32px), + un `.badge` (`data-s0-badge`) masqué en CSS sur s0/s2. Titre dynamique `data-s0-question`.
→ Aligner la modale = **réécriture de markup** (classes différentes), pas un simple override. Confirme que c'est un chantier Phase 4 à part. JS : `robin-conseiller.js`.

### B4. PILL FICHE PRODUIT — `single-product.php:424` `.conseiller-pill-secondary` (Phase 2 — mockup d'abord)
Actuel (`style.css:22249`) : `inline-flex`, **bg `--color-warm`**, **border dashed** `1.5px rgba(147,125,104,.5)`, `radius:50`, `padding:6px 18px 6px 7px`, `gap:9`, texte `12px`/600, color wood-dark ; `__avatar` **26px** rond + ombre légère ; hover → bg white + border **solid** wood-dark. Texte « Comment choisir ? » (live via `sapi-help-pill.js`).
| Écart | Cible V1 |
|---|---|
| Capsule **claire dashed** | Capsule **wood-dark pleine** |
| Avatar 26px bordé d'ombre | Photo **34px sans contour** |
| Texte Montserrat 12px | **Accroche Square Peg blanche**, copy « Je t'aide à choisir la bonne variante » |
⚠️ **À préserver** : `id="robin-product-pill"`, `data-action="open-modal"`, `data-modal-state="product"`, `data-help-pill`, `data-help-pill-text` (sinon `sapi-help-pill.js` casse).

### B5. MES CRÉATIONS — `woocommerce/archive-product.php:139` `.conseiller-card--conseil` (Phase 3 — attend le brief refonte)
Carte (`style.css:22378`) `padding:36/32/40`, hover translateY+ombre, contenant le room-picker partagé : badge crayon « Conseil de Robin » + **`<h2>` vouvoiement** + `.room-card`[`<button>` `data-piece`/`data-piece-label`, JS `sapi-cards-conseiller.js`] + or + freetext (placeholder tutoiement).
Écarts : pas de pill V1 (badge crayon à la place) ; titre **vouvoiement**. → Traiter **en dernier**, dans le cadre du brief Mes créations. `data-*` : `data-conseiller-card`, `data-conseiller-zone`, `data-mes-creations-selection`, `data-piece-photos`.

### B6. INSPIRATION — `page-inspiration.php` ❓→ **VERDICT : à EXCLURE**
N'utilise **pas** de room-picker. C'est un **filtre de galerie** distinct : `.inspiration-filter-btn` (filtrage par **pièce + essence de bois**, `data-filter-type`/`data-filter-value`), il ne réutilise QUE les icônes de pièces. Fonction différente (filtrer des photos ≠ « pour quelle pièce te conseiller »). → **Ne pas l'aligner**, le laisser tel quel. Il n'y a donc pas de « 4e room-picker ».

---

## C. Cartographie classes partagées vs spécifiques

**Socle partagé (source de vérité, `style.css:10722-10931`)** — utilisé par HOME + CONSEILS + MES-CRÉATIONS :
`.room-picker-inner`, `.room-picker-title`, `.room-picker-sub`, `.room-picker-cards`, `.room-card`(+`button.room-card`), `.room-card-icon`, `.room-card-label`, `.room-picker-or`(+`__text`), `.room-picker-freetext`(+`__input`/`__submit`). **Déjà bien factorisé.**

**Overrides par contexte :**
- `.home-projet*` → bande warm pleine largeur + grain, **pill V1**, room-card `max-width:340` + **hover orange**, titre nowrap, version mobile compacte.
- `.advice-room-picker*` → **cadre crème dashed inset**, pas de pill, hover wood, h3 vouvoiement.
- `.conseiller-card--conseil*` → carte + badge crayon, reset des marges du room-picker intégré.
- `.conseiller-card--modal*` → **markup totalement à part** (`.modal__*`, `.choices`, `.separator-or`, `.text-input*`) + `.conseiller-sig` base.
- `.conseiller-sig` : **base ancienne** (modale) vs override **V1** (`.home-projet`).

---

## D. Reco de factorisation pour la Phase 1

Le socle `.room-picker-*` est déjà partagé : **pas besoin de le refactorer**, juste de **réduire les overrides divergents** côté Conseils.

**Option recommandée — un modificateur « bande Robin » partagé :** extraire de `.home-projet` les 3 traits identitaires (grain bois + pill V1 + hover orange + tutoiement) dans une classe réutilisable (ex. `.room-picker--robin`), posée sur HOME **et** CONSEILS, et **scopée aux room-pickers de PAGE uniquement** (jamais modale/mes-créations, qui restent en phases 4/3 avec hover wood). Concrètement pour Conseils :
1. Retirer le cadre dashed crème (`.advice-room-picker::before` + framing) → adopter le langage bande warm + grain (à trancher avec Robin : bande pleine largeur comme la home, **ou** panneau contenu `max-width:1400` mais restylé warm+grain+pill, sans dashed). *Reco : panneau contenu restylé (la page Conseils est un mid-page, pas une home), pour garder le rythme de la page.*
2. Ajouter le bloc `.conseiller-sig` pill V1 (même markup que la home, accroche contextuelle « conseils »).
3. `<h3>`→`<h2>` (ou garder h3 pour la hiérarchie SEO de la page mais aligner le style) + **tutoiement** « cherches-tu ».
4. Hover room-card → **orange** (via la classe partagée).
5. Placeholder déjà OK (tutoiement identique).

**À verrouiller ensuite** dans `memory/design_system.md` une fois figé.

---

## E. Questions à trancher avec Robin (avant Phase 1)
1. **Conseils** : bande pleine largeur (comme la home) **ou** panneau contenu restylé (reco) ? Et garde-t-on `<h3>` (SEO) ou passe-t-on `<h2>` ?
2. Accroche Square Peg de la pill sur Conseils : reprendre « Mon regard d'artisan sur ton projet » ou une accroche propre à la page ?
3. OK pour confirmer **Inspiration hors périmètre** (filtre distinct) ?
