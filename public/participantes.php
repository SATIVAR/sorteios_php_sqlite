<?php
/**
 * Gerenciamento de Participantes - Sistema de Sorteios
 * Interface completa para visualização e gerenciamento de participantes
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/admin_middleware.php';
require_once 'includes/validator.php';

// Configurações da página
$page_title = 'Gerenciar Participantes';
$current_page = 'participantes';
$show_sidebar = true;
$page_scripts = ['/assets/js/participantes.js'];

// Processar ações
$action = $_GET['action'] ?? 'list';
$sorteio_id = $_GET['sorteio_id'] ?? null;
$participante_id = $_GET['participante_id'] ?? null;
$search = $_GET['search'] ?? '';
$format = $_GET['format'] ?? 'csv';
$message = '';
$error = '';

$db = getDatabase();

// Processar formulário de adição de participante
if ($_POST && isset($_POST['csrf_token'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        $result = processParticipantForm($_POST, $sorteio_id);
        if ($result['success']) {
            $message = $result['message'];
            $action = 'view'; // Redireciona para visualização após adicionar
        } else {
            $error = $result['message'];
        }
    }
}

// Processar exclusão de participante
if ($action === 'delete' && $participante_id && $sorteio_id) {
    $result = deleteParticipant($participante_id, $sorteio_id);
    if ($result['success']) {
        $message = $result['message'];
        $action = 'view';
    } else {
        $error = $result['message'];
    }
}

// Processar exportação
if ($action === 'export' && $sorteio_id) {
    $result = exportParticipants($sorteio_id, $format);
    if ($result['success']) {
        // Headers para download
        header('Content-Type: ' . $result['content_type']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . strlen($result['content']));
        echo $result['content'];
        exit;
    } else {
        $error = $result['message'];
        $action = 'list';
    }
}

// Carregar dados conforme a ação
switch ($action) {
    case 'view':
        if (!$sorteio_id) {
            $error = 'ID do sorteio não informado.';
            $action = 'list';
            break;
        }
        $sorteio = getSorteioById($sorteio_id);
        if (!$sorteio) {
            $error = 'Sorteio não encontrado.';
            $action = 'list';
            break;
        }
        $participantes = getParticipantsBySorteio($sorteio_id, $search);
        $total_participantes = getTotalParticipantsBySorteio($sorteio_id, $search);
        break;
        
    case 'add':
        if (!$sorteio_id) {
            $error = 'ID do sorteio não informado.';
            $action = 'list';
            break;
        }
        $sorteio = getSorteioById($sorteio_id);
        if (!$sorteio) {
            $error = 'Sorteio não encontrado.';
            $action = 'list';
        }
        break;
        
    case 'list':
    default:
        $sorteios = getAllSorteiosWithParticipants($search);
        break;
}

/**
 * Funções auxiliares
 */

