<?php
/**
 * Página Pública de Participação - Sistema de Sorteios
 * Interface responsiva para cadastro de participantes via URL pública
 */

// Definir constante do sistema
define('SISTEMA_SORTEIOS', true);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/validator.php';
require_once 'includes/rate_limiter.php';

// Inicializar sessão
session_start();

// Verificar se o sistema está instalado
if (!isSystemInstalled()) {
    header('Location: wizard.php');
    exit;
}

// Obter URL pública da query string
$publicUrl = $_GET['url'] ?? '';

if (empty($publicUrl)) {
    http_response_code(404);
    die('Sorteio não encontrado');
}

// Buscar sorteio pela URL pública
$db = getDatabase();
$sorteio = $db->fetchOne(
    "SELECT * FROM sorteios WHERE public_url = ? AND status = 'ativo'",
    [$publicUrl]
);

if (!$sorteio) {
    http_response_code(404);
    $error_message = 'Sorteio não encontrado ou não está mais ativo';
    include 'templates/error_public.php';
    exit;
}

// Verificar se o sorteio ainda está dentro do prazo
$now = date('Y-m-d H:i:s');
if ($sorteio['data_fim'] && $sorteio['data_fim'] < $now) {
    $error_message = 'Este sorteio já foi encerrado';
    include 'templates/error_public.php';
    exit;
}

// Verificar limite de participantes
$totalParticipantes = $db->fetchOne(
    "SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?",
    [$sorteio['id']]
)['total'];

$limitExcedido = false;
if ($sorteio['max_participantes'] > 0 && $totalParticipantes >= $sorteio['max_participantes']) {
    $limitExcedido = true;
}<?php
/**
 * Página Pública de Participação - Sistema de Sorteios
 * Interface responsiva para cadastro de participantes via URL pública
 */

// Definir constante do sistema
define('SISTEMA_SORTEIOS', true);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/validator.php';
require_once 'includes/rate_limiter.php';

// Inicializar sessão
session_start();

// Verificar se o sistema está instalado
if (!isSystemInstalled()) {
    header('Location: wizard.php');
    exit;
}

// Obter URL pública da query string
$publicUrl = $_GET['url'] ?? '';

if (empty($publicUrl)) {
    http_response_code(404);
    die('Sorteio não encontrado');
}

// Buscar sorteio pela URL pública
$db = getDatabase();
$sorteio = $db->fetchOne(
    "SELECT * FROM sorteios WHERE public_url = ? AND status = 'ativo'",
    [$publicUrl]
);

if (!$sorteio) {
    http_response_code(404);
    $error_message = 'Sorteio não encontrado ou não está mais ativo';
    include 'templates/error_public.php';
    exit;
}

// Verificar se o sorteio ainda está dentro do prazo
$now = date('Y-m-d H:i:s');
if ($sorteio['data_fim'] && $sorteio['data_fim'] < $now) {
    $error_message = 'Este sorteio já foi encerrado';
    include 'templates/error_public.php';
    exit;
}

// Verificar limite de participantes
$totalParticipantes = $db->fetchOne(
    "SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?",
    [$sorteio['id']]
)['total'];

$limitExcedido = false;
if ($sorteio['max_participantes'] > 0 && $totalParticipantes >= $sorteio['max_participantes']) {
    $limitExcedido = true;
}

// Processar configuração de campos
$camposConfig = json_decode($sorteio['campos_config'] ?? '{}', true);
$camposConfig = array_merge([
    'nome' => ['enabled' => true, 'required' => true],
    'whatsapp' => ['enabled' => true, 'required' => false],
    'cpf' => ['enabled' => false, 'required' => false],
    'email' => ['enabled' => false, 'required' => false],
    'campos_extras' => []
], $camposConfig);

