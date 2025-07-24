<?php
/**
 * Motor de Sorteio - Classe responsável pela lógica de execução de sorteios
 * Implementa algoritmo seguro de seleção aleatória com validação de integridade
 */

// Prevenir acesso direto
if (!defined('SISTEMA_SORTEIOS')) {
    die('Acesso negado');
}

class SorteioEngine {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->logger = new SorteioLogger();
    }
    
    /**
     * Executa sorteio com validação completa
     * 
     * @param int $sorteioId ID do sorteio
     * @param int $quantidade Quantidade de participantes a sortear
     * @return array Resultado do sorteio
     */
    public function executeSorteio($sorteioId, $quantidade = 1) {
        try {
            // Validar entrada
            $validation = $this->validateSorteioExecution($sorteioId, $quantidade);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Obter dados do sorteio
            $sorteio = $this->getSorteioData($sorteioId);
            if (!$sorteio) {
                throw new Exception('Sorteio não encontrado');
            }
            
            // Obter participantes elegíveis
            $participantes = $this->getEligibleParticipants($sorteioId);
            if (empty($participantes)) {
                throw new Exception('Nenhum participante elegível encontrado');
            }
            
            // Validar quantidade solicitada
            if ($quantidade > count($participantes)) {
                throw new Exception('Quantidade de sorteados maior que participantes disponíveis');
            }
            
            // Executar algoritmo de sorteio
            $sorteados = $this->performRandomSelection($participantes, $quantidade);
            
            // Salvar resultados
            $resultadoId = $this->saveResults($sorteioId, $sorteados);
            
            // Log da operação
            $this->logger->logSorteio($sorteioId, $sorteados, [
                'total_participantes' => count($participantes),
                'quantidade_sorteada' => $quantidade,
                'algoritmo' => 'secure_random',
                'timestamp' => time()
            ]);
            
            return [
                'success' => true,
                'resultado_id' => $resultadoId,
                'sorteados' => $sorteados,
                'total_participantes' => count($participantes),
                'sorteio' => $sorteio,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->logger->logError($sorteioId, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sorteados' => [],
                'total_participantes' => 0
            ];
        }
    }
    
    /**
     * Algoritmo de seleção aleatória seguro
     * Utiliza múltiplas fontes de entropia para garantir aleatoriedade
     */
    private function performRandomSelection($participantes, $quantidade) {
        // Criar pool de entropia adicional
        $entropy = $this->generateEntropy();
        
        // Embaralhar array usando algoritmo Fisher-Yates modificado
        $shuffled = $this->secureShuffleArray($participantes, $entropy);
        
        // Selecionar os primeiros N participantes
        $selected = array_slice($shuffled, 0, $quantidade);
        
        // Adicionar posição do sorteio
        foreach ($selected as $index => &$participante) {
            $participante['posicao_sorteio'] = $index + 1;
            $participante['timestamp_sorteio'] = microtime(true);
        }
        
        return $selected;
    }
    
    /**
     * Gera entropia adicional para o sorteio
     */
    private function generateEntropy() {
        $sources = [
            microtime(true),
            memory_get_usage(),
            getmypid(),
            $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
            random_int(0, PHP_INT_MAX),
            hash('sha256', random_bytes(32))
        ];
        
        return hash('sha256', implode('|', $sources));
    }
    
    /**
     * Embaralhamento seguro usando Fisher-Yates com entropia adicional
     */
    private function secureShuffleArray($array, $entropy) {
        $count = count($array);
        
        // Seed adicional baseado na entropia
        $seed = hexdec(substr(hash('md5', $entropy), 0, 8));
        mt_srand($seed);
        
        // Fisher-Yates shuffle
        for ($i = $count - 1; $i > 0; $i--) {
            // Usar múltiplas fontes de aleatoriedade
            $j = random_int(0, $i);
            
            // Swap elements
            $temp = $array[$i];
            $array[$i] = $array[$j];
            $array[$j] = $temp;
        }
        
        // Segundo embaralhamento com shuffle nativo
        shuffle($array);
        
        return $array;
    }
    
    /**
     * Valida se o sorteio pode ser executado
     */
    private function validateSorteioExecution($sorteioId, $quantidade) {
        // Validar ID do sorteio
        if (!is_numeric($sorteioId) || $sorteioId <= 0) {
            return ['valid' => false, 'message' => 'ID do sorteio inválido'];
        }
        
        // Validar quantidade
        if (!is_numeric($quantidade) || $quantidade <= 0) {
            return ['valid' => false, 'message' => 'Quantidade deve ser maior que zero'];
        }
        
        if ($quantidade > 1000) {
            return ['valid' => false, 'message' => 'Quantidade máxima de sorteados é 1000'];
        }
        
        // Verificar se sorteio existe e está ativo
        $sorteio = $this->getSorteioData($sorteioId);
        if (!$sorteio) {
            return ['valid' => false, 'message' => 'Sorteio não encontrado'];
        }
        
        if ($sorteio['status'] !== 'ativo') {
            return ['valid' => false, 'message' => 'Sorteio não está ativo'];
        }
        
        // Verificar se há participantes suficientes
        $totalParticipantes = $this->countEligibleParticipants($sorteioId);
        if ($totalParticipantes < $quantidade) {
            return [
                'valid' => false, 
                'message' => "Participantes insuficientes. Disponíveis: {$totalParticipantes}, Solicitados: {$quantidade}"
            ];
        }
        
        return ['valid' => true, 'message' => 'Validação passou'];
    }
    
    /**
     * Obtém dados do sorteio
     */
    private function getSorteioData($sorteioId) {
        return $this->db->fetchOne(
            "SELECT * FROM sorteios WHERE id = ?", 
            [$sorteioId]
        );
    }
    
    /**
     * Obtém participantes elegíveis para o sorteio
     */
    private function getEligibleParticipants($sorteioId) {
        return $this->db->fetchAll(
            "SELECT p.* FROM participantes p 
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
    }
    
    /**
     * Conta participantes elegíveis
     */
    private function countEligibleParticipants($sorteioId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM participantes p 
             WHERE p.sorteio_id = ? 
             AND p.id NOT IN (
                 SELECT sr.participante_id 
                 FROM sorteio_resultados sr 
                 JOIN participantes p2 ON sr.participante_id = p2.id 
                 WHERE p2.sorteio_id = ?
             )",
            [$sorteioId, $sorteioId]
        );
        
        return $result['count'] ?? 0;
    }
    
    /**
     * Salva resultados do sorteio no banco
     */
    private function saveResults($sorteioId, $sorteados) {
        try {
            $this->db->beginTransaction();
            
            $resultadoId = time(); // Usar timestamp como ID do resultado
            
            foreach ($sorteados as $participante) {
                $this->db->insert(
                    "INSERT INTO sorteio_resultados (sorteio_id, participante_id, posicao, data_sorteio, resultado_id) 
                     VALUES (?, ?, ?, datetime('now'), ?)",
                    [
                        $sorteioId,
                        $participante['id'],
                        $participante['posicao_sorteio'],
                        $resultadoId
                    ]
                );
            }
            
            $this->db->commit();
            return $resultadoId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Erro ao salvar resultados: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém histórico de sorteios realizados
     */
    public function getSorteioHistory($sorteioId, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT sr.*, p.nome, p.whatsapp, p.cpf, p.email
             FROM sorteio_resultados sr
             JOIN participantes p ON sr.participante_id = p.id
             WHERE sr.sorteio_id = ?
             ORDER BY sr.data_sorteio DESC, sr.posicao ASC
             LIMIT ?",
            [$sorteioId, $limit]
        );
    }
    
    /**
     * Obtém estatísticas de sorteios realizados
     */
    public function getSorteioStats($sorteioId) {
        $stats = [];
        
        // Total de sorteios realizados
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT resultado_id) as total_sorteios,
                    COUNT(*) as total_sorteados,
                    MIN(data_sorteio) as primeiro_sorteio,
                    MAX(data_sorteio) as ultimo_sorteio
             FROM sorteio_resultados sr
             JOIN participantes p ON sr.participante_id = p.id
             WHERE p.sorteio_id = ?",
            [$sorteioId]
        );
        
        $stats['total_sorteios'] = $result['total_sorteios'] ?? 0;
        $stats['total_sorteados'] = $result['total_sorteados'] ?? 0;
        $stats['primeiro_sorteio'] = $result['primeiro_sorteio'];
        $stats['ultimo_sorteio'] = $result['ultimo_sorteio'];
        
        // Total de participantes
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM participantes WHERE sorteio_id = ?",
            [$sorteioId]
        );
        $stats['total_participantes'] = $result['total'] ?? 0;
        
        // Participantes restantes
        $stats['participantes_restantes'] = $this->countEligibleParticipants($sorteioId);
        
        return $stats;
    }
    
    /**
     * Valida integridade dos resultados de sorteio
     */
    public function validateSorteioIntegrity($sorteioId, $resultadoId = null) {
        $issues = [];
        
        try {
            // Verificar duplicatas
            $duplicates = $this->db->fetchAll(
                "SELECT participante_id, COUNT(*) as count 
                 FROM sorteio_resultados sr
                 JOIN participantes p ON sr.participante_id = p.id
                 WHERE p.sorteio_id = ?" . ($resultadoId ? " AND sr.resultado_id = ?" : "") . "
                 GROUP BY participante_id 
                 HAVING count > 1",
                $resultadoId ? [$sorteioId, $resultadoId] : [$sorteioId]
            );
            
            if (!empty($duplicates)) {
                $issues[] = 'Participantes duplicados encontrados: ' . count($duplicates);
            }
            
            // Verificar sequência de posições
            $positions = $this->db->fetchAll(
                "SELECT DISTINCT posicao 
                 FROM sorteio_resultados sr
                 JOIN participantes p ON sr.participante_id = p.id
                 WHERE p.sorteio_id = ?" . ($resultadoId ? " AND sr.resultado_id = ?" : "") . "
                 ORDER BY posicao",
                $resultadoId ? [$sorteioId, $resultadoId] : [$sorteioId]
            );
            
            $expectedPosition = 1;
            foreach ($positions as $pos) {
                if ($pos['posicao'] != $expectedPosition) {
                    $issues[] = "Sequência de posições incorreta. Esperado: {$expectedPosition}, Encontrado: {$pos['posicao']}";
                    break;
                }
                $expectedPosition++;
            }
            
            // Verificar se participantes existem
            $invalidParticipants = $this->db->fetchAll(
                "SELECT sr.participante_id 
                 FROM sorteio_resultados sr
                 LEFT JOIN participantes p ON sr.participante_id = p.id
                 WHERE p.id IS NULL AND sr.sorteio_id IN (
                     SELECT id FROM sorteios WHERE id = ?
                 )" . ($resultadoId ? " AND sr.resultado_id = ?" : ""),
                $resultadoId ? [$sorteioId, $resultadoId] : [$sorteioId]
            );
            
            if (!empty($invalidParticipants)) {
                $issues[] = 'Participantes inválidos encontrados: ' . count($invalidParticipants);
            }
            
        } catch (Exception $e) {
            $issues[] = 'Erro na validação: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
    
    /**
     * Remove resultado de sorteio (para casos de erro)
     */
    public function removeSorteioResult($sorteioId, $resultadoId) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se o resultado existe
            $exists = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM sorteio_resultados sr
                 JOIN participantes p ON sr.participante_id = p.id
                 WHERE p.sorteio_id = ? AND sr.resultado_id = ?",
                [$sorteioId, $resultadoId]
            );
            
            if ($exists['count'] == 0) {
                throw new Exception('Resultado de sorteio não encontrado');
            }
            
            // Remover resultados
            $removed = $this->db->execute(
                "DELETE FROM sorteio_resultados 
                 WHERE resultado_id = ? AND sorteio_id IN (
                     SELECT id FROM sorteios WHERE id = ?
                 )",
                [$resultadoId, $sorteioId]
            );
            
            $this->db->commit();
            
            // Log da remoção
            $this->logger->logRemoval($sorteioId, $resultadoId, $removed);
            
            return [
                'success' => true,
                'removed_count' => $removed
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Classe para logging de operações de sorteio
 */
class SorteioLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = DATA_PATH . '/logs/sorteios.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function logSorteio($sorteioId, $sorteados, $metadata = []) {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'SORTEIO_EXECUTADO',
            'sorteio_id' => $sorteioId,
            'sorteados_count' => count($sorteados),
            'sorteados_ids' => array_column($sorteados, 'id'),
            'metadata' => $metadata,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->writeLog($entry);
    }
    
    public function logError($sorteioId, $error) {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'SORTEIO_ERRO',
            'sorteio_id' => $sorteioId,
            'error' => $error,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->writeLog($entry);
    }
    
    public function logRemoval($sorteioId, $resultadoId, $count) {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'RESULTADO_REMOVIDO',
            'sorteio_id' => $sorteioId,
            'resultado_id' => $resultadoId,
            'removed_count' => $count,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->writeLog($entry);
    }
    
    private function writeLog($entry) {
        $logLine = json_encode($entry) . PHP_EOL;
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>