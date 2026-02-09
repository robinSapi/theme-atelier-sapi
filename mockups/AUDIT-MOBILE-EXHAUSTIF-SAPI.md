# AUDIT MOBILE EXHAUSTIF — ATELIER SAPI
## testlumineux.atelier-sapi.fr | Viewport 390×844 (iPhone 14)

**Date :** 7 février 2026  
**Auditeur :** Claude (Expert UX/UI)  
**Méthode :** Navigation live via Chrome mobile viewport

---

## 🚨 RÉSUMÉ EXÉCUTIF

| Niveau | Nombre | Impact |
|--------|--------|--------|
| **P0 - BLOQUANTS** | 6 | 🔴 Impossible de commander |
| **P1 - CRITIQUES** | 8 | 🟠 Conversion fortement impactée |
| **P2 - IMPORTANTS** | 7 | 🟡 UX dégradée |
| **P3 - MINEURS** | 4 | 🟢 Polish |

**VERDICT : Le site est actuellement NON FONCTIONNEL pour l'achat.**  
Le tunnel de commande est entièrement cassé (toutes les URLs en 404), et plusieurs bugs critiques sur la fiche produit empêchent une conversion normale.

---

## 🔴 P0 — BUGS BLOQUANTS (Conversion = 0%)

### 1. TUNNEL D'ACHAT ENTIÈREMENT CASSÉ
| URL testée | Résultat |
|------------|----------|
| `/panier/` | ❌ 404 "Page introuvable" |
| `/checkout/` | ❌ 404 "Page introuvable" |
| `/commande/` | ❌ 404 "Page introuvable" |
| `/finaliser-commande/` | ❌ 404 "Page introuvable" |
| `/cart/` | ❌ Redirige vers page produit |

**Impact :** AUCUNE VENTE POSSIBLE  
**Cause probable :** Pages WooCommerce non créées ou permalinks mal configurés

---

### 2. MINI-CART SANS BOUTON CHECKOUT
Le slide panel "VOTRE PANIER" s'ouvre mais :
- ❌ Pas de bouton "Voir le panier"
- ❌ Pas de bouton "Commander"
- ❌ Pas de total visible
- ❌ Impossible d'aller au checkout depuis le mini-cart

**Impact :** Même si les URLs fonctionnaient, l'utilisateur ne peut pas y accéder

---

### 3. PRIX NON VISIBLE SUR FICHE PRODUIT
Sur la page "Claudine La turbine" :
- Le prix n'apparaît PAS dans la zone produit principale
- Le prix (135€ - 200€) n'est visible QUE dans le footer sticky APRÈS scroll
- L'utilisateur ne connaît pas le prix avant de configurer

**Impact :** Abandon par manque de transparence tarifaire

---

### 4. SWATCHES MATÉRIAU SANS LABELS
Les pastilles de sélection de matériau :
- ❌ Aucun texte "Okoumé" ou "Peuplier"
- ❌ Juste des cercles de couleur indistincts
- ❌ Impossible de savoir ce qu'on sélectionne

**Impact :** Impossible de choisir consciemment un matériau

---

### 5. BOUTON VIDE/FANTÔME ENTRE APPLE PAY ET "ACHETER"
Sur la fiche produit, entre le bouton Apple Pay et "ACHETER MAINTENANT" :
- Un bouton gris/blanc VIDE apparaît
- Aucun texte, aucune fonction visible
- Bug d'affichage manifeste

**Impact :** Perception de site amateur/cassé

---

### 6. COULEUR CTA "AJOUTER AU PANIER" INCORRECTE
Le bouton principal n'est PAS orange (#E35B24) comme la charte :
- Couleur actuelle : marron/violet terne
- Incohérence avec les autres CTAs du site
- Moins visible, moins cliquable

**Impact :** Réduction du taux de clic CTA

---

## 🟠 P1 — BUGS CRITIQUES (Conversion -50%)

### 7. PLACEHOLDER "PHOTO ATELIER" AU LIEU D'IMAGE
Section "Fabriqué avec passion" :
- Affiche une icône d'œil avec texte "Photo atelier"
- Pas de vraie photo de Robin/atelier
- Rupture de confiance visuelle

---

### 8. PRIX PRODUIT EN RANGE CONFUS
Affichage : "À PARTIR DE 135,00 € – 200,00 €"
- Redondant et confus
- "À partir de" + range = double information
- Devrait afficher le prix exact selon variation sélectionnée

---

