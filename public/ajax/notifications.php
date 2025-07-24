<?php
/**
 * Endpoint AJAX para Sistema de Notificações em Tempo Real
 * Gerencia notificações, alertas e atualizações do sistema
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';

// Configurar headers para JSON e SSE
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
if (!checkRateLimit('notifications_ajax', 120, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verificar CSRF para operações POST
if ($method === 'POST' && !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonError('Token CSRF inválido', 403);
}

try {
    switch ($action) {
        case 'get_all':
            handleGetAllNotifications();
            break;
            
        case 'get_unread':
            handleGetUnreadNotifications();
            break;
            
        case 'mark_read':
            handleMarkAsRead();
            break;
            
        case 'mark_all_read':
            handleMarkAllAsRead();
            break;
            
        case 'delete':
            handleDeleteNotification();
            break;
            
        case 'get_count':
            handleGetUnreadCount();
            break;
            
        case 'subscribe':
            handleSubscribeToUpdates();
            break;
            
        case 'create_custom':
            handleCreateCustomNotification();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no sistema de notificações: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Obtém todas as notificações
 */
function handleGetAllNotifications() {
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    $type = $_GET['type'] ?? '';
    
    $notifications = getNotifications($limit, $offset, $type);
    
    jsonSuccess([
        'notifications' => $notifications,
        'total' => count($notifications),
        'unread_count' => getUnreadNotificationsCount()
    ]);
}

/**
 * Obtém apenas notificações não lidas
 */
