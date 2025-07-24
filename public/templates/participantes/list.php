<?php
/**
 * Template: Lista de Sorteios com Participantes
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}
?>

<!-- Cabeçalho da página -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gerenciar Participantes</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Visualize e gerencie participantes de todos os sorteios</p>
        </div>
    </div>
</div>

<!-- Filtros e busca -->
<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <label for="search" class="sr-only">Buscar sorteios</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" 
                       placeholder="Buscar por nome do sorteio...">
            </div>
        </div>
        <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Buscar
        </button>
        <?php if (!empty($search)): ?>
            <a href="participantes.php" class="btn-secondary px-6 py-2 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                Limpar
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista de sorteios -->
<?php if (empty($sorteios)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            <?php echo !empty($search) ? 'Nenhum sorteio encontrado' : 'Nenhum sorteio criado ainda'; ?>
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
            <?php echo !empty($search) ? 'Tente ajustar os filtros de busca.' : 'Crie seu primeiro sorteio para começar a gerenciar participantes.'; ?>
        </p>
        <?php if (empty($search)): ?>
            <a href="sorteios.php?action=new" class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Criar Primeiro Sorteio
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($sorteios as $sorteio): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                <!-- Header do card -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                <?php echo htmlspecialchars($sorteio['nome']); ?>
                            </h3>
                            <?php if ($sorteio['descricao']): ?>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    <?php echo htmlspecialchars($sorteio['descricao']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php echo getStatusBadgeClass($sorteio['status']); ?>">
                            <?php echo ucfirst($sorteio['status']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                <?php echo $sorteio['total_participantes']; ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Participantes</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                <?php echo $sorteio['total_sorteados']; ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Sorteados</div>
                        </div>
                    </div>
                    
                    <!-- Informações adicionais -->
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <?php if ($sorteio['max_participantes'] > 0): ?>
                            <div class="flex justify-between">
                                <span>Limite:</span>
                                <span><?php echo $sorteio['max_participantes']; ?> participantes</span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span>Criado em:</span>
                            <span><?php echo formatDateBR($sorteio['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Ações -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="participantes.php?action=view&sorteio_id=<?php echo $sorteio['id']; ?>" 
                           class="flex-1 btn-primary text-center py-2 px-4 text-white rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Participantes
                        </a>
                        
                        <?php if ($sorteio['total_participantes'] > 0): ?>
                            <div class="flex gap-2">
                                <a href="participantes.php?action=export&sorteio_id=<?php echo $sorteio['id']; ?>&format=csv" 
                                   class="btn-secondary px-3 py-2 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                                   title="Exportar CSV">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                <a href="participantes.php?action=export&sorteio_id=<?php echo $sorteio['id']; ?>&format=excel" 
                                   class="btn-secondary px-3 py-2 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                                   title="Exportar Excel">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
/**
 * Função auxiliar para classes de badge de status
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'ativo':
            return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
        case 'pausado':
            return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
        case 'finalizado':
            return 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
        default:
            return 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
    }
}
?>