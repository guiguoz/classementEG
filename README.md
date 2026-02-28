# 🧭 Escape Game Vikazimut

Escape game géolocalisé en plein air intégré à l'application mobile **Vikazimut**. Les joueurs scannent des balises NFC/QR sur le terrain, chaque balise ouvre une épreuve interactive dans le WebView de l'app.

## 🏗️ Architecture

```
├── S1-accueil.html              # Page d'accueil (création d'équipe)
├── E1-test.html → E4-test.html  # Épreuves de test
├── F1-final.html                # Page de résultats + classement
├── Template .html               # Modèle pour créer de nouvelles épreuves
├── demo2.html                   # Démo de 11 types de jeux disponibles
├── shared/
│   └── common.js                # Fonctions utilitaires partagées
└── api/
    ├── config.php               # Configuration (charge config.local.php)
    ├── config.local.php          # 🔒 Credentials DB (non versionné)
    ├── db.php                   # Connexion PDO MySQL
    ├── utils.php                # CORS, rate limiting, helpers
    ├── challenges.php           # Réponses & indices (côté serveur)
    ├── team.php                 # Inscription, session, vérification
    ├── score.php                # Validation réponses & enregistrement scores
    └── leaderboard.php          # Classement général
```

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

## 🎮 Parcours joueur

1. **S1-accueil** → Le joueur crée son équipe → `Vikazimut.postMessage("1")`
2. **E1 → E4** → Il résout les épreuves aux balises → score enregistré + `postMessage("1")`
3. **F1-final** → Résultats, classement, confettis 🎉

## 🔐 Sécurité

- Validation des réponses **côté serveur** (`api/challenges.php`)
- Calcul des points **côté serveur** (le client ne peut pas tricher)
- Rate limiting (30 req/min/IP)
- CORS restreint au domaine de production
- Credentials DB hors du dépôt Git (`.gitignore`)
- Requêtes préparées PDO (protection injection SQL)
- Échappement HTML des noms d'équipe (anti-XSS)

## 📝 Ajouter une épreuve

1. Dupliquer `Template .html`
2. Modifier `CHALLENGE_ID`, `CHALLENGE_TITLE`, `CHALLENGE_INSTRUCTIONS`
3. Ajouter la réponse dans `api/challenges.php`
4. Ajouter `Vikazimut.postMessage("1")` après validation réussie
5. Déployer sur le serveur

## 🎮 Types de jeux disponibles (voir demo2.html)

| # | Type | Validable serveur |
|---|------|-------------------|
| 1 | QCM | ✅ Oui |
| 2 | Code secret (saisie texte) | ✅ Oui |
| 3 | Objet 3D avec hotspots | ✅ Oui |
| 4 | Panorama 360° gyroscope | ⚠️ Performance |
| 5 | Jeu de mémoire (paires) | ⚠️ Score = performance |
| 6 | Chronologie (drag & drop) | ✅ Oui |
| 7 | Cadenas à combinaison | ✅ Oui |
| 8 | Rush Hour (logique) | ⚠️ Pas de réponse fixe |
| 9 | Vidéo + question | ✅ Oui |
| 10 | Simon (séquence) | ⚠️ Score = performance |
| 11 | Taquin (puzzle glissant) | ⚠️ Score = performance |

## 🛠️ Stack technique

- **Frontend** : HTML/CSS/JS vanilla (zéro dépendance)
- **Backend** : PHP 8.0 + PDO MySQL
- **Hébergement** : IONOS (mutualisé)
- **App mobile** : Vikazimut (WebView + bridge JS)
- **Extras** : Three.js (360°), model-viewer (3D/AR)
