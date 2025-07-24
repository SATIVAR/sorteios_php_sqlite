<?php
// =============================================================================
// BLOCO DE LÓGICA PHP (FUNCIONALIDADE INALTERADA)
// =============================================================================

if (!defined('SISTEMA_SORTEIOS')) {
    define('SISTEMA_SORTEIOS', true);
}
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Extrair o identificador do sorteio da URL
$sorteioId = null;
if (isset($_GET['id'])) {
    $sorteioId = $_GET['id'];
} else {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/participar/([a-zA-Z0-9]+)#', $requestUri, $matches)) {
        $sorteioId = $matches[1];
    }
}

$db = getDatabase();
$sorteio = null;
$totalParticipantes = 0;
$limitExcedido = false;

if ($sorteioId) {
    $sorteio = $db->fetchOne("SELECT * FROM sorteios WHERE public_url = ? LIMIT 1", [$sorteioId]);
    if ($sorteio) {
        $totalParticipantes = $db->fetchOne("SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?", [$sorteio['id']])['total'];
        if ($sorteio['max_participantes'] > 0 && $totalParticipantes >= $sorteio['max_participantes']) {
            $limitExcedido = true;
        }
    }
}

// Processar configuração de campos - LÊ DIRETAMENTE DO SQLITE
$camposConfig = [
    'whatsapp' => ['enabled' => true, 'required' => false],
    'cpf' => ['enabled' => false, 'required' => false],
    'email' => ['enabled' => false, 'required' => false],
    'campos_extras' => []
];

if ($sorteio && !empty($sorteio['campos_config'])) {
    $rawConfig = $sorteio['campos_config'];
    $dbConfig = json_decode($rawConfig, true);
    
    // Garante que seja um array válido
    if (!is_array($dbConfig)) {
        $dbConfig = [];
    }
    
    // --- INÍCIO DA CORREÇÃO: Unificar campos customizados ---
    // Se não houver campos_extras, converte todos os custom_*
    if (!isset($dbConfig['campos_extras']) || !is_array($dbConfig['campos_extras'])) {
        $dbConfig['campos_extras'] = [];
        foreach ($dbConfig as $key => $config) {
            if (strpos($key, 'custom_') === 0 && isset($config['enabled']) && $config['enabled']) {
                $dbConfig['campos_extras'][] = [
                    'nome' => substr($key, 7), // remove 'custom_'
                    'label' => $config['label'] ?? ucfirst(substr($key, 7)),
                    'type' => $config['type'] ?? 'text',
                    'required' => $config['required'] ?? false
                ];
            }
        }
    }
    // --- FIM DA CORREÇÃO ---
    
    // Se for um array simples (formato antigo), converte para novo formato
    if (isset($dbConfig[0]) && is_array($dbConfig[0]) && !isset($dbConfig['campos_extras'])) {
        $camposConfig['campos_extras'] = $dbConfig;
    } 
    // Se for o formato novo com campos_extras
    elseif (isset($dbConfig['campos_extras']) && is_array($dbConfig['campos_extras'])) {
        $camposConfig['campos_extras'] = $dbConfig['campos_extras'];
    }
    
    // Processa configurações individuais
    if (isset($dbConfig['whatsapp']) && is_array($dbConfig['whatsapp'])) {
        $camposConfig['whatsapp'] = array_merge($camposConfig['whatsapp'], $dbConfig['whatsapp']);
    }
    if (isset($dbConfig['cpf']) && is_array($dbConfig['cpf'])) {
        $camposConfig['cpf'] = array_merge($camposConfig['cpf'], $dbConfig['cpf']);
    }
    if (isset($dbConfig['email']) && is_array($dbConfig['email'])) {
        $camposConfig['email'] = array_merge($camposConfig['email'], $dbConfig['email']);
    }
}

