<?php
/**
 * Endpoint AJAX para Métricas em Tempo Real
 * Fornece métricas e estatísticas atualizadas para o dashboard
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
if (!checkRateLimit('metrics_realtime_ajax', 30, 60)) {
    jsonError('Muitas requisições. Tente novamente em alguns instantes.', 429);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_dashboard_metrics':
            handleGetDashboardMetrics();
            break;
            
        case 'get_sorteios_metrics':
            handleGetSorteiosMetrics();
            break;
            
        case 'get_participantes_metrics':
            handleGetParticipantesMetrics();
            break;
            
        case 'get_chart_data':
            handleGetChartData();
            break;
            
        case 'get_performance_metrics':
            handleGetPerformanceMetrics();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no sistema de métricas em tempo real: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Obtém métricas do dashboard
 */
function handleGetDashboardMetrics() {
    $metrics = getDashboardMetrics();
    
    jsonSuccess([
        'metrics' => $metrics,
        'timestamp' => time()
    ]);
}

/**
 * Obtém métricas de sorteios
 */
function handleGetSorteiosMetrics() {
    $metrics = getSorteiosMetrics();
    
    jsonSuccess([
        'metrics' => $metrics,
        'timestamp' => time()
    ]);
}

/**
 * Obtém métricas de participantes
 */
function handleGetParticipantesMetrics() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    $metrics = getParticipantesMetrics($sorteio_id);
    
    jsonSuccess([
        'metrics' => $metrics,
        'timestamp' => time()
    ]);
}

/**
 * Obtém dados para gráficos
 */
function handleGetChartData() {
    $type = $_GET['type'] ?? 'participation';
    $period = intval($_GET['period'] ?? 30);
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    
    $data = getChartData($type, $period, $sorteio_id);
    
    jsonSuccess([
        'chart_data' => $data,
        'timestamp' => time()
    ]);
}

/**
 * Obtém métricas de performance
 */
function handleGetPerformanceMetrics() {
    $metrics = getPerformanceMetrics();
    
    jsonSuccess([
        'metrics' => $metrics,
        'timestamp' => time()
    ]);
}

/**
 * Obtém métricas principais do dashboard
 */
