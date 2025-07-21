<?php
/**
 * Sistema de Verificação de Instalação
 * Verifica se o sistema precisa de configuração inicial
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

/**
 * Verifica se o sistema está completamente instalado
 */
function checkSystemInstallation() {
    $checks = [
        'database_exists' => false,
        'tables_created' => false,
        'config_saved' => false,
        'admin_configured' => false,
        'directories_created' => false
    ];
    
    try {
        // Verificar se o banco de dados existe
        if (file_exists(DB_PATH)) {
            $checks['database_exists'] = true;
            
            // Verificar se as tabelas foram criadas
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll();
            $requiredTables = ['configuracoes', 'sorteios', 'participantes', 'sorteio_resultados'];
            $existingTables = array_column($tables, 'name');
            
            $checks['tables_created'] = count(array_intersect($requiredTables, $existingTables)) >= count($requiredTables);
            
            if ($checks['tables_created']) {
                // Verificar se existe configuração salva
                $config = $pdo->query("SELECT COUNT(*) as count FROM configuracoes")->fetch();
                $checks['config_saved'] = $config['count'] > 0;
                
                if ($checks['config_saved']) {
                    // Verificar se admin foi configurado
                    $admin = $pdo->query("SELECT admin_email, admin_password FROM configuracoes ORDER BY id DESC LIMIT 1")->fetch();
                    $checks['admin_configured'] = !empty($admin['admin_email']) && !empty($admin['admin_password']);
                }
            }
        }
        
        // Verificar se os diretórios foram criados
        $requiredDirs = [
            DATA_PATH,
            DATA_PATH . '/logs',
            DATA_PATH . '/backups',
            DATA_PATH . '/exports'
        ];
        
        $checks['directories_created'] = true;
        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                $checks['directories_created'] = false;
                break;
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro na verificação de instalação: " . $e->getMessage());
    }
    
    return $checks;
}

/**
 * Verifica se o sistema está completamente instalado (versão simplificada)
 */
function isSystemFullyInstalled() {
    $checks = checkSystemInstallation();
    return $checks['database_exists'] && 
           $checks['tables_created'] && 
           $checks['config_saved'] && 
           $checks['admin_configured'] && 
           $checks['directories_created'];
}

/**
 * Obtém o status detalhado da instalação
 */
function getInstallationStatus() {
    $checks = checkSystemInstallation();
    $status = [
        'installed' => isSystemFullyInstalled(),
        'progress' => 0,
        'next_step' => 'wizard',
        'checks' => $checks,
        'missing' => []
    ];
    
    // Calcular progresso
    $completedChecks = array_filter($checks);
    $status['progress'] = (count($completedChecks) / count($checks)) * 100;
    
    // Identificar o que está faltando
    foreach ($checks as $check => $completed) {
        if (!$completed) {
            switch ($check) {
                case 'database_exists':
                    $status['missing'][] = 'Banco de dados não foi criado';
                    break;
                case 'tables_created':
                    $status['missing'][] = 'Tabelas do banco não foram criadas';
                    break;
                case 'config_saved':
                    $status['missing'][] = 'Configurações não foram salvas';
                    break;
                case 'admin_configured':
                    $status['missing'][] = 'Administrador não foi configurado';
                    break;
                case 'directories_created':
                    $status['missing'][] = 'Diretórios do sistema não foram criados';
                    break;
            }
        }
    }
    
    return $status;
}

/**
 * Força redirecionamento para wizard se necessário
 */
function redirectToWizardIfNeeded() {
    if (!isSystemFullyInstalled()) {
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        
        // Não redirecionar se já estiver no wizard ou em arquivos permitidos
        $allowedFiles = ['wizard.php', 'setup_database.php'];
        
        if (!in_array($currentScript, $allowedFiles)) {
            header('Location: wizard.php');
            exit;
        }
    }
}

/**
 * Cria arquivo de lock para indicar instalação em progresso
 */
function createInstallationLock() {
    $lockFile = DATA_PATH . '/installation.lock';
    file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - Instalação iniciada');
    return $lockFile;
}

/**
 * Remove arquivo de lock após instalação
 */
function removeInstallationLock() {
    $lockFile = DATA_PATH . '/installation.lock';
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

/**
 * Verifica se há instalação em progresso
 */
function isInstallationInProgress() {
    $lockFile = DATA_PATH . '/installation.lock';
    return file_exists($lockFile);
}

/**
 * Limpa instalação incompleta
 */
function cleanIncompleteInstallation() {
    try {
        // Remove banco se existir mas estiver incompleto
        if (file_exists(DB_PATH)) {
            $checks = checkSystemInstallation();
            if (!$checks['tables_created'] || !$checks['config_saved']) {
                unlink(DB_PATH);
            }
        }
        
        // Remove lock de instalação
        removeInstallationLock();
        
        // Limpa sessão do wizard
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['wizard_data']);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao limpar instalação incompleta: " . $e->getMessage());
        return false;
    }
}

/**
 * Valida integridade da instalação
 */
function validateInstallationIntegrity() {
    $issues = [];
    
    try {
        // Verificar banco de dados
        if (!file_exists(DB_PATH)) {
            $issues[] = 'Arquivo do banco de dados não encontrado';
        } else {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $result = $pdo->query("PRAGMA integrity_check")->fetch();
            if ($result['integrity_check'] !== 'ok') {
                $issues[] = 'Banco de dados corrompido';
            }
        }
        
        // Verificar diretórios
        $requiredDirs = [DATA_PATH, DATA_PATH . '/logs', DATA_PATH . '/backups'];
        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                $issues[] = "Diretório não encontrado: $dir";
            } elseif (!is_writable($dir)) {
                $issues[] = "Diretório sem permissão de escrita: $dir";
            }
        }
        
        // Verificar configurações
        global $sistema_config;
        if (empty($sistema_config['nome_empresa'])) {
            $issues[] = 'Nome da empresa não configurado';
        }
        if (empty($sistema_config['admin_email'])) {
            $issues[] = 'Email do administrador não configurado';
        }
        
    } catch (Exception $e) {
        $issues[] = 'Erro na validação: ' . $e->getMessage();
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues
    ];
}
?>