<?php
/**
 * Sistema de Sorteios - Funções Auxiliares
 * Conjunto de funções utilitárias para o sistema
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

// Incluir sistema de cache
require_once __DIR__ . '/cache.php';

// Incluir sistema de minificação
require_once __DIR__ . '/minify.php';

/**
 * Funções de Instalação e Configuração
 */

/**
 * Verifica se o sistema está instalado
 */
function isSystemInstalled() {
    require_once 'install_check.php';
    return isSystemFullyInstalled();
}

/**
 * Cria os diretórios necessários do sistema
 */
function createSystemDirectories() {
    $directories = [
        DATA_PATH,
        DATA_PATH . '/logs',
        DATA_PATH . '/backups',
        DATA_PATH . '/exports',
        ASSETS_PATH . '/css',
        ASSETS_PATH . '/js',
        ASSETS_PATH . '/images'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return false;
            }
        }
    }
    
    // Criar arquivos .htaccess de proteção
    $htaccess_content = "Order Allow,Deny\nDeny from all";
    
    file_put_contents(DATA_PATH . '/.htaccess', $htaccess_content);
    file_put_contents(DATA_PATH . '/logs/.htaccess', $htaccess_content);
    file_put_contents(DATA_PATH . '/backups/.htaccess', $htaccess_content);
    
    return true;
}

