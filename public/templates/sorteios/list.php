<?php
/**
 * Template de Listagem de Sorteios
 */
?>

<!-- Cabeçalho da página -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gerenciar Sorteios</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Crie, edite e gerencie seus sorteios</p>
    </div>
    
    <a href="sorteios.php?action=new" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Novo Sorteio
    </a>
</div>

<!-- Filtros e busca avançados -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex flex-col lg:flex-row gap-4">
        <!-- Busca -->
        <div class="flex-1">
            <div class="relative">
                <input type="text" 
                       id="search-sorteios" 
                       placeholder="Buscar por nome ou descrição..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="flex flex-col sm:flex-row gap-2">
            <select id="filter-status" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="">Todos os status</option>
                <option value="ativo" <?php echo $status_filter === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                <option value="pausado" <?php echo $status_filter === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                <option value="finalizado" <?php echo $status_filter === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
            </select>
            
            <select id="sort-by" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="created_desc">Mais recentes</option>
                <option value="created_asc">Mais antigos</option>
                <option value="name_asc">Nome A-Z</option>
                <option value="name_desc">Nome Z-A</option>
                <option value="participants_desc">Mais participantes</option>
            </select>
            
            <button type="button" id="clear-filters" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg">
                Limpar
            </button>
        </div>
    </div>
    
    <!-- Ações em lote -->
    <div id="bulk-actions" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="selected-count">0</span> sorteio(s) selecionado(s)
                </span>
                
                <select id="bulk-action-select" class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Escolha uma ação</option>
                    <option value="activate">Ativar</option>
                    <option value="pause">Pausar</option>
                    <option value="finalize">Finalizar</option>
                    <option value="delete">Excluir (sem participantes)</option>
                </select>
                
                <button type="button" id="execute-bulk-action" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50" disabled>
                    Executar
                </button>
            </div>
            
            <button type="button" id="cancel-selection" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                Cancelar seleção
            </button>
        </div>
    </div>
</div>

<!-- Lista de sorteios -->
<?php if (empty($sorteios)): ?>
    <!-- Estado vazio -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum sorteio criado</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Comece criando seu primeiro sorteio para engajar sua audiência.</p>
        
        <a href="sorteios.php?action=new" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Criar Primeiro Sorteio
        </a>
    </div>
    
