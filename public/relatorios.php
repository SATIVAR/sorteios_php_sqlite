<?php
/**
 * Sistema de Relatórios Avançados - Sistema de Sorteios
 * Dashboard principal de relatórios com filtros e visualizações
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';

// Configurações da página
$page_title = 'Relatórios Avançados';
$current_page = 'relatorios';
$show_sidebar = true;
$page_scripts = ['/assets/js/relatorios.js'];

// Obter parâmetros de filtro
$filtro_periodo = $_GET['periodo'] ?? '30';
$filtro_sorteio = $_GET['sorteio'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$tipo_relatorio = $_GET['tipo'] ?? 'participacao';

$db = getDatabase();

try {
    // Obter lista de sorteios para filtros
    $sorteios = $db->fetchAll("
        SELECT id, nome, status, created_at 
        FROM sorteios 
        ORDER BY created_at DESC
    ");
    
    // Construir condições WHERE baseadas nos filtros
    $whereConditions = [];
    $params = [];
    
    // Filtro de período
    if ($filtro_periodo !== 'todos') {
        $whereConditions[] = "s.created_at >= date('now', '-{$filtro_periodo} days')";
    }
    
    // Filtro de sorteio específico
    if (!empty($filtro_sorteio)) {
        $whereConditions[] = "s.id = ?";
        $params[] = $filtro_sorteio;
    }
    
    // Filtro de status
    if (!empty($filtro_status)) {
        $whereConditions[] = "s.status = ?";
        $params[] = $filtro_status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Dados para diferentes tipos de relatórios
    $dadosRelatorio = [];
    
    switch ($tipo_relatorio) {
        case 'participacao':
            $dadosRelatorio = obterDadosParticipacao($db, $whereClause, $params);
            break;
        case 'conversao':
            $dadosRelatorio = obterDadosConversao($db, $whereClause, $params);
            break;
        case 'engajamento':
            $dadosRelatorio = obterDadosEngajamento($db, $whereClause, $params);
            break;
        case 'comparativo':
            $dadosRelatorio = obterDadosComparativo($db, $whereClause, $params);
            break;
        default:
            $dadosRelatorio = obterDadosParticipacao($db, $whereClause, $params);
    }
    
} catch (Exception $e) {
    error_log("Erro ao carregar relatórios: " . $e->getMessage());
    $sorteios = [];
    $dadosRelatorio = [];
}

// Funções para obter dados dos relatórios
function obterDadosParticipacao($db, $whereClause, $params) {
    // Métricas principais
    $metricas = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT s.id) as total_sorteios,
            COUNT(p.id) as total_participantes,
            COUNT(DISTINCT sr.participante_id) as total_ganhadores,
            AVG(participantes_por_sorteio.count) as media_participantes
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        LEFT JOIN (
            SELECT sorteio_id, COUNT(*) as count 
            FROM participantes 
            GROUP BY sorteio_id
        ) participantes_por_sorteio ON s.id = participantes_por_sorteio.sorteio_id
        $whereClause
    ", $params);
    
    // Participação por dia
    $participacaoDiaria = $db->fetchAll("
        SELECT 
            DATE(p.created_at) as data,
            COUNT(*) as participantes,
            COUNT(DISTINCT p.sorteio_id) as sorteios_ativos
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY DATE(p.created_at)
        ORDER BY data ASC
    ", $params);
    
    // Top sorteios por participação
    $topSorteios = $db->fetchAll("
        SELECT 
            s.nome,
            s.status,
            COUNT(p.id) as total_participantes,
            COUNT(sr.id) as total_ganhadores,
            s.created_at
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY s.id
        ORDER BY total_participantes DESC
        LIMIT 10
    ", $params);
    
    return [
        'metricas' => $metricas,
        'participacao_diaria' => $participacaoDiaria,
        'top_sorteios' => $topSorteios
    ];
}

function obterDadosConversao($db, $whereClause, $params) {
    // Taxa de conversão (participantes que viraram ganhadores)
    $conversao = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT p.id) as total_participantes,
            COUNT(DISTINCT sr.participante_id) as total_ganhadores,
            ROUND(
                (COUNT(DISTINCT sr.participante_id) * 100.0) / 
                NULLIF(COUNT(DISTINCT p.id), 0), 2
            ) as taxa_conversao
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
        $whereClause
    ", $params);
    
    // Conversão por sorteio
    $conversaoPorSorteio = $db->fetchAll("
        SELECT 
            s.nome,
            COUNT(p.id) as participantes,
            COUNT(sr.id) as ganhadores,
            ROUND(
                (COUNT(sr.id) * 100.0) / NULLIF(COUNT(p.id), 0), 2
            ) as taxa_conversao
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY s.id
        HAVING COUNT(p.id) > 0
        ORDER BY taxa_conversao DESC
    ", $params);
    
    return [
        'conversao_geral' => $conversao,
        'conversao_por_sorteio' => $conversaoPorSorteio
    ];
}

function obterDadosEngajamento($db, $whereClause, $params) {
    // Análise de engajamento por horário
    $engajamentoPorHora = $db->fetchAll("
        SELECT 
            strftime('%H', p.created_at) as hora,
            COUNT(*) as participantes
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY strftime('%H', p.created_at)
        ORDER BY hora
    ", $params);
    
    // Engajamento por dia da semana
    $engajamentoPorDia = $db->fetchAll("
        SELECT 
            CASE strftime('%w', p.created_at)
                WHEN '0' THEN 'Domingo'
                WHEN '1' THEN 'Segunda'
                WHEN '2' THEN 'Terça'
                WHEN '3' THEN 'Quarta'
                WHEN '4' THEN 'Quinta'
                WHEN '5' THEN 'Sexta'
                WHEN '6' THEN 'Sábado'
            END as dia_semana,
            COUNT(*) as participantes
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY strftime('%w', p.created_at)
        ORDER BY strftime('%w', p.created_at)
    ", $params);
    
    return [
        'engajamento_por_hora' => $engajamentoPorHora,
        'engajamento_por_dia' => $engajamentoPorDia
    ];
}

function obterDadosComparativo($db, $whereClause, $params) {
    // Comparativo mensal
    $comparativoMensal = $db->fetchAll("
        SELECT 
            strftime('%Y-%m', s.created_at) as mes,
            COUNT(DISTINCT s.id) as sorteios_criados,
            COUNT(p.id) as total_participantes,
            COUNT(sr.id) as total_ganhadores
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY strftime('%Y-%m', s.created_at)
        ORDER BY mes DESC
        LIMIT 12
    ", $params);
    
    return [
        'comparativo_mensal' => array_reverse($comparativoMensal)
    ];
}

// Incluir header
include 'templates/header.php';
?>

<!-- Container principal dos relatórios -->
<div class="p-6">
    
    <!-- Cabeçalho da página -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Relatórios Avançados</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Análises detalhadas e insights dos seus sorteios</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <button id="exportar-pdf" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exportar PDF
                </button>
                <button id="exportar-csv" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exportar CSV
                </button>
                <button id="salvar-template" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                    Salvar Template
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <form id="filtros-form" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <!-- Tipo de Relatório -->
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tipo de Relatório
                </label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="participacao" <?php echo $tipo_relatorio === 'participacao' ? 'selected' : ''; ?>>
                        Participação
                    </option>
                    <option value="conversao" <?php echo $tipo_relatorio === 'conversao' ? 'selected' : ''; ?>>
                        Conversão
                    </option>
                    <option value="engajamento" <?php echo $tipo_relatorio === 'engajamento' ? 'selected' : ''; ?>>
                        Engajamento
                    </option>
                    <option value="comparativo" <?php echo $tipo_relatorio === 'comparativo' ? 'selected' : ''; ?>>
                        Comparativo
                    </option>
                </select>
            </div>
            
            <!-- Período -->
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Período
                </label>
                <select id="periodo" name="periodo" class="form-select">
                    <option value="7" <?php echo $filtro_periodo === '7' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                    <option value="30" <?php echo $filtro_periodo === '30' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                    <option value="90" <?php echo $filtro_periodo === '90' ? 'selected' : ''; ?>>Últimos 90 dias</option>
                    <option value="365" <?php echo $filtro_periodo === '365' ? 'selected' : ''; ?>>Último ano</option>
                    <option value="todos" <?php echo $filtro_periodo === 'todos' ? 'selected' : ''; ?>>Todos os períodos</option>
                </select>
            </div>
            
            <!-- Sorteio -->
            <div>
                <label for="sorteio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Sorteio
                </label>
                <select id="sorteio" name="sorteio" class="form-select">
                    <option value="">Todos os sorteios</option>
                    <?php foreach ($sorteios as $sorteio): ?>
                        <option value="<?php echo $sorteio['id']; ?>" <?php echo $filtro_sorteio == $sorteio['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sorteio['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status
                </label>
                <select id="status" name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="pausado" <?php echo $filtro_status === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                    <option value="finalizado" <?php echo $filtro_status === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                </select>
            </div>
            
            <!-- Botão Aplicar -->
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Aplicar Filtros
                </button>
            </div>
            
        </form>
    </div>
    
    <!-- Conteúdo do Relatório -->
    <div id="relatorio-content">
        <?php
        switch ($tipo_relatorio) {
            case 'participacao':
                include 'templates/relatorios/participacao.php';
                break;
            case 'conversao':
                include 'templates/relatorios/conversao.php';
                break;
            case 'engajamento':
                include 'templates/relatorios/engajamento.php';
                break;
            case 'comparativo':
                include 'templates/relatorios/comparativo.php';
                break;
            default:
                include 'templates/relatorios/participacao.php';
        }
        ?>
    </div>
    
</div>

<!-- Modal para Salvar Template -->
<div id="modal-template" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
        
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Salvar Template de Relatório
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Salve as configurações atuais como um template para reutilização futura.
                        </p>
                    </div>
                </div>
            </div>
            
            <form id="form-template" class="mt-5">
                <div class="mb-4">
                    <label for="nome-template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nome do Template
                    </label>
                    <input type="text" id="nome-template" name="nome" required 
                           class="form-input" 
                           placeholder="Ex: Relatório Mensal de Participação">
                </div>
                
                <div class="mb-4">
                    <label for="descricao-template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descrição (opcional)
                    </label>
                    <textarea id="descricao-template" name="descricao" rows="3" 
                              class="form-textarea" 
                              placeholder="Descreva o propósito deste template..."></textarea>
                </div>
                
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="btn-primary w-full sm:col-start-2">
                        Salvar Template
                    </button>
                    <button type="button" id="cancelar-template" class="btn-secondary w-full mt-3 sm:mt-0 sm:col-start-1">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dados para JavaScript -->
<script>
    window.relatorioData = {
        tipo: '<?php echo $tipo_relatorio; ?>',
        dados: <?php echo json_encode($dadosRelatorio); ?>,
        filtros: {
            periodo: '<?php echo $filtro_periodo; ?>',
            sorteio: '<?php echo $filtro_sorteio; ?>',
            status: '<?php echo $filtro_status; ?>'
        }
    };
</script>

<?php
// Incluir footer
include 'templates/footer.php';
?>