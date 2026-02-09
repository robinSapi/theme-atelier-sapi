# AUDIT FICHE PRODUIT — ATELIER SAPI
## Analyse UX/UI, Bugs & Benchmark Concurrentiel

**Date :** 6 février 2026
**Page auditée :** `/nos-creations/timothee-laraignee/`
**Site :** testlumineux.atelier-sapi.fr
**Auditeur :** Claude (Expert UX/UI e-commerce)

---

## SUMMARY

**Verdict global :** La fiche produit actuelle souffre de **problèmes structurels majeurs** qui cassent l'expérience et nuisent à la conversion. Les images de la galerie sont minuscules (~70px), le prix ne s'actualise pas selon les variations, les swatches n'ont pas de labels, et **5+ images lifestyle full-height (100vh) écrasent complètement la hiérarchie visuelle** — certaines ne montrent même pas le luminaire. La page manque de storytelling, de rassurance visible, et de CTA clairs. Par rapport aux concurrents (bōlum, Loupiote, Moooi), Sapi est **en retard de 2-3 générations** en termes d'UX e-commerce. L'univers artisanal de Robin n'est pas valorisé : la fiche produit ressemble à un template WooCommerce mal configuré plutôt qu'à une vitrine premium.

---

## AUDIT

### 🔴 BUGS / DYSFONCTIONNEMENTS

#### P0 — Bloquants conversion

| # | Bug | Observation | Impact |
|---|-----|-------------|--------|
| 1 | **Images produits similaires 57×57px** | Section "Produits similaires" : images quasi invisibles (seuls les boutons orange visibles) | Perte de cross-sell |
| 2 | **Prix statique** | Affiche "90,00 € – 160,00 €" même après sélection d'une taille | Confusion prix, abandon |
| 3 | **Swatches matériau sans label** | Un seul cercle beige, aucun texte "Okoumé" ou "Peuplier" | Impossible de choisir |
| 4 | **Formulaire variations invisible (mobile)** | Sur viewport 390px, le formulaire disparaît complètement | 0% conversion mobile |

#### P1 — Importants

| # | Bug | Observation | Impact |
|---|-----|-------------|--------|
| 5 | **Vignettes galerie minuscules** | ~70×70px, impossibles à distinguer | UX dégradée |
| 6 | **Checkout sans récapitulatif** | Page validation : formulaire mais pas de liste produits | Friction checkout |
| 7 | **URLs panier incohérentes** | /panier/ → 404, /checkout/ → mauvaise page | Parcours cassé |

#### P2 — Mineurs

| # | Bug | Observation | Impact |
|---|-----|-------------|--------|
| 8 | **Titre onglet incorrect** | "Archives des Suspension" (singulier) | Perception amateur |
| 9 | **Copyright mal nommé** | "testLumineuxAtelierSapi" au lieu de "Atelier Sâpi" | Incohérence marque |

---

### 🟠 UX / CONVERSION

| Problème | Observation | Recommandation |
|----------|-------------|----------------|
| **Aucune rassurance visible above the fold** | Pas de "Fait main", "5j fabrication", "Retours 30j" dans la zone de décision | Ajouter un bloc rassurance sous le CTA |
| **CTA "Ajouter au panier" sans urgence** | Bouton orange mais aucun texte de conviction ("Plus que 2 en stock", "Livraison express") | Ajouter micro-copy contextuel |
| **Pas de description produit dans la zone principale** | Titre + options + CTA, mais ZÉRO storytelling | Ajouter 2-3 lignes de pitch sous le titre |
| **Variations déconnectées du prix** | L'utilisateur sélectionne 90cm mais ne sait pas combien ça coûte exactement | Prix dynamique obligatoire |
| **Pas de "Quick Add" ou parcours rapide** | Chaque produit nécessite d'aller sur sa fiche | Lightbox quick-view depuis les grilles |
| **Pas de mini-cart** | Clic "Ajouter" → pas de feedback visuel, pas de slide panel | Ajouter mini-cart avec animation |
| **Tunnel d'achat fragmenté** | 3 clics minimum pour commander | Réduire friction, ajouter "Acheter maintenant" |
| **FAQ en bas de page sans valeur** | Questions génériques, pas liées au produit spécifique | FAQ contextuelle ("Quelle taille pour mon salon ?") |

