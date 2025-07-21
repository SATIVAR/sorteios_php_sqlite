/**
 * JavaScript para Gerenciamento de Participantes
 * Funcionalidades interativas e validações
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeParticipantesPage();
});

function initializeParticipantesPage() {
    // Inicializar máscaras de input
    initializeInputMasks();
    
    // Inicializar validações de formulário
    initializeFormValidation();
    
    // Inicializar funcionalidades de busca
    initializeSearch();
    
    // Inicializar tooltips e modais
    initializeUIComponents();
}

/**
 * Inicializar máscaras de input
 */
function initializeInputMasks() {
    // Máscara para WhatsApp
    const whatsappFields = document.querySelectorAll('input[name="whatsapp"]');
    whatsappFields.forEach(field => {
        field.addEventListener('input', function(e) {
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
        
        // Validação em tempo real
        field.addEventListener('blur', function(e) {
            const whatsapp = e.target.value.replace(/\D/g, '');
            if (e.target.value && (whatsapp.length < 10 || whatsapp.length > 11)) {
                showFieldError(e.target, 'WhatsApp deve ter 10 ou 11 dígitos');
            } else {
                clearFieldError(e.target);
            }
        });
    });
    
    // Máscara para CPF
    const cpfFields = document.querySelectorAll('input[name="cpf"]');
    cpfFields.forEach(field => {
        field.addEventListener('input', function(e) {
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
        
        // Validação em tempo real
        field.addEventListener('blur', function(e) {
            if (e.target.value && !isValidCPF(e.target.value)) {
                showFieldError(e.target, 'CPF inválido');
            } else {
                clearFieldError(e.target);
            }
        });
    });
}

/**
 * Inicializar validações de formulário
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Validar formulário completo
 */
function validateForm(form) {
    let isValid = true;
    const errors = [];
    
    // Validar nome
    const nomeField = form.querySelector('input[name="nome"]');
    if (nomeField) {
        const nome = nomeField.value.trim();
        if (nome.length < 2) {
            showFieldError(nomeField, 'Nome deve ter pelo menos 2 caracteres');
            errors.push('Nome inválido');
            isValid = false;
        } else {
            clearFieldError(nomeField);
        }
    }
    
    // Validar WhatsApp se preenchido
    const whatsappField = form.querySelector('input[name="whatsapp"]');
    if (whatsappField && whatsappField.value.trim()) {
        const whatsapp = whatsappField.value.replace(/\D/g, '');
        if (whatsapp.length < 10 || whatsapp.length > 11) {
            showFieldError(whatsappField, 'WhatsApp deve ter 10 ou 11 dígitos');
            errors.push('WhatsApp inválido');
            isValid = false;
        } else {
            clearFieldError(whatsappField);
        }
    }
    
    // Validar CPF se preenchido
    const cpfField = form.querySelector('input[name="cpf"]');
    if (cpfField && cpfField.value.trim()) {
        if (!isValidCPF(cpfField.value)) {
            showFieldError(cpfField, 'CPF inválido');
            errors.push('CPF inválido');
            isValid = false;
        } else {
            clearFieldError(cpfField);
        }
    }
    
    // Validar email se preenchido
    const emailField = form.querySelector('input[name="email"]');
    if (emailField && emailField.value.trim()) {
        if (!isValidEmail(emailField.value)) {
            showFieldError(emailField, 'Email inválido');
            errors.push('Email inválido');
            isValid = false;
        } else {
            clearFieldError(emailField);
        }
    }
    
    // Mostrar resumo de erros se houver
    if (!isValid) {
        showNotification('Corrija os erros no formulário: ' + errors.join(', '), 'error');
    }
    
    return isValid;
}

/**
 * Mostrar erro em campo específico
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    field.classList.remove('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error mt-1 text-sm text-red-600 dark:text-red-400';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Limpar erro de campo
 */
function clearFieldError(field) {
    field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    field.classList.add('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-500');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Inicializar funcionalidades de busca
 */
function initializeSearch() {
    const searchForms = document.querySelectorAll('form[method="GET"]');
    searchForms.forEach(form => {
        const searchInput = form.querySelector('input[name="search"]');
        if (searchInput) {
            // Busca em tempo real com debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (searchInput.value.length >= 3 || searchInput.value.length === 0) {
                        form.submit();
                    }
                }, 500);
            });
        }
    });
}

/**
 * Inicializar componentes de UI
 */
