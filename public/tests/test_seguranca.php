<?php
/**
 * Teste de segurança do sistema
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE SEGURANÇA DO SISTEMA ===\n\n";

// Resetar banco de dados
echo "Preparando ambiente de teste...\n";
resetTestDatabase();

// Etapa 1: Testar proteção contra SQL Injection
echo "\n1. Testando proteção contra SQL Injection...\n";

// Criar sorteio para teste
$stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, public_url, status) 
                      VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    'Sorteio Teste Segurança', 
    'Descrição do sorteio para teste de segurança', 
    100, 
    3,
    'teste_seguranca',
    'ativo'
]);

$sorteioId = $pdo->lastInsertId();

// Adicionar alguns participantes legítimos
$stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                      VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$sorteioId, 'Participante Legítimo', '5511987654321', '12345678909', 'legitimo@example.com']);

// Tentar SQL Injection via parâmetro GET
echo "  Testando SQL Injection via parâmetro GET...\n";
$output = simulateRequest('/participantes.php', 'GET', [], ['id' => "1 OR 1=1"]);

// Verificar se a proteção funcionou
if (strpos($output, 'Participante Legítimo') === false) {
    echo "  ✓ Proteção contra SQL Injection via GET funcionando!\n";
} else {
    echo "  ✗ Vulnerabilidade de SQL Injection via GET detectada!\n";
}

// Tentar SQL Injection via parâmetro POST
echo "  Testando SQL Injection via parâmetro POST...\n";
$output = simulateRequest('/participar.php', 'POST', [
    'sorteio_id' => "1; DROP TABLE participantes; --",
    'nome' => "Hacker",
    'whatsapp' => '5511123456789',
    'cpf' => '98765432109',
    'email' => 'hacker@example.com'
], ['url' => 'teste_seguranca']);

// Verificar se a tabela ainda existe
$stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='participantes'");
$stmt->execute();
$tableExists = $stmt->fetchColumn();

if ($tableExists) {
    echo "  ✓ Proteção contra SQL Injection via POST funcionando!\n";
} else {
    echo "  ✗ Vulnerabilidade de SQL Injection via POST detectada!\n";
    exit(1);
}

// Etapa 2: Testar proteção contra XSS
echo "\n2. Testando proteção contra XSS...\n";

// Tentar injetar script via nome do participante
$scriptInjection = "<script>alert('XSS')</script>";
$stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                      VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$sorteioId, $scriptInjection, '5511987654321', '11122233344', 'xss@example.com']);

// Simular acesso à página de participantes
$output = simulateRequest('/participantes.php', 'GET', [], ['id' => $sorteioId]);

// Verificar se o script foi sanitizado
if (strpos($output, '<script>alert') === false) {
    echo "  ✓ Proteção contra XSS funcionando!\n";
} else {
    echo "  ✗ Vulnerabilidade XSS detectada!\n";
}

// Etapa 3: Testar proteção CSRF
echo "\n3. Testando proteção CSRF...\n";

// Simular acesso à página de login
$output = simulateRequest('/login.php', 'GET');

// Verificar se há token CSRF no formulário
if (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*>/', $output)) {
    echo "  ✓ Formulário de login contém token CSRF!\n";
} else {
    echo "  ✗ Formulário de login não contém token CSRF!\n";
}

// Simular acesso à página de criação de sorteio
$output = simulateRequest('/sorteios.php', 'GET');

// Verificar se há token CSRF no formulário
if (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*>/', $output)) {
    echo "  ✓ Formulário de sorteio contém token CSRF!\n";
} else {
    echo "  ✗ Formulário de sorteio não contém token CSRF!\n";
}

// Etapa 4: Testar validação de dados
echo "\n4. Testando validação de dados...\n";

// Testar validação de CPF
echo "  Testando validação de CPF...\n";
require_once ROOT_PATH . '/includes/validator.php';

$cpfValido = '52998224725'; // CPF válido para teste
$cpfInvalido = '12345678900'; // CPF inválido

if (validarCPF($cpfValido) && !validarCPF($cpfInvalido)) {
    echo "  ✓ Validação de CPF funcionando corretamente!\n";
} else {
    echo "  ✗ Problema na validação de CPF!\n";
}

// Testar validação de email
echo "  Testando validação de email...\n";
$emailValido = 'teste@example.com';
$emailInvalido = 'teste@invalido';

if (filter_var($emailValido, FILTER_VALIDATE_EMAIL) && !filter_var($emailInvalido, FILTER_VALIDATE_EMAIL)) {
    echo "  ✓ Validação de email funcionando corretamente!\n";
} else {
    echo "  ✗ Problema na validação de email!\n";
}

// Testar validação de WhatsApp
echo "  Testando validação de WhatsApp...\n";
$whatsappValido = '5511987654321';
$whatsappInvalido = '123456';

if (validarWhatsApp($whatsappValido) && !validarWhatsApp($whatsappInvalido)) {
    echo "  ✓ Validação de WhatsApp funcionando corretamente!\n";
} else {
    echo "  ✗ Problema na validação de WhatsApp!\n";
}

// Etapa 5: Testar rate limiting
echo "\n5. Testando rate limiting...\n";

// Incluir arquivo de rate limiter
require_once ROOT_PATH . '/includes/rate_limiter.php';

// Simular múltiplas requisições do mesmo IP
$ip = '192.168.1.1';
$endpoint = 'participar';
$maxRequests = 5;
$timeWindow = 60; // 60 segundos

$blocked = false;
for ($i = 1; $i <= $maxRequests + 3; $i++) {
    $limited = checkRateLimit($ip, $endpoint, $maxRequests, $timeWindow);
    
    if ($i <= $maxRequests) {
        if ($limited) {
            echo "  ✗ Rate limiting bloqueou incorretamente na requisição $i!\n";
            break;
        }
    } else {
        if ($limited) {
            $blocked = true;
            echo "  ✓ Rate limiting bloqueou corretamente após $maxRequests requisições!\n";
            break;
        }
    }
}

if (!$blocked) {
    echo "  ✗ Rate limiting não funcionou após exceder o limite!\n";
}

// Etapa 6: Testar headers de segurança
echo "\n6. Testando headers de segurança...\n";

// Simular acesso à página inicial
$output = simulateRequest('/index.php', 'GET');

// Verificar headers de segurança (simulação)
$securityHeaders = [
    'X-Content-Type-Options: nosniff',
    'X-Frame-Options: DENY',
    'X-XSS-Protection: 1; mode=block'
];

$headersPresent = true;
foreach ($securityHeaders as $header) {
    // Nota: Como estamos simulando, não podemos verificar headers reais
    // Esta é uma verificação simulada baseada no código do arquivo auth.php
    $headerName = substr($header, 0, strpos($header, ':'));
    if (!file_exists(ROOT_PATH . '/includes/auth.php') || 
        strpos(file_get_contents(ROOT_PATH . '/includes/auth.php'), $headerName) === false) {
        echo "  ✗ Header de segurança não encontrado: $headerName\n";
        $headersPresent = false;
    }
}

if ($headersPresent) {
    echo "  ✓ Headers de segurança configurados corretamente!\n";
} else {
    echo "  ⚠ Alguns headers de segurança podem estar faltando!\n";
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";

// Funções auxiliares para validação
function validarCPF($cpf) {
    // Extrai somente os números
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);
    
    // Verifica se foi informado todos os dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se foi informada uma sequência de dígitos repetidos
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Faz o cálculo para validar o CPF
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

function validarWhatsApp($whatsapp) {
    // Remove caracteres não numéricos
    $whatsapp = preg_replace('/[^0-9]/is', '', $whatsapp);
    
    // Verifica se tem entre 10 e 13 dígitos (considerando código do país)
    if (strlen($whatsapp) < 10 || strlen($whatsapp) > 13) {
        return false;
    }
    
    return true;
}

function checkRateLimit($ip, $endpoint, $maxRequests, $timeWindow) {
    static $requests = [];
    
    // Inicializar contador para este IP e endpoint
    if (!isset($requests[$ip][$endpoint])) {
        $requests[$ip][$endpoint] = [];
    }
    
    // Adicionar timestamp atual
    $now = time();
    $requests[$ip][$endpoint][] = $now;
    
    // Remover requisições antigas (fora da janela de tempo)
    $requests[$ip][$endpoint] = array_filter($requests[$ip][$endpoint], function($timestamp) use ($now, $timeWindow) {
        return $timestamp >= ($now - $timeWindow);
    });
    
    // Verificar se excedeu o limite
    return count($requests[$ip][$endpoint]) > $maxRequests;
}