/**
 * Sistema de Sorteios - Integração em Tempo Real
 * Gerencia atualizações em tempo real via AJAX
 */

class RealtimeIntegration {
    constructor(options = {}) {
        this.options = {
            baseUrl: '',
            updateInterval: 30000, // 30 segundos
            notificationsInterval: 60000, // 1 minuto
            enablePolling: true,
            enableSSE: false,
            csrfToken: null,
            ...options
        };
        
        this.endpoints = {
            notifications: 'ajax/notifications_realtime.php',
            metrics: 'ajax/metrics_realtime.php',
            data: 'ajax/data_realtime.php',
            realtime: 'ajax/realtime.php'
        };
        
        this.updateTimers = {};
        this.eventSource = null;
        this.lastUpdateTimestamp = Date.now();
        this.callbacks = {
            onNotification: null,
            onMetricsUpdate: null,
            onDataUpdate: null,
            onError: null
        };
        
        this.init();
    }
    
    /**
     * Inicializa o sistema
     */
    init() {
        this.setupCSRFToken();
        
        if (this.options.enablePolling) {
            this.startPolling();
        }
        
        if (this.options.enableSSE) {
            this.connectSSE();
        }
    }
    
    /**
     * Configura token CSRF
     */
    setupCSRFToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            this.options.csrfToken = tokenMeta.getAttribute('content');
        }
    }
    
    /**
     * Inicia polling para atualizações
     */
    startPolling() {
        // Polling para notificações
        this.updateTimers.notifications = setInterval(() => {
            this.fetchNotifications();
        }, this.options.notificationsInterval);
        
        // Polling para métricas
        this.updateTimers.metrics = setInterval(() => {
            this.fetchMetrics();
        }, this.options.updateInterval);
        
        // Primeira atualização imediata
        setTimeout(() => {
            this.fetchNotifications();
            this.fetchMetrics();
        }, 1000);
    }
    
    /**
     * Para polling
     */
    stopPolling() {
        Object.values(this.updateTimers).forEach(timer => {
            clearInterval(timer);
        });
        
        this.updateTimers = {};
    }
    
    /**
     * Conecta ao Server-Sent Events
     */
    connectSSE() {
        if (!window.EventSource) {
            console.warn('Este navegador não suporta Server-Sent Events. Usando polling como fallback.');
            return;
        }
        
        try {
            this.eventSource = new EventSource(`${this.options.baseUrl}${this.endpoints.realtime}?action=stream`);
            
            this.eventSource.addEventListener('open', () => {
                console.log('Conexão SSE estabelecida');
            });
            
            this.eventSource.addEventListener('error', (e) => {
                console.error('Erro na conexão SSE:', e);
                this.eventSource.close();
                
                // Tentar reconectar após 10 segundos
                setTimeout(() => {
                    this.connectSSE();
                }, 10000);
            });
            
            this.eventSource.addEventListener('notification', (e) => {
                const data = JSON.parse(e.data);
                this.handleNotificationUpdate(data);
            });
            
            this.eventSource.addEventListener('update', (e) => {
                const data = JSON.parse(e.data);
                this.handleDataUpdate(data);
            });
            
            this.eventSource.addEventListener('heartbeat', () => {
                // Heartbeat recebido, conexão ativa
            });
            
        } catch (error) {
            console.error('Erro ao conectar SSE:', error);
            
            // Fallback para polling
            if (!this.options.enablePolling) {
                this.options.enablePolling = true;
                this.startPolling();
            }
        }
    }
    
    /**
     * Desconecta do Server-Sent Events
     */
    disconnectSSE() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
    
    /**
     * Busca notificações não lidas
     */
    async fetchNotifications() {
        try {
            const response = await this.makeRequest(
                this.endpoints.notifications,
                { action: 'get_unread' },
                'GET'
            );
            
            if (response.success) {
                this.handleNotificationUpdate(response.data);
            }
        } catch (error) {
            this.handleError('Erro ao buscar notificações', error);
        }
    }
    
    /**
     * Busca métricas atualizadas
     */
    async fetchMetrics() {
        try {
            const response = await this.makeRequest(
                this.endpoints.metrics,
                { action: 'get_dashboard_metrics' },
                'GET'
            );
            
            if (response.success) {
                this.handleMetricsUpdate(response.data.metrics);
            }
        } catch (error) {
            this.handleError('Erro ao buscar métricas', error);
        }
    }
    
    /**
     * Busca dados para gráficos
     */
    async fetchChartData(type = 'participation', period = 30, sorteioId = null) {
        try {
            const params = {
                action: 'get_chart_data',
                type: type,
                period: period
            };
            
            if (sorteioId) {
                params.sorteio_id = sorteioId;
            }
            
            const response = await this.makeRequest(
                this.endpoints.metrics,
                params,
                'GET'
            );
            
            if (response.success) {
                return response.data.chart_data;
            }
            
            return null;
        } catch (error) {
            this.handleError('Erro ao buscar dados do gráfico', error);
            return null;
        }
    }
    
    /**
     * Busca sorteios
     */
    async fetchSorteios(status = '', limit = 20, offset = 0) {
        try {
            const response = await this.makeRequest(
                this.endpoints.data,
                {
                    action: 'get_sorteios',
                    status: status,
                    limit: limit,
                    offset: offset
                },
                'GET'
            );
            
            if (response.success) {
                return response.data.sorteios;
            }
            
            return [];
        } catch (error) {
            this.handleError('Erro ao buscar sorteios', error);
            return [];
        }
    }
    
    /**
     * Busca participantes
     */
    async fetchParticipantes(sorteioId, limit = 20, offset = 0) {
        try {
            const response = await this.makeRequest(
                this.endpoints.data,
                {
                    action: 'get_participantes',
                    sorteio_id: sorteioId,
                    limit: limit,
                    offset: offset
                },
                'GET'
            );
            
            if (response.success) {
                return response.data.participantes;
            }
            
            return [];
        } catch (error) {
            this.handleError('Erro ao buscar participantes', error);
            return [];
        }
    }
    
    /**
     * Busca resultados
     */
    async fetchResultados(sorteioId, limit = 20, offset = 0) {
        try {
            const response = await this.makeRequest(
                this.endpoints.data,
                {
                    action: 'get_resultados',
                    sorteio_id: sorteioId,
                    limit: limit,
                    offset: offset
                },
                'GET'
            );
            
            if (response.success) {
                return response.data.resultados;
            }
            
            return [];
        } catch (error) {
            this.handleError('Erro ao buscar resultados', error);
            return [];
        }
    }
    
    /**
     * Busca atividades recentes
     */
    async fetchRecentActivity(limit = 10) {
        try {
            const response = await this.makeRequest(
                this.endpoints.data,
                {
                    action: 'get_recent_activity',
                    limit: limit
                },
                'GET'
            );
            
            if (response.success) {
                return response.data.activity;
            }
            
            return [];
        } catch (error) {
            this.handleError('Erro ao buscar atividades recentes', error);
            return [];
        }
    }
    
    /**
     * Busca alertas do sistema
     */
    async fetchAlerts() {
        try {
            const response = await this.makeRequest(
                this.endpoints.notifications,
                { action: 'get_alerts' },
                'GET'
            );
            
            if (response.success) {
                return response.data.alerts;
            }
            
            return [];
        } catch (error) {
            this.handleError('Erro ao buscar alertas', error);
            return [];
        }
    }
    
    /**
     * Marca notificação como lida
     */
    async markNotificationAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            formData.append('csrf_token', this.options.csrfToken);
            
            const response = await this.makeRequest(
                this.endpoints.notifications,
                formData,
                'POST',
                true
            );
            
            return response.success;
        } catch (error) {
            this.handleError('Erro ao marcar notificação como lida', error);
            return false;
        }
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    async markAllNotificationsAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            formData.append('csrf_token', this.options.csrfToken);
            
            const response = await this.makeRequest(
                this.endpoints.notifications,
                formData,
                'POST',
                true
            );
            
            return response.success;
        } catch (error) {
            this.handleError('Erro ao marcar todas as notificações como lidas', error);
            return false;
        }
    }
    
    /**
     * Manipuladores de eventos
     */
    handleNotificationUpdate(data) {
        this.lastUpdateTimestamp = Date.now();
        
        if (this.callbacks.onNotification) {
            this.callbacks.onNotification(data);
        }
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent('realtime:notifications', {
            detail: data
        }));
    }
    
    handleMetricsUpdate(metrics) {
        this.lastUpdateTimestamp = Date.now();
        
        if (this.callbacks.onMetricsUpdate) {
            this.callbacks.onMetricsUpdate(metrics);
        }
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent('realtime:metrics', {
            detail: metrics
        }));
    }
    
    handleDataUpdate(data) {
        this.lastUpdateTimestamp = Date.now();
        
        if (this.callbacks.onDataUpdate) {
            this.callbacks.onDataUpdate(data);
        }
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent('realtime:data', {
            detail: data
        }));
    }
    
    handleError(message, error) {
        console.error(message, error);
        
        if (this.callbacks.onError) {
            this.callbacks.onError(message, error);
        }
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent('realtime:error', {
            detail: { message, error }
        }));
    }
    
    /**
     * Registra callbacks
     */
    onNotification(callback) {
        this.callbacks.onNotification = callback;
        return this;
    }
    
    onMetricsUpdate(callback) {
        this.callbacks.onMetricsUpdate = callback;
        return this;
    }
    
    onDataUpdate(callback) {
        this.callbacks.onDataUpdate = callback;
        return this;
    }
    
    onError(callback) {
        this.callbacks.onError = callback;
        return this;
    }
    
    /**
     * Faz requisição AJAX
     */
    async makeRequest(endpoint, data, method = 'GET', isFormData = false) {
        const url = `${this.options.baseUrl}${endpoint}`;
        
        const options = {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (method === 'GET' && !isFormData) {
            // Adicionar parâmetros à URL
            const params = new URLSearchParams(data);
            const fullUrl = `${url}?${params.toString()}`;
            
            const response = await fetch(fullUrl, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } else {
            // POST com FormData ou JSON
            if (!isFormData) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
                
                if (this.options.csrfToken) {
                    options.headers['X-CSRF-Token'] = this.options.csrfToken;
                }
            } else {
                options.body = data;
            }
            
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        }
    }
    
    /**
     * Verifica se as atualizações estão ativas
     */
    isActive() {
        return Object.keys(this.updateTimers).length > 0 || this.eventSource !== null;
    }
    
    /**
     * Obtém tempo desde a última atualização
     */
    getTimeSinceLastUpdate() {
        return Date.now() - this.lastUpdateTimestamp;
    }
    
    /**
     * Destrói a instância e limpa recursos
     */
    destroy() {
        this.stopPolling();
        this.disconnectSSE();
        
        this.callbacks = {
            onNotification: null,
            onMetricsUpdate: null,
            onDataUpdate: null,
            onError: null
        };
    }
}

