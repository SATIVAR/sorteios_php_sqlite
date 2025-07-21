<?php
/**
 * Endpoint AJAX para Notificações em Tempo Real
 * Fornece notificações e alertas em tempo real para o dashboard
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
if (!checkRateLimit('notifications_realtime_ajax', 60, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_unread':
            handleGetUnreadNotifications();
            break;
            
        case 'get_all':
            handleGetAllNotifications();
            break;
            
        case 'mark_read':
            handleMarkAsRead();
            break;
            
        case 'mark_all_read':
            handleMarkAllAsRead();
            break;
            
        case 'get_alerts':
            handleGetAlerts();
            break;
            
        case 'get_system_status':
            handleGetSystemStatus();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no sistema de notificações em tempo real: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Obtém notificações não lidas
 */
function handleGetUnreadNotifications() {
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $notifications = getUnreadNotifications($limit);
    
    jsonSuccess([
        'notifications' => $notifications,
        'count' => count($notifications),
        'timestamp' => time()
    ]);
}

/**
 * Obtém todas as notificações
 */
function handleGetAllNotifications() {
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    $type = $_GET['type'] ?? '';
    
    $notifications = getAllNotifications($limit, $offset, $type);
    
    jsonSuccess([
        'notifications' => $notifications,
        'total' => count($notifications),
        'has_more' => count($notifications) >= $limit,
        'timestamp' => time()
    ]);
}

/**
 * Marca notificação como lida
 */
function handleMarkAsRead() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Método não permitido', 405);
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonError('Token CSRF inválido', 403);
    }
    
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Método não permitido', 405);
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonError('Token CSRF inválido', 403);
    }
    
    $success = markAllNotificationsAsRead();
    
    if ($success) {
        jsonSuccess([], 'Todas as notificações foram marcadas como lidas');
    } else {
        jsonError('Erro ao marcar notificações como lidas');
    }
}

/**
 * Obtém alertas do sistema
 */
function handleGetAlerts() {
    $alerts = getSystemAlerts();
    
    jsonSuccess([
        'alerts' => $alerts,
        'count' => count($alerts),
        'has_critical' => hasCriticalAlerts($alerts),
        'timestamp' => time()
    ]);
}

/**
 * Obtém status do sistema
 */
function handleGetSystemStatus() {
    $status = getSystemStatus();
    
    jsonSuccess([
        'status' => $status,
        'timestamp' => time()
    ]);
}

/**
 * Obtém notificações não lidas
 */
