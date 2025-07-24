<?php
/**
 * Sistema de Histórico e Resultados - Sistema de Sorteios
 * Listagem de sorteios realizados com detalhes e timeline
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';
require_once 'includes/classes/SorteioEngine.php';

// Configurações da página
$page_title = 'Histórico de Sorteios';
$current_page = 'historico';
$show_sidebar = true;
$page_scripts = ['/assets/js/historico.js'];

// Processar parâmetros
$action = $_GET['action'] ?? 'list';
$sorteio_id = $_GET['id'] ?? null;
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$db = getDatabase();
$sorteioEngine = new SorteioEngine();

// Processar ações
$message = '';
$error = '';

if ($action === 'remove_result' && isset($_GET['resultado_id']) && $sorteio_id) {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $resultado_id = $_GET['resultado_id'];
        $result = $sorteioEngine->removeSorteioResult($sorteio_id, $resultado_id);
        
        if ($result['success']) {
            $message = 'Resultado removido com sucesso!';
        } else {
            $error = $result['error'];
        }
        
        // Redirecionar para evitar resubmissão
        header("Location: historico.php?action=details&id={$sorteio_id}");
        exit;
    }
}

// Carregar dados conforme a ação
switch ($action) {
    case 'details':
        if (!$sorteio_id) {
            $error = 'ID do sorteio não fornecido.';
            $action = 'list';
            break;
        }
        
        $sorteio = getSorteioDetails($sorteio_id);
        if (!$sorteio) {
            $error = 'Sorteio não encontrado.';
            $action = 'list';
            break;
        }
        
        $historico_sorteios = $sorteioEngine->getSorteioHistory($sorteio_id, 100);
        $stats = $sorteioEngine->getSorteioStats($sorteio_id);
        $timeline = getSorteioTimeline($sorteio_id);
        $participantes = getParticipantesBySorteio($sorteio_id);
        break;
        
    case 'list':
    default:
        $sorteios_realizados = getSorteiosRealizados($search, $date_filter, $status_filter, $per_page, ($page - 1) * $per_page);
        $total_sorteios = getTotalSorteiosRealizados($search, $date_filter, $status_filter);
        $total_pages = ceil($total_sorteios / $per_page);
        break;
}

/**
 * Funções auxiliares
 */

function getSorteiosRealizados($search = '', $date_filter = '', $status_filter = '', $limit = 20, $offset = 0) {
    try {
        $db = getDatabase();
        
        // Construir query com filtros
        $where_conditions = [];
        $params = [];
        
        // Filtro de busca
        if (!empty($search)) {
            $where_conditions[] = "(s.nome LIKE ? OR s.descricao LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Filtro de data
        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = "DATE(s.created_at) = DATE('now')";
                    break;
                case 'week':
                    $where_conditions[] = "s.created_at >= DATE('now', '-7 days')";
                    break;
                case 'month':
                    $where_conditions[] = "s.created_at >= DATE('now', '-30 days')";
                    break;
                case 'year':
                    $where_conditions[] = "s.created_at >= DATE('now', '-365 days')";
                    break;
            }
        }
        
        // Filtro de status
        if (!empty($status_filter)) {
            $where_conditions[] = "s.status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "
            SELECT s.*, 
                   COUNT(DISTINCT p.id) as total_participantes,
                   COUNT(DISTINCT sr.id) as total_sorteados,
                   COUNT(DISTINCT sr.resultado_id) as total_execucoes,
                   MAX(sr.data_sorteio) as ultimo_sorteio
            FROM sorteios s 
            LEFT JOIN participantes p ON s.id = p.sorteio_id 
            LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
            {$where_clause}
            GROUP BY s.id 
            HAVING total_sorteados > 0 OR s.status = 'finalizado'
            ORDER BY ultimo_sorteio DESC, s.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao carregar histórico: " . $e->getMessage());
        return [];
    }
}

function getTotalSorteiosRealizados($search = '', $date_filter = '', $status_filter = '') {
    try {
        $db = getDatabase();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(s.nome LIKE ? OR s.descricao LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = "DATE(s.created_at) = DATE('now')";
                    break;
                case 'week':
                    $where_conditions[] = "s.created_at >= DATE('now', '-7 days')";
                    break;
                case 'month':
                    $where_conditions[] = "s.created_at >= DATE('now', '-30 days')";
                    break;
                case 'year':
                    $where_conditions[] = "s.created_at >= DATE('now', '-365 days')";
                    break;
            }
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "s.status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "
            SELECT COUNT(DISTINCT s.id) as count
            FROM sorteios s 
            LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
            {$where_clause}
            HAVING COUNT(sr.id) > 0 OR s.status = 'finalizado'
        ";
        
        $result = $db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Erro ao contar histórico: " . $e->getMessage());
        return 0;
    }
}

