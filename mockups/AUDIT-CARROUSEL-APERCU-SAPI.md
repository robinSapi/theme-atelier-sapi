# AUDIT : Carrousel Éditorial & Aperçu Rapide
## testlumineux.atelier-sapi.fr

**Date :** 7 février 2026  
**Pages testées :** /nos-creations/, /categorie-produit/lampadaire/, /categorie-produit/suspension/

---

## 📊 RÉSUMÉ DES ÉCARTS

| Fonctionnalité | Spécification | État actuel | Priorité |
|----------------|---------------|-------------|----------|
| Vignettes multiples (28% largeur) | 3+ cartes visibles côte à côte | **1 seule carte visible** | 🔴 P0 |
| Mini-carrousel thumbnails | Sous le carrousel principal | ✅ Présent (catégories) / ❌ Absent (/nos-creations/) | 🔴 P0 |
| Bouton "Aperçu" au hover | Icône œil + texte visible au survol | **Existe mais invisible** (seulement au hover réel) | 🟠 P1 |
| Layout modal 2 colonnes | Image gauche, infos droite | **Layout vertical** (image en haut, infos en bas) | 🟠 P1 |
| Description courte modal | Texte de présentation | ❌ **Absente** | 🔴 P0 |
| Variantes dans modal | Tailles + Essences (encadré crème) | ❌ **Absentes** | 🔴 P0 |
| Flèches "organiques/voluptueuses" | Forme complexe Patricia Urquiola | **Boutons ronds simples** | 🟡 P2 |
| Superposition cartes (-80px) | Chevauchement visible | ✅ Léger chevauchement visible | ✅ |
| Compteur "X / Y" | Présent | ✅ Fonctionne | ✅ |
| Barre de progression auto-advance | Ligne qui se remplit en 3s | ✅ Visible dans modal | ✅ |

---

## 🔴 ÉCARTS CRITIQUES (P0)

### 1. Une seule carte visible au lieu de plusieurs
**Spec :** "Vignettes : 28% de largeur, espacement de 24px entre elles"  
**Réalité :** Le carrousel affiche **1 seule carte à la fois** en pleine largeur  
**Impact :** L'utilisateur ne voit qu'un produit, réduisant la découvrabilité

### 2. Mini-carrousel absent sur /nos-creations/
**Spec :** "Mini-carrousel de vignettes situé sous le carrousel principal"  
**Réalité :** 
- ✅ Présent sur /categorie-produit/lampadaire/ et /suspension/
- ❌ **Absent sur /nos-creations/** (page principale !)  
**Impact :** Incohérence UX entre les pages

### 3. Description courte absente dans la modal Aperçu
**Spec :** "Description courte - Texte de présentation extrait de la fiche produit"  
**Réalité :** Aucune description visible dans la modal  
**Impact :** L'utilisateur n'a pas d'info sur le produit sans ouvrir la fiche complète

### 4. Variantes absentes dans la modal Aperçu
**Spec :** "Variantes disponibles (encadré crème) - Tailles disponibles : S, M, L, XL / Essences disponibles : Chêne, Noyer, Frêne..."  
**Réalité :** Aucune information sur les variantes dans la modal  
**Impact :** L'utilisateur ne sait pas les options disponibles

---

## 🟠 ÉCARTS IMPORTANTS (P1)

### 5. Layout modal vertical au lieu de 2 colonnes
**Spec :** "Partie Gauche - Galerie Photos / Partie Droite - Informations Produit"  
**Réalité :** Layout vertical (image en haut, infos en bas)  
**Impact :** Modal moins efficace, scroll nécessaire sur desktop

### 6. Bouton "Aperçu" invisible sans hover réel
**Spec :** "Bouton 'Aperçu' apparaît au survol de chaque vignette - Icône œil + texte 'Aperçu'"  
**Réalité :** Le bouton existe dans le DOM mais n'est visible qu'au hover CSS réel  
**Impact :** Utilisateurs tactiles ne voient jamais le bouton

---

## 🟡 ÉCARTS MINEURS (P2)

### 7. Flèches de navigation non "organiques"
**Spec :** "Forme 'voluptueuse' avec border-radius complexe" style Patricia Urquiola  
**Réalité :** Boutons ronds/ovales simples  
**Impact :** Perte de la signature visuelle

### 8. Badges produits non vérifiés
**Spec :** "Badges 'Promo' / 'Nouveau' / 'Signature' en haut à gauche"  
**Réalité :** Non visible dans les tests (peut-être pas de produits concernés)  
**À vérifier :** Ajouter des produits avec ces attributs pour tester

---

## ✅ CE QUI FONCTIONNE

| Fonctionnalité | État |
|----------------|------|
| Navigation prev/next | ✅ Fonctionne |
| Compteur "X / Y" | ✅ Présent et mis à jour |
| Clic sur vignettes mini-carrousel | ✅ Change le produit actif |
| Modal s'ouvre au clic Aperçu | ✅ Fonctionne |
| Galerie photos dans modal | ✅ Navigation fonctionnelle |
| Barre de progression | ✅ Visible |
| Bouton "Voir fiche complète" | ✅ Présent et cliquable |
| Prix affiché dans modal | ✅ "À partir de X € – Y €" |
| Titre produit dans modal | ✅ Affiché |
| Bouton fermer modal | ✅ Fonctionne |

---

## 📝 PAGES TESTÉES - DÉTAILS

### /nos-creations/
- Carrousel : 1 carte visible
- Compteur : "1 / 29"
- Mini-carrousel : ❌ **ABSENT**
- Navigation : ✅ Fonctionne

### /categorie-produit/lampadaire/
- Carrousel : 1 carte visible
- Compteur : "1 / 5"
- Mini-carrousel : ✅ **PRÉSENT** (grille de vignettes)
- Navigation : ✅ Fonctionne

### /categorie-produit/suspension/
- Carrousel : 1 carte visible
- Compteur : "1 / 11"
- Mini-carrousel : ✅ **PRÉSENT** (grille de vignettes)
- Filtres : ✅ PRIX, ESSENCE, DIMENSIONS
- Navigation : ✅ Fonctionne

---

*Audit réalisé par Claude | 7 février 2026*
