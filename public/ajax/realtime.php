<?php
/**
 * Endpoint AJAX para Atualizações em Tempo Real
 * Server-Sent Events (SSE) e WebSocket-like functionality
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';

// Rate limiting mais restritivo para tempo real
if (!checkRateLimit('realtime_ajax', 30, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'stream':
            handleRealtimeStream();
            break;
            
        case 'poll':
            handlePolling();
            break;
            
        case 'heartbeat':
            handleHeartbeat();
            break;
            
        case 'get_updates':
            handleGetUpdates();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no sistema de tempo real: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Stream de eventos em tempo real (SSE)
 */
function handleRealtimeStream() {
    // Configurar headers para SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    
    // Desabilitar output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Função para enviar evento SSE
    function sendSSEEvent($data, $event = 'message', $id = null) {
        if ($id) {
            echo "id: $id\n";
        }
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    // Configurações do stream
    $max_duration = 300; // 5 minutos máximo
    $check_interval = 5; // Verificar a cada 5 segundos
    $start_time = time();
    $last_check = 0;
    $last_data_hash = '';
    
    // Enviar evento inicial
    sendSSEEvent([
        'type' => 'connection',
        'message' => 'Conectado ao stream de tempo real',
        'timestamp' => time()
    ], 'connected');
    
    while (time() - $start_time < $max_duration) {
        $current_time = time();
        
        // Verificar se cliente ainda está conectado
        if (connection_aborted()) {
            break;
        }
        
        // Verificar atualizações a cada intervalo
        if ($current_time - $last_check >= $check_interval) {
            $updates = getRealtimeUpdates();
            $current_hash = md5(json_encode($updates));
            
            // Enviar apenas se houver mudanças
            if ($current_hash !== $last_data_hash) {
                sendSSEEvent($updates, 'update', $current_time);
                $last_data_hash = $current_hash;
            }
            
            // Enviar heartbeat
            sendSSEEvent([
                'timestamp' => $current_time,
                'uptime' => $current_time - $start_time
            ], 'heartbeat');
            
            $last_check = $current_time;
        }
        
        sleep(1);
    }
    
    // Evento de desconexão
    sendSSEEvent([
        'type' => 'disconnect',
        'message' => 'Stream finalizado',
        'timestamp' => time()
    ], 'disconnected');
    
    exit;
}

/**
 * Polling para atualizações (alternativa ao SSE)
 */
function handlePolling() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    $last_update = $_GET['last_update'] ?? 0;
    $updates = getRealtimeUpdates($last_update);
    
    jsonSuccess([
        'updates' => $updates,
        'timestamp' => time(),
        'has_updates' => !empty($updates['changes'])
    ]);
}

/**
 * Heartbeat para manter conexão ativa
 */
function handleHeartbeat() {
    header('Content-Type: application/json');
    
    $db = getDatabase();
    
    // Verificar status básico do sistema
    $status = [
        'timestamp' => time(),
        'database' => 'ok',
        'memory_usage' => memory_get_usage(true),
        'active_connections' => 1 // Simplificado para hospedagem compartilhada
    ];
    
    try {
        $db->fetchOne("SELECT 1");
    } catch (Exception $e) {
        $status['database'] = 'error';
    }
    
    jsonSuccess($status);
}

/**
 * Obtém atualizações específicas
 */
function handleGetUpdates() {
    $types = $_GET['types'] ?? 'all';
    $since = $_GET['since'] ?? 0;
    
    $updates = getRealtimeUpdates($since, explode(',', $types));
    
    jsonSuccess($updates);
}

/**
 * Obtém atualizações em tempo real
 */
