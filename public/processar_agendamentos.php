<?php
/**
 * Script para Processar Agendamentos de Relatórios
 * Deve ser executado via cron job ou chamada manual
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/classes/RelatorioExporter.php';

// Verificar se está sendo executado via CLI ou com autenticação
$isCLI = php_sapi_name() === 'cli';
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

if (!$isCLI && !$isAuthenticated) {
    // Permitir execução com token especial para cron jobs
    $cronToken = $_GET['token'] ?? '';
    $expectedToken = hash('sha256', 'cron_' . date('Y-m-d'));
    
    if ($cronToken !== $expectedToken) {
        http_response_code(403);
        die('Acesso negado');
    }
}

$db = getDatabase();
$exporter = new RelatorioExporter();

try {
    // Buscar agendamentos que precisam ser executados
    $agendamentos = $db->fetchAll("
        SELECT * FROM relatorio_agendamentos 
        WHERE status = 'ativo' 
        AND proxima_execucao <= datetime('now')
        ORDER BY proxima_execucao ASC
    ");
    
    $processados = 0;
    $erros = 0;
    
    foreach ($agendamentos as $agendamento) {
        try {
            $resultado = processarAgendamento($agendamento, $db, $exporter);
            
            if ($resultado['success']) {
                $processados++;
                logSystem("Agendamento {$agendamento['id']} processado com sucesso", 'INFO');
            } else {
                $erros++;
                logSystem("Erro no agendamento {$agendamento['id']}: " . $resultado['message'], 'ERROR');
            }
            
        } catch (Exception $e) {
            $erros++;
            logSystem("Erro crítico no agendamento {$agendamento['id']}: " . $e->getMessage(), 'ERROR');
            
            // Marcar agendamento como erro
            $db->execute(
                "UPDATE relatorio_agendamentos SET status = 'erro', updated_at = datetime('now') WHERE id = ?",
                [$agendamento['id']]
            );
        }
    }
    
    // Limpar arquivos temporários
    $exporter->cleanupTempFiles();
    
    $message = "Processamento concluído. Processados: {$processados}, Erros: {$erros}";
    logSystem($message, 'INFO');
    
    if ($isCLI) {
        echo $message . "\n";
    } else {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'processados' => $processados,
            'erros' => $erros
        ]);
    }
    
} catch (Exception $e) {
    $errorMessage = "Erro crítico no processamento de agendamentos: " . $e->getMessage();
    logSystem($errorMessage, 'ERROR');
    
    if ($isCLI) {
        echo $errorMessage . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
}

/**
 * Processa um agendamento específico
 */
