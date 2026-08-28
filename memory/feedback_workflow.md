---
name: Workflow et préférences Robin
description: Commit/push auto, proposer avant de modifier, pattern slider mobile, déploiement O2switch
type: feedback
---

## Rôle de tasks.md (CRITIQUE)
`tasks.md` sert UNIQUEMENT à transmettre des tâches à Claude Code. Ne jamais y écrire des tâches projet/backlog.
- **Nouvelles tâches projet** → les ajouter dans `MEMORY.md` (section "Priorités") + fichier `project_*.md` concerné
- **Tâches pour Claude Code** → les écrire dans `tasks.md` uniquement quand on est prêt à les faire exécuter

**Why:** Robin a corrigé une confusion où Cowork écrivait des tâches projet dans tasks.md au lieu de MEMORY.md.

---

## Workflow Préféré de Robin
- **Commit/Push automatique** : TOUJOURS commit et push après chaque modification, SANS demander confirmation
- **TOUJOURS proposer avant de modifier** : Décrire les changements prévus et attendre validation AVANT de modifier le code
- Branche de travail par défaut : `master`
- **⚠️ TOUJOURS demander à Robin dans quelle branche travailler avant de commencer** — plusieurs branches peuvent être actives en parallèle. Consulter `memory/branches.md` pour l'état des branches.
- Workflow : Local → GitHub (auto-deploy) → test.atelier-sapi.fr

## Déploiement O2switch
- GitHub Actions (`deploy-test.yml`) → FTP Deploy vers O2switch
- Compte FTP : `deployTest@test.atelier-sapi.fr` — secrets GitHub : `FTP_USERNAME_TEST`, `FTP_PASSWORD_TEST`, `FTP_SERVER_TEST`
- Si Actions en panne : pull manuel via SSH cPanel :
```bash
cd ~/test.atelier-sapi.fr/wp-content/themes/theme-sapi-maison && git pull
```

## Pattern Slider Mobile (scroll-snap + dots)
- **Container** : `flex`, `flex-wrap: nowrap`, `overflow-x: auto`, `scroll-snap-type: x mandatory`, `-webkit-overflow-scrolling: touch`, `scrollbar-width: none`
- **Padding** : `padding: 15px 5%`
- **Si parent a du padding** : `margin: 0 -Xrem` + `width: calc(100% + 2*Xrem)`
- **Enfants** : `flex: 0 0 90%`, `scroll-snap-align: center`, **Gap** : `1rem`
- **Scrollbar masquée** : `::-webkit-scrollbar { display: none; }`
- **Dots** : Config dans `assets/scroll-dots.js` tableau `sections[]`, refresh via `window.scrollDotsRefresh()`
