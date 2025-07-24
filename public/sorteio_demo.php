<?php
/**
 * Sistema de Sorteios - Demonstração de Sorteio
 * Página de demonstração do motor de sorteio com animações
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';

// Título da página
$pageTitle = 'Demonstração de Sorteio';

// Incluir cabeçalho
include_once 'templates/header.php';
include_once 'templates/sidebar.php';

// Dados de exemplo para demonstração
$participantes = [
    ['id' => 1, 'nome' => 'João Silva', 'whatsapp' => '11987654321', 'cpf' => '12345678901'],
    ['id' => 2, 'nome' => 'Maria Oliveira', 'whatsapp' => '11987654322', 'cpf' => '12345678902'],
    ['id' => 3, 'nome' => 'Pedro Santos', 'whatsapp' => '11987654323', 'cpf' => '12345678903'],
    ['id' => 4, 'nome' => 'Ana Souza', 'whatsapp' => '11987654324', 'cpf' => '12345678904'],
    ['id' => 5, 'nome' => 'Carlos Ferreira', 'whatsapp' => '11987654325', 'cpf' => '12345678905'],
    ['id' => 6, 'nome' => 'Juliana Lima', 'whatsapp' => '11987654326', 'cpf' => '12345678906'],
    ['id' => 7, 'nome' => 'Roberto Alves', 'whatsapp' => '11987654327', 'cpf' => '12345678907'],
    ['id' => 8, 'nome' => 'Fernanda Costa', 'whatsapp' => '11987654328', 'cpf' => '12345678908'],
    ['id' => 9, 'nome' => 'Marcelo Pereira', 'whatsapp' => '11987654329', 'cpf' => '12345678909'],
    ['id' => 10, 'nome' => 'Luciana Martins', 'whatsapp' => '11987654330', 'cpf' => '12345678910']
];

$sorteio = [
    'id' => 1,
    'nome' => 'Sorteio Demonstrativo',
    'descricao' => 'Este é um sorteio de demonstração para testar as animações e funcionalidades.',
    'qtd_sorteados' => 3,
    'max_participantes' => 10,
    'status' => 'ativo'
];
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg">
        <h1 class="text-2xl font-semibold mb-4">Demonstração de Sorteio</h1>
        
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-semibold"><?php echo $sorteio['nome']; ?></h2>
                    <p class="text-gray-600"><?php echo $sorteio['descricao']; ?></p>
                </div>
                <span class="px-3 py-1 text-sm rounded bg-green-100 text-green-800">
                    <?php echo ucfirst($sorteio['status']); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-blue-50 p-3 rounded-lg">
                    <span class="text-sm text-gray-500">Total de Participantes</span>
                    <p class="text-xl font-bold"><?php echo count($participantes); ?>/<?php echo $sorteio['max_participantes']; ?></p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <span class="text-sm text-gray-500">Quantidade de Sorteados</span>
                    <p class="text-xl font-bold"><?php echo $sorteio['qtd_sorteados']; ?></p>
                </div>
                <div class="bg-yellow-50 p-3 rounded-lg">
                    <span class="text-sm text-gray-500">Status do Sorteio</span>
                    <p class="text-xl font-bold"><?php echo ucfirst($sorteio['status']); ?></p>
                </div>
            </div>
            
            <div class="mb-4">
                <button id="start-draw" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Iniciar Sorteio
                </button>
                <button id="reset-draw" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition ml-2" disabled>
                    Reiniciar
                </button>
            </div>
        </div>
        
        <!-- Área de Sorteio -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="text-lg font-semibold mb-4">Área de Sorteio</h2>
            
            <!-- Slot Machine -->
            <div id="slot-machine" class="border-2 border-blue-500 rounded-lg p-4 mb-4 bg-blue-50 text-center">
                <div id="slot-container" class="flex justify-center items-center h-32 overflow-hidden">
                    <div id="slot-items" class="transition-transform">
                        <?php foreach ($participantes as $participante): ?>
                        <div class="slot-item py-2 text-xl font-bold"><?php echo $participante['nome']; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Resultados -->
            <div id="results" class="hidden">
                <h3 class="text-lg font-semibold mb-2">Resultado do Sorteio</h3>
                <div id="winners-container" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Os ganhadores serão adicionados aqui via JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- Lista de Participantes -->
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold mb-4">Participantes</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                WhatsApp
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                CPF
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="participants-table">
                        <?php foreach ($participantes as $participante): ?>
                        <tr data-participant-id="<?php echo $participante['id']; ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo $participante['nome']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo formatWhatsApp($participante['whatsapp']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo formatCPF($participante['cpf']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Não sorteado
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos da página -->
<?php
// Incluir scripts minificados
echo includeMinifiedJS([
    'assets/js/confetti.min.js',
    'assets/js/sorteio-animation.js'
]);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados de participantes
    const participantes = <?php echo json_encode($participantes); ?>;
    
    // Configurações do sorteio
    const sorteioConfig = {
        qtdSorteados: <?php echo $sorteio['qtd_sorteados']; ?>,
        animationDuration: 3000, // 3 segundos
        slotSpeed: 50 // ms entre cada mudança
    };
    
    // Elementos DOM
    const startButton = document.getElementById('start-draw');
    const resetButton = document.getElementById('reset-draw');
    const slotItems = document.getElementById('slot-items');
    const resultsContainer = document.getElementById('results');
    const winnersContainer = document.getElementById('winners-container');
    
    // Estado do sorteio
    let isDrawing = false;
    let winners = [];
    let slotInterval;
    
    // Iniciar sorteio
    startButton.addEventListener('click', function() {
        if (isDrawing) return;
        
        isDrawing = true;
        startButton.disabled = true;
        resultsContainer.classList.add('hidden');
        
        // Iniciar animação de slot machine
        let currentIndex = 0;
        slotInterval = setInterval(() => {
            currentIndex = (currentIndex + 1) % participantes.length;
            slotItems.style.transform = `translateY(-${currentIndex * 40}px)`;
        }, sorteioConfig.slotSpeed);
        
        // Selecionar ganhadores aleatoriamente
        winners = getRandomWinners(participantes, sorteioConfig.qtdSorteados);
        
        // Parar animação após o tempo definido
        setTimeout(() => {
            clearInterval(slotInterval);
            showResults();
        }, sorteioConfig.animationDuration);
    });
    
    // Reiniciar sorteio
    resetButton.addEventListener('click', function() {
        resetButton.disabled = true;
        startButton.disabled = false;
        resultsContainer.classList.add('hidden');
        slotItems.style.transform = 'translateY(0)';
        
        // Resetar status dos participantes
        document.querySelectorAll('#participants-table tr').forEach(row => {
            const statusCell = row.querySelector('td:last-child span');
            statusCell.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
            statusCell.textContent = 'Não sorteado';
        });
        
        isDrawing = false;
        winners = [];
    });
    
    // Função para selecionar ganhadores aleatórios
    function getRandomWinners(participants, count) {
        const shuffled = [...participants].sort(() => 0.5 - Math.random());
        return shuffled.slice(0, count);
    }
    
    // Função para mostrar resultados
    function showResults() {
        // Limpar container de ganhadores
        winnersContainer.innerHTML = '';
        
        // Adicionar cada ganhador
        winners.forEach((winner, index) => {
            const winnerCard = document.createElement('div');
            winnerCard.className = 'bg-green-50 border border-green-200 rounded-lg p-4 text-center';
            winnerCard.innerHTML = `
                <div class="text-lg font-bold mb-2">${index + 1}º Lugar</div>
                <div class="text-xl font-bold mb-1">${winner.nome}</div>
                <div class="text-sm text-gray-600">${formatCPF(winner.cpf)}</div>
                <div class="text-sm text-gray-600">${formatWhatsApp(winner.whatsapp)}</div>
            `;
            winnersContainer.appendChild(winnerCard);
            
            // Atualizar status na tabela
            const row = document.querySelector(`tr[data-participant-id="${winner.id}"]`);
            if (row) {
                const statusCell = row.querySelector('td:last-child span');
                statusCell.className = 'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                statusCell.textContent = `${index + 1}º Lugar`;
            }
        });
        
        // Mostrar resultados
        resultsContainer.classList.remove('hidden');
        
        // Habilitar botão de reiniciar
        resetButton.disabled = false;
        
        // Lançar confetes
        launchConfetti();
    }
    
    // Função para lançar confetes
    function launchConfetti() {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    }
    
    // Função para formatar CPF
    function formatCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    
    // Função para formatar WhatsApp
    function formatWhatsApp(whatsapp) {
        whatsapp = whatsapp.replace(/\D/g, '');
        return whatsapp.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
});
</script>

<?php
// Incluir rodapé
include_once 'templates/footer.php';
?>