function getUnreadNotifications($limit = 10) {
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
                s.nome as sorteio_nome,
                s.id as sorteio_id
            FROM participantes p
            JOIN sorteios s ON p.sorteio_id = s.id
            WHERE p.created_at >= datetime('now', '-24 hours')
            ORDER BY p.created_at DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($participant_notifications as $notif) {
            if (!isNotificationRead($notif['id'])) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Notificações de sorteios próximos do limite
        $limit_notifications = $db->fetchAll("
            SELECT 
                'limit_' || s.id as id,
                'warning' as type,
                'Limite Próximo' as title,
                'Sorteio ' || s.nome || ' está próximo do limite (' || COUNT(p.id) || '/' || s.max_participantes || ')' as message,
                MAX(p.created_at) as timestamp,
                s.nome as sorteio_nome,
                s.id as sorteio_id
            FROM sorteios s
            LEFT JOIN participantes p ON s.id = p.sorteio_id
            WHERE s.status = 'ativo' 
            AND s.max_participantes > 0
            GROUP BY s.id
            HAVING COUNT(p.id) >= (s.max_participantes * 0.8)
            ORDER BY (COUNT(p.id) * 1.0 / s.max_participantes) DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($limit_notifications as $notif) {
            if (!isNotificationRead($notif['id'])) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Notificações de sorteios realizados
        $draw_notifications = $db->fetchAll("
            SELECT 
                'draw_' || sr.resultado_id as id,
                'success' as type,
                'Sorteio Realizado' as title,
                'Sorteio realizado em ' || s.nome || ' - ' || COUNT(DISTINCT sr.participante_id) || ' ganhador(es)' as message,
                MAX(sr.data_sorteio) as timestamp,
                s.nome as sorteio_nome,
                s.id as sorteio_id
            FROM sorteio_resultados sr
            JOIN participantes p ON sr.participante_id = p.id
            JOIN sorteios s ON p.sorteio_id = s.id
            WHERE sr.data_sorteio >= datetime('now', '-7 days')
            GROUP BY sr.resultado_id
            ORDER BY sr.data_sorteio DESC
            LIMIT ?
        ", [$limit]);
        
        foreach ($draw_notifications as $notif) {
            if (!isNotificationRead($notif['id'])) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Ordenar por timestamp
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($notifications, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Erro ao obter notificações não lidas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém todas as notificações
 */
function getAllNotifications($limit = 20, $offset = 0, $type = '') {
    try {
        $db = getDatabase();
        $notifications = [];
        
        // Notificações de participantes recentes
        if ($type == '' || $type == 'participant') {
            $participant_notifications = $db->fetchAll("
                SELECT 
                    'participant_' || p.id as id,
                    'info' as type,
                    'Novo Participante' as title,
                    'Participante ' || p.nome || ' se inscreveu em ' || s.nome as message,
                    p.created_at as timestamp,
                    s.nome as sorteio_nome,
                    s.id as sorteio_id
                FROM participantes p
                JOIN sorteios s ON p.sorteio_id = s.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ", [$limit, $offset]);
            
            foreach ($participant_notifications as $notif) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Notificações de sorteios próximos do limite
        if ($type == '' || $type == 'warning') {
            $limit_notifications = $db->fetchAll("
                SELECT 
                    'limit_' || s.id as id,
                    'warning' as type,
                    'Limite Próximo' as title,
                    'Sorteio ' || s.nome || ' está próximo do limite (' || COUNT(p.id) || '/' || s.max_participantes || ')' as message,
                    MAX(p.created_at) as timestamp,
                    s.nome as sorteio_nome,
                    s.id as sorteio_id
                FROM sorteios s
                LEFT JOIN participantes p ON s.id = p.sorteio_id
                WHERE s.max_participantes > 0
                GROUP BY s.id
                HAVING COUNT(p.id) >= (s.max_participantes * 0.8)
                ORDER BY (COUNT(p.id) * 1.0 / s.max_participantes) DESC
                LIMIT ? OFFSET ?
            ", [$limit, $offset]);
            
            foreach ($limit_notifications as $notif) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Notificações de sorteios realizados
        if ($type == '' || $type == 'success') {
            $draw_notifications = $db->fetchAll("
                SELECT 
                    'draw_' || sr.resultado_id as id,
                    'success' as type,
                    'Sorteio Realizado' as title,
                    'Sorteio realizado em ' || s.nome || ' - ' || COUNT(DISTINCT sr.participante_id) || ' ganhador(es)' as message,
                    MAX(sr.data_sorteio) as timestamp,
                    s.nome as sorteio_nome,
                    s.id as sorteio_id
                FROM sorteio_resultados sr
                JOIN participantes p ON sr.participante_id = p.id
                JOIN sorteios s ON p.sorteio_id = s.id
                GROUP BY sr.resultado_id
                ORDER BY sr.data_sorteio DESC
                LIMIT ? OFFSET ?
            ", [$limit, $offset]);
            
            foreach ($draw_notifications as $notif) {
                $notifications[] = formatNotification($notif);
            }
        }
        
        // Ordenar por timestamp
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($notifications, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Erro ao obter todas as notificações: " . $e->getMessage());
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
        'sorteio_id' => $notif['sorteio_id'] ?? null,
        'read' => isNotificationRead($notif['id'])
    ];
}

/**
 * Verifica se notificação foi lida
 */
function isNotificationRead($notification_id) {
    $read_file = DATA_PATH . '/cache/notifications_read.json';
    
    if (file_exists($read_file)) {
        $read_notifications = json_decode(file_get_contents($read_file), true) ?: [];
        return in_array($notification_id, $read_notifications);
    }
    
    return false;
}

/**
 * Marca notificação como lida
 */
function markNotificationAsRead($notification_id) {
    try {
        $read_file = DATA_PATH . '/cache/notifications_read.json';
        $read_notifications = [];
        
        if (file_exists($read_file)) {
            $read_notifications = json_decode(file_get_contents($read_file), true) ?: [];
        }
        
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
        $db = getDatabase();
        $read_file = DATA_PATH . '/cache/notifications_read.json';
        $read_notifications = [];
        
        if (file_exists($read_file)) {
            $read_notifications = json_decode(file_get_contents($read_file), true) ?: [];
        }
        
        // Obter todos os IDs de notificações
        $participant_ids = $db->fetchAll("
            SELECT 'participant_' || p.id as id
            FROM participantes p
            WHERE p.created_at >= datetime('now', '-30 days')
        ");
        
        $limit_ids = $db->fetchAll("
            SELECT 'limit_' || s.id as id
            FROM sorteios s
            WHERE s.max_participantes > 0
        ");
        
        $draw_ids = $db->fetchAll("
            SELECT 'draw_' || sr.resultado_id as id
            FROM sorteio_resultados sr
            GROUP BY sr.resultado_id
        ");
        
        // Adicionar todos os IDs à lista de lidos
        foreach ($participant_ids as $item) {
            if (!in_array($item['id'], $read_notifications)) {
                $read_notifications[] = $item['id'];
            }
        }
        
        foreach ($limit_ids as $item) {
            if (!in_array($item['id'], $read_notifications)) {
                $read_notifications[] = $item['id'];
            }
        }
        
        foreach ($draw_ids as $item) {
            if (!in_array($item['id'], $read_notifications)) {
                $read_notifications[] = $item['id'];
            }
        }
        
        // Salvar lista atualizada
        if (!is_dir(dirname($read_file))) {
            mkdir(dirname($read_file), 0755, true);
        }
        
        file_put_contents($read_file, json_encode($read_notifications));
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao marcar todas as notificações como lidas: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém alertas do sistema
 */
function getSystemAlerts() {
    try {
        $db = getDatabase();
        $alerts = [];
        
        // Sorteios próximos do limite
        $near_limit = $db->fetchAll("
            SELECT 
                s.id,
                s.nome,
                s.max_participantes,
                COUNT(p.id) as current_count,
                (COUNT(p.id) * 100.0 / s.max_participantes) as percentage
            FROM sorteios s
            LEFT JOIN participantes p ON s.id = p.sorteio_id
            WHERE s.status = 'ativo' AND s.max_participantes > 0
            GROUP BY s.id
            HAVING percentage >= 80
            ORDER BY percentage DESC
        ");
        
        foreach ($near_limit as $item) {
            $percentage = round($item['percentage']);
            $severity = $percentage >= 95 ? 'critical' : ($percentage >= 90 ? 'high' : 'medium');
            
            $alerts[] = [
                'id' => 'limit_' . $item['id'],
                'type' => 'capacity',
                'severity' => $severity,
                'title' => 'Limite de Participantes',
                'message' => "Sorteio '{$item['nome']}' está {$percentage}% cheio ({$item['current_count']}/{$item['max_participantes']})",
                'sorteio_id' => $item['id'],
                'sorteio_nome' => $item['nome'],
                'percentage' => $percentage
            ];
        }
        
        // Verificar espaço em disco
        $disk_space = disk_free_space(DATA_PATH);
        $total_space = disk_total_space(DATA_PATH);
        $used_percentage = round(($total_space - $disk_space) / $total_space * 100);
        
        if ($used_percentage >= 90) {
            $alerts[] = [
                'id' => 'disk_space',
                'type' => 'system',
                'severity' => 'critical',
                'title' => 'Espaço em Disco',
                'message' => "Espaço em disco crítico: {$used_percentage}% utilizado",
                'percentage' => $used_percentage
            ];
        } elseif ($used_percentage >= 80) {
            $alerts[] = [
                'id' => 'disk_space',
                'type' => 'system',
                'severity' => 'high',
                'title' => 'Espaço em Disco',
                'message' => "Espaço em disco baixo: {$used_percentage}% utilizado",
                'percentage' => $used_percentage
            ];
        }
        
        // Verificar backups
        $backups = $db->listBackups();
        if (empty($backups)) {
            $alerts[] = [
                'id' => 'no_backups',
                'type' => 'system',
                'severity' => 'high',
                'title' => 'Sem Backups',
                'message' => "Nenhum backup encontrado. Recomendamos criar um backup."
            ];
        } else {
            $last_backup = $backups[0];
            $last_backup_time = strtotime($last_backup['created_at']);
            $days_since_backup = floor((time() - $last_backup_time) / 86400);
            
            if ($days_since_backup > 7) {
                $alerts[] = [
                    'id' => 'old_backup',
                    'type' => 'system',
                    'severity' => 'medium',
                    'title' => 'Backup Antigo',
                    'message' => "Último backup foi há {$days_since_backup} dias",
                    'days' => $days_since_backup
                ];
            }
        }
        
        return $alerts;
        
    } catch (Exception $e) {
        error_log("Erro ao obter alertas do sistema: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica se há alertas críticos
 */
function hasCriticalAlerts($alerts) {
    foreach ($alerts as $alert) {
        if ($alert['severity'] === 'critical') {
            return true;
        }
    }
    return false;
}

/**
 * Obtém status do sistema
 */
function getSystemStatus() {
    try {
        $db = getDatabase();
        
        $status = [
            'database' => 'ok',
            'disk_space' => 'ok',
            'memory_usage' => 'ok',
            'last_backup' => null,
            'active_sessions' => 0,
            'system_load' => 'low'
        ];
        
        // Verificar banco de dados
        try {
            $db->fetchOne("SELECT 1");
        } catch (Exception $e) {
            $status['database'] = 'error';
        }
        
        // Verificar espaço em disco
        $free_bytes = disk_free_space(DATA_PATH);
        $total_bytes = disk_total_space(DATA_PATH);
        $used_percentage = (($total_bytes - $free_bytes) / $total_bytes) * 100;
        
        if ($used_percentage > 90) {
            $status['disk_space'] = 'critical';
        } elseif ($used_percentage > 80) {
            $status['disk_space'] = 'warning';
        }
        
        // Verificar uso de memória
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = convertToBytes($memory_limit);
        
        if ($memory_limit_bytes > 0) {
            $memory_percentage = ($memory_usage / $memory_limit_bytes) * 100;
            if ($memory_percentage > 80) {
                $status['memory_usage'] = 'warning';
            }
        }
        
        // Verificar último backup
        $backups = $db->listBackups();
        if (!empty($backups)) {
            $status['last_backup'] = $backups[0]['created_at'];
        }
        
        // Simular carga do sistema baseada em atividade recente
        $recent_activity = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM participantes 
            WHERE created_at >= datetime('now', '-1 hour')
        ");
        
        $activity_count = $recent_activity['count'] ?? 0;
        if ($activity_count > 50) {
            $status['system_load'] = 'high';
        } elseif ($activity_count > 20) {
            $status['system_load'] = 'medium';
        }
        
        return $status;
        
    } catch (Exception $e) {
        error_log("Erro ao obter status do sistema: " . $e->getMessage());
        return ['error' => 'Erro ao obter status do sistema'];
    }
}

/**
 * Converte string de tamanho para bytes
 */
function convertToBytes($size_str) {
    $size_str = trim($size_str);
    $last = strtolower($size_str[strlen($size_str) - 1]);
    $size_str = (int) $size_str;
    
    switch ($last) {
        case 'g': $size_str *= 1024;
        case 'm': $size_str *= 1024;
        case 'k': $size_str *= 1024;
    }
    
    return $size_str;
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