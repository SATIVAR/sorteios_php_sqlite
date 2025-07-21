<?php
/**
 * Classe Database - Gerenciamento de conexões SQLite
 * Otimizada para hospedagem compartilhada com sistema de backup automático
 */

class Database {
    private static $instance = null;
    private $pdo = null;
    private $dbPath;
    private $backupPath;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->dbPath = __DIR__ . '/../data/sorteios.db';
        $this->backupPath = __DIR__ . '/../data/backups/';
        $this->initializeDatabase();
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
     * Inicializa o banco de dados e configurações
     */
    private function initializeDatabase() {
        try {
            // Verifica se o diretório de dados existe
            $dataDir = dirname($this->dbPath);
            if (!is_dir($dataDir)) {
                if (!mkdir($dataDir, 0755, true)) {
                    throw new Exception("Não foi possível criar o diretório de dados: $dataDir");
                }
            }
            
            // Verifica permissões de escrita
            if (!is_writable($dataDir)) {
                throw new Exception("Diretório de dados não tem permissão de escrita: $dataDir");
            }
            
            // Cria conexão PDO
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            
            // Configura atributos PDO para segurança e performance
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Aplica configurações PRAGMA otimizadas para hospedagem compartilhada
            $this->configurePragmas();
            
            // Cria diretório de backup se não existir
            $this->createBackupDirectory();
            
        } catch (Exception $e) {
            error_log("Erro ao inicializar banco de dados: " . $e->getMessage());
            throw new Exception("Falha na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Configura PRAGMA otimizados para hospedagem compartilhada
     */
    private function configurePragmas() {
        $pragmas = [
            // Modo WAL para melhor concorrência (se suportado)
            'PRAGMA journal_mode=WAL',
            // Sincronização normal para balance entre segurança e performance
            'PRAGMA synchronous=NORMAL',
            // Cache em memória para melhor performance
            'PRAGMA cache_size=10000',
            // Armazenamento temporário em memória
            'PRAGMA temp_store=MEMORY',
            // Timeout para locks (importante em hospedagem compartilhada)
            'PRAGMA busy_timeout=30000',
            // Otimização de queries
            'PRAGMA optimize',
            // Habilita foreign keys
            'PRAGMA foreign_keys=ON'
        ];
        
        foreach ($pragmas as $pragma) {
            try {
                $this->pdo->exec($pragma);
            } catch (PDOException $e) {
                // Log do erro mas continua execução (alguns PRAGMAs podem não ser suportados)
                error_log("Aviso PRAGMA: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Cria diretório de backup
     */
    private function createBackupDirectory() {
        if (!is_dir($this->backupPath)) {
            if (!mkdir($this->backupPath, 0755, true)) {
                error_log("Não foi possível criar diretório de backup: " . $this->backupPath);
                return false;
            }
        }
        
        // Cria arquivo .htaccess para proteger backups
        $htaccessContent = "Order Allow,Deny\nDeny from all";
        file_put_contents($this->backupPath . '.htaccess', $htaccessContent);
        
        return true;
    }
    
    /**
     * Obtém conexão PDO
     */
    public function getConnection() {
        if ($this->pdo === null) {
            $this->initializeDatabase();
        }
        return $this->pdo;
    }
    
    /**
     * Executa query preparada com parâmetros
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na query: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Erro na execução da query: " . $e->getMessage());
        }
    }
    
    /**
     * Executa query e retorna todos os resultados
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Executa query e retorna um resultado
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Executa query e retorna o ID do último registro inserido
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Executa query de atualização/exclusão e retorna número de linhas afetadas
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Inicia transação
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirma transação
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Desfaz transação
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Verifica se está em transação
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }    
    
/**
     * Sistema de Backup Automático
     */
    
    /**
     * Cria backup do banco de dados
     */
    public function createBackup($description = '') {
        try {
            if (!$this->createBackupDirectory()) {
                throw new Exception("Não foi possível criar diretório de backup");
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . "backup_$timestamp.db";
            
            // Copia arquivo do banco
            if (!copy($this->dbPath, $backupFile)) {
                throw new Exception("Falha ao criar arquivo de backup");
            }
            
            // Registra informações do backup
            $backupInfo = [
                'timestamp' => $timestamp,
                'file' => basename($backupFile),
                'size' => filesize($backupFile),
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveBackupInfo($backupInfo);
            
            // Limpa backups antigos (mantém apenas os 3 mais recentes)
            $this->cleanOldBackups(3);
            
            return $backupFile;
            
        } catch (Exception $e) {
            error_log("Erro ao criar backup: " . $e->getMessage());
            throw new Exception("Falha ao criar backup: " . $e->getMessage());
        }
    }
    
    /**
     * Salva informações do backup em arquivo JSON
     */
    private function saveBackupInfo($backupInfo) {
        $infoFile = $this->backupPath . 'backup_info.json';
        $backups = [];
        
        // Carrega informações existentes
        if (file_exists($infoFile)) {
            $content = file_get_contents($infoFile);
            $backups = json_decode($content, true) ?: [];
        }
        
        // Adiciona novo backup
        $backups[] = $backupInfo;
        
        // Salva arquivo atualizado
        file_put_contents($infoFile, json_encode($backups, JSON_PRETTY_PRINT));
    }
    
    /**
     * Lista backups disponíveis
     */
    public function listBackups() {
        $infoFile = $this->backupPath . 'backup_info.json';
        
        if (!file_exists($infoFile)) {
            return [];
        }
        
        $content = file_get_contents($infoFile);
        $backups = json_decode($content, true) ?: [];
        
        // Ordena por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Remove backups antigos (mantém apenas os 3 mais recentes por padrão)
     */
    private function cleanOldBackups($keepCount = 3) {
        $backups = $this->listBackups();
        
        if (count($backups) <= $keepCount) {
            return;
        }
        
        // Remove backups excedentes
        $toRemove = array_slice($backups, $keepCount);
        
        foreach ($toRemove as $backup) {
            $backupFile = $this->backupPath . $backup['file'];
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
        }
        
        // Atualiza arquivo de informações
        $remainingBackups = array_slice($backups, 0, $keepCount);
        $infoFile = $this->backupPath . 'backup_info.json';
        file_put_contents($infoFile, json_encode($remainingBackups, JSON_PRETTY_PRINT));
    }
    
    /**
     * Restaura backup
     */
    public function restoreBackup($backupFile) {
        try {
            $fullBackupPath = $this->backupPath . $backupFile;
            
            if (!file_exists($fullBackupPath)) {
                throw new Exception("Arquivo de backup não encontrado: $backupFile");
            }
            
            // Cria backup do estado atual antes de restaurar
            $this->createBackup("Backup automático antes de restauração");
            
            // Fecha conexão atual
            $this->pdo = null;
            
            // Substitui arquivo do banco
            if (!copy($fullBackupPath, $this->dbPath)) {
                throw new Exception("Falha ao restaurar backup");
            }
            
            // Reinicializa conexão
            $this->initializeDatabase();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao restaurar backup: " . $e->getMessage());
            throw new Exception("Falha ao restaurar backup: " . $e->getMessage());
        }
    }
    
    /**
     * Backup automático baseado em condições
     */
    public function autoBackup() {
        try {
            $lastBackup = $this->getLastBackupTime();
            $currentTime = time();
            
            // Cria backup se:
            // 1. Nunca foi feito backup
            // 2. Último backup foi há mais de 24 horas
            // 3. Banco foi modificado recentemente
            
            $shouldBackup = false;
            
            if ($lastBackup === null) {
                $shouldBackup = true;
                $reason = "Primeiro backup";
            } elseif (($currentTime - $lastBackup) > 86400) { // 24 horas
                $shouldBackup = true;
                $reason = "Backup diário";
            } elseif ($this->isDatabaseModifiedRecently()) {
                $shouldBackup = true;
                $reason = "Modificações recentes";
            }
            
            if ($shouldBackup) {
                $this->createBackup($reason);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro no backup automático: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém timestamp do último backup
     */
    private function getLastBackupTime() {
        $backups = $this->listBackups();
        
        if (empty($backups)) {
            return null;
        }
        
        return strtotime($backups[0]['created_at']);
    }
    
    /**
     * Verifica se o banco foi modificado recentemente
     */
    private function isDatabaseModifiedRecently() {
        if (!file_exists($this->dbPath)) {
            return false;
        }
        
        $modTime = filemtime($this->dbPath);
        $currentTime = time();
        
        // Considera "recente" se foi modificado nas últimas 2 horas
        return ($currentTime - $modTime) < 7200;
    }
    
    /**
     * Obtém estatísticas do banco
     */
    public function getDatabaseStats() {
        try {
            $stats = [];
            
            // Tamanho do arquivo
            if (file_exists($this->dbPath)) {
                $stats['file_size'] = filesize($this->dbPath);
                $stats['file_size_formatted'] = $this->formatBytes($stats['file_size']);
                $stats['last_modified'] = date('Y-m-d H:i:s', filemtime($this->dbPath));
            }
            
            // Informações das tabelas
            $tables = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $stats['tables'] = [];
            
            foreach ($tables as $table) {
                $tableName = $table['name'];
                $count = $this->fetchOne("SELECT COUNT(*) as count FROM $tableName");
                $stats['tables'][$tableName] = $count['count'];
            }
            
            // Total de registros
            $stats['total_records'] = array_sum($stats['tables']);
            
            // Informações de backup
            $backups = $this->listBackups();
            $stats['backup_count'] = count($backups);
            $stats['last_backup'] = !empty($backups) ? $backups[0]['created_at'] : null;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Formata bytes em formato legível
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Destructor - executa backup automático se necessário
     */
    public function __destruct() {
        // Executa backup automático silenciosamente
        try {
            $this->autoBackup();
        } catch (Exception $e) {
            // Ignora erros no destructor para não quebrar a aplicação
            error_log("Erro no backup automático (destructor): " . $e->getMessage());
        }
    }
}

// Função auxiliar para obter instância do banco
function getDatabase() {
    return Database::getInstance();
}
?>