// Processar formulário se enviado
$formSubmitted = false;
$success = false;
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sorteio) {
    $formSubmitted = true;
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['geral'] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitCheck = checkParticipationRateLimit($ip, RATE_LIMIT_PARTICIPACAO, RATE_LIMIT_WINDOW);
        if (!$rateLimitCheck['allowed']) {
            $errors['geral'] = $rateLimitCheck['reason'];
            if (isset($rateLimitCheck['time_until_reset']) && $rateLimitCheck['time_until_reset'] > 0) {
                $minutes = ceil($rateLimitCheck['time_until_reset'] / 60);
                $errors['geral'] .= " Tente novamente em {$minutes} minuto(s).";
            }
        } else {
            $currentTotal = $db->fetchOne("SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?", [$sorteio['id']])['total'];
            if ($sorteio['max_participantes'] > 0 && $currentTotal >= $sorteio['max_participantes']) {
                $errors['geral'] = 'Este sorteio já atingiu o limite máximo de participantes.';
                $limitExcedido = true;
            } else {
                recordParticipationAttempt($ip);
                $result = processParticipantForm($_POST, $sorteio, $camposConfig);
                if ($result['success']) {
                    $success = true;
                    $formData = $result['data'];
                } else {
                    $formData = $result['data'];
                    $errors = $result['errors'];
                }
            }
        }
    }
}

// =============================================================================
// FIM DO BLOCO DE LÓGICA PHP
// =============================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sorteio['nome'] ?? 'Participar do Sorteio') ?> - <?= htmlspecialchars($sistema_config['nome_empresa'] ?? 'Sorteios') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-900: #1e3a8a;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #1a202c;
        }

        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }

        /* Glassmorphism Effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                0 4px 16px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .dark .glass-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                0 4px 16px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Premium Header */
        .premium-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .dark .premium-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Premium Buttons */
        .btn-premium {
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            box-shadow: 
                0 4px 14px rgba(59, 130, 246, 0.3),
                0 2px 8px rgba(59, 130, 246, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-premium:hover {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            box-shadow: 
                0 6px 20px rgba(59, 130, 246, 0.4),
                0 4px 12px rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }

        .btn-premium:active {
            transform: translateY(0);
        }

        /* Secondary Button */
        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(59, 130, 246, 0.2);
            color: var(--primary-600);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .btn-secondary {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #60a5fa;
        }

        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: var(--primary-500);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .dark .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: #60a5fa;
        }

        /* Premium Inputs */
        .input-premium {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(59, 130, 246, 0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #1a202c;
        }

        .dark .input-premium {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
        }

        .input-premium:focus {
            border-color: var(--primary-500);
            box-shadow: 
                0 0 0 4px rgba(59, 130, 246, 0.1),
                0 4px 12px rgba(59, 130, 246, 0.15);
            background: rgba(255, 255, 255, 0.98);
        }

        .dark .input-premium:focus {
            background: rgba(15, 23, 42, 0.95);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }

        .animate-fade-in-slow {
            animation: fadeIn 0.8s ease-out;
        }

        .animate-pulse-subtle {
            animation: pulse 2s infinite;
        }

        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }

        /* Stagger animations */
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }

        /* Loading states */
        .loading-spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Background Pattern */
        .bg-pattern {
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
        }

        /* Dark mode toggle */
        .dark-toggle {
            transition: all 0.3s ease;
        }

        /* Success confetti effect */
        @keyframes confetti {
            0% { transform: translateY(0) rotateZ(0deg); opacity: 1; }
            100% { transform: translateY(-100px) rotateZ(720deg); opacity: 0; }
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--primary-500);
            animation: confetti 3s ease-out infinite;
        }
    </style>
