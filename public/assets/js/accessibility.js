/**
 * Scripts de Acessibilidade - Sistema de Sorteios
 * Implementa melhorias de acessibilidade para navegação por teclado e leitores de tela
 */

class AccessibilityManager {
    constructor() {
        this.focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        this.init();
    }
    
    init() {
        this.setupKeyboardNavigation();
        this.setupAriaAttributes();
        this.setupFocusTrap();
        this.setupSkipLink();
    }
    
    setupKeyboardNavigation() {
        // Adicionar suporte para navegação por teclado em elementos interativos
        document.querySelectorAll('.interactive-element').forEach(element => {
            element.addEventListener('keydown', (e) => {
                // Ativar elemento com Enter ou Space
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    element.click();
                }
            });
        });
        
        // Melhorar navegação por teclado em menus dropdown
        document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
            button.addEventListener('keydown', (e) => {
                const targetId = button.getAttribute('data-dropdown-toggle');
                const target = document.getElementById(targetId);
                
                if (!target) return;
                
                // Abrir dropdown com Enter ou Space
                if ((e.key === 'Enter' || e.key === ' ') && !target.classList.contains('hidden')) {
                    e.preventDefault();
                    
                    // Focar no primeiro item do dropdown
                    const firstItem = target.querySelector(this.focusableElements);
                    if (firstItem) {
                        firstItem.focus();
                    }
                }
                
                // Fechar dropdown com Escape
                if (e.key === 'Escape' && !target.classList.contains('hidden')) {
                    e.preventDefault();
                    button.click(); // Fechar dropdown
                    button.focus(); // Retornar foco ao botão
                }
            });
        });
    }
    
    setupAriaAttributes() {
        // Atualizar atributos ARIA para sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                const expanded = sidebar.classList.contains('-translate-x-full') ? 'false' : 'true';
                sidebarToggle.setAttribute('aria-expanded', expanded);
            });
        }
        
        // Atualizar atributos ARIA para dropdowns
        document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
            button.setAttribute('aria-haspopup', 'true');
            button.setAttribute('aria-expanded', 'false');
            
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-dropdown-toggle');
                const target = document.getElementById(targetId);
                
                if (target) {
                    const isHidden = target.classList.contains('hidden');
                    button.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
                }
            });
        });
        
        // Adicionar roles e labels para elementos comuns
        document.querySelectorAll('table').forEach(table => {
            if (!table.hasAttribute('role')) {
                table.setAttribute('role', 'table');
            }
            
            if (!table.hasAttribute('aria-label') && !table.hasAttribute('aria-labelledby')) {
                const caption = table.querySelector('caption');
                if (caption) {
                    const id = 'table-caption-' + Math.random().toString(36).substr(2, 9);
                    caption.id = id;
                    table.setAttribute('aria-labelledby', id);
                } else {
                    table.setAttribute('aria-label', 'Tabela de dados');
                }
            }
        });
    }
    
    setupFocusTrap() {
        // Implementar trap de foco para modais
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;
                
                const focusableElements = modal.querySelectorAll(this.focusableElements);
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                // Se pressionar Shift+Tab no primeiro elemento, focar no último
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
                
                // Se pressionar Tab no último elemento, focar no primeiro
                else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            });
        });
    }
    
    setupSkipLink() {
        // Configurar link de "pular para o conteúdo"
        const skipLink = document.querySelector('.skip-link');
        if (skipLink) {
            skipLink.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(skipLink.getAttribute('href'));
                if (target) {
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                    
                    // Remover tabindex após o foco para não afetar a navegação normal
                    setTimeout(() => {
                        target.removeAttribute('tabindex');
                    }, 100);
                }
            });
        }
    }
    
    // Método para anunciar mensagens para leitores de tela
    announceToScreenReader(message) {
        let announcer = document.getElementById('sr-announcer');
        
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'sr-announcer';
            announcer.className = 'sr-only';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
        
        // Limpar e definir a mensagem
        announcer.textContent = '';
        setTimeout(() => {
            announcer.textContent = message;
        }, 100);
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.accessibilityManager = new AccessibilityManager();
    
    // Anunciar carregamento da página para leitores de tela
    const pageTitle = document.title;
    window.accessibilityManager.announceToScreenReader('Página ' + pageTitle + ' carregada');
});