function processParticipantForm($data, $sorteio_id) {
    $validator = getValidator();
    
    // Regras de validação básicas
    $rules = [
        'nome' => 'required|max:255'
    ];
    
    // Obter configuração do sorteio
    $sorteio = getSorteioById($sorteio_id);
    if (!$sorteio) {
        return ['success' => false, 'message' => 'Sorteio não encontrado.'];
    }
    
    $campos_config = json_decode($sorteio['campos_config'], true) ?? [];
    
    // Adicionar regras baseadas na configuração
    if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
        if (isset($campos_config['whatsapp']['required']) && $campos_config['whatsapp']['required']) {
            $rules['whatsapp'] = 'required|max:20';
        } else {
            $rules['whatsapp'] = 'max:20';
        }
    }
    
    if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
        if (isset($campos_config['cpf']['required']) && $campos_config['cpf']['required']) {
            $rules['cpf'] = 'required|cpf';
        } else {
            $rules['cpf'] = 'cpf';
        }
    }
    
    if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
        if (isset($campos_config['email']['required']) && $campos_config['email']['required']) {
            $rules['email'] = 'required|email';
        } else {
            $rules['email'] = 'email';
        }
    }
    
    $validation = validateFormData($data, $rules);
    
    if (!$validation['success']) {
        return [
            'success' => false,
            'message' => 'Dados inválidos: ' . implode(', ', $validation['errors'])
        ];
    }
    
    $cleanData = $validation['data'];
    
    try {
        $db = getDatabase();
        
        // Verificar limite de participantes
        if ($sorteio['max_participantes'] > 0) {
            $current_count = getTotalParticipantsBySorteio($sorteio_id);
            if ($current_count >= $sorteio['max_participantes']) {
                return ['success' => false, 'message' => 'Limite de participantes atingido.'];
            }
        }
        
        // Verificar duplicatas por CPF se habilitado
        if (!empty($cleanData['cpf'])) {
            $existing = $db->fetchOne(
                "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?",
                [$sorteio_id, preg_replace('/\D/', '', $cleanData['cpf'])]
            );
            if ($existing) {
                return ['success' => false, 'message' => 'Já existe um participante com este CPF neste sorteio.'];
            }
        }
        
        // Processar campos extras
        $campos_extras = [];
        foreach ($campos_config as $campo_nome => $config) {
            if (strpos($campo_nome, 'custom_') === 0 && isset($config['enabled']) && $config['enabled']) {
                if (isset($data[$campo_nome])) {
                    $campos_extras[$campo_nome] = sanitizeInput($data[$campo_nome]);
                }
            }
        }
        
        // Inserir participante
        $sql = "INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email, campos_extras, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $sorteio_id,
            $cleanData['nome'],
            $cleanData['whatsapp'] ?? null,
            !empty($cleanData['cpf']) ? preg_replace('/\D/', '', $cleanData['cpf']) : null,
            $cleanData['email'] ?? null,
            !empty($campos_extras) ? json_encode($campos_extras) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $participante_id = $db->insert($sql, $params);
        
        // Log da atividade
        logActivity('Participante adicionado manualmente', "Sorteio: {$sorteio_id}, Participante: {$participante_id}, Nome: {$cleanData['nome']}");
        
        return ['success' => true, 'message' => 'Participante adicionado com sucesso!'];
        
    } catch (Exception $e) {
        error_log("Erro ao adicionar participante: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor.'];
    }
}

function deleteParticipant($participante_id, $sorteio_id) {
    try {
        $db = getDatabase();
        
        // Verificar se o participante existe e não foi sorteado
        $participante = $db->fetchOne(
            "SELECT p.*, sr.id as foi_sorteado 
             FROM participantes p 
             LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id 
             WHERE p.id = ? AND p.sorteio_id = ?",
            [$participante_id, $sorteio_id]
        );
        
        if (!$participante) {
            return ['success' => false, 'message' => 'Participante não encontrado.'];
        }
        
        if ($participante['foi_sorteado']) {
            return ['success' => false, 'message' => 'Não é possível remover participante que já foi sorteado.'];
        }
        
        // Excluir participante
        $db->execute("DELETE FROM participantes WHERE id = ? AND sorteio_id = ?", [$participante_id, $sorteio_id]);
        
        // Log da atividade
        logActivity('Participante removido', "Sorteio: {$sorteio_id}, Participante: {$participante_id}, Nome: {$participante['nome']}");
        
        return ['success' => true, 'message' => 'Participante removido com sucesso!'];
        
    } catch (Exception $e) {
        error_log("Erro ao excluir participante: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor.'];
    }
}

function exportParticipants($sorteio_id, $format) {
    try {
        $db = getDatabase();
        
        // Obter dados do sorteio
        $sorteio = getSorteioById($sorteio_id);
        if (!$sorteio) {
            return ['success' => false, 'message' => 'Sorteio não encontrado.'];
        }
        
        // Obter participantes
        $participantes = getParticipantsBySorteio($sorteio_id);
        if (empty($participantes)) {
            return ['success' => false, 'message' => 'Nenhum participante encontrado para exportar.'];
        }
        
        $campos_config = json_decode($sorteio['campos_config'], true) ?? [];
        $filename = 'participantes_' . sanitizeFilename($sorteio['nome']) . '_' . date('Y-m-d_H-i-s');
        
        if ($format === 'csv') {
            return exportToCSV($participantes, $campos_config, $filename);
        } elseif ($format === 'excel') {
            return exportToExcel($participantes, $campos_config, $filename);
        } else {
            return ['success' => false, 'message' => 'Formato de exportação inválido.'];
        }
        
    } catch (Exception $e) {
        error_log("Erro na exportação: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor.'];
    }
}

function exportToCSV($participantes, $campos_config, $filename) {
    $output = fopen('php://temp', 'r+');
    
    // Cabeçalhos
    $headers = ['ID', 'Nome'];
    if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
        $headers[] = 'WhatsApp';
    }
    if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
        $headers[] = 'CPF';
    }
    if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
        $headers[] = 'Email';
    }
    $headers[] = 'Status';
    $headers[] = 'Data Cadastro';
    
    fputcsv($output, $headers);
    
    // Dados
    foreach ($participantes as $participante) {
        $row = [$participante['id'], $participante['nome']];
        
        if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
            $row[] = $participante['whatsapp'] ?? '';
        }
        if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
            $row[] = $participante['cpf'] ? formatCPF($participante['cpf']) : '';
        }
        if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
            $row[] = $participante['email'] ?? '';
        }
        
        $row[] = $participante['foi_sorteado'] ? 'Sorteado (' . $participante['posicao_sorteio'] . 'º)' : 'Participando';
        $row[] = formatDateBR($participante['created_at']);
        
        fputcsv($output, $row);
    }
    
    rewind($output);
    $content = stream_get_contents($output);
    fclose($output);
    
    return [
        'success' => true,
        'content' => $content,
        'filename' => $filename . '.csv',
        'content_type' => 'text/csv; charset=utf-8'
    ];
}

