# Release Guide (Test → Prod)

Ce guide décrit le workflow retenu :
- **Test** = branche `test-theme-sapi-maison` (push direct)
- **Prod** = branche `master` (PR + merge + déploiement manuel)

## 1) Workflow quotidien (site de test)

1. Travailler localement sur `test-theme-sapi-maison`
2. Commit clair
3. Push → déploiement auto vers test

```bash
git checkout test-theme-sapi-maison
git pull

# modifications...
git add .
git commit -m "Fix: icônes homepage"
git push origin test-theme-sapi-maison
```

## 2) Rollback sur le site de test

Si une version casse quelque chose, revenir au commit stable :

```bash
git log --oneline
git reset --hard <COMMIT_SHA>
git push --force origin test-theme-sapi-maison
```

> Le site test sera remis **exactement** à l’état du commit choisi.

## 3) Validation → passage en production

Quand le test est validé :

1. Créer une PR **test-theme-sapi-maison → master**
2. Vérifier la PR
3. Merge
4. Lancer le workflow **Deploy to Production** (manuel)

## 4) Tag de version (recommandé)

Pour garder un historique clair des versions validées :

```bash
git tag -a v1.0.0 -m "Version validée"
git push origin v1.0.0
```

## 5) Rappels importants

- Ne jamais push directement sur `master`
- Toujours tester sur `testlumineux` avant prod
- Un commit = une version
- Le rollback ne touche **pas** la base de données

