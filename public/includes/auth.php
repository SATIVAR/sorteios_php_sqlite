<?php
/**
 * Sistema de Autenticação - Sistema de Sorteios
 * Gerencia autenticação de administrador, sessões e segurança
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

class Auth {
    private static $instance = null;
    private $db;
    private $sessionTimeout;
    private $maxLoginAttempts;
    private $lockoutTime;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->sessionTimeout = SESSION_TIMEOUT;
        $this->maxLoginAttempts = MAX_LOGIN_ATTEMPTS;
        $this->lockoutTime = LOGIN_LOCKOUT_TIME;
        
        $this->initializeSession();
    }
    
    /**
     * Obtém instância única da classe (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializa sessão segura
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações de sessão segura já definidas no config.php
            session_start();
            
            // Regenera ID da sessão periodicamente para segurança
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Realiza login do administrador
     */
    public function login($email, $password, $csrfToken = null) {
        try {
            // Verifica token CSRF se fornecido
            if ($csrfToken !== null && !$this->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            // Verifica rate limiting
            if (!$this->checkLoginRateLimit()) {
                throw new Exception('Muitas tentativas de login. Tente novamente em ' . 
                                  ceil($this->lockoutTime / 60) . ' minutos');
            }
            
            // Sanitiza entrada
            $email = sanitizeInput($email);
            
            // Valida formato do email
            if (!validateEmail($email)) {
                $this->recordFailedLogin();
                throw new Exception('Email inválido');
            }
            
            // Busca configurações do admin
            $config = $this->getAdminConfig();
            
            if (!$config || empty($config['admin_email']) || empty($config['admin_password'])) {
                throw new Exception('Sistema não configurado. Execute o wizard de instalação');
            }
            
            // Verifica credenciais
            if ($email !== $config['admin_email'] || !password_verify($password, $config['admin_password'])) {
                $this->recordFailedLogin();
                throw new Exception('Email ou senha incorretos');
            }
            
            // Login bem-sucedido
            $this->createSession($email);
            $this->clearFailedLogins();
            
            // Log da atividade
            logActivity('LOGIN_SUCCESS', "Admin: {$email}");
            
            return true;
            
        } catch (Exception $e) {
            logActivity('LOGIN_FAILED', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Realiza logout
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $email = $_SESSION['admin_email'] ?? 'unknown';
            logActivity('LOGOUT', "Admin: {$email}");
        }
        
        // Limpa dados da sessão
        $_SESSION = [];
        
        // Remove cookie da sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destrói sessão
        session_destroy();
        
        return true;
    }
    
    /**
     * Verifica se usuário está logado
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Verifica timeout da sessão
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
                $this->logout();
                return false;
            }
        }
        
        // Atualiza última atividade
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Força login (middleware)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            // Se for requisição AJAX, retorna JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                jsonError('Sessão expirada. Faça login novamente', 401);
            }
            
            // Redireciona para página de login
            $currentUrl = $_SERVER['REQUEST_URI'];
            header('Location: login.php?redirect=' . urlencode($currentUrl));
            exit;
        }
    }
    
    /**
     * Cria sessão do administrador
     */
    private function createSession($email) {
        // Regenera ID da sessão por segurança
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        
        // Gera token CSRF para a sessão
        $this->generateCSRFToken();
    }
    
    /**
     * Obtém configurações do administrador
     */
    private function getAdminConfig() {
        try {
            $config = $this->db->fetchOne(
                "SELECT admin_email, admin_password FROM configuracoes ORDER BY id DESC LIMIT 1"
            );
            
            return $config;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar config admin: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica rate limiting para login
     */
    private function checkLoginRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = DATA_PATH . '/logs/login_attempts_' . md5($ip) . '.tmp';
        
        $currentTime = time();
        $attempts = [];
        
        // Carrega tentativas anteriores
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $attempts = json_decode($data, true) ?: [];
        }
        
        // Remove tentativas antigas
        $attempts = array_filter($attempts, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->lockoutTime;
        });
        
        // Verifica se excedeu o limite
        return count($attempts) < $this->maxLoginAttempts;
    }
    
    /**
     * Registra tentativa de login falhada
     */
    private function recordFailedLogin() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = DATA_PATH . '/logs/login_attempts_' . md5($ip) . '.tmp';
        
        $currentTime = time();
        $attempts = [];
        
        // Carrega tentativas anteriores
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $attempts = json_decode($data, true) ?: [];
        }
        
        // Adiciona nova tentativa
        $attempts[] = $currentTime;
        file_put_contents($cacheFile, json_encode($attempts));
    }
    
    /**
     * Limpa tentativas de login falhadas
     */
    private function clearFailedLogins() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = DATA_PATH . '/logs/login_attempts_' . md5($ip) . '.tmp';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    /**
     * Gera token CSRF
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        return $token;
    }
    
    /**
     * Verifica token CSRF
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Obtém token CSRF atual
     */
    public function getCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return $this->generateCSRFToken();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Obtém informações do usuário logado
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'email' => $_SESSION['admin_email'] ?? '',
            'login_time' => $_SESSION['login_time'] ?? 0,
            'last_activity' => $_SESSION['last_activity'] ?? 0
        ];
    }
    
    /**
     * Altera senha do administrador
     */
    public function changePassword($currentPassword, $newPassword, $csrfToken) {
        try {
            // Verifica se está logado
            if (!$this->isLoggedIn()) {
                throw new Exception('Usuário não autenticado');
            }
            
            // Verifica token CSRF
            if (!$this->verifyCSRFToken($csrfToken)) {
                throw new Exception('Token CSRF inválido');
            }
            
            // Valida nova senha
            if (strlen($newPassword) < 8) {
                throw new Exception('Nova senha deve ter pelo menos 8 caracteres');
            }
            
            // Busca configuração atual
            $config = $this->getAdminConfig();
            if (!$config) {
                throw new Exception('Configuração não encontrada');
            }
            
            // Verifica senha atual
            if (!password_verify($currentPassword, $config['admin_password'])) {
                throw new Exception('Senha atual incorreta');
            }
            
            // Atualiza senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->execute(
                "UPDATE configuracoes SET admin_password = ? WHERE id = (SELECT MAX(id) FROM configuracoes)",
                [$hashedPassword]
            );
            
            logActivity('PASSWORD_CHANGED', 'Admin password changed');
            
            return true;
            
        } catch (Exception $e) {
            logActivity('PASSWORD_CHANGE_FAILED', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Configura headers de segurança
     */
    public static function setSecurityHeaders() {
        if (!headers_sent()) {
            // Previne XSS
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            
            // Política de referrer
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy básico
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; img-src 'self' data: https:; font-src 'self' https:;");
            
            // HSTS (apenas em HTTPS)
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }
    
    /**
     * Obtém estatísticas de segurança
     */
    public function getSecurityStats() {
        $stats = [];
        
        // Contabiliza tentativas de login falhadas
        $logDir = DATA_PATH . '/logs';
        $attemptFiles = glob($logDir . '/login_attempts_*.tmp');
        
        $totalAttempts = 0;
        $blockedIPs = 0;
        
        foreach ($attemptFiles as $file) {
            $data = file_get_contents($file);
            $attempts = json_decode($data, true) ?: [];
            
            // Remove tentativas antigas
            $currentTime = time();
            $recentAttempts = array_filter($attempts, function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) < $this->lockoutTime;
            });
            
            $totalAttempts += count($recentAttempts);
            
            if (count($recentAttempts) >= $this->maxLoginAttempts) {
                $blockedIPs++;
            }
        }
        
        $stats['failed_login_attempts'] = $totalAttempts;
        $stats['blocked_ips'] = $blockedIPs;
        $stats['session_timeout'] = $this->sessionTimeout;
        $stats['max_login_attempts'] = $this->maxLoginAttempts;
        $stats['lockout_time'] = $this->lockoutTime;
        
        return $stats;
    }
}

// Função auxiliar para obter instância da autenticação
function getAuth() {
    return Auth::getInstance();
}

// Configura headers de segurança automaticamente
Auth::setSecurityHeaders();
?>