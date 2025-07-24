<?php
/**
 * Template de Erro Público - Sistema de Sorteios
 * Página de erro para usuários não autenticados
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

$page_title = 'Erro - Sistema de Sorteios';
$show_header = true;
$body_class = 'bg-gradient-to-br from-red-50 to-pink-100 dark:from-gray-900 dark:to-gray-800';

// Incluir header
include 'header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Ícone de Erro -->
            <div class="mx-auto w-24 h-24 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <!-- Título -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Oops! Algo deu errado
            </h1>
            
            <!-- Mensagem de Erro -->
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                <?php echo htmlspecialchars($error_message ?? 'Erro desconhecido'); ?>
            </p>
            
            <!-- Card com Informações -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    O que pode ter acontecido?
                </h2>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300 text-left">
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>O link do sorteio pode estar incorreto ou expirado</p>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>O sorteio pode ter sido encerrado ou pausado</p>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p>O limite de participantes pode ter sido atingido</p>
                    </div>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="space-y-4">
                <button onclick="window.location.reload()" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Tentar Novamente
                </button>
                
                <button onclick="window.history.back()" 
                        class="w-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-3 px-6 rounded-lg transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </button>
            </div>
            
            <!-- Informações de Contato -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Se o problema persistir, entre em contato com o organizador do sorteio.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'footer.php';
?>