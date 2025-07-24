<?php
/**
 * Gerenciamento de Sorteios - Sistema de Sorteios
 * CRUD completo para sorteios com interface moderna
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';
require_once 'includes/validator.php';

// Configurações da página
$page_title = 'Gerenciar Sorteios';
$current_page = 'sorteios';
$show_sidebar = true;
$page_scripts = ['/assets/js/sorteios.js'];

// Processar ações
$action = $_GET['action'] ?? 'list';
$sorteio_id = $_GET['id'] ?? null;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$message = '';
$error = '';

$db = getDatabase();

// Processar formulário de criação/edição
if ($_POST && isset($_POST['csrf_token'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        $result = processSorteioForm($_POST, $sorteio_id);
        if ($result['success']) {
            $message = $result['message'];
            if ($action === 'new') {
                $action = 'list'; // Redireciona para lista após criar
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Processar exclusão
if ($action === 'delete' && $sorteio_id) {
    if (deleteSorteio($sorteio_id)) {
        $message = 'Sorteio excluído com sucesso!';
        $action = 'list';
    } else {
        $error = 'Erro ao excluir sorteio.';
    }
}

// Processar duplicação
if ($action === 'duplicate' && $sorteio_id) {
    $result = duplicateSorteio($sorteio_id);
    if ($result['success']) {
        $message = 'Sorteio duplicado com sucesso!';
        $action = 'list';
    } else {
        $error = $result['message'];
    }
}

// Carregar dados conforme a ação
switch ($action) {
    case 'new':
        $sorteio = getDefaultSorteioData();
        break;
    case 'edit':
        $sorteio = getSorteioById($sorteio_id);
        if (!$sorteio) {
            $error = 'Sorteio não encontrado.';
            $action = 'list';
        }
        break;
    case 'list':
    default:
        $sorteios = getAllSorteios($search, $status_filter);
        $total_sorteios = getTotalSorteios($search, $status_filter);
        break;
}

/**
 * Funções auxiliares
 */

function processSorteioForm($data, $sorteio_id = null) {
    $validator = getValidator();
    
    // Regras de validação
    $rules = [
        'nome' => 'required|max:255',
        'descricao' => 'max:1000',
        'regulamento' => 'max:5000',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'max_participantes' => 'integer|min:0',
        'qtd_sorteados' => 'required|integer|min:1',
        'status' => 'required|in:ativo,pausado,finalizado'
    ];
    
    $validation = validateFormData($data, $rules);
    
    if (!$validation['success']) {
        return [
            'success' => false,
            'message' => 'Dados inválidos: ' . implode(', ', $validation['errors'])
        ];
    }
    
    $cleanData = $validation['data'];
    
    // Processar configuração de campos
    $campos_config = [
        'nome' => ['enabled' => true, 'required' => true],
        'whatsapp' => ['enabled' => isset($data['campo_whatsapp']), 'required' => isset($data['campo_whatsapp_required'])],
        'cpf' => ['enabled' => isset($data['campo_cpf']), 'required' => isset($data['campo_cpf_required'])],
        'email' => ['enabled' => isset($data['campo_email']), 'required' => isset($data['campo_email_required'])]
    ];
    
    // Processar campos personalizados
    if (isset($data['campos_personalizados']) && is_array($data['campos_personalizados'])) {
        foreach ($data['campos_personalizados'] as $campo) {
            if (!empty($campo['nome'])) {
                $campos_config['custom_' . sanitizeInput($campo['nome'])] = [
                    'label' => sanitizeInput($campo['nome']),
                    'type' => sanitizeInput($campo['tipo'] ?? 'text'),
                    'required' => isset($campo['required']),
                    'enabled' => true
                ];
            }
        }
    }
    
    try {
        $db = getDatabase();
        
        if ($sorteio_id) {
            // Atualizar sorteio existente
            $sql = "UPDATE sorteios SET 
                    nome = ?, descricao = ?, regulamento = ?, data_inicio = ?, data_fim = ?, 
                    max_participantes = ?, qtd_sorteados = ?, campos_config = ?, 
                    status = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $params = [
                $cleanData['nome'],
                $cleanData['descricao'],
                $cleanData['regulamento'],
                $cleanData['data_inicio'] ?: null,
                $cleanData['data_fim'] ?: null,
                $cleanData['max_participantes'] ?: 0,
                $cleanData['qtd_sorteados'],
                json_encode($campos_config),
                $cleanData['status'],
                $sorteio_id
            ];
            
            $db->execute($sql, $params);
            $message = 'Sorteio atualizado com sucesso!';
            
        } else {
            // Criar novo sorteio
            $public_url = generatePublicUrl();
            
            $sql = "INSERT INTO sorteios (nome, descricao, regulamento, data_inicio, data_fim, 
                    max_participantes, qtd_sorteados, campos_config, public_url, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $cleanData['nome'],
                $cleanData['descricao'],
                $cleanData['regulamento'],
                $cleanData['data_inicio'] ?: null,
                $cleanData['data_fim'] ?: null,
                $cleanData['max_participantes'] ?: 0,
                $cleanData['qtd_sorteados'],
                json_encode($campos_config),
                $public_url,
                $cleanData['status']
            ];
            
            $sorteio_id = $db->insert($sql, $params);
            $message = 'Sorteio criado com sucesso!';
            
            // Log da atividade
            logActivity('Sorteio criado', "ID: {$sorteio_id}, Nome: {$cleanData['nome']}");
        }
        
        return ['success' => true, 'message' => $message, 'id' => $sorteio_id];
        
    } catch (Exception $e) {
        error_log("Erro ao salvar sorteio: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor.'];
    }
}