<?php else: ?>
    <!-- Grid de sorteios -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="sorteios-grid">
        <?php foreach ($sorteios as $sorteio): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow sorteio-card" 
                 data-status="<?php echo $sorteio['status']; ?>" 
                 data-nome="<?php echo strtolower($sorteio['nome']); ?>"
                 data-id="<?php echo $sorteio['id']; ?>">
                
                <!-- Header do card -->
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between">
                        <!-- Checkbox para seleção em lote -->
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" 
                                   class="sorteio-checkbox mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" 
                                   value="<?php echo $sorteio['id']; ?>">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php echo htmlspecialchars($sorteio['nome']); ?>
                                </h3>
                                
                                <?php if ($sorteio['descricao']): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars($sorteio['descricao']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status badge -->
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-3
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
                    
                    <!-- Métricas -->
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $sorteio['total_participantes']; ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Participantes</p>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $sorteio['qtd_sorteados']; ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sorteados</p>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $sorteio['total_sorteados']; ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Realizados</p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações adicionais -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo formatDateBR($sorteio['created_at']); ?>
                        </div>
                        
                        <?php if ($sorteio['max_participantes'] > 0): ?>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Limite: <?php echo $sorteio['max_participantes']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ações -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <!-- URL pública -->
                        <div class="flex items-center space-x-2">
                            <button type="button" class="copy-url-btn text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" 
                                    data-url="<?php echo getBaseUrl(); ?>/participar/<?php echo $sorteio['public_url']; ?>"
                                    title="Copiar URL pública">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            
                            <a href="<?php echo getBaseUrl(); ?>/participar/<?php echo $sorteio['public_url']; ?>" 
                               target="_blank" 
                               class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                               title="Abrir página pública">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Menu de ações -->
                        <div class="flex items-center space-x-2">
                            <a href="sorteios.php?action=edit&id=<?php echo $sorteio['id']; ?>" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                               title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            
                            <a href="sorteios.php?action=duplicate&id=<?php echo $sorteio['id']; ?>" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                               title="Duplicar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </a>
                            
                            <?php if ($sorteio['total_participantes'] == 0): ?>
                                <button type="button" 
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 delete-sorteio-btn"
                                        data-id="<?php echo $sorteio['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($sorteio['nome']); ?>"
                                        title="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tem certeza que deseja excluir o sorteio "<span id="delete-sorteio-nome"></span>"?
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            
            <div class="items-center px-4 py-3">
                <button id="confirm-delete" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Excluir
                </button>
                <button id="cancel-delete" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-base font-medium rounded-md w-24 hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Funcionalidades avançadas da listagem
document.addEventListener('DOMContentLoaded', function() {
    // Elementos principais
    const searchInput = document.getElementById('search-sorteios');
    const statusFilter = document.getElementById('filter-status');
    const sortBy = document.getElementById('sort-by');
    const clearFilters = document.getElementById('clear-filters');
    const sorteioCards = document.querySelectorAll('.sorteio-card');
    
    // Elementos de seleção em lote
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const executeBulkAction = document.getElementById('execute-bulk-action');
    const cancelSelection = document.getElementById('cancel-selection');
    const sorteioCheckboxes = document.querySelectorAll('.sorteio-checkbox');
    
    let selectedSorteios = new Set();
    let searchTimeout;
    
    // Busca em tempo real
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                updateUrlAndFilter();
            }, 300);
        });
    }
    
    // Filtros
    if (statusFilter) {
        statusFilter.addEventListener('change', updateUrlAndFilter);
    }
    
    if (sortBy) {
        sortBy.addEventListener('change', updateUrlAndFilter);
    }
    
    // Limpar filtros
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            if (sortBy) sortBy.value = 'created_desc';
            updateUrlAndFilter();
        });
    }
    
    // Atualizar URL e aplicar filtros
    function updateUrlAndFilter() {
        const params = new URLSearchParams();
        
        if (searchInput && searchInput.value) {
            params.set('search', searchInput.value);
        }
        
        if (statusFilter && statusFilter.value) {
            params.set('status', statusFilter.value);
        }
        
        if (sortBy && sortBy.value !== 'created_desc') {
            params.set('sort', sortBy.value);
        }
        
        // Atualizar URL sem recarregar a página
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newUrl);
        
        // Aplicar filtros localmente (para melhor UX)
        filterSorteiosLocally();
    }
    
    // Filtrar sorteios localmente
    function filterSorteiosLocally() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        
        let visibleCount = 0;
        
        sorteioCards.forEach(card => {
            const nome = card.dataset.nome || '';
            const status = card.dataset.status || '';
            
            const matchesSearch = !searchTerm || nome.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const shouldShow = matchesSearch && matchesStatus;
            
            if (shouldShow) {
                card.style.display = 'block';
                card.classList.add('fade-in');
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.classList.remove('fade-in');
            }
        });
        
        // Mostrar mensagem se nenhum resultado
        showNoResultsMessage(visibleCount === 0 && sorteioCards.length > 0);
    }
    
    // Mostrar/esconder mensagem de nenhum resultado
    function showNoResultsMessage(show) {
        let noResultsMsg = document.getElementById('no-results-message');
        const grid = document.getElementById('sorteios-grid');
        
        if (show && !noResultsMsg && grid) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'col-span-full text-center py-12';
            noResultsMsg.innerHTML = `
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-2">Nenhum sorteio encontrado com os filtros aplicados.</p>
                <button type="button" id="clear-search-filters" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    Limpar filtros
                </button>
            `;
            grid.appendChild(noResultsMsg);
            
            // Event listener para limpar filtros
            document.getElementById('clear-search-filters').addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (statusFilter) statusFilter.value = '';
                updateUrlAndFilter();
            });
        } else if (!show && noResultsMsg) {
            noResultsMsg.remove();
        }
    }
    
    // Seleção em lote
    sorteioCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sorteioId = this.value;
            
            if (this.checked) {
                selectedSorteios.add(sorteioId);
            } else {
                selectedSorteios.delete(sorteioId);
            }
            
            updateBulkActionsUI();
        });
    });
    
    // Atualizar interface de ações em lote
    function updateBulkActionsUI() {
        const count = selectedSorteios.size;
        
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (bulkActions) {
            if (count > 0) {
                bulkActions.classList.remove('hidden');
            } else {
                bulkActions.classList.add('hidden');
            }
        }
        
        if (executeBulkAction) {
            executeBulkAction.disabled = count === 0 || !bulkActionSelect?.value;
        }
    }
    
    // Habilitar/desabilitar botão de executar ação em lote
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            if (executeBulkAction) {
                executeBulkAction.disabled = selectedSorteios.size === 0 || !this.value;
            }
        });
    }
    
    // Executar ação em lote
    if (executeBulkAction) {
        executeBulkAction.addEventListener('click', function() {
            const action = bulkActionSelect?.value;
            if (!action || selectedSorteios.size === 0) return;
            
            const confirmMessage = getConfirmMessage(action, selectedSorteios.size);
            if (!confirm(confirmMessage)) return;
            
            // Mostrar loading
            this.disabled = true;
            this.textContent = 'Executando...';
            
            // Executar ação via AJAX
            executeBulkActionAjax(action, Array.from(selectedSorteios));
        });
    }
    
    // Cancelar seleção
    if (cancelSelection) {
        cancelSelection.addEventListener('click', function() {
            selectedSorteios.clear();
            sorteioCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkActionsUI();
        });
    }
    
    // Obter mensagem de confirmação para ação em lote
    function getConfirmMessage(action, count) {
        const messages = {
            'activate': `Ativar ${count} sorteio(s)?`,
            'pause': `Pausar ${count} sorteio(s)?`,
            'finalize': `Finalizar ${count} sorteio(s)?`,
            'delete': `Excluir ${count} sorteio(s)? Esta ação não pode ser desfeita.`
        };
        return messages[action] || `Executar ação em ${count} sorteio(s)?`;
    }
    
    // Executar ação em lote via AJAX
    function executeBulkActionAjax(action, sorteioIds) {
        const formData = new FormData();
        formData.append('action', 'bulk_action');
        formData.append('bulk_action', action);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        sorteioIds.forEach(id => {
            formData.append('sorteio_ids[]', id);
        });
        
        fetch('ajax/sorteios.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                // Recarregar página após sucesso
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage(data.message || 'Erro ao executar ação', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showMessage('Erro de conexão', 'error');
        })
        .finally(() => {
            // Restaurar botão
            if (executeBulkAction) {
                executeBulkAction.disabled = false;
                executeBulkAction.textContent = 'Executar';
            }
        });
    }
    
    // Mostrar mensagem de feedback
    function showMessage(message, type = 'info') {
        const alertClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 
                          type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 
                          'bg-blue-50 border-blue-200 text-blue-700';
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `fixed top-4 right-4 z-50 ${alertClass} border px-4 py-3 rounded-lg shadow-lg`;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        // Remover após 5 segundos
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
    
    // Copiar URL
    document.querySelectorAll('.copy-url-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const url = this.dataset.url;
            
            try {
                await navigator.clipboard.writeText(url);
                showCopyFeedback(this, 'URL copiada!');
            } catch (err) {
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopyFeedback(this, 'URL copiada!');
            }
        });
    });
    
    // Feedback visual ao copiar
    function showCopyFeedback(element, message) {
        const originalTitle = element.title;
        const originalClasses = element.className;
        
        element.title = message;
        element.classList.add('text-green-600', 'dark:text-green-400');
        element.classList.remove('text-blue-600', 'dark:text-blue-400');
        
        setTimeout(() => {
            element.title = originalTitle;
            element.className = originalClasses;
        }, 2000);
    }
    
    // Modal de exclusão
    const deleteModal = document.getElementById('delete-modal');
    const deleteNomeSpan = document.getElementById('delete-sorteio-nome');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    let deleteId = null;
    
    document.querySelectorAll('.delete-sorteio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteId = this.dataset.id;
            deleteNomeSpan.textContent = this.dataset.nome;
            showModal(deleteModal);
        });
    });
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            hideModal(deleteModal);
            deleteId = null;
        });
    }
    
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (deleteId) {
                this.disabled = true;
                this.textContent = 'Excluindo...';
                window.location.href = `sorteios.php?action=delete&id=${deleteId}`;
            }
        });
    }
    
    // Fechar modal clicando fora ou ESC
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                hideModal(deleteModal);
                deleteId = null;
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
                hideModal(deleteModal);
                deleteId = null;
            }
        });
    }
    
    // Utilitários para modais
    function showModal(modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Foco no modal para acessibilidade
        const firstFocusable = modal.querySelector('button, input, select, textarea');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }
    
    function hideModal(modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Aplicar filtros iniciais baseados na URL
    filterSorteiosLocally();
});

// Estilos CSS adicionais
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .sorteio-card {
        transition: all 0.2s ease-in-out;
    }
    
    .sorteio-card:hover {
        transform: translateY(-2px);
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
`;
document.head.appendChild(additionalStyles);
</script>