<?php
/**
 * Template de Relatório de Engajamento
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$engajamentoPorHora = $dadosRelatorio['engajamento_por_hora'] ?? [];
$engajamentoPorDia = $dadosRelatorio['engajamento_por_dia'] ?? [];

// Calcular métricas de engajamento
$totalParticipacoes = array_sum(array_column($engajamentoPorHora, 'participantes'));
$horasPico = [];
$diasPico = [];

if (!empty($engajamentoPorHora)) {
    $maxParticipacoes = max(array_column($engajamentoPorHora, 'participantes'));
    foreach ($engajamentoPorHora as $hora) {
        if ($hora['participantes'] == $maxParticipacoes) {
            $horasPico[] = $hora['hora'] . 'h';
        }
    }
}

if (!empty($engajamentoPorDia)) {
    $maxParticipacoesDia = max(array_column($engajamentoPorDia, 'participantes'));
    foreach ($engajamentoPorDia as $dia) {
        if ($dia['participantes'] == $maxParticipacoesDia) {
            $diasPico[] = $dia['dia_semana'];
        }
    }
}
?>

<!-- Métricas de Engajamento -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    
    <!-- Total de Participações -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Participações</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo number_format($totalParticipacoes); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Horário de Pico -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Horário de Pico</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo !empty($horasPico) ? implode(', ', $horasPico) : 'N/A'; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Dia de Pico -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dia de Pico</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo !empty($diasPico) ? implode(', ', $diasPico) : 'N/A'; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Média por Hora -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Média por Hora</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo count($engajamentoPorHora) > 0 ? number_format($totalParticipacoes / count($engajamentoPorHora), 1) : '0'; ?>
                </p>
            </div>
        </div>
    </div>
    
</div>

<!-- Grid de Gráficos de Engajamento -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    
    <!-- Engajamento por Horário -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Engajamento por Horário</h3>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                    24 horas
                </span>
            </div>
        </div>
        
        <div class="h-80">
            <canvas id="engajamento-hora-chart"></canvas>
        </div>
        
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            <p><strong>Insight:</strong> 
            <?php if (!empty($horasPico)): ?>
                O maior engajamento ocorre às <?php echo implode(' e ', $horasPico); ?>.
            <?php else: ?>
                Dados insuficientes para determinar horário de pico.
            <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Engajamento por Dia da Semana -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Engajamento por Dia da Semana</h3>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                    7 dias
                </span>
            </div>
        </div>
        
        <div class="h-80">
            <canvas id="engajamento-dia-chart"></canvas>
        </div>
        
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            <p><strong>Insight:</strong> 
            <?php if (!empty($diasPico)): ?>
                <?php echo implode(' e ', $diasPico); ?> <?php echo count($diasPico) > 1 ? 'são os dias' : 'é o dia'; ?> com maior participação.
            <?php else: ?>
                Dados insuficientes para determinar dia de pico.
            <?php endif; ?>
            </p>
        </div>
    </div>
    
</div>

<!-- Análise Detalhada de Padrões -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Análise de Padrões de Engajamento</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Padrões por Horário -->
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Distribuição por Período do Dia</h4>
            
            <?php
            // Agrupar por períodos do dia
            $periodos = [
                'Madrugada (00h-06h)' => 0,
                'Manhã (06h-12h)' => 0,
                'Tarde (12h-18h)' => 0,
                'Noite (18h-24h)' => 0
            ];
            
            foreach ($engajamentoPorHora as $hora) {
                $h = (int)$hora['hora'];
                if ($h >= 0 && $h < 6) {
                    $periodos['Madrugada (00h-06h)'] += $hora['participantes'];
                } elseif ($h >= 6 && $h < 12) {
                    $periodos['Manhã (06h-12h)'] += $hora['participantes'];
                } elseif ($h >= 12 && $h < 18) {
                    $periodos['Tarde (12h-18h)'] += $hora['participantes'];
                } else {
                    $periodos['Noite (18h-24h)'] += $hora['participantes'];
                }
            }
            ?>
            
            <div class="space-y-4">
                <?php foreach ($periodos as $periodo => $participantes): ?>
                    <?php 
                    $porcentagem = $totalParticipacoes > 0 ? ($participantes / $totalParticipacoes) * 100 : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span><?php echo $periodo; ?></span>
                            <span><?php echo number_format($participantes); ?> (<?php echo number_format($porcentagem, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: <?php echo $porcentagem; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Padrões por Dia da Semana -->
        <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Distribuição Semanal</h4>
            
            <?php
            // Agrupar por tipo de dia
            $tiposDia = [
                'Dias Úteis' => 0,
                'Fim de Semana' => 0
            ];
            
            foreach ($engajamentoPorDia as $dia) {
                if (in_array($dia['dia_semana'], ['Sábado', 'Domingo'])) {
                    $tiposDia['Fim de Semana'] += $dia['participantes'];
                } else {
                    $tiposDia['Dias Úteis'] += $dia['participantes'];
                }
            }
            ?>
            
            <div class="space-y-4">
                <?php foreach ($tiposDia as $tipo => $participantes): ?>
                    <?php 
                    $porcentagem = $totalParticipacoes > 0 ? ($participantes / $totalParticipacoes) * 100 : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span><?php echo $tipo; ?></span>
                            <span><?php echo number_format($participantes); ?> (<?php echo number_format($porcentagem, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: <?php echo $porcentagem; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Lista detalhada por dia -->
            <div class="mt-6">
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Detalhamento por Dia</h5>
                <div class="space-y-2">
                    <?php foreach ($engajamentoPorDia as $dia): ?>
                        <?php 
                        $porcentagem = $totalParticipacoes > 0 ? ($dia['participantes'] / $totalParticipacoes) * 100 : 0;
                        ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400"><?php echo $dia['dia_semana']; ?></span>
                            <div class="flex items-center space-x-2">
                                <span class="text-gray-900 dark:text-white font-medium">
                                    <?php echo number_format($dia['participantes']); ?>
                                </span>
                                <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                    <div class="bg-purple-500 h-1 rounded-full" 
                                         style="width: <?php echo $porcentagem; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Recomendações Baseadas em Engajamento -->
<div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-6">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-2">
                Recomendações para Otimizar Engajamento
            </h3>
            <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <?php if (!empty($horasPico)): ?>
                    <p>• <strong>Horário Ideal:</strong> Lance novos sorteios às <?php echo implode(' ou ', $horasPico); ?> para maximizar participação.</p>
                <?php endif; ?>
                
                <?php if (!empty($diasPico)): ?>
                    <p>• <strong>Dia Estratégico:</strong> <?php echo implode(' e ', $diasPico); ?> <?php echo count($diasPico) > 1 ? 'são os melhores dias' : 'é o melhor dia'; ?> para lançamentos.</p>
                <?php endif; ?>
                
                <?php 
                $melhorPeriodo = array_keys($periodos, max($periodos))[0];
                ?>
                <p>• <strong>Período de Maior Engajamento:</strong> <?php echo $melhorPeriodo; ?> concentra a maior participação.</p>
                
                <?php if ($tiposDia['Fim de Semana'] > $tiposDia['Dias Úteis']): ?>
                    <p>• <strong>Padrão Semanal:</strong> Fins de semana geram mais engajamento que dias úteis.</p>
                <?php else: ?>
                    <p>• <strong>Padrão Semanal:</strong> Dias úteis são mais efetivos para engajamento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Dados para os gráficos de engajamento
window.engajamentoData = {
    engajamento_por_hora: <?php echo json_encode($engajamentoPorHora); ?>,
    engajamento_por_dia: <?php echo json_encode($engajamentoPorDia); ?>,
    periodos: <?php echo json_encode($periodos); ?>,
    tipos_dia: <?php echo json_encode($tiposDia); ?>
};
</script>