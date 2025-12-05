<?php
session_start();

// Caminhos
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('SECTIONS_FILE', DATA_DIR . '/sections.json');
define('COMMUNITY_FILE', DATA_DIR . '/community.json');
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// Garante que diretórios existem
if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
if (!file_exists(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0777, true);

function getUsers() {
    if (!file_exists(USERS_FILE)) return [];
    return json_decode(file_get_contents(USERS_FILE), true);
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function getSections() {
    if (!file_exists(SECTIONS_FILE)) return [];
    return json_decode(file_get_contents(SECTIONS_FILE), true);
}

function saveSections($sections) {
    file_put_contents(SECTIONS_FILE, json_encode($sections, JSON_PRETTY_PRINT));
}

function getCommunity() {
    if (!file_exists(COMMUNITY_FILE)) return [];
    return json_decode(file_get_contents(COMMUNITY_FILE), true);
}

function saveCommunity($data) {
    file_put_contents(COMMUNITY_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function checkAuth() {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Inicializa usuário padrão se não existir
$users = getUsers();
if (empty($users)) {
    // Usuário: admin, Senha: 123456
    $users['admin'] = password_hash('123456', PASSWORD_DEFAULT);
    saveUsers($users);
}
?>