// Inicialização global
window.RealtimeIntegration = RealtimeIntegration;

// Auto-inicialização se estiver na página de admin
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#dashboard-metrics') || document.querySelector('#notifications-container')) {
        window.realtimeSystem = new RealtimeIntegration({
            updateInterval: 30000,
            notificationsInterval: 60000,
            enablePolling: true
        });
        
        // Configurar handlers para atualizar a interface
        window.realtimeSystem.onNotification(function(data) {
            updateNotificationsUI(data);
        });
        
        window.realtimeSystem.onMetricsUpdate(function(metrics) {
            updateMetricsUI(metrics);
        });
    }
});

/**
 * Atualiza interface de notificações
 */
function updateNotificationsUI(data) {
    const container = document.getElementById('notifications-container');
    if (!container) return;
    
    const notificationsList = document.getElementById('notifications-list');
    if (!notificationsList) return;
    
    const notificationCount = document.getElementById('notification-count');
    if (notificationCount) {
        notificationCount.textContent = data.count;
        notificationCount.style.display = data.count > 0 ? 'inline-flex' : 'none';
    }
    
    // Atualizar lista de notificações
    if (data.notifications && data.notifications.length > 0) {
        // Limpar notificações existentes
        while (notificationsList.firstChild) {
            notificationsList.removeChild(notificationsList.firstChild);
        }
        
        // Adicionar novas notificações
        data.notifications.forEach(notification => {
            const notificationItem = document.createElement('div');
            notificationItem.className = `notification-item p-4 border-b border-gray-200 dark:border-gray-700 ${notification.read ? 'opacity-70' : ''}`;
            notificationItem.dataset.id = notification.id;
            
            const typeClass = {
                'info': 'text-blue-500 dark:text-blue-400',
                'success': 'text-green-500 dark:text-green-400',
                'warning': 'text-yellow-500 dark:text-yellow-400',
                'error': 'text-red-500 dark:text-red-400'
            }[notification.type] || 'text-blue-500 dark:text-blue-400';
            
            notificationItem.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0 ${typeClass}">
                        ${getNotificationIcon(notification.type)}
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${notification.title}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ${notification.message}
                        </p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            ${notification.time_ago}
                        </p>
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <button type="button" class="mark-read-btn inline-flex text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            notificationsList.appendChild(notificationItem);
            
            // Adicionar evento para marcar como lida
            const markReadBtn = notificationItem.querySelector('.mark-read-btn');
            if (markReadBtn) {
                markReadBtn.addEventListener('click', function() {
                    markNotificationAsRead(notification.id);
                    notificationItem.classList.add('opacity-70');
                });
            }
        });
    } else {
        // Mostrar mensagem de nenhuma notificação
        notificationsList.innerHTML = `
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                <p>Nenhuma notificação não lida</p>
            </div>
        `;
    }
}

