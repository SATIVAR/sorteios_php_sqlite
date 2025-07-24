<?php
/**
 * Script para Executar Migrações do Banco de Dados
 * Executa todas as migrações pendentes
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';

// Verificar autenticação (apenas admin pode executar migrações)
session_start();
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI && !$isAuthenticated) {
    http_response_code(403);
    die('Acesso negado. Apenas administradores podem executar migrações.');
}

$db = getDatabase();

try {
    // Criar tabela de controle de migrações se não existir
    $db->execute("
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name TEXT NOT NULL UNIQUE,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Lista de migrações disponíveis
    $migrations = [
        'add_relatorios_tables' => 'includes/migrations/add_relatorios_tables.php'
    ];
    
    $executedMigrations = [];
    $errors = [];
    
    foreach ($migrations as $migrationName => $migrationFile) {
        try {
            // Verificar se a migração já foi executada
            $executed = $db->fetchOne(
                "SELECT id FROM migrations WHERE migration_name = ?",
                [$migrationName]
            );
            
            if ($executed) {
                if ($isCLI) {
                    echo "Migração '{$migrationName}' já foi executada.\n";
                }
                continue;
            }
            
            // Executar migração
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                
                // Chamar função de migração específica
                $functionName = 'executeMigration' . str_replace('_', '', ucwords($migrationName, '_'));
                
                if (function_exists($functionName)) {
                    $result = $functionName($db);
                    
                    if ($result) {
                        // Registrar migração como executada
                        $db->insert(
                            "INSERT INTO migrations (migration_name) VALUES (?)",
                            [$migrationName]
                        );
                        
                        $executedMigrations[] = $migrationName;
                        
                        if ($isCLI) {
                            echo "Migração '{$migrationName}' executada com sucesso.\n";
                        }
                    } else {
                        throw new Exception("Migração '{$migrationName}' retornou false");
                    }
                } else {
                    throw new Exception("Função de migração '{$functionName}' não encontrada");
                }
            } else {
                throw new Exception("Arquivo de migração '{$migrationFile}' não encontrado");
            }
            
        } catch (Exception $e) {
            $errors[] = "Erro na migração '{$migrationName}': " . $e->getMessage();
            logSystem("Erro na migração '{$migrationName}': " . $e->getMessage(), 'ERROR');
        }
    }
    
    // Resultado final
    $totalExecuted = count($executedMigrations);
    $totalErrors = count($errors);
    
    $message = "Migrações concluídas. Executadas: {$totalExecuted}, Erros: {$totalErrors}";
    logSystem($message, 'INFO');
    
    if ($isCLI) {
        echo "\n" . $message . "\n";
        
        if (!empty($executedMigrations)) {
            echo "Migrações executadas:\n";
            foreach ($executedMigrations as $migration) {
                echo "  - {$migration}\n";
            }
        }
        
        if (!empty($errors)) {
            echo "\nErros encontrados:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
            exit(1);
        }
    } else {
        // Resposta JSON para interface web
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $totalErrors === 0,
            'message' => $message,
            'executed' => $executedMigrations,
            'errors' => $errors,
            'total_executed' => $totalExecuted,
            'total_errors' => $totalErrors
        ]);
    }
    
} catch (Exception $e) {
    $errorMessage = "Erro crítico nas migrações: " . $e->getMessage();
    logSystem($errorMessage, 'ERROR');
    
    if ($isCLI) {
        echo $errorMessage . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    }
}
?>