// Processar formulário se enviado
$formSubmitted = false;
$success = false;
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['geral'] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        // Verificar rate limiting avançado
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitCheck = checkParticipationRateLimit($ip, RATE_LIMIT_PARTICIPACAO, RATE_LIMIT_WINDOW);
        
        if (!$rateLimitCheck['allowed']) {
            $errors['geral'] = $rateLimitCheck['reason'];
            if (isset($rateLimitCheck['time_until_reset']) && $rateLimitCheck['time_until_reset'] > 0) {
                $minutes = ceil($rateLimitCheck['time_until_reset'] / 60);
                $errors['geral'] .= " Tente novamente em {$minutes} minuto(s).";
            }
        } else {
            // Verificar novamente se o limite não foi excedido
            $totalParticipantes = $db->fetchOne(
                "SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?",
                [$sorteio['id']]
            )['total'];
            
            if ($sorteio['max_participantes'] > 0 && $totalParticipantes >= $sorteio['max_participantes']) {
                $errors['geral'] = 'Este sorteio já atingiu o limite máximo de participantes.';
            } else {
                // Registrar tentativa de participação
                recordParticipationAttempt($ip);
                
                // Processar dados do formulário
                $result = processParticipantForm($_POST, $sorteio, $camposConfig);
                
                if ($result['success']) {
                    $success = true;
                    logActivity('PARTICIPANTE_CADASTRADO', "Sorteio ID: {$sorteio['id']}, Nome: {$result['data']['nome']}");
                } else {
                    $errors = $result['errors'];
                    $formData = $result['data'];
                }
            }
        }
    }
}

// Configurar variáveis para o template
$page_title = $sorteio['nome'];
$show_header = true;
$body_class = 'bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800';