/**
 * Atualiza interface de métricas
 */
function updateMetricsUI(metrics) {
    // Atualizar contadores
    updateCounter('total-sorteios', metrics.total_sorteios);
    updateCounter('total-participantes', metrics.total_participantes);
    updateCounter('sorteios-ativos', metrics.sorteios_ativos);
    updateCounter('participantes-hoje', metrics.participantes_hoje);
    
    // Atualizar outros elementos se necessário
    if (metrics.taxa_conversao !== undefined) {
        const taxaElement = document.getElementById('taxa-conversao');
        if (taxaElement) {
            taxaElement.textContent = metrics.taxa_conversao;
        }
    }
    
    // Atualizar sorteios populares
    if (metrics.sorteios_populares && metrics.sorteios_populares.length > 0) {
        const popularList = document.getElementById('sorteios-populares');
        if (popularList) {
            popularList.innerHTML = '';
            
            metrics.sorteios_populares.forEach(sorteio => {
                const item = document.createElement('li');
                item.className = 'py-2 border-b border-gray-200 dark:border-gray-700 last:border-0';
                item.innerHTML = `
                    <div class="flex justify-between items-center">
                        <a href="sorteios.php?id=${sorteio.id}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            ${sorteio.nome}
                        </a>
                        <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded">
                            ${sorteio.total_participantes}
                        </span>
                    </div>
                `;
                popularList.appendChild(item);
            });
        }
    }
}

