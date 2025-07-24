<?php
/**
 * Script de Limpeza de Backups Antigos
 * Mantém apenas os X backups mais recentes para economizar espaço
 * 
 * Uso: php cleanup_backups.php [número_de_backups_a_manter]
 */

// Configurações
define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/database.php';

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
    
    $isCommandLine = php_sapi_name() === 'cli';
    
    if ($isCommandLine) {
        $color = $colors[$type] ?? $colors['info'];
        echo $color . $message . $colors['reset'] . "\n";
    } else {
        $cssClass = $type;
        echo "<div class=\"$cssClass\">$message</div>\n";
    }
}

// Verifica se é execução via linha de comando ou web
$isCommandLine = php_sapi_name() === 'cli';

if (!$isCommandLine) {
    echo "<!DOCTYPE html>
    <html lang=\"pt-BR\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>Limpeza de Backups - Sistema de Sorteios</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
            h1 { color: #333; }
            .info { color: #0066cc; }
            .success { color: #008800; }
            .warning { color: #cc8400; }
            .error { color: #cc0000; }
        </style>
    </head>
    <body>
        <h1>Limpeza de Backups - Sistema de Sorteios</h1>
    ";
}

displayMessage("=== LIMPEZA DE BACKUPS - SISTEMA DE SORTEIOS ===", 'info');
displayMessage("");

// Determinar quantos backups manter
$keepCount = 3; // Padrão

// Se for linha de comando, verificar argumentos
if ($isCommandLine && isset($argv[1]) && is_numeric($argv[1]) && $argv[1] > 0) {
    $keepCount = (int)$argv[1];
}
// Se for web, verificar parâmetro GET
elseif (!$isCommandLine && isset($_GET['keep']) && is_numeric($_GET['keep']) && $_GET['keep'] > 0) {
    $keepCount = (int)$_GET['keep'];
}

displayMessage("Mantendo os $keepCount backups mais recentes...", 'info');

try {
    // Obter instância do banco
    $db = Database::getInstance();
    
    // Listar backups atuais
    $backups = $db->listBackups();
    $totalBackups = count($backups);
    
    displayMessage("Total de backups encontrados: $totalBackups", 'info');
    
    if ($totalBackups <= $keepCount) {
        displayMessage("Não há backups excedentes para remover.", 'info');
    } else {
        // Criar backup atual antes de limpar (segurança)
        displayMessage("Criando backup atual antes da limpeza...", 'info');
        $newBackup = $db->createBackup("Backup pré-limpeza");
        displayMessage("Novo backup criado: " . basename($newBackup), 'success');
        
        // Recarregar lista de backups
        $backups = $db->listBackups();
        $totalBackups = count($backups);
        
        // Calcular quantos backups remover
        $removeCount = $totalBackups - $keepCount;
        
        if ($removeCount > 0) {
            displayMessage("Removendo $removeCount backups antigos...", 'info');
            
            // Backups a remover (os mais antigos)
            $toRemove = array_slice($backups, $keepCount);
            
            foreach ($toRemove as $backup) {
                $backupFile = DB_BACKUP_PATH . '/' . $backup['file'];
                if (file_exists($backupFile)) {
                    if (unlink($backupFile)) {
                        displayMessage("Removido: " . $backup['file'] . " (" . $backup['created_at'] . ")", 'success');
                    } else {
                        displayMessage("Falha ao remover: " . $backup['file'], 'error');
                    }
                } else {
                    displayMessage("Arquivo não encontrado: " . $backup['file'], 'warning');
                }
            }
            
            // Atualizar arquivo de informações
            $remainingBackups = array_slice($backups, 0, $keepCount);
            $infoFile = DB_BACKUP_PATH . '/backup_info.json';
            file_put_contents($infoFile, json_encode($remainingBackups, JSON_PRETTY_PRINT));
            
            displayMessage("Arquivo de informações de backup atualizado.", 'success');
        }
    }
    
    displayMessage("\nLimpeza concluída com sucesso!", 'success');
    
} catch (Exception $e) {
    displayMessage("Erro durante a limpeza: " . $e->getMessage(), 'error');
}

if (!$isCommandLine) {
    echo "</body></html>";
}