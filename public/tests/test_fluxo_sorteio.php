<?php
/**
 * Teste do fluxo completo de criação e execução de sorteio
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE FLUXO COMPLETO DE SORTEIO ===\n\n";

// Resetar banco de dados
echo "Preparando ambiente de teste...\n";
resetTestDatabase();

// Etapa 1: Criar um novo sorteio
echo "\n1. Testando criação de sorteio...\n";
$sorteioData = [
    'nome' => 'Sorteio de Teste Automatizado',
    'descricao' => 'Descrição do sorteio para teste automatizado',
    'max_participantes' => 100,
    'qtd_sorteados' => 3,
    'campos_config' => json_encode([
        'nome' => true,
        'whatsapp' => true,
        'cpf' => true,
        'email' => true
    ])
];

$stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, campos_config, public_url, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
$publicUrl = generateRandomString(8);
$stmt->execute([
    $sorteioData['nome'], 
    $sorteioData['descricao'], 
    $sorteioData['max_participantes'], 
    $sorteioData['qtd_sorteados'],
    $sorteioData['campos_config'],
    $publicUrl,
    'ativo'
]);

$sorteioId = $pdo->lastInsertId();

if ($sorteioId) {
    echo "✓ Sorteio criado com sucesso! ID: $sorteioId, URL pública: $publicUrl\n";
} else {
    echo "✗ Falha ao criar sorteio!\n";
    exit(1);
}

// Etapa 2: Adicionar participantes
echo "\n2. Testando cadastro de participantes...\n";
$numParticipantes = 20;
$participantesIds = [];

$stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                      VALUES (?, ?, ?, ?, ?)");

for ($i = 1; $i <= $numParticipantes; $i++) {
    $nome = "Participante Teste $i";
    $whatsapp = "5511" . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    $cpf = str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
    $email = "teste$i@example.com";
    
    $stmt->execute([$sorteioId, $nome, $whatsapp, $cpf, $email]);
    $participantesIds[] = $pdo->lastInsertId();
}

// Verificar se os participantes foram adicionados
$stmt = $pdo->prepare("SELECT COUNT(*) FROM participantes WHERE sorteio_id = ?");
$stmt->execute([$sorteioId]);
$count = $stmt->fetchColumn();

if ($count == $numParticipantes) {
    echo "✓ $count participantes adicionados com sucesso!\n";
} else {
    echo "✗ Falha ao adicionar participantes! Encontrados: $count, Esperados: $numParticipantes\n";
    exit(1);
}

// Etapa 3: Executar o sorteio
echo "\n3. Testando execução do sorteio...\n";

// Incluir a classe SorteioEngine
require_once ROOT_PATH . '/includes/classes/SorteioEngine.php';

// Criar instância do motor de sorteio
$sorteioEngine = new SorteioEngine($pdo);

// Executar o sorteio
$qtdSorteados = 3;
$resultado = $sorteioEngine->executeSorteio($sorteioId, $qtdSorteados);

if (count($resultado) == $qtdSorteados) {
    echo "✓ Sorteio executado com sucesso! " . $qtdSorteados . " ganhadores selecionados.\n";
    
    echo "\nGanhadores:\n";
    foreach ($resultado as $index => $ganhador) {
        echo ($index + 1) . ". " . $ganhador['nome'] . " (ID: " . $ganhador['id'] . ")\n";
    }
} else {
    echo "✗ Falha ao executar sorteio! Ganhadores encontrados: " . count($resultado) . ", Esperados: $qtdSorteados\n";
    exit(1);
}

// Etapa 4: Verificar resultados salvos no banco
echo "\n4. Verificando resultados no banco de dados...\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sorteio_resultados WHERE sorteio_id = ?");
$stmt->execute([$sorteioId]);
$count = $stmt->fetchColumn();

if ($count == $qtdSorteados) {
    echo "✓ Resultados salvos com sucesso no banco de dados!\n";
} else {
    echo "✗ Falha ao salvar resultados! Encontrados: $count, Esperados: $qtdSorteados\n";
    exit(1);
}

// Etapa 5: Verificar histórico
echo "\n5. Verificando histórico de sorteios...\n";

$stmt = $pdo->prepare("SELECT * FROM sorteios WHERE id = ?");
$stmt->execute([$sorteioId]);
$sorteio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($sorteio) {
    echo "✓ Sorteio encontrado no histórico!\n";
    echo "  Nome: " . $sorteio['nome'] . "\n";
    echo "  Status: " . $sorteio['status'] . "\n";
    echo "  URL pública: " . $sorteio['public_url'] . "\n";
} else {
    echo "✗ Falha ao encontrar sorteio no histórico!\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";

// Função auxiliar para gerar string aleatória
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}