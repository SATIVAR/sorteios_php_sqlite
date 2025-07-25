<?php
// cleanup_configuracoes.php
// Script CLI para limpar tabela 'configuracoes' e backups antigos

define('SISTEMA_SORTEIOS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

$db = getDatabase();

try {
    // 1. Manter apenas o registro mais recente em 'configuracoes'
    $latest = $db->fetchOne("SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1");
    if ($latest && isset($latest['id'])) {
        $latestId = $latest['id'];
        $deleted = $db->execute("DELETE FROM configuracoes WHERE id != ?", [$latestId]);
        echo "Registros antigos removidos de 'configuracoes': $deleted\n";
    } else {
        echo "Nenhum registro encontrado em 'configuracoes'.\n";
    }

    // 2. Limpar backups antigos, mantendo apenas o mais recente
    $backupDir = __DIR__ . '/data/backups/';
    $backups = glob($backupDir . 'backup_*.db');
    if ($backups && count($backups) > 1) {
        // Ordenar por data de modificação (mais recente por último)
        usort($backups, function($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });
        // Manter apenas o último
        $toDelete = array_slice($backups, 0, -1);
        foreach ($toDelete as $file) {
            unlink($file);
            echo "Backup removido: $file\n";
        }
    } else {
        echo "Nenhum backup antigo para remover.\n";
    }

    echo "Limpeza concluída com sucesso.\n";
} catch (Exception $e) {
    echo "Erro ao limpar: " . $e->getMessage() . "\n";
    exit(1);
} 