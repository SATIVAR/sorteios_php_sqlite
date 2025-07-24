<?php
/**
 * Endpoint AJAX para operações com sorteios
 * Suporte a busca, filtros, paginação e operações CRUD
 */

define('SISTEMA_SORTEIOS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/admin_middleware.php';
require_once __DIR__ . '/../includes/validator.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'search':
            handleSearch();
            break;
            
        case 'update_status':
            handleUpdateStatus();
            break;
            
        case 'get_sorteio':
            handleGetSorteio();
            break;
            
        case 'quick_edit':
            handleQuickEdit();
            break;
            
        case 'bulk_action':
            handleBulkAction();
            break;
            
        case 'get_stats':
            handleGetStats();
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    error_log("Erro no AJAX sorteios: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

/**
 * Busca e filtragem de sorteios
 */
function handleSearch() {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = max(1, min(50, intval($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $per_page;
    
    $db = getDatabase();
    
    // Construir query com filtros
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.nome LIKE ? OR s.descricao LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if (!empty($status)) {
        $where_conditions[] = "s.status = ?";
        $params[] = $status;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query principal
    $sql = "
        SELECT s.*, 
               COUNT(DISTINCT p.id) as total_participantes,
               COUNT(DISTINCT sr.id) as total_sorteados
        FROM sorteios s 
        LEFT JOIN participantes p ON s.id = p.sorteio_id 
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        {$where_clause}
        GROUP BY s.id 
        ORDER BY s.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $sorteios = $db->fetchAll($sql, $params);
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM sorteios s {$where_clause}";
    $total_result = $db->fetchOne($count_sql, $params);
    $total = $total_result['total'];
    
    // Processar dados para resposta
    $processed_sorteios = [];
    foreach ($sorteios as $sorteio) {
        $processed_sorteios[] = [
            'id' => $sorteio['id'],
            'nome' => $sorteio['nome'],
            'descricao' => $sorteio['descricao'],
            'status' => $sorteio['status'],
            'max_participantes' => $sorteio['max_participantes'],
            'qtd_sorteados' => $sorteio['qtd_sorteados'],
            'total_participantes' => $sorteio['total_participantes'],
            'total_sorteados' => $sorteio['total_sorteados'],
            'public_url' => $sorteio['public_url'],
            'created_at' => $sorteio['created_at'],
            'updated_at' => $sorteio['updated_at'],
            'can_delete' => $sorteio['total_participantes'] == 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $processed_sorteios,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'has_next' => $page < ceil($total / $per_page),
            'has_prev' => $page > 1
        ]
    ]);
}

/**
 * Atualizar status de um sorteio
 */
function handleUpdateStatus() {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Token CSRF inválido');
    }
    
    $sorteio_id = intval($_POST['sorteio_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    if (!$sorteio_id || !in_array($new_status, ['ativo', 'pausado', 'finalizado'])) {
        throw new Exception('Dados inválidos');
    }
    
    $db = getDatabase();
    
    // Verificar se o sorteio existe
    $sorteio = $db->fetchOne("SELECT * FROM sorteios WHERE id = ?", [$sorteio_id]);
    if (!$sorteio) {
        throw new Exception('Sorteio não encontrado');
    }
    
    // Atualizar status
    $db->execute(
        "UPDATE sorteios SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$new_status, $sorteio_id]
    );
    
    logActivity('Status do sorteio alterado', "ID: {$sorteio_id}, Status: {$new_status}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso',
        'data' => [
            'id' => $sorteio_id,
            'status' => $new_status
        ]
    ]);
}

/**
 * Obter dados de um sorteio específico
 */
function handleGetSorteio() {
    $sorteio_id = intval($_GET['id'] ?? 0);
    
    if (!$sorteio_id) {
        throw new Exception('ID do sorteio não informado');
    }
    
    $db = getDatabase();
    
    $sql = "
        SELECT s.*, 
               COUNT(DISTINCT p.id) as total_participantes,
               COUNT(DISTINCT sr.id) as total_sorteados
        FROM sorteios s 
        LEFT JOIN participantes p ON s.id = p.sorteio_id 
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        WHERE s.id = ?
        GROUP BY s.id
    ";
    
    $sorteio = $db->fetchOne($sql, [$sorteio_id]);
    
    if (!$sorteio) {
        throw new Exception('Sorteio não encontrado');
    }
    
    // Decodificar configuração de campos
    if ($sorteio['campos_config']) {
        $sorteio['campos_config'] = json_decode($sorteio['campos_config'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $sorteio
    ]);
}

/**
 * Edição rápida de sorteio
 */
function handleQuickEdit() {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Token CSRF inválido');
    }
    
    $sorteio_id = intval($_POST['sorteio_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    if (!$sorteio_id || !$field) {
        throw new Exception('Dados inválidos');
    }
    
    $db = getDatabase();
    
    // Verificar se o sorteio existe
    $sorteio = $db->fetchOne("SELECT * FROM sorteios WHERE id = ?", [$sorteio_id]);
    if (!$sorteio) {
        throw new Exception('Sorteio não encontrado');
    }
    
    // Validar campo e valor
    $allowed_fields = ['nome', 'descricao', 'max_participantes', 'qtd_sorteados'];
    if (!in_array($field, $allowed_fields)) {
        throw new Exception('Campo não permitido para edição rápida');
    }
    
    // Validações específicas
    switch ($field) {
        case 'nome':
            if (empty(trim($value)) || strlen($value) > 255) {
                throw new Exception('Nome inválido');
            }
            break;
        case 'descricao':
            if (strlen($value) > 1000) {
                throw new Exception('Descrição muito longa');
            }
            break;
        case 'max_participantes':
        case 'qtd_sorteados':
            if (!is_numeric($value) || intval($value) < 0) {
                throw new Exception('Valor numérico inválido');
            }
            if ($field === 'qtd_sorteados' && intval($value) < 1) {
                throw new Exception('Quantidade de sorteados deve ser pelo menos 1');
            }
            $value = intval($value);
            break;
    }
    
    // Atualizar campo
    $db->execute(
        "UPDATE sorteios SET {$field} = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$value, $sorteio_id]
    );
    
    logActivity('Sorteio editado (edição rápida)', "ID: {$sorteio_id}, Campo: {$field}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Campo atualizado com sucesso',
        'data' => [
            'id' => $sorteio_id,
            'field' => $field,
            'value' => $value
        ]
    ]);
}

/**
 * Ações em lote
 */
function handleBulkAction() {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Token CSRF inválido');
    }
    
    $action = $_POST['bulk_action'] ?? '';
    $sorteio_ids = $_POST['sorteio_ids'] ?? [];
    
    if (!$action || !is_array($sorteio_ids) || empty($sorteio_ids)) {
        throw new Exception('Dados inválidos para ação em lote');
    }
    
    // Sanitizar IDs
    $sorteio_ids = array_map('intval', $sorteio_ids);
    $sorteio_ids = array_filter($sorteio_ids, function($id) { return $id > 0; });
    
    if (empty($sorteio_ids)) {
        throw new Exception('Nenhum sorteio válido selecionado');
    }
    
    $db = getDatabase();
    $results = [];
    
    switch ($action) {
        case 'activate':
            foreach ($sorteio_ids as $id) {
                $db->execute("UPDATE sorteios SET status = 'ativo', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
                $results[] = $id;
            }
            $message = count($results) . ' sorteio(s) ativado(s)';
            break;
            
        case 'pause':
            foreach ($sorteio_ids as $id) {
                $db->execute("UPDATE sorteios SET status = 'pausado', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
                $results[] = $id;
            }
            $message = count($results) . ' sorteio(s) pausado(s)';
            break;
            
        case 'finalize':
            foreach ($sorteio_ids as $id) {
                $db->execute("UPDATE sorteios SET status = 'finalizado', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
                $results[] = $id;
            }
            $message = count($results) . ' sorteio(s) finalizado(s)';
            break;
            
        case 'delete':
            foreach ($sorteio_ids as $id) {
                // Verificar se tem participantes
                $participantes = $db->fetchOne("SELECT COUNT(*) as count FROM participantes WHERE sorteio_id = ?", [$id]);
                if ($participantes['count'] == 0) {
                    $db->execute("DELETE FROM sorteios WHERE id = ?", [$id]);
                    $results[] = $id;
                }
            }
            $message = count($results) . ' sorteio(s) excluído(s)';
            break;
            
        default:
            throw new Exception('Ação em lote não reconhecida');
    }
    
    logActivity('Ação em lote executada', "Ação: {$action}, IDs: " . implode(',', $results));
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'action' => $action,
            'affected_ids' => $results
        ]
    ]);
}

/**
 * Obter estatísticas dos sorteios
 */
function handleGetStats() {
    $db = getDatabase();
    
    $stats = [
        'total_sorteios' => 0,
        'sorteios_ativos' => 0,
        'sorteios_pausados' => 0,
        'sorteios_finalizados' => 0,
        'total_participantes' => 0,
        'total_sorteados' => 0,
        'sorteios_recentes' => []
    ];
    
    // Contadores básicos
    $counts = $db->fetchAll("
        SELECT status, COUNT(*) as count 
        FROM sorteios 
        GROUP BY status
    ");
    
    foreach ($counts as $count) {
        $stats['total_sorteios'] += $count['count'];
        switch ($count['status']) {
            case 'ativo':
                $stats['sorteios_ativos'] = $count['count'];
                break;
            case 'pausado':
                $stats['sorteios_pausados'] = $count['count'];
                break;
            case 'finalizado':
                $stats['sorteios_finalizados'] = $count['count'];
                break;
        }
    }
    
    // Total de participantes
    $participantes_result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes");
    $stats['total_participantes'] = $participantes_result['count'];
    
    // Total de sorteados
    $sorteados_result = $db->fetchOne("SELECT COUNT(*) as count FROM sorteio_resultados");
    $stats['total_sorteados'] = $sorteados_result['count'];
    
    // Sorteios recentes (últimos 5)
    $stats['sorteios_recentes'] = $db->fetchAll("
        SELECT s.id, s.nome, s.status, s.created_at,
               COUNT(p.id) as participantes
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}
?>