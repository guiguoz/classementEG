-- Schéma de base de données pour Escape Game Vikazimut
-- Cible: dbs15374126
-- Crée les tables: teams, challenge_scores, team_sessions
-- Assure l'unicité d'une épreuve par équipe via UNIQUE(team_id, challenge_id)

-- Créer la base si nécessaire (optionnel)
CREATE DATABASE IF NOT EXISTS dbs15374126 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dbs15374126;

-- Table des équipes
CREATE TABLE IF NOT EXISTS teams (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  uuid CHAR(36) NOT NULL,
  game_id VARCHAR(20) NOT NULL DEFAULT 'archi',
  total_score INT NOT NULL DEFAULT 0,
  is_finished TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_teams_uuid (uuid),
  UNIQUE KEY uq_teams_name_game (name, game_id),
  KEY idx_teams_game (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scores par épreuve (1 seul enregistrement par équipe et par épreuve)
CREATE TABLE IF NOT EXISTS challenge_scores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_id INT UNSIGNED NOT NULL,
  challenge_id VARCHAR(10) NOT NULL,
  points INT NOT NULL,
  attempts INT NOT NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_team_challenge (team_id, challenge_id),
  KEY idx_scores_team (team_id),
  CONSTRAINT fk_scores_team FOREIGN KEY (team_id)
    REFERENCES teams(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session/état par équipe (JSON stocké en texte pour compatibilité large MySQL/MariaDB)
CREATE TABLE IF NOT EXISTS team_sessions (
  team_id INT UNSIGNED NOT NULL,
  challenges_completed TEXT NOT NULL, -- JSON: ex. ["T1","T2"]
  current_challenge VARCHAR(10) DEFAULT NULL,
  session_data TEXT NOT NULL,        -- JSON arbitraire
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (team_id),
  CONSTRAINT fk_team_sessions_team FOREIGN KEY (team_id)
    REFERENCES teams(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conseils d'initialisation côté application:
-- - Lors de l'inscription (team.php?action=register), le code insère déjà
--   challenges_completed = JSON_ARRAY() et session_data = JSON_OBJECT().
-- - Si votre SGBD ne supporte pas JSON_ARRAY()/JSON_OBJECT(), modifiez le code
--   pour insérer les chaînes '[]' et '{}' respectivement.

-- V��rifications rapides (optionnel):
-- SELECT * FROM teams LIMIT 1;
-- SELECT * FROM team_sessions LIMIT 1;
-- SELECT * FROM challenge_scores LIMIT 1;

-- ============================================================
-- MIGRATION : ajouter game_id aux bases existantes
-- ============================================================
-- ALTER TABLE teams ADD COLUMN game_id VARCHAR(20) NOT NULL DEFAULT 'archi' AFTER uuid;
-- ALTER TABLE teams DROP INDEX uq_teams_name;
-- ALTER TABLE teams ADD UNIQUE KEY uq_teams_name_game (name, game_id);
-- ALTER TABLE teams ADD KEY idx_teams_game (game_id);
-- UPDATE teams SET game_id = 'archi' WHERE game_id = '';