function getRealtimeUpdates($since = 0, $types = ['all']) {
    try {
        $db = getDatabase();
        $updates = [
            'timestamp' => time(),
            'changes' => []
        ];
        
        $since_datetime = date('Y-m-d H:i:s', $since);
        
        // Novos participantes
        if (in_array('all', $types) || in_array('participants', $types)) {
            $new_participants = $db->fetchAll("
                SELECT p.id, p.nome, p.created_at, s.nome as sorteio_nome, s.id as sorteio_id
                FROM participantes p
                JOIN sorteios s ON p.sorteio_id = s.id
                WHERE p.created_at > ?
                ORDER BY p.created_at DESC
                LIMIT 10
            ", [$since_datetime]);
            
            if (!empty($new_participants)) {
                $updates['changes']['new_participants'] = array_map(function($p) {
                    return [
                        'id' => $p['id'],
                        'nome' => $p['nome'],
                        'sorteio_nome' => $p['sorteio_nome'],
                        'sorteio_id' => $p['sorteio_id'],
                        'created_at' => $p['created_at'],
                        'time_ago' => getTimeAgo($p['created_at'])
                    ];
                }, $new_participants);
            }
        }
        
        // Novos sorteios
        if (in_array('all', $types) || in_array('sorteios', $types)) {
            $new_sorteios = $db->fetchAll("
                SELECT id, nome, status, created_at
                FROM sorteios
                WHERE created_at > ? OR updated_at > ?
                ORDER BY COALESCE(updated_at, created_at) DESC
                LIMIT 5
            ", [$since_datetime, $since_datetime]);
            
            if (!empty($new_sorteios)) {
                $updates['changes']['sorteios_updated'] = array_map(function($s) {
                    return [
                        'id' => $s['id'],
                        'nome' => $s['nome'],
                        'status' => $s['status'],
                        'created_at' => $s['created_at']
                    ];
                }, $new_sorteios);
            }
        }
        
        // Resultados de sorteios
        if (in_array('all', $types) || in_array('results', $types)) {
            $new_results = $db->fetchAll("
                SELECT sr.id, sr.data_sorteio, p.nome as participante_nome, s.nome as sorteio_nome, s.id as sorteio_id
                FROM sorteio_resultados sr
                JOIN participantes p ON sr.participante_id = p.id
                JOIN sorteios s ON p.sorteio_id = s.id
                WHERE sr.data_sorteio > ?
                ORDER BY sr.data_sorteio DESC
                LIMIT 5
            ", [$since_datetime]);
            
            if (!empty($new_results)) {
                $updates['changes']['new_results'] = array_map(function($r) {
                    return [
                        'id' => $r['id'],
                        'participante_nome' => $r['participante_nome'],
                        'sorteio_nome' => $r['sorteio_nome'],
                        'sorteio_id' => $r['sorteio_id'],
                        'data_sorteio' => $r['data_sorteio'],
                        'time_ago' => getTimeAgo($r['data_sorteio'])
                    ];
                }, $new_results);
            }
        }
        
        // Métricas atualizadas
        if (in_array('all', $types) || in_array('metrics', $types)) {
            $metrics = [
                'total_sorteios' => $db->fetchOne("SELECT COUNT(*) as count FROM sorteios")['count'],
                'total_participantes' => $db->fetchOne("SELECT COUNT(*) as count FROM participantes")['count'],
                'sorteios_ativos' => $db->fetchOne("SELECT COUNT(*) as count FROM sorteios WHERE status = 'ativo'")['count'],
                'participantes_hoje' => $db->fetchOne("SELECT COUNT(*) as count FROM participantes WHERE DATE(created_at) = DATE('now')")['count']
            ];
            
            $updates['changes']['metrics'] = $metrics;
        }
        
        // Status de sorteios próximos do limite
        if (in_array('all', $types) || in_array('alerts', $types)) {
            $alerts = $db->fetchAll("
                SELECT s.id, s.nome, s.max_participantes, COUNT(p.id) as current_count
                FROM sorteios s
                LEFT JOIN participantes p ON s.id = p.sorteio_id
                WHERE s.status = 'ativo' AND s.max_participantes > 0
                GROUP BY s.id
                HAVING current_count >= (s.max_participantes * 0.9)
                ORDER BY (current_count / s.max_participantes) DESC
            ");
            
            if (!empty($alerts)) {
                $updates['changes']['alerts'] = array_map(function($a) {
                    $percentage = round(($a['current_count'] / $a['max_participantes']) * 100);
                    return [
                        'id' => $a['id'],
                        'nome' => $a['nome'],
                        'current_count' => $a['current_count'],
                        'max_participantes' => $a['max_participantes'],
                        'percentage' => $percentage,
                        'type' => $percentage >= 100 ? 'full' : 'near_full'
                    ];
                }, $alerts);
            }
        }
        
        return $updates;
        
    } catch (Exception $e) {
        error_log("Erro ao obter atualizações em tempo real: " . $e->getMessage());
        return [
            'timestamp' => time(),
            'changes' => [],
            'error' => 'Erro ao obter atualizações'
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
?>