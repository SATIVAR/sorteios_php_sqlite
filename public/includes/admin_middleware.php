<?php
/**
 * Middleware de Autenticação Administrativa
 * Protege páginas que requerem autenticação de administrador
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

// Inclui dependências se não foram incluídas
if (!class_exists('Auth')) {
    require_once 'auth.php';
}

// Obtém instância da autenticação
$auth = getAuth();

// Força login se não estiver autenticado
$auth->requireLogin();

// Verifica se a sessão ainda é válida
if (!$auth->isLoggedIn()) {
    // Redireciona para login se sessão expirou
    $currentUrl = $_SERVER['REQUEST_URI'];
    header('Location: login.php?redirect=' . urlencode($currentUrl));
    exit;
}

// Disponibiliza dados do usuário para a página
$currentUser = $auth->getUser();
$csrfToken = $auth->getCSRFToken();

// Função auxiliar para verificar CSRF em formulários
function requireCSRF($token = null) {
    global $auth;
    
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    if (!$auth->verifyCSRFToken($token)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            jsonError('Token CSRF inválido', 403);
        } else {
            die('Token CSRF inválido. Recarregue a página e tente novamente.');
        }
    }
}

// Função auxiliar para gerar campo CSRF hidden
function csrfField() {
    global $csrfToken;
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
}

// Função auxiliar para gerar meta tag CSRF (para AJAX)
function csrfMeta() {
    global $csrfToken;
    return '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken) . '">';
}
?>