---

### 🟡 DESIGN / COHÉRENCE VISUELLE

| Problème | Observation | Impact |
|----------|-------------|--------|
| **Hiérarchie visuelle inversée** | Zone produit (vignettes rikiki) << Images lifestyle (100vh chacune) | Le produit est noyé |
| **5+ images lifestyle dont 2 SANS luminaire** | Mur orange + plantes, lit/couette striée → aucun produit visible | Confusion, déconnexion |
| **Typographie plate** | Titre en majuscules mais pas de contraste, pas de Square Peg signature | Manque de personnalité |
| **Pas de section "L'histoire de ce luminaire"** | Aucun storytelling Robin/artisanat | Opportunité manquée |
| **Footer sticky "Ajouter au panier"** | Bonne idée mais prix range au lieu de prix exact | Confusion prix |
| **Densité d'information déséquilibrée** | Zone produit : très dense | Lifestyle : vide | Rythme cassé |
| **Pas de video/GIF du produit allumé** | On vend de la LUMIÈRE mais on ne la montre pas en mouvement | Manque d'impact émotionnel |

---

## BENCHMARK

### CONCURRENTS DIRECTS

| Site | Points forts | À reprendre pour Sapi |
|------|--------------|----------------------|
| **bōlum.fr** | Fiches épurées, galerie grande, prix clair, rassurance visible, storytelling "fait main" | Structure 2 colonnes équilibrée, rassurance sous CTA, pitch produit de 2 lignes |
| **Atelier Loupiote** | Photos produit sur fond neutre, variations claires avec labels, ambiance atelier authentique | Swatches avec labels texte, photos cohérentes (fond uniforme), moins de lifestyle |
| **HAY (hay.dk)** | Minimalisme scandinave, galerie zoomable, specs en tableau, livraison estimée visible | Tableau caractéristiques lisible, estimation livraison dynamique |
| **Ferm Living** | Story produit intégrée, matériaux explicités, upsell élégant | Section "Matériaux & Savoir-faire" avec photo atelier |

### STATE OF THE ART

| Site | Pattern clé | Transfert possible |
|------|-------------|-------------------|
| **Moooi (Awwwards)** | Galerie immersive avec video, storytelling designer, hover sophistiqués | Video du luminaire allumé, section "Mot de Robin" |
| **Vitra** | "Shop the Look" (images cliquables), configurateur visuel | Galerie lifestyle avec hotspots cliquables (comme mockup LUMIÈRE) |
| **Bellroy** | Micro-animations sur variations, GIF des produits, comparateur tailles | GIF 360° du luminaire, animation au changement de taille |
| **Aesop** | Texte poétique, rituel d'achat, packaging comme expérience | Copywriting "sculptural", mention du packaging artisanal |

### PATTERNS TRANSFÉRABLES (8)

1. **Galerie 50/50** : Image principale large + vignettes à droite ou en dessous (≥100px)
2. **Prix dynamique temps réel** : Le prix s'affiche instantanément selon variation sélectionnée
3. **Rassurance sous CTA** : Icônes + texte court (fabrication, livraison, retours)
4. **Pitch 2-3 lignes** : Accroche émotionnelle sous le titre ("La seule araignée que vous voudrez voir descendre du plafond")
5. **Tableau specs** : Dimensions, poids, matériaux, ampoule — lisible, scannable
6. **Section "Artisan"** : Photo Robin + 2 phrases sur la création de ce modèle spécifique
7. **Lifestyle contrôlé** : 2-3 images MAX, toutes montrant le produit, hauteur ≤60vh
8. **Quick View** : Lightbox depuis les grilles pour parcours rapide

---

## RECOMMANDATIONS

### P0 — CRITIQUES (à faire immédiatement)

