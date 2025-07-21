<?php
/**
 * Migração: Adicionar Tabelas do Sistema de Relatórios
 * Cria as tabelas necessárias para relatórios avançados e agendamentos
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

function executeMigrationRelatorios($db) {
    try {
        $db->beginTransaction();
        
        // Verificar se as tabelas já existem
        $existingTables = $db->fetchAll("
            SELECT name FROM sqlite_master 
            WHERE type='table' AND name IN ('relatorio_templates', 'relatorio_agendamentos')
        ");
        
        $existingTableNames = array_column($existingTables, 'name');
        
        // Criar tabela de templates de relatórios se não existir
        if (!in_array('relatorio_templates', $existingTableNames)) {
            $db->execute("
                CREATE TABLE relatorio_templates (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    descricao TEXT,
                    tipo TEXT NOT NULL,
                    configuracao TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Criar índices
            $db->execute("CREATE INDEX idx_relatorio_templates_tipo ON relatorio_templates(tipo)");
            $db->execute("CREATE INDEX idx_relatorio_templates_created ON relatorio_templates(created_at)");
            
            logSystem("Tabela relatorio_templates criada com sucesso", 'INFO');
        }
        
        // Criar tabela de agendamentos de relatórios se não existir
        if (!in_array('relatorio_agendamentos', $existingTableNames)) {
            $db->execute("
                CREATE TABLE relatorio_agendamentos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    configuracao TEXT NOT NULL,
                    proxima_execucao DATETIME NOT NULL,
                    ultima_execucao DATETIME,
                    status TEXT DEFAULT 'ativo',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Criar índices
            $db->execute("CREATE INDEX idx_relatorio_agendamentos_status ON relatorio_agendamentos(status)");
            $db->execute("CREATE INDEX idx_relatorio_agendamentos_proxima ON relatorio_agendamentos(proxima_execucao)");
            $db->execute("CREATE INDEX idx_relatorio_agendamentos_created ON relatorio_agendamentos(created_at)");
            
            logSystem("Tabela relatorio_agendamentos criada com sucesso", 'INFO');
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        logSystem("Erro na migração de relatórios: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

// Executar migração se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'add_relatorios_tables.php') {
    require_once '../config.php';
    require_once '../functions.php';
    require_once '../database.php';
    
    try {
        $db = getDatabase();
        $success = executeMigrationRelatorios($db);
        
        if ($success) {
            echo "Migração executada com sucesso!\n";
        } else {
            echo "Erro na migração.\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "Erro crítico: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>