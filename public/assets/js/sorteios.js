/**
 * Sistema de Sorteios - JavaScript para Gerenciamento de Sorteios
 * Funcionalidades específicas para CRUD de sorteios
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar funcionalidades baseadas na página atual
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('sorteios.php')) {
        initSorteiosPage();
    }
    
});

/**
 * Inicializa funcionalidades da página de sorteios
 */
function initSorteiosPage() {
    // Verificar se estamos na listagem ou formulário
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    if (action === 'new' || action === 'edit') {
        initSorteioForm();
    } else {
        initSorteiosList();
    }
}

/**
 * Inicializa funcionalidades da listagem de sorteios
 */
function initSorteiosList() {
    // Busca em tempo real
    const searchInput = document.getElementById('search-sorteios');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterSorteios();
            }, 300);
        });
    }
    
    // Filtro por status
    const statusFilter = document.getElementById('filter-status');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterSorteios);
    }
    
    // Botão limpar filtros
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            filterSorteios();
        });
    }
    
    // Copiar URLs públicas
    initCopyUrlButtons();
    
    // Modal de exclusão
    initDeleteModal();
    
    // Animações de hover nos cards
    initCardAnimations();
}

/**
 * Filtra sorteios baseado na busca e filtros
 */
function filterSorteios() {
    const searchTerm = document.getElementById('search-sorteios')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('filter-status')?.value || '';
    const cards = document.querySelectorAll('.sorteio-card');
    
    let visibleCount = 0;
    
    cards.forEach(card => {
        const nome = card.dataset.nome || '';
        const status = card.dataset.status || '';
        
        const matchesSearch = !searchTerm || nome.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
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
    showNoResultsMessage(visibleCount === 0 && cards.length > 0);
}

/**
 * Mostra/esconde mensagem de nenhum resultado
 */
function showNoResultsMessage(show) {
    let noResultsMsg = document.getElementById('no-results-message');
    
    if (show && !noResultsMsg) {
        const grid = document.getElementById('sorteios-grid');
        if (grid) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'col-span-full text-center py-12';
            noResultsMsg.innerHTML = `
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Nenhum sorteio encontrado com os filtros aplicados.</p>
                <button type="button" id="clear-search-filters" class="mt-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    Limpar filtros
                </button>
            `;
            grid.appendChild(noResultsMsg);
            
            // Event listener para limpar filtros
            document.getElementById('clear-search-filters').addEventListener('click', function() {
                document.getElementById('search-sorteios').value = '';
                document.getElementById('filter-status').value = '';
                filterSorteios();
            });
        }
    } else if (!show && noResultsMsg) {
        noResultsMsg.remove();
    }
}

/**
 * Inicializa botões de copiar URL
 */
function initCopyUrlButtons() {
    document.querySelectorAll('.copy-url-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
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
}

/**
 * Mostra feedback visual ao copiar
 */
function showCopyFeedback(element, message) {
    const originalTitle = element.title;
    const originalColor = element.className;
    
    element.title = message;
    element.classList.add('text-green-600', 'dark:text-green-400');
    element.classList.remove('text-blue-600', 'dark:text-blue-400');
    
    setTimeout(() => {
        element.title = originalTitle;
        element.className = originalColor;
    }, 2000);
}

/**
 * Inicializa modal de exclusão
 */
function initDeleteModal() {
    const deleteModal = document.getElementById('delete-modal');
    const deleteNomeSpan = document.getElementById('delete-sorteio-nome');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    
    if (!deleteModal) return;
    
    let deleteId = null;
    
    // Botões de exclusão
    document.querySelectorAll('.delete-sorteio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteId = this.dataset.id;
            deleteNomeSpan.textContent = this.dataset.nome;
            showModal(deleteModal);
        });
    });
    
    // Cancelar exclusão
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            hideModal(deleteModal);
            deleteId = null;
        });
    }
    
    // Confirmar exclusão
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (deleteId) {
                // Mostrar loading
                this.disabled = true;
                this.textContent = 'Excluindo...';
                
                // Redirecionar para exclusão
                window.location.href = `sorteios.php?action=delete&id=${deleteId}`;
            }
        });
    }
    
    // Fechar modal clicando fora
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            hideModal(deleteModal);
            deleteId = null;
        }
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
            hideModal(deleteModal);
            deleteId = null;
        }
    });
}

/**
 * Inicializa animações dos cards
 */
