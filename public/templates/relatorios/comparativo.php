<?php
/**
 * Template de Relatório Comparativo
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$comparativoMensal = $dadosRelatorio['comparativo_mensal'] ?? [];

// Calcular métricas comparativas
$totalMeses = count($comparativoMensal);
$crescimentoSorteios = 0;
$crescimentoParticipantes = 0;
$tendencia = 'estável';

if ($totalMeses >= 2) {
    $mesAtual = end($comparativoMensal);
    $mesAnterior = prev($comparativoMensal);
    
    if ($mesAnterior['sorteios_criados'] > 0) {
        $crescimentoSorteios = (($mesAtual['sorteios_criados'] - $mesAnterior['sorteios_criados']) / $mesAnterior['sorteios_criados']) * 100;
    }
    
    if ($mesAnterior['total_participantes'] > 0) {
        $crescimentoParticipantes = (($mesAtual['total_participantes'] - $mesAnterior['total_participantes']) / $mesAnterior['total_participantes']) * 100;
    }
    
    if ($crescimentoParticipantes > 5) {
        $tendencia = 'crescimento';
    } elseif ($crescimentoParticipantes < -5) {
        $tendencia = 'declínio';
    }
}

// Calcular totais
$totalSorteiosPeriodo = array_sum(array_column($comparativoMensal, 'sorteios_criados'));
$totalParticipantesPeriodo = array_sum(array_column($comparativoMensal, 'total_participantes'));
$totalGanhadoresPeriodo = array_sum(array_column($comparativoMensal, 'total_ganhadores'));
?>

<!-- Métricas Comparativas -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    
    <!-- Total do Período -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteios no Período</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($totalSorteiosPeriodo); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Crescimento de Sorteios -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 <?php echo $crescimentoSorteios >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'; ?> rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 <?php echo $crescimentoSorteios >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php if ($crescimentoSorteios >= 0): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        <?php endif; ?>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Crescimento Sorteios</p>
                <p class="text-2xl font-bold <?php echo $crescimentoSorteios >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                    <?php echo $crescimentoSorteios >= 0 ? '+' : ''; ?><?php echo number_format($crescimentoSorteios, 1); ?>%
                </p>
            </div>
        </div>
    </div>
    
    <!-- Crescimento de Participantes -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 <?php echo $crescimentoParticipantes >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'; ?> rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 <?php echo $crescimentoParticipantes >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php if ($crescimentoParticipantes >= 0): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        <?php endif; ?>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Crescimento Participantes</p>
                <p class="text-2xl font-bold <?php echo $crescimentoParticipantes >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                    <?php echo $crescimentoParticipantes >= 0 ? '+' : ''; ?><?php echo number_format($crescimentoParticipantes, 1); ?>%
                </p>
            </div>
        </div>
    </div>
    
    <!-- Tendência -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 <?php echo $tendencia === 'crescimento' ? 'bg-green-100 dark:bg-green-900' : ($tendencia === 'declínio' ? 'bg-red-100 dark:bg-red-900' : 'bg-yellow-100 dark:bg-yellow-900'); ?> rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 <?php echo $tendencia === 'crescimento' ? 'text-green-600 dark:text-green-400' : ($tendencia === 'declínio' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tendência</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo ucfirst($tendencia); ?>
                </p>
            </div>
        </div>
    </div>
    
</div>

<!-- Gráfico Comparativo Principal -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Evolução Mensal</h3>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                <?php echo $totalMeses; ?> meses
            </span>
        </div>
    </div>
    
    <div class="h-96">
        <canvas id="comparativo-mensal-chart"></canvas>
    </div>
</div>

<!-- Análise Detalhada por Mês -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Análise Detalhada por Mês</h3>
        <button id="exportar-comparativo" class="btn-secondary text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exportar
        </button>
    </div>
    
    <?php if (empty($comparativoMensal)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum dado comparativo encontrado</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Mês/Ano
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Sorteios Criados
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
                            Variação
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($comparativoMensal as $index => $mes): ?>
                        <?php 
                        $taxaConversao = $mes['total_participantes'] > 0 ? ($mes['total_ganhadores'] / $mes['total_participantes']) * 100 : 0;
                        
                        // Calcular variação em relação ao mês anterior
                        $variacao = 0;
                        if ($index > 0) {
                            $mesAnterior = $comparativoMensal[$index - 1];
                            if ($mesAnterior['total_participantes'] > 0) {
                                $variacao = (($mes['total_participantes'] - $mesAnterior['total_participantes']) / $mesAnterior['total_participantes']) * 100;
                            }
                        }
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo date('M/Y', strtotime($mes['mes'] . '-01')); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($mes['sorteios_criados']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    <?php echo number_format($mes['total_participantes']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($mes['total_ganhadores']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($taxaConversao, 2); ?>%
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($index === 0): ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $variacao >= 0 ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                        <?php echo $variacao >= 0 ? '+' : ''; ?><?php echo number_format($variacao, 1); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Insights e Recomendações -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Insights Principais -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-6">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-3">
                    Insights Principais
                </h3>
                <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <?php if ($totalMeses >= 2): ?>
                        <p>• <strong>Tendência Atual:</strong> O sistema está em <?php echo $tendencia; ?> com base nos últimos dados.</p>
                        
                        <?php if ($crescimentoParticipantes > 0): ?>
                            <p>• <strong>Crescimento Positivo:</strong> Aumento de <?php echo number_format($crescimentoParticipantes, 1); ?>% na participação.</p>
                        <?php elseif ($crescimentoParticipantes < 0): ?>
                            <p>• <strong>Atenção Necessária:</strong> Queda de <?php echo number_format(abs($crescimentoParticipantes), 1); ?>% na participação.</p>
                        <?php endif; ?>
                        
                        <?php 
                        $melhorMes = array_reduce($comparativoMensal, function($carry, $item) {
                            return ($carry === null || $item['total_participantes'] > $carry['total_participantes']) ? $item : $carry;
                        });
                        ?>
                        <p>• <strong>Melhor Performance:</strong> <?php echo date('M/Y', strtotime($melhorMes['mes'] . '-01')); ?> com <?php echo number_format($melhorMes['total_participantes']); ?> participantes.</p>
                        
                        <?php 
                        $mediaParticipantes = $totalParticipantesPeriodo / $totalMeses;
                        ?>
                        <p>• <strong>Média Mensal:</strong> <?php echo number_format($mediaParticipantes, 0); ?> participantes por mês.</p>
                    <?php else: ?>
                        <p>• Dados insuficientes para análise comparativa detalhada.</p>
                        <p>• Continue coletando dados para obter insights mais precisos.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recomendações Estratégicas -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700 p-6">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-green-900 dark:text-green-100 mb-3">
                    Recomendações Estratégicas
                </h3>
                <div class="space-y-2 text-sm text-green-800 dark:text-green-200">
                    <?php if ($tendencia === 'crescimento'): ?>
                        <p>• <strong>Aproveite o Momentum:</strong> Aumente a frequência de sorteios para capitalizar o crescimento.</p>
                        <p>• <strong>Diversifique:</strong> Teste novos tipos de sorteios e prêmios.</p>
                    <?php elseif ($tendencia === 'declínio'): ?>
                        <p>• <strong>Revise a Estratégia:</strong> Analise os fatores que podem estar causando a queda.</p>
                        <p>• <strong>Melhore o Engajamento:</strong> Considere prêmios mais atrativos ou mudanças na comunicação.</p>
                    <?php else: ?>
                        <p>• <strong>Mantenha a Consistência:</strong> Continue com a estratégia atual que está funcionando.</p>
                        <p>• <strong>Teste Melhorias:</strong> Experimente pequenas otimizações para impulsionar o crescimento.</p>
                    <?php endif; ?>
                    
                    <p>• <strong>Análise Contínua:</strong> Monitore mensalmente para identificar padrões sazonais.</p>
                    <p>• <strong>Benchmarking:</strong> Compare com períodos anteriores para avaliar progresso.</p>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
// Dados para os gráficos comparativos
window.comparativoData = {
    comparativo_mensal: <?php echo json_encode($comparativoMensal); ?>,
    crescimento_sorteios: <?php echo $crescimentoSorteios; ?>,
    crescimento_participantes: <?php echo $crescimentoParticipantes; ?>,
    tendencia: '<?php echo $tendencia; ?>'
};
</script>