// Incluir header
include 'templates/header.php';
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header do Sorteio -->
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?php echo htmlspecialchars($sorteio['nome']); ?>
            </h1>
            <?php if ($sorteio['descricao']): ?>
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-4">
                <?php echo nl2br(htmlspecialchars($sorteio['descricao'])); ?>
            </p>
            <?php endif; ?>
            
            <!-- Informações do Sorteio -->
            <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <?php if ($sorteio['max_participantes'] > 0): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <?php echo $totalParticipantes; ?>/<?php echo $sorteio['max_participantes']; ?> participantes
                </div>
                <?php endif; ?>
                
                <?php if ($sorteio['qtd_sorteados'] > 1): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <?php echo $sorteio['qtd_sorteados']; ?> ganhadores
                </div>
                <?php endif; ?>
                
                <?php if ($sorteio['data_fim']): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Até <?php echo formatDateBR($sorteio['data_fim']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <?php if ($success): ?>
            <!-- Mensagem de Sucesso -->
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Cadastro Realizado com Sucesso!
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Você foi cadastrado no sorteio <strong><?php echo htmlspecialchars($sorteio['nome']); ?></strong>.
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
            
            <?php elseif ($limitExcedido): ?>
            <!-- Limite Excedido -->
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Sorteio Lotado
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Este sorteio já atingiu o limite máximo de <strong><?php echo $sorteio['max_participantes']; ?> participantes</strong>.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Fique atento aos próximos sorteios!
                </p>
            </div>
            
            <?php else: ?>
            <!-- Formulário de Cadastro -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Participar do Sorteio
                </h2>
                
                <?php if (!empty($errors['geral'])): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-800 dark:text-red-200"><?php echo htmlspecialchars($errors['geral']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6" id="participacao-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Campo Nome (sempre obrigatório) -->
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nome Completo *
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               value="<?php echo htmlspecialchars($formData['nome'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="Digite seu nome completo"
                               required>
                        <?php if (!empty($errors['nome'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['nome']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Campo WhatsApp -->
                    <?php if ($camposConfig['whatsapp']['enabled']): ?>
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            WhatsApp <?php echo $camposConfig['whatsapp']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="tel" 
                               id="whatsapp" 
                               name="whatsapp" 
                               value="<?php echo htmlspecialchars($formData['whatsapp'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="(11) 99999-9999"
                               <?php echo $camposConfig['whatsapp']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['whatsapp'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['whatsapp']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campo Email -->
                    <?php if ($camposConfig['email']['enabled']): ?>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <?php echo $camposConfig['email']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="seu@email.com"
                               <?php echo $camposConfig['email']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campo CPF -->
                    <?php if ($camposConfig['cpf']['enabled']): ?>
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            CPF <?php echo $camposConfig['cpf']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="text" 
                               id="cpf" 
                               name="cpf" 
                               value="<?php echo htmlspecialchars($formData['cpf'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="000.000.000-00"
                               <?php echo $camposConfig['cpf']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['cpf'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['cpf']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campos Extras -->
                    <?php if (!empty($camposConfig['campos_extras'])): ?>
                        <?php foreach ($camposConfig['campos_extras'] as $index => $campo): ?>
                        <div>
                            <label for="extra_<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo htmlspecialchars($campo['label']); ?> <?php echo $campo['required'] ? '*' : ''; ?>
                            </label>
                            <?php if ($campo['type'] === 'textarea'): ?>
                            <textarea id="extra_<?php echo $index; ?>" 
                                      name="campos_extras[<?php echo $index; ?>]" 
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                                      placeholder="<?php echo htmlspecialchars($campo['placeholder'] ?? ''); ?>"
                                      rows="3"
                                      <?php echo $campo['required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($formData['campos_extras'][$index] ?? ''); ?></textarea>
                            <?php else: ?>
                            <input type="<?php echo htmlspecialchars($campo['type'] ?? 'text'); ?>" 
                                   id="extra_<?php echo $index; ?>" 
                                   name="campos_extras[<?php echo $index; ?>]" 
                                   value="<?php echo htmlspecialchars($formData['campos_extras'][$index] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                                   placeholder="<?php echo htmlspecialchars($campo['placeholder'] ?? ''); ?>"
                                   <?php echo $campo['required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                            <?php if (!empty($errors['campos_extras'][$index])): ?>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['campos_extras'][$index]); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Botão de Envio -->
                    <div class="pt-4">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                id="submit-btn">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Participar do Sorteio
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Informações Adicionais -->
        <?php if (!$success && !$limitExcedido): ?>
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Informações Importantes
            </h3>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Todos os dados fornecidos são confidenciais e utilizados apenas para este sorteio.</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>O resultado do sorteio será divulgado após o encerramento das inscrições.</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Certifique-se de que seus dados estão corretos antes de enviar o formulário.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts específicos da página -->
<script src="<?php echo getBaseUrl(); ?>/assets/js/participar.js"></script>

<?php
// Incluir footer
include 'templates/footer.php';

/**
 * Função para processar o formulário de participação
 */
function processParticipantForm($postData, $sorteio, $camposConfig) {
    $validator = getValidator();
    
    // Definir regras de validação baseadas na configuração
    $rules = [
        'nome' => [
            'required' => true,
            'sanitize' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ]
    ];
    
    // Adicionar regras condicionais baseadas na configuração
    if ($camposConfig['whatsapp']['enabled']) {
        $rules['whatsapp'] = [
            'required' => $camposConfig['whatsapp']['required'],
            'sanitize' => 'whatsapp',
            'whatsapp' => true
        ];
    }
    
    if ($camposConfig['email']['enabled']) {
        $rules['email'] = [
            'required' => $camposConfig['email']['required'],
            'sanitize' => 'email',
            'email' => true
        ];
    }
    
    if ($camposConfig['cpf']['enabled']) {
        $rules['cpf'] = [
            'required' => $camposConfig['cpf']['required'],
            'sanitize' => 'cpf',
            'cpf' => true
        ];
    }
    
    // Validar dados principais
    $result = validateFormData($postData, $rules);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    // Processar campos extras
    $camposExtras = [];
    if (!empty($camposConfig['campos_extras']) && !empty($postData['campos_extras'])) {
        foreach ($camposConfig['campos_extras'] as $index => $campo) {
            $value = $postData['campos_extras'][$index] ?? '';
            
            if ($campo['required'] && empty($value)) {
                $result['success'] = false;
                $result['errors']['campos_extras'][$index] = "O campo {$campo['label']} é obrigatório";
                continue;
            }
            
            if (!empty($value)) {
                $camposExtras[$index] = sanitizeInput($value);
            }
        }
    }
    
    if (!$result['success']) {
        return $result;
    }
    
    // Verificar duplicatas (por CPF se habilitado, senão por nome + whatsapp)
    $db = getDatabase();
    
    if ($camposConfig['cpf']['enabled'] && !empty($data['cpf'])) {
        $existing = $db->fetchOne(
            "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?",
            [$sorteio['id'], $data['cpf']]
        );
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['cpf' => 'Este CPF já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    } else {
        // Verificar por nome + whatsapp
        $whereClause = "sorteio_id = ? AND nome = ?";
        $params = [$sorteio['id'], $data['nome']];
        
        if (!empty($data['whatsapp'])) {
            $whereClause .= " AND whatsapp = ?";
            $params[] = $data['whatsapp'];
        }
        
        $existing = $db->fetchOne("SELECT id FROM participantes WHERE $whereClause", $params);
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['geral' => 'Você já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    }
    
    // Inserir participante
    try {
        $db->beginTransaction();
        
        $participanteId = $db->insert(
            "INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email, campos_extras, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
            [
                $sorteio['id'],
                $data['nome'],
                $data['whatsapp'] ?? null,
                $data['cpf'] ?? null,
                $data['email'] ?? null,
                !empty($camposExtras) ? json_encode($camposExtras) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        $db->commit();
        
        return [
            'success' => true,
            'errors' => [],
            'data' => array_merge($data, ['id' => $participanteId])
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        logSystem("Erro ao cadastrar participante: " . $e->getMessage(), 'ERROR');
        
        return [
            'success' => false,
            'errors' => ['geral' => 'Erro interno. Tente novamente em alguns instantes.'],
            'data' => $data
        ];
    }
}
?>
// Proce
ssar configuração de campos
$camposConfig = json_decode($sorteio['campos_config'] ?? '{}', true);
$camposConfig = array_merge([
    'nome' => ['enabled' => true, 'required' => true],
    'whatsapp' => ['enabled' => true, 'required' => false],
    'cpf' => ['enabled' => false, 'required' => false],
    'email' => ['enabled' => false, 'required' => false],
    'campos_extras' => []
], $camposConfig);

// Processar formulário se enviado
$formSubmitted = false;
$success = false;
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['geral'] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        // Verificar rate limiting avançado
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitCheck = checkParticipationRateLimit($ip, RATE_LIMIT_PARTICIPACAO, RATE_LIMIT_WINDOW);
        
        if (!$rateLimitCheck['allowed']) {
            $errors['geral'] = $rateLimitCheck['reason'];
            if (isset($rateLimitCheck['time_until_reset']) && $rateLimitCheck['time_until_reset'] > 0) {
                $minutes = ceil($rateLimitCheck['time_until_reset'] / 60);
                $errors['geral'] .= " Tente novamente em {$minutes} minuto(s).";
            }
        } else {
            // Verificar novamente se o limite não foi excedido
            $totalParticipantes = $db->fetchOne(
                "SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?",
                [$sorteio['id']]
            )['total'];
            
            if ($sorteio['max_participantes'] > 0 && $totalParticipantes >= $sorteio['max_participantes']) {
                $errors['geral'] = 'Este sorteio já atingiu o limite máximo de participantes.';
            } else {
                // Registrar tentativa de participação
                recordParticipationAttempt($ip);
                
                // Processar dados do formulário
                $result = processParticipantForm($_POST, $sorteio, $camposConfig);
                
                if ($result['success']) {
                    $success = true;
                    logActivity('PARTICIPANTE_CADASTRADO', "Sorteio ID: {$sorteio['id']}, Nome: {$result['data']['nome']}");
                } else {
                    $errors = $result['errors'];
                    $formData = $result['data'];
                }
            }
        }
    }
}

// Configurar variáveis para o template
$page_title = $sorteio['nome'];
$show_header = true;
$body_class = 'bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800';

// Incluir header
include 'templates/header.php';
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header do Sorteio -->
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?php echo htmlspecialchars($sorteio['nome']); ?>
            </h1>
            <?php if ($sorteio['descricao']): ?>
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-4">
                <?php echo nl2br(htmlspecialchars($sorteio['descricao'])); ?>
            </p>
            <?php endif; ?>
            
            <!-- Informações do Sorteio -->
            <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <?php if ($sorteio['max_participantes'] > 0): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <?php echo $totalParticipantes; ?>/<?php echo $sorteio['max_participantes']; ?> participantes
                </div>
                <?php endif; ?>
                
                <?php if ($sorteio['qtd_sorteados'] > 1): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <?php echo $sorteio['qtd_sorteados']; ?> ganhadores
                </div>
                <?php endif; ?>
                
                <?php if ($sorteio['data_fim']): ?>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Até <?php echo formatDateBR($sorteio['data_fim']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <?php if ($success): ?>
            <!-- Mensagem de Sucesso -->
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Cadastro Realizado com Sucesso!
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Você foi cadastrado no sorteio <strong><?php echo htmlspecialchars($sorteio['nome']); ?></strong>.
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
            
            <?php elseif ($limitExcedido): ?>
            <!-- Limite Excedido -->
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Sorteio Lotado
                </h2>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    Este sorteio já atingiu o limite máximo de <strong><?php echo $sorteio['max_participantes']; ?> participantes</strong>.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Fique atento aos próximos sorteios!
                </p>
            </div>
            
            <?php else: ?>
            <!-- Formulário de Cadastro -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Participar do Sorteio
                </h2>
                
                <?php if (!empty($errors['geral'])): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-800 dark:text-red-200"><?php echo htmlspecialchars($errors['geral']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6" id="participacao-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Campo Nome (sempre obrigatório) -->
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nome Completo *
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               value="<?php echo htmlspecialchars($formData['nome'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="Digite seu nome completo"
                               required>
                        <?php if (!empty($errors['nome'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['nome']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Campo WhatsApp -->
                    <?php if ($camposConfig['whatsapp']['enabled']): ?>
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            WhatsApp <?php echo $camposConfig['whatsapp']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="tel" 
                               id="whatsapp" 
                               name="whatsapp" 
                               value="<?php echo htmlspecialchars($formData['whatsapp'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="(11) 99999-9999"
                               <?php echo $camposConfig['whatsapp']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['whatsapp'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['whatsapp']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campo Email -->
                    <?php if ($camposConfig['email']['enabled']): ?>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <?php echo $camposConfig['email']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="seu@email.com"
                               <?php echo $camposConfig['email']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campo CPF -->
                    <?php if ($camposConfig['cpf']['enabled']): ?>
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            CPF <?php echo $camposConfig['cpf']['required'] ? '*' : ''; ?>
                        </label>
                        <input type="text" 
                               id="cpf" 
                               name="cpf" 
                               value="<?php echo htmlspecialchars($formData['cpf'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                               placeholder="000.000.000-00"
                               <?php echo $camposConfig['cpf']['required'] ? 'required' : ''; ?>>
                        <?php if (!empty($errors['cpf'])): ?>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['cpf']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campos Extras -->
                    <?php if (!empty($camposConfig['campos_extras'])): ?>
                        <?php foreach ($camposConfig['campos_extras'] as $index => $campo): ?>
                        <div>
                            <label for="extra_<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo htmlspecialchars($campo['label']); ?> <?php echo $campo['required'] ? '*' : ''; ?>
                            </label>
                            <?php if ($campo['type'] === 'textarea'): ?>
                            <textarea id="extra_<?php echo $index; ?>" 
                                      name="campos_extras[<?php echo $index; ?>]" 
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                                      placeholder="<?php echo htmlspecialchars($campo['placeholder'] ?? ''); ?>"
                                      rows="3"
                                      <?php echo $campo['required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($formData['campos_extras'][$index] ?? ''); ?></textarea>
                            <?php else: ?>
                            <input type="<?php echo htmlspecialchars($campo['type'] ?? 'text'); ?>" 
                                   id="extra_<?php echo $index; ?>" 
                                   name="campos_extras[<?php echo $index; ?>]" 
                                   value="<?php echo htmlspecialchars($formData['campos_extras'][$index] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                                   placeholder="<?php echo htmlspecialchars($campo['placeholder'] ?? ''); ?>"
                                   <?php echo $campo['required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                            <?php if (!empty($errors['campos_extras'][$index])): ?>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($errors['campos_extras'][$index]); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Botão de Envio -->
                    <div class="pt-4">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                id="submit-btn">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Participar do Sorteio
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Informações Adicionais -->
        <?php if (!$success && !$limitExcedido): ?>
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Informações Importantes
            </h3>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Todos os dados fornecidos são confidenciais e utilizados apenas para este sorteio.</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>O resultado do sorteio será divulgado após o encerramento das inscrições.</p>
                </div>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Certifique-se de que seus dados estão corretos antes de enviar o formulário.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts específicos da página -->
<script src="<?php echo getBaseUrl(); ?>/assets/js/participar.js"></script>

<?php
// Incluir footer
include 'templates/footer.php';

/**
 * Função para processar o formulário de participação
 */
function processParticipantForm($postData, $sorteio, $camposConfig) {
    $validator = getValidator();
    
    // Definir regras de validação baseadas na configuração
    $rules = [
        'nome' => [
            'required' => true,
            'sanitize' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ]
    ];
    
    // Adicionar regras condicionais baseadas na configuração
    if ($camposConfig['whatsapp']['enabled']) {
        $rules['whatsapp'] = [
            'required' => $camposConfig['whatsapp']['required'],
            'sanitize' => 'whatsapp',
            'whatsapp' => true
        ];
    }
    
    if ($camposConfig['email']['enabled']) {
        $rules['email'] = [
            'required' => $camposConfig['email']['required'],
            'sanitize' => 'email',
            'email' => true
        ];
    }
    
    if ($camposConfig['cpf']['enabled']) {
        $rules['cpf'] = [
            'required' => $camposConfig['cpf']['required'],
            'sanitize' => 'cpf',
            'cpf' => true
        ];
    }
    
    // Validar dados principais
    $result = validateFormData($postData, $rules);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    // Processar campos extras
    $camposExtras = [];
    if (!empty($camposConfig['campos_extras']) && !empty($postData['campos_extras'])) {
        foreach ($camposConfig['campos_extras'] as $index => $campo) {
            $value = $postData['campos_extras'][$index] ?? '';
            
            if ($campo['required'] && empty($value)) {
                $result['success'] = false;
                $result['errors']['campos_extras'][$index] = "O campo {$campo['label']} é obrigatório";
                continue;
            }
            
            if (!empty($value)) {
                $camposExtras[$index] = sanitizeInput($value);
            }
        }
    }
    
    if (!$result['success']) {
        return $result;
    }
    
    // Verificar duplicatas (por CPF se habilitado, senão por nome + whatsapp)
    $db = getDatabase();
    
    if ($camposConfig['cpf']['enabled'] && !empty($data['cpf'])) {
        $existing = $db->fetchOne(
            "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?",
            [$sorteio['id'], $data['cpf']]
        );
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['cpf' => 'Este CPF já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    } else {
        // Verificar por nome + whatsapp
        $whereClause = "sorteio_id = ? AND nome = ?";
        $params = [$sorteio['id'], $data['nome']];
        
        if (!empty($data['whatsapp'])) {
            $whereClause .= " AND whatsapp = ?";
            $params[] = $data['whatsapp'];
        }
        
        $existing = $db->fetchOne("SELECT id FROM participantes WHERE $whereClause", $params);
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['geral' => 'Você já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    }
    
    // Inserir participante
    try {
        $db->beginTransaction();
        
        $participanteId = $db->insert(
            "INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email, campos_extras, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
            [
                $sorteio['id'],
                $data['nome'],
                $data['whatsapp'] ?? null,
                $data['cpf'] ?? null,
                $data['email'] ?? null,
                !empty($camposExtras) ? json_encode($camposExtras) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        $db->commit();
        
        return [
            'success' => true,
            'errors' => [],
            'data' => array_merge($data, ['id' => $participanteId])
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        logSystem("Erro ao cadastrar participante: " . $e->getMessage(), 'ERROR');
        
        return [
            'success' => false,
            'errors' => ['geral' => 'Erro interno. Tente novamente em alguns instantes.'],
            'data' => $data
        ];
    }
}
?>