</head>
<body class="antialiased bg-pattern">
    <!-- Premium Header -->
    <header class="premium-header fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            <?= htmlspecialchars($sistema_config['nome_empresa'] ?? 'Sorteios') ?>
                        </span>
                    </a>
                </div>

                <!-- Dark Mode Toggle -->
                <button id="theme-toggle" class="dark-toggle p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <?php if (!$sorteio): ?>
                <!-- Sorteio Não Encontrado -->
                <div class="flex items-center justify-center min-h-[60vh]">
                    <div class="glass-card rounded-3xl p-8 md:p-12 text-center max-w-md w-full animate-fade-in-up">
                        <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900 dark:to-red-800 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Sorteio não encontrado</h1>
                        <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">O link informado é inválido, expirado ou o sorteio não está mais disponível.</p>
                        <a href="/" class="btn-premium text-white font-semibold py-3 px-8 rounded-2xl inline-flex items-center space-x-2 hover:scale-105 transition-transform duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Voltar para página inicial</span>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Hero Section -->
                <div class="text-center mb-12 animate-fade-in-up">
                    <h1 class="text-4xl md:text-6xl font-black text-gray-900 dark:text-white mb-4 bg-gradient-to-r from-blue-600 via-purple-600 to-blue-800 bg-clip-text text-transparent">
                        <?= htmlspecialchars($sorteio['nome']) ?>
                    </h1>
                    <?php if (!empty($sorteio['descricao'])): ?>
                        <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto leading-relaxed stagger-1 animate-fade-in-up">
                            <?= nl2br(htmlspecialchars($sorteio['descricao'])) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12 stagger-2 animate-fade-in-up">
                    <?php if (!empty($sorteio['data_fim'])): ?>
                    <div class="glass-card rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Encerra em</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white"><?= formatDateBR($sorteio['data_fim']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="glass-card rounded-2xl p-6 hover:scale-105 transition-transform duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Participantes</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">
                                    <?= $totalParticipantes ?><?php if ($sorteio['max_participantes'] > 0) echo ' / ' . $sorteio['max_participantes']; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="glass-card rounded-3xl p-8 md:p-12 stagger-3 animate-fade-in-up">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="text-center animate-fade-in">
                            <div class="relative mb-8">
                                <div class="w-24 h-24 mx-auto bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 rounded-full flex items-center justify-center animate-pulse-subtle">
                                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <!-- Confetti elements -->
                                <div class="confetti" style="left: 20%; animation-delay: 0s; background: #3b82f6;"></div>
                                <div class="confetti" style="left: 40%; animation-delay: 0.5s; background: #8b5cf6;"></div>
                                <div class="confetti" style="left: 60%; animation-delay: 1s; background: #10b981;"></div>
                                <div class="confetti" style="left: 80%; animation-delay: 1.5s; background: #f59e0b;"></div>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Cadastro Realizado!</h2>
                            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">Parabéns! Sua participação foi confirmada. Boa sorte no sorteio!</p>
                            <button onclick="window.location.reload()" class="btn-premium text-white font-semibold py-4 px-8 rounded-2xl inline-flex items-center space-x-2 hover:scale-105 transition-transform duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Cadastrar Outro Participante</span>
                            </button>
                        </div>

                    <?php elseif ($limitExcedido): ?>
                        <!-- Limit Exceeded State -->
                        <div class="text-center animate-fade-in">
                            <div class="w-24 h-24 mx-auto mb-8 bg-gradient-to-br from-yellow-100 to-yellow-200 dark:from-yellow-900 dark:to-yellow-800 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Sorteio Lotado</h2>
                            <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed">Este sorteio já atingiu o número máximo de participantes. Fique atento aos próximos sorteios!</p>
                        </div>

                    <?php else: ?>
                        <!-- Form State -->
                        <div class="animate-fade-in">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Inscreva-se para participar</h2>
                                <p class="text-lg text-gray-600 dark:text-gray-300">Preencha os dados abaixo e concorra a prêmios incríveis!</p>
                            </div>
                            
                            <?php if (!empty($errors['geral'])): ?>
                                <div class="mb-8 p-4 bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 border border-red-200 dark:border-red-800/50 rounded-2xl flex items-center space-x-3 animate-shake">
                                    <div class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <span class="text-red-800 dark:text-red-200 font-medium"><?= htmlspecialchars($errors['geral']); ?></span>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="space-y-6" id="participacao-form">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                                
                                <?php
                                function render_premium_input($name, $label, $type, $placeholder, $icon_svg, $is_required, $value, $error) {
                                    // Sanitiza o nome para criar um ID de formulário válido, substituindo caracteres especiais.
                                    $input_id = 'form-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
                                    $error_class = !empty($error) ? 'border-red-300 dark:border-red-600' : '';
                                    
                                    echo '<div class="space-y-2">';
                                    echo '<label for="' . $input_id . '" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">';
                                    echo htmlspecialchars($label);
                                    if ($is_required) echo ' <span class="text-red-500">*</span>';
                                    echo '</label>';
                                    
                                    echo '<div class="relative group">';
                                    echo '<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">';
                                    echo '<div class="text-gray-400 group-focus-within:text-blue-500 transition-colors duration-200">' . $icon_svg . '</div>';
                                    echo '</div>';
                                    
                                    echo '<input type="' . $type . '" id="' . $input_id . '" name="' . $name . '" value="' . htmlspecialchars($value) . '" ';
                                    echo 'class="input-premium w-full pl-12 pr-4 py-4 rounded-2xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none ' . $error_class . '" ';
                                    echo 'placeholder="' . htmlspecialchars($placeholder) . '" ';
                                    echo ($is_required ? 'required' : '') . '>';
                                    echo '</div>';
                                    
                                    if (!empty($error)) {
                                        echo '<p class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">';
                                        echo '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                                        echo '</svg>';
                                        echo '<span>' . htmlspecialchars($error) . '</span>';
                                        echo '</p>';
                                    }
                                    echo '</div>';
                                }

                                // Nome
                                render_premium_input('nome', 'Nome Completo', 'text', 'Digite seu nome completo', 
                                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>', 
                                    true, $formData['nome'] ?? '', $errors['nome'] ?? '');

                                // WhatsApp
                                if ($camposConfig['whatsapp']['enabled']) {
                                    render_premium_input('whatsapp', 'WhatsApp', 'tel', '(99) 99999-9999', 
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>', 
                                        $camposConfig['whatsapp']['required'], $formData['whatsapp'] ?? '', $errors['whatsapp'] ?? '');
                                }

                                // Email
                                if ($camposConfig['email']['enabled']) {
                                    render_premium_input('email', 'Email', 'email', 'seu@email.com', 
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>', 
                                        $camposConfig['email']['required'], $formData['email'] ?? '', $errors['email'] ?? '');
                                }

                                // CPF
                                if ($camposConfig['cpf']['enabled']) {
                                    render_premium_input('cpf', 'CPF', 'text', '000.000.000-00', 
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 
                                        $camposConfig['cpf']['required'], $formData['cpf'] ?? '', $errors['cpf'] ?? '');
                                }

                                // Campos Extras
                                if (!empty($camposConfig['campos_extras']) && is_array($camposConfig['campos_extras'])) {
                                    foreach ($camposConfig['campos_extras'] as $campo) {
                                        if (empty($campo['nome']) || empty($campo['label'])) continue;

                                        $fieldName = 'campos_extras[' . $campo['nome'] . ']';
                                        $fieldLabel = htmlspecialchars($campo['label']);
                                        $fieldRequired = !empty($campo['required']);
                                        
                                        $fieldValue = '';
                                        if (isset($formData['campos_extras']) && is_array($formData['campos_extras']) && isset($formData['campos_extras'][$campo['nome']])) {
                                            $fieldValue = $formData['campos_extras'][$campo['nome']];
                                        }

                                        $fieldError = '';
                                        if (isset($errors['campos_extras']) && is_array($errors['campos_extras']) && isset($errors['campos_extras'][$campo['nome']])) {
                                            $fieldError = $errors['campos_extras'][$campo['nome']];
                                        }

                                        render_premium_input(
                                            $fieldName,
                                            $fieldLabel,
                                            'text',
                                            'Digite ' . strtolower($fieldLabel),
                                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>',
                                            $fieldRequired,
                                            $fieldValue,
                                            $fieldError
                                        );
                                    }
                                }
                                ?>

                                <div class="pt-6">
                                    <button type="submit" id="submit-btn" class="btn-premium w-full text-white font-bold py-4 px-8 rounded-2xl flex items-center justify-center space-x-3 text-lg hover:scale-105 transition-all duration-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Confirmar Participação</span>
                                        <div id="loading-spinner" class="loading-spinner hidden"></div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Novo Bloco de Informações Importantes -->
        <div class="max-w-4xl mx-auto mt-10 glass-card rounded-3xl shadow-xl border border-blue-100 dark:border-blue-900/30 p-8 animate-fade-in-slow">
            <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                Informações Importantes
            </h3>
            <div class="space-y-4 text-base text-gray-600 dark:text-gray-300">
                <div class="flex items-start gap-3 animate-fade-in">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Todos os dados fornecidos são <span class="font-semibold text-blue-700 dark:text-blue-300">confidenciais</span> e utilizados apenas para este sorteio.</span>
                </div>
                <div class="flex items-start gap-3 animate-fade-in-slow">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>O resultado do sorteio será divulgado <span class="font-semibold text-blue-700 dark:text-blue-300">após o encerramento</span> das inscrições.</span>
                </div>
                <div class="flex items-start gap-3 animate-fade-in">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Certifique-se de que seus dados estão <span class="font-semibold text-blue-700 dark:text-blue-300">corretos</span> antes de enviar o formulário.</span>
                </div>
            </div>
            
            <!-- Botão Ler Regulamento -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button data-modal-target="regulamento-modal" data-modal-toggle="regulamento-modal" class="btn-secondary w-full font-semibold py-4 px-8 rounded-2xl flex items-center justify-center space-x-3 text-lg hover:scale-105 transition-all duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Ler Regulamento do Sorteio</span>
                </button>
            </div>
        </div>

    </main>

    <!-- Modal do Regulamento -->
    <div id="regulamento-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-4xl max-h-full">
            <!-- Modal content -->
            <div class="relative glass-card rounded-3xl shadow-2xl">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-6 md:p-8 border-b border-gray-200 dark:border-gray-700 rounded-t-3xl">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Regulamento do Sorteio
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-2xl text-sm w-10 h-10 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white transition-colors duration-200" data-modal-hide="regulamento-modal">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Fechar modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-6 md:p-8 space-y-6 max-h-96 overflow-y-auto">
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                        <?php if (!empty($sorteio['regulamento'])): ?>
                            <?= nl2br(htmlspecialchars($sorteio['regulamento'])) ?>
                        <?php else: ?>
                            <p>O regulamento para este sorteio não foi fornecido.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center justify-end p-6 md:p-8 space-x-3 border-t border-gray-200 dark:border-gray-700 rounded-b-3xl">
                    <button data-modal-hide="regulamento-modal" type="button" class="btn-premium text-white font-semibold py-3 px-8 rounded-2xl inline-flex items-center space-x-2 hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Entendi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        if (currentTheme === 'dark') {
            document.documentElement.classList.add('dark');
            darkIcon.classList.add('hidden');
            lightIcon.classList.remove('hidden');
        } else {
            document.documentElement.classList.remove('dark');
            lightIcon.classList.add('hidden');
            darkIcon.classList.remove('hidden');
        }

        themeToggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            
            if (document.documentElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
            } else {
                localStorage.setItem('theme', 'light');
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        });

        // Input masks
        const whatsappInput = document.getElementById('form-whatsapp');
        if (whatsappInput) {
            const im = new Inputmask({
                mask: ['(99) 9999-9999', '(99) 99999-9999'],
                keepStatic: true
            });
            im.mask(whatsappInput);
        }

        const cpfInput = document.getElementById('form-cpf');
        if (cpfInput) {
            const im_cpf = new Inputmask('999.999.999-99');
            im_cpf.mask(cpfInput);
        }

        // Form submission with loading state
        const form = document.getElementById('participacao-form');
        const submitBtn = document.getElementById('submit-btn');
        const loadingSpinner = document.getElementById('loading-spinner');

        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75');
                loadingSpinner.classList.remove('hidden');
                
                // Clean masks before submission
                if (whatsappInput) {
                    whatsappInput.value = whatsappInput.inputmask.unmaskedvalue();
                }
                if (cpfInput) {
                    cpfInput.value = cpfInput.inputmask.unmaskedvalue();
                }
            });
        }

        // Smooth scroll for any anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add subtle parallax effect to background
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.bg-pattern');
            if (parallax) {
                const speed = scrolled * 0.5;
                parallax.style.transform = `translateY(${speed}px)`;
            }
        });

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.glass-card, .stagger-1, .stagger-2, .stagger-3').forEach(el => {
            observer.observe(el);
        });
    });
    </script>
</body>
</html>
