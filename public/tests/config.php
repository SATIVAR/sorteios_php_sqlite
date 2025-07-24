<?php
/**
 * Configuração para ambiente de testes
 */

// Definir ambiente como teste
define('ENVIRONMENT', 'testing');

// Caminho para o diretório raiz
define('ROOT_PATH', dirname(__DIR__));

// Incluir arquivos necessários
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/validator.php';

// Configurar banco de dados de teste
$GLOBALS['db_path'] = ROOT_PATH . '/data/test_sorteios.db';

// Função para limpar o banco de dados de teste
function resetTestDatabase() {
    global $pdo;
    
    // Fechar conexão existente
    $pdo = null;
    
    // Remover banco de dados de teste se existir
    if (file_exists($GLOBALS['db_path'])) {
        unlink($GLOBALS['db_path']);
    }
    
    // Recriar conexão
    $pdo = new PDO('sqlite:' . $GLOBALS['db_path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurar pragmas
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA synchronous=NORMAL');
    $pdo->exec('PRAGMA cache_size=10000');
    $pdo->exec('PRAGMA temp_store=MEMORY');
    
    // Criar tabelas
    include_once ROOT_PATH . '/includes/database_schema.php';
    createDatabaseTables($pdo);
    
    // Inserir dados de teste básicos
    $pdo->exec("INSERT INTO configuracoes (nome_empresa, regras_padrao, admin_email) 
                VALUES ('Empresa Teste', 'Regras padrão para testes', 'admin@teste.com')");
                
    return $pdo;
}

// Função para gerar dados de teste
function generateTestData($numParticipantes = 10) {
    global $pdo;
    
    // Criar sorteio de teste
    $stmt = $pdo->prepare("INSERT INTO sorteios (nome, descricao, max_participantes, qtd_sorteados, public_url, status) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Sorteio Teste', 'Descrição do sorteio para testes', 1000, 3, 'teste123', 'ativo']);
    $sorteioId = $pdo->lastInsertId();
    
    // Criar participantes de teste
    $stmt = $pdo->prepare("INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email) 
                          VALUES (?, ?, ?, ?, ?)");
                          
    for ($i = 1; $i <= $numParticipantes; $i++) {
        $nome = "Participante Teste $i";
        $whatsapp = "5511" . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $cpf = str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
        $email = "teste$i@example.com";
        
        $stmt->execute([$sorteioId, $nome, $whatsapp, $cpf, $email]);
    }
    
    return $sorteioId;
}

// Função para simular requisição HTTP
function simulateRequest($url, $method = 'GET', $postData = [], $getParams = []) {
    // Salvar variáveis globais originais
    $originalGet = $_GET;
    $originalPost = $_POST;
    $originalRequest = $_REQUEST;
    $originalServer = $_SERVER;
    
    // Configurar novas variáveis
    $_GET = $getParams;
    $_POST = $postData;
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $url;
    
    // Iniciar buffer de saída
    ob_start();
    
    // Incluir o arquivo solicitado
    $filePath = ROOT_PATH . $url;
    if (file_exists($filePath)) {
        include $filePath;
    } else {
        echo "Arquivo não encontrado: $filePath";
    }
    
    // Capturar saída
    $output = ob_get_clean();
    
    // Restaurar variáveis globais
    $_GET = $originalGet;
    $_POST = $originalPost;
    $_REQUEST = $originalRequest;
    $_SERVER = $originalServer;
    
    return $output;
}

// Inicializar banco de dados de teste
$pdo = resetTestDatabase();