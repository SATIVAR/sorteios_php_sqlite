<?php
/**
 * Script de Verificação Pré-Deploy
 * Verifica se o sistema está pronto para ser implantado em produção
 * 
 * Uso: php pre_deploy_check.php
 */

// Configurações
define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
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
        <title>Verificação Pré-Deploy - Sistema de Sorteios</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
            h1 { color: #333; }
            .info { color: #0066cc; }
            .success { color: #008800; }
            .warning { color: #cc8400; }
            .error { color: #cc0000; }
            .section { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .result { font-weight: bold; }
            .pass { color: #008800; }
            .fail { color: #cc0000; }
        </style>
    </head>
    <body>
        <h1>Verificação Pré-Deploy - Sistema de Sorteios</h1>
    ";
}

displayMessage("=== VERIFICAÇÃO PRÉ-DEPLOY - SISTEMA DE SORTEIOS ===", 'info');
displayMessage("");

// Array para armazenar resultados
$results = [
    'pass' => [],
    'warning' => [],
    'fail' => []
];

// Função para registrar resultado
function addResult($message, $status) {
    global $results;
    $results[$status][] = $message;
    
    $statusText = $status === 'pass' ? 'PASS' : ($status === 'warning' ? 'AVISO' : 'FALHA');
    $type = $status === 'pass' ? 'success' : ($status === 'warning' ? 'warning' : 'error');
    
    displayMessage("[$statusText] $message", $type);
}

// 1. Verificar versão do PHP
displayMessage("1. Verificando versão do PHP...", 'info');
$phpVersion = phpversion();
$requiredVersion = '7.4.0';

if (version_compare($phpVersion, $requiredVersion, '>=')) {
    addResult("Versão do PHP: $phpVersion (OK)", 'pass');
} else {
    addResult("Versão do PHP: $phpVersion (Requer $requiredVersion ou superior)", 'fail');
}

// 2. Verificar extensões necessárias
displayMessage("\n2. Verificando extensões do PHP...", 'info');
$requiredExtensions = ['pdo_sqlite', 'json', 'mbstring'];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        addResult("Extensão $ext: Carregada", 'pass');
    } else {
        $severity = $ext === 'mbstring' ? 'warning' : 'fail';
        addResult("Extensão $ext: Não encontrada", $severity);
    }
}

// 3. Verificar permissões de diretórios
displayMessage("\n3. Verificando permissões de diretórios...", 'info');
$directories = [
    'data' => DATA_PATH,
    'data/logs' => DATA_PATH . '/logs',
    'data/backups' => DATA_PATH . '/backups',
    'data/exports' => DATA_PATH . '/exports'
];

foreach ($directories as $name => $dir) {
    if (!file_exists($dir)) {
        addResult("Diretório $name: Não existe", 'fail');
    } elseif (!is_writable($dir)) {
        addResult("Diretório $name: Sem permissão de escrita", 'fail');
    } else {
        addResult("Diretório $name: OK", 'pass');
    }
}

// 4. Verificar banco de dados
displayMessage("\n4. Verificando banco de dados...", 'info');

try {
    $db = Database::getInstance();
    
    // Verificar se o banco existe
    if (!file_exists(DB_PATH)) {
        addResult("Arquivo do banco: Não existe", 'fail');
    } else {
        addResult("Arquivo do banco: Existe", 'pass');
        
        // Verificar integridade
        $result = $db->fetchOne("PRAGMA integrity_check");
        if ($result['integrity_check'] === 'ok') {
            addResult("Integridade do banco: OK", 'pass');
        } else {
            addResult("Integridade do banco: Falha", 'fail');
        }
        
        // Verificar tabelas
        $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $requiredTables = ['configuracoes', 'sorteios', 'participantes', 'sorteio_resultados'];
        $existingTables = array_column($tables, 'name');
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (empty($missingTables)) {
            addResult("Tabelas do banco: Todas presentes", 'pass');
        } else {
            addResult("Tabelas do banco: Faltando " . implode(', ', $missingTables), 'fail');
        }
        
        // Verificar configurações
        $config = $db->fetchOne("SELECT COUNT(*) as count FROM configuracoes");
        if ($config['count'] > 0) {
            addResult("Configurações: Presentes", 'pass');
        } else {
            addResult("Configurações: Não configuradas", 'fail');
        }
    }
} catch (Exception $e) {
    addResult("Erro ao verificar banco: " . $e->getMessage(), 'fail');
}

// 5. Verificar arquivos críticos
displayMessage("\n5. Verificando arquivos críticos...", 'info');
$criticalFiles = [
    'index.php',
    'admin.php',
    'includes/config.php',
    'includes/database.php',
    'includes/functions.php',
    '.htaccess'
];

foreach ($criticalFiles as $file) {
    if (!file_exists(__DIR__ . "/" . $file)) {
        addResult("Arquivo $file: Não encontrado", 'fail');
    } else {
        addResult("Arquivo $file: OK", 'pass');
    }
}

// 6. Verificar arquivos de desenvolvimento que devem ser removidos
displayMessage("\n6. Verificando arquivos de desenvolvimento...", 'info');
$devFiles = [
    'pre_deploy_check.php',
    'test_participantes.php',
    'test_historico.php',
    'ajax_demo.php',
    'sorteio_demo.php'
];

foreach ($devFiles as $file) {
    if (file_exists($file)) {
        addResult("Arquivo de desenvolvimento $file: Deve ser removido", 'warning');
    } else {
        addResult("Arquivo de desenvolvimento $file: Não encontrado (OK)", 'pass');
    }
}

// 7. Verificar configurações de segurança
displayMessage("\n7. Verificando configurações de segurança...", 'info');

// Verificar display_errors
$displayErrors = ini_get('display_errors');
if ($displayErrors == 1) {
    addResult("display_errors: Ativado (deve ser desativado em produção)", 'warning');
} else {
    addResult("display_errors: Desativado (OK)", 'pass');
}

// Verificar configurações de sessão
$sessionSecure = ini_get('session.cookie_secure');
if ($sessionSecure == 0) {
    addResult("session.cookie_secure: Desativado (ative se usar HTTPS)", 'warning');
} else {
    addResult("session.cookie_secure: Ativado (OK)", 'pass');
}

$sessionHttpOnly = ini_get('session.cookie_httponly');
if ($sessionHttpOnly == 0) {
    addResult("session.cookie_httponly: Desativado (deve ser ativado)", 'warning');
} else {
    addResult("session.cookie_httponly: Ativado (OK)", 'pass');
}

// 8. Verificar backups
displayMessage("\n8. Verificando sistema de backup...", 'info');

try {
    $backups = $db->listBackups();
    
    if (count($backups) > 0) {
        addResult("Backups: " . count($backups) . " encontrados", 'pass');
        
        // Verificar backup recente (últimas 24h)
        $recentBackup = false;
        foreach ($backups as $backup) {
            $backupTime = strtotime($backup['created_at']);
            if ((time() - $backupTime) < 86400) {
                $recentBackup = true;
                break;
            }
        }
        
        if ($recentBackup) {
            addResult("Backup recente: Encontrado", 'pass');
        } else {
            addResult("Backup recente: Não encontrado (crie um backup antes do deploy)", 'warning');
        }
    } else {
        addResult("Backups: Nenhum encontrado (crie um backup antes do deploy)", 'warning');
    }
} catch (Exception $e) {
    addResult("Erro ao verificar backups: " . $e->getMessage(), 'warning');
}

// Resumo dos resultados
displayMessage("\n=== RESUMO DA VERIFICAÇÃO ===", 'info');
displayMessage("Testes passados: " . count($results['pass']), 'success');
displayMessage("Avisos: " . count($results['warning']), 'warning');
displayMessage("Falhas: " . count($results['fail']), 'error');

// Recomendação final
if (count($results['fail']) > 0) {
    displayMessage("\n❌ RESULTADO: O sistema NÃO está pronto para deploy!", 'error');
    displayMessage("Corrija os problemas acima antes de prosseguir.", 'error');
} elseif (count($results['warning']) > 0) {
    displayMessage("\n⚠️ RESULTADO: O sistema pode ser implantado, mas há avisos!", 'warning');
    displayMessage("Revise os avisos acima antes de prosseguir.", 'warning');
} else {
    displayMessage("\n✅ RESULTADO: O sistema está pronto para deploy!", 'success');
}

// Instruções para deploy
displayMessage("\n=== INSTRUÇÕES PARA DEPLOY ===", 'info');
displayMessage("1. Crie um backup completo do banco de dados", 'info');
displayMessage("2. Remova os arquivos de desenvolvimento listados acima", 'info');
displayMessage("3. Verifique as configurações de segurança", 'info');
displayMessage("4. Faça upload dos arquivos para o servidor de produção", 'info');
displayMessage("5. Verifique as permissões dos diretórios no servidor", 'info');
displayMessage("6. Acesse o sistema e verifique se está funcionando corretamente", 'info');

if (!$isCommandLine) {
    echo "</body></html>";
}