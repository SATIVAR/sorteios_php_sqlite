<?php
/**
 * Template: Visualização de Participantes do Sorteio
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$campos_config = json_decode($sorteio['campos_config'], true) ?? [];
?>

<!-- Cabeçalho da página -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="participantes.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Participantes
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($sorteio['nome']); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Participantes: <?php echo htmlspecialchars($sorteio['nome']); ?>
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Gerencie os participantes deste sorteio
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">
            <a href="participantes.php?action=add&sorteio_id=<?php echo $sorteio['id']; ?>" 
               class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Adicionar Participante
            </a>
        </div>
    </div>
</div>

<!-- Informações do sorteio -->
<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                <?php echo $total_participantes; ?>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total de Participantes</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                <?php echo $sorteio['qtd_sorteados']; ?>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Quantidade a Sortear</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                <?php echo $sorteio['max_participantes'] > 0 ? $sorteio['max_participantes'] : '∞'; ?>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Limite de Participantes</div>
        </div>
        <div class="text-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                <?php echo getStatusBadgeClass($sorteio['status']); ?>">
                <?php echo ucfirst($sorteio['status']); ?>
            </span>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status do Sorteio</div>
        </div>
    </div>
    
    <!-- URL Pública -->
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            URL Pública para Participação:
        </label>
        <div class="flex">
            <input type="text" 
                   id="public-url" 
                   value="<?php echo getBaseUrl() . '/participar/' . $sorteio['public_url']; ?>" 
                   readonly 
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <button type="button" 
                    onclick="copyToClipboard('public-url')" 
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-r-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Filtros e busca -->
<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="hidden" name="action" value="view">
        <input type="hidden" name="sorteio_id" value="<?php echo $sorteio['id']; ?>">
        
        <div class="flex-1">
            <label for="search" class="sr-only">Buscar participantes</label>
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
                       placeholder="Buscar por nome, WhatsApp, CPF ou email...">
            </div>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Buscar
            </button>
            
            <?php if (!empty($search)): ?>
                <a href="participantes.php?action=view&sorteio_id=<?php echo $sorteio['id']; ?>" 
                   class="btn-secondary px-6 py-2 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                    Limpar
                </a>
            <?php endif; ?>
            
            <?php if (!empty($participantes)): ?>
                <div class="flex gap-1">
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
    </form>
</div>

<!-- Lista de participantes -->
<?php if (empty($participantes)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            <?php echo !empty($search) ? 'Nenhum participante encontrado' : 'Nenhum participante cadastrado ainda'; ?>
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
            <?php echo !empty($search) ? 'Tente ajustar os filtros de busca.' : 'Compartilhe a URL pública ou adicione participantes manualmente.'; ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="participantes.php?action=add&sorteio_id=<?php echo $sorteio['id']; ?>" 
               class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Adicionar Participante
            </a>
            <button type="button" 
                    onclick="copyToClipboard('public-url')" 
                    class="btn-secondary inline-flex items-center px-4 py-2 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Copiar URL Pública
            </button>
        </div>
    </div>
<?php else: ?>
    <!-- Tabela de participantes -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Participante
                        </th>
                        <?php if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                WhatsApp
                            </th>
                        <?php endif; ?>
                        <?php if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                CPF
                            </th>
                        <?php endif; ?>
                        <?php if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Email
                            </th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Data Cadastro
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($participantes as $participante): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                            <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                                <?php echo strtoupper(substr($participante['nome'], 0, 2)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($participante['nome']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            ID: <?php echo $participante['id']; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <?php if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo $participante['whatsapp'] ? htmlspecialchars($participante['whatsapp']) : '-'; ?>
                                </td>
                            <?php endif; ?>
                            
                            <?php if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo $participante['cpf'] ? formatCPF($participante['cpf']) : '-'; ?>
                                </td>
                            <?php endif; ?>
                            
                            <?php if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo $participante['email'] ? htmlspecialchars($participante['email']) : '-'; ?>
                                </td>
                            <?php endif; ?>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($participante['foi_sorteado']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Sorteado (<?php echo $participante['posicao_sorteio']; ?>º)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        Participando
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo formatDateBR($participante['created_at']); ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if (!$participante['foi_sorteado']): ?>
                                    <button type="button" 
                                            onclick="confirmDelete(<?php echo $participante['id']; ?>, '<?php echo htmlspecialchars($participante['nome'], ENT_QUOTES); ?>')"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500" title="Não é possível remover participante sorteado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-2">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tem certeza que deseja remover o participante <strong id="delete-participant-name"></strong>?
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirm-delete" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Excluir
                </button>
                <button id="cancel-delete" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let participantToDelete = null;

function confirmDelete(participantId, participantName) {
    participantToDelete = participantId;
    document.getElementById('delete-participant-name').textContent = participantName;
    document.getElementById('delete-modal').classList.remove('hidden');
}

document.getElementById('confirm-delete').addEventListener('click', function() {
    if (participantToDelete) {
        window.location.href = `participantes.php?action=delete&sorteio_id=<?php echo $sorteio['id']; ?>&participante_id=${participantToDelete}`;
    }
});

document.getElementById('cancel-delete').addEventListener('click', function() {
    document.getElementById('delete-modal').classList.add('hidden');
    participantToDelete = null;
});

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Feedback visual
    showNotification('URL copiada para a área de transferência!', 'success');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

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