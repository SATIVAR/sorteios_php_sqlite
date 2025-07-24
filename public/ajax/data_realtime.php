<?php
/**
 * Endpoint AJAX para Dados em Tempo Real
 * Fornece dados atualizados para sorteios, participantes e resultados
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');

// Verificar se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    jsonError('Requisição inválida', 400);
}

// Rate limiting
if (!checkRateLimit('data_realtime_ajax', 30, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_sorteios':
            handleGetSorteios();
            break;
            
        case 'get_participantes':
            handleGetParticipantes();
            break;
            
        case 'get_resultados':
            handleGetResultados();
            break;
            
        case 'get_recent_activity':
            handleGetRecentActivity();
            break;
            
        case 'search_sorteios':
            handleSearchSorteios();
            break;
            
        case 'search_participantes':
            handleSearchParticipantes();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no sistema de dados em tempo real: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Obtém lista de sorteios
 */
function handleGetSorteios() {
    $status = $_GET['status'] ?? '';
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $sorteios = getSorteios($status, $limit, $offset);
    
    jsonSuccess([
        'sorteios' => $sorteios,
        'total' => count($sorteios),
        'timestamp' => time()
    ]);
}

/**
 * Obtém lista de participantes
 */
function handleGetParticipantes() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $participantes = getParticipantes($sorteio_id, $limit, $offset);
    
    jsonSuccess([
        'participantes' => $participantes,
        'total' => count($participantes),
        'timestamp' => time()
    ]);
}

/**
 * Obtém resultados de sorteios
 */
function handleGetResultados() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $resultados = getResultados($sorteio_id, $limit, $offset);
    
    jsonSuccess([
        'resultados' => $resultados,
        'total' => count($resultados),
        'timestamp' => time()
    ]);
}

/**
 * Obtém atividades recentes
 */
function handleGetRecentActivity() {
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $activity = getRecentActivity($limit);
    
    jsonSuccess([
        'activity' => $activity,
        'timestamp' => time()
    ]);
}

/**
 * Busca sorteios
 */
function handleSearchSorteios() {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $sorteios = searchSorteios($search, $status, $limit, $offset);
    
    jsonSuccess([
        'sorteios' => $sorteios['results'],
        'total' => $sorteios['total'],
        'timestamp' => time()
    ]);
}

/**
 * Busca participantes
 */
function handleSearchParticipantes() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    $search = $_GET['search'] ?? '';
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $participantes = searchParticipantes($sorteio_id, $search, $limit, $offset);
    
    jsonSuccess([
        'participantes' => $participantes['results'],
        'total' => $participantes['total'],
        'timestamp' => time()
    ]);
}

/**
 * Obtém lista de sorteios
 */