function getDashboardMetrics() {
    try {
        $db = getDatabase();
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
        
        // Média de participantes por sorteio
        if ($metrics['total_sorteios'] > 0) {
            $metrics['media_participantes'] = round($metrics['total_participantes'] / $metrics['total_sorteios'], 2);
        } else {
            $metrics['media_participantes'] = 0;
        }
        
        // Sorteios mais populares
        $popular_sorteios = $db->fetchAll("
            SELECT s.id, s.nome, COUNT(p.id) as total_participantes
            FROM sorteios s
            LEFT JOIN participantes p ON s.id = p.sorteio_id
            GROUP BY s.id
            ORDER BY total_participantes DESC
            LIMIT 5
        ");
        
        $metrics['sorteios_populares'] = array_map(function($item) {
            return [
                'id' => $item['id'],
                'nome' => $item['nome'],
                'total_participantes' => (int)$item['total_participantes']
            ];
        }, $popular_sorteios);
        
        return $metrics;
        
    } catch (Exception $e) {
        error_log("Erro ao obter métricas do dashboard: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém métricas de sorteios
 */
function getSorteiosMetrics() {
    try {
        $db = getDatabase();
        $metrics = [];
        
        // Contagem por status
        $status_counts = $db->fetchAll("
            SELECT status, COUNT(*) as count
            FROM sorteios
            GROUP BY status
        ");
        
        $metrics['status_counts'] = [];
        foreach ($status_counts as $item) {
            $metrics['status_counts'][$item['status']] = (int)$item['count'];
        }
        
        // Sorteios criados por período
        $metrics['created_today'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM sorteios WHERE DATE(created_at) = DATE('now')
        ")['count'];
        
        $metrics['created_week'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM sorteios WHERE created_at >= date('now', '-7 days')
        ")['count'];
        
        $metrics['created_month'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM sorteios WHERE created_at >= date('now', '-30 days')
        ")['count'];
        
        // Sorteios com mais resultados
        $metrics['most_draws'] = $db->fetchAll("
            SELECT s.id, s.nome, COUNT(DISTINCT sr.resultado_id) as total_sorteios
            FROM sorteios s
            JOIN participantes p ON s.id = p.sorteio_id
            JOIN sorteio_resultados sr ON p.id = sr.participante_id
            GROUP BY s.id
            ORDER BY total_sorteios DESC
            LIMIT 5
        ");
        
        // Formatar dados
        $metrics['most_draws'] = array_map(function($item) {
            return [
                'id' => $item['id'],
                'nome' => $item['nome'],
                'total_sorteios' => (int)$item['total_sorteios']
            ];
        }, $metrics['most_draws']);
        
        return $metrics;
        
    } catch (Exception $e) {
        error_log("Erro ao obter métricas de sorteios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém métricas de participantes
 */
function getParticipantesMetrics($sorteio_id = null) {
    try {
        $db = getDatabase();
        $metrics = [];
        
        // Construir condição para sorteio específico
        $sorteio_condition = '';
        $params = [];
        
        if ($sorteio_id) {
            $sorteio_condition = 'WHERE sorteio_id = ?';
            $params[] = $sorteio_id;
        }
        
        // Total de participantes
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes {$sorteio_condition}", $params);
        $metrics['total_participantes'] = (int)($result['count'] ?? 0);
        
        // Participantes por período
        $metrics['participantes_hoje'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM participantes 
            WHERE DATE(created_at) = DATE('now') {$sorteio_condition ? 'AND ' . substr($sorteio_condition, 6) : ''}
        ", $params)['count'];
        
        $metrics['participantes_semana'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM participantes 
            WHERE created_at >= date('now', '-7 days') {$sorteio_condition ? 'AND ' . substr($sorteio_condition, 6) : ''}
        ", $params)['count'];
        
        $metrics['participantes_mes'] = (int)$db->fetchOne("
            SELECT COUNT(*) as count FROM participantes 
            WHERE created_at >= date('now', '-30 days') {$sorteio_condition ? 'AND ' . substr($sorteio_condition, 6) : ''}
        ", $params)['count'];
        
        // Participantes sorteados
        $sorteados_params = $params;
        $sorteados_condition = $sorteio_condition;
        
        if ($sorteio_id) {
            $sorteados_condition = 'WHERE p.sorteio_id = ?';
        }
        
        $result = $db->fetchOne("
            SELECT COUNT(DISTINCT sr.participante_id) as count
            FROM sorteio_resultados sr
            JOIN participantes p ON sr.participante_id = p.id
            {$sorteados_condition}
        ", $sorteados_params);
        
        $metrics['total_sorteados'] = (int)($result['count'] ?? 0);
        
        // Percentual de sorteados
        if ($metrics['total_participantes'] > 0) {
            $metrics['percentual_sorteados'] = round(($metrics['total_sorteados'] / $metrics['total_participantes']) * 100, 2);
        } else {
            $metrics['percentual_sorteados'] = 0;
        }
        
        // Estatísticas de campos preenchidos
        $campos = ['whatsapp', 'cpf', 'email'];
        $metrics['campos_preenchidos'] = [];
        
        foreach ($campos as $campo) {
            $campo_params = $params;
            $campo_condition = $sorteio_condition ? $sorteio_condition . ' AND' : 'WHERE';
            $campo_condition .= " {$campo} IS NOT NULL AND {$campo} != ''";
            
            $result = $db->fetchOne("
                SELECT COUNT(*) as count FROM participantes {$campo_condition}
            ", $campo_params);
            
            $metrics['campos_preenchidos'][$campo] = (int)($result['count'] ?? 0);
            
            if ($metrics['total_participantes'] > 0) {
                $metrics['campos_preenchidos']["{$campo}_percentual"] = round(($metrics['campos_preenchidos'][$campo] / $metrics['total_participantes']) * 100, 2);
            } else {
                $metrics['campos_preenchidos']["{$campo}_percentual"] = 0;
            }
        }
        
        return $metrics;
        
    } catch (Exception $e) {
        error_log("Erro ao obter métricas de participantes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém dados para gráficos
 */
function getChartData($type = 'participation', $period = 30, $sorteio_id = null) {
    try {
        $db = getDatabase();
        $data = [];
        
        switch ($type) {
            case 'participation':
                // Gráfico de participação ao longo do tempo
                $sorteio_condition = '';
                $params = [$period];
                
                if ($sorteio_id) {
                    $sorteio_condition = 'AND sorteio_id = ?';
                    $params[] = $sorteio_id;
                }
                
                $result = $db->fetchAll("
                    SELECT DATE(created_at) as data, COUNT(*) as participantes
                    FROM participantes 
                    WHERE created_at >= date('now', '-{$period} days') {$sorteio_condition}
                    GROUP BY DATE(created_at)
                    ORDER BY data ASC
                ", $params);
                
                $labels = [];
                $values = [];
                
                // Preencher dados faltantes com zero
                $startDate = new DateTime("-{$period} days");
                $endDate = new DateTime();
                
                $dataMap = [];
                foreach ($result as $item) {
                    $dataMap[$item['data']] = (int)$item['participantes'];
                }
                
                while ($startDate <= $endDate) {
                    $dateStr = $startDate->format('Y-m-d');
                    $labels[] = $startDate->format('d/m');
                    $values[] = $dataMap[$dateStr] ?? 0;
                    $startDate->add(new DateInterval('P1D'));
                }
                
                $data = [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Participantes',
                            'data' => $values,
                            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                            'borderColor' => 'rgb(59, 130, 246)',
                            'borderWidth' => 2,
                            'tension' => 0.4
                        ]
                    ]
                ];
                break;
                
            case 'sorteios':
                // Gráfico de sorteios por status
                $result = $db->fetchAll("
                    SELECT status, COUNT(*) as count
                    FROM sorteios
                    GROUP BY status
                ");
                
                $labels = [];
                $values = [];
                $colors = [];
                
                $statusColors = [
                    'ativo' => 'rgb(34, 197, 94)',
                    'pausado' => 'rgb(234, 179, 8)',
                    'finalizado' => 'rgb(59, 130, 246)'
                ];
                
                foreach ($result as $item) {
                    $labels[] = ucfirst($item['status']);
                    $values[] = (int)$item['count'];
                    $colors[] = $statusColors[$item['status']] ?? 'rgb(107, 114, 128)';
                }
                
                $data = [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Sorteios por Status',
                            'data' => $values,
                            'backgroundColor' => $colors
                        ]
                    ]
                ];
                break;
                
            case 'resultados':
                // Gráfico de resultados de sorteio ao longo do tempo
                $sorteio_condition = '';
                $params = [$period];
                
                if ($sorteio_id) {
                    $sorteio_condition = 'AND p.sorteio_id = ?';
                    $params[] = $sorteio_id;
                }
                
                $result = $db->fetchAll("
                    SELECT DATE(sr.data_sorteio) as data, COUNT(DISTINCT sr.resultado_id) as sorteios
                    FROM sorteio_resultados sr
                    JOIN participantes p ON sr.participante_id = p.id
                    WHERE sr.data_sorteio >= date('now', '-{$period} days') {$sorteio_condition}
                    GROUP BY DATE(sr.data_sorteio)
                    ORDER BY data ASC
                ", $params);
                
                $labels = [];
                $values = [];
                
                // Preencher dados faltantes com zero
                $startDate = new DateTime("-{$period} days");
                $endDate = new DateTime();
                
                $dataMap = [];
                foreach ($result as $item) {
                    $dataMap[$item['data']] = (int)$item['sorteios'];
                }
                
                while ($startDate <= $endDate) {
                    $dateStr = $startDate->format('Y-m-d');
                    $labels[] = $startDate->format('d/m');
                    $values[] = $dataMap[$dateStr] ?? 0;
                    $startDate->add(new DateInterval('P1D'));
                }
                
                $data = [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Sorteios Realizados',
                            'data' => $values,
                            'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                            'borderColor' => 'rgb(249, 115, 22)',
                            'borderWidth' => 2,
                            'tension' => 0.4
                        ]
                    ]
                ];
                break;
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Erro ao obter dados para gráfico: " . $e->getMessage());
        return [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Erro',
                    'data' => []
                ]
            ]
        ];
    }
}

/**
 * Obtém métricas de performance
 */
function getPerformanceMetrics() {
    try {
        $metrics = [];
        
        // Tamanho do banco de dados
        $db_size = filesize(DB_PATH);
        $metrics['db_size'] = $db_size;
        $metrics['db_size_formatted'] = formatBytes($db_size);
        
        // Espaço em disco
        $free_space = disk_free_space(DATA_PATH);
        $total_space = disk_total_space(DATA_PATH);
        $used_space = $total_space - $free_space;
        
        $metrics['disk_free'] = $free_space;
        $metrics['disk_total'] = $total_space;
        $metrics['disk_used'] = $used_space;
        $metrics['disk_free_formatted'] = formatBytes($free_space);
        $metrics['disk_total_formatted'] = formatBytes($total_space);
        $metrics['disk_used_formatted'] = formatBytes($used_space);
        $metrics['disk_usage_percent'] = round(($used_space / $total_space) * 100, 2);
        
        // Uso de memória
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = convertToBytes($memory_limit);
        
        $metrics['memory_usage'] = $memory_usage;
        $metrics['memory_limit'] = $memory_limit_bytes;
        $metrics['memory_usage_formatted'] = formatBytes($memory_usage);
        $metrics['memory_limit_formatted'] = $memory_limit;
        
        if ($memory_limit_bytes > 0) {
            $metrics['memory_usage_percent'] = round(($memory_usage / $memory_limit_bytes) * 100, 2);
        } else {
            $metrics['memory_usage_percent'] = 0;
        }
        
        // Estatísticas de tabelas
        $db = getDatabase();
        $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        $metrics['tables'] = [];
        foreach ($tables as $table) {
            $table_name = $table['name'];
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM {$table_name}");
            $metrics['tables'][$table_name] = (int)$count['count'];
        }
        
        // Tempo de execução
        $metrics['execution_time'] = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); // em ms
        
        return $metrics;
        
    } catch (Exception $e) {
        error_log("Erro ao obter métricas de performance: " . $e->getMessage());
        return [];
    }
}

/**
 * Formata bytes em formato legível
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
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