function processarAgendamento($agendamento, $db, $exporter) {
    try {
        $configuracao = json_decode($agendamento['configuracao'], true);
        
        if (!$configuracao) {
            throw new Exception('Configuração inválida');
        }
        
        // Obter dados do relatório
        $dadosRelatorio = obterDadosRelatorio($db, $configuracao);
        
        // Gerar relatório
        $resultado = $exporter->export(
            $configuracao['tipo_relatorio'],
            $configuracao['formato'],
            $dadosRelatorio,
            $configuracao['filtros'] ?? []
        );
        
        if (!$resultado['success']) {
            throw new Exception('Falha na geração do relatório');
        }
        
        // Enviar por email
        $emailEnviado = enviarRelatorioEmail(
            $configuracao['email_destino'],
            $agendamento['nome'],
            $resultado['filepath'],
            $resultado['filename'],
            $configuracao
        );
        
        if (!$emailEnviado) {
            throw new Exception('Falha no envio do email');
        }
        
        // Atualizar próxima execução
        $proximaExecucao = calcularProximaExecucao($configuracao['frequencia']);
        
        $db->execute(
            "UPDATE relatorio_agendamentos 
             SET proxima_execucao = ?, ultima_execucao = datetime('now'), updated_at = datetime('now') 
             WHERE id = ?",
            [$proximaExecucao, $agendamento['id']]
        );
        
        // Limpar arquivo temporário
        if (file_exists($resultado['filepath'])) {
            unlink($resultado['filepath']);
        }
        
        return ['success' => true, 'message' => 'Agendamento processado com sucesso'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtém dados do relatório baseado na configuração
 */
function obterDadosRelatorio($db, $configuracao) {
    $tipoRelatorio = $configuracao['tipo_relatorio'];
    $filtros = $configuracao['filtros'] ?? [];
    
    // Construir condições WHERE
    $whereConditions = [];
    $params = [];
    
    if (isset($filtros['periodo']) && $filtros['periodo'] !== 'todos') {
        $whereConditions[] = "s.created_at >= date('now', '-{$filtros['periodo']} days')";
    }
    
    if (!empty($filtros['sorteio'])) {
        $whereConditions[] = "s.id = ?";
        $params[] = $filtros['sorteio'];
    }
    
    if (!empty($filtros['status'])) {
        $whereConditions[] = "s.status = ?";
        $params[] = $filtros['status'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Usar as mesmas funções do relatório principal
    switch ($tipoRelatorio) {
        case 'participacao':
            return obterDadosParticipacao($db, $whereClause, $params);
        case 'conversao':
            return obterDadosConversao($db, $whereClause, $params);
        case 'engajamento':
            return obterDadosEngajamento($db, $whereClause, $params);
        case 'comparativo':
            return obterDadosComparativo($db, $whereClause, $params);
        default:
            throw new Exception('Tipo de relatório inválido');
    }
}

/**
 * Envia relatório por email
 */
function enviarRelatorioEmail($emailDestino, $nomeRelatorio, $caminhoArquivo, $nomeArquivo, $configuracao) {
    global $sistema_config;
    
    // Verificar se as configurações de email estão definidas
    if (empty(SMTP_HOST) || empty(FROM_EMAIL)) {
        logSystem("Configurações de email não definidas", 'WARNING');
        return false; // Por enquanto, retornar false se email não configurado
    }
    
    try {
        // Preparar conteúdo do email
        $assunto = "Relatório Agendado: {$nomeRelatorio}";
        
        $corpo = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Relatório Agendado</h2>
            <p><strong>Nome:</strong> {$nomeRelatorio}</p>
            <p><strong>Tipo:</strong> " . ucfirst($configuracao['tipo_relatorio']) . "</p>
            <p><strong>Formato:</strong> " . strtoupper($configuracao['formato']) . "</p>
            <p><strong>Gerado em:</strong> " . date('d/m/Y H:i:s') . "</p>
            <p><strong>Sistema:</strong> {$sistema_config['nome_empresa']}</p>
            
            <hr>
            <p>Este é um relatório gerado automaticamente pelo Sistema de Sorteios.</p>
            <p>O arquivo está anexado a este email.</p>
        </body>
        </html>
        ";
        
        // Headers do email
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
            'Reply-To: ' . FROM_EMAIL,
            'X-Mailer: Sistema de Sorteios'
        ];
        
        // Para implementação básica, usar mail() do PHP
        // Em produção, recomenda-se usar PHPMailer ou similar
        $enviado = mail(
            $emailDestino,
            $assunto,
            $corpo,
            implode("\r\n", $headers)
        );
        
        if ($enviado) {
            logSystem("Email enviado para {$emailDestino}: {$nomeRelatorio}", 'INFO');
            return true;
        } else {
            logSystem("Falha no envio de email para {$emailDestino}", 'ERROR');
            return false;
        }
        
    } catch (Exception $e) {
        logSystem("Erro no envio de email: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Calcula próxima execução baseada na frequência
 */
function calcularProximaExecucao($frequencia) {
    $agora = new DateTime();
    
    switch ($frequencia) {
        case 'diario':
            $agora->add(new DateInterval('P1D'));
            break;
        case 'semanal':
            $agora->add(new DateInterval('P7D'));
            break;
        case 'mensal':
            $agora->add(new DateInterval('P1M'));
            break;
        default:
            $agora->add(new DateInterval('P1D'));
    }
    
    return $agora->format('Y-m-d H:i:s');
}

// Incluir as funções de obtenção de dados do relatório principal
function obterDadosParticipacao($db, $whereClause, $params) {
    $metricas = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT s.id) as total_sorteios,
            COUNT(p.id) as total_participantes,
            COUNT(DISTINCT sr.participante_id) as total_ganhadores,
            AVG(participantes_por_sorteio.count) as media_participantes
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        LEFT JOIN (
            SELECT sorteio_id, COUNT(*) as count 
            FROM participantes 
            GROUP BY sorteio_id
        ) participantes_por_sorteio ON s.id = participantes_por_sorteio.sorteio_id
        $whereClause
    ", $params);
    
    $participacaoDiaria = $db->fetchAll("
        SELECT 
            DATE(p.created_at) as data,
            COUNT(*) as participantes
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY DATE(p.created_at)
        ORDER BY data ASC
    ", $params);
    
    $topSorteios = $db->fetchAll("
        SELECT 
            s.nome,
            s.status,
            COUNT(p.id) as total_participantes,
            COUNT(sr.id) as total_ganhadores,
            s.created_at
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY s.id
        ORDER BY total_participantes DESC
        LIMIT 10
    ", $params);
    
    return [
        'metricas' => $metricas,
        'participacao_diaria' => $participacaoDiaria,
        'top_sorteios' => $topSorteios
    ];
}

function obterDadosConversao($db, $whereClause, $params) {
    $conversao = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT p.id) as total_participantes,
            COUNT(DISTINCT sr.participante_id) as total_ganhadores,
            ROUND(
                (COUNT(DISTINCT sr.participante_id) * 100.0) / 
                NULLIF(COUNT(DISTINCT p.id), 0), 2
            ) as taxa_conversao
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        LEFT JOIN sorteio_resultados sr ON p.id = sr.participante_id
        $whereClause
    ", $params);
    
    $conversaoPorSorteio = $db->fetchAll("
        SELECT 
            s.nome,
            COUNT(p.id) as participantes,
            COUNT(sr.id) as ganhadores,
            ROUND(
                (COUNT(sr.id) * 100.0) / NULLIF(COUNT(p.id), 0), 2
            ) as taxa_conversao
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY s.id
        HAVING COUNT(p.id) > 0
        ORDER BY taxa_conversao DESC
    ", $params);
    
    return [
        'conversao_geral' => $conversao,
        'conversao_por_sorteio' => $conversaoPorSorteio
    ];
}

function obterDadosEngajamento($db, $whereClause, $params) {
    $engajamentoPorHora = $db->fetchAll("
        SELECT 
            strftime('%H', p.created_at) as hora,
            COUNT(*) as participantes
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY strftime('%H', p.created_at)
        ORDER BY hora
    ", $params);
    
    $engajamentoPorDia = $db->fetchAll("
        SELECT 
            CASE strftime('%w', p.created_at)
                WHEN '0' THEN 'Domingo'
                WHEN '1' THEN 'Segunda'
                WHEN '2' THEN 'Terça'
                WHEN '3' THEN 'Quarta'
                WHEN '4' THEN 'Quinta'
                WHEN '5' THEN 'Sexta'
                WHEN '6' THEN 'Sábado'
            END as dia_semana,
            COUNT(*) as participantes
        FROM participantes p
        JOIN sorteios s ON p.sorteio_id = s.id
        $whereClause
        GROUP BY strftime('%w', p.created_at)
        ORDER BY strftime('%w', p.created_at)
    ", $params);
    
    return [
        'engajamento_por_hora' => $engajamentoPorHora,
        'engajamento_por_dia' => $engajamentoPorDia
    ];
}

function obterDadosComparativo($db, $whereClause, $params) {
    $comparativoMensal = $db->fetchAll("
        SELECT 
            strftime('%Y-%m', s.created_at) as mes,
            COUNT(DISTINCT s.id) as sorteios_criados,
            COUNT(p.id) as total_participantes,
            COUNT(sr.id) as total_ganhadores
        FROM sorteios s
        LEFT JOIN participantes p ON s.id = p.sorteio_id
        LEFT JOIN sorteio_resultados sr ON s.id = sr.sorteio_id
        $whereClause
        GROUP BY strftime('%Y-%m', s.created_at)
        ORDER BY mes DESC
        LIMIT 12
    ", $params);
    
    return [
        'comparativo_mensal' => array_reverse($comparativoMensal)
    ];
}
?>