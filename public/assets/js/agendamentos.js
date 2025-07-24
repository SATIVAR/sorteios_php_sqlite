/**
 * Sistema de Agendamentos de Relatórios - JavaScript
 * Gerencia criação, edição e controle de agendamentos
 */

class AgendamentosManager {
    constructor() {
        this.currentData = window.agendamentosData || {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupModal();
    }

    setupEventListeners() {
        // Botão novo agendamento
        const btnNovo = document.getElementById('novo-agendamento');
        const btnCriarPrimeiro = document.getElementById('criar-primeiro-agendamento');
        
        if (btnNovo) {
            btnNovo.addEventListener('click', () => this.abrirModalNovo());
        }
        
        if (btnCriarPrimeiro) {
            btnCriarPrimeiro.addEventListener('click', () => this.abrirModalNovo());
        }

        // Botão processar agendamentos
        const btnProcessar = document.getElementById('processar-agendamentos');
        if (btnProcessar) {
            btnProcessar.addEventListener('click', () => this.processarAgendamentos());
        }
    }

    setupModal() {
        const modal = document.getElementById('modal-agendamento');
        const btnCancelar = document.getElementById('cancelar-agendamento');
        const form = document.getElementById('form-agendamento');

        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                this.fecharModal();
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => this.salvarAgendamento(e));
        }

        // Fechar modal clicando fora
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.fecharModal();
                }
            });
        }
    }

    abrirModalNovo() {
        const modal = document.getElementById('modal-agendamento');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('form-agendamento');

        title.textContent = 'Novo Agendamento de Relatório';
        form.reset();
        document.getElementById('agendamento-id').value = '';

        modal.classList.remove('hidden');
    }

    abrirModalEdicao(agendamentoId) {
        const agendamento = this.currentData.agendamentos.find(a => a.id == agendamentoId);
        if (!agendamento) {
            this.showNotification('Agendamento não encontrado', 'error');
            return;
        }

        const modal = document.getElementById('modal-agendamento');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('form-agendamento');

        title.textContent = 'Editar Agendamento';
        
        // Preencher formulário
        const config = JSON.parse(agendamento.configuracao);
        
        document.getElementById('agendamento-id').value = agendamento.id;
        document.getElementById('nome-agendamento').value = agendamento.nome;
        document.getElementById('tipo-relatorio').value = config.tipo_relatorio;
        document.getElementById('formato').value = config.formato;
        document.getElementById('frequencia').value = config.frequencia;
        document.getElementById('email-destino').value = config.email_destino;
        
        // Preencher filtros
        if (config.filtros) {
            document.getElementById('filtro-periodo').value = config.filtros.periodo || '30';
            document.getElementById('filtro-sorteio').value = config.filtros.sorteio || '';
            document.getElementById('filtro-status').value = config.filtros.status || '';
        }

        modal.classList.remove('hidden');
    }

    fecharModal() {
        const modal = document.getElementById('modal-agendamento');
        const form = document.getElementById('form-agendamento');
        
        modal.classList.add('hidden');
        form.reset();
    }

    async salvarAgendamento(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const agendamentoId = formData.get('id');
        
        // Construir objeto de dados
        const dados = {
            nome: formData.get('nome'),
            tipo_relatorio: formData.get('tipo_relatorio'),
            formato: formData.get('formato'),
            frequencia: formData.get('frequencia'),
            email_destino: formData.get('email_destino'),
            filtros: {
                periodo: formData.get('filtros[periodo]'),
                sorteio: formData.get('filtros[sorteio]'),
                status: formData.get('filtros[status]')
            }
        };

        try {
            const url = agendamentoId ? 'ajax/editar_agendamento.php' : 'ajax/agendar_relatorio.php';
            if (agendamentoId) {
                dados.id = agendamentoId;
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(result.message, 'success');
                this.fecharModal();
                
                // Recarregar página para mostrar mudanças
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showNotification('Erro: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao salvar agendamento:', error);
            this.showNotification('Erro ao salvar agendamento', 'error');
        }
    }

    async alterarStatusAgendamento(agendamentoId, novoStatus) {
        try {
            const response = await fetch('ajax/alterar_status_agendamento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: agendamentoId,
                    status: novoStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(result.message, 'success');
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showNotification('Erro: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao alterar status:', error);
            this.showNotification('Erro ao alterar status', 'error');
        }
    }

    async excluirAgendamento(agendamentoId) {
        if (!confirm('Tem certeza que deseja excluir este agendamento?')) {
            return;
        }

        try {
            const response = await fetch('ajax/excluir_agendamento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: agendamentoId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(result.message, 'success');
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showNotification('Erro: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao excluir agendamento:', error);
            this.showNotification('Erro ao excluir agendamento', 'error');
        }
    }

    async processarAgendamentos() {
        const btnProcessar = document.getElementById('processar-agendamentos');
        const originalText = btnProcessar.innerHTML;
        
        // Mostrar loading
        btnProcessar.innerHTML = `
            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processando...
        `;
        btnProcessar.disabled = true;

        try {
            const response = await fetch('processar_agendamentos.php', {
                method: 'GET'
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(`${result.message}`, 'success');
            } else {
                this.showNotification('Erro: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao processar agendamentos:', error);
            this.showNotification('Erro ao processar agendamentos', 'error');
        } finally {
            // Restaurar botão
            btnProcessar.innerHTML = originalText;
            btnProcessar.disabled = false;
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
}

// Funções globais para os botões da tabela
window.editarAgendamento = function(id) {
    window.agendamentosManager.abrirModalEdicao(id);
};

window.pausarAgendamento = function(id) {
    window.agendamentosManager.alterarStatusAgendamento(id, 'pausado');
};

window.ativarAgendamento = function(id) {
    window.agendamentosManager.alterarStatusAgendamento(id, 'ativo');
};

window.excluirAgendamento = function(id) {
    window.agendamentosManager.excluirAgendamento(id);
};

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.agendamentosManager = new AgendamentosManager();
});