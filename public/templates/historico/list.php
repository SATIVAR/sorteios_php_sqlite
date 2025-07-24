<?php
/**
 * Template de Listagem do Histórico de Sorteios
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}
?>

<!-- Cabeçalho da página -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Visualize o histórico completo de sorteios realizados
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="sorteios.php?action=new" class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Novo Sorteio
            </a>
        </div>
    </div>
</div>

<!-- Filtros e busca -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <form method="GET" action="historico.php" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
        
        <!-- Campo de busca -->
        <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Buscar sorteios
            </label>
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
                       placeholder="Nome ou descrição do sorteio..."
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
        </div>
        
        <!-- Filtro de data -->
        <div>
            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Período
            </label>
            <select id="date" 
                    name="date" 
                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Todos os períodos</option>
                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Hoje</option>
                <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Última semana</option>
                <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Último mês</option>
                <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>Último ano</option>
            </select>
        </div>
        
        <!-- Filtro de status -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Status
            </label>
            <select id="status" 
                    name="status" 
                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Todos os status</option>
                <option value="ativo" <?php echo $status_filter === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                <option value="pausado" <?php echo $status_filter === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                <option value="finalizado" <?php echo $status_filter === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
            </select>
        </div>
        
        <!-- Botões -->
        <div class="flex space-x-2">
            <button type="submit" class="btn-primary px-4 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Filtrar
            </button>
            
            <?php if ($search || $date_filter || $status_filter): ?>
                <a href="historico.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Limpar
                </a>
            <?php endif; ?>
        </div>
        
    </form>
</div>

<!-- Estatísticas rápidas -->
<?php if (!empty($sorteios_realizados)): ?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <?php
    $total_participantes_geral = array_sum(array_column($sorteios_realizados, 'total_participantes'));
    $total_sorteados_geral = array_sum(array_column($sorteios_realizados, 'total_sorteados'));
    $total_execucoes_geral = array_sum(array_column($sorteios_realizados, 'total_execucoes'));
    ?>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteios</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo count($sorteios_realizados); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Participantes</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($total_participantes_geral); ?></p>
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
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Execuções</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $total_execucoes_geral; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sorteados</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $total_sorteados_geral; ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Lista de sorteios -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    
    <?php if (empty($sorteios_realizados)): ?>
        <!-- Estado vazio -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Nenhum sorteio encontrado</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                <?php if ($search || $date_filter || $status_filter): ?>
                    Tente ajustar os filtros ou criar um novo sorteio.
                <?php else: ?>
                    Você ainda não realizou nenhum sorteio. Que tal criar o primeiro?
                <?php endif; ?>
            </p>
            <div class="mt-6">
                <a href="sorteios.php?action=new" class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Criar Primeiro Sorteio
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Cabeçalho da tabela -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Histórico de Sorteios
                <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                    (<?php echo $total_sorteios; ?> <?php echo $total_sorteios === 1 ? 'sorteio' : 'sorteios'; ?>)
                </span>
            </h3>
        </div>
        
        <!-- Tabela -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Sorteio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Participantes
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Execuções
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Último Sorteio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($sorteios_realizados as $sorteio): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($sorteio['nome']); ?>
                                    </div>
                                    <?php if ($sorteio['descricao']): ?>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            <?php echo htmlspecialchars($sorteio['descricao']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        Criado em <?php echo formatDateBR($sorteio['created_at']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <span class="font-medium"><?php echo number_format($sorteio['total_participantes']); ?></span>
                                    <span class="text-gray-500 dark:text-gray-400">participantes</span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-medium text-green-600 dark:text-green-400"><?php echo $sorteio['total_sorteados']; ?></span>
                                    sorteados
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <span class="font-medium"><?php echo $sorteio['total_execucoes']; ?></span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <?php echo $sorteio['total_execucoes'] === 1 ? 'execução' : 'execuções'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($sorteio['ultimo_sorteio']): ?>
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo formatDateBR($sorteio['ultimo_sorteio']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo formatTimeAgo($sorteio['ultimo_sorteio']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="historico.php?action=details&id=<?php echo $sorteio['id']; ?>" 
                                       class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 transition-colors"
                                       title="Ver detalhes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    
                                    <a href="sorteios.php?action=edit&id=<?php echo $sorteio['id']; ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors"
                                       title="Editar sorteio">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando <?php echo (($page - 1) * $per_page) + 1; ?> a <?php echo min($page * $per_page, $total_sorteios); ?> 
                        de <?php echo $total_sorteios; ?> sorteios
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 text-sm <?php echo $i === $page ? 'bg-primary-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> rounded-lg transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Próxima
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
    
</div>