<?php
/**
 * Endpoint AJAX para Gerenciar Templates de Relatórios
 * Listar, carregar e excluir templates
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        listarTemplates();
        break;
    case 'load':
        carregarTemplate();
        break;
    case 'delete':
        excluirTemplate();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
}

function listarTemplates() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    
    $db = getDatabase();
    
    try {
        $tipo = $_GET['tipo'] ?? '';
        $whereClause = '';
        $params = [];
        
        if (!empty($tipo)) {
            $whereClause = 'WHERE tipo = ?';
            $params[] = $tipo;
        }
        
        $templates = $db->fetchAll("
            SELECT id, nome, descricao, tipo, created_at
            FROM relatorio_templates 
            $whereClause
            ORDER BY created_at DESC
        ", $params);
        
        echo json_encode([
            'success' => true,
            'templates' => $templates
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao listar templates: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function carregarTemplate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    
    $templateId = $_GET['id'] ?? '';
    
    if (empty($templateId) || !is_numeric($templateId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do template inválido']);
        return;
    }
    
    $db = getDatabase();
    
    try {
        $template = $db->fetchOne(
            "SELECT * FROM relatorio_templates WHERE id = ?",
            [$templateId]
        );
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template não encontrado']);
            return;
        }
        
        // Decodificar configuração JSON
        $configuracao = json_decode($template['configuracao'], true);
        
        echo json_encode([
            'success' => true,
            'template' => [
                'id' => $template['id'],
                'nome' => $template['nome'],
                'descricao' => $template['descricao'],
                'tipo' => $template['tipo'],
                'configuracao' => $configuracao,
                'created_at' => $template['created_at']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao carregar template: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

function excluirTemplate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    
    // Verificar CSRF token
    if (!verifyCSRFToken()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $templateId = $data['id'] ?? '';
    
    if (empty($templateId) || !is_numeric($templateId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do template inválido']);
        return;
    }
    
    $db = getDatabase();
    
    try {
        // Verificar se o template existe
        $template = $db->fetchOne(
            "SELECT id FROM relatorio_templates WHERE id = ?",
            [$templateId]
        );
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template não encontrado']);
            return;
        }
        
        // Excluir template
        $deleted = $db->execute(
            "DELETE FROM relatorio_templates WHERE id = ?",
            [$templateId]
        );
        
        if ($deleted > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Template excluído com sucesso'
            ]);
        } else {
            throw new Exception('Falha ao excluir template');
        }
        
    } catch (Exception $e) {
        error_log("Erro ao excluir template: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>