function initCardAnimations() {
    const cards = document.querySelectorAll('.sorteio-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease-in-out';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Inicializa funcionalidades do formulário de sorteio
 */
function initSorteioForm() {
    // Validação em tempo real
    initFormValidation();
    
    // Gerenciamento de campos personalizados
    initCustomFields();
    
    // Preview da URL pública
    initUrlPreview();
    
    // Auto-save (draft)
    initAutoSave();
    
    // Validação de datas
    initDateValidation();
}

/**
 * Inicializa validação do formulário
 */
function initFormValidation() {
    const form = document.getElementById('sorteio-form');
    if (!form) return;
    
    const nomeInput = document.getElementById('nome');
    const qtdSorteadosInput = document.getElementById('qtd_sorteados');
    
    // Validação do nome
    if (nomeInput) {
        nomeInput.addEventListener('blur', function() {
            validateNome(this);
        });
        
        nomeInput.addEventListener('input', function() {
            clearValidationError(this);
        });
    }
    
    // Validação da quantidade de sorteados
    if (qtdSorteadosInput) {
        qtdSorteadosInput.addEventListener('blur', function() {
            validateQtdSorteados(this);
        });
        
        qtdSorteadosInput.addEventListener('input', function() {
            clearValidationError(this);
        });
    }
    
    // Validação no submit
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
}

/**
 * Valida nome do sorteio
 */
function validateNome(input) {
    const value = input.value.trim();
    
    if (!value) {
        showValidationError(input, 'Nome do sorteio é obrigatório');
        return false;
    }
    
    if (value.length < 3) {
        showValidationError(input, 'Nome deve ter pelo menos 3 caracteres');
        return false;
    }
    
    if (value.length > 255) {
        showValidationError(input, 'Nome deve ter no máximo 255 caracteres');
        return false;
    }
    
    clearValidationError(input);
    return true;
}

/**
 * Valida quantidade de sorteados
 */
function validateQtdSorteados(input) {
    const value = parseInt(input.value);
    
    if (!value || value < 1) {
        showValidationError(input, 'Quantidade deve ser pelo menos 1');
        return false;
    }
    
    if (value > 1000) {
        showValidationError(input, 'Quantidade máxima é 1000');
        return false;
    }
    
    clearValidationError(input);
    return true;
}

/**
 * Mostra erro de validação
 */
function showValidationError(input, message) {
    clearValidationError(input);
    
    input.classList.add('border-red-500', 'focus:ring-red-500');
    input.classList.remove('border-gray-300', 'focus:ring-blue-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    input.parentNode.appendChild(errorDiv);
}

/**
 * Remove erro de validação
 */
function clearValidationError(input) {
    input.classList.remove('border-red-500', 'focus:ring-red-500');
    input.classList.add('border-gray-300', 'focus:ring-blue-500');
    
    const errorDiv = input.parentNode.querySelector('.validation-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Valida todo o formulário
 */
function validateForm() {
    const nomeInput = document.getElementById('nome');
    const qtdSorteadosInput = document.getElementById('qtd_sorteados');
    
    let isValid = true;
    
    if (nomeInput && !validateNome(nomeInput)) {
        isValid = false;
    }
    
    if (qtdSorteadosInput && !validateQtdSorteados(qtdSorteadosInput)) {
        isValid = false;
    }
    
    // Validar datas
    if (!validateDates()) {
        isValid = false;
    }
    
    return isValid;
}

/**
 * Inicializa validação de datas
 */
function initDateValidation() {
    const dataInicioInput = document.getElementById('data_inicio');
    const dataFimInput = document.getElementById('data_fim');
    
    if (dataInicioInput && dataFimInput) {
        [dataInicioInput, dataFimInput].forEach(input => {
            input.addEventListener('change', validateDates);
        });
    }
}

/**
 * Valida datas
 */
function validateDates() {
    const dataInicioInput = document.getElementById('data_inicio');
    const dataFimInput = document.getElementById('data_fim');
    
    if (!dataInicioInput || !dataFimInput) return true;
    
    const dataInicio = dataInicioInput.value;
    const dataFim = dataFimInput.value;
    
    if (dataInicio && dataFim) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        
        if (inicio >= fim) {
            showValidationError(dataFimInput, 'Data de fim deve ser posterior à data de início');
            return false;
        }
    }
    
    clearValidationError(dataInicioInput);
    clearValidationError(dataFimInput);
    return true;
}

/**
 * Inicializa campos personalizados
 */
function initCustomFields() {
    // Já implementado no template PHP, mas podemos adicionar funcionalidades extras aqui
    
    // Drag and drop para reordenar campos (funcionalidade futura)
    // Auto-complete para nomes de campos comuns
    // Validação de nomes duplicados
}

/**
 * Inicializa preview da URL pública
 */
function initUrlPreview() {
    const nomeInput = document.getElementById('nome');
    if (!nomeInput) return;
    
    // Para novos sorteios, mostrar preview da URL baseado no nome
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        nomeInput.addEventListener('input', function() {
            // Implementar preview da URL (funcionalidade futura)
        });
    }
}

/**
 * Inicializa auto-save
 */
function initAutoSave() {
    // Implementar auto-save em localStorage para não perder dados (funcionalidade futura)
    const form = document.getElementById('sorteio-form');
    if (!form) return;
    
    // Salvar dados a cada 30 segundos
    setInterval(() => {
        saveFormDraft();
    }, 30000);
    
    // Restaurar dados ao carregar a página
    restoreFormDraft();
}

/**
 * Salva rascunho do formulário
 */
function saveFormDraft() {
    const form = document.getElementById('sorteio-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem('sorteio_draft', JSON.stringify(data));
}

/**
 * Restaura rascunho do formulário
 */
function restoreFormDraft() {
    const draft = localStorage.getItem('sorteio_draft');
    if (!draft) return;
    
    try {
        const data = JSON.parse(draft);
        const form = document.getElementById('sorteio-form');
        
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && !input.value) {
                input.value = data[key];
            }
        });
    } catch (e) {
        console.error('Erro ao restaurar rascunho:', e);
    }
}

/**
 * Utilitários para modais
 */
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

/**
 * Utilitários de animação
 */
function fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';
    
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.min(progress / duration, 1);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

function fadeOut(element, duration = 300) {
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.max(1 - (progress / duration), 0);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
        }
    }
    
    requestAnimationFrame(animate);
}

// Adicionar estilos CSS dinâmicos
const style = document.createElement('style');
style.textContent = `
    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card-hover {
        transition: all 0.2s ease-in-out;
    }
    
    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .validation-error {
        animation: shake 0.3s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);