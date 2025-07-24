<?php
/**
 * Sistema de Validação e Sanitização - Sistema de Sorteios
 * Classe completa para validação de dados e proteção contra ataques
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

class Validator {
    private static $instance = null;
    private $errors = [];
    
    /**
     * Obtém instância única da classe (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Limpa array de erros
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }
    
    /**
     * Obtém todos os erros
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Adiciona erro
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * SANITIZAÇÃO DE DADOS
     */
    
    /**
     * Sanitiza string básica
     */
    public function sanitizeString($value, $allowHtml = false) {
        if ($value === null) return null;
        
        $value = trim($value);
        
        if (!$allowHtml) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            // Remove apenas scripts perigosos mas mantém HTML básico
            $value = $this->sanitizeHtml($value);
        }
        
        return $value;
    }
    
    /**
     * Sanitiza HTML removendo elementos perigosos
     */
    public function sanitizeHtml($html) {
        // Lista de tags permitidas
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><span>';
        
        // Remove scripts e outros elementos perigosos
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $html);
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);
        
        return strip_tags($html, $allowedTags);
    }
    
    /**
     * Sanitiza email
     */
    public function sanitizeEmail($email) {
        if ($email === null) return null;
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitiza URL
     */
    public function sanitizeUrl($url) {
        if ($url === null) return null;
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitiza número inteiro
     */
    public function sanitizeInt($value) {
        if ($value === null || $value === '') return null;
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitiza número float
     */
    public function sanitizeFloat($value) {
        if ($value === null || $value === '') return null;
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitiza CPF (remove formatação)
     */
    public function sanitizeCPF($cpf) {
        if ($cpf === null) return null;
        return preg_replace('/[^0-9]/', '', trim($cpf));
    }
    
    /**
     * Sanitiza WhatsApp (remove formatação)
     */
    public function sanitizeWhatsApp($whatsapp) {
        if ($whatsapp === null) return null;
        return preg_replace('/[^0-9]/', '', trim($whatsapp));
    }
    
    /**
     * VALIDAÇÕES
     */
    
    /**
     * Valida campo obrigatório
     */
    public function required($value, $field = 'campo') {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "O campo {$field} é obrigatório");
            return false;
        }
        return true;
    }
    
    /**
     * Valida email
     */
    public function email($email, $field = 'email') {
        if (empty($email)) return true; // Permite vazio se não for obrigatório
        
        $sanitized = $this->sanitizeEmail($email);
        
        if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Email inválido');
            return false;
        }
        
        // Verifica domínio básico
        $domain = substr(strrchr($sanitized, "@"), 1);
        if (!$domain || !checkdnsrr($domain, "MX")) {
            $this->addError($field, 'Domínio do email inválido');
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida CPF
     */
    public function cpf($cpf, $field = 'cpf') {
        if (empty($cpf)) return true; // Permite vazio se não for obrigatório
        
        $cpf = $this->sanitizeCPF($cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            $this->addError($field, 'CPF deve ter 11 dígitos');
            return false;
        }
        
        // Verifica se não são todos iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $this->addError($field, 'CPF inválido');
            return false;
        }
        
        // Calcula os dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                $this->addError($field, 'CPF inválido');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida WhatsApp (formato brasileiro)
     */
    public function whatsapp($whatsapp, $field = 'whatsapp') {
        if (empty($whatsapp)) return true; // Permite vazio se não for obrigatório
        
        $whatsapp = $this->sanitizeWhatsApp($whatsapp);
        
        // Formato: (11) 9xxxx-xxxx ou 11 9xxxx-xxxx
        if (!preg_match('/^(\d{2})9\d{8}$/', $whatsapp)) {
            $this->addError($field, 'WhatsApp deve estar no formato (11) 9xxxx-xxxx');
            return false;
        }
        
        // Verifica se o DDD é válido (códigos brasileiros)
        $ddd = substr($whatsapp, 0, 2);
        $dddsValidos = [
            '11', '12', '13', '14', '15', '16', '17', '18', '19', // SP
            '21', '22', '24', // RJ
            '27', '28', // ES
            '31', '32', '33', '34', '35', '37', '38', // MG
            '41', '42', '43', '44', '45', '46', // PR
            '47', '48', '49', // SC
            '51', '53', '54', '55', // RS
            '61', // DF
            '62', '64', // GO
            '63', // TO
            '65', '66', // MT
            '67', // MS
            '68', // AC
            '69', // RO
            '71', '73', '74', '75', '77', // BA
            '79', // SE
            '81', '87', // PE
            '82', // AL
            '83', // PB
            '84', // RN
            '85', '88', // CE
            '86', '89', // PI
            '91', '93', '94', // PA
            '92', '97', // AM
            '95', // RR
            '96', // AP
            '98', '99' // MA
        ];
        
        if (!in_array($ddd, $dddsValidos)) {
            $this->addError($field, 'DDD inválido');
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida comprimento mínimo
     */
    public function minLength($value, $min, $field = 'campo') {
        if (empty($value)) return true; // Permite vazio se não for obrigatório
        
        if (strlen($value) < $min) {
            $this->addError($field, "O campo {$field} deve ter pelo menos {$min} caracteres");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida comprimento máximo
     */
    public function maxLength($value, $max, $field = 'campo') {
        if (empty($value)) return true; // Permite vazio se não for obrigatório
        
        if (strlen($value) > $max) {
            $this->addError($field, "O campo {$field} deve ter no máximo {$max} caracteres");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida número inteiro
     */
    public function integer($value, $field = 'campo') {
        if (empty($value)) return true; // Permite vazio se não for obrigatório
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "O campo {$field} deve ser um número inteiro");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida número positivo
     */
    public function positive($value, $field = 'campo') {
        if (empty($value)) return true; // Permite vazio se não for obrigatório
        
        if (!is_numeric($value) || $value <= 0) {
            $this->addError($field, "O campo {$field} deve ser um número positivo");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida data no formato brasileiro
     */
    public function dateBR($date, $field = 'data') {
        if (empty($date)) return true; // Permite vazio se não for obrigatório
        
        // Formato: dd/mm/yyyy ou dd/mm/yyyy hh:mm
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}(\s\d{2}:\d{2})?$/', $date)) {
            $this->addError($field, 'Data deve estar no formato dd/mm/aaaa ou dd/mm/aaaa hh:mm');
            return false;
        }
        
        $parts = explode(' ', $date);
        $datePart = $parts[0];
        $timePart = isset($parts[1]) ? $parts[1] : null;
        
        list($day, $month, $year) = explode('/', $datePart);
        
        if (!checkdate($month, $day, $year)) {
            $this->addError($field, 'Data inválida');
            return false;
        }
        
        if ($timePart) {
            list($hour, $minute) = explode(':', $timePart);
            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                $this->addError($field, 'Horário inválido');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida URL
     */
    public function url($url, $field = 'url') {
        if (empty($url)) return true; // Permite vazio se não for obrigatório
        
        $sanitized = $this->sanitizeUrl($url);
        
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'URL inválida');
            return false;
        }
        
        return true;
    }
    
    /**
     * PROTEÇÕES CONTRA ATAQUES
     */
    
    /**
     * Detecta tentativas de SQL Injection
     */
    public function detectSQLInjection($value) {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s*\d+\s*=\s*\d+/i',
            '/[\'";].*(\bOR\b|\bAND\b)/i',
            '/\b(exec|execute|sp_|xp_)\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                logActivity('SQL_INJECTION_ATTEMPT', "Tentativa detectada: " . substr($value, 0, 100));
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detecta tentativas de XSS
     */
    public function detectXSS($value) {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                logActivity('XSS_ATTEMPT', "Tentativa detectada: " . substr($value, 0, 100));
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida e sanitiza array de dados
     */
    public function validateArray($data, $rules) {
        $sanitized = [];
        $this->clearErrors();
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Aplica sanitização primeiro
            if (isset($fieldRules['sanitize'])) {
                switch ($fieldRules['sanitize']) {
                    case 'string':
                        $value = $this->sanitizeString($value, $fieldRules['allow_html'] ?? false);
                        break;
                    case 'email':
                        $value = $this->sanitizeEmail($value);
                        break;
                    case 'cpf':
                        $value = $this->sanitizeCPF($value);
                        break;
                    case 'whatsapp':
                        $value = $this->sanitizeWhatsApp($value);
                        break;
                    case 'int':
                        $value = $this->sanitizeInt($value);
                        break;
                    case 'float':
                        $value = $this->sanitizeFloat($value);
                        break;
                }
            }
            
            // Detecta ataques
            if ($value && is_string($value)) {
                if ($this->detectSQLInjection($value)) {
                    $this->addError($field, 'Conteúdo suspeito detectado');
                    continue;
                }
                
                if ($this->detectXSS($value)) {
                    $this->addError($field, 'Conteúdo suspeito detectado');
                    continue;
                }
            }
            
            // Aplica validações
            if (isset($fieldRules['required']) && $fieldRules['required']) {
                $this->required($value, $field);
            }
            
            if (isset($fieldRules['email']) && $fieldRules['email']) {
                $this->email($value, $field);
            }
            
            if (isset($fieldRules['cpf']) && $fieldRules['cpf']) {
                $this->cpf($value, $field);
            }
            
            if (isset($fieldRules['whatsapp']) && $fieldRules['whatsapp']) {
                $this->whatsapp($value, $field);
            }
            
            if (isset($fieldRules['min_length'])) {
                $this->minLength($value, $fieldRules['min_length'], $field);
            }
            
            if (isset($fieldRules['max_length'])) {
                $this->maxLength($value, $fieldRules['max_length'], $field);
            }
            
            if (isset($fieldRules['integer']) && $fieldRules['integer']) {
                $this->integer($value, $field);
            }
            
            if (isset($fieldRules['positive']) && $fieldRules['positive']) {
                $this->positive($value, $field);
            }
            
            if (isset($fieldRules['date_br']) && $fieldRules['date_br']) {
                $this->dateBR($value, $field);
            }
            
            if (isset($fieldRules['url']) && $fieldRules['url']) {
                $this->url($value, $field);
            }
            
            $sanitized[$field] = $value;
        }
        
        return $sanitized;
    }
    
    /**
     * Formata erros para exibição
     */
    public function getFormattedErrors() {
        $formatted = [];
        
        foreach ($this->errors as $field => $fieldErrors) {
            $formatted[$field] = implode(', ', $fieldErrors);
        }
        
        return $formatted;
    }
    
    /**
     * Obtém primeiro erro de um campo
     */
    public function getFirstError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : null;
    }
}

// Classe de compatibilidade
class FormValidator extends Validator {
    // Herda todas as funcionalidades da classe Validator
}

// Função auxiliar para obter instância do validador, declarada apenas se ainda não existir
if (!function_exists('getValidator')) {
    function getValidator() {
        return Validator::getInstance();
    }
}
?>