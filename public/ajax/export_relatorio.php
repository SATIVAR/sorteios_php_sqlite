<?php
/**
 * Endpoint AJAX para Exportação de Relatórios
 * Suporta PDF, CSV e Excel usando RelatorioExporter
 */

define('SISTEMA_SORTEIOS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';
require_once '../includes/admin_middleware.php';
require_once '../includes/classes/RelatorioExporter.php';

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Método não permitido');
}

// Obter parâmetros
$exportType = $_GET['export'] ?? 'csv';
$tipoRelatorio = $_GET['tipo'] ?? 'participacao';
$filtros = [
    'periodo' => $_GET['periodo'] ?? '30',
    'sorteio' => $_GET['sorteio'] ?? '',
    'status' => $_GET['status'] ?? '',
    'table' => $_GET['table'] ?? ''
];

$db = getDatabase();

try {
    // Construir condições WHERE baseadas nos filtros
    $whereConditions = [];
    $params = [];
    
    if ($filtros['periodo'] !== 'todos') {
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
    
    // Obter dados baseados no tipo de relatório
    $dadosExport = obterDadosParaExport($db, $tipoRelatorio, $whereClause, $params, $filtros['table']);
    
    // Usar RelatorioExporter para processar exportação
    $exporter = new RelatorioExporter();
    $result = $exporter->export($tipoRelatorio, $exportType, $dadosExport, $filtros);
    
    if ($result['success']) {
        // Determinar content type baseado no formato
        $contentTypes = [
            'csv' => 'text/csv; charset=UTF-8',
            'pdf' => 'application/pdf',
            'html' => 'text/html; charset=UTF-8',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $extension = pathinfo($result['filename'], PATHINFO_EXTENSION);
        $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
        
        // Headers para download
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . $result['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Enviar arquivo
        readfile($result['filepath']);
        
        // Limpar arquivo temporário após envio
        if (file_exists($result['filepath'])) {
            unlink($result['filepath']);
        }
        
        exit;
    } else {
        throw new Exception('Falha na exportação');
    }
    
} catch (Exception $e) {
    error_log("Erro na exportação: " . $e->getMessage());
    http_response_code(500);
    die('Erro na exportação: ' . $e->getMessage());
}

function obterDadosParaExport($db, $tipoRelatorio, $whereClause, $params, $table = '') {
    $dados = [];
    
    switch ($tipoRelatorio) {
        case 'participacao':
            if ($table === 'top-sorteios' || empty($table)) {
                $dados['top_sorteios'] = $db->fetchAll("
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
                    LIMIT 50
                ", $params);
            }
            
            if (empty($table)) {
                $dados['participacao_diaria'] = $db->fetchAll("
                    SELECT 
                        DATE(p.created_at) as data,
                        COUNT(*) as participantes
                    FROM participantes p
                    JOIN sorteios s ON p.sorteio_id = s.id
                    $whereClause
                    GROUP BY DATE(p.created_at)
                    ORDER BY data ASC
                ", $params);
            }
            break;
            
        case 'conversao':
            if ($table === 'conversao' || empty($table)) {
                $dados['conversao_por_sorteio'] = $db->fetchAll("
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
            }
            break;
            
        case 'engajamento':
            $dados['engajamento_por_hora'] = $db->fetchAll("
                SELECT 
                    strftime('%H', p.created_at) as hora,
                    COUNT(*) as participantes
                FROM participantes p
                JOIN sorteios s ON p.sorteio_id = s.id
                $whereClause
                GROUP BY strftime('%H', p.created_at)
                ORDER BY hora
            ", $params);
            
            $dados['engajamento_por_dia'] = $db->fetchAll("
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
            break;
            
        case 'comparativo':
            if ($table === 'comparativo' || empty($table)) {
                $dados['comparativo_mensal'] = $db->fetchAll("
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
            }
            break;
    }
    
    return $dados;
}

function exportarCSV($dados, $tipoRelatorio, $filtros) {
    $filename = "relatorio_{$tipoRelatorio}_" . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    switch ($tipoRelatorio) {
        case 'participacao':
            if (isset($dados['top_sorteios'])) {
                fputcsv($output, ['Nome do Sorteio', 'Status', 'Total Participantes', 'Total Ganhadores', 'Data Criação'], ';');
                foreach ($dados['top_sorteios'] as $sorteio) {
                    fputcsv($output, [
                        $sorteio['nome'],
                        $sorteio['status'],
                        $sorteio['total_participantes'],
                        $sorteio['total_ganhadores'],
                        formatDateBR($sorteio['created_at'])
                    ], ';');
                }
            }
            break;
            
        case 'conversao':
            if (isset($dados['conversao_por_sorteio'])) {
                fputcsv($output, ['Nome do Sorteio', 'Participantes', 'Ganhadores', 'Taxa de Conversão (%)'], ';');
                foreach ($dados['conversao_por_sorteio'] as $sorteio) {
                    fputcsv($output, [
                        $sorteio['nome'],
                        $sorteio['participantes'],
                        $sorteio['ganhadores'],
                        $sorteio['taxa_conversao']
                    ], ';');
                }
            }
            break;
            
        case 'engajamento':
            // Exportar dados de engajamento por hora
            if (isset($dados['engajamento_por_hora'])) {
                fputcsv($output, ['Horário', 'Participantes'], ';');
                foreach ($dados['engajamento_por_hora'] as $hora) {
                    fputcsv($output, [
                        $hora['hora'] . 'h',
                        $hora['participantes']
                    ], ';');
                }
                
                fputcsv($output, [''], ';'); // Linha em branco
                
                // Dados por dia da semana
                fputcsv($output, ['Dia da Semana', 'Participantes'], ';');
                foreach ($dados['engajamento_por_dia'] as $dia) {
                    fputcsv($output, [
                        $dia['dia_semana'],
                        $dia['participantes']
                    ], ';');
                }
            }
            break;
            
        case 'comparativo':
            if (isset($dados['comparativo_mensal'])) {
                fputcsv($output, ['Mês/Ano', 'Sorteios Criados', 'Participantes', 'Ganhadores', 'Taxa de Conversão (%)'], ';');
                foreach ($dados['comparativo_mensal'] as $mes) {
                    $taxaConversao = $mes['total_participantes'] > 0 ? 
                        round(($mes['total_ganhadores'] / $mes['total_participantes']) * 100, 2) : 0;
                    
                    fputcsv($output, [
                        date('M/Y', strtotime($mes['mes'] . '-01')),
                        $mes['sorteios_criados'],
                        $mes['total_participantes'],
                        $mes['total_ganhadores'],
                        $taxaConversao
                    ], ';');
                }
            }
            break;
    }
    
    fclose($output);
    exit;
}

function exportarPDF($dados, $tipoRelatorio, $filtros) {
    // Para implementação futura com TCPDF ou similar
    // Por enquanto, redirecionar para CSV
    exportarCSV($dados, $tipoRelatorio, $filtros);
}

function exportarExcel($dados, $tipoRelatorio, $filtros) {
    // Para implementação futura com PhpSpreadsheet ou similar
    // Por enquanto, redirecionar para CSV
    exportarCSV($dados, $tipoRelatorio, $filtros);
}
?>