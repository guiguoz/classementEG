# 🧭 Escape Game Vikazimut

Escape game géolocalisé en plein air intégré à l'application mobile **Vikazimut**. Les joueurs scannent des balises NFC/QR sur le terrain, chaque balise ouvre une épreuve interactive dans le WebView de l'app.

## 🏗️ Architecture

```
├── S1-accueil.html              # Page d'accueil (création d'équipe)
├── E1-test.html → E4-test.html  # Épreuves de test
├── F1-final.html                # Page de résultats + classement
├── sw.js                        # Service Worker (cache offline)
├── shared/
│   ├── style.css                # CSS commun minifié (2.5ko)
│   └── common.js                # Fonctions utilitaires partagées
└── api/
    ├── config.php               # Configuration (charge config.local.php)
    ├── config.local.php         # 🔒 Credentials DB (non versionné)
    ├── db.php                   # Connexion PDO MySQL
    ├── utils.php                # CORS, rate limiting, helpers
    ├── challenges.php           # Réponses & indices (côté serveur)
    ├── team.php                 # Inscription, session, vérification
    ├── score.php                # Validation réponses & enregistrement scores
    └── leaderboard.php          # Classement général
```

## ⚡ Optimisations performances

- **CSS externe** : `shared/style.css` minifié (E1-E4 : -66% poids)
- **Service Worker** : cache offline, 2e chargement instantané (0.1s)
- **Preconnect DNS** : API pré-résolue (-200ms 1er appel)
- **Progressive Web App** : installable, fonctionne hors ligne
- **Architecture optimisée** : 
  - S1-accueil.html : 18.6ko → styles spécifiques inline (modal, hero)
  - E1-E4-test.html : 8.8ko → 3ko (-66% avec CSS externe)
  - F1-final.html : 22.8ko → styles spécifiques inline (celebration, confetti)

### Métriques

| Métrique | Avant | Après |
|---|---|---|
| Poids E1-E4 | 8.8ko | **3ko** (-66%) |
| 1er chargement | 5s | **2s** (-60%) |
| 2e+ chargements | 5s | **0.1s** (cache) |
| Offline | ❌ | ✅ |

## 📱 Intégration Vikazimut

Les pages HTML sont chargées dans le **WebView** de l'app mobile Vikazimut. La communication avec l'app se fait via le bridge JavaScript :

```javascript
// Signaler à l'app que l'étape est validée
if (typeof Vikazimut !== 'undefined') {
    Vikazimut.postMessage("1");  // 1 = réussi, 0 = échoué
}
```

L'app gère la navigation entre les balises — les pages ne font que valider et remonter le résultat. Le bouton "continuer le parcours" est affiché par l'app sous le WebView.

## 🚀 Installation

### 1. Base de données

Déployer `api/test-db.php` une fois pour créer les tables, puis le supprimer :
- `teams` — Équipes (nom, UUID, score total)
- `team_sessions` — Progression (épreuves complétées)
- `challenge_scores` — Scores par épreuve

### 2. Configuration

Créer `api/config.local.php` sur le serveur (non versionné) :

```php
<?php
define('DB_HOST', 'votre-host');
define('DB_PORT', 3306);
define('DB_NAME', 'votre-base');
define('DB_USER', 'votre-user');
define('DB_PASS', 'votre-mot-de-passe');
define('DB_CHARSET', 'utf8mb4');
```

### 3. Déploiement

Uploader tous les fichiers sur le serveur web (PHP 8.0+ avec PDO MySQL).
URL de production : `https://hebergementvikazimut.vikazim.fr/escapegame/`

**Important** : Le Service Worker (`sw.js`) nécessite HTTPS pour fonctionner.

## 🎮 Parcours joueur

1. **S1-accueil** → Le joueur crée son équipe → `Vikazimut.postMessage("1")`
2. **E1 → E4** → Il résout les épreuves aux balises → score enregistré + `postMessage("1")`
3. **F1-final** → Résultats, classement, confettis 🎉

### Reprise d'aventure

- Le système vérifie si **toutes** les épreuves (`T1`, `T2`, `T3`, `T4`) sont complétées
- Permet de reprendre en cours : ordre libre des épreuves supporté
- Réinitialisation automatique seulement si 100% terminé

## 🔐 Sécurité

- Validation des réponses **côté serveur** (`api/challenges.php`)
- Calcul des points **côté serveur** (le client ne peut pas tricher)
- Rate limiting (30 req/min/IP)
- CORS restreint au domaine de production
- Credentials DB hors du dépôt Git (`.gitignore`)
- Requêtes préparées PDO (protection injection SQL)
- Échappement HTML des noms d'équipe (anti-XSS)

## 📝 Ajouter une épreuve

1. Dupliquer un fichier E1-E4 existant
2. Modifier `CHALLENGE_ID` (ex: `'T5'`)
3. Ajouter la réponse dans `api/challenges.php`
4. Ajouter `'T5'` dans `ALL_CHALLENGES` de `S1-accueil.html`
5. Ajouter le fichier dans `sw.js` pour le cache offline
6. Ajouter `Vikazimut.postMessage("1")` après validation réussie
7. Déployer sur le serveur

## 🛠️ Stack technique

- **Frontend** : HTML/CSS/JS vanilla (zéro dépendance npm)
- **Backend** : PHP 8.0 + PDO MySQL
- **PWA** : Service Worker, cache API, offline-ready
- **Hébergement** : IONOS (mutualisé)
- **App mobile** : Vikazimut (WebView + bridge JS)
- **Performance** : CSS minifié, preconnect DNS, lazy loading

## 🐛 Debug

### Service Worker ne s'active pas
- Vérifier HTTPS (requis)
- Console → Application → Service Workers
- `navigator.serviceWorker.getRegistrations()` dans console

### CSS non chargé
- Vérifier chemin relatif : `shared/style.css` (pas `/shared/`)
- Network tab : status 200 pour style.css

### API timeout
- Vérifier `hebergementvikazimut.vikazim.fr` accessible
- Console → erreurs CORS
- Tester endpoints directement dans navigateur

## 📊 Logs & Monitoring

- **Erreurs client** : Console navigateur (F12)
- **Erreurs serveur** : PHP error logs (hosting panel)
- **Performance** : Lighthouse audit (Chrome DevTools)

---

**Développé pour Vikazimut** | Optimisé Feb 2026
