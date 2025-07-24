<?php
/**
 * Endpoint AJAX para Agendamento de Relatórios
 * Permite agendar envio automático de relatórios por email
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
$requiredFields = ['nome', 'tipo_relatorio', 'formato', 'frequencia', 'email_destino'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
        exit;
    }
}

$db = getDatabase();

try {
    // Validar email
    if (!filter_var($data['email_destino'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    // Validar frequência
    $frequenciasValidas = ['diario', 'semanal', 'mensal'];
    if (!in_array($data['frequencia'], $frequenciasValidas)) {
        echo json_encode(['success' => false, 'message' => 'Frequência inválida']);
        exit;
    }
    
    // Validar formato
    $formatosValidos = ['pdf', 'csv', 'excel'];
    if (!in_array($data['formato'], $formatosValidos)) {
        echo json_encode(['success' => false, 'message' => 'Formato inválido']);
        exit;
    }
    
    // Calcular próxima execução
    $proximaExecucao = calcularProximaExecucao($data['frequencia']);
    
    // Preparar configuração
    $configuracao = [
        'tipo_relatorio' => $data['tipo_relatorio'],
        'formato' => $data['formato'],
        'filtros' => $data['filtros'] ?? [],
        'email_destino' => $data['email_destino'],
        'frequencia' => $data['frequencia'],
        'ativo' => true,
        'criado_em' => date('Y-m-d H:i:s')
    ];
    
    // Inserir agendamento
    $agendamentoId = $db->insert(
        "INSERT INTO relatorio_agendamentos (nome, configuracao, proxima_execucao, status, created_at) 
         VALUES (?, ?, ?, 'ativo', datetime('now'))",
        [
            trim($data['nome']),
            json_encode($configuracao),
            $proximaExecucao
        ]
    );
    
    if ($agendamentoId) {
        echo json_encode([
            'success' => true,
            'message' => 'Relatório agendado com sucesso',
            'agendamento_id' => $agendamentoId,
            'proxima_execucao' => $proximaExecucao
        ]);
    } else {
        throw new Exception('Falha ao agendar relatório');
    }
    
} catch (Exception $e) {
    error_log("Erro ao agendar relatório: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

/**
 * Calcula próxima execução baseada na frequência
 */
function calcularProximaExecucao($frequencia) {
    $agora = new DateTime();
    
    switch ($frequencia) {
        case 'diario':
            $agora->add(new DateInterval('P1D'));
            break;
        case 'semanal':
            $agora->add(new DateInterval('P7D'));
            break;
        case 'mensal':
            $agora->add(new DateInterval('P1M'));
            break;
        default:
            $agora->add(new DateInterval('P1D'));
    }
    
    return $agora->format('Y-m-d H:i:s');
}
?>