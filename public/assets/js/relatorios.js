/**
 * Sistema de Relatórios Avançados - JavaScript
 * Gerencia gráficos, filtros e exportações
 */

class RelatoriosManager {
    constructor() {
        this.charts = {};
        this.currentData = window.relatorioData || {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.setupExportHandlers();
        this.setupTemplateModal();
    }

    setupEventListeners() {
        // Auto-submit do formulário de filtros quando houver mudança
        const filtrosForm = document.getElementById('filtros-form');
        if (filtrosForm) {
            const selects = filtrosForm.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', () => {
                    filtrosForm.submit();
                });
            });
        }
    }

    initializeCharts() {
        const tipo = this.currentData.tipo;
        
        switch (tipo) {
            case 'participacao':
                this.initParticipacaoCharts();
                break;
            case 'conversao':
                this.initConversaoCharts();
                break;
            case 'engajamento':
                this.initEngajamentoCharts();
                break;
            case 'comparativo':
                this.initComparativoCharts();
                break;
        }
    }

    initParticipacaoCharts() {
        if (window.participacaoData) {
            this.createParticipacaoDiariaChart();
            this.createDistribuicaoChart();
        }
    }

    createParticipacaoDiariaChart() {
        const ctx = document.getElementById('participacao-diaria-chart');
        if (!ctx || !window.participacaoData.participacao_diaria) return;

        const data = window.participacaoData.participacao_diaria;
        const labels = data.map(item => {
            const date = new Date(item.data);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        });
        const participantes = data.map(item => parseInt(item.participantes));

        this.charts.participacaoDiaria = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Participantes',
                    data: participantes,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    createDistribuicaoChart() {
        const ctx = document.getElementById('distribuicao-chart');
        if (!ctx || !window.participacaoData.top_sorteios) return;

        const data = window.participacaoData.top_sorteios.slice(0, 5);
        const labels = data.map(item => item.nome.length > 20 ? item.nome.substring(0, 20) + '...' : item.nome);
        const participantes = data.map(item => parseInt(item.total_participantes));

        const colors = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)'
        ];

        this.charts.distribuicao = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: participantes,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    initConversaoCharts() {
        if (window.conversaoData) {
            this.createConversaoChart();
        }
    }

    createConversaoChart() {
        const ctx = document.getElementById('conversao-chart');
        if (!ctx || !window.conversaoData.conversao_por_sorteio) return;

        const data = window.conversaoData.conversao_por_sorteio;
        const labels = data.map(item => item.nome.length > 15 ? item.nome.substring(0, 15) + '...' : item.nome);
        const taxas = data.map(item => parseFloat(item.taxa_conversao));

        this.charts.conversao = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Taxa de Conversão (%)',
                    data: taxas,
                    backgroundColor: taxas.map(taxa => {
                        if (taxa >= 10) return 'rgba(16, 185, 129, 0.8)';
                        if (taxa >= 5) return 'rgba(245, 158, 11, 0.8)';
                        return 'rgba(239, 68, 68, 0.8)';
                    }),
                    borderColor: taxas.map(taxa => {
                        if (taxa >= 10) return 'rgb(16, 185, 129)';
                        if (taxa >= 5) return 'rgb(245, 158, 11)';
                        return 'rgb(239, 68, 68)';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45
                        }
                    }
                }
            }
        });
    }

    initEngajamentoCharts() {
        if (window.engajamentoData) {
            this.createEngajamentoHoraChart();
            this.createEngajamentoDiaChart();
        }
    }

    createEngajamentoHoraChart() {
        const ctx = document.getElementById('engajamento-hora-chart');
        if (!ctx || !window.engajamentoData.engajamento_por_hora) return;

        const data = window.engajamentoData.engajamento_por_hora;
        const labels = data.map(item => item.hora + 'h');
        const participantes = data.map(item => parseInt(item.participantes));

        this.charts.engajamentoHora = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Participantes',
                    data: participantes,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    createEngajamentoDiaChart() {
        const ctx = document.getElementById('engajamento-dia-chart');
        if (!ctx || !window.engajamentoData.engajamento_por_dia) return;

        const data = window.engajamentoData.engajamento_por_dia;
        const labels = data.map(item => item.dia_semana);
        const participantes = data.map(item => parseInt(item.participantes));

        const colors = [
            'rgba(239, 68, 68, 0.6)',   // Domingo - Vermelho
            'rgba(59, 130, 246, 0.6)',  // Segunda - Azul
            'rgba(16, 185, 129, 0.6)',  // Terça - Verde
            'rgba(245, 158, 11, 0.6)',  // Quarta - Amarelo
            'rgba(139, 92, 246, 0.6)',  // Quinta - Roxo
            'rgba(236, 72, 153, 0.6)',  // Sexta - Rosa
            'rgba(34, 197, 94, 0.6)'    // Sábado - Verde claro
        ];

        this.charts.engajamentoDia = new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels: labels,
                datasets: [{
                    data: participantes,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    initComparativoCharts() {
        if (window.comparativoData) {
            this.createComparativoMensalChart();
        }
    }

    createComparativoMensalChart() {
        const ctx = document.getElementById('comparativo-mensal-chart');
        if (!ctx || !window.comparativoData.comparativo_mensal) return;

        const data = window.comparativoData.comparativo_mensal;
        const labels = data.map(item => {
            const date = new Date(item.mes + '-01');
            return date.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
        });

        this.charts.comparativoMensal = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sorteios Criados',
                        data: data.map(item => parseInt(item.sorteios_criados)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Participantes',
                        data: data.map(item => parseInt(item.total_participantes)),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Ganhadores',
                        data: data.map(item => parseInt(item.total_ganhadores)),
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Período'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sorteios'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Pessoas'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    setupExportHandlers() {
        // Exportar PDF
        const btnExportPdf = document.getElementById('exportar-pdf');
        if (btnExportPdf) {
            btnExportPdf.addEventListener('click', () => this.exportToPDF());
        }

        // Exportar CSV
        const btnExportCsv = document.getElementById('exportar-csv');
        if (btnExportCsv) {
            btnExportCsv.addEventListener('click', () => this.exportToCSV());
        }

        // Exportar tabelas específicas
        const exportButtons = document.querySelectorAll('[id^="exportar-"]');
        exportButtons.forEach(btn => {
            if (!['exportar-pdf', 'exportar-csv'].includes(btn.id)) {
                btn.addEventListener('click', (e) => this.exportTable(e.target));
            }
        });
    }

    exportToPDF() {
        this.showNotification('Gerando PDF...', 'info');
        
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'pdf');
        
        window.open(`ajax/export_relatorio.php?${params.toString()}`, '_blank');
    }

    exportToCSV() {
        this.showNotification('Gerando CSV...', 'info');
        
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        
        window.location.href = `ajax/export_relatorio.php?${params.toString()}`;
    }

    exportTable(button) {
        const tableId = button.id.replace('exportar-', '');
        this.showNotification(`Exportando ${tableId}...`, 'info');
        
        // Implementar exportação específica da tabela
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        params.set('table', tableId);
        
        window.location.href = `ajax/export_relatorio.php?${params.toString()}`;
    }

    setupTemplateModal() {
        const btnSalvarTemplate = document.getElementById('salvar-template');
        const modal = document.getElementById('modal-template');
        const btnCancelar = document.getElementById('cancelar-template');
        const form = document.getElementById('form-template');

        if (btnSalvarTemplate && modal) {
            btnSalvarTemplate.addEventListener('click', () => {
                modal.classList.remove('hidden');
            });
        }

        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                modal.classList.add('hidden');
                form.reset();
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => this.salvarTemplate(e));
        }

        // Fechar modal clicando fora
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    form.reset();
                }
            });
        }
    }

    async salvarTemplate(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const templateData = {
            nome: formData.get('nome'),
            descricao: formData.get('descricao'),
            tipo: this.currentData.tipo,
            filtros: this.currentData.filtros,
            configuracao: {
                tipo_relatorio: this.currentData.tipo,
                filtros_aplicados: this.currentData.filtros,
                data_criacao: new Date().toISOString()
            }
        };

        try {
            const response = await fetch('ajax/salvar_template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(templateData)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Template salvo com sucesso!', 'success');
                document.getElementById('modal-template').classList.add('hidden');
                document.getElementById('form-template').reset();
            } else {
                this.showNotification('Erro ao salvar template: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao salvar template:', error);
            this.showNotification('Erro ao salvar template', 'error');
        }
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification-toast ${type} transform transition-all duration-300 translate-x-full`;
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            info: 'bg-blue-500',
            warning: 'bg-yellow-500'
        };

        notification.innerHTML = `
            <div class="flex items-center p-4 ${colors[type]} text-white rounded-lg shadow-lg">
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    // Método para atualizar dados via AJAX (para futuras implementações)
    async updateData(filters) {
        try {
            const response = await fetch('ajax/relatorios_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(filters)
            });

            const data = await response.json();
            
            if (data.success) {
                this.currentData = data.data;
                this.destroyCharts();
                this.initializeCharts();
            }
        } catch (error) {
            console.error('Erro ao atualizar dados:', error);
            this.showNotification('Erro ao atualizar dados', 'error');
        }
    }

    destroyCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new RelatoriosManager();
});

// Utilitários para formatação
window.RelatoriosUtils = {
    formatNumber: (num) => {
        return new Intl.NumberFormat('pt-BR').format(num);
    },
    
    formatPercent: (num) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'percent',
            minimumFractionDigits: 1,
            maximumFractionDigits: 2
        }).format(num / 100);
    },
    
    formatDate: (dateString) => {
        return new Date(dateString).toLocaleDateString('pt-BR');
    }
};