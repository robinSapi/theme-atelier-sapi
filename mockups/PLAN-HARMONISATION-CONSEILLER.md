# Plan d'action — Harmonisation design « Robin Conseiller » sur tout le site
*10 juin 2026. Référence = le room-picker de la HOME (post-DA), pill Robin V1 (photo sans contour + accroche Square Peg, sans label « Le conseil de Robin »).*

## Emplacements concernés (repérés dans le code)
1. **Room-picker HOME** — `front-page.php` `.home-projet` → **RÉFÉRENCE** (déjà au bon design).
2. **Room-picker page Conseils** — `page-conseils-eclaires.php` `.advice-room-picker-section` (cadre crème dashed, titre vouvoiement « cherchez-vous », pas de pill Robin) → à aligner.
3. **Room-picker MODALE Conseiller** — `functions.php` (mégafiltre S0) → à aligner (a déjà la signature mais ancien style).
4. **Pill fiche produit** — `single-product.php` `.conseiller-pill-secondary` (« Comment choisir ? ») → passer en V1, accroche « Je t'aide à choisir la bonne variante ».
5. **Card Robin sur Mes créations** — `woocommerce/archive-product.php` → ⚠️ dépend du futur **brief refonte Mes créations**, à traiter en dernier.
- ❓ `page-inspiration.php` utilise aussi des `room-card` → à vérifier en Phase 0 (filtre différent ou 4e room-picker ?).

## La cible (tokens du composant, à figer en Phase 0)
- **Bande** : fond crème (`--color-warm`) + grain bois en filigrane (`repeating-linear-gradient` 92deg, opacité ~.05) + ourlet `border-top`.
- **Titre** : Montserrat 700, `--color-wood-dark`, gabarit section ; **tutoiement** « Pour quelle pièce cherches-tu un luminaire ? ».
- **Pill Robin V1** : capsule `--color-wood-dark` radius 60px, photo ronde 34px `border:none`, accroche Square Peg blanche ~24px, PAS de label.
- **Chips (room-card)** : carré d'icône crème → orange pâle au hover, `translateY(-2px)` + ombre, bordure orange au hover.
- **Séparateur** « ou » + **champ libre** : input pill + bouton rond orange (flèche).
- **Couleurs** : palette charte, accents orange `--color-orange`.
- **Comportement** : attributs `data-room-picker` / `data-piece` / `data-room-picker-freetext` INTACTS (câblage Conseiller).

## Phases (ordre validé Robin 10/06)
**Phase 0 — Spec + audit (CC lecture seule)** ✅ lancée. Écrire la spec ci-dessus + auditer l'état réel des emplacements (+inspiration), lister les écarts précis. Aucune modif.

**Phase 1 — Aligner les room-pickers de PAGE** (Conseils → niveau Home). Home = référence (déjà ok). Factoriser idéalement en une seule famille `.room-picker*` partagée ; retirer les overrides divergents (`.advice-room-picker` dashed/crème, titre vouvoiement). Harmoniser fond, hovers, pill V1, textes (tutoiement + placeholder identiques), couleurs. Valider sur test.
→ ⚠️ Le 3e room-picker (celui de la MODALE) est traité en **Phase 4** (refonte complète de la modale) pour ne PAS toucher la modale deux fois.

**Phase 2 — Pill fiche produit** : `.conseiller-pill-secondary` → V1 + accroche « Je t'aide à choisir la bonne variante ». **Robin veut un mockup rapide d'abord** (à quoi ça ressemble sur la fiche produit) avant de coder. Puis valider sur test.

**Phase 3 — Card Robin Mes créations** : DANS le cadre du brief refonte Mes créations (à venir, Robin l'enverra). Ne pas la finaliser avant. Option quick win : poser la pill V1 en attendant si Robin veut.

**Phase 4 — Refonte COMPLÈTE de la modale Conseiller (TRÈS IMPORTANTE, ajout Robin)** : pas seulement le room-picker S0, mais **tous les états de la modale** (S0 accueil/pièce, S1, S2 chat libre, S3, récap, contact) → design harmonisé avec la référence (fond, chips, pill Robin V1, couleurs, textes tutoiement). C'est un chantier à part entière → prévoir une **exploration mockup** comme pour la home. Le room-picker S0 = le 3e des « 3 room-pickers » à rendre identique, traité ici.

**Transverse** : branche `test-theme-sapi-maison`, validation par phase, go-live par phase ou groupé. Une fois figé → consigner le composant dans `memory/design_system.md`.

## Points à valider avec Robin au fil de l'eau
- Modale (Phase 4) : « même fond » crème ou adaptation au contexte modale ? (exploration mockup).
- Inspiration : room-picker à aligner, ou filtre distinct à laisser ? (réponse en Phase 0).
- Mes créations : on attend ton brief refonte (la card Robin y sera intégrée, Phase 3).