| Tâche | Section | Pourquoi | Impact | Effort |
|-------|---------|----------|--------|--------|
| Corriger le bug images 57×57px | Related | Actuellement invisibles, cross-sell = 0 | 🔴 Critique | ⚡ 1h |
| Implémenter prix dynamique | Variations | Le client ne sait pas combien il paie | 🔴 Conversion +15-25% | ⚡ 2h |
| Ajouter labels aux swatches | Variations | Impossible de choisir un matériau | 🔴 Conversion | ⚡ 1h |
| Corriger formulaire mobile | Variations | 0% conversion mobile actuellement | 🔴 ~50% du trafic | ⚡ 2h |
| Agrandir vignettes galerie (100px min) | Galerie | UX de base non respectée | 🟠 Perception qualité | ⚡ 30min |

### P1 — IMPORTANTS (sprint 1)

| Tâche | Section | Pourquoi | Impact | Effort |
|-------|---------|----------|--------|--------|
| Ajouter bloc rassurance sous CTA | CTA | "<5j Fabrication · Livraison 48h · Retours 30j" — réduction de la friction | 🟠 Conversion +5-10% | ⚡ 1h |
| Ajouter pitch produit (2-3 lignes) | Hero | Storytelling = émotion = conversion | 🟠 Engagement | ⚡ 30min (copy) |
| Limiter images lifestyle à 2-3 | Galerie | Actuellement 5+ dont certaines sans produit | 🟠 Focus produit | ⚡ 30min |
| Plafonner hauteur lifestyle à 50-60vh | Galerie | Actuellement 100vh = scroll infini | 🟠 Rythme page | ⚡ 30min |
| Supprimer images SANS luminaire | Galerie | Mur orange, lit/couette = confusion | 🟠 Cohérence | ⚡ 15min |
| Ajouter mini-cart slide panel | CTA | Feedback "Ajouté!" + incitation au checkout | 🟠 Conversion | 🔶 4h |
| Créer tableau specs lisible | Specs | Dimensions, matériaux, ampoule — scannable | 🟠 Décision achat | ⚡ 1h |
| Ajouter récapitulatif au checkout | Checkout | Le client ne voit pas ce qu'il commande | 🟠 Abandon -20% | ⚡ 1h |

### P2 — AMÉLIORATIONS (sprint 2+)

| Tâche | Section | Pourquoi | Impact | Effort |
|-------|---------|----------|--------|--------|
| Ajouter section "Mot de Robin" | Storytelling | Différenciation artisan, lien émotionnel | 🟡 Branding | 🔶 2h |
| Ajouter video/GIF luminaire allumé | Galerie | On vend de la LUMIÈRE, il faut la montrer vivante | 🟡 Émotion +++ | 🔶 4h (tournage) |
| Implémenter Quick View lightbox | Grilles | Parcours rapide depuis boutique/catégories | 🟡 UX fluide | 🔶 6h |
| FAQ contextuelle par produit | FAQ | "Quelle taille pour un salon de 20m² ?" | 🟡 Aide décision | 🔶 2h |
| Ajouter hotspots sur lifestyle | Galerie | Cliquer sur le luminaire dans la scène → fiche produit | 🟡 Interaction | 🔴 8h |
| Configurateur visuel (preview) | Variations | Voir le luminaire changer selon taille/matériau sélectionné | 🟡 Premium | 🔴 20h+ |
| Ajouter estimation livraison | Rassurance | "Chez vous le 12 février" selon code postal | 🟡 Conversion | 🔶 4h |
| Corriger copyright footer | Footer | "Atelier Sâpi" au lieu de "testLumineux..." | 🟢 Cohérence | ⚡ 5min |

---

## RÉCAPITULATIF VISUEL

### Structure actuelle vs. Structure recommandée

