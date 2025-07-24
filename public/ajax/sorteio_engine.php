<?php
/**
 * Endpoint AJAX para Motor de Sorteio
 * Gerencia execução, histórico e validação de sorteios
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/classes/SorteioEngine.php';

// Verificar autenticação
if (!isAuthenticated()) {
    jsonError('Acesso negado', 401);
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método não permitido', 405);
}

// Verificar token CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonError('Token CSRF inválido', 403);
}

// Obter ação solicitada
$action = $_POST['action'] ?? '';

try {
    $engine = new SorteioEngine();
    
    switch ($action) {
        case 'executar_sorteio':
            handleExecutarSorteio($engine);
            break;
            
        case 'obter_historico':
            handleObterHistorico($engine);
            break;
            
        case 'obter_estatisticas':
            handleObterEstatisticas($engine);
            break;
            
        case 'validar_integridade':
            handleValidarIntegridade($engine);
            break;
            
        case 'remover_resultado':
            handleRemoverResultado($engine);
            break;
            
        case 'obter_participantes_elegiveis':
            handleObterParticipantesElegiveis($engine);
            break;
            
        default:
            jsonError('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    logSystem("Erro no motor de sorteio: " . $e->getMessage(), 'ERROR');
    jsonError('Erro interno do servidor: ' . $e->getMessage());
}

/**
 * Executa sorteio
 */
function handleExecutarSorteio($engine) {
    // Validar parâmetros
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    $quantidade = filter_var($_POST['quantidade'] ?? 1, FILTER_VALIDATE_INT);
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    if (!$quantidade || $quantidade <= 0) {
        jsonError('Quantidade deve ser maior que zero');
    }
    
    if ($quantidade > 100) {
        jsonError('Quantidade máxima de sorteados por vez é 100');
    }
    
    // Rate limiting para execução de sorteios
    if (!checkRateLimit('executar_sorteio', 10, 300)) { // 10 sorteios por 5 minutos
        jsonError('Muitas tentativas de sorteio. Aguarde alguns minutos.');
    }
    
    // Executar sorteio
    $resultado = $engine->executeSorteio($sorteioId, $quantidade);
    
    if ($resultado['success']) {
        // Log da atividade
        logActivity('SORTEIO_EXECUTADO', "Sorteio ID: {$sorteioId}, Quantidade: {$quantidade}");
        
        // Formatar dados dos sorteados para resposta
        $sorteados = array_map(function($participante) {
            return [
                'id' => $participante['id'],
                'nome' => $participante['nome'],
                'whatsapp' => $participante['whatsapp'] ?? '',
                'cpf' => $participante['cpf'] ?? '',
                'email' => $participante['email'] ?? '',
                'posicao' => $participante['posicao_sorteio'],
                'whatsapp_formatted' => !empty($participante['whatsapp']) ? formatWhatsApp($participante['whatsapp']) : '',
                'cpf_formatted' => !empty($participante['cpf']) ? formatCPF($participante['cpf']) : ''
            ];
        }, $resultado['sorteados']);
        
        jsonSuccess([
            'resultado_id' => $resultado['resultado_id'],
            'sorteados' => $sorteados,
            'total_participantes' => $resultado['total_participantes'],
            'quantidade_sorteada' => count($sorteados),
            'timestamp' => $resultado['timestamp'],
            'sorteio' => [
                'id' => $resultado['sorteio']['id'],
                'nome' => $resultado['sorteio']['nome']
            ]
        ], 'Sorteio executado com sucesso!');
        
    } else {
        jsonError($resultado['error'] ?? 'Erro desconhecido na execução do sorteio');
    }
}

/**
 * Obtém histórico de sorteios
 */
function handleObterHistorico($engine) {
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    $limit = filter_var($_POST['limit'] ?? 50, FILTER_VALIDATE_INT);
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    if ($limit > 500) {
        $limit = 500; // Limite máximo para performance
    }
    
    $historico = $engine->getSorteioHistory($sorteioId, $limit);
    
    // Formatar dados para exibição
    $historicoFormatado = array_map(function($item) {
        return [
            'id' => $item['id'],
            'participante_id' => $item['participante_id'],
            'nome' => $item['nome'],
            'whatsapp' => formatWhatsApp($item['whatsapp'] ?? ''),
            'cpf' => formatCPF($item['cpf'] ?? ''),
            'email' => $item['email'] ?? '',
            'posicao' => $item['posicao'],
            'data_sorteio' => formatDateBR($item['data_sorteio']),
            'resultado_id' => $item['resultado_id'] ?? null
        ];
    }, $historico);
    
    jsonSuccess([
        'historico' => $historicoFormatado,
        'total' => count($historicoFormatado)
    ]);
}

