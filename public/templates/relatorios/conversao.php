<?php
/**
 * Template de Relatório de Conversão
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$conversaoGeral = $dadosRelatorio['conversao_geral'] ?? [];
$conversaoPorSorteio = $dadosRelatorio['conversao_por_sorteio'] ?? [];
?>

<!-- Métricas de Conversão -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <!-- Total de Participantes -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Participantes</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($conversaoGeral['total_participantes'] ?? 0); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Total de Ganhadores -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Ganhadores</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($conversaoGeral['total_ganhadores'] ?? 0); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Taxa de Conversão -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taxa de Conversão</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($conversaoGeral['taxa_conversao'] ?? 0, 2); ?>%
                </p>
            </div>
        </div>
    </div>
    
</div>

<!-- Indicador Visual de Conversão -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Funil de Conversão</h3>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Visualização do processo de conversão
        </div>
    </div>
    
    <div class="relative">
        <!-- Funil Visual -->
        <div class="flex items-center justify-center space-x-8">
            
            <!-- Participantes -->
            <div class="text-center">
                <div class="w-24 h-24 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mb-3">
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            <?php echo number_format($conversaoGeral['total_participantes'] ?? 0); ?>
                        </div>
                        <div class="text-xs text-blue-500 dark:text-blue-300">100%</div>
                    </div>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Participantes</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total de inscrições</p>
            </div>
            
            <!-- Seta -->
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            
            <!-- Ganhadores -->
            <div class="text-center">
                <div class="w-24 h-24 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mb-3">
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600 dark:text-green-400">
                            <?php echo number_format($conversaoGeral['total_ganhadores'] ?? 0); ?>
                        </div>
                        <div class="text-xs text-green-500 dark:text-green-300">
                            <?php echo number_format($conversaoGeral['taxa_conversao'] ?? 0, 1); ?>%
                        </div>
                    </div>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Ganhadores</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Convertidos em prêmios</p>
            </div>
            
        </div>
        
        <!-- Barra de Progresso -->
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Taxa de Conversão</span>
                <span><?php echo number_format($conversaoGeral['taxa_conversao'] ?? 0, 2); ?>%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500" 
                     style="width: <?php echo min(100, $conversaoGeral['taxa_conversao'] ?? 0); ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de Conversão por Sorteio -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Taxa de Conversão por Sorteio</h3>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                <?php echo count($conversaoPorSorteio); ?> sorteios
            </span>
        </div>
    </div>
    
    <div class="h-80">
        <canvas id="conversao-chart"></canvas>
    </div>
</div>

<!-- Tabela Detalhada de Conversão -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalhamento por Sorteio</h3>
        <button id="exportar-conversao" class="btn-secondary text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exportar
        </button>
    </div>
    
    <?php if (empty($conversaoPorSorteio)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum dado de conversão encontrado</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Nome do Sorteio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Participantes
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ganhadores
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Taxa de Conversão
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Performance
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($conversaoPorSorteio as $sorteio): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($sorteio['nome']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($sorteio['participantes']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($sorteio['ganhadores']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    <?php echo number_format($sorteio['taxa_conversao'], 2); ?>%
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $taxa = $sorteio['taxa_conversao'];
                                $performance = $taxa >= 10 ? 'alta' : ($taxa >= 5 ? 'media' : 'baixa');
                                $corClass = $performance === 'alta' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 
                                           ($performance === 'media' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : 
                                           'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200');
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $corClass; ?>">
                                    <?php echo ucfirst($performance); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Dados para os gráficos de conversão
window.conversaoData = {
    conversao_geral: <?php echo json_encode($conversaoGeral); ?>,
    conversao_por_sorteio: <?php echo json_encode($conversaoPorSorteio); ?>
};
</script>