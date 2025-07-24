<?php
/**
 * Script para executar as migrações do banco de dados de forma manual.
 */

// Define uma constante para segurança, se necessário em outros arquivos
define('SISTEMA_SORTEIOS', true);

// Exibe todos os erros para facilitar a depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>"; // Formata a saída para melhor legibilidade no navegador

// Inclui os arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/database_schema.php';

echo "Iniciando processo de migração...\n";

try {
    // Cria uma instância do DatabaseSchema e inicializa
    $schema = new DatabaseSchema();
    $schema->initialize();
    
    echo "\nMigrações executadas com sucesso!\n";
    echo "O campo 'regulamento' foi adicionado à tabela 'sorteios'.\n";
    
} catch (Exception $e) {
    echo "\nOcorreu um erro durante a migração:\n";
    echo "======================================\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "======================================\n";
}

echo "\nProcesso de migração finalizado.</pre>";

?>
