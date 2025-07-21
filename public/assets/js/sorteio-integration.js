/**
 * Integração do Sistema de Sorteio com Animações
 * Conecta o motor de sorteio com as animações premium
 */

class SorteioIntegration {
    constructor(options = {}) {
        this.options = {
            animationContainer: 'sorteio-animation-container',
            apiEndpoint: 'ajax/sorteio_engine.php',
            csrfToken: null,
            ...options
        };
        
        this.animations = null;
        this.currentSorteio = null;
        this.participantes = [];
        
        this.init();
    }
    
    init() {
        this.setupCSRFToken();
        this.initializeAnimations();
        this.bindEvents();
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
     * Inicializa sistema de animações
     */
    initializeAnimations() {
        if (typeof SorteioAnimations !== 'undefined') {
            this.animations = new SorteioAnimations(this.options.animationContainer, {
                duration: 4000,
                slotMachineSpeed: 80,
                confettiDuration: 5000,
                soundEnabled: true
            });
        } else {
            console.error('SorteioAnimations não encontrado. Certifique-se de incluir sorteio-animations.js');
        }
    }
    
    /**
     * Vincula eventos globais
     */
    bindEvents() {
        // Escutar eventos customizados
        document.addEventListener('sorteio:start', (e) => {
            this.handleSorteioStart(e.detail);
        });
        
        document.addEventListener('sorteio:participantes-loaded', (e) => {
            this.handleParticipantesLoaded(e.detail);
        });
        
        // Sobrescrever função de início do sorteio nas animações
        if (this.animations) {
            const originalStart = this.animations.startSorteio.bind(this.animations);
            this.animations.startSorteio = () => {
                this.executeSorteio();
            };
        }
    }
    
    /**
     * Carrega participantes elegíveis para o sorteio
     */
    async loadParticipantes(sorteioId) {
        try {
            const response = await this.makeRequest('obter_participantes_elegiveis', {
                sorteio_id: sorteioId
            });
            
            if (response.success) {
                this.participantes = response.data.participantes;
                
                if (this.animations) {
                    this.animations.setParticipantes(this.participantes);
                }
                
                // Disparar evento
                document.dispatchEvent(new CustomEvent('sorteio:participantes-loaded', {
                    detail: { participantes: this.participantes }
                }));
                
                return this.participantes;
            } else {
                throw new Error(response.message || 'Erro ao carregar participantes');
            }
        } catch (error) {
            console.error('Erro ao carregar participantes:', error);
            this.showError('Erro ao carregar participantes: ' + error.message);
            return [];
        }
    }
    
    /**
     * Executa sorteio completo com animações
     */
    async executeSorteio() {
        if (!this.currentSorteio) {
            this.showError('Nenhum sorteio selecionado');
            return;
        }
        
        try {
            // Verificar se há participantes
            if (!this.participantes.length) {
                await this.loadParticipantes(this.currentSorteio.id);
            }
            
            if (!this.participantes.length) {
                this.showError('Nenhum participante elegível encontrado');
                return;
            }
            
            // Obter quantidade a sortear
            const quantidade = this.getQuantidadeSorteio();
            
            if (quantidade > this.participantes.length) {
                this.showError(`Quantidade solicitada (${quantidade}) maior que participantes disponíveis (${this.participantes.length})`);
                return;
            }
            
            // Mostrar loading nas animações
            if (this.animations) {
                this.animations.showLoading();
            }
            
            // Executar sorteio no backend
            const response = await this.makeRequest('executar_sorteio', {
                sorteio_id: this.currentSorteio.id,
                quantidade: quantidade
            });
            
            if (response.success) {
                const sorteados = response.data.sorteados;
                
                // Configurar sorteados nas animações
                if (this.animations) {
                    this.animations.setSorteados(sorteados);
                    this.animations.hideLoading();
                    
                    // Iniciar animação de slot machine
                    await this.animations.animateSlotMachine();
                }
                
                // Disparar evento de sucesso
                document.dispatchEvent(new CustomEvent('sorteio:completed', {
                    detail: { 
                        sorteados: sorteados,
                        resultado_id: response.data.resultado_id,
                        total_participantes: response.data.total_participantes
                    }
                }));
                
                // Atualizar estatísticas
                this.updateStats();
                
                this.showSuccess(`Sorteio realizado com sucesso! ${sorteados.length} participante(s) sorteado(s).`);
                
            } else {
                throw new Error(response.message || 'Erro na execução do sorteio');
            }
            
        } catch (error) {
            console.error('Erro no sorteio:', error);
            
            if (this.animations) {
                this.animations.hideLoading();
                this.animations.stopAnimation();
            }
            
            this.showError('Erro no sorteio: ' + error.message);
        }
    }
    
    /**
     * Obtém quantidade de participantes a sortear
     */
    getQuantidadeSorteio() {
        // Tentar obter de input na página
        const quantidadeInput = document.getElementById('quantidade-sorteio');
        if (quantidadeInput) {
            return parseInt(quantidadeInput.value) || 1;
        }
        
        // Usar configuração do sorteio
        if (this.currentSorteio && this.currentSorteio.qtd_sorteados) {
            return parseInt(this.currentSorteio.qtd_sorteados) || 1;
        }
        
        return 1;
    }
    
    /**
     * Configura sorteio atual
     */
    setSorteio(sorteio) {
        this.currentSorteio = sorteio;
        this.participantes = []; // Reset participantes
        
        // Carregar participantes automaticamente
        if (sorteio && sorteio.id) {
            this.loadParticipantes(sorteio.id);
        }
    }
    
    /**
     * Obtém histórico de sorteios
     */
    async getHistorico(sorteioId, limit = 50) {
        try {
            const response = await this.makeRequest('obter_historico', {
                sorteio_id: sorteioId,
                limit: limit
            });
            
            if (response.success) {
                return response.data.historico;
            } else {
                throw new Error(response.message || 'Erro ao obter histórico');
            }
        } catch (error) {
            console.error('Erro ao obter histórico:', error);
            this.showError('Erro ao carregar histórico: ' + error.message);
            return [];
        }
    }
    
    /**
     * Obtém estatísticas do sorteio
     */
    async getEstatisticas(sorteioId) {
        try {
            const response = await this.makeRequest('obter_estatisticas', {
                sorteio_id: sorteioId
            });
            
            if (response.success) {
                return response.data;
            } else {
                throw new Error(response.message || 'Erro ao obter estatísticas');
            }
        } catch (error) {
            console.error('Erro ao obter estatísticas:', error);
            return null;
        }
    }
    
    /**
     * Valida integridade dos resultados
     */
    async validarIntegridade(sorteioId, resultadoId = null) {
        try {
            const response = await this.makeRequest('validar_integridade', {
                sorteio_id: sorteioId,
                resultado_id: resultadoId
            });
            
            if (response.success) {
                return response.data;
            } else {
                throw new Error(response.message || 'Erro na validação');
            }
        } catch (error) {
            console.error('Erro na validação:', error);
            return { valid: false, issues: ['Erro na validação: ' + error.message] };
        }
    }
    
    /**
     * Remove resultado de sorteio
     */
    async removerResultado(sorteioId, resultadoId) {
        if (!confirm('Tem certeza que deseja remover este resultado? Esta ação não pode ser desfeita.')) {
            return false;
        }
        
        try {
            const response = await this.makeRequest('remover_resultado', {
                sorteio_id: sorteioId,
                resultado_id: resultadoId,
                confirmacao: 'CONFIRMAR_REMOCAO'
            });
            
            if (response.success) {
                this.showSuccess('Resultado removido com sucesso');
                this.updateStats();
                return true;
            } else {
                throw new Error(response.message || 'Erro ao remover resultado');
            }
        } catch (error) {
            console.error('Erro ao remover resultado:', error);
            this.showError('Erro ao remover resultado: ' + error.message);
            return false;
        }
    }
    
    /**
     * Atualiza estatísticas na interface
     */
    async updateStats() {
        if (!this.currentSorteio) return;
        
        try {
            const stats = await this.getEstatisticas(this.currentSorteio.id);
            if (stats) {
                // Disparar evento para atualizar interface
                document.dispatchEvent(new CustomEvent('sorteio:stats-updated', {
                    detail: stats
                }));
            }
        } catch (error) {
            console.error('Erro ao atualizar estatísticas:', error);
        }
    }
    
    /**
     * Faz requisição AJAX para o backend
     */
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', this.options.csrfToken);
        
        // Adicionar dados
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch(this.options.apiEndpoint, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    /**
     * Manipuladores de eventos
     */
    handleSorteioStart(detail) {
        if (detail.sorteio) {
            this.setSorteio(detail.sorteio);
        }
    }
    
    handleParticipantesLoaded(detail) {
        console.log(`${detail.participantes.length} participantes carregados`);
    }
    
    /**
     * Utilitários de UI
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Tentar usar sistema de notificação existente
        if (window.showNotification) {
            window.showNotification(message, type);
            return;
        }
        
        // Fallback para alert/console
        if (type === 'error') {
            console.error(message);
            alert('Erro: ' + message);
        } else {
            console.log(message);
            if (type === 'success') {
                alert('Sucesso: ' + message);
            }
        }
    }
    
    /**
     * Reset completo
     */
    reset() {
        this.currentSorteio = null;
        this.participantes = [];
        
        if (this.animations) {
            this.animations.reset();
        }
    }
    
    /**
     * Destroy - limpa recursos
     */
    destroy() {
        this.reset();
        
        // Remover event listeners se necessário
        document.removeEventListener('sorteio:start', this.handleSorteioStart);
        document.removeEventListener('sorteio:participantes-loaded', this.handleParticipantesLoaded);
    }
}

// Inicialização global
window.SorteioIntegration = SorteioIntegration;

// Auto-inicialização se container existir
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sorteio-animation-container');
    if (container) {
        window.sorteioSystem = new SorteioIntegration();
    }
});

// Funções auxiliares globais
window.initSorteioSystem = function(options = {}) {
    return new SorteioIntegration(options);
};

window.executarSorteio = function(sorteioId, quantidade = 1) {
    if (window.sorteioSystem) {
        window.sorteioSystem.setSorteio({ id: sorteioId, qtd_sorteados: quantidade });
        return window.sorteioSystem.executeSorteio();
    } else {
        console.error('Sistema de sorteio não inicializado');
    }
};

window.carregarParticipantes = function(sorteioId) {
    if (window.sorteioSystem) {
        return window.sorteioSystem.loadParticipantes(sorteioId);
    } else {
        console.error('Sistema de sorteio não inicializado');
        return Promise.resolve([]);
    }
};