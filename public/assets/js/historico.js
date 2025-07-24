/**
 * Sistema de Histórico - JavaScript
 * Funcionalidades interativas para a página de histórico
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar funcionalidades
    initializeSearch();
    initializeFilters();
    initializeCopyButtons();
    initializeTooltips();
    
    /**
     * Inicializar busca em tempo real
     */
    function initializeSearch() {
        const searchInput = document.getElementById('search');
        if (!searchInput) return;
        
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                
                if (searchTerm.length >= 2 || searchTerm.length === 0) {
                    // Atualizar URL com parâmetro de busca
                    const url = new URL(window.location);
                    if (searchTerm) {
                        url.searchParams.set('search', searchTerm);
                    } else {
                        url.searchParams.delete('search');
                    }
                    url.searchParams.delete('page'); // Reset página
                    
                    // Recarregar página com nova busca
                    window.location.href = url.toString();
                }
            }, 500);
        });
    }
    
    /**
     * Inicializar filtros automáticos
     */
    function initializeFilters() {
        const dateFilter = document.getElementById('date');
        const statusFilter = document.getElementById('status');
        
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                updateFilters();
            });
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                updateFilters();
            });
        }
    }
    
    /**
     * Atualizar filtros na URL
     */
    function updateFilters() {
        const url = new URL(window.location);
        const dateFilter = document.getElementById('date');
        const statusFilter = document.getElementById('status');
        
        // Atualizar parâmetros
        if (dateFilter && dateFilter.value) {
            url.searchParams.set('date', dateFilter.value);
        } else {
            url.searchParams.delete('date');
        }
        
        if (statusFilter && statusFilter.value) {
            url.searchParams.set('status', statusFilter.value);
        } else {
            url.searchParams.delete('status');
        }
        
        // Reset página
        url.searchParams.delete('page');
        
        // Recarregar página
        window.location.href = url.toString();
    }
    
    /**
     * Inicializar botões de copiar
     */
    function initializeCopyButtons() {
        // Função global para copiar URL pública
        window.copyPublicUrl = function(url) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showCopyFeedback(event.target, 'URL copiada!');
                }).catch(function(err) {
                    console.error('Erro ao copiar URL: ', err);
                    fallbackCopyTextToClipboard(url);
                });
            } else {
                fallbackCopyTextToClipboard(url);
            }
        };
        
        // Fallback para navegadores sem clipboard API
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            
            // Evitar scroll para o bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyFeedback(event.target, 'URL copiada!');
                } else {
                    showCopyFeedback(event.target, 'Erro ao copiar', true);
                }
            } catch (err) {
                console.error('Fallback: Erro ao copiar', err);
                showCopyFeedback(event.target, 'Erro ao copiar', true);
            }
            
            document.body.removeChild(textArea);
        }
        
        // Mostrar feedback visual
        function showCopyFeedback(button, message, isError = false) {
            const originalText = button.innerHTML;
            const originalClasses = button.className;
            
            // Atualizar botão
            button.innerHTML = `<svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isError ? 'M6 18L18 6M6 6l12 12' : 'M5 13l4 4L19 7'}"></path>
            </svg>${message}`;
            
            if (isError) {
                button.className = button.className.replace(/bg-gray-\d+/g, 'bg-red-100')
                                                 .replace(/dark:bg-gray-\d+/g, 'dark:bg-red-900')
                                                 .replace(/text-gray-\d+/g, 'text-red-700')
                                                 .replace(/dark:text-gray-\d+/g, 'dark:text-red-200');
            } else {
                button.className = button.className.replace(/bg-gray-\d+/g, 'bg-green-100')
                                                 .replace(/dark:bg-gray-\d+/g, 'dark:bg-green-900')
                                                 .replace(/text-gray-\d+/g, 'text-green-700')
                                                 .replace(/dark:text-gray-\d+/g, 'dark:text-green-200');
            }
            
            // Restaurar após 2 segundos
            setTimeout(function() {
                button.innerHTML = originalText;
                button.className = originalClasses;
            }, 2000);
        }
    }
    
    /**
     * Inicializar tooltips
     */
    function initializeTooltips() {
        // Adicionar tooltips para ações
        const actionButtons = document.querySelectorAll('[title]');
        
        actionButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                showTooltip(this, this.getAttribute('title'));
            });
            
            button.addEventListener('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    /**
     * Mostrar tooltip
     */
    function showTooltip(element, text) {
        // Remover tooltip existente
        hideTooltip();
        
        const tooltip = document.createElement('div');
        tooltip.id = 'custom-tooltip';
        tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg pointer-events-none';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        // Posicionar tooltip
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        tooltip.style.left = (rect.left + rect.width / 2 - tooltipRect.width / 2) + 'px';
        tooltip.style.top = (rect.top - tooltipRect.height - 5) + 'px';
    }
    
    /**
     * Esconder tooltip
     */
    function hideTooltip() {
        const tooltip = document.getElementById('custom-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }
    
    /**
     * Animações de entrada
     */
    function initializeAnimations() {
        // Animar cards de estatísticas
        const statsCards = document.querySelectorAll('.grid > div');
        
        statsCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Animar timeline
        const timelineItems = document.querySelectorAll('.flow-root li');
        
        timelineItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 500 + (index * 150));
        });
    }
    
    // Inicializar animações após carregamento
    setTimeout(initializeAnimations, 100);
    
    /**
     * Confirmar remoção de resultado
     */
    window.confirmRemoveResult = function(resultadoId, sorteioId) {
        if (confirm('Tem certeza que deseja remover este resultado de sorteio? Esta ação não pode ser desfeita.')) {
            window.location.href = `historico.php?action=remove_result&id=${sorteioId}&resultado_id=${resultadoId}`;
        }
    };
    
    /**
     * Atualizar dados em tempo real (se necessário)
     */
    function initializeRealTimeUpdates() {
        // Verificar se há atualizações a cada 30 segundos (apenas na página de detalhes)
        if (window.location.search.includes('action=details')) {
            setInterval(function() {
                // Aqui poderia implementar uma verificação AJAX para atualizações
                // Por enquanto, apenas log para debug
                console.log('Verificando atualizações...');
            }, 30000);
        }
    }
    
    // Inicializar atualizações em tempo real
    initializeRealTimeUpdates();
    
    /**
     * Melhorar experiência de navegação
     */
    function initializeNavigation() {
        // Adicionar loading state nos links de navegação
        const navLinks = document.querySelectorAll('a[href*="historico.php"], a[href*="sorteios.php"]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Não aplicar loading para links que abrem em nova aba
                if (this.target === '_blank') return;
                
                // Adicionar indicador de loading
                const originalText = this.innerHTML;
                this.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current inline" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>Carregando...`;
                
                this.style.pointerEvents = 'none';
                
                // Restaurar se a navegação falhar
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 5000);
            });
        });
    }
    
    // Inicializar melhorias de navegação
    initializeNavigation();
    
});

/**
 * Utilitários globais
 */

// Formatar números
window.formatNumber = function(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
};

// Formatar datas
window.formatDate = function(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Debounce function
window.debounce = function(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};