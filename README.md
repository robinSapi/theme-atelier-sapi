# Atelier Sapi - Theme WordPress Custom

Thème WordPress personnalisé pour [atelier-sapi.fr](https://atelier-sapi.fr), boutique e-commerce spécialisée dans les luminaires artisanaux.

## 📋 À propos

Ce thème a été développé from scratch pour remplacer l'ancien thème basé sur Elementor. Il offre :
- Performance optimisée (pas de page builder)
- Code propre et maintenable
- Intégration native WooCommerce
- Design responsive et moderne
- Templates personnalisés pour les pages clés

## 🏗️ Structure du Projet

```
/
├── .github/
│   └── workflows/           # GitHub Actions (déploiement automatique)
├── wp-content/
│   └── themes/
│       └── theme-sapi-maison/
│           ├── assets/      # JavaScript et ressources
│           ├── template-parts/  # Composants réutilisables
│           ├── woocommerce/     # Templates WooCommerce
│           ├── *.php        # Templates WordPress
│           ├── style.css    # Styles principaux
│           ├── functions.php    # Configuration du thème
│           └── screenshot.png   # Prévisualisation thème
├── .gitignore
└── README.md
```

### Ce qui est versionné

- ✅ Le thème custom (`wp-content/themes/theme-sapi-maison/`)
- ❌ WordPress core (wp-admin, wp-includes, etc.)
- ❌ Plugins tiers
- ❌ Uploads
- ❌ Base de données

## 🚀 Installation en Local

### Prérequis

- PHP 7.4+
- MySQL 5.7+ ou MariaDB
- WordPress 6.0+
- WooCommerce 7.0+
- Environnement local (MAMP, Local by Flywheel, XAMPP, etc.)

### Étapes

1. Cloner le repo dans votre installation WordPress locale :
   ```bash
   cd /path/to/wordpress/
   git clone https://github.com/robinSapi/testLumineux-atelier-sapi.git .
   ```

2. Installer WordPress et WooCommerce (si ce n'est pas déjà fait)

3. Activer le thème "Theme Sapi Maison" depuis l'admin WordPress

4. Configurer WooCommerce et importer les produits

## 🔀 Workflow Git

### Branches

- **`master`** : Code de production (protégée)
- **`test-theme-sapi-maison`** : Branche de développement active
- **`feature/*`** : Branches de fonctionnalités ponctuelles

### Workflow de développement

1. Développer sur la branche `test-theme-sapi-maison`
2. Commiter régulièrement avec des messages clairs
3. Pusher sur GitHub :
   ```bash
   git push origin test-theme-sapi-maison
   ```
4. Le déploiement automatique se déclenche vers `testLumineux.atelier-sapi.fr`
5. Tester les changements sur le site de test
6. Quand tout est validé, créer une Pull Request vers `master`

### Commandes Git utiles

```bash
# Vérifier l'état
git status

# Voir les changements
git diff

# Ajouter des fichiers
git add wp-content/themes/theme-sapi-maison/

# Commiter
git commit -m "Description des changements"

# Pusher
git push origin test-theme-sapi-maison

# Voir l'historique
git log --oneline -10
```

## 🚢 Déploiement

### Site de test

- URL : [testLumineux.atelier-sapi.fr](https://testLumineux.atelier-sapi.fr)
- Déploiement : Automatique via GitHub Actions à chaque push sur `test-theme-sapi-maison`
- Serveur : O2Switch

### Site de production

- URL : [atelier-sapi.fr](https://atelier-sapi.fr)
- Déploiement : Automatique via GitHub Actions après merge dans `master` (à activer)
- Serveur : O2Switch

### Configuration GitHub Actions

Les credentials FTP sont stockés dans les secrets GitHub :
- `FTP_SERVER` : ftp.atelier-sapi.fr
- `FTP_USERNAME` : majSite@atelier-sapi.fr
- `FTP_PASSWORD` : [confidentiel]

## 🛠️ Stack Technique

### Backend
- PHP 7.4+
- WordPress 6.0+
- WooCommerce 7.0+

### Frontend
- HTML5 semantic
- CSS3 (Grid, Flexbox, Custom Properties)
- JavaScript Vanilla (minimal)

### Fonts
- Montserrat (400, 500, 600, 700)
- Square Peg (cursive)

### Design System
Voir les variables CSS dans [style.css](wp-content/themes/theme-sapi-maison/style.css:10-31) :
- Palette de couleurs (ocre, orange, crème, etc.)
- Border radius : 5px
- Ombres standardisées
- Breakpoints : 768px, 1024px

## 📁 Fichiers Clés

### Templates Principaux
- [front-page.php](wp-content/themes/theme-sapi-maison/front-page.php) : Page d'accueil (carousel héros + catégories)
- [page.php](wp-content/themes/theme-sapi-maison/page.php) : Template de page générique
- [single.php](wp-content/themes/theme-sapi-maison/single.php) : Article de blog individuel
- [archive.php](wp-content/themes/theme-sapi-maison/archive.php) : Archives (catégories, tags)

### Templates Custom
- [page-conseils-eclaires.php](wp-content/themes/theme-sapi-maison/page-conseils-eclaires.php) : Page "Conseils Éclairés"
- [page-contact.php](wp-content/themes/theme-sapi-maison/page-contact.php) : Page de contact
- [page-lumiere-dartisan.php](wp-content/themes/theme-sapi-maison/page-lumiere-dartisan.php) : Page "Lumière d'Artisan"

### Templates WooCommerce
- [archive-product.php](wp-content/themes/theme-sapi-maison/woocommerce/archive-product.php) : Page boutique/catégorie
- [single-product.php](wp-content/themes/theme-sapi-maison/woocommerce/single-product.php) : Fiche produit
- [taxonomy-product_cat.php](wp-content/themes/theme-sapi-maison/woocommerce/taxonomy-product_cat.php) : Catégorie de produits

### Configuration
- [functions.php](wp-content/themes/theme-sapi-maison/functions.php) : Configuration du thème, hooks, enqueue
- [style.css](wp-content/themes/theme-sapi-maison/style.css) : Styles CSS + en-tête du thème

## 🎨 Customisation

### Modifier les couleurs

Éditer les variables CSS dans [style.css](wp-content/themes/theme-sapi-maison/style.css:10-31) :

```css
:root {
  --sapi-primary: #585858;
  --sapi-secondary: #018501;
  --sapi-orange: #E35B24;
  --sapi-ocre: #937D68;
  /* ... */
}
```

### Ajouter un nouveau template de page

1. Créer un fichier `page-mon-template.php`
2. Ajouter l'en-tête :
   ```php
   <?php
   /*
   Template Name: Mon Template
   */
   get_header();
   ?>
   <!-- Votre contenu ici -->
   <?php get_footer(); ?>
   ```
3. Commiter et pusher

### Modifier le menu

Les menus sont configurables dans l'admin WordPress :
- **Apparence > Menus**
- Deux emplacements : "Menu principal" et "Menu pied de page"

## 📞 Support

- Client : Robin (Atelier Sapi)
- Développeur : Samuel
- Hébergeur : O2Switch
- Repo GitHub : [robinSapi/testLumineux-atelier-sapi](https://github.com/robinSapi/testLumineux-atelier-sapi)

## 📝 Licence

Thème propriétaire développé pour Atelier Sapi. Tous droits réservés.

---

**Version actuelle :** 0.1.0
**Dernière mise à jour :** Janvier 2026
