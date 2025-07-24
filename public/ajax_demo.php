<?php
/**
 * Sistema de Sorteios - Demonstração AJAX
 * Página de demonstração das funcionalidades AJAX
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';

// Título da página
$pageTitle = 'Demonstração AJAX';

// Incluir cabeçalho
include_once 'templates/header.php';
include_once 'templates/sidebar.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg">
        <h1 class="text-2xl font-semibold mb-4">Demonstração de Funcionalidades AJAX</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Métricas em Tempo Real -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-2">Métricas em Tempo Real</h2>
                <div id="dashboard-metrics" class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <span class="text-sm text-gray-500">Total de Sorteios</span>
                        <p class="text-xl font-bold" data-metric="total_sorteios">0</p>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <span class="text-sm text-gray-500">Total de Participantes</span>
                        <p class="text-xl font-bold" data-metric="total_participantes">0</p>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded-lg">
                        <span class="text-sm text-gray-500">Sorteios Ativos</span>
                        <p class="text-xl font-bold" data-metric="sorteios_ativos">0</p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <span class="text-sm text-gray-500">Participantes Hoje</span>
                        <p class="text-xl font-bold" data-metric="participantes_hoje">0</p>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-sm font-medium mb-2">Sorteios Populares</h3>
                    <div id="sorteios-populares" class="border rounded-lg overflow-hidden">
                        <div class="p-4 text-center text-gray-500">Carregando...</div>
                    </div>
                </div>
            </div>
            
            <!-- Notificações -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold">Notificações</h2>
                    <span id="notifications-badge" class="px-2 py-1 text-xs bg-red-500 text-white rounded-full hidden">0</span>
                </div>
                <div id="notifications-container" class="border rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                    <div id="notifications-list" class="divide-y">
                        <div class="p-4 text-center text-gray-500">Carregando notificações...</div>
                    </div>
                </div>
                <div class="mt-2 text-right">
                    <button data-action="mark-all-read" class="text-sm text-blue-600 hover:underline">Marcar todas como lidas</button>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="text-lg font-semibold mb-2">Gráficos em Tempo Real</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium mb-2">Participação (últimos 30 dias)</h3>
                    <div class="border rounded-lg p-2 h-64">
                        <canvas id="participation-chart" data-chart="participation" data-chart-type="line" data-period="30"></canvas>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium mb-2">Sorteios por Status</h3>
                    <div class="border rounded-lg p-2 h-64">
                        <canvas id="status-chart" data-chart="sorteios" data-chart-type="doughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Sorteios com Lazy Loading -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="text-lg font-semibold mb-2">Lista de Sorteios (Lazy Loading)</h2>
            <div class="mb-4 flex flex-wrap gap-2">
                <div class="flex-1">
                    <input type="text" id="sorteios-search" placeholder="Buscar sorteios..." class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <select data-filter="sorteios" name="status" class="px-3 py-2 border rounded-lg">
                        <option value="">Todos os status</option>
                        <option value="ativo">Ativos</option>
                        <option value="pausado">Pausados</option>
                        <option value="finalizado">Finalizados</option>
                    </select>
                </div>
            </div>
            <div id="sorteios-list" data-lazy-load="true" data-url="<?php echo getBaseUrl(); ?>/ajax/data_realtime.php?action=search_sorteios" data-infinite-scroll="true" data-render-function="renderSorteioItem" class="space-y-4">
                <div class="p-4 text-center text-gray-500">Carregando sorteios...</div>
            </div>
        </div>
        
        <!-- Demonstração de Cache -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Demonstração de Cache</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium mb-2">Estatísticas de Cache</h3>
                    <div id="cache-stats" class="border rounded-lg p-4">
                        <?php
                        $cache = getCache();
                        $stats = $cache->getStats();
                        ?>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-xs text-gray-500">Total de Itens</span>
                                <p class="font-medium"><?php echo $stats['total_items']; ?></p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Tamanho Total</span>
                                <p class="font-medium"><?php echo formatBytes($stats['total_size']); ?></p>
                            </div>
                            <?php if (!empty($stats['newest_item'])): ?>
                            <div>
                                <span class="text-xs text-gray-500">Item Mais Recente</span>
                                <p class="font-medium truncate"><?php echo $stats['newest_item']; ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($stats['oldest_item'])): ?>
                            <div>
                                <span class="text-xs text-gray-500">Item Mais Antigo</span>
                                <p class="font-medium truncate"><?php echo $stats['oldest_item']; ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <button id="clear-cache" class="text-sm text-blue-600 hover:underline">Limpar Cache</button>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium mb-2">Teste de Performance</h3>
                    <div class="border rounded-lg p-4">
                        <div class="mb-2">
                            <button id="test-no-cache" class="px-3 py-2 bg-blue-600 text-white rounded-lg mr-2">Testar sem Cache</button>
                            <button id="test-with-cache" class="px-3 py-2 bg-green-600 text-white rounded-lg">Testar com Cache</button>
                        </div>
                        <div id="performance-results" class="mt-2 text-sm">
                            <p>Clique nos botões acima para testar a performance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos da página -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Função para renderizar item de sorteio (usada pelo lazy loading)
function renderSorteioItem(sorteio) {
    const statusClass = getSorteioStatusClass(sorteio.status);
    const percentFilled = sorteio.max_participantes > 0 
        ? Math.min(100, Math.round((sorteio.total_participantes / sorteio.max_participantes) * 100))
        : 0;
    
    return `
        <div class="bg-white rounded-lg shadow p-4" data-item-id="${sorteio.id}">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-semibold">
                    <a href="sorteios.php?id=${sorteio.id}" class="text-blue-600 hover:underline">
                        ${sorteio.nome}
                    </a>
                </h3>
                <span class="px-2 py-1 text-xs rounded ${statusClass}">
                    ${formatSorteioStatus(sorteio.status)}
                </span>
            </div>
            
            <p class="text-gray-600 text-sm mt-1">${sorteio.descricao || 'Sem descrição'}</p>
            
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div>
                    <span class="text-xs text-gray-500">Participantes</span>
                    <p class="font-medium">${sorteio.total_participantes}${sorteio.max_participantes > 0 ? '/' + sorteio.max_participantes : ''}</p>
                    
                    ${sorteio.max_participantes > 0 ? `
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${percentFilled}%"></div>
                        </div>
                    ` : ''}
                </div>
                <div>
                    <span class="text-xs text-gray-500">Sorteados</span>
                    <p class="font-medium">${sorteio.total_sorteados}/${sorteio.qtd_sorteados}</p>
                </div>
            </div>
            
            <div class="mt-3 text-xs text-gray-500">
                Criado em ${sorteio.formatted_date}
            </div>
            
            <div class="mt-3 flex space-x-2">
                <a href="sorteios.php?id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                    Detalhes
                </a>
                <a href="participantes.php?sorteio_id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                    Participantes
                </a>
                <a href="sorteio_engine.php?id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                    Sortear
                </a>
            </div>
        </div>
    `;
}

// Função para obter classe CSS para status do sorteio
function getSorteioStatusClass(status) {
    switch (status) {
        case 'ativo': return 'bg-green-100 text-green-800';
        case 'pausado': return 'bg-yellow-100 text-yellow-800';
        case 'finalizado': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

// Função para formatar status do sorteio
function formatSorteioStatus(status) {
    switch (status) {
        case 'ativo': return 'Ativo';
        case 'pausado': return 'Pausado';
        case 'finalizado': return 'Finalizado';
        default: return status;
    }
}

// Demonstração de cache
document.addEventListener('DOMContentLoaded', function() {
    // Limpar cache
    document.getElementById('clear-cache').addEventListener('click', function() {
        $.ajax({
            url: '<?php echo getBaseUrl(); ?>/ajax/dashboard.php',
            data: { action: 'clear_cache' },
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    showNotification('Cache limpo com sucesso', 'success');
                    // Atualizar estatísticas
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Erro ao limpar cache', 'error');
                }
            }
        });
    });
    
    // Teste sem cache
    document.getElementById('test-no-cache').addEventListener('click', function() {
        const startTime = performance.now();
        
        $.ajax({
            url: '<?php echo getBaseUrl(); ?>/ajax/data_realtime.php',
            data: { 
                action: 'get_sorteios',
                cache: 'no'
            },
            method: 'GET',
            success: function(response) {
                const endTime = performance.now();
                const duration = (endTime - startTime).toFixed(2);
                
                document.getElementById('performance-results').innerHTML = `
                    <p class="text-blue-600">Sem cache: ${duration}ms</p>
                    <p>Itens carregados: ${response.data.sorteios.length}</p>
                `;
            }
        });
    });
    
    // Teste com cache
    document.getElementById('test-with-cache').addEventListener('click', function() {
        const startTime = performance.now();
        
        $.ajax({
            url: '<?php echo getBaseUrl(); ?>/ajax/data_realtime.php',
            data: { 
                action: 'get_sorteios',
                cache: 'yes'
            },
            method: 'GET',
            success: function(response) {
                const endTime = performance.now();
                const duration = (endTime - startTime).toFixed(2);
                
                const currentResults = document.getElementById('performance-results').innerHTML;
                document.getElementById('performance-results').innerHTML = `
                    ${currentResults}
                    <p class="text-green-600">Com cache: ${duration}ms</p>
                    <p>Itens carregados: ${response.data.sorteios.length}</p>
                `;
            }
        });
    });
});
</script>

<?php
// Incluir scripts AJAX
include_once 'templates/ajax_scripts.php';

// Incluir rodapé
include_once 'templates/footer.php';

/**
 * Função auxiliar para formatar bytes
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>