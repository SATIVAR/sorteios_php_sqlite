<?php
/**
 * Template: Formulário de Adição Manual de Participante
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$campos_config = json_decode($sorteio['campos_config'], true) ?? [];
?>

<!-- Cabeçalho da página -->
<div class="mb-8">
    <nav class="flex mb-2" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="participantes.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Participantes
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <a href="participantes.php?action=view&sorteio_id=<?php echo $sorteio['id']; ?>" 
                       class="ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <?php echo htmlspecialchars($sorteio['nome']); ?>
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-gray-500 dark:text-gray-400">Adicionar Participante</span>
                </div>
            </li>
        </ol>
    </nav>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Adicionar Participante</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Adicione um participante manualmente ao sorteio: <strong><?php echo htmlspecialchars($sorteio['nome']); ?></strong>
    </p>
</div>

<!-- Informações do sorteio -->
<div class="mb-6 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                Configuração do Sorteio
            </h3>
            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <p>Os campos abaixo são baseados na configuração deste sorteio.</p>
                <ul class="list-disc list-inside mt-1">
                    <li>Campos obrigatórios estão marcados com *</li>
                    <?php if ($sorteio['max_participantes'] > 0): ?>
                        <li>Limite máximo: <?php echo $sorteio['max_participantes']; ?> participantes</li>
                    <?php endif; ?>
                    <li>Status do sorteio: <strong><?php echo ucfirst($sorteio['status']); ?></strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Formulário -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <form method="POST" class="p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <!-- Nome (sempre obrigatório) -->
        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome Completo *
            </label>
            <input type="text" 
                   id="nome" 
                   name="nome" 
                   required
                   maxlength="255"
                   class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                   placeholder="Digite o nome completo do participante">
        </div>
        
        <!-- WhatsApp -->
        <?php if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']): ?>
            <div>
                <label for="whatsapp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    WhatsApp <?php echo (isset($campos_config['whatsapp']['required']) && $campos_config['whatsapp']['required']) ? '*' : ''; ?>
                </label>
                <input type="tel" 
                       id="whatsapp" 
                       name="whatsapp" 
                       <?php echo (isset($campos_config['whatsapp']['required']) && $campos_config['whatsapp']['required']) ? 'required' : ''; ?>
                       maxlength="20"
                       class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="(11) 99999-9999">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Digite apenas números ou use o formato (11) 99999-9999
                </p>
            </div>
        <?php endif; ?>
        
        <!-- CPF -->
        <?php if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']): ?>
            <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    CPF <?php echo (isset($campos_config['cpf']['required']) && $campos_config['cpf']['required']) ? '*' : ''; ?>
                </label>
                <input type="text" 
                       id="cpf" 
                       name="cpf" 
                       <?php echo (isset($campos_config['cpf']['required']) && $campos_config['cpf']['required']) ? 'required' : ''; ?>
                       maxlength="14"
                       class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="000.000.000-00">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Digite apenas números ou use o formato 000.000.000-00
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Email -->
        <?php if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']): ?>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email <?php echo (isset($campos_config['email']['required']) && $campos_config['email']['required']) ? '*' : ''; ?>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       <?php echo (isset($campos_config['email']['required']) && $campos_config['email']['required']) ? 'required' : ''; ?>
                       class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="exemplo@email.com">
            </div>
        <?php endif; ?>
        
        <!-- Campos personalizados -->
        <?php foreach ($campos_config as $campo_nome => $config): ?>
            <?php if (strpos($campo_nome, 'custom_') === 0 && isset($config['enabled']) && $config['enabled']): ?>
                <div>
                    <label for="<?php echo htmlspecialchars($campo_nome); ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?php echo htmlspecialchars($config['label']); ?> <?php echo (isset($config['required']) && $config['required']) ? '*' : ''; ?>
                    </label>
                    
                    <?php if ($config['type'] === 'textarea'): ?>
                        <textarea id="<?php echo htmlspecialchars($campo_nome); ?>" 
                                  name="<?php echo htmlspecialchars($campo_nome); ?>" 
                                  <?php echo (isset($config['required']) && $config['required']) ? 'required' : ''; ?>
                                  rows="3"
                                  class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    <?php else: ?>
                        <input type="<?php echo htmlspecialchars($config['type'] ?? 'text'); ?>" 
                               id="<?php echo htmlspecialchars($campo_nome); ?>" 
                               name="<?php echo htmlspecialchars($campo_nome); ?>" 
                               <?php echo (isset($config['required']) && $config['required']) ? 'required' : ''; ?>
                               class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <!-- Botões de ação -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <button type="submit" 
                    class="flex-1 btn-primary inline-flex justify-center items-center px-6 py-3 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Adicionar Participante
            </button>
            
            <a href="participantes.php?action=view&sorteio_id=<?php echo $sorteio['id']; ?>" 
               class="flex-1 btn-secondary inline-flex justify-center items-center px-6 py-3 text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </form>
</div>

<!-- Informações adicionais -->
<div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Informações Importantes:</h3>
    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
        <li>• O participante será adicionado imediatamente ao sorteio</li>
        <li>• Todos os dados serão validados antes da inclusão</li>
        <?php if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']): ?>
            <li>• Não é possível adicionar participantes com CPF duplicado no mesmo sorteio</li>
        <?php endif; ?>
        <li>• O participante receberá o mesmo tratamento que os cadastros via URL pública</li>
        <?php if ($sorteio['max_participantes'] > 0): ?>
            <li>• Verifique se o limite de participantes não foi atingido</li>
        <?php endif; ?>
    </ul>
</div>

<script>
// Máscaras para campos
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para WhatsApp
    const whatsappField = document.getElementById('whatsapp');
    if (whatsappField) {
        whatsappField.addEventListener('input', function(e) {
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
    }
    
    // Máscara para CPF
    const cpfField = document.getElementById('cpf');
    if (cpfField) {
        cpfField.addEventListener('input', function(e) {
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
    }
    
    // Validação em tempo real
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        if (nome.length < 2) {
            e.preventDefault();
            alert('O nome deve ter pelo menos 2 caracteres.');
            return;
        }
        
        // Validação de CPF se habilitado e preenchido
        if (cpfField && cpfField.value.trim()) {
            const cpf = cpfField.value.replace(/\D/g, '');
            if (cpf.length !== 11 || !isValidCPF(cpf)) {
                e.preventDefault();
                alert('CPF inválido. Verifique os dados digitados.');
                return;
            }
        }
        
        // Validação de WhatsApp se habilitado e preenchido
        if (whatsappField && whatsappField.value.trim()) {
            const whatsapp = whatsappField.value.replace(/\D/g, '');
            if (whatsapp.length < 10 || whatsapp.length > 11) {
                e.preventDefault();
                alert('WhatsApp deve ter 10 ou 11 dígitos.');
                return;
            }
        }
    });
});

// Função para validar CPF
function isValidCPF(cpf) {
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
</script>