function getSorteios($status = '', $limit = 20, $offset = 0) {
    try {
        $db = getDatabase();
        
        $where_clause = '';
        $params = [];
        
        if ($status) {
            $where_clause = 'WHERE status = ?';
            $params[] = $status;
        }
        
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
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $sorteios = $db->fetchAll($sql, $params);
        
        // Formatar dados para resposta
        return array_map(function($sorteio) {
            return [
                'id' => $sorteio['id'],
                'nome' => $sorteio['nome'],
                'descricao' => $sorteio['descricao'],
                'status' => $sorteio['status'],
                'max_participantes' => $sorteio['max_participantes'],
                'qtd_sorteados' => $sorteio['qtd_sorteados'],
                'total_participantes' => (int)$sorteio['total_participantes'],
                'total_sorteados' => (int)$sorteio['total_sorteados'],
                'public_url' => $sorteio['public_url'],
                'created_at' => $sorteio['created_at'],
                'updated_at' => $sorteio['updated_at'],
                'formatted_date' => formatDateBR($sorteio['created_at'])
            ];
        }, $sorteios);
        
    } catch (Exception $e) {
        error_log("Erro ao obter sorteios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém lista de participantes
 */
function getParticipantes($sorteio_id, $limit = 20, $offset = 0) {
    try {
        $db = getDatabase();
        
        $sql = "
            SELECT p.*, 
                   CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END as foi_sorteado,
                   sr.posicao as posicao_sorteio
            FROM participantes p 
            LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
            WHERE p.sorteio_id = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $participantes = $db->fetchAll($sql, [$sorteio_id, $limit, $offset]);
        
        // Formatar dados para resposta
        return array_map(function($participante) {
            return [
                'id' => $participante['id'],
                'nome' => $participante['nome'],
                'whatsapp' => $participante['whatsapp'],
                'whatsapp_formatted' => formatWhatsApp($participante['whatsapp'] ?? ''),
                'cpf' => $participante['cpf'],
                'cpf_formatted' => formatCPF($participante['cpf'] ?? ''),
                'email' => $participante['email'],
                'foi_sorteado' => (bool)$participante['foi_sorteado'],
                'posicao_sorteio' => $participante['posicao_sorteio'],
                'created_at' => $participante['created_at'],
                'formatted_date' => formatDateBR($participante['created_at'])
            ];
        }, $participantes);
        
    } catch (Exception $e) {
        error_log("Erro ao obter participantes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém resultados de sorteios
 */
function getResultados($sorteio_id, $limit = 20, $offset = 0) {
    try {
        $db = getDatabase();
        
        $sql = "
            SELECT sr.*, p.nome, p.whatsapp, p.cpf, p.email
            FROM sorteio_resultados sr
            JOIN participantes p ON sr.participante_id = p.id
            WHERE sr.sorteio_id = ?
            ORDER BY sr.data_sorteio DESC, sr.posicao ASC
            LIMIT ? OFFSET ?
        ";
        
        $resultados = $db->fetchAll($sql, [$sorteio_id, $limit, $offset]);
        
        // Formatar dados para resposta
        return array_map(function($resultado) {
            return [
                'id' => $resultado['id'],
                'participante_id' => $resultado['participante_id'],
                'nome' => $resultado['nome'],
                'whatsapp' => $resultado['whatsapp'],
                'whatsapp_formatted' => formatWhatsApp($resultado['whatsapp'] ?? ''),
                'cpf' => $resultado['cpf'],
                'cpf_formatted' => formatCPF($resultado['cpf'] ?? ''),
                'email' => $resultado['email'],
                'posicao' => $resultado['posicao'],
                'resultado_id' => $resultado['resultado_id'],
                'data_sorteio' => $resultado['data_sorteio'],
                'formatted_date' => formatDateBR($resultado['data_sorteio'])
            ];
        }, $resultados);
        
    } catch (Exception $e) {
        error_log("Erro ao obter resultados: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém atividades recentes
 */
function getRecentActivity($limit = 10) {
    try {
        $db = getDatabase();
        
        // Atividades recentes (sorteios criados, participantes cadastrados, sorteios realizados)
        $activities = [];
        
        // Sorteios criados recentemente
        $sorteios_criados = $db->fetchAll("
            SELECT 'sorteio_criado' as type, id, nome, created_at as timestamp
            FROM sorteios
            ORDER BY created_at DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($sorteios_criados as $item) {
            $activities[] = [
                'type' => 'sorteio_criado',
                'entity_id' => $item['id'],
                'title' => 'Sorteio Criado',
                'message' => "Sorteio '{$item['nome']}' foi criado",
                'timestamp' => $item['timestamp'],
                'formatted_date' => formatDateBR($item['timestamp']),
                'time_ago' => getTimeAgo($item['timestamp'])
            ];
        }
        
        // Participantes cadastrados recentemente
        $participantes_cadastrados = $db->fetchAll("
            SELECT 'participante_cadastrado' as type, p.id, p.nome, p.created_at as timestamp, s.id as sorteio_id, s.nome as sorteio_nome
            FROM participantes p
            JOIN sorteios s ON p.sorteio_id = s.id
            ORDER BY p.created_at DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($participantes_cadastrados as $item) {
            $activities[] = [
                'type' => 'participante_cadastrado',
                'entity_id' => $item['id'],
                'sorteio_id' => $item['sorteio_id'],
                'title' => 'Participante Cadastrado',
                'message' => "Participante '{$item['nome']}' se cadastrou em '{$item['sorteio_nome']}'",
                'timestamp' => $item['timestamp'],
                'formatted_date' => formatDateBR($item['timestamp']),
                'time_ago' => getTimeAgo($item['timestamp'])
            ];
        }
        
        // Sorteios realizados recentemente
        $sorteios_realizados = $db->fetchAll("
            SELECT 'sorteio_realizado' as type, sr.resultado_id, sr.data_sorteio as timestamp, s.id as sorteio_id, s.nome as sorteio_nome, COUNT(DISTINCT sr.participante_id) as total_sorteados
            FROM sorteio_resultados sr
            JOIN participantes p ON sr.participante_id = p.id
            JOIN sorteios s ON p.sorteio_id = s.id
            GROUP BY sr.resultado_id
            ORDER BY sr.data_sorteio DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($sorteios_realizados as $item) {
            $activities[] = [
                'type' => 'sorteio_realizado',
                'entity_id' => $item['resultado_id'],
                'sorteio_id' => $item['sorteio_id'],
                'title' => 'Sorteio Realizado',
                'message' => "Sorteio realizado em '{$item['sorteio_nome']}' com {$item['total_sorteados']} ganhador(es)",
                'timestamp' => $item['timestamp'],
                'formatted_date' => formatDateBR($item['timestamp']),
                'time_ago' => getTimeAgo($item['timestamp'])
            ];
        }
        
        // Ordenar por timestamp
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activities, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Erro ao obter atividades recentes: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca sorteios
 */
function searchSorteios($search = '', $status = '', $limit = 20, $offset = 0) {
    try {
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
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $sorteios = $db->fetchAll($sql, $params);
        
        // Contar total
        $count_sql = "SELECT COUNT(*) as total FROM sorteios s {$where_clause}";
        $total_result = $db->fetchOne($count_sql, $params);
        $total = $total_result['total'];
        
        // Formatar dados para resposta
        $processed_sorteios = array_map(function($sorteio) {
            return [
                'id' => $sorteio['id'],
                'nome' => $sorteio['nome'],
                'descricao' => $sorteio['descricao'],
                'status' => $sorteio['status'],
                'max_participantes' => $sorteio['max_participantes'],
                'qtd_sorteados' => $sorteio['qtd_sorteados'],
                'total_participantes' => (int)$sorteio['total_participantes'],
                'total_sorteados' => (int)$sorteio['total_sorteados'],
                'public_url' => $sorteio['public_url'],
                'created_at' => $sorteio['created_at'],
                'updated_at' => $sorteio['updated_at'],
                'formatted_date' => formatDateBR($sorteio['created_at'])
            ];
        }, $sorteios);
        
        return [
            'results' => $processed_sorteios,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        error_log("Erro na busca de sorteios: " . $e->getMessage());
        return [
            'results' => [],
            'total' => 0
        ];
    }
}

/**
 * Busca participantes
 */
function searchParticipantes($sorteio_id, $search = '', $limit = 20, $offset = 0) {
    try {
        $db = getDatabase();
        
        // Construir query com filtros
        $where_conditions = ["p.sorteio_id = ?"];
        $params = [$sorteio_id];
        
        if (!empty($search)) {
            $where_conditions[] = "(p.nome LIKE ? OR p.whatsapp LIKE ? OR p.cpf LIKE ? OR p.email LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Query principal
        $sql = "
            SELECT p.*, 
                   CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END as foi_sorteado,
                   sr.posicao as posicao_sorteio
            FROM participantes p 
            LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
            WHERE {$where_clause}
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $participantes = $db->fetchAll($sql, $params);
        
        // Contar total
        $count_sql = "SELECT COUNT(*) as total FROM participantes p WHERE p.sorteio_id = ?";
        $count_params = [$sorteio_id];
        
        if (!empty($search)) {
            $count_sql .= " AND (p.nome LIKE ? OR p.whatsapp LIKE ? OR p.cpf LIKE ? OR p.email LIKE ?)";
            $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        $total_result = $db->fetchOne($count_sql, $count_params);
        $total = $total_result['total'];
        
        // Formatar dados para resposta
        $processed_participantes = array_map(function($participante) {
            return [
                'id' => $participante['id'],
                'nome' => $participante['nome'],
                'whatsapp' => $participante['whatsapp'],
                'whatsapp_formatted' => formatWhatsApp($participante['whatsapp'] ?? ''),
                'cpf' => $participante['cpf'],
                'cpf_formatted' => formatCPF($participante['cpf'] ?? ''),
                'email' => $participante['email'],
                'foi_sorteado' => (bool)$participante['foi_sorteado'],
                'posicao_sorteio' => $participante['posicao_sorteio'],
                'created_at' => $participante['created_at'],
                'formatted_date' => formatDateBR($participante['created_at'])
            ];
        }, $participantes);
        
        return [
            'results' => $processed_participantes,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        error_log("Erro na busca de participantes: " . $e->getMessage());
        return [
            'results' => [],
            'total' => 0
        ];
    }
}

/**
 * Calcula tempo decorrido em formato amigável
 */
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . ' h atrás';
    if ($time < 2592000) return floor($time/86400) . ' dias atrás';
    if ($time < 31536000) return floor($time/2592000) . ' meses atrás';
    
    return floor($time/31536000) . ' anos atrás';
}