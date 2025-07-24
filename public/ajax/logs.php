<?php
// Sistema de Logs Visual - Endpoint AJAX
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Só permite acesso de admin logado (ajuste conforme seu sistema de autenticação)
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}


// Expansão: permitir logs de diferentes áreas
$allowedTypes = [
    'system' => DATA_PATH . '/logs/system.log',
    'activity' => DATA_PATH . '/logs/activity.log',
    'backup' => DATA_PATH . '/logs/backup.log',
];
$type = isset($_GET['type']) ? $_GET['type'] : 'system';
if (!isset($allowedTypes[$type])) {
    $type = 'system';
}
$logFile = $allowedTypes[$type];

if (!file_exists($logFile)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo de log não encontrado']);
    exit;
}

$lines = file($logFile);
$maxLines = 200;
$recent = array_slice($lines, -$maxLines);
$recent = array_reverse($recent);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'logs' => $recent
]);
exit;
