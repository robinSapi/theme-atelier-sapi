# Guide de Déploiement - Atelier Sapi

Ce guide explique comment configurer et utiliser le déploiement automatique via GitHub Actions vers les serveurs O2Switch.

## 🔐 Configuration des Secrets GitHub

Les informations FTP sensibles sont stockées dans les secrets GitHub pour des raisons de sécurité.

### Étapes de configuration

1. **Aller sur GitHub**
   - Accédez à votre repo : [github.com/robinSapi/testLumineux-atelier-sapi](https://github.com/robinSapi/testLumineux-atelier-sapi)

2. **Ouvrir les Settings**
   - Cliquez sur l'onglet **Settings** (en haut à droite)

3. **Accéder aux Secrets**
   - Dans le menu latéral gauche, cliquez sur **Secrets and variables**
   - Puis cliquez sur **Actions**

4. **Ajouter les secrets FTP**
   - Cliquez sur **New repository secret**
   - Ajoutez les **6 secrets** suivants (3 pour le test + 3 pour la production) :

### Secrets pour le Site de TEST (testLumineux)

#### Secret 1 : FTP_SERVER_TEST
- **Name:** `FTP_SERVER_TEST`
- **Secret:** `ftp.velu1541.odns.fr`
- Cliquez sur **Add secret**

#### Secret 2 : FTP_USERNAME_TEST
- **Name:** `FTP_USERNAME_TEST`
- **Secret:** `majSiteTest@testlumineux.atelier-sapi.fr`
- Cliquez sur **Add secret**

#### Secret 3 : FTP_PASSWORD_TEST
- **Name:** `FTP_PASSWORD_TEST`
- **Secret:** [Mot de passe du compte majSiteTest - à définir dans cPanel]
- Cliquez sur **Add secret**

### Secrets pour le Site de PRODUCTION (atelier-sapi.fr)

#### Secret 4 : FTP_SERVER_PROD
- **Name:** `FTP_SERVER_PROD`
- **Secret:** `ftp.velu1541.odns.fr`
- Cliquez sur **Add secret**

#### Secret 5 : FTP_USERNAME_PROD
- **Name:** `FTP_USERNAME_PROD`
- **Secret:** `majSite@atelier-sapi.fr`
- Cliquez sur **Add secret**

#### Secret 6 : FTP_PASSWORD_PROD
- **Name:** `FTP_PASSWORD_PROD`
- **Secret:** [Mot de passe du compte majSite - à définir dans cPanel]
- Cliquez sur **Add secret**

> **⚠️ Important :** Les secrets ne seront jamais visibles après leur création (c'est normal et voulu pour la sécurité).

## 🚀 Workflows Disponibles

### 1. Déploiement Test (Automatique)

**Fichier :** [.github/workflows/deploy-test.yml](.github/workflows/deploy-test.yml)

**Déclenché par :**
- Push sur la branche `test-theme-sapi-maison`
- Manuellement depuis l'interface GitHub

**Destination :**
- Serveur : `testLumineux.atelier-sapi.fr`
- Chemin : `/testLumineux.atelier-sapi.fr/`

**Utilisation :**
```bash
# Développer localement
git add .
git commit -m "Fix: correction du header"
git push origin test-theme-sapi-maison

# Le déploiement démarre automatiquement !
```

**Suivre le déploiement :**
1. Aller sur GitHub : onglet **Actions**
2. Voir le workflow en cours d'exécution
3. Cliquer dessus pour voir les logs détaillés

### 2. Déploiement Production (Manuel)

**Fichier :** [.github/workflows/deploy-prod.yml](.github/workflows/deploy-prod.yml)

**Déclenché par :**
- Manuellement uniquement (pour éviter les accidents)

**Destination :**
- Serveur : `atelier-sapi.fr`
- Chemin : `/atelier-sapi.fr/`

**Utilisation :**
1. Merger votre PR dans `master`
2. Aller sur GitHub : onglet **Actions**
3. Sélectionner le workflow **Deploy to Production**
4. Cliquer sur **Run workflow**
5. Confirmer le déploiement

> **⚠️ Attention :** Ce workflow déploie sur le site de production visible par les clients. Ne l'utilisez qu'après avoir testé sur `testLumineux`.

## 📋 Checklist de Déploiement

### Avant chaque déploiement

- [ ] J'ai testé mes changements en local
- [ ] Le code ne contient pas d'erreurs PHP
- [ ] Les styles CSS s'affichent correctement
- [ ] Le site est responsive (mobile, tablette, desktop)
- [ ] J'ai commité avec un message clair

### Déploiement Test

- [ ] Push sur `test-theme-sapi-maison`
- [ ] Vérifier que le workflow GitHub Actions se termine avec succès
- [ ] Tester sur `testLumineux.atelier-sapi.fr`
- [ ] Vérifier les fonctionnalités WooCommerce (panier, fiche produit, etc.)
- [ ] Faire valider par Robin si nécessaire

### Déploiement Production

- [ ] Tout est validé sur le site de test
- [ ] Créer une Pull Request vers `master`
- [ ] Review du code
- [ ] Merge dans `master`
- [ ] Déclencher manuellement le workflow de production
- [ ] Vérifier `atelier-sapi.fr` immédiatement après

## 🔍 Monitoring et Logs

### Voir les logs de déploiement

1. GitHub > onglet **Actions**
2. Cliquer sur le workflow en cours ou terminé
3. Cliquer sur **deploy** pour voir les détails
4. Sections disponibles :
   - **Checkout code** : Récupération du code
   - **Deploy to O2Switch via FTP** : Upload FTP

### En cas d'erreur

Les erreurs courantes et leurs solutions :

#### Erreur : "Connection refused"
- **Cause :** Mauvaises credentials FTP ou serveur inaccessible
- **Solution :** Vérifier les secrets GitHub (FTP_SERVER, FTP_USERNAME, FTP_PASSWORD)

#### Erreur : "Permission denied"
- **Cause :** Droits insuffisants sur le serveur
- **Solution :** Vérifier les permissions du dossier sur O2Switch (755 recommandé)

#### Erreur : "File not found"
- **Cause :** Chemin de destination incorrect
- **Solution :** Vérifier le `server-dir` dans le workflow (doit correspondre au chemin sur O2Switch)

#### Déploiement lent
- **Cause :** Beaucoup de fichiers à uploader
- **Solution :** Normal lors du premier déploiement. Les suivants seront plus rapides (upload incrémental).

## 🛠️ Maintenance

### Modifier les credentials FTP

Si vous changez de mot de passe FTP :

1. GitHub > Settings > Secrets and variables > Actions
2. Cliquer sur le secret `FTP_PASSWORD`
3. Cliquer sur **Update secret**
4. Entrer le nouveau mot de passe
5. Sauvegarder

### Désactiver temporairement le déploiement auto

Si vous voulez pusher sans déclencher de déploiement :

**Option 1 : Désactiver le workflow**
- GitHub > Actions > Deploy to Test Server
- Cliquer sur les 3 points ⋮
- **Disable workflow**

**Option 2 : Travailler sur une branche feature**
```bash
git checkout -b feature/mon-test
git push origin feature/mon-test
# Pas de déploiement car la branche ne correspond pas
```

### Activer le déploiement automatique en production

Quand vous serez prêt à déployer automatiquement vers la production :

1. Éditer [.github/workflows/deploy-prod.yml](.github/workflows/deploy-prod.yml)
2. Décommenter les lignes :
   ```yaml
   on:
     push:
       branches:
         - master
     workflow_dispatch:
   ```
3. Commiter et pusher

> **⚠️ Attention :** À n'activer qu'une fois le thème complètement finalisé et testé !

## 📊 Structure de Déploiement

```
GitHub Repository (testLumineux-atelier-sapi)
│
├── Branch: test-theme-sapi-maison
│   └── Push → Deploy to testLumineux.atelier-sapi.fr/
│       └── Upload: wp-content/themes/theme-sapi-maison/
│
└── Branch: master
    └── Manual trigger → Deploy to atelier-sapi.fr/
        └── Upload: wp-content/themes/theme-sapi-maison/
```

## 🔗 Liens Utiles

- **GitHub Repository :** [github.com/robinSapi/testLumineux-atelier-sapi](https://github.com/robinSapi/testLumineux-atelier-sapi)
- **Site de test :** [testLumineux.atelier-sapi.fr](https://testLumineux.atelier-sapi.fr)
- **Site de production :** [atelier-sapi.fr](https://atelier-sapi.fr)
- **Panel O2Switch :** [atelier-sapi.fr:2222](https://atelier-sapi.fr:2222) (cPanel)

## 📞 Support

En cas de problème avec le déploiement :

1. Vérifier les logs dans GitHub Actions
2. Vérifier les credentials FTP dans les secrets
3. Vérifier que le serveur O2Switch est accessible
4. Contacter le support O2Switch si nécessaire

---

**Dernière mise à jour :** Janvier 2026
