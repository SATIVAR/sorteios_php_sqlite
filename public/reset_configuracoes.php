<?php
// reset_configuracoes.php
// Script CLI para resetar a tabela 'configuracoes' para um estado limpo

define('SISTEMA_SORTEIOS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

if (php_sapi_name() !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

$db = getDatabase();

try {
    // Apagar todos os registros da tabela 'configuracoes'
    $deleted = $db->execute("DELETE FROM configuracoes");
    echo "Registros removidos de 'configuracoes': $deleted\n";

    // Inserir registro padrão (admin não configurado)
    $db->insert(
        "INSERT INTO configuracoes (id, nome_empresa, regras_padrao, admin_email, admin_password, created_at) VALUES (1, ?, ?, '', '', datetime('now'))",
        [
            'Minha Empresa',
            'Regulamento padrão do sorteio.'
        ]
    );
    echo "Registro padrão inserido em 'configuracoes'.\n";
    echo "Tabela de configurações resetada com sucesso.\n";
} catch (Exception $e) {
    echo "Erro ao resetar: " . $e->getMessage() . "\n";
    exit(1);
} 