<?php
/**
 * Template de Formulário de Sorteios (Criar/Editar)
 */

$is_edit = ($action === 'edit');
$form_title = $is_edit ? 'Editar Sorteio' : 'Novo Sorteio';
$submit_text = $is_edit ? 'Atualizar Sorteio' : 'Criar Sorteio';
?>

<!-- Cabeçalho da página -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $form_title; ?></h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            <?php echo $is_edit ? 'Edite as configurações do seu sorteio' : 'Configure seu novo sorteio com campos personalizados'; ?>
        </p>
    </div>
    
    <a href="sorteios.php" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </a>
</div>

<!-- Formulário -->
<form method="POST" class="space-y-8" id="sorteio-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <!-- Informações Básicas -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Informações Básicas</h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Nome do Sorteio -->
            <div class="lg:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nome do Sorteio *
                </label>
                <input type="text" 
                       id="nome" 
                       name="nome" 
                       value="<?php echo htmlspecialchars($sorteio['nome'] ?? ''); ?>"
                       required
                       maxlength="255"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="Ex: Sorteio de Natal 2024">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nome que aparecerá para os participantes</p>
            </div>
            
            <!-- Descrição -->
            <div class="lg:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Descrição
                </label>
                <textarea id="descricao" 
                          name="descricao" 
                          rows="3"
                          maxlength="1000"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                          placeholder="Descreva seu sorteio, prêmios e regras..."><?php echo htmlspecialchars($sorteio['descricao'] ?? ''); ?></textarea>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Informações adicionais sobre o sorteio (opcional)</p>
            </div>
            
            <!-- Data de Início -->
            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Data de Início
                </label>
                <input type="datetime-local" 
                       id="data_inicio" 
                       name="data_inicio" 
                       value="<?php echo $sorteio['data_inicio'] ? date('Y-m-d\TH:i', strtotime($sorteio['data_inicio'])) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quando as inscrições começam (opcional)</p>
            </div>
            
            <!-- Data de Fim -->
            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Data de Fim
                </label>
                <input type="datetime-local" 
                       id="data_fim" 
                       name="data_fim" 
                       value="<?php echo $sorteio['data_fim'] ? date('Y-m-d\TH:i', strtotime($sorteio['data_fim'])) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quando as inscrições encerram (opcional)</p>
            </div>
            
            <!-- Limite de Participantes -->
            <div>
                <label for="max_participantes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Limite de Participantes
                </label>
                <input type="number" 
                       id="max_participantes" 
                       name="max_participantes" 
                       value="<?php echo $sorteio['max_participantes'] ?? 0; ?>"
                       min="0"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">0 = sem limite</p>
            </div>
            
            <!-- Quantidade de Sorteados -->
            <div>
                <label for="qtd_sorteados" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Quantidade de Sorteados *
                </label>
                <input type="number" 
                       id="qtd_sorteados" 
                       name="qtd_sorteados" 
                       value="<?php echo $sorteio['qtd_sorteados'] ?? 1; ?>"
                       min="1"
                       required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quantas pessoas serão sorteadas</p>
            </div>
            
            <!-- Status -->
            <div class="lg:col-span-2">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status *
                </label>
                <select id="status" 
                        name="status" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="ativo" <?php echo ($sorteio['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>
                        Ativo - Aceita participantes
                    </option>
                    <option value="pausado" <?php echo ($sorteio['status'] ?? '') === 'pausado' ? 'selected' : ''; ?>>
                        Pausado - Não aceita novos participantes
                    </option>
                    <option value="finalizado" <?php echo ($sorteio['status'] ?? '') === 'finalizado' ? 'selected' : ''; ?>>
                        Finalizado - Sorteio encerrado
                    </option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Configuração de Campos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Campos do Formulário de Participação</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Configure quais informações os participantes devem fornecer</p>
        
        <!-- Campos Padrão -->
        <div class="space-y-4 mb-8">
            <h4 class="font-medium text-gray-900 dark:text-white">Campos Padrão</h4>
            
            <!-- Nome (sempre obrigatório) -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Nome Completo</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Campo obrigatório (sempre ativo)</p>
                    </div>
                </div>
                <span class="text-sm font-medium text-green-600 dark:text-green-400">Obrigatório</span>
            </div>
            
            <!-- WhatsApp -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_whatsapp" 
                           name="campo_whatsapp" 
                           <?php echo (isset($sorteio['campos_config']['whatsapp']['enabled']) && $sorteio['campos_config']['whatsapp']['enabled']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-3">
                    <div>
                        <label for="campo_whatsapp" class="font-medium text-gray-900 dark:text-white cursor-pointer">WhatsApp</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Número de telefone/WhatsApp</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_whatsapp_required" 
                           name="campo_whatsapp_required" 
                           <?php echo (isset($sorteio['campos_config']['whatsapp']['required']) && $sorteio['campos_config']['whatsapp']['required']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-2">
                    <label for="campo_whatsapp_required" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">Obrigatório</label>
                </div>
            </div>
            
            <!-- CPF -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_cpf" 
                           name="campo_cpf" 
                           <?php echo (isset($sorteio['campos_config']['cpf']['enabled']) && $sorteio['campos_config']['cpf']['enabled']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-3">
                    <div>
                        <label for="campo_cpf" class="font-medium text-gray-900 dark:text-white cursor-pointer">CPF</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Documento de identificação</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_cpf_required" 
                           name="campo_cpf_required" 
                           <?php echo (isset($sorteio['campos_config']['cpf']['required']) && $sorteio['campos_config']['cpf']['required']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-2">
                    <label for="campo_cpf_required" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">Obrigatório</label>
                </div>
            </div>
            
            <!-- Email -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_email" 
                           name="campo_email" 
                           <?php echo (isset($sorteio['campos_config']['email']['enabled']) && $sorteio['campos_config']['email']['enabled']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-3">
                    <div>
                        <label for="campo_email" class="font-medium text-gray-900 dark:text-white cursor-pointer">E-mail</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Endereço de e-mail</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="campo_email_required" 
                           name="campo_email_required" 
                           <?php echo (isset($sorteio['campos_config']['email']['required']) && $sorteio['campos_config']['email']['required']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-2">
                    <label for="campo_email_required" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">Obrigatório</label>
                </div>
            </div>
        </div>
        
        <!-- Campos Personalizados -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="font-medium text-gray-900 dark:text-white">Campos Personalizados</h4>
                <button type="button" id="add-custom-field" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">
                    + Adicionar Campo
                </button>
            </div>
            
            <div id="custom-fields-container" class="space-y-3">
                <!-- Campos personalizados serão adicionados aqui via JavaScript -->
            </div>
            
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Adicione campos extras como idade, cidade, profissão, etc.
            </p>
        </div>
    </div>
    
    <!-- URL Pública (apenas para edição) -->
    <?php if ($is_edit && isset($sorteio['public_url'])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">URL Pública</h3>
            
            <div class="flex items-center space-x-3">
                <input type="text" 
                       value="<?php echo getBaseUrl(); ?>/participar/<?php echo $sorteio['public_url']; ?>" 
                       readonly
                       class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400">
                
                <button type="button" 
                        id="copy-public-url" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                    Copiar
                </button>
                
                <a href="<?php echo getBaseUrl(); ?>/participar/<?php echo $sorteio['public_url']; ?>" 
                   target="_blank" 
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-500">
                    Abrir
                </a>
            </div>
            
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Compartilhe esta URL para que as pessoas possam participar do sorteio
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Botões de Ação -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
        <a href="sorteios.php" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-500">
            Cancelar
        </a>
        
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <?php echo $submit_text; ?>
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gerenciamento de campos personalizados
    const customFieldsContainer = document.getElementById('custom-fields-container');
    const addCustomFieldBtn = document.getElementById('add-custom-field');
    let customFieldCounter = 0;
    
    // Carregar campos personalizados existentes (para edição)
    <?php if ($is_edit && isset($sorteio['campos_config'])): ?>
        <?php foreach ($sorteio['campos_config'] as $key => $config): ?>
            <?php if (strpos($key, 'custom_') === 0): ?>
                addCustomField('<?php echo $config['label']; ?>', '<?php echo $config['type']; ?>', <?php echo $config['required'] ? 'true' : 'false'; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    addCustomFieldBtn.addEventListener('click', function() {
        addCustomField();
    });
    
    function addCustomField(nome = '', tipo = 'text', required = false) {
        const fieldId = 'custom_field_' + customFieldCounter++;
        
        const fieldHtml = `
            <div class="custom-field-item p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Campo</label>
                        <input type="text" 
                               name="campos_personalizados[${fieldId}][nome]" 
                               value="${nome}"
                               placeholder="Ex: Idade, Cidade, Profissão"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                        <select name="campos_personalizados[${fieldId}][tipo]" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white text-sm">
                            <option value="text" ${tipo === 'text' ? 'selected' : ''}>Texto</option>
                            <option value="number" ${tipo === 'number' ? 'selected' : ''}>Número</option>
                            <option value="email" ${tipo === 'email' ? 'selected' : ''}>E-mail</option>
                            <option value="tel" ${tipo === 'tel' ? 'selected' : ''}>Telefone</option>
                            <option value="date" ${tipo === 'date' ? 'selected' : ''}>Data</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="campos_personalizados[${fieldId}][required]" 
                                   ${required ? 'checked' : ''}
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 mr-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Obrigatório</label>
                        </div>
                        
                        <button type="button" 
                                class="remove-custom-field text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 ml-3"
                                title="Remover campo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        customFieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
        
        // Adicionar event listener para remoção
        const newField = customFieldsContainer.lastElementChild;
        newField.querySelector('.remove-custom-field').addEventListener('click', function() {
            newField.remove();
        });
    }
    
    // Copiar URL pública (apenas para edição)
    <?php if ($is_edit): ?>
        const copyUrlBtn = document.getElementById('copy-public-url');
        if (copyUrlBtn) {
            copyUrlBtn.addEventListener('click', function() {
                const urlInput = this.previousElementSibling;
                urlInput.select();
                document.execCommand('copy');
                
                const originalText = this.textContent;
                this.textContent = 'Copiado!';
                this.classList.add('bg-green-600');
                this.classList.remove('bg-blue-600');
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-blue-600');
                }, 2000);
            });
        }
    <?php endif; ?>
    
    // Validação do formulário
    const form = document.getElementById('sorteio-form');
    form.addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        const qtdSorteados = document.getElementById('qtd_sorteados').value;
        
        if (!nome) {
            e.preventDefault();
            alert('Por favor, informe o nome do sorteio.');
            document.getElementById('nome').focus();
            return;
        }
        
        if (!qtdSorteados || qtdSorteados < 1) {
            e.preventDefault();
            alert('Por favor, informe uma quantidade válida de sorteados.');
            document.getElementById('qtd_sorteados').focus();
            return;
        }
        
        // Validar datas
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;
        
        if (dataInicio && dataFim && new Date(dataInicio) >= new Date(dataFim)) {
            e.preventDefault();
            alert('A data de fim deve ser posterior à data de início.');
            return;
        }
    });
    
    // Habilitar/desabilitar campos obrigatórios baseado na checkbox principal
    ['whatsapp', 'cpf', 'email'].forEach(campo => {
        const enableCheckbox = document.getElementById(`campo_${campo}`);
        const requiredCheckbox = document.getElementById(`campo_${campo}_required`);
        
        enableCheckbox.addEventListener('change', function() {
            requiredCheckbox.disabled = !this.checked;
            if (!this.checked) {
                requiredCheckbox.checked = false;
            }
        });
        
        // Estado inicial
        requiredCheckbox.disabled = !enableCheckbox.checked;
    });
});
</script>