function getAllSorteios($search = '', $status_filter = '', $limit = null, $offset = 0) {
    try {
        $db = getDatabase();
        
        // Construir query com filtros
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(s.nome LIKE ? OR s.descricao LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "s.status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $limit_clause = $limit ? "LIMIT {$limit} OFFSET {$offset}" : '';
        
        $sql = "
            SELECT s.*, 
                   COUNT(DISTINCT p.id) as total_participantes,
                   COUNT(DISTINCT sr.id) as total_sorteados
            FROM sorteios s 
            LEFT JOIN participantes p ON s.id = p.sorteio_id 
            LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
            {$where_clause}
            GROUP BY s.id 
            ORDER BY s.created_at DESC
            {$limit_clause}
        ";
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao carregar sorteios: " . $e->getMessage());
        return [];
    }
}

function getTotalSorteios($search = '', $status_filter = '') {
    try {
        $db = getDatabase();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(nome LIKE ? OR descricao LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM sorteios {$where_clause}", $params);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Erro ao contar sorteios: " . $e->getMessage());
        return 0;
    }
}

function getSorteioById($id) {
    try {
        $db = getDatabase();
        $sorteio = $db->fetchOne("SELECT * FROM sorteios WHERE id = ?", [$id]);
        
        if ($sorteio && $sorteio['campos_config']) {
            $sorteio['campos_config'] = json_decode($sorteio['campos_config'], true);
        }
        
        return $sorteio;
    } catch (Exception $e) {
        error_log("Erro ao carregar sorteio: " . $e->getMessage());
        return null;
    }
}

function getDefaultSorteioData() {
    return [
        'nome' => '',
        'descricao' => '',
        'regulamento' => '',
        'data_inicio' => '',
        'data_fim' => '',
        'max_participantes' => 0,
        'qtd_sorteados' => 1,
        'status' => 'ativo',
        'campos_config' => [
            'nome' => ['enabled' => true, 'required' => true],
            'whatsapp' => ['enabled' => true, 'required' => false],
            'cpf' => ['enabled' => false, 'required' => false],
            'email' => ['enabled' => false, 'required' => false]
        ]
    ];
}

function deleteSorteio($id) {
    try {
        $db = getDatabase();
        
        // Verificar se tem participantes
        $participantes = $db->fetchOne("SELECT COUNT(*) as count FROM participantes WHERE sorteio_id = ?", [$id]);
        
        if ($participantes['count'] > 0) {
            return false; // Não permite excluir sorteio com participantes
        }
        
        $db->execute("DELETE FROM sorteios WHERE id = ?", [$id]);
        logActivity('Sorteio excluído', "ID: {$id}");
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao excluir sorteio: " . $e->getMessage());
        return false;
    }
}

function duplicateSorteio($id) {
    try {
        $db = getDatabase();
        $original = getSorteioById($id);
        
        if (!$original) {
            return ['success' => false, 'message' => 'Sorteio original não encontrado.'];
        }
        
        // Criar cópia com novo nome e URL
        $novo_nome = $original['nome'] . ' (Cópia)';
        $public_url = generatePublicUrl();
        
        $sql = "INSERT INTO sorteios (nome, descricao, regulamento, data_inicio, data_fim, 
                max_participantes, qtd_sorteados, campos_config, public_url, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $novo_nome,
            $original['descricao'],
            $original['regulamento'],
            null, // Reset datas
            null,
            $original['max_participantes'],
            $original['qtd_sorteados'],
            $original['campos_config'],
            $public_url,
            'ativo'
        ];
        
        $novo_id = $db->insert($sql, $params);
        logActivity('Sorteio duplicado', "Original: {$id}, Novo: {$novo_id}");
        
        return ['success' => true, 'message' => 'Sorteio duplicado com sucesso!'];
        
    } catch (Exception $e) {
        error_log("Erro ao duplicar sorteio: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor.'];
    }
}

// Incluir header
include 'templates/header.php';
?>

<!-- Container principal -->
<div class="p-6">
    
    <!-- Mensagens de feedback -->
    <?php if ($message): ?>
        <div class="mb-6 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <!-- Listagem de Sorteios -->
        <?php include 'templates/sorteios/list.php'; ?>
        
    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <!-- Formulário de Criação/Edição -->
        <?php include 'templates/sorteios/form.php'; ?>
        
    <?php endif; ?>
    
</div>

<?php
// Incluir footer
include 'templates/footer.php';
?>
