<?php
/**
 * Script para executar todos os testes do sistema
 */

echo "=== EXECUTANDO TODOS OS TESTES DO SISTEMA DE SORTEIOS ===\n\n";

// Definir tempo limite de execução para testes longos
set_time_limit(600); // 10 minutos

// Executar testes funcionais
echo "\n\n========================================\n";
echo "EXECUTANDO TESTES FUNCIONAIS\n";
echo "========================================\n\n";

ob_start();
$functionalResult = include __DIR__ . '/run_functional_tests.php';
$output = ob_get_clean();
echo $output;

// Executar testes de performance e segurança
echo "\n\n========================================\n";
echo "EXECUTANDO TESTES DE PERFORMANCE E SEGURANÇA\n";
echo "========================================\n\n";

ob_start();
$performanceSecurityResult = include __DIR__ . '/run_performance_security_tests.php';
$output = ob_get_clean();
echo $output;

// Resumo final
echo "\n\n========================================\n";
echo "RESUMO FINAL DE TODOS OS TESTES\n";
echo "========================================\n\n";

if ($functionalResult !== false && $performanceSecurityResult !== false) {
    echo "✓ TODOS OS TESTES FORAM CONCLUÍDOS COM SUCESSO!\n";
    echo "  O sistema está pronto para produção.\n";
} else {
    echo "✗ ALGUNS TESTES FALHARAM. VERIFIQUE OS LOGS ACIMA PARA MAIS DETALHES.\n";
    if ($functionalResult === false) {
        echo "  - Falhas nos testes funcionais\n";
    }
    if ($performanceSecurityResult === false) {
        echo "  - Falhas nos testes de performance e segurança\n";
    }
    exit(1);
}

echo "\n=== FIM DE TODOS OS TESTES ===\n";