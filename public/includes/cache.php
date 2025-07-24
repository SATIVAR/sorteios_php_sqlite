<?php
/**
 * Sistema de Sorteios - Sistema de Cache
 * Implementa cache de queries e dados frequentes
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

/**
 * Classe de gerenciamento de cache
 */
class CacheManager {
    private $cacheDir;
    private $enabled = true;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->cacheDir = DATA_PATH . '/cache';
        $this->ensureCacheDirectory();
    }
    
    /**
     * Garante que o diretório de cache existe
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                $this->enabled = false;
                error_log("Não foi possível criar diretório de cache: {$this->cacheDir}");
            }
        }
        
        // Criar arquivo .htaccess para proteção
        $htaccess = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Allow,Deny\nDeny from all");
        }
    }
    
    /**
     * Verifica se o cache está habilitado
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Gera chave de cache baseada nos parâmetros
     */
    public function generateKey($name, $params = []) {
        $paramString = '';
        
        if (!empty($params)) {
            if (is_array($params)) {
                $paramString = '_' . md5(json_encode($params));
            } else {
                $paramString = '_' . md5((string)$params);
            }
        }
        
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . $paramString;
    }
    
    /**
     * Obtém caminho do arquivo de cache
     */
    private function getCachePath($key) {
        return $this->cacheDir . '/' . $key . '.cache';
    }
    
    /**
     * Verifica se o cache existe e é válido
     */
    public function has($key, $ttl = 300) {
        if (!$this->enabled) return false;
        
        $cachePath = $this->getCachePath($key);
        
        if (!file_exists($cachePath)) {
            return false;
        }
        
        // Verificar se o cache expirou
        $modTime = filemtime($cachePath);
        if ((time() - $modTime) > $ttl) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtém dados do cache
     */
    public function get($key, $default = null) {
        if (!$this->enabled) return $default;
        
        $cachePath = $this->getCachePath($key);
        
        if (!file_exists($cachePath)) {
            return $default;
        }
        
        $data = file_get_contents($cachePath);
        if ($data === false) {
            return $default;
        }
        
        $decoded = json_decode($data, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }
        
        return $decoded;
    }
    
    /**
     * Armazena dados no cache
     */
    public function set($key, $data) {
        if (!$this->enabled) return false;
        
        $cachePath = $this->getCachePath($key);
        
        $encoded = json_encode($data);
        if ($encoded === false) {
            error_log("Erro ao codificar dados para cache: " . json_last_error_msg());
            return false;
        }
        
        $result = file_put_contents($cachePath, $encoded);
        return $result !== false;
    }
    
    /**
     * Remove item do cache
     */
    public function delete($key) {
        if (!$this->enabled) return false;
        
        $cachePath = $this->getCachePath($key);
        
        if (file_exists($cachePath)) {
            return unlink($cachePath);
        }
        
        return true;
    }
    
    /**
     * Limpa todo o cache ou por prefixo
     */
    public function clear($prefix = '') {
        if (!$this->enabled) return false;
        
        $pattern = $this->cacheDir . '/';
        if (!empty($prefix)) {
            $pattern .= preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix) . '*';
        } else {
            $pattern .= '*.cache';
        }
        
        $files = glob($pattern);
        
        if (empty($files)) {
            return true;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                $success = false;
                error_log("Não foi possível remover arquivo de cache: {$file}");
            }
        }
        
        return $success;
    }
    
    /**
     * Obtém estatísticas do cache
     */
    public function getStats() {
        if (!$this->enabled) return [];
        
        $files = glob($this->cacheDir . '/*.cache');
        
        $stats = [
            'total_items' => count($files),
            'total_size' => 0,
            'oldest_item' => null,
            'newest_item' => null
        ];
        
        if (empty($files)) {
            return $stats;
        }
        
        $oldest = PHP_INT_MAX;
        $newest = 0;
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            
            $mtime = filemtime($file);
            if ($mtime < $oldest) {
                $oldest = $mtime;
                $stats['oldest_item'] = basename($file);
            }
            
            if ($mtime > $newest) {
                $newest = $mtime;
                $stats['newest_item'] = basename($file);
            }
        }
        
        $stats['oldest_time'] = $oldest;
        $stats['newest_time'] = $newest;
        
        return $stats;
    }
    
    /**
     * Obtém ou define cache com callback
     */
    public function remember($key, $ttl, $callback) {
        if ($this->has($key, $ttl)) {
            return $this->get($key);
        }
        
        $data = $callback();
        $this->set($key, $data);
        
        return $data;
    }
}

// Função auxiliar para obter instância do cache
function getCache() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new CacheManager();
    }
    
    return $instance;
}

/**
 * Função para cache de queries
 */
function cacheQuery($db, $sql, $params = [], $ttl = 300, $cacheKey = null) {
    $cache = getCache();
    
    if (!$cache->isEnabled()) {
        // Cache desabilitado, executar query diretamente
        return $db->fetchAll($sql, $params);
    }
    
    // Gerar chave de cache se não fornecida
    if ($cacheKey === null) {
        $cacheKey = $cache->generateKey('query', [$sql, $params]);
    }
    
    // Verificar cache
    if ($cache->has($cacheKey, $ttl)) {
        return $cache->get($cacheKey);
    }
    
    // Executar query
    $result = $db->fetchAll($sql, $params);
    
    // Armazenar no cache
    $cache->set($cacheKey, $result);
    
    return $result;
}

/**
 * Função para cache de query única
 */
function cacheQueryOne($db, $sql, $params = [], $ttl = 300, $cacheKey = null) {
    $cache = getCache();
    
    if (!$cache->isEnabled()) {
        // Cache desabilitado, executar query diretamente
        return $db->fetchOne($sql, $params);
    }
    
    // Gerar chave de cache se não fornecida
    if ($cacheKey === null) {
        $cacheKey = $cache->generateKey('query_one', [$sql, $params]);
    }
    
    // Verificar cache
    if ($cache->has($cacheKey, $ttl)) {
        return $cache->get($cacheKey);
    }
    
    // Executar query
    $result = $db->fetchOne($sql, $params);
    
    // Armazenar no cache
    $cache->set($cacheKey, $result);
    
    return $result;
}

/**
 * Função para invalidar cache de queries
 */
function invalidateQueryCache($prefix = '') {
    $cache = getCache();
    
    if (!$cache->isEnabled()) {
        return true;
    }
    
    return $cache->clear($prefix);
}