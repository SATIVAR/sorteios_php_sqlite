<?php
/**
 * Sistema de Sorteios - Entry Point Principal
 * Redireciona para área administrativa ou wizard de instalação
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/install_check.php';

// Verificar status da instalação
$installStatus = getInstallationStatus();

if (!$installStatus['installed']) {
    // Sistema não está instalado - redirecionar para wizard
    header('Location: wizard.php');
    exit;
}

// Validar integridade da instalação
$integrity = validateInstallationIntegrity();
if (!$integrity['valid']) {
    // Instalação corrompida - mostrar página de erro ou redirecionar para wizard
    error_log('Instalação corrompida detectada: ' . implode(', ', $integrity['issues']));
    
    // Por segurança, redirecionar para wizard para reinstalação
    header('Location: wizard.php?reinstall=1');
    exit;
}

// Sistema instalado e íntegro - verificar se existe admin.php
if (file_exists('admin.php')) {
    header('Location: admin.php');
} else {
    // Fallback - mostrar página simples
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema de Sorteios</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Sistema de Sorteios</h1>
                    <p class="text-gray-600 mb-6">Sistema instalado com sucesso!</p>
                    <div class="space-y-3">
                        <a href="admin.php" class="block w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                            Área Administrativa
                        </a>
                        <a href="wizard.php" class="block w-full bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700">
                            Reconfigurar Sistema
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
exit;
?>