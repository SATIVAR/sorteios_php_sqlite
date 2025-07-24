<?php
/**
 * Script de Inicialização do Banco de Dados
 * Execute este arquivo para configurar o banco SQLite pela primeira vez
 */

// Inclui arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/database_schema.php';

// Configurações de exibição
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função para exibir mensagens formatadas
function displayMessage($message, $type = 'info') {
    $colors = [
        'info' => "\033[0;36m",    // Ciano
        'success' => "\033[0;32m", // Verde
        'warning' => "\033[0;33m", // Amarelo
        'error' => "\033[0;31m",   // Vermelho
        'reset' => "\033[0m"       // Reset
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    echo $color . $message . $colors['reset'] . "\n";
}

try {
    displayMessage("=== SETUP DO BANCO DE DADOS - SISTEMA DE SORTEIOS ===", 'info');
    displayMessage("");
    
    // Verifica se é execução via linha de comando ou web
    $isCommandLine = php_sapi_name() === 'cli';
    
    if (!$isCommandLine) {
        echo "<pre>";
        echo "<h2>Setup do Banco de Dados - Sistema de Sorteios</h2>";
    }
    
    // Verifica se o banco já existe e tem dados
    if (!needsDatabaseSetup()) {
        displayMessage("⚠️  O banco de dados já está configurado!", 'warning');
        displayMessage("Se deseja recriar o banco, delete o arquivo data/sorteios.db primeiro.", 'warning');
        
        // Mostra informações do banco atual
        $db = Database::getInstance();
        $stats = $db->getDatabaseStats();
        
        displayMessage("\nInformações do banco atual:", 'info');
        displayMessage("- Tamanho: " . $stats['file_size_formatted'], 'info');
        displayMessage("- Última modificação: " . $stats['last_modified'], 'info');
        displayMessage("- Total de registros: " . $stats['total_records'], 'info');
        
        if (!empty($stats['tables'])) {
            displayMessage("\nTabelas:", 'info');
            foreach ($stats['tables'] as $table => $count) {
                displayMessage("  - $table: $count registros", 'info');
            }
        }
        
        exit(0);
    }
    
    displayMessage("🚀 Iniciando configuração do banco de dados...", 'info');
    displayMessage("");
    
    // Cria instância do schema
    $schema = new DatabaseSchema();
    
    // Executa setup completo
    ob_start();
    $success = $schema->setupDatabase();
    $output = ob_get_clean();
    
    if ($success) {
        displayMessage("✅ Banco de dados configurado com sucesso!", 'success');
        displayMessage("");
        
        // Exibe informações do banco criado
        $db = Database::getInstance();
        $stats = $db->getDatabaseStats();
        $schemaInfo = $schema->getSchemaInfo();
        
        displayMessage("📊 Informações do banco criado:", 'info');
        displayMessage("- Versão do schema: " . $schemaInfo['version'], 'info');
        displayMessage("- Tabelas criadas: " . $schemaInfo['table_count'], 'info');
        displayMessage("- Índices criados: " . $schemaInfo['index_count'], 'info');
        displayMessage("- Triggers criados: " . $schemaInfo['trigger_count'], 'info');
        displayMessage("- Tamanho inicial: " . $stats['file_size_formatted'], 'info');
        
        displayMessage("", 'info');
        displayMessage("📋 Tabelas criadas:", 'info');
        foreach ($schemaInfo['tables'] as $table) {
            displayMessage("  ✓ $table", 'success');
        }
        
        displayMessage("", 'info');
        displayMessage("🎯 Próximos passos:", 'info');
        displayMessage("1. Configure as credenciais de administrador no wizard", 'info');
        displayMessage("2. Acesse o sistema através do index.php", 'info');
        displayMessage("3. Complete a configuração inicial", 'info');
        
        // Cria backup inicial
        displayMessage("", 'info');
        displayMessage("💾 Criando backup inicial...", 'info');
        $backupFile = $db->createBackup("Setup inicial do sistema");
        displayMessage("Backup criado: " . basename($backupFile), 'success');
        
    } else {
        displayMessage("❌ Erro na configuração do banco de dados!", 'error');
        exit(1);
    }
    
} catch (Exception $e) {
    displayMessage("❌ ERRO: " . $e->getMessage(), 'error');
    displayMessage("", 'error');
    displayMessage("Verifique:", 'warning');
    displayMessage("- Permissões de escrita no diretório 'data'", 'warning');
    displayMessage("- Extensão SQLite habilitada no PHP", 'warning');
    displayMessage("- Espaço em disco disponível", 'warning');
    
    if (!$isCommandLine) {
        echo "</pre>";
    }
    
    exit(1);
}

if (!$isCommandLine) {
    echo "</pre>";
}

displayMessage("", 'info');
displayMessage("=== SETUP CONCLUÍDO ===", 'success');
?>