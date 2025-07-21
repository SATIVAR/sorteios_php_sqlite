/**
 * Scripts para Página de Participação - Sistema de Sorteios
 * Funcionalidades específicas para cadastro de participantes
 */

class ParticipacaoManager {
    constructor() {
        this.form = document.getElementById('participacao-form');
        this.submitBtn = document.getElementById('submit-btn');
        this.publicUrl = this.getPublicUrlFromPath();
        this.isSubmitting = false;
        this.validationErrors = {};
        
        // Configurações
        this.config = {
            debounceDelay: 500,
            maxRetries: 3,
            retryDelay: 1000
        };
        
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        this.setupEventListeners();
        this.setupRealTimeValidation();
        this.setupDuplicateCheck();
        this.startParticipantCounter();
        this.setupFormSubmission();
    }
    
    getPublicUrlFromPath() {
        const path = window.location.pathname;
        const matches = path.match(/\/participar\/([a-zA-Z0-9]+)/) || path.match(/participar\.php\?url=([a-zA-Z0-9]+)/);
        return matches ? matches[1] : new URLSearchParams(window.location.search).get('url');
    }
    
    setupEventListeners() {
        // Máscaras de input
        const whatsappInput = document.getElementById('whatsapp');
        if (whatsappInput) {
            SorteiosSystem.InputMasks.whatsapp(whatsappInput);
        }
        
        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            SorteiosSystem.InputMasks.cpf(cpfInput);
        }
        
