<?php
/**
 * Dashboard Administrativo - Sistema de Sorteios
 * Página principal com métricas e análises
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';

// Configurações da página
$page_title = 'Dashboard';
$current_page = 'dashboard';
$show_sidebar = true;
$page_scripts = ['/assets/js/admin.js'];

// Obter métricas do dashboard
$db = getDatabase();

try {
    // Métricas principais
    $totalSorteios = $db->fetchOne("SELECT COUNT(*) as count FROM sorteios")['count'] ?? 0;
    $totalParticipantes = $db->fetchOne("SELECT COUNT(*) as count FROM participantes")['count'] ?? 0;
    $sorteiosAtivos = $db->fetchOne("SELECT COUNT(*) as count FROM sorteios WHERE status = 'ativo'")['count'] ?? 0;
    $sorteiosFinalizados = $db->fetchOne("SELECT COUNT(*) as count FROM sorteio_resultados")['count'] ?? 0;
    
    // Atividades recentes (últimos 10 sorteios)
    $atividadesRecentes = $db->fetchAll("
        SELECT s.nome, s.status, s.created_at, 
               COUNT(p.id) as total_participantes,
               COUNT(sr.id) as total_sorteados
        FROM sorteios s 
        LEFT JOIN participantes p ON s.id = p.sorteio_id 
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        GROUP BY s.id 
        ORDER BY s.created_at DESC 
        LIMIT 10
    ");
    
    // Dados para gráfico de participação (últimos 30 dias)
    $dadosGrafico = $db->fetchAll("
        SELECT DATE(p.created_at) as data, COUNT(*) as participantes
        FROM participantes p
        WHERE p.created_at >= date('now', '-30 days')
        GROUP BY DATE(p.created_at)
        ORDER BY data ASC
    ");
    
    // Sorteios mais populares
    $sorteiosPopulares = $db->fetchAll("
        SELECT s.nome, s.status, COUNT(p.id) as total_participantes
        FROM sorteios s 
        LEFT JOIN participantes p ON s.id = p.sorteio_id 
        GROUP BY s.id 
        ORDER BY total_participantes DESC 
        LIMIT 5
    ");
    
} catch (Exception $e) {
    error_log("Erro ao carregar dashboard: " . $e->getMessage());
    $totalSorteios = $totalParticipantes = $sorteiosAtivos = $sorteiosFinalizados = 0;
    $atividadesRecentes = $dadosGrafico = $sorteiosPopulares = [];
}

// Preparar dados do gráfico para JavaScript
$graficoLabels = [];
$graficoData = [];
foreach ($dadosGrafico as $item) {
    $graficoLabels[] = date('d/m', strtotime($item['data']));
    $graficoData[] = (int)$item['participantes'];
}

// Incluir header
include 'templates/header.php';
?>

<!-- Container principal do dashboard -->
<div class="p-6">
    
    <!-- Cabeçalho da página -->
    <div class="mb-8">
        <p class="mt-2 text-gray-600 dark:text-gray-400">Visão geral dos seus sorteios e participantes</p>
    </div>
        
    <!-- Cards de métricas principais -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total de Sorteios -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow card-hover">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Sorteios</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white counter" data-target="<?php echo $totalSorteios; ?>">0</p>
                </div>
            </div>
        </div>
        
        <!-- Total de Participantes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow card-hover">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Participantes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white counter" data-target="<?php echo $totalParticipantes; ?>">0</p>
                </div>
            </div>
        </div>
        
        <!-- Sorteios Ativos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow card-hover">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteios Ativos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white counter" data-target="<?php echo $sorteiosAtivos; ?>">0</p>
                </div>
            </div>
        </div>
        
        <!-- Sorteios Finalizados -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow card-hover">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteios Finalizados</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white counter" data-target="<?php echo $sorteiosFinalizados; ?>">0</p>
                </div>
            </div>
        </div>
        
    </div>
        
    <!-- Grid de conteúdo -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Gráfico de Participação -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Participação nos Últimos 30 Dias</h3>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                            Atualizado agora
                        </span>
                    </div>
                </div>
                
                <div class="h-80">
                    <canvas id="participationChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Sorteios Populares -->
        <div class="space-y-6">
            
            <!-- Card Sorteios Populares -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sorteios Mais Populares</h3>
                
                <?php if (empty($sorteiosPopulares)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum sorteio criado ainda</p>
                        <a href="sorteios.php" class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 hover:bg-blue-200 dark:hover:bg-blue-800">
                            Criar Primeiro Sorteio
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($sorteiosPopulares as $sorteio): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($sorteio['nome']); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Status: 
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $sorteio['status'] === 'ativo' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200'; ?>">
                                            <?php echo ucfirst($sorteio['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $sorteio['total_participantes']; ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">participantes</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Card Ações Rápidas -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ações Rápidas</h3>
                
                <div class="space-y-3">
                    <a href="sorteios.php?action=new" class="btn-primary block w-full text-white text-center py-2 px-4 rounded-lg transition-colors">
                        <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Sorteio
                    </a>
                    
                    <a href="historico.php" class="block w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-center py-2 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ver Histórico
                    </a>
                    
                    <a href="relatorios.php" class="block w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-center py-2 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                        </svg>
                        Relatórios
                    </a>
                </div>
            </div>
            
        </div>
        
    </div>
    
    <!-- Timeline de Atividades Recentes -->
    <div class="mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Atividades Recentes</h3>
            
            <?php if (empty($atividadesRecentes)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhuma atividade recente</p>
                </div>
            <?php else: ?>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <?php foreach ($atividadesRecentes as $index => $atividade): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index < count($atividadesRecentes) - 1): ?>
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full 
                                                <?php echo $atividade['status'] === 'ativo' ? 'bg-green-500' : 'bg-gray-400'; ?> 
                                                flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-900 dark:text-white">
                                                    <strong><?php echo htmlspecialchars($atividade['nome']); ?></strong>
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <?php echo $atividade['total_participantes']; ?> participantes
                                                    <?php if ($atividade['total_sorteados'] > 0): ?>
                                                        • <?php echo $atividade['total_sorteados']; ?> sorteados
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                <time datetime="<?php echo $atividade['created_at']; ?>">
                                                    <?php echo formatDateBR($atividade['created_at']); ?>
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
        
</div>

<!-- Dados para JavaScript -->
<script>
    window.dashboardData = {
        chartLabels: <?php echo json_encode($graficoLabels); ?>,
        chartData: <?php echo json_encode($graficoData); ?>,
        metrics: {
            totalSorteios: <?php echo $totalSorteios; ?>,
            totalParticipantes: <?php echo $totalParticipantes; ?>,
            sorteiosAtivos: <?php echo $sorteiosAtivos; ?>,
            sorteiosFinalizados: <?php echo $sorteiosFinalizados; ?>
        }
    };
</script>

<?php
// Incluir footer
include 'templates/footer.php';
?>