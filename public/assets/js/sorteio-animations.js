/**
 * Sistema de Animações Premium para Sorteios
 * Implementa animações elegantes inspiradas em 21st.dev e Aceternity UI
 */

class SorteioAnimations {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            duration: 3000,
            slotMachineSpeed: 100,
            highlightDuration: 500,
            confettiDuration: 3000,
            soundEnabled: true,
            ...options
        };
        
        this.isAnimating = false;
        this.participantes = [];
        this.sorteados = [];
        this.animationFrameId = null;
        
        this.init();
    }
    
    init() {
        this.createAnimationStructure();
        this.loadConfettiLibrary();
    }
    
    /**
     * Cria estrutura HTML para as animações
     */
    createAnimationStructure() {
        if (!this.container) return;
        
        this.container.innerHTML = `
            <div class="sorteio-animation-wrapper">
                <!-- Área de participantes rolando -->
                <div class="slot-machine-container">
                    <div class="slot-machine-header">
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
                            🎲 Sorteando Participantes...
                        </h3>
                    </div>
                    
                    <div class="slot-machine-display">
                        <div class="slot-reel" id="slot-reel">
                            <!-- Participantes serão inseridos aqui -->
                        </div>
                        <div class="slot-machine-overlay">
                            <div class="selection-indicator"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Área de resultados -->
                <div class="results-container" id="results-container" style="display: none;">
                    <div class="results-header">
                        <h3 class="text-3xl font-bold text-center mb-6">
                            🎉 Parabéns aos Sorteados! 🎉
                        </h3>
                    </div>
                    
                    <div class="winners-grid" id="winners-grid">
                        <!-- Vencedores serão inseridos aqui -->
                    </div>
                    
                    <div class="celebration-area" id="celebration-area">
                        <!-- Área para confetes e celebração -->
                    </div>
                </div>
                
                <!-- Controles -->
                <div class="animation-controls" id="animation-controls">
                    <button id="start-sorteio" class="btn-primary">
                        <span class="btn-icon">🎲</span>
                        Iniciar Sorteio
                    </button>
                    
                    <button id="stop-sorteio" class="btn-secondary" style="display: none;">
                        <span class="btn-icon">⏹️</span>
                        Parar
                    </button>
                </div>
                
                <!-- Loading overlay -->
                <div class="loading-overlay" id="loading-overlay" style="display: none;">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p class="loading-text">Preparando sorteio...</p>
                    </div>
                </div>
            </div>
        `;
        
        this.bindEvents();
        this.injectStyles();
    }
    
    /**
     * Injeta estilos CSS para as animações
     */
    injectStyles() {
        const styleId = 'sorteio-animations-styles';
        if (document.getElementById(styleId)) return;
        
        const styles = `
            <style id="${styleId}">
                .sorteio-animation-wrapper {
                    position: relative;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 2rem;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                
                .slot-machine-container {
                    background: rgba(255,255,255,0.95);
                    border-radius: 15px;
                    padding: 2rem;
                    margin-bottom: 2rem;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255,255,255,0.2);
                }
                
                .slot-machine-display {
                    position: relative;
                    height: 300px;
                    background: #1a1a1a;
                    border-radius: 10px;
                    overflow: hidden;
                    border: 3px solid #ffd700;
                    box-shadow: inset 0 0 20px rgba(255,215,0,0.3);
                }
                
                .slot-reel {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    transition: transform 0.1s ease-out;
                }
                
                .participant-card {
                    min-height: 60px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(45deg, #2d3748, #4a5568);
                    color: white;
                    font-size: 1.2rem;
                    font-weight: bold;
                    border-bottom: 1px solid #4a5568;
                    padding: 1rem;
                    text-align: center;
                    transition: all 0.3s ease;
                }
                
                .participant-card.highlighted {
                    background: linear-gradient(45deg, #ffd700, #ffed4e);
                    color: #1a1a1a;
                    transform: scale(1.05);
                    box-shadow: 0 0 20px rgba(255,215,0,0.6);
                    animation: pulse 0.5s ease-in-out infinite alternate;
                }
                
                .slot-machine-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    pointer-events: none;
                }
                
                .selection-indicator {
                    position: absolute;
                    top: 50%;
                    left: 0;
                    right: 0;
                    height: 60px;
                    transform: translateY(-50%);
                    border: 3px solid #ffd700;
                    border-left: none;
                    border-right: none;
                    background: rgba(255,215,0,0.1);
                    box-shadow: 0 0 20px rgba(255,215,0,0.3);
                }
                
                .results-container {
                    background: rgba(255,255,255,0.95);
                    border-radius: 15px;
                    padding: 2rem;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255,255,255,0.2);
                    animation: slideInUp 0.8s ease-out;
                }
                
                .winners-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 1.5rem;
                    margin-bottom: 2rem;
                }
                
                .winner-card {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 1.5rem;
                    border-radius: 15px;
                    text-align: center;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    transform: translateY(20px);
                    opacity: 0;
                    animation: slideInUp 0.6s ease-out forwards;
                }
                
                .winner-card:nth-child(2) { animation-delay: 0.2s; }
                .winner-card:nth-child(3) { animation-delay: 0.4s; }
                .winner-card:nth-child(4) { animation-delay: 0.6s; }
                
                .winner-position {
                    font-size: 2rem;
                    font-weight: bold;
                    margin-bottom: 0.5rem;
                    color: #ffd700;
                }
                
                .winner-name {
                    font-size: 1.3rem;
                    font-weight: bold;
                    margin-bottom: 0.5rem;
                }
                
                .winner-details {
                    font-size: 0.9rem;
                    opacity: 0.9;
                }
                
                .animation-controls {
                    text-align: center;
                    margin-top: 2rem;
                }
                
                .btn-primary, .btn-secondary {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 1rem 2rem;
                    font-size: 1.1rem;
                    font-weight: bold;
                    border: none;
                    border-radius: 50px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    margin: 0 0.5rem;
                }
                
                .btn-primary {
                    background: linear-gradient(45deg, #4CAF50, #45a049);
                    color: white;
                    box-shadow: 0 4px 15px rgba(76,175,80,0.3);
                }
                
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(76,175,80,0.4);
                }
                
                .btn-secondary {
                    background: linear-gradient(45deg, #f44336, #da190b);
                    color: white;
                    box-shadow: 0 4px 15px rgba(244,67,54,0.3);
                }
                
                .btn-secondary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(244,67,54,0.4);
                }
                
                .loading-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 20px;
                    z-index: 1000;
                }
                
                .loading-spinner {
                    text-align: center;
                    color: white;
                }
                
                .spinner {
                    width: 50px;
                    height: 50px;
                    border: 4px solid rgba(255,255,255,0.3);
                    border-top: 4px solid #ffd700;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                }
                
                .loading-text {
                    font-size: 1.2rem;
                    font-weight: bold;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                @keyframes pulse {
                    0% { box-shadow: 0 0 20px rgba(255,215,0,0.6); }
                    100% { box-shadow: 0 0 30px rgba(255,215,0,0.9); }
                }
                
                @keyframes slideInUp {
                    from {
                        transform: translateY(30px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                @keyframes bounce {
                    0%, 20%, 53%, 80%, 100% {
                        transform: translate3d(0,0,0);
                    }
                    40%, 43% {
                        transform: translate3d(0, -30px, 0);
                    }
                    70% {
                        transform: translate3d(0, -15px, 0);
                    }
                    90% {
                        transform: translate3d(0, -4px, 0);
                    }
                }
                
                .celebration-area {
                    position: relative;
                    height: 200px;
                    overflow: hidden;
                }
                
                /* Responsividade */
                @media (max-width: 768px) {
                    .sorteio-animation-wrapper {
                        padding: 1rem;
                    }
                    
                    .slot-machine-container,
                    .results-container {
                        padding: 1rem;
                    }
                    
                    .slot-machine-display {
                        height: 200px;
                    }
                    
                    .winners-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .btn-primary, .btn-secondary {
                        padding: 0.8rem 1.5rem;
                        font-size: 1rem;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    /**
     * Vincula eventos aos controles
     */
    bindEvents() {
        const startBtn = document.getElementById('start-sorteio');
        const stopBtn = document.getElementById('stop-sorteio');
        
        if (startBtn) {
            startBtn.addEventListener('click', () => this.startSorteio());
        }
        
        if (stopBtn) {
            stopBtn.addEventListener('click', () => this.stopAnimation());
        }
    }
    
    /**
     * Carrega biblioteca de confetes
     */
    loadConfettiLibrary() {
        if (window.confetti) return;
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
        script.onload = () => {
            console.log('Biblioteca de confetes carregada');
        };
        document.head.appendChild(script);
    }
    
    /**
     * Define participantes para o sorteio
     */
    setParticipantes(participantes) {
        this.participantes = participantes;
        this.populateSlotMachine();
    }
    
    /**
     * Popula a máquina de slots com participantes
     */
    populateSlotMachine() {
        const reel = document.getElementById('slot-reel');
        if (!reel || !this.participantes.length) return;
        
        // Criar múltiplas cópias dos participantes para efeito de rolagem
        const repeatedParticipants = [];
        for (let i = 0; i < 10; i++) {
            repeatedParticipants.push(...this.participantes);
        }
        
        reel.innerHTML = repeatedParticipants.map((p, index) => `
            <div class="participant-card" data-id="${p.id}" data-index="${index}">
                <div class="participant-info">
                    <div class="participant-name">${p.nome}</div>
                    ${p.whatsapp ? `<div class="participant-detail">${p.whatsapp}</div>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Inicia animação de sorteio
     */
    async startSorteio() {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        this.showLoading();
        
        try {
            // Esconder resultados anteriores
            this.hideResults();
            
            // Mostrar controles apropriados
            this.toggleControls(true);
            
            // Aguardar um pouco para dar tempo do loading aparecer
            await this.delay(1000);
            
            this.hideLoading();
            
            // Iniciar animação de slot machine
            await this.animateSlotMachine();
            
        } catch (error) {
            console.error('Erro no sorteio:', error);
            this.hideLoading();
            this.isAnimating = false;
            this.toggleControls(false);
        }
    }
    
    /**
     * Anima a máquina de slots
     */
    async animateSlotMachine() {
        const reel = document.getElementById('slot-reel');
        if (!reel) return;
        
        let speed = this.options.slotMachineSpeed;
        let position = 0;
        let slowingDown = false;
        let targetPosition = 0;
        
        // Calcular posição alvo baseada nos sorteados
        if (this.sorteados.length > 0) {
            const firstWinner = this.sorteados[0];
            const cards = reel.querySelectorAll('.participant-card');
            
            for (let i = 0; i < cards.length; i++) {
                if (cards[i].dataset.id == firstWinner.id) {
                    targetPosition = i * 60; // altura de cada card
                    break;
                }
            }
        }
        
        return new Promise((resolve) => {
            const animate = () => {
                if (!this.isAnimating) {
                    resolve();
                    return;
                }
                
                // Acelerar no início
                if (speed > 20 && !slowingDown) {
                    speed -= 2;
                }
                
                // Começar a desacelerar após 2 segundos
                if (Date.now() - this.animationStartTime > 2000 && !slowingDown) {
                    slowingDown = true;
                }
                
                // Desacelerar gradualmente
                if (slowingDown) {
                    speed += 3;
                    
                    // Parar quando próximo da posição alvo
                    if (speed > 200 && Math.abs(position - targetPosition) < 120) {
                        this.stopSlotMachine(targetPosition);
                        resolve();
                        return;
                    }
                }
                
                position += 60; // mover para próximo participante
                reel.style.transform = `translateY(-${position}px)`;
                
                // Reset position para criar loop infinito
                if (position >= reel.scrollHeight - 300) {
                    position = 0;
                }
                
                setTimeout(animate, speed);
            };
            
            this.animationStartTime = Date.now();
            animate();
        });
    }
    
    /**
     * Para a máquina de slots na posição correta
     */
    stopSlotMachine(targetPosition) {
        const reel = document.getElementById('slot-reel');
        if (!reel) return;
        
        // Animar para posição final
        reel.style.transition = 'transform 1s ease-out';
        reel.style.transform = `translateY(-${targetPosition}px)`;
        
        // Destacar o participante selecionado
        setTimeout(() => {
            this.highlightWinner(targetPosition);
        }, 1000);
        
        // Mostrar resultados após highlight
        setTimeout(() => {
            this.showResults();
        }, 2000);
    }
    
    /**
     * Destaca o vencedor na slot machine
     */
    highlightWinner(position) {
        const reel = document.getElementById('slot-reel');
        if (!reel) return;
        
        const cards = reel.querySelectorAll('.participant-card');
        const targetIndex = Math.floor(position / 60);
        
        if (cards[targetIndex]) {
            cards[targetIndex].classList.add('highlighted');
            
            // Efeito sonoro (se habilitado)
            if (this.options.soundEnabled) {
                this.playWinSound();
            }
        }
    }
    
    /**
     * Mostra os resultados finais
     */
    showResults() {
        const resultsContainer = document.getElementById('results-container');
        const winnersGrid = document.getElementById('winners-grid');
        
        if (!resultsContainer || !winnersGrid) return;
        
        // Esconder slot machine
        const slotContainer = document.querySelector('.slot-machine-container');
        if (slotContainer) {
            slotContainer.style.display = 'none';
        }
        
        // Mostrar container de resultados
        resultsContainer.style.display = 'block';
        
        // Popular grid de vencedores
        winnersGrid.innerHTML = this.sorteados.map((winner, index) => `
            <div class="winner-card">
                <div class="winner-position">${this.getPositionEmoji(index + 1)} ${index + 1}º Lugar</div>
                <div class="winner-name">${winner.nome}</div>
                <div class="winner-details">
                    ${winner.whatsapp ? `📱 ${winner.whatsapp}<br>` : ''}
                    ${winner.cpf ? `🆔 ${winner.cpf}<br>` : ''}
                    ${winner.email ? `📧 ${winner.email}` : ''}
                </div>
            </div>
        `).join('');
        
        // Iniciar celebração
        setTimeout(() => {
            this.startCelebration();
        }, 1000);
        
        // Resetar controles
        this.isAnimating = false;
        this.toggleControls(false);
    }
    
    /**
     * Inicia celebração com confetes
     */
    startCelebration() {
        if (!window.confetti) return;
        
        // Confetes dourados
        const duration = this.options.confettiDuration;
        const end = Date.now() + duration;
        
        const colors = ['#ffd700', '#ffed4e', '#fff200', '#ffb347'];
        
        (function frame() {
            confetti({
                particleCount: 3,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: colors
            });
            
            confetti({
                particleCount: 3,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: colors
            });
            
            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
        
        // Confete central após 1 segundo
        setTimeout(() => {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: colors
            });
        }, 1000);
    }
    
    /**
     * Obtém emoji para posição
     */
    getPositionEmoji(position) {
        const emojis = ['🥇', '🥈', '🥉', '🏆', '🎖️'];
        return emojis[position - 1] || '🏅';
    }
    
    /**
     * Para animação
     */
    stopAnimation() {
        this.isAnimating = false;
        
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        this.toggleControls(false);
        this.hideLoading();
    }
    
    /**
     * Mostra/esconde loading
     */
    showLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) loading.style.display = 'flex';
    }
    
    hideLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) loading.style.display = 'none';
    }
    
    /**
     * Mostra/esconde resultados
     */
    hideResults() {
        const results = document.getElementById('results-container');
        if (results) results.style.display = 'none';
        
        const slotContainer = document.querySelector('.slot-machine-container');
        if (slotContainer) slotContainer.style.display = 'block';
    }
    
    /**
     * Alterna controles
     */
    toggleControls(animating) {
        const startBtn = document.getElementById('start-sorteio');
        const stopBtn = document.getElementById('stop-sorteio');
        
        if (startBtn) startBtn.style.display = animating ? 'none' : 'inline-flex';
        if (stopBtn) stopBtn.style.display = animating ? 'inline-flex' : 'none';
    }
    
    /**
     * Toca som de vitória
     */
    playWinSound() {
        // Criar som usando Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.3);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.log('Som não disponível:', error);
        }
    }
    
    /**
     * Utilitário para delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Define sorteados (para ser chamado externamente)
     */
    setSorteados(sorteados) {
        this.sorteados = sorteados;
    }
    
    /**
     * Reset completo da animação
     */
    reset() {
        this.stopAnimation();
        this.hideResults();
        this.hideLoading();
        
        const reel = document.getElementById('slot-reel');
        if (reel) {
            reel.style.transition = '';
            reel.style.transform = 'translateY(0)';
            
            // Remove highlights
            reel.querySelectorAll('.participant-card').forEach(card => {
                card.classList.remove('highlighted');
            });
        }
        
        this.sorteados = [];
    }
}

// Função auxiliar para inicializar animações
window.initSorteioAnimations = function(containerId, options) {
    return new SorteioAnimations(containerId, options);
};

// Export para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SorteioAnimations;
}