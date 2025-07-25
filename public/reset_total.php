<?php
// reset_total.php
// Script CLI para resetar completamente o sistema (todas as tabelas e dados)

define('SISTEMA_SORTEIOS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

$db = getDatabase();

try {
    // 1. Apagar todas as tabelas principais
    $tables = [
        'sorteio_resultados',
        'participantes',
        'sorteios',
        'relatorio_agendamentos',
        'relatorio_templates',
        'configuracoes'
    ];
    foreach ($tables as $table) {
        $db->execute("DROP TABLE IF EXISTS $table");
        echo "Tabela '$table' removida.\n";
    }

    // 2. Limpar backups
    $backupDir = __DIR__ . '/data/backups/';
    $backups = glob($backupDir . 'backup_*.db');
    if ($backups) {
        foreach ($backups as $file) {
            unlink($file);
            echo "Backup removido: $file\n";
        }
    }
    // Limpar arquivo de info de backups
    $infoFile = $backupDir . 'backup_info.json';
    if (file_exists($infoFile)) {
        unlink($infoFile);
        echo "Arquivo de info de backups removido.\n";
    }

    // 3. Pronto para reinstalação
    echo "Reset total concluído. O sistema está pronto para reinstalação pelo wizard.\n";
} catch (Exception $e) {
    echo "Erro ao resetar: " . $e->getMessage() . "\n";
    exit(1);
} 