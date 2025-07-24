<?php
/**
 * Sistema de Rate Limiting - Sistema de Sorteios
 * Controle avançado de tentativas para prevenir spam e ataques
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

class RateLimiter {
    private static $instance = null;
    private $dataPath;
    
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
     * Construtor privado
     */
    private function __construct() {
        $this->dataPath = DATA_PATH . '/rate_limits';
        $this->ensureDataDirectory();
    }
    
    /**
     * Garante que o diretório de dados existe
     */
    private function ensureDataDirectory() {
        if (!is_dir($this->dataPath)) {
            if (!mkdir($this->dataPath, 0755, true)) {
                throw new Exception("Não foi possível criar diretório de rate limiting: {$this->dataPath}");
            }
        }
        
        // Criar arquivo .htaccess para proteger os dados
        $htaccessFile = $this->dataPath . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Order Allow,Deny\nDeny from all");
        }
    }
    
    /**
     * Verifica se uma ação está dentro do limite permitido
     */
    public function checkLimit($identifier, $action, $maxAttempts, $windowSeconds) {
        $key = $this->generateKey($identifier, $action);
        $attempts = $this->getAttempts($key);
        $currentTime = time();
        
        // Filtrar tentativas dentro da janela de tempo
        $validAttempts = array_filter($attempts, function($timestamp) use ($currentTime, $windowSeconds) {
            return ($currentTime - $timestamp) < $windowSeconds;
        });
        
        // Verificar se excedeu o limite
        if (count($validAttempts) >= $maxAttempts) {
            $this->logViolation($identifier, $action, count($validAttempts), $maxAttempts);
            return false;
        }
        
        return true;
    }
    
    /**
     * Registra uma tentativa
     */
    public function recordAttempt($identifier, $action) {
        $key = $this->generateKey($identifier, $action);
        $attempts = $this->getAttempts($key);
        
        // Adicionar nova tentativa
        $attempts[] = time();
        
        // Manter apenas as últimas 100 tentativas para evitar arquivos muito grandes
        if (count($attempts) > 100) {
            $attempts = array_slice($attempts, -100);
        }
        
        $this->saveAttempts($key, $attempts);
    }
    
    /**
     * Obtém o tempo restante até poder tentar novamente
     */
    public function getTimeUntilReset($identifier, $action, $windowSeconds) {
        $key = $this->generateKey($identifier, $action);
        $attempts = $this->getAttempts($key);
        
        if (empty($attempts)) {
            return 0;
        }
        
        $oldestAttempt = min($attempts);
        $timeElapsed = time() - $oldestAttempt;
        
        return max(0, $windowSeconds - $timeElapsed);
    }
    
    /**
     * Limpa tentativas antigas
     */
    public function cleanup($maxAge = 86400) { // 24 horas por padrão
        $files = glob($this->dataPath . '/rl_*.json');
        $currentTime = time();
        
        foreach ($files as $file) {
            $lastModified = filemtime($file);
            
            if (($currentTime - $lastModified) > $maxAge) {
                unlink($file);
            } else {
                // Limpar tentativas antigas dentro do arquivo
                $data = json_decode(file_get_contents($file), true);
                if (is_array($data)) {
                    $validAttempts = array_filter($data, function($timestamp) use ($currentTime, $maxAge) {
                        return ($currentTime - $timestamp) < $maxAge;
                    });
                    
                    if (count($validAttempts) !== count($data)) {
                        if (empty($validAttempts)) {
                            unlink($file);
                        } else {
                            file_put_contents($file, json_encode(array_values($validAttempts)));
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Verifica se um IP está em lista negra temporária
     */
    public function isBlacklisted($ip, $duration = 3600) { // 1 hora por padrão
        $blacklistFile = $this->dataPath . '/blacklist.json';
        
        if (!file_exists($blacklistFile)) {
            return false;
        }
        
        $blacklist = json_decode(file_get_contents($blacklistFile), true) ?: [];
        
        if (!isset($blacklist[$ip])) {
            return false;
        }
        
        $blacklistTime = $blacklist[$ip];
        $currentTime = time();
        
        // Verificar se ainda está na lista negra
        if (($currentTime - $blacklistTime) < $duration) {
            return true;
        }
        
        // Remover da lista negra se expirou
        unset($blacklist[$ip]);
        file_put_contents($blacklistFile, json_encode($blacklist));
        
        return false;
    }
    
    /**
     * Adiciona IP à lista negra temporária
     */
    public function addToBlacklist($ip, $reason = '') {
        $blacklistFile = $this->dataPath . '/blacklist.json';
        $blacklist = [];
        
        if (file_exists($blacklistFile)) {
            $blacklist = json_decode(file_get_contents($blacklistFile), true) ?: [];
        }
        
        $blacklist[$ip] = time();
        file_put_contents($blacklistFile, json_encode($blacklist));
        
        // Log da ação
        logActivity('IP_BLACKLISTED', "IP: {$ip}, Reason: {$reason}");
    }
    
    /**
     * Verifica múltiplas condições de rate limiting
     */
    public function checkMultipleConditions($identifier, $conditions) {
        foreach ($conditions as $condition) {
            $action = $condition['action'];
            $maxAttempts = $condition['max_attempts'];
            $windowSeconds = $condition['window_seconds'];
            
            if (!$this->checkLimit($identifier, $action, $maxAttempts, $windowSeconds)) {
                return [
                    'allowed' => false,
                    'violated_condition' => $condition,
                    'time_until_reset' => $this->getTimeUntilReset($identifier, $action, $windowSeconds)
                ];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Sistema de rate limiting adaptativo baseado em comportamento
     */
    public function adaptiveCheck($identifier, $action, $baseLimit, $windowSeconds) {
        $violationCount = $this->getViolationCount($identifier, 3600); // Últimas 1 hora
        
        // Reduzir limite baseado em violações anteriores
        $adaptedLimit = max(1, $baseLimit - floor($violationCount / 2));
        
        return $this->checkLimit($identifier, $action, $adaptedLimit, $windowSeconds);
    }
    
    /**
     * Gera chave única para identificar tentativas
     */
    private function generateKey($identifier, $action) {
        return 'rl_' . md5($identifier . '_' . $action);
    }
    
    /**
     * Obtém tentativas registradas
     */
    private function getAttempts($key) {
        $file = $this->dataPath . '/' . $key . '.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $data = file_get_contents($file);
        $attempts = json_decode($data, true);
        
        return is_array($attempts) ? $attempts : [];
    }
    
    /**
     * Salva tentativas
     */
    private function saveAttempts($key, $attempts) {
        $file = $this->dataPath . '/' . $key . '.json';
        file_put_contents($file, json_encode($attempts), LOCK_EX);
    }
    
    /**
     * Registra violação de rate limiting
     */
    private function logViolation($identifier, $action, $attempts, $maxAttempts) {
        $violationFile = $this->dataPath . '/violations.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] RATE_LIMIT_VIOLATION: {$identifier} | Action: {$action} | Attempts: {$attempts}/{$maxAttempts}" . PHP_EOL;
        
        file_put_contents($violationFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Log no sistema principal também
        logActivity('RATE_LIMIT_VIOLATION', "ID: {$identifier}, Action: {$action}, Attempts: {$attempts}/{$maxAttempts}");
    }
    
    /**
     * Obtém número de violações de um identificador
     */
    public function getViolationCount($identifier, $windowSeconds) {
        $violationFile = $this->dataPath . '/violations.log';
        
        if (!file_exists($violationFile)) {
            return 0;
        }
        
        $lines = file($violationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $currentTime = time();
        
        foreach (array_reverse($lines) as $line) {
            if (strpos($line, $identifier) !== false) {
                // Extrair timestamp da linha
                if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if (($currentTime - $logTime) < $windowSeconds) {
                        $count++;
                    } else {
                        break; // Parar se chegou em logs muito antigos
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Obtém estatísticas de rate limiting
     */
    public function getStats() {
        $files = glob($this->dataPath . '/rl_*.json');
        $totalAttempts = 0;
        $activeKeys = 0;
        $currentTime = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $recentAttempts = array_filter($data, function($timestamp) use ($currentTime) {
                    return ($currentTime - $timestamp) < 3600; // Últimas 1 hora
                });
                
                if (!empty($recentAttempts)) {
                    $activeKeys++;
                    $totalAttempts += count($recentAttempts);
                }
            }
        }
        
        // Contar IPs na lista negra
        $blacklistFile = $this->dataPath . '/blacklist.json';
        $blacklistedIPs = 0;
        
        if (file_exists($blacklistFile)) {
            $blacklist = json_decode(file_get_contents($blacklistFile), true) ?: [];
            $blacklistedIPs = count($blacklist);
        }
        
        return [
            'active_keys' => $activeKeys,
            'total_attempts_last_hour' => $totalAttempts,
            'blacklisted_ips' => $blacklistedIPs,
            'data_files' => count($files)
        ];
    }
}

/**
 * Funções auxiliares para facilitar o uso
 */

/**
 * Verifica rate limiting para participação
 */
function checkParticipationRateLimit($ip, $maxAttempts = 5, $windowSeconds = 300) {
    $rateLimiter = RateLimiter::getInstance();
    
    // Verificar se IP está na lista negra
    if ($rateLimiter->isBlacklisted($ip)) {
        return [
            'allowed' => false,
            'reason' => 'IP temporariamente bloqueado',
            'time_until_reset' => 3600 // 1 hora
        ];
    }
    
    // Verificar rate limiting normal
    if (!$rateLimiter->checkLimit($ip, 'participation', $maxAttempts, $windowSeconds)) {
        $timeUntilReset = $rateLimiter->getTimeUntilReset($ip, 'participation', $windowSeconds);
        
        // Se muitas violações, adicionar à lista negra
        $violationCount = $rateLimiter->getViolationCount($ip, 3600);
        if ($violationCount >= 10) {
            $rateLimiter->addToBlacklist($ip, 'Múltiplas violações de rate limit');
        }
        
        return [
            'allowed' => false,
            'reason' => 'Muitas tentativas. Aguarde antes de tentar novamente.',
            'time_until_reset' => $timeUntilReset
        ];
    }
    
    return ['allowed' => true];
}

/**
 * Registra tentativa de participação
 */
function recordParticipationAttempt($ip) {
    $rateLimiter = RateLimiter::getInstance();
    $rateLimiter->recordAttempt($ip, 'participation');
}

/**
 * Verifica rate limiting para formulários em geral
 */
function checkFormRateLimit($identifier, $action, $maxAttempts = 10, $windowSeconds = 600) {
    $rateLimiter = RateLimiter::getInstance();
    
    if (!$rateLimiter->checkLimit($identifier, $action, $maxAttempts, $windowSeconds)) {
        $timeUntilReset = $rateLimiter->getTimeUntilReset($identifier, $action, $windowSeconds);
        
        return [
            'allowed' => false,
            'time_until_reset' => $timeUntilReset
        ];
    }
    
    return ['allowed' => true];
}

/**
 * Registra tentativa de formulário
 */
function recordFormAttempt($identifier, $action) {
    $rateLimiter = RateLimiter::getInstance();
    $rateLimiter->recordAttempt($identifier, $action);
}

/**
 * Limpa dados antigos de rate limiting (para ser executado periodicamente)
 */
function cleanupRateLimitData() {
    $rateLimiter = RateLimiter::getInstance();
    $rateLimiter->cleanup();
}

/**
 * Obtém instância do rate limiter
 */
function getRateLimiter() {
    return RateLimiter::getInstance();
}
?>