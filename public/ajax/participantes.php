<?php
/**
 * Endpoint AJAX para Gerenciamento de Participantes
 * Sistema de Sorteios
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';
require_once '../includes/validator.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Rate limiting
if (!checkRateLimit('ajax_participantes', 30, 60)) {
    jsonError('Muitas tentativas. Tente novamente em alguns instantes.', 429);
}

// Verificar CSRF para operações POST
if ($method === 'POST' && !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonError('Token de segurança inválido', 403);
}

try {
    switch ($action) {
        case 'get_stats':
            handleGetStats();
            break;
            
        case 'search':
            handleSearch();
            break;
            
        case 'add':
            handleAdd();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        case 'bulk_delete':
            handleBulkDelete();
            break;
            
        case 'export_preview':
            handleExportPreview();
            break;
            
        case 'validate_field':
            handleValidateField();
            break;
            
        default:
            jsonError('Ação não reconhecida', 400);
    }
} catch (Exception $e) {
    error_log("Erro no AJAX participantes: " . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}

/**
 * Obtém estatísticas de participantes
 */
function handleGetStats() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    $stats = getParticipantStats($sorteio_id);
    
    if ($stats === null) {
        jsonError('Erro ao obter estatísticas');
    }
    
    jsonSuccess($stats, 'Estatísticas obtidas com sucesso');
}

/**
 * Busca participantes com filtros
 */
function handleSearch() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    $search_term = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = min(100, max(10, intval($_GET['per_page'] ?? 20)));
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    // Filtros adicionais
    $filters = [];
    if (isset($_GET['foi_sorteado'])) {
        $filters['foi_sorteado'] = $_GET['foi_sorteado'] === '1';
    }
    if (!empty($_GET['data_inicio'])) {
        $filters['data_inicio'] = $_GET['data_inicio'];
    }
    if (!empty($_GET['data_fim'])) {
        $filters['data_fim'] = $_GET['data_fim'];
    }
    
    $participantes = searchParticipants($sorteio_id, $search_term, $filters);
    
    // Paginação
    $total = count($participantes);
    $offset = ($page - 1) * $per_page;
    $participantes_page = array_slice($participantes, $offset, $per_page);
    
    // Formatar dados para exibição
    $formatted = array_map(function($p) {
        return formatParticipantData($p);
    }, $participantes_page);
    
    jsonSuccess([
        'participantes' => $formatted,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

/**
 * Adiciona participante manualmente
 */
function handleAdd() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Método não permitido', 405);
    }
    
    $sorteio_id = $_POST['sorteio_id'] ?? null;
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    // Carregar sorteio
    $sorteio = getSorteioById($sorteio_id);
    if (!$sorteio) {
        jsonError('Sorteio não encontrado');
    }
    
    $campos_config = $sorteio['campos_config'] ?? [];
    
    // Validar dados
    $validation = validateParticipantData($_POST, $campos_config, $sorteio_id);
    
    if (!$validation['valid']) {
        jsonError('Dados inválidos', 400, $validation['errors']);
    }
    
    // Verificar limite de participantes
    if (!validateParticipantLimit($sorteio_id, $sorteio['max_participantes'])) {
        jsonError('Limite de participantes atingido');
    }
    
    try {
        $db = getDatabase();
        $db->beginTransaction();
        
        // Processar campos extras
        $campos_extras = [];
        foreach ($campos_config as $campo_nome => $config) {
            if (strpos($campo_nome, 'custom_') === 0 && isset($_POST[$campo_nome])) {
                $campos_extras[$campo_nome] = sanitizeInput($_POST[$campo_nome]);
            }
        }
        
        // Inserir participante
        $participante_id = $db->insert(
            "INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email, campos_extras, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $sorteio_id,
                sanitizeInput($_POST['nome']),
                !empty($_POST['whatsapp']) ? preg_replace('/[^0-9]/', '', $_POST['whatsapp']) : null,
                !empty($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : null,
                !empty($_POST['email']) ? sanitizeInput($_POST['email']) : null,
                !empty($campos_extras) ? json_encode($campos_extras) : null,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
        
        $db->commit();
        
        // Log da atividade
        logActivity('Participante adicionado via AJAX', "Sorteio: {$sorteio_id}, Participante: {$participante_id}");
        
        // Obter dados do participante criado
        $participante = $db->fetchOne(
            "SELECT p.*, 0 as foi_sorteado, NULL as posicao_sorteio 
             FROM participantes p WHERE p.id = ?",
            [$participante_id]
        );
        
        jsonSuccess([
            'participante' => formatParticipantData($participante),
            'id' => $participante_id
        ], 'Participante adicionado com sucesso');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Erro ao adicionar participante: " . $e->getMessage());
        jsonError('Erro ao adicionar participante');
    }
}

/**
 * Remove participante
 */
function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Método não permitido', 405);
    }
    
    $participante_id = $_POST['participante_id'] ?? null;
    $sorteio_id = $_POST['sorteio_id'] ?? null;
    
    if (!$participante_id || !$sorteio_id) {
        jsonError('IDs são obrigatórios');
    }
    
    // Verificar se pode remover
    if (!canRemoveParticipant($participante_id)) {
        jsonError('Não é possível remover participante que foi sorteado');
    }
    
    try {
        $db = getDatabase();
        
        // Obter dados do participante para log
        $participante = $db->fetchOne(
            "SELECT nome FROM participantes WHERE id = ? AND sorteio_id = ?",
            [$participante_id, $sorteio_id]
        );
        
        if (!$participante) {
            jsonError('Participante não encontrado');
        }
        
        $affected = $db->execute(
            "DELETE FROM participantes WHERE id = ? AND sorteio_id = ?",
            [$participante_id, $sorteio_id]
        );
        
        if ($affected === 0) {
            jsonError('Participante não encontrado');
        }
        
        // Log da atividade
        logActivity('Participante removido via AJAX', "Sorteio: {$sorteio_id}, Nome: {$participante['nome']}");
        
        jsonSuccess(['id' => $participante_id], 'Participante removido com sucesso');
        
    } catch (Exception $e) {
        error_log("Erro ao remover participante: " . $e->getMessage());
        jsonError('Erro ao remover participante');
    }
}