function getSorteioDetails($id) {
    try {
        $db = getDatabase();
        $sorteio = $db->fetchOne("SELECT * FROM sorteios WHERE id = ?", [$id]);
        
        if ($sorteio && $sorteio['campos_config']) {
            $sorteio['campos_config'] = json_decode($sorteio['campos_config'], true);
        }
        
        return $sorteio;
    } catch (Exception $e) {
        error_log("Erro ao carregar detalhes do sorteio: " . $e->getMessage());
        return null;
    }
}

function getSorteioTimeline($sorteio_id) {
    try {
        $db = getDatabase();
        
        $timeline = [];
        
        // Criação do sorteio
        $sorteio = $db->fetchOne("SELECT created_at, nome FROM sorteios WHERE id = ?", [$sorteio_id]);
        if ($sorteio) {
            $timeline[] = [
                'type' => 'created',
                'title' => 'Sorteio Criado',
                'description' => "Sorteio '{$sorteio['nome']}' foi criado",
                'timestamp' => $sorteio['created_at'],
                'icon' => 'plus',
                'color' => 'blue'
            ];
        }
        
        // Primeiros participantes
        $primeiro_participante = $db->fetchOne(
            "SELECT MIN(created_at) as data FROM participantes WHERE sorteio_id = ?", 
            [$sorteio_id]
        );
        if ($primeiro_participante && $primeiro_participante['data']) {
            $timeline[] = [
                'type' => 'first_participant',
                'title' => 'Primeira Inscrição',
                'description' => 'Primeiro participante se inscreveu',
                'timestamp' => $primeiro_participante['data'],
                'icon' => 'user-plus',
                'color' => 'green'
            ];
        }
        
        // Marcos de participantes (100, 500, 1000, etc.)
        $marcos = [100, 500, 1000, 2000, 5000];
        foreach ($marcos as $marco) {
            $participante_marco = $db->fetchOne(
                "SELECT created_at FROM participantes WHERE sorteio_id = ? ORDER BY created_at LIMIT 1 OFFSET ?", 
                [$sorteio_id, $marco - 1]
            );
            if ($participante_marco) {
                $timeline[] = [
                    'type' => 'milestone',
                    'title' => "Marco de {$marco} Participantes",
                    'description' => "Sorteio atingiu {$marco} participantes",
                    'timestamp' => $participante_marco['created_at'],
                    'icon' => 'users',
                    'color' => 'purple'
                ];
            }
        }
        
        // Execuções de sorteio
        $execucoes = $db->fetchAll(
            "SELECT DISTINCT resultado_id, data_sorteio, COUNT(*) as sorteados
             FROM sorteio_resultados sr
             JOIN participantes p ON sr.participante_id = p.id
             WHERE p.sorteio_id = ?
             GROUP BY resultado_id
             ORDER BY data_sorteio ASC",
            [$sorteio_id]
        );
        
        foreach ($execucoes as $execucao) {
            $timeline[] = [
                'type' => 'draw',
                'title' => 'Sorteio Realizado',
                'description' => "{$execucao['sorteados']} participante(s) sorteado(s)",
                'timestamp' => $execucao['data_sorteio'],
                'icon' => 'gift',
                'color' => 'yellow',
                'metadata' => [
                    'resultado_id' => $execucao['resultado_id'],
                    'sorteados' => $execucao['sorteados']
                ]
            ];
        }
        
        // Ordenar por timestamp
        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return $timeline;
        
    } catch (Exception $e) {
        error_log("Erro ao carregar timeline: " . $e->getMessage());
        return [];
    }
}

function getParticipantesBySorteio($sorteio_id) {
    try {
        $db = getDatabase();
        return $db->fetchAll(
            "SELECT p.*, 
                    CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END as foi_sorteado,
                    sr.posicao as posicao_sorteio,
                    sr.data_sorteio,
                    sr.resultado_id
             FROM participantes p
             LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
             WHERE p.sorteio_id = ?
             ORDER BY sr.posicao ASC, p.created_at ASC",
            [$sorteio_id]
        );
    } catch (Exception $e) {
        error_log("Erro ao carregar participantes: " . $e->getMessage());
        return [];
    }
}

// Incluir header
include 'templates/header.php';
?>

<!-- Container principal -->
<div class="p-6">
    
    <!-- Mensagens de feedback -->
    <?php if ($message): ?>
        <div class="mb-6 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <!-- Listagem de Histórico -->
        <?php include 'templates/historico/list.php'; ?>
        
    <?php elseif ($action === 'details'): ?>
        <!-- Detalhes do Sorteio -->
        <?php include 'templates/historico/details.php'; ?>
        
    <?php endif; ?>
    
</div>

<?php
// Incluir footer
include 'templates/footer.php';
?>