/**
 * Funções de Segurança
 */

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Se já existe token para esta sessão, reutiliza-o
    if (isset($_SESSION[CSRF_TOKEN_NAME]) && !empty($_SESSION[CSRF_TOKEN_NAME])) {
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    // Caso contrário, gera um novo token criptograficamente seguro
    $token = bin2hex(random_bytes(32));
    $_SESSION[CSRF_TOKEN_NAME] = $token;
    return $token;
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitiza entrada de dados (função de compatibilidade)
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email (função de compatibilidade)
 */
function validateEmail($email) {
    $validator = getValidator();
    $validator->clearErrors();
    return $validator->email($email);
}

/**
 * Valida CPF (função de compatibilidade)
 */
function validateCPF($cpf) {
    $validator = getValidator();
    $validator->clearErrors();
    return $validator->cpf($cpf);
}

/**
 * Valida WhatsApp (função de compatibilidade)
 */
function validateWhatsApp($whatsapp) {
    $validator = getValidator();
    $validator->clearErrors();
    return $validator->whatsapp($whatsapp);
}

/**
 * Valida e sanitiza dados de formulário
 */
function validateFormData($data, $rules) {
    $validator = getValidator();
    $sanitized = $validator->validateArray($data, $rules);
    
    if ($validator->hasErrors()) {
        return [
            'success' => false,
            'errors' => $validator->getFormattedErrors(),
            'data' => $sanitized
        ];
    }
    
    return [
        'success' => true,
        'errors' => [],
        'data' => $sanitized
    ];
}

/**
 * Proteção contra ataques comuns
 */
function detectMaliciousInput($value) {
    $validator = getValidator();
    
    if (is_string($value)) {
        return $validator->detectSQLInjection($value) || $validator->detectXSS($value);
    }
    
    if (is_array($value)) {
        foreach ($value as $item) {
            if (detectMaliciousInput($item)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Funções de Utilidade
 */

/**
 * Formata CPF para exibição
 */
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Formata WhatsApp para exibição
 */
function formatWhatsApp($whatsapp) {
    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
    return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $whatsapp);
}

/**
 * Gera URL pública única para sorteio
 */
function generatePublicUrl() {
    return bin2hex(random_bytes(16));
}

/**
 * Converte data para formato brasileiro
 */
function formatDateBR($date) {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Converte data brasileira para formato SQL
 */
function formatDateSQL($date_br) {
    if (empty($date_br)) return null;
    
    $parts = explode(' ', $date_br);
    $date_part = $parts[0];
    $time_part = isset($parts[1]) ? $parts[1] : '00:00';
    
    $date_array = explode('/', $date_part);
    if (count($date_array) != 3) return null;
    
    return $date_array[2] . '-' . $date_array[1] . '-' . $date_array[0] . ' ' . $time_part;
}

/**
 * Funções de Log e Debug
 */

/**
 * Registra log do sistema
 */
function logSystem($message, $level = 'INFO') {
    $log_file = DATA_PATH . '/logs/system.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Registra atividade do usuário
 */
function logActivity($action, $details = '') {
    $log_file = DATA_PATH . '/logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[{$timestamp}] IP: {$ip} | Action: {$action} | Details: {$details} | UA: {$user_agent}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Funções de Rate Limiting
 */

/**
 * Verifica rate limiting por IP
 */
function checkRateLimit($action, $limit = 5, $window = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cache_file = DATA_PATH . '/logs/rate_limit_' . md5($ip . $action) . '.tmp';
    
    $current_time = time();
    $attempts = [];
    
    // Carrega tentativas anteriores
    if (file_exists($cache_file)) {
        $data = file_get_contents($cache_file);
        $attempts = json_decode($data, true) ?: [];
    }
    
    // Remove tentativas antigas
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    // Verifica se excedeu o limite
    if (count($attempts) >= $limit) {
        return false;
    }
    
    // Adiciona nova tentativa
    $attempts[] = $current_time;
    file_put_contents($cache_file, json_encode($attempts));
    
    return true;
}

/**
 * Funções de Resposta JSON
 */

/**
 * Retorna resposta JSON de sucesso
 */
function jsonSuccess($data = [], $message = 'Sucesso') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Retorna resposta JSON de erro
 */
function jsonError($message = 'Erro interno', $code = 400) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null
    ]);
    exit;
}

/**
 * Funções de Template
 */

/**
 * Inclui template com variáveis
 */
function includeTemplate($template, $vars = []) {
    extract($vars);
    $template_file = TEMPLATES_PATH . '/' . $template . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        echo "Template não encontrado: {$template}";
    }
}

/**
 * Gera URL base do sistema
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Funções de Backup
 */

/**
 * Cria backup do banco de dados
 */
function createDatabaseBackup() {
    if (!file_exists(DB_PATH)) {
        return false;
    }
    
    $backup_dir = DATA_PATH . '/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.db';
    
    if (copy(DB_PATH, $backup_file)) {
        logSystem("Backup criado: {$backup_file}");
        return $backup_file;
    }
    
    return false;
}

/**
 * Remove backups antigos (mantém apenas os 10 mais recentes)
 */
function cleanOldBackups() {
    $backup_dir = DATA_PATH . '/backups';
    if (!is_dir($backup_dir)) {
        return;
    }
    
    $files = glob($backup_dir . '/backup_*.db');
    if (count($files) <= 10) {
        return;
    }
    
    // Ordena por data de modificação
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Remove os mais antigos
    $to_remove = array_slice($files, 0, count($files) - 10);
    foreach ($to_remove as $file) {
        unlink($file);
    }
}

/**
 * Processa formulário de participante (versão para página pública)
 */
function processParticipantForm($postData, $sorteio, $camposConfig) {
    $validator = getValidator();
    
    // Definir regras de validação baseadas na configuração
    $rules = [
        'nome' => [
            'required' => true,
            'sanitize' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ]
    ];
    
    // Adicionar regras condicionais baseadas na configuração
    if ($camposConfig['whatsapp']['enabled']) {
        $rules['whatsapp'] = [
            'required' => $camposConfig['whatsapp']['required'],
            'sanitize' => 'whatsapp',
            'whatsapp' => true
        ];
    }
    
    if ($camposConfig['email']['enabled']) {
        $rules['email'] = [
            'required' => $camposConfig['email']['required'],
            'sanitize' => 'email',
            'email' => true
        ];
    }
    
    if ($camposConfig['cpf']['enabled']) {
        $rules['cpf'] = [
            'required' => $camposConfig['cpf']['required'],
            'sanitize' => 'cpf',
            'cpf' => true
        ];
    }
    
    // Validar dados principais
    $result = validateFormData($postData, $rules);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    // Processar campos extras
    $camposExtras = [];
    if (!empty($camposConfig['campos_extras']) && !empty($postData['campos_extras'])) {
        foreach ($camposConfig['campos_extras'] as $index => $campo) {
            $value = $postData['campos_extras'][$index] ?? '';
            
            if ($campo['required'] && empty($value)) {
                $result['success'] = false;
                $result['errors']['campos_extras'][$index] = "O campo {$campo['label']} é obrigatório";
                continue;
            }
            
            if (!empty($value)) {
                $camposExtras[$index] = sanitizeInput($value);
            }
        }
    }
    
    if (!$result['success']) {
        return $result;
    }
    
    // Verificar duplicatas (por CPF se habilitado, senão por nome + whatsapp)
    $db = getDatabase();
    
    if ($camposConfig['cpf']['enabled'] && !empty($data['cpf'])) {
        $existing = $db->fetchOne(
            "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?",
            [$sorteio['id'], $data['cpf']]
        );
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['cpf' => 'Este CPF já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    } else {
        // Verificar por nome + whatsapp
        $whereClause = "sorteio_id = ? AND nome = ?";
        $params = [$sorteio['id'], $data['nome']];
        
        if (!empty($data['whatsapp'])) {
            $whereClause .= " AND whatsapp = ?";
            $params[] = $data['whatsapp'];
        }
        
        $existing = $db->fetchOne("SELECT id FROM participantes WHERE $whereClause", $params);
        
        if ($existing) {
            return [
                'success' => false,
                'errors' => ['geral' => 'Você já está cadastrado neste sorteio'],
                'data' => $data
            ];
        }
    }
    
    // Inserir participante
    try {
        $db->beginTransaction();
        
        $participanteId = $db->insert(
            "INSERT INTO participantes (sorteio_id, nome, whatsapp, cpf, email, campos_extras, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
            [
                $sorteio['id'],
                $data['nome'],
                $data['whatsapp'] ?? null,
                $data['cpf'] ?? null,
                $data['email'] ?? null,
                !empty($camposExtras) ? json_encode($camposExtras) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        $db->commit();
        
        return [
            'success' => true,
            'errors' => [],
            'data' => array_merge($data, ['id' => $participanteId])
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        logSystem("Erro ao cadastrar participante: " . $e->getMessage(), 'ERROR');
        
        return [
            'success' => false,
            'errors' => ['geral' => 'Erro interno. Tente novamente em alguns instantes.'],
            'data' => $data
        ];
    }
}

/**
 * Sanitiza nome de arquivo para exportação
 */
function sanitizeFilename($filename) {
    // Remove caracteres especiais e acentos
    $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    $filename = trim($filename, '_');
    
    return $filename ?: 'export';
}

/**
 * Verifica se participante pode ser removido
 */
function canRemoveParticipant($participante_id) {
    try {
        $db = getDatabase();
        
        // Verifica se o participante foi sorteado
        $sorteado = $db->fetchOne(
            "SELECT id FROM sorteio_resultados WHERE participante_id = ?", 
            [$participante_id]
        );
        
        return !$sorteado; // Pode remover se não foi sorteado
    } catch (Exception $e) {
        error_log("Erro ao verificar participante: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém estatísticas de participantes por sorteio
 */
function getParticipantStats($sorteio_id) {
    try {
        $db = getDatabase();
        
        $stats = [
            'total' => 0,
            'sorteados' => 0,
            'por_data' => [],
            'campos_preenchidos' => []
        ];
        
        // Total de participantes
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM participantes WHERE sorteio_id = ?", 
            [$sorteio_id]
        );
        $stats['total'] = $result['count'] ?? 0;
        
        // Total de sorteados
        $result = $db->fetchOne(
            "SELECT COUNT(DISTINCT sr.participante_id) as count 
             FROM sorteio_resultados sr 
             JOIN participantes p ON sr.participante_id = p.id 
             WHERE p.sorteio_id = ?", 
            [$sorteio_id]
        );
        $stats['sorteados'] = $result['count'] ?? 0;
        
        // Participantes por data (últimos 30 dias)
        $por_data = $db->fetchAll(
            "SELECT DATE(created_at) as data, COUNT(*) as count 
             FROM participantes 
             WHERE sorteio_id = ? AND created_at >= date('now', '-30 days')
             GROUP BY DATE(created_at) 
             ORDER BY data ASC", 
            [$sorteio_id]
        );
        
        foreach ($por_data as $item) {
            $stats['por_data'][$item['data']] = $item['count'];
        }
        
        // Estatísticas de campos preenchidos
        $campos = ['whatsapp', 'cpf', 'email'];
        foreach ($campos as $campo) {
            $result = $db->fetchOne(
                "SELECT COUNT(*) as count FROM participantes 
                 WHERE sorteio_id = ? AND $campo IS NOT NULL AND $campo != ''", 
                [$sorteio_id]
            );
            $stats['campos_preenchidos'][$campo] = $result['count'] ?? 0;
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
        return [];
    }
}

/**
 * Valida limite de participantes
 */
function validateParticipantLimit($sorteio_id, $max_participantes) {
    if ($max_participantes <= 0) {
        return true; // Sem limite
    }
    
    try {
        $db = getDatabase();
        $result = $db->fetchOne(
            "SELECT COUNT(*) as count FROM participantes WHERE sorteio_id = ?", 
            [$sorteio_id]
        );
        
        return ($result['count'] ?? 0) < $max_participantes;
    } catch (Exception $e) {
        error_log("Erro ao validar limite: " . $e->getMessage());
        return false;
    }
}

/**
 * Gera relatório de participantes em formato array
 */
function generateParticipantReport($sorteio_id, $format = 'array') {
    try {
        $db = getDatabase();
        
        // Obter dados do sorteio
        $sorteio = getSorteioById($sorteio_id);
        if (!$sorteio) {
            throw new Exception('Sorteio não encontrado');
        }
        
        // Obter participantes com informações de sorteio
        $participantes = $db->fetchAll(
            "SELECT p.*, 
                    CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END as foi_sorteado,
                    sr.posicao as posicao_sorteio,
                    sr.data_sorteio
             FROM participantes p 
             LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
             WHERE p.sorteio_id = ?
             ORDER BY p.created_at ASC", 
            [$sorteio_id]
        );
        
        $report = [
            'sorteio' => $sorteio,
            'participantes' => $participantes,
            'estatisticas' => getParticipantStats($sorteio_id),
            'gerado_em' => date('Y-m-d H:i:s'),
            'total_participantes' => count($participantes)
        ];
        
        return $report;
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório: " . $e->getMessage());
        return null;
    }
}

/**
 * Processa busca de participantes com filtros
 */
function searchParticipants($sorteio_id, $search_term = '', $filters = []) {
    try {
        $db = getDatabase();
        
        $where_conditions = ["p.sorteio_id = ?"];
        $params = [$sorteio_id];
        
        // Busca por termo
        if (!empty($search_term)) {
            $where_conditions[] = "(p.nome LIKE ? OR p.whatsapp LIKE ? OR p.cpf LIKE ? OR p.email LIKE ?)";
            $search_param = "%{$search_term}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        // Filtros adicionais
        if (isset($filters['foi_sorteado'])) {
            if ($filters['foi_sorteado']) {
                $where_conditions[] = "sr.id IS NOT NULL";
            } else {
                $where_conditions[] = "sr.id IS NULL";
            }
        }
        
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $where_conditions[] = "DATE(p.created_at) >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $where_conditions[] = "DATE(p.created_at) <= ?";
            $params[] = $filters['data_fim'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT p.*, 
                   CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END as foi_sorteado,
                   sr.posicao as posicao_sorteio
            FROM participantes p 
            LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
            WHERE {$where_clause}
            ORDER BY p.created_at DESC
        ";
        
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro na busca de participantes: " . $e->getMessage());
        return [];
    }
}

/**
 * Valida dados de participante antes da inserção
 */
function validateParticipantData($data, $campos_config, $sorteio_id = null) {
    $errors = [];
    
    // Nome é sempre obrigatório
    if (empty($data['nome']) || strlen(trim($data['nome'])) < 2) {
        $errors['nome'] = 'Nome deve ter pelo menos 2 caracteres';
    }
    
    // Validar campos condicionais
    if (isset($campos_config['whatsapp']['enabled']) && $campos_config['whatsapp']['enabled']) {
        if (isset($campos_config['whatsapp']['required']) && $campos_config['whatsapp']['required']) {
            if (empty($data['whatsapp'])) {
                $errors['whatsapp'] = 'WhatsApp é obrigatório';
            }
        }
        
        if (!empty($data['whatsapp']) && !validateWhatsApp($data['whatsapp'])) {
            $errors['whatsapp'] = 'WhatsApp inválido';
        }
    }
    
    if (isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
        if (isset($campos_config['cpf']['required']) && $campos_config['cpf']['required']) {
            if (empty($data['cpf'])) {
                $errors['cpf'] = 'CPF é obrigatório';
            }
        }
        
        if (!empty($data['cpf']) && !validateCPF($data['cpf'])) {
            $errors['cpf'] = 'CPF inválido';
        }
    }
    
    if (isset($campos_config['email']['enabled']) && $campos_config['email']['enabled']) {
        if (isset($campos_config['email']['required']) && $campos_config['email']['required']) {
            if (empty($data['email'])) {
                $errors['email'] = 'Email é obrigatório';
            }
        }
        
        if (!empty($data['email']) && !validateEmail($data['email'])) {
            $errors['email'] = 'Email inválido';
        }
    }
    
    // Verificar duplicatas se sorteio_id fornecido
    if ($sorteio_id && !empty($data['cpf']) && isset($campos_config['cpf']['enabled']) && $campos_config['cpf']['enabled']) {
        $db = getDatabase();
        $existing = $db->fetchOne(
            "SELECT id FROM participantes WHERE sorteio_id = ? AND cpf = ?", 
            [$sorteio_id, preg_replace('/[^0-9]/', '', $data['cpf'])]
        );
        
        if ($existing) {
            $errors['cpf'] = 'Este CPF já está cadastrado neste sorteio';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Formata dados de participante para exibição
 */
function formatParticipantData($participante, $campos_config = null) {
    $formatted = $participante;
    
    // Formatar CPF
    if (!empty($formatted['cpf'])) {
        $formatted['cpf_formatted'] = formatCPF($formatted['cpf']);
    }
    
    // Formatar WhatsApp
    if (!empty($formatted['whatsapp'])) {
        $formatted['whatsapp_formatted'] = formatWhatsApp($formatted['whatsapp']);
    }
    
    // Formatar data
    if (!empty($formatted['created_at'])) {
        $formatted['created_at_formatted'] = formatDateBR($formatted['created_at']);
    }
    
    // Processar campos extras
    if (!empty($formatted['campos_extras'])) {
        $campos_extras = json_decode($formatted['campos_extras'], true);
        $formatted['campos_extras_array'] = $campos_extras ?: [];
    }
    
    return $formatted;
}

/**
 * Formata tempo relativo (ex: "há 2 horas")
 */
function formatTimeAgo($datetime) {
    if (empty($datetime)) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'há poucos segundos';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "há {$minutes} " . ($minutes == 1 ? 'minuto' : 'minutos');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "há {$hours} " . ($hours == 1 ? 'hora' : 'horas');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "há {$days} " . ($days == 1 ? 'dia' : 'dias');
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return "há {$months} " . ($months == 1 ? 'mês' : 'meses');
    } else {
        $years = floor($diff / 31536000);
        return "há {$years} " . ($years == 1 ? 'ano' : 'anos');
    }
}

/**
 * Obtém instância do validador
 */
function getValidator() {
    require_once 'validator.php';
    return new FormValidator();
}