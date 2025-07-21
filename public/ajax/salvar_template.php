<?php
/**
 * Endpoint AJAX para Salvar Templates de Relatórios
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar CSRF token
if (!verifyCSRFToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

// Obter dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Validar dados obrigatórios
$requiredFields = ['nome', 'tipo', 'filtros', 'configuracao'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
        exit;
    }
}

$db = getDatabase();

try {
    // Verificar se já existe um template com o mesmo nome
    $existingTemplate = $db->fetchOne(
        "SELECT id FROM relatorio_templates WHERE nome = ?",
        [$data['nome']]
    );
    
    if ($existingTemplate) {
        echo json_encode(['success' => false, 'message' => 'Já existe um template com este nome']);
        exit;
    }
    
    // Preparar dados para inserção
    $nome = trim($data['nome']);
    $descricao = isset($data['descricao']) ? trim($data['descricao']) : '';
    $tipo = $data['tipo'];
    $configuracao = json_encode([
        'tipo_relatorio' => $tipo,
        'filtros' => $data['filtros'],
        'configuracao_adicional' => $data['configuracao'],
        'versao' => '1.0'
    ]);
    
    // Inserir template no banco
    $templateId = $db->insert(
        "INSERT INTO relatorio_templates (nome, descricao, tipo, configuracao, created_at) 
         VALUES (?, ?, ?, ?, datetime('now'))",
        [$nome, $descricao, $tipo, $configuracao]
    );
    
    if ($templateId) {
        echo json_encode([
            'success' => true,
            'message' => 'Template salvo com sucesso',
            'template_id' => $templateId
        ]);
    } else {
        throw new Exception('Falha ao salvar template no banco de dados');
    }
    
} catch (Exception $e) {
    error_log("Erro ao salvar template: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>