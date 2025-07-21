<?php
/**
 * Script para executar todos os testes de performance e segurança
 */

echo "=== EXECUTANDO TESTES DE PERFORMANCE E SEGURANÇA DO SISTEMA DE SORTEIOS ===\n\n";

$testFiles = [
    'test_performance_grande_volume.php',
    'test_seguranca.php',
    'test_performance_hospedagem.php',
    'test_compatibilidade_php.php'
];

$successCount = 0;
$failCount = 0;

foreach ($testFiles as $testFile) {
    echo "\n\n========================================\n";
    echo "Executando: $testFile\n";
    echo "========================================\n\n";
    
    // Executar o teste
    ob_start();
    $result = include __DIR__ . '/' . $testFile;
    $output = ob_get_clean();
    
    echo $output;
    
    // Verificar resultado
    if ($result !== false) {
        $successCount++;
    } else {
        $failCount++;
        echo "\n✗ FALHA no teste: $testFile\n";
    }
}

echo "\n\n========================================\n";
echo "RESUMO DOS TESTES DE PERFORMANCE E SEGURANÇA\n";
echo "========================================\n\n";
echo "Total de testes: " . count($testFiles) . "\n";
echo "Testes bem-sucedidos: $successCount\n";
echo "Testes com falha: $failCount\n\n";

if ($failCount == 0) {
    echo "✓ TODOS OS TESTES DE PERFORMANCE E SEGURANÇA FORAM CONCLUÍDOS COM SUCESSO!\n";
} else {
    echo "✗ ALGUNS TESTES FALHARAM. VERIFIQUE OS LOGS ACIMA PARA MAIS DETALHES.\n";
    exit(1);
}

echo "\n=== FIM DOS TESTES DE PERFORMANCE E SEGURANÇA ===\n";