// Configuration globale
const API_BASE_URL = 'https://hebergementvikazimut.vikazim.fr/escapegame/api';
const GAME_ID = 'archi';
const STORAGE_KEY_UUID = `vikazimut_team_uuid_${GAME_ID}`;
const STORAGE_KEY_SESSION = 'vikazimut_session';

// Récupérer l'UUID de l'équipe
function getTeamUUID() {
    return localStorage.getItem(STORAGE_KEY_UUID);
}

// Sauvegarder l'UUID
function setTeamUUID(uuid) {
    localStorage.setItem(STORAGE_KEY_UUID, uuid);
}

// Appel API générique
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur API');
        }
        
        return result.data;
    } catch (error) {
        console.error('Erreur API:', error);
        throw error;
    }
}

// Enregistrer une équipe
async function registerTeam(teamName) {
    const data = await apiCall('/team.php?action=register', 'POST', { name: teamName, game_id: GAME_ID });
    setTeamUUID(data.uuid);
    return data;
}

// Soumettre un score
async function submitScore(challengeId, points, attempts) {
    const uuid = getTeamUUID();
    if (!uuid) {
        throw new Error('Aucune équipe enregistrée');
    }
    
    return await apiCall('/score.php?action=submit', 'POST', {
        uuid,
        challenge_id: challengeId,
        points,
        attempts
    });
}

// Récupérer la session
async function getSession() {
    const uuid = getTeamUUID();
    if (!uuid) return null;
    
    return await apiCall(`/team.php?action=session&uuid=${uuid}`);
}

// Récupérer le classement
async function getLeaderboard(limit = 100) {
    return await apiCall(`/leaderboard.php?limit=${limit}&game_id=${GAME_ID}`);
}

// Calculer les points selon les tentatives
function calculatePoints(basePoints, attempts) {
    const penalty = 100;
    return Math.max(0, basePoints - (attempts - 1) * penalty);
}

// Afficher un message toast
function showToast(message, type = 'info') {
    // Implémenter un système de notification
    alert(message); // Temporaire
}