### 9. CONTRASTE TEXTE QUASI INVISIBLE (HOMEPAGE)
Plusieurs sections de la homepage ont un texte à peine lisible :
- Section "100% / <5j / Lyon" : texte crème sur fond crème
- Certaines images de fond avec texte superposé illisible
- Non conforme WCAG AA

---

### 10. VIGNETTES GALERIE TROP PETITES
Sur fiche produit :
- Vignettes ~60px de large
- Difficile de distinguer les différentes vues
- Minimum recommandé : 80-100px

---

### 11. TITRE ONGLET AVEC "testLumineuxAtelierSapi"
- Title tag : "Claudine La turbine - testLumineuxAtelierSapi"
- Devrait être : "Claudine La turbine | Atelier Sâpi"
- Perception de site non fini

---

### 12. FORMULAIRE TAILLE : RADIO BUTTONS NON SÉLECTIONNÉS PAR DÉFAUT
Options "65 cm" et "85 cm" :
- Aucune option pré-sélectionnée
- L'utilisateur doit deviner qu'il faut cliquer
- Devrait avoir une option par défaut

---

### 13. COLLECTION "LAMPADAIRES" - IMAGE SANS PRODUIT
Sur la homepage, la card "LAMPADAIRES" :
- L'image montre un mur/sol, pas de lampadaire visible
- Incohérence avec les autres cards qui montrent les produits

---

### 14. PRIX ABERRANT DANS LE PANIER : 1200€
Un produit "Oliviaa La gardiena - 75 cm, Peuplier" affiché à 1200,00 € :
- Les autres variations sont à 145€
- Erreur de prix probable dans WooCommerce
- Risque de commande à prix erroné

---

## 🟡 P2 — PROBLÈMES IMPORTANTS (UX Dégradée)

### 15. HEADER STICKY DOUBLE-BARRE
La barre de rassurance "Livraison 48-72h | Fabrication <5j | Retours 30j" :
- Reste fixe en haut
- Prend de l'espace vertical précieux sur mobile
- Empêche de voir le contenu

---

### 16. PAS DE FEEDBACK "AJOUTÉ AU PANIER"
Quand on clique "AJOUTER AU PANIER" :
- Pas d'animation de confirmation
- Pas de notification toast
- L'utilisateur ne sait pas si ça a fonctionné

---

### 17. QUANTITÉ : CHAMP TEXTE MINUSCULE
Le sélecteur de quantité :
- Très petit (~40px)
- Difficile à manipuler au doigt
- Devrait être plus grand avec boutons +/-

---

### 18. SCROLL INFINI SUR HOMEPAGE
Sections qui se succèdent sans fin claire :
- Hero → Quote → Section 100% → Atelier → Collections → Newsletter → Footer
- Pas de hiérarchie visuelle claire des sections

---

### 19. ESPACEMENT INCOHÉRENT
Certaines sections ont des margins énormes, d'autres sont collées :
- Après la quote Robin : trop d'espace blanc
- Avant les collections : espacement correct
- Incohérence de rythme

---

### 20. PAS DE BREADCRUMB CLICKABLE COMPLET
Breadcrumb "Accueil / Lampadaire / Claudine La turbine" :
- Les liens semblent fonctionner
- Mais le style est peu visible (texte gris petit)

---

### 21. FILTRES CATÉGORIE : TROP DE PILLS
Sur /nos-creations/ :
- 7 pills de catégorie + 3 dropdowns de filtres
- Prend 4 lignes sur mobile
- Devrait être dans un menu déroulant ou accordion

---

## 🟢 P3 — PROBLÈMES MINEURS (Polish)

### 22. FAVICON MANQUANT OU GÉNÉRIQUE
- Pas de favicon personnalisé visible
- Devrait avoir le logo Sâpi

---

### 23. NUMÉROTATION SECTIONS (01, 02, 03...)
Utile pour la structure mais :
- Style visuel peu intégré
- Pourrait être plus élégant

---

### 24. ANIMATIONS DE SCROLL ABSENTES
- Pas d'animations d'apparition au scroll
- Le site paraît statique
- Opportunité d'ajouter des micro-animations

---

### 25. FORMAT PRIX AVEC ESPACES
- "135,00 €" est correct
- Mais certains affichent "1200,00 €" sans espace milliers
- Devrait être "1 200,00 €" si vraiment ce prix

---

## 📊 SYNTHÈSE PAR PAGE

### HOMEPAGE
| Aspect | Note | Commentaire |
|--------|------|-------------|
| Structure | 🟡 6/10 | Trop longue, hiérarchie confuse |
| Design | 🟢 7/10 | Cohérent avec la marque |
| Lisibilité | 🔴 4/10 | Contraste insuffisant par endroits |
| Performance | 🟡 6/10 | Images lourdes |

