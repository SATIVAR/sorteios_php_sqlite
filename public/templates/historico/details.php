<?php
/**
 * Template de Detalhes do Histórico de Sorteios
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}
?>

<!-- Cabeçalho da página -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2 mb-2">
                <a href="historico.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo htmlspecialchars($sorteio['nome']); ?>
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?php 
                    switch($sorteio['status']) {
                        case 'ativo':
                            echo 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
                            break;
                        case 'pausado':
                            echo 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
                            break;
                        case 'finalizado':
                            echo 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                            break;
                        default:
                            echo 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                    }
                    ?>">
                    <?php echo ucfirst($sorteio['status']); ?>
                </span>
            </div>
            
            <?php if ($sorteio['descricao']): ?>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    <?php echo htmlspecialchars($sorteio['descricao']); ?>
                </p>
            <?php endif; ?>
            
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Criado em <?php echo formatDateBR($sorteio['created_at']); ?>
                <?php if ($sorteio['public_url']): ?>
                    • URL: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($sorteio['public_url']); ?></code>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="sorteios.php?action=edit&id=<?php echo $sorteio['id']; ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar Sorteio
            </a>
            
            <?php if ($sorteio['status'] === 'ativo'): ?>
                <a href="sorteios.php?action=draw&id=<?php echo $sorteio['id']; ?>" 
                   class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 0v1m-2 0V6a2 2 0 00-2 0v1m2 0V9.5m0 0V8"></path>
                    </svg>
                    Realizar Sorteio
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estatísticas do sorteio -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_participantes']); ?></p>
            </div>
        </div>
    </div>
    
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
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Participantes Sorteados</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_sorteados']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 0v1m-2 0V6a2 2 0 00-2 0v1m2 0V9.5m0 0V8"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteios Realizados</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_sorteios']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Restantes</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['participantes_restantes']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Timeline de atividades -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Timeline de Atividades</h3>
    
    <?php if (empty($timeline)): ?>
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhuma atividade registrada</p>
        </div>
    <?php else: ?>
        <div class="flow-root">
            <ul class="-mb-8">
                <?php foreach ($timeline as $index => $evento): ?>
                    <li>
                        <div class="relative pb-8">
                            <?php if ($index < count($timeline) - 1): ?>
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600" aria-hidden="true"></span>
                            <?php endif; ?>
                            
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                        <?php 
                                        switch($evento['color']) {
                                            case 'blue': echo 'bg-blue-500'; break;
                                            case 'green': echo 'bg-green-500'; break;
                                            case 'yellow': echo 'bg-yellow-500'; break;
                                            case 'purple': echo 'bg-purple-500'; break;
                                            default: echo 'bg-gray-500';
                                        }
                                        ?>">
                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </span>
                                </div>
                                
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($evento['title']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($evento['description']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time datetime="<?php echo $evento['timestamp']; ?>">
                                            <?php echo formatDateBR($evento['timestamp']); ?>
                                        </time>
                                        <div class="text-xs">
                                            <?php echo formatTimeAgo($evento['timestamp']); ?>
                                        </div>
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

<!-- Histórico de sorteios realizados -->
<?php if (!empty($historico_sorteios)): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Histórico de Sorteios Realizados
            <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                (<?php echo count($historico_sorteios); ?> resultados)
            </span>
        </h3>
    </div>
    
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <?php 
        $current_resultado_id = null;
        $resultado_count = 0;
        ?>
        
        <?php foreach ($historico_sorteios as $resultado): ?>
            <?php if ($current_resultado_id !== $resultado['resultado_id']): ?>
                <?php if ($current_resultado_id !== null): ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php 
                $current_resultado_id = $resultado['resultado_id'];
                $resultado_count++;
                ?>
                
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                Sorteio #<?php echo $resultado_count; ?>
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Realizado em <?php echo formatDateBR($resultado['data_sorteio']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                <?php 
                                $count_sorteados = count(array_filter($historico_sorteios, function($r) use ($resultado) {
                                    return $r['resultado_id'] === $resultado['resultado_id'];
                                }));
                                echo $count_sorteados;
                                ?> sorteado(s)
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php endif; ?>
            
            <!-- Card do participante sorteado -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                        <?php echo $resultado['posicao']; ?>º lugar
                    </span>
                </div>
                
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($resultado['nome']); ?>
                    </p>
                    
                    <?php if ($resultado['whatsapp']): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            WhatsApp: <?php echo formatWhatsApp($resultado['whatsapp']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($resultado['cpf']): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            CPF: <?php echo formatCPF($resultado['cpf']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($resultado['email']): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?php echo htmlspecialchars($resultado['email']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php endforeach; ?>
        
        <?php if ($current_resultado_id !== null): ?>
                    </div>
                </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>