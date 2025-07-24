/**
 * Scripts Comuns - Sistema de Sorteios
 * Funcionalidades JavaScript compartilhadas
 */

// Configurações globais
window.SorteiosSystem = {
    baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, ''),
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    
    // Configurações de notificação
    notifications: {
        duration: 5000,
        position: 'top-right'
    },
    
    // Configurações de tema
    theme: {
        current: localStorage.getItem('theme') || 'light',
        toggle: function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                this.current = 'light';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                this.current = 'dark';
            }
            
            this.updateToggleIcons();
        },
        
        updateToggleIcons: function() {
            const darkIcons = document.querySelectorAll('#theme-toggle-dark-icon');
            const lightIcons = document.querySelectorAll('#theme-toggle-light-icon');
            
            if (this.current === 'dark') {
                darkIcons.forEach(icon => icon.classList.remove('hidden'));
                lightIcons.forEach(icon => icon.classList.add('hidden'));
            } else {
                darkIcons.forEach(icon => icon.classList.add('hidden'));
                lightIcons.forEach(icon => icon.classList.remove('hidden'));
            }
        },
        
        init: function() {
            // Aplicar tema salvo
            if (this.current === 'dark') {
                document.documentElement.classList.add('dark');
            }
            this.updateToggleIcons();
            
            // Adicionar event listeners para botões de tema
            document.querySelectorAll('#theme-toggle').forEach(button => {
                button.addEventListener('click', () => this.toggle());
            });
        }
    }
};

// Sistema de Notificações
class NotificationSystem {
    constructor() {
        this.container = this.createContainer();
        this.notifications = [];
    }
    
    createContainer() {
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }
        return container;
    }
    
    show(message, type = 'info', duration = null) {
        const notification = this.createNotification(message, type);
        this.container.appendChild(notification);
        this.notifications.push(notification);
        
        // Auto remove
        const timeout = duration || SorteiosSystem.notifications.duration;
        setTimeout(() => {
            this.remove(notification);
        }, timeout);
        
        return notification;
    }
    
    createNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-slide-in-right`;
        
        const icons = {
            success: `<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>`,
            error: `<svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                   </svg>`,
            warning: `<svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                     </svg>`,
            info: `<svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>`
        };
        
        notification.innerHTML = `
            <div class="p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        ${icons[type] || icons.info}
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${message}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="notification-close bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <span class="sr-only">Fechar</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar event listener para fechar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.remove(notification));
        
        return notification;
    }
    
    remove(notification) {
        if (notification && notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                
                const index = this.notifications.indexOf(notification);
                if (index > -1) {
                    this.notifications.splice(index, 1);
                }
            }, 300);
        }
    }
    
    clear() {
        this.notifications.forEach(notification => this.remove(notification));
    }
}

// Sistema de Loading
class LoadingSystem {
    constructor() {
        this.overlay = null;
        this.isVisible = false;
    }
    
    show(message = 'Carregando...') {
        if (this.isVisible) return;
        
        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay';
        this.overlay.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
                <div class="flex items-center space-x-4">
                    <div class="loading-spinner"></div>
                    <span class="text-gray-700 dark:text-gray-300">${message}</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.overlay);
        this.isVisible = true;
    }
    
    hide() {
        if (this.overlay && this.overlay.parentNode) {
            this.overlay.parentNode.removeChild(this.overlay);
            this.overlay = null;
            this.isVisible = false;
        }
    }
}

// Sistema de Máscaras de Input
class InputMasks {
    static cpf(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
            } else if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '$1.$2');
            }
            e.target.value = value;
        });
    }
    
    static whatsapp(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });
    }
    
    static phone(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 10) {
                if (value.length === 11) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                }
            } else if (value.length >= 6) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });
    }
    
    static date(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.replace(/(\d{2})(\d{2})(\d{0,4})/, '$1/$2/$3');
            } else if (value.length >= 2) {
                value = value.replace(/(\d{2})(\d{0,2})/, '$1/$2');
            }
            e.target.value = value;
        });
    }
    
    static time(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.replace(/(\d{2})(\d{0,2})/, '$1:$2');
            }
            e.target.value = value;
        });
    }
}

// Sistema de Validação de Formulários
class FormValidator {
    static validateCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }
        
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        
        let remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(9))) return false;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        
        return remainder === parseInt(cpf.charAt(10));
    }
    
    static validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    static validateWhatsApp(whatsapp) {
        const cleaned = whatsapp.replace(/\D/g, '');
        return cleaned.length === 11 && cleaned.startsWith('11') && cleaned.charAt(2) === '9';
    }
    
    static validateRequired(value) {
        return value && value.trim().length > 0;
    }
    
    static validateMinLength(value, min) {
        return value && value.length >= min;
    }
    
    static validateMaxLength(value, max) {
        return !value || value.length <= max;
    }
}