### PAGE CATÉGORIE
| Aspect | Note | Commentaire |
|--------|------|-------------|
| Structure | 🟢 7/10 | Filtres fonctionnels |
| Design | 🟢 7/10 | Cards produits claires |
| Navigation | 🟡 6/10 | Trop de filtres visibles |
| CTA | 🟢 8/10 | Boutons "Découvrir" visibles |

### FICHE PRODUIT
| Aspect | Note | Commentaire |
|--------|------|-------------|
| Galerie | 🟡 6/10 | Vignettes trop petites |
| Infos produit | 🔴 3/10 | Prix caché, swatches sans labels |
| CTA | 🔴 4/10 | Couleur incorrecte, bouton fantôme |
| Rassurance | 🟢 8/10 | Bloc rassurance bien placé |
| Storytelling | 🟢 8/10 | Sections "Pourquoi", "Robin", avis |

### PANIER/CHECKOUT
| Aspect | Note | Commentaire |
|--------|------|-------------|
| Accessibilité | 🔴 0/10 | PAGES EN 404 |
| Mini-cart | 🔴 2/10 | Pas de bouton checkout |
| Tunnel | 🔴 0/10 | INEXISTANT |

---

## 🎯 PLAN D'ACTION PRIORISÉ

### SEMAINE 1 — URGENCE ABSOLUE
| # | Tâche | Impact |
|---|-------|--------|
| 1 | **Créer/réparer pages panier + checkout** | 🔴 Sans ça = 0 vente |
| 2 | **Ajouter boutons au mini-cart** (Voir panier / Commander) | 🔴 Tunnel bloqué |
| 3 | **Afficher le prix dans la zone produit** (pas juste sticky footer) | 🔴 Confiance |
| 4 | **Ajouter labels aux swatches matériau** | 🔴 Choix impossible |
| 5 | **Supprimer le bouton fantôme** entre Apple Pay et Acheter | 🟠 Perception cassée |
| 6 | **Corriger couleur CTA** → Orange #E35B24 | 🟠 Cohérence |

### SEMAINE 2 — CRITIQUE
| # | Tâche | Impact |
|---|-------|--------|
| 7 | Ajouter vraie photo atelier (section Robin) | 🟠 Confiance |
| 8 | Corriger prix en range → prix dynamique | 🟠 Clarté |
| 9 | Améliorer contraste texte homepage | 🟠 Accessibilité |
| 10 | Agrandir vignettes galerie (80px min) | 🟠 UX |
| 11 | Corriger title tags | 🟠 SEO + perception |
| 12 | Vérifier prix 1200€ aberrant | 🟠 Risque financier |

### SEMAINE 3-4 — IMPORTANT
| # | Tâche | Impact |
|---|-------|--------|
| 13 | Pré-sélectionner une taille par défaut | 🟡 UX |
| 14 | Corriger image collection Lampadaires | 🟡 Cohérence |
| 15 | Ajouter feedback "Ajouté au panier" | 🟡 Confirmation |
| 16 | Optimiser espace sticky header | 🟡 Viewport |
| 17 | Regrouper filtres en accordion | 🟡 Mobile UX |

### PLUS TARD — POLISH
| # | Tâche | Impact |
|---|-------|--------|
| 18 | Ajouter favicon personnalisé | 🟢 Branding |
| 19 | Animations scroll | 🟢 Premium feel |
| 20 | Harmoniser espacements | 🟢 Rythme |

---

## 📸 PREUVES VISUELLES

Les captures d'écran ont été prises en live pendant l'audit :
- Homepage scroll complet
- Page catégorie avec filtres
- Fiche produit "Claudine La turbine"
- Mini-cart ouvert
- Pages 404 (panier, checkout, commande)

---

## CONCLUSION

**Le site testlumineux.atelier-sapi.fr est actuellement INUTILISABLE pour la vente.**

Les 3 problèmes les plus urgents :
1. **Tunnel d'achat cassé** — aucune page panier/checkout accessible
2. **Mini-cart sans issue** — impossible de finaliser une commande
3. **Prix invisible** — l'utilisateur ne voit pas ce qu'il va payer

Avant tout travail de design ou d'optimisation, **il faut d'abord que le site permette d'acheter.**

---

*Audit réalisé par Claude | 7 février 2026*  
*Pour Atelier Sâpi — testlumineux.atelier-sapi.fr*
