<?php
/**
 * Endpoint AJAX para Dashboard
 * Fornece dados em tempo real para o dashboard administrativo
 * Versão otimizada com cache e notificações em tempo real
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
if (!checkRateLimit('dashboard_ajax', 60, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

try {
    // Verificar método da requisição
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Obter dados da requisição
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        // Verificar token CSRF para operações POST
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            throw new Exception('Token CSRF inválido');
        }
    } else {
        $action = $_GET['action'] ?? '';
    }
    
    $db = getDatabase();
    
    switch ($action) {
        case 'get_metrics':
            $metrics = getDashboardMetrics($db);
            jsonSuccess($metrics, 'Métricas atualizadas');
            break;
            
        case 'get_chart_data':
            $period = $input['period'] ?? 30;
            $chartData = getParticipationChartData($db, $period);
            jsonSuccess($chartData, 'Dados do gráfico atualizados');
            break;
            
        case 'get_recent_activities':
            $limit = $input['limit'] ?? 10;
            $activities = getRecentActivities($db, $limit);
            jsonSuccess($activities, 'Atividades recentes atualizadas');
            break;
            
        case 'get_popular_sorteios':
            $limit = $input['limit'] ?? 5;
            $popular = getPopularSorteios($db, $limit);
            jsonSuccess($popular, 'Sorteios populares atualizados');
            break;
            
        case 'export_data':
            $format = $input['format'] ?? 'csv';
            exportDashboardData($db, $format);
            break;
            
        case 'get_notifications':
            $notifications = getRealtimeNotifications($db);
            jsonSuccess($notifications, 'Notificações obtidas');
            break;
            
        case 'mark_notification_read':
            $notification_id = $input['notification_id'] ?? '';
            markNotificationAsRead($notification_id);
            jsonSuccess([], 'Notificação marcada como lida');
            break;
            
        case 'get_system_status':
            $status = getSystemStatus($db);
            jsonSuccess($status, 'Status do sistema obtido');
            break;
            
        case 'clear_cache':
            clearDashboardCache();
            jsonSuccess([], 'Cache limpo com sucesso');
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    error_log("Erro no dashboard AJAX: " . $e->getMessage());
    jsonError($e->getMessage());
}

/**
 * Obtém métricas principais do dashboard
 */
