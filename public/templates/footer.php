<?php
/**
 * Template Footer - Sistema de Sorteios
 */

if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}
?>
            </main>
            
            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row justify-between items-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            © <?php echo date('Y'); ?> Sistema de Sorteios v<?php echo SYSTEM_VERSION; ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2 sm:mt-0">
                            Desenvolvido com ❤️ para facilitar seus sorteios
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Scripts -->
    
    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    
    <!-- Scripts comuns -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/common.js"></script>
    
    <!-- Scripts de acessibilidade -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/accessibility.js"></script>
    
    <!-- Scripts de responsividade -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/responsive.js"></script>
    
    <!-- Scripts específicos da página -->
    <?php 
    if (isset($page_scripts) && is_array($page_scripts)) {
        foreach ($page_scripts as $script) {
            echo '<script src="' . getBaseUrl() . $script . '"></script>';
        }
    }
    ?>
    
    <!-- Script inline para tema -->
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Aplicar tema salvo
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        });
    </script>
    
    <!-- Scripts inline da página -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
    
</body>
</html>
