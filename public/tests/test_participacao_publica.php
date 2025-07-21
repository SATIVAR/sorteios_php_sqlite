<?php
/**
 * Teste do sistema de participação pública
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE PARTICIPAÇÃO PÚBLICA ===\n\n";

// Resetar banco de dados
echo "Preparando ambiente de teste...\n";
resetTestDatabase();

// Etapa 1: Criar um sorteio para teste
echo "\n1. Criando sorteio para teste...\n";
$publicUrl = 'teste_publico';
$stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, campos_config, public_url, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    'Sorteio Teste Público', 
    'Descrição do sorteio para teste de participação pública', 
    100, 
    3,
    json_encode([
        'nome' => true,
        'whatsapp' => true,
        'cpf' => true,
        'email' => true
    ]),
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

// Etapa 2: Testar acesso à URL pública
echo "\n2. Testando acesso à URL pública...\n";

// Simular acesso à página de participação
$output = simulateRequest('/participar.php', 'GET', [], ['url' => $publicUrl]);

// Verificar se a página foi carregada corretamente
if (strpos($output, 'Sorteio Teste Público') !== false) {
    echo "✓ Página de participação carregada com sucesso!\n";
} else {
    echo "✗ Falha ao carregar página de participação!\n";
    echo "Output: " . substr($output, 0, 200) . "...\n";
    exit(1);
}

// Etapa 3: Testar cadastro de participante
echo "\n3. Testando cadastro de participante...\n";

// Dados do participante
$participanteData = [
    'nome' => 'Participante Teste Público',
    'whatsapp' => '5511987654321',
    'cpf' => '12345678909',
    'email' => 'teste_publico@example.com',
    'sorteio_id' => $sorteioId
];

// Simular envio do formulário
$output = simulateRequest('/participar.php', 'POST', $participanteData, ['url' => $publicUrl]);

// Verificar se o participante foi cadastrado
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE sorteio_id = ? AND nome = ?");
$stmt->execute([$sorteioId, $participanteData['nome']]);
$participante = $stmt->fetch(PDO::FETCH_ASSOC);

if ($participante) {
    echo "✓ Participante cadastrado com sucesso!\n";
    echo "  Nome: " . $participante['nome'] . "\n";
    echo "  WhatsApp: " . $participante['whatsapp'] . "\n";
    echo "  CPF: " . $participante['cpf'] . "\n";
    echo "  Email: " . $participante['email'] . "\n";
} else {
    echo "✗ Falha ao cadastrar participante!\n";
    exit(1);
}

// Etapa 4: Testar validação de CPF duplicado
echo "\n4. Testando validação de CPF duplicado...\n";

// Tentar cadastrar participante com mesmo CPF
$participanteData2 = [
    'nome' => 'Outro Participante',
    'whatsapp' => '5511123456789',
    'cpf' => '12345678909', // Mesmo CPF
    'email' => 'outro@example.com',
    'sorteio_id' => $sorteioId
];

// Simular envio do formulário
$output = simulateRequest('/participar.php', 'POST', $participanteData2, ['url' => $publicUrl]);

// Verificar se o sistema impediu o cadastro duplicado
$stmt = $pdo->prepare("SELECT COUNT(*) FROM participantes WHERE sorteio_id = ? AND cpf = ?");
$stmt->execute([$sorteioId, $participanteData2['cpf']]);
$count = $stmt->fetchColumn();

if ($count == 1) {
    echo "✓ Sistema impediu cadastro com CPF duplicado!\n";
} else {
    echo "✗ Falha na validação de CPF duplicado! Encontrados: $count, Esperado: 1\n";
    exit(1);
}

// Etapa 5: Testar limite de participantes
echo "\n5. Testando limite de participantes...\n";

// Alterar limite para 2 participantes
$stmt = $pdo->prepare("UPDATE sorteios SET max_participantes = 2 WHERE id = ?");
$stmt->execute([$sorteioId]);

// Adicionar mais um participante válido
$participanteData3 = [
    'nome' => 'Segundo Participante',
    'whatsapp' => '5511555555555',
    'cpf' => '98765432109',
    'email' => 'segundo@example.com',
    'sorteio_id' => $sorteioId
];

// Simular envio do formulário
$output = simulateRequest('/participar.php', 'POST', $participanteData3, ['url' => $publicUrl]);

// Verificar se o participante foi cadastrado
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE sorteio_id = ? AND cpf = ?");
$stmt->execute([$sorteioId, $participanteData3['cpf']]);
$participante = $stmt->fetch(PDO::FETCH_ASSOC);

if ($participante) {
    echo "✓ Segundo participante cadastrado com sucesso!\n";
} else {
    echo "✗ Falha ao cadastrar segundo participante!\n";
    exit(1);
}

// Tentar adicionar um terceiro participante (deve falhar devido ao limite)
$participanteData4 = [
    'nome' => 'Terceiro Participante',
    'whatsapp' => '5511666666666',
    'cpf' => '11122233344',
    'email' => 'terceiro@example.com',
    'sorteio_id' => $sorteioId
];

// Simular envio do formulário
$output = simulateRequest('/participar.php', 'POST', $participanteData4, ['url' => $publicUrl]);

// Verificar se o sistema impediu o cadastro além do limite
$stmt = $pdo->prepare("SELECT COUNT(*) FROM participantes WHERE sorteio_id = ?");
$stmt->execute([$sorteioId]);
$count = $stmt->fetchColumn();

if ($count == 2) {
    echo "✓ Sistema respeitou o limite de participantes!\n";
} else {
    echo "✗ Falha na validação de limite de participantes! Encontrados: $count, Esperado: 2\n";
    exit(1);
}

// Etapa 6: Testar sorteio inativo
echo "\n6. Testando acesso a sorteio inativo...\n";

// Desativar o sorteio
$stmt = $pdo->prepare("UPDATE sorteios SET status = 'finalizado' WHERE id = ?");
$stmt->execute([$sorteioId]);

// Simular acesso à página de participação
$output = simulateRequest('/participar.php', 'GET', [], ['url' => $publicUrl]);

// Verificar se a página mostra que o sorteio está inativo
if (strpos($output, 'finalizado') !== false || strpos($output, 'encerrado') !== false) {
    echo "✓ Sistema exibe corretamente sorteio finalizado!\n";
} else {
    echo "✗ Falha ao exibir status de sorteio finalizado!\n";
    exit(1);
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";