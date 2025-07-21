/**
 * Sistema de Sorteios - Módulo AJAX em Tempo Real
 * Implementa funcionalidades AJAX para atualização em tempo real de dados
 */

// Namespace para funções AJAX
const SorteiosAjax = {
    // Configurações
    config: {
        refreshInterval: 30000, // 30 segundos
        notificationsInterval: 60000, // 1 minuto
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, ''),
        activeTimers: {}
    },
    
    /**
     * Inicializa o módulo AJAX
     */
    init: function() {
        // Configurar headers padrão para todas as requisições
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.config.csrfToken
            }
        });
        
        // Inicializar componentes se existirem no DOM
        this.initDashboardMetrics();
        this.initNotifications();
        this.initSorteiosList();
        this.initParticipantesList();
        this.initRealtimeCharts();
    },
    
    /**
     * Inicializa métricas do dashboard
     */
    initDashboardMetrics: function() {
        const dashboardContainer = document.getElementById('dashboard-metrics');
        if (!dashboardContainer) return;
        
        // Carregar métricas iniciais
        this.loadDashboardMetrics();
        
        // Configurar atualização periódica
        this.startPeriodicUpdate('dashboardMetrics', this.loadDashboardMetrics.bind(this), this.config.refreshInterval);
    },
    
    /**
     * Carrega métricas do dashboard
     */
    loadDashboardMetrics: function() {
        $.ajax({
            url: this.config.baseUrl + '/ajax/metrics_realtime.php',
            data: { action: 'get_dashboard_metrics' },
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    SorteiosAjax.updateDashboardMetrics(response.data.metrics);
                }
            },
            error: function() {
                console.error('Erro ao carregar métricas do dashboard');
            }
        });
    },
    
    /**
     * Atualiza métricas do dashboard no DOM
     */
    updateDashboardMetrics: function(metrics) {
        // Atualizar contadores
        for (const [key, value] of Object.entries(metrics)) {
            const element = document.querySelector(`[data-metric="${key}"]`);
            if (element) {
                // Animação de contagem
                const currentValue = parseInt(element.getAttribute('data-value') || '0');
                const newValue = typeof value === 'number' ? value : 0;
                
                if (currentValue !== newValue) {
                    this.animateCounter(element, currentValue, newValue);
                    element.setAttribute('data-value', newValue);
                }
            }
        }
        
        // Atualizar sorteios populares se existir
        if (metrics.sorteios_populares && Array.isArray(metrics.sorteios_populares)) {
            const popularContainer = document.getElementById('sorteios-populares');
            if (popularContainer) {
                let html = '';
                
                metrics.sorteios_populares.forEach(sorteio => {
                    html += `
                        <div class="flex items-center justify-between p-2 border-b">
                            <div class="flex-1">
                                <a href="sorteios.php?id=${sorteio.id}" class="text-blue-600 hover:underline">
                                    ${sorteio.nome}
                                </a>
                            </div>
                            <div class="text-gray-600">
                                ${sorteio.total_participantes} participantes
                            </div>
                        </div>
                    `;
                });
                
                popularContainer.innerHTML = html;
            }
        }
        
        // Disparar evento de métricas atualizadas
        document.dispatchEvent(new CustomEvent('metricsUpdated', { detail: metrics }));
    },
    
    /**
     * Inicializa sistema de notificações
     */
    initNotifications: function() {
        const notificationsContainer = document.getElementById('notifications-container');
        if (!notificationsContainer) return;
        
        // Carregar notificações iniciais
        this.loadNotifications();
        
        // Configurar atualização periódica
        this.startPeriodicUpdate('notifications', this.loadNotifications.bind(this), this.config.notificationsInterval);
        
        // Configurar eventos
        document.addEventListener('click', function(e) {
            // Marcar notificação como lida
            if (e.target.closest('[data-action="mark-read"]')) {
                e.preventDefault();
                const notificationId = e.target.closest('[data-notification-id]').getAttribute('data-notification-id');
                SorteiosAjax.markNotificationAsRead(notificationId);
            }
            
            // Marcar todas como lidas
            if (e.target.closest('[data-action="mark-all-read"]')) {
                e.preventDefault();
                SorteiosAjax.markAllNotificationsAsRead();
            }
        });
    },
    
    /**
     * Carrega notificações
     */
    loadNotifications: function() {
        $.ajax({
            url: this.config.baseUrl + '/ajax/notifications_realtime.php',
            data: { action: 'get_unread' },
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    SorteiosAjax.updateNotifications(response.data.notifications);
                }
            },
            error: function() {
                console.error('Erro ao carregar notificações');
            }
        });
    },
    
    /**
     * Atualiza notificações no DOM
     */
    updateNotifications: function(notifications) {
        const container = document.getElementById('notifications-list');
        const badge = document.getElementById('notifications-badge');
        
        if (!container) return;
        
        // Atualizar contador
        if (badge) {
            const count = notifications.length;
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
        
        // Atualizar lista
        if (notifications.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhuma notificação</div>';
            return;
        }
        
        let html = '';
        
        notifications.forEach(notification => {
            const typeClass = this.getNotificationTypeClass(notification.type);
            
            html += `
                <div class="p-3 border-b hover:bg-gray-50" data-notification-id="${notification.id}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="inline-block w-2 h-2 mt-1 rounded-full ${typeClass}"></span>
                        </div>
                        <div class="ml-2 flex-1">
                            <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                            <p class="text-sm text-gray-500">${notification.message}</p>
                            <p class="text-xs text-gray-400 mt-1">${notification.time_ago}</p>
                        </div>
                        <div class="ml-2">
                            <button data-action="mark-read" class="text-xs text-blue-600 hover:text-blue-800">
                                Marcar como lida
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Disparar evento de notificações atualizadas
        document.dispatchEvent(new CustomEvent('notificationsUpdated', { detail: notifications }));
    },
    
    /**
     * Retorna classe CSS para tipo de notificação
     */
    getNotificationTypeClass: function(type) {
        switch (type) {
            case 'info': return 'bg-blue-500';
            case 'success': return 'bg-green-500';
            case 'warning': return 'bg-yellow-500';
            case 'error': return 'bg-red-500';
            default: return 'bg-gray-500';
        }
    },
    
    /**
     * Marca notificação como lida
     */
    markNotificationAsRead: function(notificationId) {
        $.ajax({
            url: this.config.baseUrl + '/ajax/notifications_realtime.php',
            data: { 
                action: 'mark_read',
                notification_id: notificationId,
                csrf_token: this.config.csrfToken
            },
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    // Remover notificação do DOM
                    const element = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (element) {
                        element.remove();
                    }
                    
                    // Recarregar notificações
                    SorteiosAjax.loadNotifications();
                }
            }
        });
    },
    
    /**
     * Marca todas as notificações como lidas
     */
    markAllNotificationsAsRead: function() {
        $.ajax({
            url: this.config.baseUrl + '/ajax/notifications_realtime.php',
            data: { 
                action: 'mark_all_read',
                csrf_token: this.config.csrfToken
            },
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    // Recarregar notificações
                    SorteiosAjax.loadNotifications();
                }
            }
        });
    },
    
    /**
     * Inicializa lista de sorteios
     */
    initSorteiosList: function() {
        const sorteiosContainer = document.getElementById('sorteios-list');
        if (!sorteiosContainer) return;
        
        // Carregar sorteios iniciais
        this.loadSorteiosList();
        
        // Configurar atualização periódica
        this.startPeriodicUpdate('sorteiosList', this.loadSorteiosList.bind(this), this.config.refreshInterval);
        
        // Configurar eventos de filtro
        document.querySelectorAll('[data-filter="sorteios"]').forEach(filter => {
            filter.addEventListener('change', () => {
                this.loadSorteiosList();
            });
        });
        
        // Configurar busca
        const searchInput = document.getElementById('sorteios-search');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.loadSorteiosList();
                }, 500);
            });
        }
    },
    
    /**
     * Carrega lista de sorteios
     */
    loadSorteiosList: function() {
        const status = document.querySelector('[data-filter="sorteios"][name="status"]')?.value || '';
        const search = document.getElementById('sorteios-search')?.value || '';
        
        $.ajax({
            url: this.config.baseUrl + '/ajax/data_realtime.php',
            data: { 
                action: 'search_sorteios',
                status: status,
                search: search,
                limit: 20,
                offset: 0
            },
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    SorteiosAjax.updateSorteiosList(response.data.sorteios);
                }
            },
            error: function() {
                console.error('Erro ao carregar lista de sorteios');
            }
        });
    },
    
    /**
     * Atualiza lista de sorteios no DOM
     */
    updateSorteiosList: function(sorteios) {
        const container = document.getElementById('sorteios-list');
        if (!container) return;
        
        if (sorteios.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum sorteio encontrado</div>';
            return;
        }
        
        let html = '';
        
        sorteios.forEach(sorteio => {
            const statusClass = this.getSorteioStatusClass(sorteio.status);
            const percentFilled = sorteio.max_participantes > 0 
                ? Math.min(100, Math.round((sorteio.total_participantes / sorteio.max_participantes) * 100))
                : 0;
            
            html += `
                <div class="bg-white rounded-lg shadow p-4 mb-4" data-sorteio-id="${sorteio.id}">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg font-semibold">
                            <a href="sorteios.php?id=${sorteio.id}" class="text-blue-600 hover:underline">
                                ${sorteio.nome}
                            </a>
                        </h3>
                        <span class="px-2 py-1 text-xs rounded ${statusClass}">
                            ${this.formatSorteioStatus(sorteio.status)}
                        </span>
                    </div>
                    
                    <p class="text-gray-600 text-sm mt-1">${sorteio.descricao || 'Sem descrição'}</p>
                    
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-xs text-gray-500">Participantes</span>
                            <p class="font-medium">${sorteio.total_participantes}${sorteio.max_participantes > 0 ? '/' + sorteio.max_participantes : ''}</p>
                            
                            ${sorteio.max_participantes > 0 ? `
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${percentFilled}%"></div>
                                </div>
                            ` : ''}
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Sorteados</span>
                            <p class="font-medium">${sorteio.total_sorteados}/${sorteio.qtd_sorteados}</p>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-500">
                        Criado em ${sorteio.formatted_date}
                    </div>
                    
                    <div class="mt-3 flex space-x-2">
                        <a href="sorteios.php?id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                            Detalhes
                        </a>
                        <a href="participantes.php?sorteio_id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                            Participantes
                        </a>
                        <a href="sorteio_engine.php?id=${sorteio.id}" class="text-sm text-blue-600 hover:underline">
                            Sortear
                        </a>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Disparar evento de sorteios atualizados
        document.dispatchEvent(new CustomEvent('sorteiosUpdated', { detail: sorteios }));
    },
    
    /**
     * Retorna classe CSS para status do sorteio
     */
    getSorteioStatusClass: function(status) {
        switch (status) {
            case 'ativo': return 'bg-green-100 text-green-800';
            case 'pausado': return 'bg-yellow-100 text-yellow-800';
            case 'finalizado': return 'bg-blue-100 text-blue-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    },
    
    /**
     * Formata status do sorteio
     */
    formatSorteioStatus: function(status) {
        switch (status) {
            case 'ativo': return 'Ativo';
            case 'pausado': return 'Pausado';
            case 'finalizado': return 'Finalizado';
            default: return status;
        }
    },
    
    /**
     * Inicializa lista de participantes
     */
    initParticipantesList: function() {
        const participantesContainer = document.getElementById('participantes-list');
        if (!participantesContainer) return;
        
        const sorteioId = participantesContainer.getAttribute('data-sorteio-id');
        if (!sorteioId) return;
        
        // Carregar participantes iniciais
        this.loadParticipantesList(sorteioId);
        
        // Configurar atualização periódica
        this.startPeriodicUpdate('participantesList', () => this.loadParticipantesList(sorteioId), this.config.refreshInterval);
        
        // Configurar busca
        const searchInput = document.getElementById('participantes-search');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.loadParticipantesList(sorteioId);
                }, 500);
            });
        }
    },
    
    /**
     * Carrega lista de participantes
     */
    loadParticipantesList: function(sorteioId) {
        const search = document.getElementById('participantes-search')?.value || '';
        
        $.ajax({
            url: this.config.baseUrl + '/ajax/data_realtime.php',
            data: { 
                action: 'search_participantes',
                sorteio_id: sorteioId,
                search: search,
                limit: 50,
                offset: 0
            },
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    SorteiosAjax.updateParticipantesList(response.data.participantes);
                }
            },
            error: function() {
                console.error('Erro ao carregar lista de participantes');
            }
        });
    },
    
    /**
     * Atualiza lista de participantes no DOM
     */
    updateParticipantesList: function(participantes) {
        const container = document.getElementById('participantes-list');
        if (!container) return;
        
        if (participantes.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum participante encontrado</div>';
            return;
        }
        
        let html = '';
        
        participantes.forEach(participante => {
            const sorteadoClass = participante.foi_sorteado 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800';
            
            html += `
                <div class="bg-white rounded-lg shadow p-4 mb-4" data-participante-id="${participante.id}">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg font-semibold">${participante.nome}</h3>
                        <span class="px-2 py-1 text-xs rounded ${sorteadoClass}">
                            ${participante.foi_sorteado ? 'Sorteado' : 'Não sorteado'}
                        </span>
                    </div>
                    
                    <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        ${participante.whatsapp ? `
                            <div>
                                <span class="text-xs text-gray-500">WhatsApp</span>
                                <p>${participante.whatsapp_formatted}</p>
                            </div>
                        ` : ''}
                        
                        ${participante.cpf ? `
                            <div>
                                <span class="text-xs text-gray-500">CPF</span>
                                <p>${participante.cpf_formatted}</p>
                            </div>
                        ` : ''}
                        
                        ${participante.email ? `
                            <div>
                                <span class="text-xs text-gray-500">Email</span>
                                <p class="truncate">${participante.email}</p>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-500">
                        Cadastrado em ${participante.formatted_date}
                    </div>
                    
                    ${participante.foi_sorteado ? `
                        <div class="mt-2 text-sm text-green-600">
                            Posição no sorteio: ${participante.posicao_sorteio}
                        </div>
                    ` : ''}
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Disparar evento de participantes atualizados
        document.dispatchEvent(new CustomEvent('participantesUpdated', { detail: participantes }));
    },
    
    /**
     * Inicializa gráficos em tempo real
     */
    initRealtimeCharts: function() {
        document.querySelectorAll('[data-chart]').forEach(chartElement => {
            const chartType = chartElement.getAttribute('data-chart');
            const chartId = chartElement.getAttribute('id');
            
            if (!chartId) return;
            
            // Carregar dados iniciais
            this.loadChartData(chartId, chartType);
            
            // Configurar atualização periódica
            this.startPeriodicUpdate(`chart_${chartId}`, () => this.loadChartData(chartId, chartType), this.config.refreshInterval);
        });
    },
    
    /**
     * Carrega dados para gráfico
     */
    loadChartData: function(chartId, chartType) {
        const chartElement = document.getElementById(chartId);
        if (!chartElement) return;
        
        const sorteioId = chartElement.getAttribute('data-sorteio-id') || null;
        const period = chartElement.getAttribute('data-period') || 30;
        
        $.ajax({
            url: this.config.baseUrl + '/ajax/metrics_realtime.php',
            data: { 
                action: 'get_chart_data',
                type: chartType,
                period: period,
                sorteio_id: sorteioId
            },
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    SorteiosAjax.updateChart(chartId, response.data.chart_data);
                }
            },
            error: function() {
                console.error('Erro ao carregar dados do gráfico');
            }
        });
    },
    
    /**
     * Atualiza gráfico com novos dados
     */
    updateChart: function(chartId, chartData) {
        const chartElement = document.getElementById(chartId);
        if (!chartElement) return;
        
        // Verificar se o gráfico já existe
        let chart = Chart.getChart(chartId);
        
        if (chart) {
            // Atualizar dados do gráfico existente
            chart.data.labels = chartData.labels;
            chart.data.datasets = chartData.datasets;
            chart.update();
        } else {
            // Criar novo gráfico
            const ctx = chartElement.getContext('2d');
            chart = new Chart(ctx, {
                type: chartElement.getAttribute('data-chart-type') || 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }
        
        // Disparar evento de gráfico atualizado
        document.dispatchEvent(new CustomEvent('chartUpdated', { 
            detail: { chartId, chartData } 
        }));
    },
    
    /**
     * Inicia atualização periódica
     */
    startPeriodicUpdate: function(key, callback, interval) {
        // Limpar timer existente
        if (this.config.activeTimers[key]) {
            clearInterval(this.config.activeTimers[key]);
        }
        
        // Iniciar novo timer
        this.config.activeTimers[key] = setInterval(callback, interval);
    },
    
    /**
     * Para atualização periódica
     */
    stopPeriodicUpdate: function(key) {
        if (this.config.activeTimers[key]) {
            clearInterval(this.config.activeTimers[key]);
            delete this.config.activeTimers[key];
        }
    },
    
    /**
     * Anima contador
     */
    animateCounter: function(element, start, end) {
        const duration = 1000;
        const startTime = performance.now();
        const updateCount = timestamp => {
            const runtime = timestamp - startTime;
            const progress = Math.min(runtime / duration, 1);
            
            const currentCount = Math.floor(start + (end - start) * progress);
            element.textContent = currentCount.toLocaleString();
            
            if (runtime < duration) {
                requestAnimationFrame(updateCount);
            } else {
                element.textContent = end.toLocaleString();
            }
        };
        
        requestAnimationFrame(updateCount);
    }
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    SorteiosAjax.init();
});