/**
 * Remove múltiplos participantes
 */
function handleBulkDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Método não permitido', 405);
    }
    
    $participante_ids = $_POST['participante_ids'] ?? [];
    $sorteio_id = $_POST['sorteio_id'] ?? null;
    
    if (empty($participante_ids) || !$sorteio_id) {
        jsonError('IDs são obrigatórios');
    }
    
    if (!is_array($participante_ids)) {
        $participante_ids = explode(',', $participante_ids);
    }
    
    // Limitar quantidade para evitar timeout
    if (count($participante_ids) > 50) {
        jsonError('Máximo de 50 participantes por operação');
    }
    
    try {
        $db = getDatabase();
        $db->beginTransaction();
        
        $removed_count = 0;
        $errors = [];
        
        foreach ($participante_ids as $participante_id) {
            $participante_id = intval($participante_id);
            
            // Verificar se pode remover
            if (!canRemoveParticipant($participante_id)) {
                $errors[] = "Participante ID {$participante_id} foi sorteado e não pode ser removido";
                continue;
            }
            
            $affected = $db->execute(
                "DELETE FROM participantes WHERE id = ? AND sorteio_id = ?",
                [$participante_id, $sorteio_id]
            );
            
            if ($affected > 0) {
                $removed_count++;
            }
        }
        
        $db->commit();
        
        // Log da atividade
        logActivity('Remoção em lote via AJAX', "Sorteio: {$sorteio_id}, Removidos: {$removed_count}");
        
        $message = "Removidos {$removed_count} participantes";
        if (!empty($errors)) {
            $message .= ". Avisos: " . implode(', ', $errors);
        }
        
        jsonSuccess([
            'removed_count' => $removed_count,
            'errors' => $errors
        ], $message);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Erro na remoção em lote: " . $e->getMessage());
        jsonError('Erro na remoção em lote');
    }
}

/**
 * Preview de exportação
 */
function handleExportPreview() {
    $sorteio_id = $_GET['sorteio_id'] ?? null;
    $format = $_GET['format'] ?? 'csv';
    
    if (!$sorteio_id) {
        jsonError('ID do sorteio é obrigatório');
    }
    
    $report = generateParticipantReport($sorteio_id);
    
    if (!$report) {
        jsonError('Erro ao gerar preview');
    }
    
    // Limitar preview a 10 registros
    $preview_data = array_slice($report['participantes'], 0, 10);
    
    jsonSuccess([
        'preview' => $preview_data,
        'total_records' => $report['total_participantes'],
        'sorteio' => $report['sorteio'],
        'format' => $format
    ], 'Preview gerado com sucesso');
}

/**
 * Valida campo individual
 */
function handleValidateField() {
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $sorteio_id = $_POST['sorteio_id'] ?? null;
    
    if (!$field) {
        jsonError('Campo é obrigatório');
    }
    
    $validator = getValidator();
    $validator->clearErrors();
    
    $isValid = true;
    $message = '';
    
    switch ($field) {
        case 'nome':
            if (empty($value) || strlen(trim($value)) < 2) {
                $isValid = false;
                $message = 'Nome deve ter pelo menos 2 caracteres';
            }
            break;
            
        case 'email':
            if (!empty($value) && !$validator->email($value)) {
                $isValid = false;
                $message = 'Email inválido';
            }
            break;
            
        case 'cpf':
            if (!empty($value)) {
                if (!$validator->cpf($value)) {
                    $isValid = false;
                    $message = 'CPF inválido';
                } elseif ($sorteio_id) {
                    // Verificar duplicata
                    $db = getDatabase();
                    $existing = $db->fetchOne(
                        "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?",
                        [$sorteio_id, preg_replace('/[^0-9]/', '', $value)]
                    );
                    
                    if ($existing) {
                        $isValid = false;
                        $message = 'Este CPF já está cadastrado neste sorteio';
                    }
                }
            }
            break;
            
        case 'whatsapp':
            if (!empty($value) && !$validator->whatsapp($value)) {
                $isValid = false;
                $message = 'WhatsApp inválido';
            }
            break;
            
        default:
            jsonError('Campo não reconhecido');
    }
    
    jsonSuccess([
        'valid' => $isValid,
        'message' => $message,
        'field' => $field
    ]);
}

/**
 * Função auxiliar para resposta JSON de erro com dados extras
 */
function jsonError($message = 'Erro interno', $code = 400, $extra_data = null) {
    header('Content-Type: application/json');
    http_response_code($code);
    
    $response = [
        'success' => false,
        'message' => $message,
        'data' => null
    ];
    
    if ($extra_data !== null) {
        $response['errors'] = $extra_data;
    }
    
    echo json_encode($response);
    exit;
}
?>