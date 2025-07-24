<?php
/**
 * Sistema de Sorteios - Wizard de Instalação Inicial
 * Interface completa de configuração inicial do sistema
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/validator.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é uma reinstalação
$isReinstall = isset($_GET['reinstall']) && $_GET['reinstall'] == '1';

// Se já estiver instalado e não for reinstalação, redireciona
if (isSystemInstalled() && !$isReinstall) {
    header('Location: index.php');
    exit;
}

// Se for reinstalação, limpar dados anteriores
if ($isReinstall && !isset($_SESSION['reinstall_confirmed'])) {
    // Mostrar confirmação de reinstalação
    if (!isset($_POST['confirm_reinstall'])) {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reinstalação - Sistema de Sorteios</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Confirmar Reinstalação</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Esta ação irá <strong>apagar todos os dados</strong> existentes e reinstalar o sistema do zero.
                        Todos os sorteios, participantes e configurações serão perdidos.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-6">
                        <p class="text-xs text-yellow-800">
                            <strong>Atenção:</strong> Esta ação não pode ser desfeita. 
                            Certifique-se de ter um backup dos dados importantes.
                        </p>
                    </div>
                    <form method="POST" class="space-y-4">
                        <button type="submit" name="confirm_reinstall" value="1" 
                                class="w-full bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700">
                            Sim, Reinstalar Sistema
                        </button>
                        <a href="index.php" 
                           class="block w-full bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400 text-center">
                            Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Confirmação recebida - limpar instalação
        require_once 'includes/install_check.php';
        cleanIncompleteInstallation();
        $_SESSION['reinstall_confirmed'] = true;
        
        // Remover banco existente se houver
        if (file_exists(DB_PATH)) {
            unlink(DB_PATH);
        }
    }
}

$step = (int) ($_GET['step'] ?? $_POST['step'] ?? 1);
$errors = [];
$success = false;
$systemChecks = [];

// Função para verificar requisitos do sistema
function checkSystemRequirements() {
    $checks = [
        'php_version' => [
            'name' => 'Versão do PHP',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'message' => 'PHP 7.4+ (atual: ' . PHP_VERSION . ')',
            'required' => true
        ],
        'pdo_sqlite' => [
            'name' => 'PDO SQLite',
            'status' => extension_loaded('pdo_sqlite'),
            'message' => 'Extensão PDO SQLite',
            'required' => true
        ],
        'json' => [
            'name' => 'JSON',
            'status' => extension_loaded('json'),
            'message' => 'Extensão JSON',
            'required' => true
        ],
        'mbstring' => [
            'name' => 'Multibyte String',
            'status' => extension_loaded('mbstring'),
            'message' => 'Extensão mbstring',
            'required' => false
        ],
        'data_writable' => [
            'name' => 'Diretório de dados',
            'status' => is_writable(dirname(__FILE__)) || createSystemDirectories(),
            'message' => 'Permissão de escrita no diretório',
            'required' => true
        ],
        'memory_limit' => [
            'name' => 'Limite de memória',
            'status' => (int) ini_get('memory_limit') >= 64,
            'message' => 'Mínimo 64MB (atual: ' . ini_get('memory_limit') . ')',
            'required' => false
        ]
    ];
    
    return $checks;
}

// Processar formulário
if ($_POST) {
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        $validator = getValidator();
        
        switch ($step) {
            case 1:
                // Verificar requisitos do sistema
                $systemChecks = checkSystemRequirements();
                $hasRequiredErrors = false;
                
                foreach ($systemChecks as $check) {
                    if ($check['required'] && !$check['status']) {
                        $errors[] = "Requisito obrigatório não atendido: " . $check['message'];
                        $hasRequiredErrors = true;
                    }
                }
                
                if (!$hasRequiredErrors) {
                    // Criar diretórios necessários
                    if (!createSystemDirectories()) {
                        $errors[] = 'Erro ao criar diretórios do sistema. Verifique as permissões.';
                    }
                }
                break;
                
            case 2:
                // Validar dados da empresa
                $rules = [
                    'nome_empresa' => [
                        'required' => true,
                        'sanitize' => 'string',
                        'min_length' => 2,
                        'max_length' => 100
                    ],
                    'regras_padrao' => [
                        'sanitize' => 'string',
                        'max_length' => 2000,
                        'allow_html' => true
                    ]
                ];
                
                $data = $validator->validateArray($_POST, $rules);
                
                if ($validator->hasErrors()) {
                    $errors = array_merge($errors, array_values($validator->getFormattedErrors()));
                } else {
                    $_SESSION['wizard_data']['nome_empresa'] = $data['nome_empresa'];
                    $_SESSION['wizard_data']['regras_padrao'] = $data['regras_padrao'] ?: 'Regulamento padrão do sorteio.';
                }
                break;
                
            case 3:
                // Validar credenciais de admin
                $rules = [
                    'admin_email' => [
                        'required' => true,
                        'sanitize' => 'email',
                        'email' => true
                    ],
                    'admin_password' => [
                        'required' => true,
                        'min_length' => 6,
                        'max_length' => 50
                    ],
                    'confirm_password' => [
                        'required' => true
                    ]
                ];
                
                $data = $validator->validateArray($_POST, $rules);
                
                // Verificar se senhas coincidem
                if ($data['admin_password'] !== $data['confirm_password']) {
                    $errors[] = 'As senhas não coincidem';
                }
                
                if ($validator->hasErrors()) {
                    $errors = array_merge($errors, array_values($validator->getFormattedErrors()));
                } else {
                    $_SESSION['wizard_data']['admin_email'] = $data['admin_email'];
                    $_SESSION['wizard_data']['admin_password'] = $data['admin_password'];
                }
                break;
                
            case 4:
                // Finalizar instalação
                if (!isset($_SESSION['wizard_data']) || 
                    empty($_SESSION['wizard_data']['nome_empresa']) || 
                    empty($_SESSION['wizard_data']['admin_email']) || 
                    empty($_SESSION['wizard_data']['admin_password'])) {
                    
                    $errors[] = 'Dados da instalação incompletos. Reinicie o processo.';
                    $step = 1;
                    unset($_SESSION['wizard_data']);
                } else {
                    try {
                        // Incluir arquivos necessários
                        require_once 'includes/database.php';
                        require_once 'includes/database_schema.php';
                        
                        // Inicializar banco de dados
                        if (initializeDatabase()) {
                            // Salvar configurações no banco
                            $config = [
                                'nome_empresa' => $_SESSION['wizard_data']['nome_empresa'],
                                'regras_padrao' => $_SESSION['wizard_data']['regras_padrao'],
                                'admin_email' => $_SESSION['wizard_data']['admin_email'],
                                'admin_password' => $_SESSION['wizard_data']['admin_password']
                            ];
                            
                            if (saveSystemConfig($config)) {
                                // Log da instalação
                                logSystem('Sistema instalado com sucesso', 'INFO');
                                logActivity('SYSTEM_INSTALL', 'Wizard de instalação concluído');
                                
                                // Limpar dados da sessão
                                unset($_SESSION['wizard_data']);
                                $success = true;
                                
                                // Criar backup inicial
                                try {
                                    $db = Database::getInstance();
                                    $db->createBackup('Backup inicial pós-instalação');
                                } catch (Exception $e) {
                                    // Não falha a instalação por causa do backup
                                    logSystem('Aviso: Não foi possível criar backup inicial: ' . $e->getMessage(), 'WARNING');
                                }
                                
                            } else {
                                $errors[] = 'Erro ao salvar configurações no banco de dados.';
                            }
                        } else {
                            $errors[] = 'Erro ao inicializar o banco de dados.';
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = 'Erro durante a instalação: ' . $e->getMessage();
                        logSystem('Erro na instalação: ' . $e->getMessage(), 'ERROR');
                    }
                }
                break;
        }
        
        // Avançar para próximo step se não houver erros
        if (empty($errors) && !$success && $step < 4) {
            $step++;
        }
    }
}

// Obter verificações do sistema para o step 1
if ($step == 1) {
    $systemChecks = checkSystemRequirements();
}

$page_title = 'Instalação - Sistema de Sorteios';
$show_header = false;
?>

<?php include 'templates/header.php'; ?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header do Wizard -->
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Sistema de Sorteios
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Configuração inicial - Passo <?php echo $step; ?> de 4
            </p>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                 style="width: <?php echo ($step / 4) * 100; ?>%"></div>
        </div>
        
        <!-- Mensagens -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Erro na instalação
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-md p-4">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-green-800 mb-2">
                        Instalação Concluída!
                    </h3>
                    <p class="text-sm text-green-700 mb-4">
                        O sistema foi configurado com sucesso.
                    </p>
                    <a href="admin.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        Acessar Sistema
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulário do Step Atual -->
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="step" value="<?php echo $step; ?>">
                
                <?php switch ($step): 
                    case 1: ?>
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Verificação do Sistema
                            </h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Verificando se seu servidor atende aos requisitos mínimos.
                            </p>
                            
                            <div class="space-y-3 text-left bg-gray-50 rounded-lg p-4">
                                <?php foreach ($systemChecks as $check): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <?php if ($check['status']): ?>
                                                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            <?php endif; ?>
                                            <span class="text-sm font-medium text-gray-900"><?php echo $check['name']; ?></span>
                                            <?php if ($check['required']): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Obrigatório
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo $check['message']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php 
                            $hasRequiredFailures = false;
                            foreach ($systemChecks as $check) {
                                if ($check['required'] && !$check['status']) {
                                    $hasRequiredFailures = true;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if ($hasRequiredFailures): ?>
                                <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Atenção:</strong> Alguns requisitos obrigatórios não foram atendidos. 
                                        Corrija os problemas antes de continuar.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 bg-green-50 border border-green-200 rounded-md p-3">
                                    <p class="text-sm text-green-700">
                                        <strong>Perfeito!</strong> Todos os requisitos foram atendidos. 
                                        Você pode prosseguir com a instalação.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($hasRequiredFailures): ?>
                            <button type="button" disabled class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gray-400 cursor-not-allowed">
                                Corrija os Problemas para Continuar
                            </button>
                        <?php else: ?>
                            <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Continuar
                            </button>
                        <?php endif; ?>
                        
                    <?php break; case 2: ?>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Dados da Empresa
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="nome_empresa" class="block text-sm font-medium text-gray-700">
                                        Nome da Empresa/Plataforma *
                                    </label>
                                    <input id="nome_empresa" name="nome_empresa" type="text" required 
                                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                           placeholder="Ex: Minha Empresa Ltda"
                                           value="<?php echo htmlspecialchars($_SESSION['wizard_data']['nome_empresa'] ?? ''); ?>">
                                </div>
                                
                                <div>
                                    <label for="regras_padrao" class="block text-sm font-medium text-gray-700">
                                        Regras Padrão dos Sorteios
                                    </label>
                                    <textarea id="regras_padrao" name="regras_padrao" rows="4"
                                              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                              placeholder="Digite as regras padrão que aparecerão nos sorteios..."><?php echo htmlspecialchars($_SESSION['wizard_data']['regras_padrao'] ?? 'Regulamento padrão do sorteio.'); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Continuar
                        </button>
                        
                    <?php break; case 3: ?>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Credenciais de Administrador
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="admin_email" class="block text-sm font-medium text-gray-700">
                                        Email do Administrador *
                                    </label>
                                    <input id="admin_email" name="admin_email" type="email" required 
                                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                           placeholder="admin@empresa.com"
                                           value="<?php echo htmlspecialchars($_SESSION['wizard_data']['admin_email'] ?? ''); ?>">
                                </div>
                                
                                <div>
                                    <label for="admin_password" class="block text-sm font-medium text-gray-700">
                                        Senha *
                                    </label>
                                    <input id="admin_password" name="admin_password" type="password" required 
                                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                           placeholder="Mínimo 6 caracteres">
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                        Confirmar Senha *
                                    </label>
                                    <input id="confirm_password" name="confirm_password" type="password" required 
                                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                           placeholder="Repita a senha">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Continuar
                        </button>
                        
                    <?php break; case 4: ?>
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Finalizar Instalação
                            </h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Clique em "Instalar" para finalizar a configuração do sistema.
                            </p>
                            
                            <div class="bg-gray-50 rounded-lg p-4 text-left">
                                <h4 class="font-medium text-gray-900 mb-2">Resumo da Configuração:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><strong>Empresa:</strong> <?php echo htmlspecialchars($_SESSION['wizard_data']['nome_empresa'] ?? ''); ?></li>
                                    <li><strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['wizard_data']['admin_email'] ?? ''); ?></li>
                                    <li><strong>Banco:</strong> SQLite</li>
                                </ul>
                            </div>
                        </div>
                        
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Instalar Sistema
                        </button>
                        
                    <?php break; endswitch; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>