function initializeUIComponents() {
    // Inicializar tooltips
    initializeTooltips();
    
    // Inicializar modais
    initializeModals();
    
    // Inicializar botões de cópia
    initializeCopyButtons();
}

/**
 * Inicializar tooltips
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

/**
 * Inicializar modais
 */
function initializeModals() {
    // Modal de confirmação de exclusão
    const deleteModal = document.getElementById('delete-modal');
    if (deleteModal) {
        const confirmButton = deleteModal.querySelector('#confirm-delete');
        const cancelButton = deleteModal.querySelector('#cancel-delete');
        
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                hideModal('delete-modal');
            });
        }
        
        // Fechar modal ao clicar fora
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                hideModal('delete-modal');
            }
        });
    }
}

/**
 * Inicializar botões de cópia
 */
function initializeCopyButtons() {
    const copyButtons = document.querySelectorAll('[onclick*="copyToClipboard"]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('onclick').match(/copyToClipboard\('([^']+)'\)/)[1];
            copyToClipboard(targetId);
        });
    });
}

/**
 * Funções utilitárias
 */

/**
 * Validar CPF
 */
function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    
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
    if (remainder !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

/**
 * Validar email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Copiar texto para área de transferência
 */
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.select();
    element.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showNotification('Copiado para a área de transferência!', 'success');
    } catch (err) {
        showNotification('Erro ao copiar. Tente selecionar e copiar manualmente.', 'error');
    }
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white shadow-lg transform transition-all duration-300 translate-x-full`;
    
    // Definir cor baseada no tipo
    switch (type) {
        case 'success':
            notification.classList.add('bg-green-500');
            break;
        case 'error':
            notification.classList.add('bg-red-500');
            break;
        case 'warning':
            notification.classList.add('bg-yellow-500');
            break;
        default:
            notification.classList.add('bg-blue-500');
    }
    
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="mr-2">${getNotificationIcon(type)}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Remover automaticamente
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

/**
 * Obter ícone para notificação
 */
function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
        case 'error':
            return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
        case 'warning':
            return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
        default:
            return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
    }
}

/**
 * Mostrar tooltip
 */
function showTooltip(e) {
    const element = e.target;
    const title = element.getAttribute('title');
    if (!title) return;
    
    // Remover title para evitar tooltip nativo
    element.setAttribute('data-original-title', title);
    element.removeAttribute('title');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'fixed z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg pointer-events-none';
    tooltip.textContent = title;
    tooltip.id = 'custom-tooltip';
    
    document.body.appendChild(tooltip);
    
    // Posicionar tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

/**
 * Esconder tooltip
 */
function hideTooltip(e) {
    const element = e.target;
    const originalTitle = element.getAttribute('data-original-title');
    if (originalTitle) {
        element.setAttribute('title', originalTitle);
        element.removeAttribute('data-original-title');
    }
    
    const tooltip = document.getElementById('custom-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Mostrar modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Esconder modal
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

/**
 * Confirmar exclusão de participante
 */
function confirmDelete(participantId, participantName) {
    const modal = document.getElementById('delete-modal');
    const nameElement = document.getElementById('delete-participant-name');
    const confirmButton = document.getElementById('confirm-delete');
    
    if (modal && nameElement && confirmButton) {
        nameElement.textContent = participantName;
        
        // Remover listeners anteriores
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        
        // Adicionar novo listener
        newConfirmButton.addEventListener('click', function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('action', 'delete');
            currentUrl.searchParams.set('participante_id', participantId);
            window.location.href = currentUrl.toString();
        });
        
        showModal('delete-modal');
    }
}

/**
 * Exportar participantes
 */
function exportParticipants(sorteioId, format) {
    const url = `participantes.php?action=export&sorteio_id=${sorteioId}&format=${format}`;
    
    // Criar link temporário para download
    const link = document.createElement('a');
    link.href = url;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification(`Exportação ${format.toUpperCase()} iniciada!`, 'success');
}

/**
 * Atualizar contadores em tempo real
 */
function updateCounters() {
    // Esta função pode ser expandida para atualizar contadores via AJAX
    // Por enquanto, apenas recarrega a página se necessário
}

/**
 * Filtrar tabela localmente (para melhor UX)
 */
function filterTable(searchTerm) {
    const table = document.querySelector('table tbody');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Expor funções globalmente para uso em templates
window.confirmDelete = confirmDelete;
window.copyToClipboard = copyToClipboard;
window.exportParticipants = exportParticipants;
window.showNotification = showNotification;