/**
 * Obtém estatísticas do sorteio
 */
function handleObterEstatisticas($engine) {
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    $stats = $engine->getSorteioStats($sorteioId);
    
    // Formatar datas
    if ($stats['primeiro_sorteio']) {
        $stats['primeiro_sorteio_formatted'] = formatDateBR($stats['primeiro_sorteio']);
    }
    
    if ($stats['ultimo_sorteio']) {
        $stats['ultimo_sorteio_formatted'] = formatDateBR($stats['ultimo_sorteio']);
    }
    
    // Calcular percentual de sorteados
    if ($stats['total_participantes'] > 0) {
        $stats['percentual_sorteados'] = round(($stats['total_sorteados'] / $stats['total_participantes']) * 100, 2);
    } else {
        $stats['percentual_sorteados'] = 0;
    }
    
    jsonSuccess($stats);
}

/**
 * Valida integridade dos resultados
 */
function handleValidarIntegridade($engine) {
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    $resultadoId = $_POST['resultado_id'] ?? null;
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    $validation = $engine->validateSorteioIntegrity($sorteioId, $resultadoId);
    
    jsonSuccess([
        'valid' => $validation['valid'],
        'issues' => $validation['issues'],
        'total_issues' => count($validation['issues'])
    ]);
}

/**
 * Remove resultado de sorteio
 */
function handleRemoverResultado($engine) {
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    $resultadoId = $_POST['resultado_id'] ?? '';
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    if (empty($resultadoId)) {
        jsonError('ID do resultado é obrigatório');
    }
    
    // Confirmação adicional
    $confirmacao = $_POST['confirmacao'] ?? '';
    if ($confirmacao !== 'CONFIRMAR_REMOCAO') {
        jsonError('Confirmação necessária para remoção');
    }
    
    $resultado = $engine->removeSorteioResult($sorteioId, $resultadoId);
    
    if ($resultado['success']) {
        logActivity('RESULTADO_REMOVIDO', "Sorteio ID: {$sorteioId}, Resultado ID: {$resultadoId}");
        
        jsonSuccess([
            'removed_count' => $resultado['removed_count']
        ], 'Resultado removido com sucesso');
        
    } else {
        jsonError($resultado['error'] ?? 'Erro ao remover resultado');
    }
}

/**
 * Obtém participantes elegíveis para sorteio
 */
function handleObterParticipantesElegiveis($engine) {
    $sorteioId = filter_var($_POST['sorteio_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if (!$sorteioId || $sorteioId <= 0) {
        jsonError('ID do sorteio inválido');
    }
    
    // Usar método privado através de reflexão ou criar método público
    $db = getDatabase();
    
    $participantes = $db->fetchAll(
        "SELECT p.id, p.nome, p.whatsapp, p.cpf, p.email, p.created_at
         FROM participantes p 
         WHERE p.sorteio_id = ? 
         AND p.id NOT IN (
             SELECT sr.participante_id 
             FROM sorteio_resultados sr 
             JOIN participantes p2 ON sr.participante_id = p2.id 
             WHERE p2.sorteio_id = ?
         )
         ORDER BY p.created_at ASC",
        [$sorteioId, $sorteioId]
    );
    
    // Formatar dados
    $participantesFormatados = array_map(function($p) {
        return [
            'id' => $p['id'],
            'nome' => $p['nome'],
            'whatsapp' => formatWhatsApp($p['whatsapp'] ?? ''),
            'cpf' => formatCPF($p['cpf'] ?? ''),
            'email' => $p['email'] ?? '',
            'created_at' => formatDateBR($p['created_at'])
        ];
    }, $participantes);
    
    jsonSuccess([
        'participantes' => $participantesFormatados,
        'total' => count($participantesFormatados)
    ]);
}
?>