function exportToExcel($participantes, $campos_config, $filename) {
    // Implementação básica de Excel (formato HTML que o Excel aceita)
    $html = '<table border="1">';
    
    // Cabeçalhos
    $html .= '<tr>';
    $html .= '<th>ID</th><th>Nome</th>';
    if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
        $html .= '<th>WhatsApp</th>';
    }
    if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
        $html .= '<th>CPF</th>';
    }
    if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
        $html .= '<th>Email</th>';
    }
    $html .= '<th>Status</th><th>Data Cadastro</th>';
    $html .= '</tr>';
    
    // Dados
    foreach ($participantes as $participante) {
        $html .= '<tr>';
        $html .= '<td>' . $participante['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($participante['nome']) . '</td>';
        
        if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
            $html .= '<td>' . htmlspecialchars($participante['whatsapp'] ?? '') . '</td>';
        }
        if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
            $html .= '<td>' . ($participante['cpf'] ? formatCPF($participante['cpf']) : '') . '</td>';
        }
        if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
            $html .= '<td>' . htmlspecialchars($participante['email'] ?? '') . '</td>';
        }
        
        $status = $participante['foi_sorteado'] ? 'Sorteado (' . $participante['posicao_sorteio'] . 'º)' : 'Participando';
        $html .= '<td>' . $status . '</td>';
        $html .= '<td>' . formatDateBR($participante['created_at']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    return [
        'success' => true,
        'content' => $html,
        'filename' => $filename . '.xls',
        'content_type' => 'application/vnd.ms-excel'
    ];
}

function getAllSorteiosWithParticipants($search = '') {
    try {
        $db = getDatabase();
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(s.nome LIKE ? OR s.descricao LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
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
        ";
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao carregar sorteios: " . $e->getMessage());
        return [];
    }
}

function getParticipantsBySorteio($sorteio_id, $search = '') {
    try {
        $db = getDatabase();
        
        $where_conditions = ["p.sorteio_id = ?"];
        $params = [$sorteio_id];
        
        if (!empty($search)) {
            $where_conditions[] = "(p.nome LIKE ? OR p.whatsapp LIKE ? OR p.cpf LIKE ? OR p.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT p.*, 
                   sr.id as foi_sorteado,
                   sr.posicao as posicao_sorteio
            FROM participantes p 
            LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
            {$where_clause}
            ORDER BY p.created_at DESC
        ";
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao carregar participantes: " . $e->getMessage());
        return [];
    }
}

function getTotalParticipantsBySorteio($sorteio_id, $search = '') {
    try {
        $db = getDatabase();
        
        $where_conditions = ["sorteio_id = ?"];
        $params = [$sorteio_id];
        
        if (!empty($search)) {
            $where_conditions[] = "(nome LIKE ? OR whatsapp LIKE ? OR cpf LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM participantes {$where_clause}", $params);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Erro ao contar participantes: " . $e->getMessage());
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
        <?php include 'templates/participantes/list.php'; ?>
        
    <?php elseif ($action === 'view'): ?>
        <!-- Visualização de Participantes -->
        <?php include 'templates/participantes/view.php'; ?>
        
    <?php elseif ($action === 'add'): ?>
        <!-- Formulário de Adição -->
        <?php include 'templates/participantes/form.php'; ?>
        
    <?php endif; ?>
    
</div>

<?php
// Incluir footer
include 'templates/footer.php';
?>