<?php
/**
 * Teste de performance em ambiente de hospedagem compartilhada
 * 
 * Este teste simula as limitações comuns de uma hospedagem compartilhada
 * e verifica se o sistema funciona adequadamente nessas condições.
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE PERFORMANCE EM HOSPEDAGEM COMPARTILHADA ===\n\n";

// Configurar limites simulados de hospedagem compartilhada
echo "Configurando limites de hospedagem compartilhada...\n";
ini_set('memory_limit', '64M');       // Limite de memória típico
set_time_limit(30);                   // Timeout típico
ini_set('max_execution_time', 30);    // Tempo máximo de execução

echo "✓ Limites configurados:\n";
echo "  - Memória: " . ini_get('memory_limit') . "\n";
echo "  - Tempo máximo de execução: " . ini_get('max_execution_time') . " segundos\n";

// Resetar banco de dados
echo "\nPreparando ambiente de teste...\n";
resetTestDatabase();

// Etapa 1: Testar carregamento inicial do sistema
echo "\n1. Testando carregamento inicial do sistema...\n";

// Medir tempo e uso de memória
$memoryBefore = memory_get_usage();
$startTime = microtime(true);

// Simular carregamento da página inicial
$output = simulateRequest('/index.php', 'GET');

// Calcular métricas
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "  Tempo de carregamento: " . number_format($totalTime, 4) . " segundos\n";
echo "  Memória utilizada: " . formatBytes($memoryUsed) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($totalTime <= 1.0) {
    echo "  ✓ Tempo de carregamento dentro do limite aceitável (<=1s)\n";
} else {
    echo "  ⚠ Tempo de carregamento acima do ideal (>1s)\n";
}

if ($memoryUsed <= 10 * 1024 * 1024) { // 10MB
    echo "  ✓ Uso de memória dentro do limite aceitável (<=10MB)\n";
} else {
    echo "  ⚠ Uso de memória acima do ideal (>10MB)\n";
}

// Etapa 2: Testar operações de banco de dados
echo "\n2. Testando operações de banco de dados...\n";

// Criar sorteio para teste
$stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, public_url, status) 
                      VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    'Sorteio Teste Hospedagem', 
    'Descrição do sorteio para teste de hospedagem', 
    100, 
    3,
    'teste_hospedagem',
    'ativo'
]);

$sorteioId = $pdo->lastInsertId();

// Adicionar participantes (operação intensiva de banco)
echo "  Adicionando 100 participantes...\n";
$startTime = microtime(true);
$memoryBefore = memory_get_usage();

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                          VALUES (?, ?, ?, ?, ?)");
                          
    for ($i = 1; $i <= 100; $i++) {
        $nome = "Participante $i";
        $whatsapp = "5511" . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $cpf = str_pad($i, 11, '0', STR_PAD_LEFT);
        $email = "teste$i@example.com";
        
        $stmt->execute([$sorteioId, $nome, $whatsapp, $cpf, $email]);
    }
    
    $pdo->commit();
    
    // Calcular métricas
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    $memoryAfter = memory_get_usage();
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    echo "  Tempo para adicionar 100 participantes: " . number_format($totalTime, 4) . " segundos\n";
    echo "  Memória utilizada: " . formatBytes($memoryUsed) . "\n";
    
    // Verificar se está dentro dos limites aceitáveis
    if ($totalTime <= 10.0) {
        echo "  ✓ Tempo de inserção dentro do requisito (<=10s para 100 participantes)\n";
    } else {
        echo "  ✗ Tempo de inserção acima do requisito (>10s para 100 participantes)\n";
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "  ✗ Erro ao adicionar participantes: " . $e->getMessage() . "\n";
    exit(1);
}

// Etapa 3: Testar carregamento de página com muitos dados
echo "\n3. Testando carregamento de página com muitos dados...\n";

// Medir tempo e uso de memória
$memoryBefore = memory_get_usage();
$startTime = microtime(true);

// Simular carregamento da página de participantes
$output = simulateRequest('/participantes.php', 'GET', [], ['id' => $sorteioId]);

// Calcular métricas
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "  Tempo de carregamento: " . number_format($totalTime, 4) . " segundos\n";
echo "  Memória utilizada: " . formatBytes($memoryUsed) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($totalTime <= 3.0) {
    echo "  ✓ Tempo de carregamento dentro do limite aceitável (<=3s)\n";
} else {
    echo "  ⚠ Tempo de carregamento acima do ideal (>3s)\n";
}

// Etapa 4: Testar execução de sorteio
echo "\n4. Testando execução de sorteio...\n";

// Incluir a classe SorteioEngine
require_once ROOT_PATH . '/includes/classes/SorteioEngine.php';

// Criar instância do motor de sorteio
$sorteioEngine = new SorteioEngine($pdo);

// Medir tempo e uso de memória
$memoryBefore = memory_get_usage();
$startTime = microtime(true);

// Executar o sorteio
$qtdSorteados = 3;
$resultado = $sorteioEngine->executeSorteio($sorteioId, $qtdSorteados);

// Calcular métricas
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "  Tempo de execução: " . number_format($totalTime, 4) . " segundos\n";
echo "  Memória utilizada: " . formatBytes($memoryUsed) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($totalTime <= 5.0) {
    echo "  ✓ Tempo de execução dentro do limite aceitável (<=5s)\n";
} else {
    echo "  ⚠ Tempo de execução acima do ideal (>5s)\n";
}

// Etapa 5: Testar geração de relatório
echo "\n5. Testando geração de relatório...\n";

// Incluir a classe RelatorioExporter
require_once ROOT_PATH . '/includes/classes/RelatorioExporter.php';

// Criar instância do exportador de relatórios
$relatorioExporter = new RelatorioExporter($pdo);

// Medir tempo e uso de memória
$memoryBefore = memory_get_usage();
$startTime = microtime(true);

// Gerar relatório de participantes
$relatorioData = $relatorioExporter->generateParticipantesReport($sorteioId);

// Calcular métricas
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "  Tempo de geração: " . number_format($totalTime, 4) . " segundos\n";
echo "  Memória utilizada: " . formatBytes($memoryUsed) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($totalTime <= 10.0) {
    echo "  ✓ Tempo de geração dentro do limite aceitável (<=10s)\n";
} else {
    echo "  ⚠ Tempo de geração acima do ideal (>10s)\n";
}

// Etapa 6: Verificar tamanho do banco de dados
echo "\n6. Verificando tamanho do banco de dados...\n";

$dbSize = filesize($GLOBALS['db_path']);
echo "  Tamanho do banco de dados: " . formatBytes($dbSize) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($dbSize <= 10 * 1024 * 1024) { // 10MB
    echo "  ✓ Tamanho do banco dentro do limite aceitável (<=10MB)\n";
} else {
    echo "  ⚠ Tamanho do banco acima do ideal (>10MB)\n";
}

// Etapa 7: Verificar uso de recursos do sistema
echo "\n7. Verificando uso de recursos do sistema...\n";

// Obter uso de CPU (simulado)
$cpuUsage = rand(5, 20); // Simulação
echo "  Uso de CPU (simulado): $cpuUsage%\n";

// Obter uso de memória
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);
echo "  Uso atual de memória: " . formatBytes($memoryUsage) . "\n";
echo "  Pico de uso de memória: " . formatBytes($memoryPeak) . "\n";

// Verificar se está dentro dos limites aceitáveis
if ($memoryPeak <= 64 * 1024 * 1024) { // 64MB
    echo "  ✓ Pico de memória dentro do limite da hospedagem (<=64MB)\n";
} else {
    echo "  ✗ Pico de memória acima do limite da hospedagem (>64MB)\n";
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";

// Função para formatar bytes em unidades legíveis
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}