function handleGetUnreadNotifications() {
    $notifications = getNotifications(50, 0, '', true);
    
    jsonSuccess([
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
}

/**
 * Marca notificação como lida
 */
function handleMarkAsRead() {
    $notification_id = $_POST['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        jsonError('ID da notificação é obrigatório');
    }
    
    $success = markNotificationAsRead($notification_id);
    
    if ($success) {
        jsonSuccess(['id' => $notification_id], 'Notificação marcada como lida');
    } else {
        jsonError('Erro ao marcar notificação como lida');
    }
}

/**
 * Marca todas as notificações como lidas
 */
function handleMarkAllAsRead() {
    $success = markAllNotificationsAsRead();
    
    if ($success) {
        jsonSuccess([], 'Todas as notificações foram marcadas como lidas');
    } else {
        jsonError('Erro ao marcar notificações como lidas');
    }
}

/**
 * Deleta uma notificação
 */
function handleDeleteNotification() {
    $notification_id = $_POST['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        jsonError('ID da notificação é obrigatório');
    }
    
    $success = deleteNotification($notification_id);
    
    if ($success) {
        jsonSuccess(['id' => $notification_id], 'Notificação removida');
    } else {
        jsonError('Erro ao remover notificação');
    }
}

/**
 * Obtém contagem de notificações não lidas
 */
function handleGetUnreadCount() {
    $count = getUnreadNotificationsCount();
    
    jsonSuccess([
        'count' => $count,
        'has_unread' => $count > 0
    ]);
}

/**
 * Subscreve para atualizações em tempo real (Server-Sent Events)
 */
function handleSubscribeToUpdates() {
    // Configurar headers para SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    
    // Função para enviar evento SSE
    function sendSSEEvent($data, $event = 'message') {
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }
    
    // Loop de monitoramento (máximo 30 segundos)
    $start_time = time();
    $last_check = 0;
    
    while (time() - $start_time < 30) {
        $current_time = time();
        
        // Verificar a cada 5 segundos
        if ($current_time - $last_check >= 5) {
            $notifications = getNotifications(10, 0, '', true);
            $count = count($notifications);
            
            if ($count > 0) {
                sendSSEEvent([
                    'type' => 'notifications_update',
                    'count' => $count,
                    'notifications' => array_slice($notifications, 0, 5) // Enviar apenas as 5 mais recentes
                ], 'notification');
            }
            
            // Enviar heartbeat
            sendSSEEvent(['timestamp' => $current_time], 'heartbeat');
            
            $last_check = $current_time;
        }
        
        sleep(1);
    }
    
    exit;
}

/**
 * Cria notificação personalizada
 */
function handleCreateCustomNotification() {
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'info';
    
    if (empty($title) || empty($message)) {
        jsonError('Título e mensagem são obrigatórios');
    }
    
    $notification_id = createCustomNotification($title, $message, $type);
    
    if ($notification_id) {
        jsonSuccess(['id' => $notification_id], 'Notificação criada com sucesso');
    } else {
        jsonError('Erro ao criar notificação');
    }
}

/**
 * Obtém notificações do sistema
 */
function getNotifications($limit = 20, $offset = 0, $type = '', $unread_only = false) {
    try {
        $db = getDatabase();
        $notifications = [];
        
        // Notificações de participantes recentes
        $participant_notifications = $db->fetchAll("
            SELECT 
                'participant_' || p.id as id,
                'info' as type,
                'Novo Participante' as title,
                'Participante ' || p.nome || ' se inscreveu em ' || s.nome as message,
                p.created_at as timestamp,
                s.nome as sorteio_nome
            FROM participantes p
            JOIN sorteios s ON p.sorteio_id = s.id
            WHERE p.created_at >= datetime('now', '-24 hours')
            ORDER BY p.created_at DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($participant_notifications as $notif) {
            $notifications[] = formatNotification($notif);
        }
        
        // Notificações de sorteios próximos do limite
        $limit_notifications = $db->fetchAll("
            SELECT 
                'limit_' || s.id as id,
                'warning' as type,
                'Limite Próximo' as title,
                'Sorteio ' || s.nome || ' está próximo do limite (' || COUNT(p.id) || '/' || s.max_participantes || ')' as message,
                MAX(p.created_at) as timestamp,
                s.nome as sorteio_nome
            FROM sorteios s
            LEFT JOIN participantes p ON s.id = p.sorteio_id
            WHERE s.status = 'ativo' 
            AND s.max_participantes > 0
            GROUP BY s.id
            HAVING COUNT(p.id) >= (s.max_participantes * 0.8)
            ORDER BY timestamp DESC
        ");
        
        foreach ($limit_notifications as $notif) {
            $notifications[] = formatNotification($notif);
        }
        
        // Notificações de sorteios realizados
        $draw_notifications = $db->fetchAll("
            SELECT 
                'draw_' || sr.id as id,
                'success' as type,
                'Sorteio Realizado' as title,
                'Sorteio realizado em ' || s.nome || ' - ' || COUNT(sr.id) || ' ganhador(es)' as message,
                sr.data_sorteio as timestamp,
                s.nome as sorteio_nome
            FROM sorteio_resultados sr
            JOIN participantes p ON sr.participante_id = p.id
            JOIN sorteios s ON p.sorteio_id = s.id
            WHERE sr.data_sorteio >= datetime('now', '-7 days')
            GROUP BY s.id, DATE(sr.data_sorteio)
            ORDER BY sr.data_sorteio DESC
            LIMIT 10
        ");
        
        foreach ($draw_notifications as $notif) {
            $notifications[] = formatNotification($notif);
        }
        
        // Ordenar por timestamp
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Aplicar filtros
        if (!empty($type)) {
            $notifications = array_filter($notifications, function($notif) use ($type) {
                return $notif['type'] === $type;
            });
        }
        
        if ($unread_only) {
            $read_notifications = getReadNotifications();
            $notifications = array_filter($notifications, function($notif) use ($read_notifications) {
                return !in_array($notif['id'], $read_notifications);
            });
        }
        
        // Aplicar paginação
        return array_slice($notifications, $offset, $limit);
        
    } catch (Exception $e) {
        error_log("Erro ao obter notificações: " . $e->getMessage());
        return [];
    }
}

/**
 * Formata notificação para exibição
 */
function formatNotification($notif) {
    return [
        'id' => $notif['id'],
        'type' => $notif['type'],
        'title' => $notif['title'],
        'message' => $notif['message'],
        'timestamp' => $notif['timestamp'],
        'time_ago' => getTimeAgo($notif['timestamp']),
        'formatted_date' => formatDateBR($notif['timestamp']),
        'sorteio_nome' => $notif['sorteio_nome'] ?? null,
        'read' => isNotificationRead($notif['id'])
    ];
}

/**
 * Verifica se notificação foi lida
 */
function isNotificationRead($notification_id) {
    $read_notifications = getReadNotifications();
    return in_array($notification_id, $read_notifications);
}

/**
 * Obtém lista de notificações lidas
 */
function getReadNotifications() {
    $read_file = DATA_PATH . '/cache/notifications_read.json';
    
    if (file_exists($read_file)) {
        return json_decode(file_get_contents($read_file), true) ?: [];
    }
    
    return [];
}

/**
 * Marca notificação como lida
 */
function markNotificationAsRead($notification_id) {
    try {
        $read_file = DATA_PATH . '/cache/notifications_read.json';
        $read_notifications = getReadNotifications();
        
        if (!in_array($notification_id, $read_notifications)) {
            $read_notifications[] = $notification_id;
            
            if (!is_dir(dirname($read_file))) {
                mkdir(dirname($read_file), 0755, true);
            }
            
            file_put_contents($read_file, json_encode($read_notifications));
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
        return false;
    }
}

/**
 * Marca todas as notificações como lidas
 */
function markAllNotificationsAsRead() {
    try {
        $notifications = getNotifications(1000); // Obter todas
        $notification_ids = array_column($notifications, 'id');
        
        $read_file = DATA_PATH . '/cache/notifications_read.json';
        
        if (!is_dir(dirname($read_file))) {
            mkdir(dirname($read_file), 0755, true);
        }
        
        file_put_contents($read_file, json_encode($notification_ids));
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao marcar todas as notificações como lidas: " . $e->getMessage());
        return false;
    }
}

/**
 * Deleta uma notificação (adiciona à lista de deletadas)
 */
function deleteNotification($notification_id) {
    try {
        $deleted_file = DATA_PATH . '/cache/notifications_deleted.json';
        $deleted_notifications = [];
        
        if (file_exists($deleted_file)) {
            $deleted_notifications = json_decode(file_get_contents($deleted_file), true) ?: [];
        }
        
        if (!in_array($notification_id, $deleted_notifications)) {
            $deleted_notifications[] = $notification_id;
            
            if (!is_dir(dirname($deleted_file))) {
                mkdir(dirname($deleted_file), 0755, true);
            }
            
            file_put_contents($deleted_file, json_encode($deleted_notifications));
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao deletar notificação: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém contagem de notificações não lidas
 */
function getUnreadNotificationsCount() {
    $notifications = getNotifications(100, 0, '', true);
    return count($notifications);
}

/**
 * Cria notificação personalizada
 */
function createCustomNotification($title, $message, $type = 'info') {
    try {
        $custom_file = DATA_PATH . '/cache/custom_notifications.json';
        $custom_notifications = [];
        
        if (file_exists($custom_file)) {
            $custom_notifications = json_decode(file_get_contents($custom_file), true) ?: [];
        }
        
        $notification_id = 'custom_' . uniqid();
        
        $custom_notifications[] = [
            'id' => $notification_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['admin_email'] ?? 'admin'
        ];
        
        if (!is_dir(dirname($custom_file))) {
            mkdir(dirname($custom_file), 0755, true);
        }
        
        file_put_contents($custom_file, json_encode($custom_notifications));
        
        logActivity('Notificação personalizada criada', "Título: {$title}");
        
        return $notification_id;
    } catch (Exception $e) {
        error_log("Erro ao criar notificação personalizada: " . $e->getMessage());
        return false;
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