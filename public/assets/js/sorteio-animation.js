/**
 * Sistema de Sorteios - Animações de Sorteio
 * Implementa animações para o motor de sorteio
 */

// Namespace para animações de sorteio
const SorteioAnimation = {
    // Configurações
    config: {
        slotSpeed: 50, // ms entre cada mudança
        animationDuration: 3000, // 3 segundos
        confettiDuration: 5000, // 5 segundos
        confettiColors: ['#26ccff', '#a25afd', '#ff5e7e', '#88ff5a', '#fcff42', '#ffa62d', '#ff36ff']
    },
    
    // Estado
    state: {
        isAnimating: false,
        winners: [],
        participants: [],
        slotInterval: null
    },
    
    /**
     * Inicializa animação de sorteio
     */
    init: function(options = {}) {
        // Mesclar opções com padrões
        this.config = {
            ...this.config,
            ...options
        };
        
        // Configurar elementos
        this.setupElements();
        
        // Configurar eventos
        this.setupEvents();
    },
    
    /**
     * Configura elementos DOM
     */
    setupElements: function() {
        this.elements = {
            startButton: document.getElementById('start-draw'),
            resetButton: document.getElementById('reset-draw'),
            slotContainer: document.getElementById('slot-container'),
            slotItems: document.getElementById('slot-items'),
            resultsContainer: document.getElementById('results'),
            winnersContainer: document.getElementById('winners-container'),
            participantsTable: document.getElementById('participants-table')
        };
        
        // Verificar se todos os elementos existem
        for (const [key, element] of Object.entries(this.elements)) {
            if (!element) {
                console.error(`Elemento ${key} não encontrado`);
            }
        }
    },
    
    /**
     * Configura eventos
     */
    setupEvents: function() {
        if (this.elements.startButton) {
            this.elements.startButton.addEventListener('click', () => this.startDraw());
        }
        
        if (this.elements.resetButton) {
            this.elements.resetButton.addEventListener('click', () => this.resetDraw());
        }
    },
    
    /**
     * Define participantes
     */
    setParticipants: function(participants) {
        this.state.participants = participants;
        
        // Atualizar slot machine
        if (this.elements.slotItems) {
            this.elements.slotItems.innerHTML = '';
            
            participants.forEach(participant => {
                const slotItem = document.createElement('div');
                slotItem.className = 'slot-item py-2 text-xl font-bold';
                slotItem.textContent = participant.nome;
                this.elements.slotItems.appendChild(slotItem);
            });
        }
    },
    
    /**
     * Inicia sorteio
     */
    startDraw: function() {
        if (this.state.isAnimating || this.state.participants.length === 0) return;
        
        this.state.isAnimating = true;
        
        // Desabilitar botão de iniciar
        if (this.elements.startButton) {
            this.elements.startButton.disabled = true;
        }
        
        // Esconder resultados anteriores
        if (this.elements.resultsContainer) {
            this.elements.resultsContainer.classList.add('hidden');
        }
        
        // Iniciar animação de slot machine
        let currentIndex = 0;
        this.state.slotInterval = setInterval(() => {
            currentIndex = (currentIndex + 1) % this.state.participants.length;
            
            if (this.elements.slotItems) {
                this.elements.slotItems.style.transform = `translateY(-${currentIndex * 40}px)`;
            }
        }, this.config.slotSpeed);
        
        // Selecionar ganhadores aleatoriamente
        this.state.winners = this.getRandomWinners(this.state.participants, this.config.qtdSorteados);
        
        // Parar animação após o tempo definido
        setTimeout(() => {
            clearInterval(this.state.slotInterval);
            this.showResults();
        }, this.config.animationDuration);
    },
    
    /**
     * Reinicia sorteio
     */
    resetDraw: function() {
        // Desabilitar botão de reiniciar
        if (this.elements.resetButton) {
            this.elements.resetButton.disabled = true;
        }
        
        // Habilitar botão de iniciar
        if (this.elements.startButton) {
            this.elements.startButton.disabled = false;
        }
        
        // Esconder resultados
        if (this.elements.resultsContainer) {
            this.elements.resultsContainer.classList.add('hidden');
        }
        
        // Resetar slot machine
        if (this.elements.slotItems) {
            this.elements.slotItems.style.transform = 'translateY(0)';
        }
        
        // Resetar status dos participantes
        if (this.elements.participantsTable) {
            const rows = this.elements.participantsTable.querySelectorAll('tr');
            rows.forEach(row => {
                const statusCell = row.querySelector('td:last-child span');
                if (statusCell) {
                    statusCell.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
                    statusCell.textContent = 'Não sorteado';
                }
            });
        }
        
        // Resetar estado
        this.state.isAnimating = false;
        this.state.winners = [];
    },
    
    /**
     * Seleciona ganhadores aleatórios
     */
    getRandomWinners: function(participants, count) {
        const shuffled = [...participants].sort(() => 0.5 - Math.random());
        return shuffled.slice(0, Math.min(count, participants.length));
    },
    
    /**
     * Mostra resultados
     */
    showResults: function() {
        // Limpar container de ganhadores
        if (this.elements.winnersContainer) {
            this.elements.winnersContainer.innerHTML = '';
            
            // Adicionar cada ganhador
            this.state.winners.forEach((winner, index) => {
                const winnerCard = document.createElement('div');
                winnerCard.className = 'bg-green-50 border border-green-200 rounded-lg p-4 text-center';
                winnerCard.innerHTML = `
                    <div class="text-lg font-bold mb-2">${index + 1}º Lugar</div>
                    <div class="text-xl font-bold mb-1">${winner.nome}</div>
                    <div class="text-sm text-gray-600">${this.formatCPF(winner.cpf)}</div>
                    <div class="text-sm text-gray-600">${this.formatWhatsApp(winner.whatsapp)}</div>
                `;
                this.elements.winnersContainer.appendChild(winnerCard);
                
                // Adicionar animação de entrada
                setTimeout(() => {
                    winnerCard.classList.add('animate-bounce');
                    setTimeout(() => {
                        winnerCard.classList.remove('animate-bounce');
                    }, 1000);
                }, index * 300);
            });
        }
        
        // Atualizar status na tabela
        if (this.elements.participantsTable) {
            this.state.winners.forEach((winner, index) => {
                const row = this.elements.participantsTable.querySelector(`tr[data-participant-id="${winner.id}"]`);
                if (row) {
                    const statusCell = row.querySelector('td:last-child span');
                    if (statusCell) {
                        statusCell.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                        statusCell.textContent = `${index + 1}º Lugar`;
                    }
                    
                    // Destacar linha
                    row.classList.add('bg-green-50');
                }
            });
        }
        
        // Mostrar resultados
        if (this.elements.resultsContainer) {
            this.elements.resultsContainer.classList.remove('hidden');
        }
        
        // Habilitar botão de reiniciar
        if (this.elements.resetButton) {
            this.elements.resetButton.disabled = false;
        }
        
        // Lançar confetes
        this.launchConfetti();
        
        // Atualizar estado
        this.state.isAnimating = false;
    },
    
    /**
     * Lança confetes
     */
    launchConfetti: function() {
        if (typeof confetti !== 'function') {
            console.error('Biblioteca confetti não encontrada');
            return;
        }
        
        // Configurações de confete
        const confettiSettings = {
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 },
            colors: this.config.confettiColors
        };
        
        // Lançar confete inicial
        confetti(confettiSettings);
        
        // Lançar confetes adicionais
        const duration = this.config.confettiDuration;
        const animationEnd = Date.now() + duration;
        const interval = setInterval(() => {
            const timeLeft = animationEnd - Date.now();
            
            if (timeLeft <= 0) {
                clearInterval(interval);
                return;
            }
            
            // Lançar confetes com menor intensidade
            confetti({
                ...confettiSettings,
                particleCount: 50,
                origin: { 
                    x: Math.random(),
                    y: Math.random() - 0.2
                }
            });
        }, 250);
    },
    
    /**
     * Formata CPF
     */
    formatCPF: function(cpf) {
        if (!cpf) return '';
        
        cpf = cpf.toString().replace(/\D/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    },
    
    /**
     * Formata WhatsApp
     */
    formatWhatsApp: function(whatsapp) {
        if (!whatsapp) return '';
        
        whatsapp = whatsapp.toString().replace(/\D/g, '');
        return whatsapp.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se estamos na página de sorteio
    if (document.getElementById('slot-machine')) {
        // Obter participantes do data attribute ou variável global
        let participants = [];
        
        if (typeof window.sorteioParticipantes !== 'undefined') {
            participants = window.sorteioParticipantes;
        }
        
        // Inicializar animação
        SorteioAnimation.init({
            qtdSorteados: typeof window.sorteioConfig !== 'undefined' ? window.sorteioConfig.qtdSorteados : 3
        });
        
        // Definir participantes
        if (participants.length > 0) {
            SorteioAnimation.setParticipants(participants);
        }
    }
});