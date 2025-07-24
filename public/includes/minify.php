<?php
/**
 * Sistema de Sorteios - Minificação de Assets
 * Implementa minificação de CSS e JavaScript para melhor performance
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

/**
 * Classe para minificação de assets
 */
class AssetMinifier {
    private $cacheDir;
    private $enabled = true;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->cacheDir = DATA_PATH . '/cache/assets';
        $this->ensureCacheDirectory();
    }
    
    /**
     * Garante que o diretório de cache existe
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                $this->enabled = false;
                error_log("Não foi possível criar diretório de cache de assets: {$this->cacheDir}");
            }
        }
        
        // Criar arquivo .htaccess para proteção
        $htaccess = dirname($this->cacheDir) . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Allow,Deny\nDeny from all");
        }
    }
    
    /**
     * Verifica se a minificação está habilitada
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Gera nome de arquivo minificado
     */
    private function generateMinifiedFilename($files, $type) {
        $hash = md5(implode('|', $files) . filemtime(__FILE__));
        return "min_{$type}_{$hash}.{$type}";
    }
    
    /**
     * Minifica CSS
     */
    public function minifyCSS($files, $inline = false) {
        if (!$this->enabled || empty($files)) {
            return $this->fallbackCSS($files, $inline);
        }
        
        // Verificar se todos os arquivos existem
        foreach ($files as $file) {
            if (!file_exists($file)) {
                return $this->fallbackCSS($files, $inline);
            }
        }
        
        // Gerar nome do arquivo minificado
        $minFilename = $this->generateMinifiedFilename($files, 'css');
        $minFilePath = $this->cacheDir . '/' . $minFilename;
        
        // Verificar se o arquivo minificado já existe
        if (!file_exists($minFilePath)) {
            // Minificar CSS
            $minifiedCSS = '';
            
            foreach ($files as $file) {
                $css = file_get_contents($file);
                $minifiedCSS .= $this->minifyCSSContent($css);
            }
            
            // Salvar arquivo minificado
            if (!file_put_contents($minFilePath, $minifiedCSS)) {
                return $this->fallbackCSS($files, $inline);
            }
        }
        
        // Retornar CSS minificado
        if ($inline) {
            return file_get_contents($minFilePath);
        } else {
            $relativePath = str_replace(ROOT_PATH, '', $minFilePath);
            return '<link rel="stylesheet" href="' . $relativePath . '">';
        }
    }
    
    /**
     * Minifica conteúdo CSS
     */
    private function minifyCSSContent($css) {
        // Remover comentários
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espaços em branco
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espaços antes e depois de caracteres específicos
        $css = preg_replace('/\s*({|}|;|:|,)\s*/', '$1', $css);
        
        // Remover ponto e vírgula final antes de chaves
        $css = preg_replace('/;}/', '}', $css);
        
        return $css;
    }
    
    /**
     * Fallback para CSS (quando a minificação falha)
     */
    private function fallbackCSS($files, $inline) {
        if ($inline) {
            $css = '';
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $css .= file_get_contents($file);
                }
            }
            return $css;
        } else {
            $html = '';
            foreach ($files as $file) {
                $relativePath = str_replace(ROOT_PATH, '', $file);
                $html .= '<link rel="stylesheet" href="' . $relativePath . '">' . PHP_EOL;
            }
            return $html;
        }
    }
    
    /**
     * Minifica JavaScript
     */
    public function minifyJS($files, $inline = false) {
        if (!$this->enabled || empty($files)) {
            return $this->fallbackJS($files, $inline);
        }
        
        // Verificar se todos os arquivos existem
        foreach ($files as $file) {
            if (!file_exists($file)) {
                return $this->fallbackJS($files, $inline);
            }
        }
        
        // Gerar nome do arquivo minificado
        $minFilename = $this->generateMinifiedFilename($files, 'js');
        $minFilePath = $this->cacheDir . '/' . $minFilename;
        
        // Verificar se o arquivo minificado já existe
        if (!file_exists($minFilePath)) {
            // Minificar JavaScript
            $minifiedJS = '';
            
            foreach ($files as $file) {
                $js = file_get_contents($file);
                $minifiedJS .= $this->minifyJSContent($js);
            }
            
            // Salvar arquivo minificado
            if (!file_put_contents($minFilePath, $minifiedJS)) {
                return $this->fallbackJS($files, $inline);
            }
        }
        
        // Retornar JavaScript minificado
        if ($inline) {
            return file_get_contents($minFilePath);
        } else {
            $relativePath = str_replace(ROOT_PATH, '', $minFilePath);
            return '<script src="' . $relativePath . '"></script>';
        }
    }
    
    /**
     * Minifica conteúdo JavaScript
     */
    private function minifyJSContent($js) {
        // Implementação simples de minificação JS
        // Remover comentários de linha única
        $js = preg_replace('!//.*!', '', $js);
        
        // Remover comentários de múltiplas linhas
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        
        // Remover espaços em branco
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remover espaços antes e depois de caracteres específicos
        $js = preg_replace('/\s*({|}|;|:|,|\(|\))\s*/', '$1', $js);
        
        return $js;
    }
    
    /**
     * Fallback para JavaScript (quando a minificação falha)
     */
    private function fallbackJS($files, $inline) {
        if ($inline) {
            $js = '';
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $js .= file_get_contents($file);
                }
            }
            return $js;
        } else {
            $html = '';
            foreach ($files as $file) {
                $relativePath = str_replace(ROOT_PATH, '', $file);
                $html .= '<script src="' . $relativePath . '"></script>' . PHP_EOL;
            }
            return $html;
        }
    }
    
    /**
     * Limpa cache de assets
     */
    public function clearCache() {
        if (!$this->enabled) {
            return false;
        }
        
        $files = glob($this->cacheDir . '/min_*.*');
        
        if (empty($files)) {
            return true;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                $success = false;
                error_log("Não foi possível remover arquivo de cache de asset: {$file}");
            }
        }
        
        return $success;
    }
}

// Função auxiliar para obter instância do minificador
function getAssetMinifier() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new AssetMinifier();
    }
    
    return $instance;
}

/**
 * Função para incluir CSS minificado
 */
function includeMinifiedCSS($files, $inline = false) {
    $minifier = getAssetMinifier();
    
    // Converter para caminhos absolutos
    $absoluteFiles = [];
    foreach ($files as $file) {
        if (strpos($file, ROOT_PATH) !== 0) {
            $absoluteFiles[] = ROOT_PATH . '/' . ltrim($file, '/');
        } else {
            $absoluteFiles[] = $file;
        }
    }
    
    return $minifier->minifyCSS($absoluteFiles, $inline);
}

/**
 * Função para incluir JavaScript minificado
 */
function includeMinifiedJS($files, $inline = false) {
    $minifier = getAssetMinifier();
    
    // Converter para caminhos absolutos
    $absoluteFiles = [];
    foreach ($files as $file) {
        if (strpos($file, ROOT_PATH) !== 0) {
            $absoluteFiles[] = ROOT_PATH . '/' . ltrim($file, '/');
        } else {
            $absoluteFiles[] = $file;
        }
    }
    
    return $minifier->minifyJS($absoluteFiles, $inline);
}