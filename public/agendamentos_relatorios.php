<?php
/**
 * Gerenciamento de Agendamentos de Relatórios
 * Interface para criar, editar e gerenciar agendamentos
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';

// Configurações da página
$page_title = 'Agendamentos de Relatórios';
$current_page = 'relatorios';
$show_sidebar = true;
$page_scripts = ['/assets/js/agendamentos.js'];

$db = getDatabase();

try {
    // Obter agendamentos existentes
    $agendamentos = $db->fetchAll("
        SELECT * FROM relatorio_agendamentos 
        ORDER BY created_at DESC
    ");
    
    // Obter lista de sorteios para filtros
    $sorteios = $db->fetchAll("
        SELECT id, nome, status 
        FROM sorteios 
        ORDER BY created_at DESC
    ");
    
} catch (Exception $e) {
    error_log("Erro ao carregar agendamentos: " . $e->getMessage());
    $agendamentos = [];
    $sorteios = [];
}

// Incluir header
include 'templates/header.php';
?>

<!-- Container principal -->
<div class="p-6">
    
    <!-- Cabeçalho da página -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Agendamentos de Relatórios</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Gerencie o envio automático de relatórios por email</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <button id="processar-agendamentos" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Processar Agora
                </button>
                <button id="novo-agendamento" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Agendamento
                </button>
            </div>
        </div>
    </div>
    
    <!-- Lista de Agendamentos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Agendamentos Ativos</h3>
        </div>
        
        <?php if (empty($agendamentos)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum agendamento criado ainda</p>
                <button id="criar-primeiro-agendamento" class="mt-4 btn-primary">
                    Criar Primeiro Agendamento
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Nome
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Tipo/Formato
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Frequência
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Email Destino
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Próxima Execução
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($agendamentos as $agendamento): ?>
                            <?php 
                            $config = json_decode($agendamento['configuracao'], true);
                            $statusClass = $agendamento['status'] === 'ativo' ? 'badge-success' : 
                                          ($agendamento['status'] === 'pausado' ? 'badge-warning' : 'badge-danger');
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($agendamento['nome']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo ucfirst($config['tipo_relatorio'] ?? ''); ?> / <?php echo strtoupper($config['formato'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo ucfirst($config['frequencia'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($config['email_destino'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo formatDateBR($agendamento['proxima_execucao']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" 
                                                onclick="editarAgendamento(<?php echo $agendamento['id']; ?>)">
                                            Editar
                                        </button>
                                        <?php if ($agendamento['status'] === 'ativo'): ?>
                                            <button class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" 
                                                    onclick="pausarAgendamento(<?php echo $agendamento['id']; ?>)">
                                                Pausar
                                            </button>
                                        <?php else: ?>
                                            <button class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" 
                                                    onclick="ativarAgendamento(<?php echo $agendamento['id']; ?>)">
                                                Ativar
                                            </button>
                                        <?php endif; ?>
                                        <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" 
                                                onclick="excluirAgendamento(<?php echo $agendamento['id']; ?>)">
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- Modal para Novo/Editar Agendamento -->
<div id="modal-agendamento" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
        
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div>
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 id="modal-title" class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Novo Agendamento de Relatório
                    </h3>
                </div>
            </div>
            
            <form id="form-agendamento" class="mt-6 space-y-6">
                <input type="hidden" id="agendamento-id" name="id">
                
                <!-- Nome do Agendamento -->
                <div>
                    <label for="nome-agendamento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nome do Agendamento
                    </label>
                    <input type="text" id="nome-agendamento" name="nome" required 
                           class="form-input" 
                           placeholder="Ex: Relatório Semanal de Participação">
                </div>
                
                <!-- Tipo de Relatório -->
                <div>
                    <label for="tipo-relatorio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipo de Relatório
                    </label>
                    <select id="tipo-relatorio" name="tipo_relatorio" required class="form-select">
                        <option value="">Selecione o tipo</option>
                        <option value="participacao">Participação</option>
                        <option value="conversao">Conversão</option>
                        <option value="engajamento">Engajamento</option>
                        <option value="comparativo">Comparativo</option>
                    </select>
                </div>
                
                <!-- Formato -->
                <div>
                    <label for="formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Formato de Exportação
                    </label>
                    <select id="formato" name="formato" required class="form-select">
                        <option value="">Selecione o formato</option>
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
                
                <!-- Frequência -->
                <div>
                    <label for="frequencia" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Frequência
                    </label>
                    <select id="frequencia" name="frequencia" required class="form-select">
                        <option value="">Selecione a frequência</option>
                        <option value="diario">Diário</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensal">Mensal</option>
                    </select>
                </div>
                
                <!-- Email Destino -->
                <div>
                    <label for="email-destino" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email de Destino
                    </label>
                    <input type="email" id="email-destino" name="email_destino" required 
                           class="form-input" 
                           placeholder="exemplo@empresa.com">
                </div>
                
                <!-- Filtros -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Filtros do Relatório</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Período -->
                        <div>
                            <label for="filtro-periodo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Período
                            </label>
                            <select id="filtro-periodo" name="filtros[periodo]" class="form-select">
                                <option value="7">Últimos 7 dias</option>
                                <option value="30" selected>Últimos 30 dias</option>
                                <option value="90">Últimos 90 dias</option>
                                <option value="365">Último ano</option>
                                <option value="todos">Todos os períodos</option>
                            </select>
                        </div>
                        
                        <!-- Sorteio -->
                        <div>
                            <label for="filtro-sorteio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sorteio
                            </label>
                            <select id="filtro-sorteio" name="filtros[sorteio]" class="form-select">
                                <option value="">Todos os sorteios</option>
                                <?php foreach ($sorteios as $sorteio): ?>
                                    <option value="<?php echo $sorteio['id']; ?>">
                                        <?php echo htmlspecialchars($sorteio['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label for="filtro-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select id="filtro-status" name="filtros[status]" class="form-select">
                                <option value="">Todos os status</option>
                                <option value="ativo">Ativo</option>
                                <option value="pausado">Pausado</option>
                                <option value="finalizado">Finalizado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="btn-primary w-full sm:col-start-2">
                        Salvar Agendamento
                    </button>
                    <button type="button" id="cancelar-agendamento" class="btn-secondary w-full mt-3 sm:mt-0 sm:col-start-1">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dados para JavaScript -->
<script>
    window.agendamentosData = {
        agendamentos: <?php echo json_encode($agendamentos); ?>,
        sorteios: <?php echo json_encode($sorteios); ?>
    };
</script>

<?php
// Incluir footer
include 'templates/footer.php';
?>