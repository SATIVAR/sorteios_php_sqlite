<?php
/**
 * Template Header - Sistema de Sorteios
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

global $sistema_config;
$page_title = $page_title ?? 'Sistema de Sorteios';
$body_class = $body_class ?? '';
$current_page = $current_page ?? '';
$show_sidebar = $show_sidebar ?? true;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? 'Sistema de Sorteios - Plataforma para gerenciamento e execução de sorteios'); ?>">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite via CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Canvas Confetti para animações -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    
    <!-- Estilos personalizados -->
    <link href="<?php echo getBaseUrl(); ?>/assets/css/custom.css" rel="stylesheet">
    <link href="<?php echo getBaseUrl(); ?>/assets/css/accessibility.css" rel="stylesheet">
    <link href="<?php echo getBaseUrl(); ?>/assets/css/responsive.css" rel="stylesheet">
    
    <!-- Configuração do Tailwind -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        gray: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 <?php echo $body_class; ?>">
    <!-- Link para pular para o conteúdo principal - acessibilidade -->
    <a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>
    
    <!-- Container de notificações -->
    <div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <?php if ($is_admin && $show_sidebar): ?>
    <!-- Layout com Sidebar -->
    <div class="flex h-full">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex flex-col h-full">
                <!-- Logo/Header da Sidebar -->
                <div class="flex items-center justify-between h-14 px-4 bg-primary-600 dark:bg-primary-700">
                    <div class="flex items-center min-w-0">
                        <div class="flex-shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-2 min-w-0">
                            <h1 class="text-base font-semibold text-white truncate max-w-[10.5rem]" title="<?php echo htmlspecialchars($sistema_config['nome_empresa'] ?: 'Sorteios'); ?>">
                                <?php echo htmlspecialchars($sistema_config['nome_empresa'] ?: 'Sorteios'); ?>
                            </h1>
                        </div>
                    </div>
                    <button id="sidebar-close" class="lg:hidden text-white hover:text-gray-200 interactive-element" aria-label="Fechar menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Navegação -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto" role="navigation" aria-label="Menu principal">
                    <a href="admin.php" class="nav-link interactive-element <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'dashboard' ? 'page' : 'false'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        <span class="ml-3 whitespace-nowrap">Dashboard</span>
                    </a>
                    
                    <a href="sorteios.php" class="nav-link interactive-element <?php echo $current_page === 'sorteios' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'sorteios' ? 'page' : 'false'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span class="ml-3 whitespace-nowrap">Sorteios</span>
                    </a>
                    
                    <a href="participantes.php" class="nav-link interactive-element <?php echo $current_page === 'participantes' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'participantes' ? 'page' : 'false'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span class="ml-3 whitespace-nowrap">Participantes</span>
                    </a>
                    
                    <a href="historico.php" class="nav-link interactive-element <?php echo $current_page === 'historico' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'historico' ? 'page' : 'false'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="ml-3 whitespace-nowrap">Histórico</span>
                    </a>
                    
                    <a href="relatorios.php" class="nav-link interactive-element <?php echo $current_page === 'relatorios' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'relatorios' ? 'page' : 'false'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H9z"></path>
                        </svg>
                        <span class="ml-3 whitespace-nowrap">Relatórios</span>
                    </a>
                    
                    <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="configuracoes.php" class="nav-link interactive-element <?php echo $current_page === 'configuracoes' ? 'active' : ''; ?>" aria-current="<?php echo $current_page === 'configuracoes' ? 'page' : 'false'; ?>">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="ml-3 whitespace-nowrap">Configurações</span>
                        </a>
                    </div>
                </nav>
                
                <!-- Footer da Sidebar -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-gray-900 dark:text-white">Admin</p>
                                <p class="text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?></p>
                            </div>
                        </div>
                        <button id="theme-toggle" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 interactive-element" aria-label="Alternar tema claro/escuro">
                            <svg id="theme-toggle-dark-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                            <svg id="theme-toggle-light-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M10 2L13.09 8.26L20 9L14 14.74L15.18 21.02L10 17.77L4.82 21.02L6 14.74L0 9L6.91 8.26L10 2Z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3">
                        <a href="logout.php" class="flex items-center w-full px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors interactive-element" aria-label="Sair do sistema">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overlay para mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden hidden"></div>
        
        <!-- Conteúdo principal -->
        <div class="flex-1 flex flex-col lg:ml-0">
            <!-- Header superior -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center h-14 gap-2">
                        <!-- Botão do menu mobile -->
                        <button id="sidebar-toggle" class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 interactive-element" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <!-- Título da página -->
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white ml-2 truncate">
                            <?php echo htmlspecialchars($page_title); ?>
                        </h1>
                        <!-- Indicador de status -->
                        <div class="hidden sm:flex items-center space-x-2 ml-auto">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Online</span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Conteúdo da página -->
            <main id="main-content" class="flex-1 overflow-y-auto">
    <?php else: ?>
    <!-- Layout simples sem sidebar -->
    <?php if (isset($show_header) && $show_header): ?>
    <!-- Header simples -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Nome -->
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($sistema_config['nome_empresa'] ?: 'Sistema de Sorteios'); ?>
                    </h1>
                </div>
                
                <!-- Botão de tema -->
                <button id="theme-toggle" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L13.09 8.26L20 9L14 14.74L15.18 21.02L10 17.77L4.82 21.02L6 14.74L0 9L6.91 8.26L10 2Z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <!-- Conteúdo principal -->
    <main id="main-content" class="<?php echo isset($show_header) && $show_header ? '' : ''; ?>">
    <?php endif; ?>