/**
 * Atualiza contador com animação
 */
function updateCounter(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent.replace(/\D/g, '')) || 0;
    
    if (currentValue !== newValue) {
        animateCounter(element, currentValue, newValue);
    }
}

/**
 * Anima contador
 */
function animateCounter(element, from, to) {
    const duration = 1000; // 1 segundo
    const steps = 60;
    const increment = (to - from) / steps;
    let current = from;
    let step = 0;
    
    const animate = () => {
        current += increment;
        step++;
        
        if ((increment > 0 && current < to) || (increment < 0 && current > to)) {
            element.textContent = Math.floor(current).toLocaleString();
            
            if (step < steps) {
                requestAnimationFrame(animate);
            } else {
                element.textContent = to.toLocaleString();
            }
        } else {
            element.textContent = to.toLocaleString();
        }
    };
    
    animate();
}

/**
 * Marca notificação como lida
 */
function markNotificationAsRead(notificationId) {
    if (window.realtimeSystem) {
        window.realtimeSystem.markNotificationAsRead(notificationId);
    }
}

/**
 * Marca todas as notificações como lidas
 */
function markAllNotificationsAsRead() {
    if (window.realtimeSystem) {
        window.realtimeSystem.markAllNotificationsAsRead();
    }
}

/**
 * Obtém ícone para notificação
 */
function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
        case 'error':
            return '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
        case 'warning':
            return '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
        default:
            return '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
    }
}