**ACTUEL (problématique):**
```
┌─────────────────────────────────────────────┐
│ Header                                      │
├─────────────────────────────────────────────┤
│ Breadcrumb                                  │
├──────────────────┬──────────────────────────┤
│ Image principale │ Titre (majuscules)       │
│ (~300px)         │ Prix RANGE (confus)      │
│                  │ Taille: ○ ○ ○            │
│ Vignettes RIKIKI │ Matériau: ● (sans label) │
│ (70px)           │ [AJOUTER AU PANIER]      │
│                  │ Apple Pay / Google Pay   │
├──────────────────┴──────────────────────────┤
│ "La seule qu'on veut voir descendre..."     │
│ Rassurance (perdue ici en bas)              │
├─────────────────────────────────────────────┤
│ 4 pictos Détails                            │
├─────────────────────────────────────────────┤
│ IMAGE LIFESTYLE 100vh (sans produit!)       │
├─────────────────────────────────────────────┤
│ IMAGE LIFESTYLE 100vh (avec produit)        │
├─────────────────────────────────────────────┤
│ IMAGE LIFESTYLE 100vh (sans produit!)       │
├─────────────────────────────────────────────┤
│ IMAGE LIFESTYLE 100vh (avec produit)        │
├─────────────────────────────────────────────┤
│ IMAGE LIFESTYLE 100vh (sans produit!)       │
├─────────────────────────────────────────────┤
│ FAQ                                         │
├─────────────────────────────────────────────┤
│ Produits similaires (IMAGES 57px = BUG)     │
├─────────────────────────────────────────────┤
│ Footer                                      │
└─────────────────────────────────────────────┘
```

**RECOMMANDÉ:**
```
┌─────────────────────────────────────────────┐
│ Header                                      │
├─────────────────────────────────────────────┤
│ Breadcrumb                                  │
├──────────────────┬──────────────────────────┤
│                  │ "Suspension"             │
│ IMAGE PRINCIPALE │ TIMOTHÉE L'ARAIGNÉE      │
│ (grande, ~500px) │                          │
│                  │ "La seule araignée que   │
│                  │  vous voudrez voir..."   │
│                  │                          │
│ VIGNETTES 100px+ │ Prix: 90€ (dynamique)    │
│ [■][■][■][■]     │                          │
│                  │ Taille: [50cm][70cm][90] │
│                  │ Matériau: [Okoumé ●]     │
│                  │           [Peuplier ○]   │
│                  │                          │
│                  │ [+]  1  [-]              │
│                  │                          │
│                  │ [ AJOUTER AU PANIER ]    │
│                  │                          │
│                  │ ────────────────────     │
│                  │ 🔧 <5j Fab · 📦 48h      │
│                  │ ↩️ 30j retours           │
├──────────────────┴──────────────────────────┤
│ DESCRIPTION & SPECS (accordéon/tabs)        │
│ - Description poétique                      │
│ - Caractéristiques (tableau)                │
│ - Installation                              │
├─────────────────────────────────────────────┤
│ MOT DE ROBIN (photo + texte)                │
├─────────────────────────────────────────────┤
│ 2 IMAGES LIFESTYLE (50vh, AVEC produit)     │
│ ┌─────────────┐ ┌─────────────┐             │
│ │             │ │             │             │
│ └─────────────┘ └─────────────┘             │
├─────────────────────────────────────────────┤
│ PRODUITS SIMILAIRES (images normales!)      │
│ ┌───┐ ┌───┐ ┌───┐ ┌───┐                     │
│ │   │ │   │ │   │ │   │                     │
│ └───┘ └───┘ └───┘ └───┘                     │
├─────────────────────────────────────────────┤
│ Footer                                      │
└─────────────────────────────────────────────┘
```

---

## MÉTRIQUES DE SUCCÈS

| Métrique | Actuel (estimé) | Cible post-fix |
|----------|-----------------|----------------|
| Taux de conversion fiche produit | ~1-1.5% | 2.5-3% |
| Taux d'ajout au panier | ~3% | 8-10% |
| Temps sur page | ~45s (scroll images) | ~90s (engagement réel) |
| Scroll depth meaningful | 20% (perdu dans lifestyle) | 60%+ |
| Taux d'abandon checkout | ~75% | ~55% |

---

## SOURCES

- [Awwwards E-Commerce Collection](https://www.awwwards.com/awwwards/collections/e-commerce/)
- [Shopify Product Page Best Practices 2026](https://www.shopify.com/blog/product-page)
- [BigCommerce Product Page Examples](https://www.bigcommerce.com/articles/ecommerce/product-page-examples/)
- Benchmark direct : bōlum.fr, Atelier Loupiote, HAY, Moooi, Ferm Living, Vitra, Bellroy, Aesop

---

*Audit réalisé par Claude | 6 février 2026*
*Pour Atelier Sâpi — theme-sapi-maison*
