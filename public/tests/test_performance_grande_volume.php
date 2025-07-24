<?php
/**
 * Teste de performance com grande volume de participantes (1000+)
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE PERFORMANCE COM GRANDE VOLUME DE PARTICIPANTES ===\n\n";

// Resetar banco de dados
echo "Preparando ambiente de teste...\n";
resetTestDatabase();

// Configurar limite de tempo de execução
set_time_limit(300); // 5 minutos

// Etapa 1: Criar sorteio para teste
echo "\n1. Criando sorteio para teste de grande volume...\n";
$stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, public_url, status) 
                      VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    'Sorteio Grande Volume', 
    'Teste de performance com grande volume de participantes', 
    2000, // Permitir até 2000 participantes
    10,
    'grande_volume',
    'ativo'
]);

$sorteioId = $pdo->lastInsertId();

if ($sorteioId) {
    echo "✓ Sorteio criado com sucesso! ID: $sorteioId\n";
} else {
    echo "✗ Falha ao criar sorteio!\n";
    exit(1);
}

// Etapa 2: Adicionar 1000+ participantes
echo "\n2. Adicionando 1000+ participantes...\n";
$numParticipantes = 1000;

// Medir tempo de início
$startTime = microtime(true);

// Usar transação para melhorar performance
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                          VALUES (?, ?, ?, ?, ?)");
                          
    for ($i = 1; $i <= $numParticipantes; $i++) {
        $nome = "Participante $i";
        $whatsapp = "5511" . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $cpf = str_pad($i, 11, '0', STR_PAD_LEFT);
        $email = "teste$i@example.com";
        
        $stmt->execute([$sorteioId, $nome, $whatsapp, $cpf, $email]);
        
        // Mostrar progresso a cada 100 participantes
        if ($i % 100 == 0) {
            echo "  Adicionados $i participantes...\n";
        }
    }
    
    $pdo->commit();
    
    // Calcular tempo total
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    echo "✓ $numParticipantes participantes adicionados com sucesso!\n";
    echo "  Tempo total: " . number_format($totalTime, 2) . " segundos\n";
    echo "  Média: " . number_format($numParticipantes / $totalTime, 2) . " participantes/segundo\n";
    
    // Verificar se atende ao requisito de performance (10 segundos para 100 participantes)
    $requisito = 10; // 10 segundos para 100 participantes
    $requisitoProporcional = $requisito * ($numParticipantes / 100); // Tempo proporcional para o número de participantes
    
    if ($totalTime <= $requisitoProporcional) {
        echo "✓ Performance dentro do requisito! ($totalTime segundos <= $requisitoProporcional segundos)\n";
    } else {
        echo "✗ Performance abaixo do requisito! ($totalTime segundos > $requisitoProporcional segundos)\n";
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Erro ao adicionar participantes: " . $e->getMessage() . "\n";
    exit(1);
}

// Etapa 3: Testar consulta de participantes
echo "\n3. Testando consulta de todos os participantes...\n";

// Medir tempo de início
$startTime = microtime(true);

// Consultar todos os participantes
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE sorteio_id = ?");
$stmt->execute([$sorteioId]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular tempo total
$endTime = microtime(true);
$totalTime = $endTime - $startTime;

echo "✓ Consulta de " . count($participantes) . " participantes realizada!\n";
echo "  Tempo total: " . number_format($totalTime, 2) . " segundos\n";

// Etapa 4: Testar execução de sorteio com grande volume
echo "\n4. Testando execução de sorteio com grande volume...\n";

// Incluir a classe SorteioEngine
require_once ROOT_PATH . '/includes/classes/SorteioEngine.php';

// Criar instância do motor de sorteio
$sorteioEngine = new SorteioEngine($pdo);

// Medir tempo de início
$startTime = microtime(true);

// Executar o sorteio
$qtdSorteados = 10;
$resultado = $sorteioEngine->executeSorteio($sorteioId, $qtdSorteados);

// Calcular tempo total
$endTime = microtime(true);
$totalTime = $endTime - $startTime;

if (count($resultado) == $qtdSorteados) {
    echo "✓ Sorteio executado com sucesso! " . $qtdSorteados . " ganhadores selecionados.\n";
    echo "  Tempo total: " . number_format($totalTime, 2) . " segundos\n";
    
    // Verificar se a animação seria fluida (menos de 5 segundos)
    if ($totalTime <= 5) {
        echo "✓ Performance adequada para animação fluida! ($totalTime segundos <= 5 segundos)\n";
    } else {
        echo "⚠ Performance pode comprometer animação fluida! ($totalTime segundos > 5 segundos)\n";
    }
} else {
    echo "✗ Falha ao executar sorteio! Ganhadores encontrados: " . count($resultado) . ", Esperados: $qtdSorteados\n";
    exit(1);
}

// Etapa 5: Testar geração de relatório com grande volume
echo "\n5. Testando geração de relatório com grande volume...\n";

// Incluir a classe RelatorioExporter
require_once ROOT_PATH . '/includes/classes/RelatorioExporter.php';

// Criar instância do exportador de relatórios
$relatorioExporter = new RelatorioExporter($pdo);

// Medir tempo de início
$startTime = microtime(true);

// Gerar relatório de participantes
$relatorioData = $relatorioExporter->generateParticipantesReport($sorteioId);

// Calcular tempo total
$endTime = microtime(true);
$totalTime = $endTime - $startTime;

if (isset($relatorioData['participantes']) && count($relatorioData['participantes']) == $numParticipantes) {
    echo "✓ Relatório de participantes gerado com sucesso!\n";
    echo "  Tempo total: " . number_format($totalTime, 2) . " segundos\n";
} else {
    echo "✗ Falha ao gerar relatório de participantes!\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";