<?php
/**
 * Classe RelatorioExporter - Sistema de Exportação de Relatórios
 * Suporta PDF, CSV, Excel e agendamento de relatórios
 */

class RelatorioExporter {
    private $db;
    private $tempDir;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->tempDir = DATA_PATH . '/exports';
        $this->ensureTempDir();
    }
    
    private function ensureTempDir() {
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        // Criar .htaccess para proteger diretório
        $htaccessPath = $this->tempDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Order Allow,Deny\nDeny from all");
        }
    }
    
    /**
     * Exporta relatório em formato especificado
     */
    public function export($tipo, $formato, $dados, $filtros = []) {
        switch ($formato) {
            case 'pdf':
                return $this->exportToPDF($tipo, $dados, $filtros);
            case 'csv':
                return $this->exportToCSV($tipo, $dados, $filtros);
            case 'excel':
                return $this->exportToExcel($tipo, $dados, $filtros);
            default:
                throw new Exception('Formato de exportação não suportado');
        }
    }
    
    /**
     * Exporta para CSV
     */
    public function exportToCSV($tipo, $dados, $filtros = []) {
        $filename = $this->generateFilename($tipo, 'csv', $filtros);
        $filepath = $this->tempDir . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        
        // BOM para UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($tipo) {
            case 'participacao':
                $this->exportParticipacaoCSV($handle, $dados);
                break;
            case 'conversao':
                $this->exportConversaoCSV($handle, $dados);
                break;
            case 'engajamento':
                $this->exportEngajamentoCSV($handle, $dados);
                break;
            case 'comparativo':
                $this->exportComparativoCSV($handle, $dados);
                break;
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath)
        ];
    }
    
    /**
     * Exporta para PDF usando HTML/CSS
     */
    public function exportToPDF($tipo, $dados, $filtros = []) {
        // Gerar HTML do relatório
        $html = $this->generateReportHTML($tipo, $dados, $filtros);
        
        $filename = $this->generateFilename($tipo, 'pdf', $filtros);
        $filepath = $this->tempDir . '/' . $filename;
        
        // Para implementação básica, vamos usar wkhtmltopdf se disponível
        // ou uma alternativa simples com HTML
        if ($this->isWkhtmltopdfAvailable()) {
            return $this->generatePDFWithWkhtmltopdf($html, $filepath);
        } else {
            // Fallback: salvar como HTML com CSS para impressão
            return $this->generateHTMLForPrint($html, $filepath);
        }
    }
    
    /**
     * Exporta para Excel (formato básico CSV com extensão .xlsx)
     */
    public function exportToExcel($tipo, $dados, $filtros = []) {
        // Para implementação básica, usar CSV com extensão Excel
        $result = $this->exportToCSV($tipo, $dados, $filtros);
        
        if ($result['success']) {
            $excelFilename = str_replace('.csv', '.xlsx', $result['filename']);
            $excelFilepath = str_replace('.csv', '.xlsx', $result['filepath']);
            
            // Renomear arquivo (Excel pode abrir CSV)
            rename($result['filepath'], $excelFilepath);
            
            return [
                'success' => true,
                'filename' => $excelFilename,
                'filepath' => $excelFilepath,
                'size' => filesize($excelFilepath)
            ];
        }
        
        return $result;
    }
    
    /**
     * Gera nome de arquivo único
     */
    private function generateFilename($tipo, $formato, $filtros = []) {
        $timestamp = date('Y-m-d_H-i-s');
        $periodo = $filtros['periodo'] ?? 'todos';
        
        $name = "relatorio_{$tipo}_{$periodo}_{$timestamp}.{$formato}";
        return sanitizeFilename($name);
    }
    
    /**
     * Exporta dados de participação para CSV
     */
    private function exportParticipacaoCSV($handle, $dados) {
        // Cabeçalho do relatório
        fputcsv($handle, ['RELATÓRIO DE PARTICIPAÇÃO'], ';');
        fputcsv($handle, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($handle, [''], ';'); // Linha em branco
        
        // Métricas principais
        if (isset($dados['metricas'])) {
            fputcsv($handle, ['MÉTRICAS PRINCIPAIS'], ';');
            fputcsv($handle, ['Total de Sorteios', $dados['metricas']['total_sorteios'] ?? 0], ';');
            fputcsv($handle, ['Total de Participantes', $dados['metricas']['total_participantes'] ?? 0], ';');
            fputcsv($handle, ['Total de Ganhadores', $dados['metricas']['total_ganhadores'] ?? 0], ';');
            fputcsv($handle, ['Média de Participantes', number_format($dados['metricas']['media_participantes'] ?? 0, 1)], ';');
            fputcsv($handle, [''], ';');
        }
        
        // Top sorteios
        if (isset($dados['top_sorteios']) && !empty($dados['top_sorteios'])) {
            fputcsv($handle, ['TOP SORTEIOS POR PARTICIPAÇÃO'], ';');
            fputcsv($handle, ['Posição', 'Nome do Sorteio', 'Status', 'Participantes', 'Ganhadores', 'Data de Criação'], ';');
            
            foreach ($dados['top_sorteios'] as $index => $sorteio) {
                fputcsv($handle, [
                    $index + 1,
                    $sorteio['nome'],
                    ucfirst($sorteio['status']),
                    $sorteio['total_participantes'],
                    $sorteio['total_ganhadores'],
                    formatDateBR($sorteio['created_at'])
                ], ';');
            }
            fputcsv($handle, [''], ';');
        }
        
        // Participação diária
        if (isset($dados['participacao_diaria']) && !empty($dados['participacao_diaria'])) {
            fputcsv($handle, ['PARTICIPAÇÃO DIÁRIA'], ';');
            fputcsv($handle, ['Data', 'Participantes'], ';');
            
            foreach ($dados['participacao_diaria'] as $dia) {
                fputcsv($handle, [
                    date('d/m/Y', strtotime($dia['data'])),
                    $dia['participantes']
                ], ';');
            }
        }
    }
    
    /**
     * Exporta dados de conversão para CSV
     */
    private function exportConversaoCSV($handle, $dados) {
        fputcsv($handle, ['RELATÓRIO DE CONVERSÃO'], ';');
        fputcsv($handle, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($handle, [''], ';');
        
        // Conversão geral
        if (isset($dados['conversao_geral'])) {
            fputcsv($handle, ['CONVERSÃO GERAL'], ';');
            fputcsv($handle, ['Total de Participantes', $dados['conversao_geral']['total_participantes'] ?? 0], ';');
            fputcsv($handle, ['Total de Ganhadores', $dados['conversao_geral']['total_ganhadores'] ?? 0], ';');
            fputcsv($handle, ['Taxa de Conversão (%)', number_format($dados['conversao_geral']['taxa_conversao'] ?? 0, 2)], ';');
            fputcsv($handle, [''], ';');
        }
        
        // Conversão por sorteio
        if (isset($dados['conversao_por_sorteio']) && !empty($dados['conversao_por_sorteio'])) {
            fputcsv($handle, ['CONVERSÃO POR SORTEIO'], ';');
            fputcsv($handle, ['Nome do Sorteio', 'Participantes', 'Ganhadores', 'Taxa de Conversão (%)', 'Performance'], ';');
            
            foreach ($dados['conversao_por_sorteio'] as $sorteio) {
                $taxa = $sorteio['taxa_conversao'];
                $performance = $taxa >= 10 ? 'Alta' : ($taxa >= 5 ? 'Média' : 'Baixa');
                
                fputcsv($handle, [
                    $sorteio['nome'],
                    $sorteio['participantes'],
                    $sorteio['ganhadores'],
                    number_format($taxa, 2),
                    $performance
                ], ';');
            }
        }
    }
    
    /**
     * Exporta dados de engajamento para CSV
     */
    private function exportEngajamentoCSV($handle, $dados) {
        fputcsv($handle, ['RELATÓRIO DE ENGAJAMENTO'], ';');
        fputcsv($handle, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($handle, [''], ';');
        
        // Engajamento por hora
        if (isset($dados['engajamento_por_hora']) && !empty($dados['engajamento_por_hora'])) {
            fputcsv($handle, ['ENGAJAMENTO POR HORÁRIO'], ';');
            fputcsv($handle, ['Horário', 'Participantes'], ';');
            
            foreach ($dados['engajamento_por_hora'] as $hora) {
                fputcsv($handle, [
                    $hora['hora'] . 'h',
                    $hora['participantes']
                ], ';');
            }
            fputcsv($handle, [''], ';');
        }
        
        // Engajamento por dia da semana
        if (isset($dados['engajamento_por_dia']) && !empty($dados['engajamento_por_dia'])) {
            fputcsv($handle, ['ENGAJAMENTO POR DIA DA SEMANA'], ';');
            fputcsv($handle, ['Dia da Semana', 'Participantes'], ';');
            
            foreach ($dados['engajamento_por_dia'] as $dia) {
                fputcsv($handle, [
                    $dia['dia_semana'],
                    $dia['participantes']
                ], ';');
            }
        }
    }
    
    /**
     * Exporta dados comparativos para CSV
     */
    private function exportComparativoCSV($handle, $dados) {
        fputcsv($handle, ['RELATÓRIO COMPARATIVO'], ';');
        fputcsv($handle, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($handle, [''], ';');
        
        if (isset($dados['comparativo_mensal']) && !empty($dados['comparativo_mensal'])) {
            fputcsv($handle, ['EVOLUÇÃO MENSAL'], ';');
            fputcsv($handle, ['Mês/Ano', 'Sorteios Criados', 'Participantes', 'Ganhadores', 'Taxa de Conversão (%)', 'Variação (%)'], ';');
            
            $dadosOrdenados = array_reverse($dados['comparativo_mensal']);
            
            foreach ($dadosOrdenados as $index => $mes) {
                $taxaConversao = $mes['total_participantes'] > 0 ? 
                    ($mes['total_ganhadores'] / $mes['total_participantes']) * 100 : 0;
                
                // Calcular variação
                $variacao = 0;
                if ($index > 0) {
                    $mesAnterior = $dadosOrdenados[$index - 1];
                    if ($mesAnterior['total_participantes'] > 0) {
                        $variacao = (($mes['total_participantes'] - $mesAnterior['total_participantes']) / $mesAnterior['total_participantes']) * 100;
                    }
                }
                
                fputcsv($handle, [
                    date('M/Y', strtotime($mes['mes'] . '-01')),
                    $mes['sorteios_criados'],
                    $mes['total_participantes'],
                    $mes['total_ganhadores'],
                    number_format($taxaConversao, 2),
                    $index === 0 ? '-' : ($variacao >= 0 ? '+' : '') . number_format($variacao, 1)
                ], ';');
            }
        }
    }
    
    /**
     * Gera HTML do relatório para PDF
     */
    private function generateReportHTML($tipo, $dados, $filtros) {
        global $sistema_config;
        
        $html = '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Relatório ' . ucfirst($tipo) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3b82f6; padding-bottom: 20px; }
                .company-name { font-size: 24px; font-weight: bold; color: #3b82f6; }
                .report-title { font-size: 20px; margin: 10px 0; }
                .report-date { color: #666; font-size: 14px; }
                .section { margin: 30px 0; }
                .section-title { font-size: 18px; font-weight: bold; color: #3b82f6; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
                .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .metric-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; text-align: center; }
                .metric-value { font-size: 24px; font-weight: bold; color: #3b82f6; }
                .metric-label { color: #666; font-size: 14px; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }
                th { background-color: #f9fafb; font-weight: bold; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
                .badge-success { background-color: #d1fae5; color: #065f46; }
                .badge-warning { background-color: #fef3c7; color: #92400e; }
                .badge-danger { background-color: #fee2e2; color: #991b1b; }
                .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
                @media print { body { margin: 0; } .no-print { display: none; } }
            </style>
        </head>
        <body>';
        
        // Header
        $html .= '<div class="header">
            <div class="company-name">' . htmlspecialchars($sistema_config['nome_empresa'] ?: 'Sistema de Sorteios') . '</div>
            <div class="report-title">Relatório de ' . ucfirst($tipo) . '</div>
            <div class="report-date">Gerado em: ' . date('d/m/Y H:i:s') . '</div>
        </div>';
        
        // Conteúdo específico por tipo
        switch ($tipo) {
            case 'participacao':
                $html .= $this->generateParticipacaoHTML($dados);
                break;
            case 'conversao':
                $html .= $this->generateConversaoHTML($dados);
                break;
            case 'engajamento':
                $html .= $this->generateEngajamentoHTML($dados);
                break;
            case 'comparativo':
                $html .= $this->generateComparativoHTML($dados);
                break;
        }
        
        // Footer
        $html .= '<div class="footer">
            <p>Relatório gerado automaticamente pelo Sistema de Sorteios</p>
            <p>Data/Hora: ' . date('d/m/Y H:i:s') . '</p>
        </div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Gera HTML específico para relatório de participação
     */
    private function generateParticipacaoHTML($dados) {
        $html = '';
        
        // Métricas principais
        if (isset($dados['metricas'])) {
            $html .= '<div class="section">
                <div class="section-title">Métricas Principais</div>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['metricas']['total_sorteios'] ?? 0) . '</div>
                        <div class="metric-label">Total de Sorteios</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['metricas']['total_participantes'] ?? 0) . '</div>
                        <div class="metric-label">Total de Participantes</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['metricas']['total_ganhadores'] ?? 0) . '</div>
                        <div class="metric-label">Total de Ganhadores</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['metricas']['media_participantes'] ?? 0, 1) . '</div>
                        <div class="metric-label">Média por Sorteio</div>
                    </div>
                </div>
            </div>';
        }
        
        // Top sorteios
        if (isset($dados['top_sorteios']) && !empty($dados['top_sorteios'])) {
            $html .= '<div class="section">
                <div class="section-title">Top Sorteios por Participação</div>
                <table>
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Nome do Sorteio</th>
                            <th>Status</th>
                            <th class="text-center">Participantes</th>
                            <th class="text-center">Ganhadores</th>
                            <th>Data de Criação</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($dados['top_sorteios'] as $index => $sorteio) {
                $statusClass = $sorteio['status'] === 'ativo' ? 'badge-success' : 
                              ($sorteio['status'] === 'finalizado' ? 'badge-warning' : 'badge-danger');
                
                $html .= '<tr>
                    <td class="text-center">' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($sorteio['nome']) . '</td>
                    <td><span class="badge ' . $statusClass . '">' . ucfirst($sorteio['status']) . '</span></td>
                    <td class="text-center">' . number_format($sorteio['total_participantes']) . '</td>
                    <td class="text-center">' . number_format($sorteio['total_ganhadores']) . '</td>
                    <td>' . formatDateBR($sorteio['created_at']) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        return $html;
    }
    
    /**
     * Gera HTML específico para relatório de conversão
     */
    private function generateConversaoHTML($dados) {
        $html = '';
        
        // Conversão geral
        if (isset($dados['conversao_geral'])) {
            $html .= '<div class="section">
                <div class="section-title">Conversão Geral</div>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['conversao_geral']['total_participantes'] ?? 0) . '</div>
                        <div class="metric-label">Total de Participantes</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['conversao_geral']['total_ganhadores'] ?? 0) . '</div>
                        <div class="metric-label">Total de Ganhadores</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">' . number_format($dados['conversao_geral']['taxa_conversao'] ?? 0, 2) . '%</div>
                        <div class="metric-label">Taxa de Conversão</div>
                    </div>
                </div>
            </div>';
        }
        
        // Conversão por sorteio
        if (isset($dados['conversao_por_sorteio']) && !empty($dados['conversao_por_sorteio'])) {
            $html .= '<div class="section">
                <div class="section-title">Conversão por Sorteio</div>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Sorteio</th>
                            <th class="text-center">Participantes</th>
                            <th class="text-center">Ganhadores</th>
                            <th class="text-center">Taxa de Conversão</th>
                            <th class="text-center">Performance</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($dados['conversao_por_sorteio'] as $sorteio) {
                $taxa = $sorteio['taxa_conversao'];
                $performance = $taxa >= 10 ? 'Alta' : ($taxa >= 5 ? 'Média' : 'Baixa');
                $performanceClass = $taxa >= 10 ? 'badge-success' : ($taxa >= 5 ? 'badge-warning' : 'badge-danger');
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($sorteio['nome']) . '</td>
                    <td class="text-center">' . number_format($sorteio['participantes']) . '</td>
                    <td class="text-center">' . number_format($sorteio['ganhadores']) . '</td>
                    <td class="text-center">' . number_format($taxa, 2) . '%</td>
                    <td class="text-center"><span class="badge ' . $performanceClass . '">' . $performance . '</span></td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        return $html;
    }
    
    /**
     * Gera HTML específico para relatório de engajamento
     */
    private function generateEngajamentoHTML($dados) {
        $html = '';
        
        // Engajamento por hora
        if (isset($dados['engajamento_por_hora']) && !empty($dados['engajamento_por_hora'])) {
            $html .= '<div class="section">
                <div class="section-title">Engajamento por Horário</div>
                <table>
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th class="text-center">Participantes</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($dados['engajamento_por_hora'] as $hora) {
                $html .= '<tr>
                    <td>' . $hora['hora'] . 'h</td>
                    <td class="text-center">' . number_format($hora['participantes']) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        // Engajamento por dia da semana
        if (isset($dados['engajamento_por_dia']) && !empty($dados['engajamento_por_dia'])) {
            $html .= '<div class="section">
                <div class="section-title">Engajamento por Dia da Semana</div>
                <table>
                    <thead>
                        <tr>
                            <th>Dia da Semana</th>
                            <th class="text-center">Participantes</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($dados['engajamento_por_dia'] as $dia) {
                $html .= '<tr>
                    <td>' . $dia['dia_semana'] . '</td>
                    <td class="text-center">' . number_format($dia['participantes']) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        return $html;
    }
    
    /**
     * Gera HTML específico para relatório comparativo
     */
    private function generateComparativoHTML($dados) {
        $html = '';
        
        if (isset($dados['comparativo_mensal']) && !empty($dados['comparativo_mensal'])) {
            $html .= '<div class="section">
                <div class="section-title">Evolução Mensal</div>
                <table>
                    <thead>
                        <tr>
                            <th>Mês/Ano</th>
                            <th class="text-center">Sorteios Criados</th>
                            <th class="text-center">Participantes</th>
                            <th class="text-center">Ganhadores</th>
                            <th class="text-center">Taxa de Conversão</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $dadosOrdenados = array_reverse($dados['comparativo_mensal']);
            
            foreach ($dadosOrdenados as $mes) {
                $taxaConversao = $mes['total_participantes'] > 0 ? 
                    ($mes['total_ganhadores'] / $mes['total_participantes']) * 100 : 0;
                
                $html .= '<tr>
                    <td>' . date('M/Y', strtotime($mes['mes'] . '-01')) . '</td>
                    <td class="text-center">' . number_format($mes['sorteios_criados']) . '</td>
                    <td class="text-center">' . number_format($mes['total_participantes']) . '</td>
                    <td class="text-center">' . number_format($mes['total_ganhadores']) . '</td>
                    <td class="text-center">' . number_format($taxaConversao, 2) . '%</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        return $html;
    }
    
    /**
     * Verifica se wkhtmltopdf está disponível
     */
    private function isWkhtmltopdfAvailable() {
        // Para hospedagem compartilhada, geralmente não está disponível
        return false;
    }
    
    /**
     * Gera PDF com wkhtmltopdf (se disponível)
     */
    private function generatePDFWithWkhtmltopdf($html, $filepath) {
        // Implementação futura se wkhtmltopdf estiver disponível
        return $this->generateHTMLForPrint($html, $filepath);
    }
    
    /**
     * Gera HTML otimizado para impressão
     */
    private function generateHTMLForPrint($html, $filepath) {
        $filename = str_replace('.pdf', '.html', basename($filepath));
        $htmlFilepath = str_replace('.pdf', '.html', $filepath);
        
        file_put_contents($htmlFilepath, $html);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $htmlFilepath,
            'size' => filesize($htmlFilepath),
            'note' => 'Arquivo HTML gerado. Use Ctrl+P no navegador para imprimir como PDF.'
        ];
    }
    
    /**
     * Limpa arquivos temporários antigos
     */
    public function cleanupTempFiles($maxAge = 3600) {
        $files = glob($this->tempDir . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }
    
    /**
     * Agenda relatório para envio por email (implementação futura)
     */
    public function scheduleReport($config) {
        // Para implementação futura com sistema de cron/agendamento
        throw new Exception('Agendamento de relatórios ainda não implementado');
    }
}
?>