// Sistema de AJAX
class AjaxHelper {
    static async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Adicionar CSRF token se disponível
        if (SorteiosSystem.csrfToken) {
            defaultOptions.headers['X-CSRF-Token'] = SorteiosSystem.csrfToken;
        }
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('Ajax request failed:', error);
            throw error;
        }
    }
    
    static async get(url, params = {}) {
        const urlParams = new URLSearchParams(params);
        const fullUrl = urlParams.toString() ? `${url}?${urlParams}` : url;
        
        return this.request(fullUrl);
    }
    
    static async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    static async postForm(url, formData) {
        return this.request(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
    }
}

// Sistema de Sidebar (para área admin)
class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.overlay = document.getElementById('sidebar-overlay');
        this.toggleBtn = document.getElementById('sidebar-toggle');
        this.closeBtn = document.getElementById('sidebar-close');
        
        this.init();
    }
    
    init() {
        if (!this.sidebar) return;
        
        // Event listeners
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggle());
        }
        
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.hide());
        }
        
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.hide());
        }
        
        // Fechar sidebar ao pressionar ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isVisible()) {
                this.hide();
            }
        });
        
        // Gerenciar responsividade
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.hide();
            }
        });
    }
    
    toggle() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    show() {
        if (this.sidebar) {
            this.sidebar.classList.remove('-translate-x-full');
        }
        if (this.overlay) {
            this.overlay.classList.remove('hidden');
        }
    }
    
    hide() {
        if (this.sidebar) {
            this.sidebar.classList.add('-translate-x-full');
        }
        if (this.overlay) {
            this.overlay.classList.add('hidden');
        }
    }
    
    isVisible() {
        return this.sidebar && !this.sidebar.classList.contains('-translate-x-full');
    }
}

// Utilitários gerais
class Utils {
    static formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
    
    static formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return new Intl.DateTimeFormat('pt-BR', { ...defaultOptions, ...options }).format(new Date(date));
    }
    
    static formatNumber(number) {
        return new Intl.NumberFormat('pt-BR').format(number);
    }
    
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    static copyToClipboard(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback para navegadores mais antigos
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return Promise.resolve();
            } catch (err) {
                document.body.removeChild(textArea);
                return Promise.reject(err);
            }
        }
    }
    
    static generateId() {
        return Math.random().toString(36).substr(2, 9);
    }
    
    static scrollToTop(smooth = true) {
        window.scrollTo({
            top: 0,
            behavior: smooth ? 'smooth' : 'auto'
        });
    }
    
    static isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistemas globais
    window.notifications = new NotificationSystem();
    window.loading = new LoadingSystem();
    window.sidebar = new SidebarManager();
    
    // Inicializar tema
    SorteiosSystem.theme.init();
    
    // Aplicar máscaras automaticamente
    document.querySelectorAll('input[data-mask="cpf"]').forEach(input => {
        InputMasks.cpf(input);
    });
    
    document.querySelectorAll('input[data-mask="whatsapp"], input[data-mask="phone"]').forEach(input => {
        InputMasks.whatsapp(input);
    });
    
    document.querySelectorAll('input[data-mask="date"]').forEach(input => {
        InputMasks.date(input);
    });
    
    document.querySelectorAll('input[data-mask="time"]').forEach(input => {
        InputMasks.time(input);
    });
    
    // Adicionar funcionalidade de copiar para elementos com data-copy
    document.querySelectorAll('[data-copy]').forEach(element => {
        element.addEventListener('click', function() {
            const text = this.getAttribute('data-copy') || this.textContent;
            Utils.copyToClipboard(text).then(() => {
                notifications.show('Copiado para a área de transferência!', 'success');
            }).catch(() => {
                notifications.show('Erro ao copiar texto', 'error');
            });
        });
    });
    
    // Adicionar funcionalidade de scroll to top
    const scrollTopBtn = document.getElementById('scroll-top');
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', () => Utils.scrollToTop());
        
        // Mostrar/ocultar botão baseado na posição do scroll
        window.addEventListener('scroll', Utils.throttle(() => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.remove('hidden');
            } else {
                scrollTopBtn.classList.add('hidden');
            }
        }, 100));
    }
    
    // Adicionar confirmação para elementos com data-confirm
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    console.log('Sistema de Sorteios carregado com sucesso!');
});

// Exportar para uso global
window.SorteiosSystem.NotificationSystem = NotificationSystem;
window.SorteiosSystem.LoadingSystem = LoadingSystem;
window.SorteiosSystem.InputMasks = InputMasks;
window.SorteiosSystem.FormValidator = FormValidator;
window.SorteiosSystem.AjaxHelper = AjaxHelper;
window.SorteiosSystem.SidebarManager = SidebarManager;
window.SorteiosSystem.Utils = Utils;