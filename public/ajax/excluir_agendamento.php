<?php
/**
 * Endpoint AJAX para Excluir Agendamento
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

// Validar ID
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$agendamentoId = $data['id'];
$db = getDatabase();

try {
    // Verificar se o agendamento existe
    $agendamento = $db->fetchOne(
        "SELECT id, nome FROM relatorio_agendamentos WHERE id = ?",
        [$agendamentoId]
    );
    
    if (!$agendamento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado']);
        exit;
    }
    
    // Excluir agendamento
    $deleted = $db->execute(
        "DELETE FROM relatorio_agendamentos WHERE id = ?",
        [$agendamentoId]
    );
    
    if ($deleted > 0) {
        logSystem("Agendamento excluído: {$agendamento['nome']} (ID: {$agendamentoId})", 'INFO');
        
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento excluído com sucesso'
        ]);
    } else {
        throw new Exception('Falha ao excluir agendamento');
    }
    
} catch (Exception $e) {
    error_log("Erro ao excluir agendamento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>