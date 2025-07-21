<?php
/**
 * Teste do sistema de relatórios e exportações
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE RELATÓRIOS E EXPORTAÇÕES ===\n\n";

// Resetar banco de dados
echo "Preparando ambiente de teste...\n";
resetTestDatabase();

// Etapa 1: Criar dados para teste
echo "\n1. Criando dados para teste...\n";

// Criar múltiplos sorteios
$sorteiosIds = [];
for ($i = 1; $i <= 3; $i++) {
    $stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, public_url, status) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        "Sorteio Teste $i", 
        "Descrição do sorteio para teste $i", 
        100, 
        3,
        "teste$i",
        'ativo'
    ]);
    $sorteiosIds[] = $pdo->lastInsertId();
}

echo "✓ " . count($sorteiosIds) . " sorteios criados com sucesso!\n";

// Adicionar participantes aos sorteios
$totalParticipantes = 0;
foreach ($sorteiosIds as $sorteioId) {
    $numParticipantes = rand(5, 15);
    $totalParticipantes += $numParticipantes;
    
    $stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                          VALUES (?, ?, ?, ?, ?)");
                          
    for ($i = 1; $i <= $numParticipantes; $i++) {
        $nome = "Participante $i do Sorteio $sorteioId";
        $whatsapp = "5511" . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $cpf = str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
        $email = "teste$i.sorteio$sorteioId@example.com";
        
        $stmt->execute([$sorteioId, $nome, $whatsapp, $cpf, $email]);
    }
}

echo "✓ $totalParticipantes participantes adicionados com sucesso!\n";

// Realizar sorteios
require_once ROOT_PATH . '/includes/classes/SorteioEngine.php';
$sorteioEngine = new SorteioEngine($pdo);

$totalSorteados = 0;
foreach ($sorteiosIds as $sorteioId) {
    $qtdSorteados = rand(1, 3);
    $totalSorteados += $qtdSorteados;
    $resultado = $sorteioEngine->executeSorteio($sorteioId, $qtdSorteados);
}

echo "✓ $totalSorteados ganhadores sorteados com sucesso!\n";

// Etapa 2: Testar geração de relatório de participantes
echo "\n2. Testando relatório de participantes...\n";

// Incluir a classe RelatorioExporter
require_once ROOT_PATH . '/includes/classes/RelatorioExporter.php';

// Criar instância do exportador de relatórios
$relatorioExporter = new RelatorioExporter($pdo);

// Gerar relatório de participantes
$sorteioId = $sorteiosIds[0];
$relatorioData = $relatorioExporter->generateParticipantesReport($sorteioId);

// Verificar se o relatório foi gerado corretamente
if (isset($relatorioData['participantes']) && is_array($relatorioData['participantes'])) {
    echo "✓ Relatório de participantes gerado com sucesso!\n";
    echo "  Total de participantes no relatório: " . count($relatorioData['participantes']) . "\n";
} else {
    echo "✗ Falha ao gerar relatório de participantes!\n";
    exit(1);
}

// Etapa 3: Testar exportação para CSV
echo "\n3. Testando exportação para CSV...\n";

// Exportar para CSV
$csvContent = $relatorioExporter->exportToCSV($relatorioData['participantes']);

// Verificar se o CSV foi gerado corretamente
if (strlen($csvContent) > 0 && strpos($csvContent, 'Nome,WhatsApp,CPF,Email') !== false) {
    echo "✓ Exportação para CSV realizada com sucesso!\n";
    echo "  Tamanho do arquivo CSV: " . strlen($csvContent) . " bytes\n";
} else {
    echo "✗ Falha ao exportar para CSV!\n";
    exit(1);
}

// Etapa 4: Testar exportação para PDF
echo "\n4. Testando exportação para PDF...\n";

// Exportar para PDF
$pdfPath = ROOT_PATH . '/data/exports/test_report.pdf';
$result = $relatorioExporter->exportToPDF($relatorioData, "Relatório de Teste", $pdfPath);

// Verificar se o PDF foi gerado corretamente
if ($result && file_exists($pdfPath)) {
    echo "✓ Exportação para PDF realizada com sucesso!\n";
    echo "  Arquivo PDF gerado em: $pdfPath\n";
} else {
    echo "✗ Falha ao exportar para PDF!\n";
    exit(1);
}

// Etapa 5: Testar relatório de sorteios
echo "\n5. Testando relatório de sorteios...\n";

// Gerar relatório de sorteios
$relatorioSorteios = $relatorioExporter->generateSorteiosReport();

// Verificar se o relatório foi gerado corretamente
if (isset($relatorioSorteios['sorteios']) && is_array($relatorioSorteios['sorteios'])) {
    echo "✓ Relatório de sorteios gerado com sucesso!\n";
    echo "  Total de sorteios no relatório: " . count($relatorioSorteios['sorteios']) . "\n";
} else {
    echo "✗ Falha ao gerar relatório de sorteios!\n";
    exit(1);
}

// Etapa 6: Testar relatório de ganhadores
echo "\n6. Testando relatório de ganhadores...\n";

// Gerar relatório de ganhadores
$relatorioGanhadores = $relatorioExporter->generateGanhadoresReport();

// Verificar se o relatório foi gerado corretamente
if (isset($relatorioGanhadores['ganhadores']) && is_array($relatorioGanhadores['ganhadores'])) {
    echo "✓ Relatório de ganhadores gerado com sucesso!\n";
    echo "  Total de ganhadores no relatório: " . count($relatorioGanhadores['ganhadores']) . "\n";
} else {
    echo "✗ Falha ao gerar relatório de ganhadores!\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";