function getDashboardMetrics($db) {
    try {
        $metrics = [];
        
        // Total de sorteios
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM sorteios");
        $metrics['total_sorteios'] = (int)($result['count'] ?? 0);
        
        // Total de participantes
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes");
        $metrics['total_participantes'] = (int)($result['count'] ?? 0);
        
        // Sorteios ativos
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM sorteios WHERE status = 'ativo'");
        $metrics['sorteios_ativos'] = (int)($result['count'] ?? 0);
        
        // Sorteios finalizados (com resultados)
        $result = $db->fetchOne("SELECT COUNT(DISTINCT sorteio_id) as count FROM sorteio_resultados");
        $metrics['sorteios_finalizados'] = (int)($result['count'] ?? 0);
        
        // Participantes hoje
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes WHERE DATE(created_at) = DATE('now')");
        $metrics['participantes_hoje'] = (int)($result['count'] ?? 0);
        
        // Participantes esta semana
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes WHERE created_at >= date('now', '-7 days')");
        $metrics['participantes_semana'] = (int)($result['count'] ?? 0);
        
        // Taxa de conversão (participantes por sorteio ativo)
        if ($metrics['sorteios_ativos'] > 0) {
            $metrics['taxa_conversao'] = round($metrics['total_participantes'] / $metrics['sorteios_ativos'], 2);
        } else {
            $metrics['taxa_conversao'] = 0;
        }
        
        return $metrics;
        
    } catch (Exception $e) {
        error_log("Erro ao obter métricas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém dados para gráfico de participação
 */
function getParticipationChartData($db, $period = 30) {
    try {
        $data = $db->fetchAll("
            SELECT DATE(created_at) as data, COUNT(*) as participantes
            FROM participantes 
            WHERE created_at >= date('now', '-{$period} days')
            GROUP BY DATE(created_at)
            ORDER BY data ASC
        ");
        
        $labels = [];
        $values = [];
        
        // Preencher dados faltantes com zero
        $startDate = new DateTime("-{$period} days");
        $endDate = new DateTime();
        
        $dataMap = [];
        foreach ($data as $item) {
            $dataMap[$item['data']] = (int)$item['participantes'];
        }
        
        while ($startDate <= $endDate) {
            $dateStr = $startDate->format('Y-m-d');
            $labels[] = $startDate->format('d/m');
            $values[] = $dataMap[$dateStr] ?? 0;
            $startDate->add(new DateInterval('P1D'));
        }
        
        return [
            'labels' => $labels,
            'data' => $values,
            'period' => $period
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao obter dados do gráfico: " . $e->getMessage());
        return ['labels' => [], 'data' => [], 'period' => $period];
    }
}

/**
 * Obtém atividades recentes
 */
function getRecentActivities($db, $limit = 10) {
    try {
        $activities = $db->fetchAll("
            SELECT s.id, s.nome, s.status, s.created_at, s.updated_at,
                   COUNT(p.id) as total_participantes,
                   COUNT(sr.id) as total_sorteados,
                   CASE 
                       WHEN s.updated_at > s.created_at THEN 'updated'
                       ELSE 'created'
                   END as action_type
            FROM sorteios s 
            LEFT JOIN participantes p ON s.id = p.sorteio_id 
            LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
            GROUP BY s.id 
            ORDER BY COALESCE(s.updated_at, s.created_at) DESC 
            LIMIT ?
        ", [$limit]);
        
        // Formatar dados para exibição
        foreach ($activities as &$activity) {
            $activity['formatted_date'] = formatDateBR($activity['created_at']);
            $activity['time_ago'] = getTimeAgo($activity['created_at']);
            $activity['total_participantes'] = (int)$activity['total_participantes'];
            $activity['total_sorteados'] = (int)$activity['total_sorteados'];
        }
        
        return $activities;
        
    } catch (Exception $e) {
        error_log("Erro ao obter atividades recentes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém sorteios mais populares
 */
function getPopularSorteios($db, $limit = 5) {
    try {
        $popular = $db->fetchAll("
            SELECT s.id, s.nome, s.status, s.created_at,
                   COUNT(p.id) as total_participantes,
                   COUNT(sr.id) as total_sorteados
            FROM sorteios s 
            LEFT JOIN participantes p ON s.id = p.sorteio_id 
            LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
            GROUP BY s.id 
            ORDER BY total_participantes DESC, s.created_at DESC
            LIMIT ?
        ", [$limit]);
        
        // Formatar dados
        foreach ($popular as &$sorteio) {
            $sorteio['total_participantes'] = (int)$sorteio['total_participantes'];
            $sorteio['total_sorteados'] = (int)$sorteio['total_sorteados'];
            $sorteio['formatted_date'] = formatDateBR($sorteio['created_at']);
        }
        
        return $popular;
        
    } catch (Exception $e) {
        error_log("Erro ao obter sorteios populares: " . $e->getMessage());
        return [];
    }
}

/**
 * Exporta dados do dashboard
 */
function exportDashboardData($db, $format = 'csv') {
    try {
        // Obter dados completos
        $metrics = getDashboardMetrics($db);
        $chartData = getParticipationChartData($db, 30);
        $activities = getRecentActivities($db, 50);
        $popular = getPopularSorteios($db, 10);
        
        $exportData = [
            'metrics' => $metrics,
            'chart_data' => $chartData,
            'recent_activities' => $activities,
            'popular_sorteios' => $popular,
            'export_date' => date('Y-m-d H:i:s'),
            'export_by' => $_SESSION['admin_email'] ?? 'admin'
        ];
        
        switch ($format) {
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="dashboard_' . date('Y-m-d') . '.json"');
                echo json_encode($exportData, JSON_PRETTY_PRINT);
                break;
                
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="dashboard_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // Métricas
                fputcsv($output, ['MÉTRICAS DO DASHBOARD']);
                fputcsv($output, ['Métrica', 'Valor']);
                foreach ($metrics as $key => $value) {
                    fputcsv($output, [ucfirst(str_replace('_', ' ', $key)), $value]);
                }
                
                fputcsv($output, []); // Linha vazia
                
                // Atividades recentes
                fputcsv($output, ['ATIVIDADES RECENTES']);
                fputcsv($output, ['Nome', 'Status', 'Participantes', 'Sorteados', 'Data']);
                foreach ($activities as $activity) {
                    fputcsv($output, [
                        $activity['nome'],
                        $activity['status'],
                        $activity['total_participantes'],
                        $activity['total_sorteados'],
                        $activity['formatted_date']
                    ]);
                }
                
                fclose($output);
                break;
                
            default:
                throw new Exception('Formato de exportação não suportado');
        }
        
        exit;
        
    } catch (Exception $e) {
        error_log("Erro ao exportar dados: " . $e->getMessage());
        jsonError('Erro ao exportar dados: ' . $e->getMessage());
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

/**
 * Obtém notificações em tempo real
 */
function getRealtimeNotifications($db) {
    try {
        // Cache de 30 segundos para notificações
        $cache_file = DATA_PATH . '/cache/notifications.json';
        $cache_time = 30;
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            return json_decode(file_get_contents($cache_file), true);
        }
        
        $notifications = [];
        
        // Novos participantes nas últimas 24 horas
        $new_participants = $db->fetchAll("
            SELECT s.nome as sorteio_nome, COUNT(*) as count, MAX(p.created_at) as last_created
            FROM participantes p 
            JOIN sorteios s ON p.sorteio_id = s.id
            WHERE p.created_at >= datetime('now', '-24 hours')
            GROUP BY s.id, s.nome
            ORDER BY last_created DESC
            LIMIT 5
        ");
        
        foreach ($new_participants as $item) {
            $notifications[] = [
                'id' => 'new_participants_' . md5($item['sorteio_nome']),
                'type' => 'info',
                'title' => 'Novos Participantes',
                'message' => "{$item['count']} novos participantes em '{$item['sorteio_nome']}'",
                'time' => getTimeAgo($item['last_created']),
                'timestamp' => $item['last_created'],
                'read' => false
            ];
        }
        
        // Sorteios próximos do limite
        $near_limit = $db->fetchAll("
            SELECT s.nome, s.max_participantes, COUNT(p.id) as current_count
            FROM sorteios s
            LEFT JOIN participantes p ON s.id = p.sorteio_id
            WHERE s.status = 'ativo' AND s.max_participantes > 0
            GROUP BY s.id
            HAVING current_count >= (s.max_participantes * 0.8)
            ORDER BY (current_count / s.max_participantes) DESC
            LIMIT 3
        ");
        
        foreach ($near_limit as $item) {
            $percentage = round(($item['current_count'] / $item['max_participantes']) * 100);
            $notifications[] = [
                'id' => 'near_limit_' . md5($item['nome']),
                'type' => 'warning',
                'title' => 'Limite Próximo',
                'message' => "'{$item['nome']}' está {$percentage}% cheio ({$item['current_count']}/{$item['max_participantes']})",
                'time' => 'agora',
                'timestamp' => date('Y-m-d H:i:s'),
                'read' => false
            ];
        }
        
        // Erros recentes no sistema
        $error_log = DATA_PATH . '/logs/system.log';
        if (file_exists($error_log)) {
            $recent_errors = [];
            $lines = file($error_log);
            $lines = array_slice($lines, -100); // Últimas 100 linhas
            
            foreach (array_reverse($lines) as $line) {
                if (strpos($line, '[ERROR]') !== false && strpos($line, date('Y-m-d')) !== false) {
                    $recent_errors[] = $line;
                    if (count($recent_errors) >= 3) break;
                }
            }
            
            foreach ($recent_errors as $error) {
                $notifications[] = [
                    'id' => 'error_' . md5($error),
                    'type' => 'error',
                    'title' => 'Erro do Sistema',
                    'message' => 'Erro detectado no sistema. Verifique os logs.',
                    'time' => 'recente',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'read' => false
                ];
            }
        }
        
        // Salvar cache
        if (!is_dir(dirname($cache_file))) {
            mkdir(dirname($cache_file), 0755, true);
        }
        file_put_contents($cache_file, json_encode($notifications));
        
        return $notifications;
        
    } catch (Exception $e) {
        error_log("Erro ao obter notificações: " . $e->getMessage());
        return [];
    }
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
 * Obtém status do sistema
 */
function getSystemStatus($db) {
    try {
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
        $backup_dir = DATA_PATH . '/backups';
        if (is_dir($backup_dir)) {
            $backups = glob($backup_dir . '/backup_*.db');
            if (!empty($backups)) {
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $status['last_backup'] = formatDateBR(date('Y-m-d H:i:s', filemtime($backups[0])));
            }
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
 * Limpa cache do dashboard
 */
function clearDashboardCache() {
    try {
        $cache_dir = DATA_PATH . '/cache';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
        
        logActivity('Cache do dashboard limpo', 'Dashboard cache cleared via AJAX');
        return true;
    } catch (Exception $e) {
        error_log("Erro ao limpar cache: " . $e->getMessage());
        return false;
    }
}
?>