        // Validação em tempo real
        this.form.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', SorteiosSystem.Utils.debounce(() => {
                this.clearFieldError(input);
            }, 300));
        });
    }
    
    setupRealTimeValidation() {
        const nomeInput = document.getElementById('nome');
        const cpfInput = document.getElementById('cpf');
        const emailInput = document.getElementById('email');
        const whatsappInput = document.getElementById('whatsapp');
        
        if (nomeInput) {
            nomeInput.addEventListener('input', SorteiosSystem.Utils.debounce(() => {
                this.validateNome(nomeInput.value);
            }, this.config.debounceDelay));
        }
        
        if (cpfInput) {
            cpfInput.addEventListener('input', SorteiosSystem.Utils.debounce(() => {
                this.validateCPF(cpfInput.value);
            }, this.config.debounceDelay));
        }
        
        if (emailInput) {
            emailInput.addEventListener('input', SorteiosSystem.Utils.debounce(() => {
                this.validateEmail(emailInput.value);
            }, this.config.debounceDelay));
        }
        
        if (whatsappInput) {
            whatsappInput.addEventListener('input', SorteiosSystem.Utils.debounce(() => {
                this.validateWhatsApp(whatsappInput.value);
            }, this.config.debounceDelay));
        }
    }
    
    setupDuplicateCheck() {
        const cpfInput = document.getElementById('cpf');
        const nomeInput = document.getElementById('nome');
        const whatsappInput = document.getElementById('whatsapp');
        
        const checkDuplicate = SorteiosSystem.Utils.debounce(() => {
            this.checkForDuplicate();
        }, 1000);
        
        if (cpfInput) {
            cpfInput.addEventListener('input', checkDuplicate);
        }
        
        if (nomeInput && whatsappInput) {
            nomeInput.addEventListener('input', checkDuplicate);
            whatsappInput.addEventListener('input', checkDuplicate);
        }
    }
    
    setupFormSubmission() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmit();
        });
    }
    
    startParticipantCounter() {
        this.updateParticipantCount();
        
        // Atualizar contador a cada 30 segundos
        setInterval(() => {
            this.updateParticipantCount();
        }, 30000);
    }
    
    async updateParticipantCount() {
        try {
            const response = await SorteiosSystem.AjaxHelper.get(
                'ajax/participantes.php?action=contar',
                { public_url: this.publicUrl }
            );
            
            if (response.success) {
                this.updateCounterDisplay(response.data);
            }
        } catch (error) {
            console.warn('Erro ao atualizar contador:', error);
        }
    }
    
    updateCounterDisplay(data) {
        const counterElements = document.querySelectorAll('[data-participant-count]');
        counterElements.forEach(element => {
            if (data.max_participantes > 0) {
                element.textContent = `${data.total}/${data.max_participantes} participantes`;
            } else {
                element.textContent = `${data.total} participantes`;
            }
        });
        
        // Verificar se o limite foi atingido
        if (data.max_participantes > 0 && data.total >= data.max_participantes) {
            this.handleLimitReached();
        }
    }
    
    handleLimitReached() {
        // Desabilitar formulário
        this.form.querySelectorAll('input, textarea, select, button').forEach(element => {
            element.disabled = true;
        });
        
        // Mostrar mensagem
        const message = document.createElement('div');
        message.className = 'alert alert-warning mb-6';
        message.innerHTML = `
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <p>Este sorteio atingiu o limite máximo de participantes.</p>
            </div>
        `;
        
        this.form.insertBefore(message, this.form.firstChild);
    }
    
    async checkForDuplicate() {
        const formData = new FormData(this.form);
        const cpf = formData.get('cpf');
        const nome = formData.get('nome');
        const whatsapp = formData.get('whatsapp');
        
        // Só verificar se tiver dados suficientes
        if (!nome || nome.length < 2) return;
        if (!cpf && !whatsapp) return;
        
        try {
            const response = await SorteiosSystem.AjaxHelper.post(
                'ajax/participantes.php?action=verificar_duplicata',
                {
                    public_url: this.publicUrl,
                    cpf: cpf || '',
                    nome: nome || '',
                    whatsapp: whatsapp || ''
                }
            );
            
            if (response.success && response.data.is_duplicate) {
                this.showDuplicateWarning(response.data.message);
            } else {
                this.hideDuplicateWarning();
            }
        } catch (error) {
            console.warn('Erro ao verificar duplicata:', error);
        }
    }
    
    showDuplicateWarning(message) {
        this.hideDuplicateWarning(); // Remove warning anterior
        
        const warning = document.createElement('div');
        warning.id = 'duplicate-warning';
        warning.className = 'alert alert-warning mb-6';
        warning.innerHTML = `
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <p>${message}</p>
            </div>
        `;
        
        this.form.insertBefore(warning, this.form.firstChild);
        
        // Desabilitar botão de envio
        if (this.submitBtn) {
            this.submitBtn.disabled = true;
            this.submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    hideDuplicateWarning() {
        const warning = document.getElementById('duplicate-warning');
        if (warning) {
            warning.remove();
        }
        
        // Reabilitar botão de envio se não houver outros erros
        if (this.submitBtn && Object.keys(this.validationErrors).length === 0) {
            this.submitBtn.disabled = false;
            this.submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    
    async handleFormSubmit() {
        if (this.isSubmitting) return;
        
        // Validar formulário completo
        if (!this.validateForm()) {
            notifications.show('Por favor, corrija os erros no formulário', 'error');
            return;
        }
        
        this.isSubmitting = true;
        this.setSubmitButtonLoading(true);
        
        try {
            const formData = new FormData(this.form);
            formData.append('public_url', this.publicUrl);
            
            const response = await SorteiosSystem.AjaxHelper.postForm(
                'ajax/participantes.php?action=cadastrar',
                formData
            );
            
            if (response.success) {
                this.handleSubmitSuccess(response);
            } else {
                this.handleSubmitError(response);
            }
        } catch (error) {
            console.error('Erro ao enviar formulário:', error);
            notifications.show('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.isSubmitting = false;
            this.setSubmitButtonLoading(false);
        }
    }
    
    handleSubmitSuccess(response) {
        // Mostrar mensagem de sucesso
        notifications.show(response.message, 'success', 8000);
        
        // Substituir formulário por mensagem de sucesso
        this.showSuccessMessage(response.data);
        
        // Atualizar contador
        this.updateParticipantCount();
        
        // Scroll para o topo
        SorteiosSystem.Utils.scrollToTop();
        
        // Confetes!
        this.showConfetti();
    }
    
    handleSubmitError(response) {
        if (response.errors) {
            // Mostrar erros específicos dos campos
            Object.keys(response.errors).forEach(field => {
                this.showFieldError(field, response.errors[field]);
            });
        }
        
        notifications.show(response.message || 'Erro ao processar cadastro', 'error');
    }
    
    showSuccessMessage(data) {
        const successHtml = `
            <div class="text-center p-8">
                <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Cadastro Realizado com Sucesso!
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Olá <strong>${data.nome}</strong>! Você foi cadastrado no sorteio.
                    Boa sorte!
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Mantenha este link salvo para acompanhar o sorteio. O resultado será divulgado em breve.
                    </p>
                </div>
                <button onclick="window.location.reload()" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Cadastrar Outro Participante
                </button>
            </div>
        `;
        
        // Encontrar o card principal e substituir conteúdo
        const cardBody = this.form.closest('.bg-white, .card');
        if (cardBody) {
            cardBody.innerHTML = successHtml;
        }
    }
    
    showConfetti() {
        if (typeof confetti !== 'undefined') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
            
            // Segundo burst após 200ms
            setTimeout(() => {
                confetti({
                    particleCount: 50,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 }
                });
            }, 200);
            
            // Terceiro burst após 400ms
            setTimeout(() => {
                confetti({
                    particleCount: 50,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 }
                });
            }, 400);
        }
    }
    
    setSubmitButtonLoading(loading) {
        if (!this.submitBtn) return;
        
        if (loading) {
            this.submitBtn.disabled = true;
            this.submitBtn.innerHTML = `
                <span class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enviando...
                </span>
            `;
        } else {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = `
                <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Participar do Sorteio
                </span>
            `;
        }
    }
    
    validateForm() {
        let isValid = true;
        this.validationErrors = {};
        
        // Validar todos os campos
        this.form.querySelectorAll('input[required], textarea[required], select[required]').forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(input) {
        const value = input.value.trim();
        const fieldName = input.name;
        let isValid = true;
        
        // Limpar erro anterior
        this.clearFieldError(input);
        
        // Validação de campo obrigatório
        if (input.hasAttribute('required') && !value) {
            this.showFieldError(fieldName, 'Este campo é obrigatório');
            isValid = false;
        }
        
        // Validações específicas por tipo
        if (value) {
            switch (input.type) {
                case 'email':
                    if (!SorteiosSystem.FormValidator.validateEmail(value)) {
                        this.showFieldError(fieldName, 'Email inválido');
                        isValid = false;
                    }
                    break;
            }
            
            // Validações específicas por nome do campo
            switch (fieldName) {
                case 'cpf':
                    if (!SorteiosSystem.FormValidator.validateCPF(value)) {
                        this.showFieldError(fieldName, 'CPF inválido');
                        isValid = false;
                    }
                    break;
                case 'whatsapp':
                    if (!SorteiosSystem.FormValidator.validateWhatsApp(value)) {
                        this.showFieldError(fieldName, 'WhatsApp deve estar no formato (11) 9xxxx-xxxx');
                        isValid = false;
                    }
                    break;
                case 'nome':
                    if (value.length < 2) {
                        this.showFieldError(fieldName, 'Nome deve ter pelo menos 2 caracteres');
                        isValid = false;
                    }
                    break;
            }
        }
        
        return isValid;
    }
    
    validateNome(value) {
        if (!value || value.length < 2) {
            this.showFieldError('nome', 'Nome deve ter pelo menos 2 caracteres');
            return false;
        }
        this.clearFieldError(document.getElementById('nome'));
        return true;
    }
    
    validateCPF(value) {
        if (value && !SorteiosSystem.FormValidator.validateCPF(value)) {
            this.showFieldError('cpf', 'CPF inválido');
            return false;
        }
        this.clearFieldError(document.getElementById('cpf'));
        return true;
    }
    
    validateEmail(value) {
        if (value && !SorteiosSystem.FormValidator.validateEmail(value)) {
            this.showFieldError('email', 'Email inválido');
            return false;
        }
        this.clearFieldError(document.getElementById('email'));
        return true;
    }
    
    validateWhatsApp(value) {
        if (value && !SorteiosSystem.FormValidator.validateWhatsApp(value)) {
            this.showFieldError('whatsapp', 'WhatsApp deve estar no formato (11) 9xxxx-xxxx');
            return false;
        }
        this.clearFieldError(document.getElementById('whatsapp'));
        return true;
    }
    
    showFieldError(fieldName, message) {
        this.validationErrors[fieldName] = message;
        
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (!input) return;
        
        // Adicionar classe de erro ao input
        input.classList.add('border-red-500', 'focus:ring-red-500');
        input.classList.remove('border-gray-300', 'focus:ring-blue-500');
        
        // Remover mensagem de erro anterior
        const existingError = input.parentNode.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Adicionar nova mensagem de erro
        const errorElement = document.createElement('p');
        errorElement.className = 'form-error';
        errorElement.textContent = message;
        
        input.parentNode.appendChild(errorElement);
    }
    
    clearFieldError(input) {
        if (!input) return;
        
        const fieldName = input.name;
        delete this.validationErrors[fieldName];
        
        // Remover classes de erro
        input.classList.remove('border-red-500', 'focus:ring-red-500');
        input.classList.add('border-gray-300', 'focus:ring-blue-500');
        
        // Remover mensagem de erro
        const errorElement = input.parentNode.querySelector('.form-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Só inicializar se estivermos na página de participação
    if (document.getElementById('participacao-form')) {
        window.participacaoManager = new ParticipacaoManager();
    }
});