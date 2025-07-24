<?php
/**
 * Script para executar todos os testes funcionais
 */

echo "=== EXECUTANDO TESTES FUNCIONAIS DO SISTEMA DE SORTEIOS ===\n\n";

$testFiles = [
    'test_fluxo_sorteio.php',
    'test_participacao_publica.php',
    'test_relatorios.php',
    'test_responsividade.php'
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
echo "RESUMO DOS TESTES FUNCIONAIS\n";
echo "========================================\n\n";
echo "Total de testes: " . count($testFiles) . "\n";
echo "Testes bem-sucedidos: $successCount\n";
echo "Testes com falha: $failCount\n\n";

if ($failCount == 0) {
    echo "✓ TODOS OS TESTES FUNCIONAIS FORAM CONCLUÍDOS COM SUCESSO!\n";
} else {
    echo "✗ ALGUNS TESTES FALHARAM. VERIFIQUE OS LOGS ACIMA PARA MAIS DETALHES.\n";
    exit(1);
}

echo "\n=== FIM DOS TESTES FUNCIONAIS ===\n";