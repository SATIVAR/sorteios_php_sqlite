<?php
/**
 * Sistema de Sorteios - Configurações Principais
 * Arquivo de configuração central do sistema
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    define('SISTEMA_SORTEIOS', true);
}

// Configurações de ambiente para hospedagem compartilhada
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/logs/error.log');

// Configurações de sessão segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configurações de performance para hospedagem compartilhada
ini_set('memory_limit', '128M');
set_time_limit(30);

// Definições de caminhos
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('DATA_PATH', ROOT_PATH . '/data');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('AJAX_PATH', ROOT_PATH . '/ajax');

// Configurações do banco de dados SQLite
define('DB_PATH', DATA_PATH . '/sorteios.db');
define('DB_BACKUP_PATH', DATA_PATH . '/backups');

// Configurações de segurança
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Configurações do sistema
define('SYSTEM_NAME', 'Sistema de Sorteios');
define('SYSTEM_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'America/Sao_Paulo');

// Configurações de upload
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Configurações de email (para relatórios futuros)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', '');
define('FROM_NAME', '');

// Configurações de rate limiting
define('RATE_LIMIT_PARTICIPACAO', 5); // 5 tentativas por minuto
define('RATE_LIMIT_WINDOW', 60); // janela de 1 minuto

// Configurações padrão do sistema
$GLOBALS['sistema_config'] = [
    'nome_empresa' => '',
    'regras_padrao' => 'Regulamento padrão do sorteio.',
    'admin_email' => '',
    'admin_password' => '',
    'instalado' => false,
    'tema' => 'light',
    'idioma' => 'pt-BR',
    'fuso_horario' => DEFAULT_TIMEZONE
];

// Configurar timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Função para carregar configurações do banco
function loadSystemConfig() {
    global $sistema_config;
    
    if (!file_exists(DB_PATH)) {
        return $sistema_config;
    }
    
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            $sistema_config['nome_empresa'] = $config['nome_empresa'] ?? '';
            $sistema_config['regras_padrao'] = $config['regras_padrao'] ?? '';
            $sistema_config['admin_email'] = $config['admin_email'] ?? '';
            $sistema_config['instalado'] = true;
        }
        
    } catch (Exception $e) {
        error_log("Erro ao carregar configurações: " . $e->getMessage());
    }
    
    return $sistema_config;
}

// Função para salvar configurações
function saveSystemConfig($config) {
    global $sistema_config;
    
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Hash da senha se fornecida
        $adminPassword = null;
        if (isset($config['admin_password']) && !empty($config['admin_password'])) {
            $adminPassword = password_hash($config['admin_password'], PASSWORD_DEFAULT);
        }
        
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO configuracoes 
            (id, nome_empresa, regras_padrao, admin_email, admin_password, created_at) 
            VALUES (1, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $config['nome_empresa'],
            $config['regras_padrao'],
            $config['admin_email'],
            $adminPassword
        ]);
        
        $sistema_config = array_merge($sistema_config, $config);
        $sistema_config['instalado'] = true;
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        return false;
    }
}

// Carregar configurações na inicialização
$sistema_config = loadSystemConfig();
?>
