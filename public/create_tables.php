<?php
define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = getDatabase();

try {
    // Criar tabela de templates
    $db->execute('CREATE TABLE IF NOT EXISTS relatorio_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        descricao TEXT,
        tipo TEXT NOT NULL,
        configuracao TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    // Criar tabela de agendamentos
    $db->execute('CREATE TABLE IF NOT EXISTS relatorio_agendamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        configuracao TEXT NOT NULL,
        proxima_execucao DATETIME NOT NULL,
        ultima_execucao DATETIME,
        status TEXT DEFAULT "ativo",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    echo 'Tabelas criadas com sucesso!';
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>