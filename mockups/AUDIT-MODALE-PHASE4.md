# Harmonisation Conseiller — PHASE 4 : audit de la MODALE (mégafiltre « Robin »)
*10 juin 2026 — audit visuel + DOM sur `test.atelier-sapi.fr`. Lecture seule, aucun fichier modifié, aucun formulaire envoyé.*

Référence de langage : la HOME (`.home-projet`) + pill Robin V1 — voir `mockups/AUDIT-CONSEILLER-PHASE0.md`.
Cible : fond crème `--color-warm` + grain bois en filigrane, chips pièce (hover orange + translateY + ombre), **pill Robin V1** (capsule `--color-wood-dark`, photo ronde 34px sans contour, accroche Square Peg blanche), boutons pills orange/bois, **tutoiement** partout.

---

## 0. Comment la modale se déclenche (important)

- ⚠️ **Les chips « pièce » de la HOME n'ouvrent PAS la modale.** Ce sont de simples liens `<a class="room-card" href="/mes-creations/?piece=salon" data-piece="salon">` qui **naviguent** vers `/mes-creations/`. Le script `robin-conseiller.js` n'est **pas chargé sur la home**.
- La modale (`.conseiller-modal`) est injectée dans le DOM **sur la fiche produit variable** (testé sur Gaston, p=3119). Scripts chargés : `sapi-cards-conseiller.js`, `sapi-modal-conseiller.js`, `sapi-help-pill.js`.
- **Déclencheur** : la pill `button#robin-product-pill.conseiller-pill-secondary` (« Je t'aide à choisir la bonne version », `data-action="open-modal"`, `data-modal-state="product"`). Au clic → ouvre la modale sur l'écran `s0` (avec, le cas échéant, l'état pièce déjà mémorisé par le localStorage projet).
- Mécanique d'affichage : la modale et ses écrans sont masqués par **l'attribut `hidden`** ; le JS l'ajoute/retire pour naviguer entre écrans (`.conseiller-modal[hidden]{display:none}`).

**La pill produit elle-même est DÉJÀ au langage V1** : capsule bois sombre `--color-wood-dark`, photo ronde de Robin sans contour, accroche Square Peg blanche « Je t'aide à choisir la bonne version ». (C'est l'objet de la Phase 2, visiblement déjà appliquée sur test.) → rien à refaire sur la pill, elle sert de **modèle** pour la signature de la modale.

---

## 1. Les états de la modale (dans l'ordre du tunnel)

La modale contient **6 écrans** : `s0`, `s1`, `s2-chat`, `s-product-recap`, `s-contact`, `s3`. Tous partagent le même cadre : carte `.conseiller-card--modal`.

### Cadre commun (tous les écrans)
- **Fond carte** : `#FBF6EA` = `--color-warm` (crème). ✅ déjà la bonne couleur de fond.
- **Bordure** : `border-radius:16px`, **pseudo `::before` en pointillés** `1.11px dashed rgba(139,115,85,.35)` en inset. ❌ **PAS de grain bois** (`background:none` sur la carte) — le filigrane bois de la home est absent.
- **Overlay** : `rgba(30,22,14,.82)`, `z-index:10050`.
- **Bouton fermer** : `.modal__close` croix simple en haut à droite.
- Ombre carte : `0 24px 64px rgba(0,0,0,.25)` (ombre noire générique, pas l'ombre bois `--shadow-card`).

---

### État S0 — Accueil / choix de la pièce
*Écran `[data-screen="s0"]`, titre `data-s0-question` = « POUR QUELLE PIÈCE ? »*

**Contenu :**
- **Signature** `.conseiller-sig` (header) : photo Robin + « LE CONSEIL DE ROBIN » + « Mon regard d'artisan sur ton projet ».
- Titre `<h2>` « POUR QUELLE PIÈCE ? ».
- 7 cartes de choix `.choices > *` (`data-s0-choices`, peuplées par JS), **grille 3 colonnes** (`153px`, gap 10px, max-width 480) : **CUISINE, BUREAU / ATELIER, SALON / SALLE À MANGER, CHAMBRE, CHAMBRE ENFANT, ENTRÉE / COULOIR, CAGE D'ESCALIER**. (⚠️ libellés DIFFÉRENTS des chips home : « Salon » seul, « Bureau » seul, etc.)
- Séparateur `.separator-or` « OU ».
- Champ libre `.text-input-wrap`/`.text-input` placeholder « Décris ton projet en quelques mots… » (tutoiement ✅) + `.text-submit` rond orange.
- Réassurance `data-s0-reassure` « Robin t'aide à choisir, sans engagement » (italique).
- Footer `.footer-link` « Effacer et recommencer » (visible seulement si projet en cours).
- `data-s0-badge` (« Conseil de Robin ») masqué sur s0.
- 📌 *Observation* : au tout premier affichage (reset), le titre + le champ libre sont visuellement effacés au profit de la seule ligne de réassurance ; ils réapparaissent ensuite. À garder en tête pour la cohérence visuelle.

**Style actuel :**
- Signature en **ANCIEN style de base** (`.conseiller-sig` sans `--v1`) : avatar **72px** bordé blanc 2.8px + ombre, label « who » **VISIBLE** (Montserrat 12px uppercase wood), hook Square Peg.
- Cartes de choix : **blanches**, `radius:12`, bordure `rgba(147,125,104,.18)`, ombre `--shadow-card` (icône carrée crème + label Montserrat 700 uppercase). Très proche des `.room-card` mais markup `.choices`.
- Séparateur/champ libre : visuellement identiques au room-picker home (filets bois, pill blanche, submit rond orange) mais classes propres (`.separator-or`, `.text-input*`).

**Écarts vs cible :**
1. **Signature = pill V1 à appliquer** : la classe `.conseiller-sig--v1` **existe déjà dans le CSS** (capsule wood-dark, avatar 34px sans contour, « who » masqué, hook Square Peg blanc 24px) mais **n'est pas posée** sur la signature de la modale. → ajouter `--v1` (ou réécrire le markup signature) = gain rapide.
2. **Pas de grain bois** sur la carte (à ajouter en filigrane, en cohabitant avec/ou remplaçant le `::before` dashed).
3. **Cadre dashed crème** à trancher (le garder restylé, ou passer au langage bande warm + grain sans pointillés).
4. **Hover des cartes de choix** : à confirmer (cible home = bordure **orange** + translateY + ombre).
5. Libellés pièces plus longs que la home (à conserver tels quels si voulu — ce sont des regroupements).

---

### État S1 — Questions intermédiaires (taille / style…)
*Écran `[data-screen="s1"]`, RÉUTILISÉ pour chaque étape de filtre. Titre `data-question-title`.*

**Contenu (exemples observés) :**
- « QUELLE TAILLE FAIT VOTRE SALON ? » → PETIT / STANDARD / GRAND / JE NE SAIS PAS.
- « QUEL STYLE POUR VOTRE SALON ? » → MODERNE, NEUF, TONS CLAIRS / ANCIEN, PIERRE, BOIS, TONS CHAUDS / PAS DE PRÉFÉRENCE.
- Header : **petit badge** `.badge` wood-dark « ✏ CONSEIL DE ROBIN » (pas la signature photo).
- Cartes de choix `[data-choices]` (icône + label gras + sous-titre).
- **Barre de progression** `[data-progress-fill]` en bas.
- Footer « ← ÉTAPE PRÉCÉDENTE ».

**Style actuel :**
- Badge : capsule `--color-wood-dark` (#4A3F35), texte blanc, Montserrat 14px uppercase, `radius:50px`, padding 9/19 — déjà charté (mais c'est un badge texte, pas la pill photo).
- Cartes : blanches, `radius:12`, ombre `--shadow-card`.
- Barre : piste `rgba(139,115,85,.15)` h~4px radius 2 ; remplissage **orange `#E35B24`** (`--color-orange`). ✅ déjà charté.
- Footer-link : wood-mid 11.5px uppercase souligné.

**Écarts vs cible :**
1. **Vouvoiement** dans les titres : « VOTRE salon » → à passer en tutoiement (« ton salon »). C'est le principal écart de copy.
2. Header sans pill Robin V1 (juste le badge texte) — à arbitrer : garder le badge sobre sur les étapes, ou réintroduire la pill photo.
3. Hover cartes (orange vs wood) à aligner comme S0.

---

### État S2-CHAT — Échange libre avec « l'IA Robin »
*Écran `[data-screen="s2-chat"]`.*

**Contenu :**
- **Signature complète** `.conseiller-sig` (photo + « LE CONSEIL DE ROBIN » + « Mon regard d'artisan sur ton projet »).
- Zone messages `[data-chat-messages]` (fil de discussion).
- Champ `[data-chat-input]` placeholder « Ta réponse… » + submit rond orange.
- CTA `[data-chat-cta]` « Voir ma sélection » (`.action-btn--primary`, masqué tant qu'il n'y a pas d'échange).

**Style actuel :** mêmes constats que S0 — signature en **ANCIEN style**, carte crème + dashed, pas de grain.

**Écarts vs cible :** identiques à S0 (signature → V1, grain, dashed). Copy déjà au tutoiement.

---

### État S-PRODUCT-RECAP — Récap / reco produit (état « product »)
*Écran `[data-screen="s-product-recap"]`. C'est l'aboutissement quand la modale est ouverte depuis une fiche produit.*

**Contenu :**
- Badge wood-dark « ✏ MON CONSEIL POUR TOI ».
- Intro `data-product-recap-intro` : « Pour **votre** salon / salle à manger, Robin recommande : » (⚠️ vouvoiement).
- Carte blanche récap : **ESSENCE** (ex. Peuplier) + **TAILLE** (ex. 70 cm) — `data-product-recap-essence` / `data-product-recap-taille`.
- 2 lignes de conseil italiques `data-product-recap-conseil*` (tutoiement : « ton intérieur moderne »).
- Bouton primaire orange pill « ✓ APPLIQUER CETTE SÉLECTION » (`.action-btn--primary`) → applique les variations sur la fiche.
- Bouton secondaire blanc pill « MODIFIER MON PROJET » (`.action-btn--secondary`).
- Footer-link « CONTACTER ROBIN » → ⚠️ **redirige vers la page `/contact/`** (PAS vers l'écran s-contact interne).

**Style actuel :** carte crème + dashed, carte récap blanche, boutons pills (primaire orange `#E35B24` Montserrat 12px uppercase, secondaire blanc).

**Écarts vs cible :**
1. **Vouvoiement** dans l'intro (« votre salon ») → tutoiement.
2. Pas de pill Robin V1 en tête (badge texte seulement).
3. Grain + dashed comme les autres écrans.
4. *(À discuter)* « Contacter Robin » qui quitte la modale vers `/contact/` au lieu d'ouvrir `s-contact` — incohérent avec l'existence d'un écran contact interne.

---

### État S3 — Récap « Ton projet » (flux générique, hors produit)
*Écran `[data-screen="s3"]`. Équivalent du recap quand on n'est pas sur une fiche produit (ex. /mes-creations/).*

**Contenu :**
- Badge wood-dark « ⊙ TON PROJET » (tutoiement ✅).
- Titre « VOICI TON PROJET » (tutoiement ✅).
- Chips récap des réponses `[data-recap-chips]` (cliquables pour modifier une réponse).
- Bouton primaire orange « → VOIR LA SÉLECTION ».
- Bouton secondaire blanc « ☺ PRÉCISER AVEC ROBIN » (→ ouvre le chat s2).
- Footer-link « ↻ RECOMMENCER ».
- Microcopy « Tu peux modifier n'importe quelle réponse en cliquant sur une chip » (tutoiement ✅).

**Style actuel :** carte crème + dashed, boutons pills orange/blanc, badge wood-dark.

**Écarts vs cible :** copy déjà au tutoiement (bon élève). Restent : pas de pill Robin V1, grain + dashed.

---

### État S-CONTACT — Mini-formulaire « Échanger avec Robin »
*Écran `[data-screen="s-contact"]`. (Atteint depuis le flux interne, ≠ lien « Contacter Robin » du recap produit qui part sur /contact/.)*

**Contenu :**
- Badge wood-dark « ✏ ÉCHANGEONS ENSEMBLE ».
- Champ email `[email]` placeholder « Ton email » (tutoiement ✅).
- Textarea « Décris ton projet… » (`data-contact-message-field`, tutoiement ✅).
- **Honeypot anti-spam** `input[name="website"]` (masqué — NE PAS supprimer).
- Bouton orange pill « ENVOYER MA DEMANDE → » (`data-contact-submit`).
- Sous-texte « Réponse de Robin sous 48h · Aucun engagement ».
- Microcopy de consentement : « En envoyant **votre** demande, **vous** acceptez de recevoir… » (⚠️ vouvoiement).
- Footer-link « ← REPRENDRE LA DISCUSSION » ; bouton « Fermer » (`.action-btn--secondary`).
- État de succès `data-contact-state` : « Reçu — Robin t'écrit sous 48h » (tutoiement ✅).
- ⚠️ **Formulaire non soumis** pendant l'audit (consigne respectée).

**Style actuel :** carte crème + dashed, champs blancs pill, bouton orange pill.

**Écarts vs cible :**
1. **Vouvoiement** dans la microcopy de consentement → tutoiement.
2. Pas de pill Robin V1.
3. Grain + dashed.

---

## 2. Tableau de synthèse — état → ce qu'il faut harmoniser

| État | Signature actuelle | Copy | Fond/cadre | Écarts à corriger |
|---|---|---|---|---|
| **S0** (pièce) | `.conseiller-sig` ANCIEN (72px bordé, label visible) | Tutoiement (placeholder, réassurance) ✅ | crème + dashed, **sans grain** | Appliquer `.conseiller-sig--v1` ; ajouter grain ; arbitrer dashed ; hover cartes orange |
| **S1** (taille/style…) | badge texte « Conseil de Robin » | ❌ **vouvoiement** (« VOTRE salon ») | crème + dashed, sans grain | Tutoiement titres ; grain ; (option) pill V1 |
| **S2-chat** | `.conseiller-sig` ANCIEN | tutoiement ✅ | crème + dashed, sans grain | Signature → V1 ; grain ; dashed |
| **S-product-recap** | badge « Mon conseil pour toi » | ❌ intro **vouvoiement** | crème + dashed, sans grain | Tutoiement intro ; grain ; (option) pill V1 ; lien « Contacter Robin » → s-contact ? |
| **S3** (recap projet) | badge « Ton projet » ✅ | tutoiement ✅ | crème + dashed, sans grain | Grain ; dashed ; (option) pill V1 |
| **S-contact** | badge « Échangeons ensemble » | ❌ consentement **vouvoiement** | crème + dashed, sans grain | Tutoiement consentement ; grain ; (option) pill V1 |

**Les 3 écarts transverses majeurs :**
1. **Signature** : remplacer l'ancien `.conseiller-sig` (S0 + S2-chat) par la pill V1 (`.conseiller-sig--v1` existe déjà). Les autres écrans utilisent un badge texte wood-dark — à décider s'ils adoptent aussi la pill photo.
2. **Grain bois absent** sur toute la modale (le `::before` sert au pointillé, pas au grain).
3. **Vouvoiement résiduel** sur S1 (titres), S-product-recap (intro) et S-contact (consentement). S0/S2/S3 sont déjà au tutoiement.

---

## 3. Composants transverses de la modale (état actuel)

| Composant | Sélecteur | État actuel | Cible |
|---|---|---|---|
| **Carte modale** | `.conseiller-card--modal` | crème `--color-warm`, `radius:16`, `::before` **dashed** `1.11px rgba(139,115,85,.35)`, ombre noire `0 24px 64px rgba(0,0,0,.25)`, max-width 700 | + **grain bois** filigrane ; arbitrer dashed ; ombre bois ? |
| **Overlay** | `.conseiller-modal` | `rgba(30,22,14,.82)`, z-index 10050 | OK |
| **Bouton fermer** | `.modal__close` | croix simple top-right | OK |
| **Signature (S0/S2)** | `.conseiller-sig` | ANCIEN : avatar 72px bordé blanc + ombre, « who » visible, hook Square Peg | **`.conseiller-sig--v1`** : pill wood-dark, photo 34px sans contour, « who » masqué, hook blanc 24px |
| **Badge étape** | `.badge` (S1/recap/s3/contact) | pill wood-dark `#4A3F35`, blanc, Montserrat 14px uppercase, radius 50 + icône crayon/cible | déjà charté ; à harmoniser avec la pill photo si besoin |
| **Cartes de choix** | `.choices > *` | blanches, `radius:12`, bordure `rgba(147,125,104,.18)`, ombre `--shadow-card`, grille 3 col | hover **orange** + translateY (comme home) à confirmer |
| **Barre de progression** | `[data-progress-fill]` + piste | piste wood 15 %, remplissage **orange `#E35B24`** | ✅ déjà charté |
| **Séparateur** | `.separator-or` / `__text` | filets + texte « OU » 10.5px/700 uppercase wood sur fond crème | OK (équiv. room-picker) |
| **Champ libre** | `.text-input-wrap` / `.text-input` / `.text-submit` | pill blanche border `rgba(147,125,104,.18)` radius 50, submit rond **orange** 36px | OK (équiv. room-picker) |
| **Bouton primaire** | `.action-btn--primary` | pill **orange `#E35B24`**, blanc, Montserrat 12px uppercase, radius 50 | ✅ charté |
| **Bouton secondaire** | `.action-btn--secondary` | pill **blanche** | ✅ charté |
| **Footer-link** | `.footer-link` | wood-mid 11.5px uppercase souligné | OK |

### Responsive (mobile, CSS — non rendu visuellement, voir note §5)
- `@media (max-width:600px)` : `.conseiller-card--modal` passe **plein écran** (`max-width:100%`, `height:calc(100dvh - 64px)`), `::before` inset 8px.
- Signature mobile : `.conseiller-sig__avatar` **52px**, hook 24px (et `.conseiller-sig--v1` : hook 21px, max-width 100 %).
- `.modal__head` padding réduit.
- Les `.choices` repassent en **2 colonnes** sur certains contextes (`@media max-width:768px`, `grid-template-columns: repeat(2,1fr)`).

---

## 4. À NE PAS CASSER (hooks JS / data-attributes pilotés par le filtre)

Markup propre à la modale (≠ famille `.room-picker-*`). Scripts : `sapi-modal-conseiller.js`, `sapi-cards-conseiller.js`, `sapi-help-pill.js`. **Conserver tous ces sélecteurs/attributs** :

**Structure & navigation :**
- `.conseiller-modal` `[data-conseiller-modal]` (conteneur) ; `.conseiller-card--modal` `[data-modal-card]`.
- `.modal__screen` `[data-screen="s0|s1|s2-chat|s-product-recap|s-contact|s3"]` + l'attribut **`hidden`** (mécanique d'affichage).
- `.modal__close` `[data-action="close"]` ; tous les `[data-action]` (close, etc.).

**S0 :** `[data-s0-question]`, `[data-s0-choices]`, `[data-s0-form]`, `[data-s0-input]`, `[data-s0-reassure]`, `[data-s0-reset-wrap]`, `[data-s0-badge]`, `[data-s0-badge-text]`.

**S1 :** `[data-question-title]`, `[data-choices]`, `[data-progress-fill]`.

**S2-chat :** `[data-chat-messages]`, `[data-chat-form]`, `[data-chat-input]`, `[data-chat-cta]`.

**S-product-recap :** `[data-product-recap-intro]`, `[data-product-recap-card]`, `[data-product-recap-essence]`, `[data-product-recap-essence-value]`, `[data-product-recap-taille]`, `[data-product-recap-taille-value]`, `[data-product-recap-conseil]`, `[data-product-recap-conseil-taille]`.

**S3 :** `[data-recap-chips]`.

**S-contact :** `[data-contact-state]`, `[data-contact-badge-text]`, `[data-contact-message]`, `[data-contact-recap]`, `[data-contact-form]`, `[data-contact-message-field]`, `[data-contact-submit]`, **`input[name="website"]` (honeypot anti-spam — ne pas retirer)**.

**Pill déclencheur (fiche produit) :** `button#robin-product-pill.conseiller-pill-secondary` avec `data-action="open-modal"`, `data-modal-state="product"`, `data-help-pill`, `data-help-pill-text` (pilotée par `sapi-help-pill.js`). Déjà en V1, à conserver.

---

## 5. Notes & limites de l'audit

- **Mobile non capturé en image** : `resize_window` n'a pas reflowé le viewport rendu (le screenshot reste à 1352px de large). Le responsive est documenté §3 à partir du CSS (`@media max-width:600/768px`). À revérifier sur un vrai device / DevTools responsive avant la refonte.
- **Captures réalisées** (desktop) : S0 (reset pièces + variante style), S1 (taille), S-product-recap, S-contact, S3, S2-chat, + la pill produit V1.
- **Aucun fichier modifié, aucun formulaire envoyé.** Les écrans s-contact/s3/s2-chat ont été affichés en retirant temporairement l'attribut `hidden` dans le DOM de la page (manipulation runtime, non persistée).
- **Bonne nouvelle pour la refonte** : la classe cible `.conseiller-sig--v1` **existe déjà** dans le CSS — l'alignement de la signature peut se faire en posant ce modificateur (ou en réécrivant le markup signature des écrans s0/s2-chat), sans créer de nouveau composant.
- **Point d'arbitrage** : la modale a son **propre markup** (`.modal__*`, `.choices`, `.separator-or`, `.text-input*`), distinct de la famille partagée `.room-picker-*`. L'harmonisation passe donc par une **réécriture/override de ce markup propre**, pas par la simple réutilisation des classes home.
