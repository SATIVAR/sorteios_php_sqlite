<?php
/**
 * Endpoint AJAX para Alterar Status de Agendamento
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
if (!isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID e status são obrigatórios']);
    exit;
}

$agendamentoId = $data['id'];
$novoStatus = $data['status'];

// Validar status
$statusValidos = ['ativo', 'pausado', 'erro'];
if (!in_array($novoStatus, $statusValidos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

$db = getDatabase();

try {
    // Verificar se o agendamento existe
    $agendamento = $db->fetchOne(
        "SELECT id, status FROM relatorio_agendamentos WHERE id = ?",
        [$agendamentoId]
    );
    
    if (!$agendamento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado']);
        exit;
    }
    
    // Atualizar status
    $updated = $db->execute(
        "UPDATE relatorio_agendamentos SET status = ?, updated_at = datetime('now') WHERE id = ?",
        [$novoStatus, $agendamentoId]
    );
    
    if ($updated > 0) {
        $statusTexto = [
            'ativo' => 'ativado',
            'pausado' => 'pausado',
            'erro' => 'marcado como erro'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => "Agendamento {$statusTexto[$novoStatus]} com sucesso"
        ]);
    } else {
        throw new Exception('Falha ao atualizar status');
    }
    
} catch (Exception $e) {
    error_log("Erro ao alterar status do agendamento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>