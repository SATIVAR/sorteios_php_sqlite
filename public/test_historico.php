<?php
/**
 * Teste do Sistema de Histórico
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';

try {
    echo "<h1>Teste do Sistema de Histórico</h1>";
    
    // Testar conexão com banco
    $db = getDatabase();
    echo "<p>✅ Conexão com banco: OK</p>";
    
    // Verificar se as tabelas existem
    $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    echo "<h2>Tabelas encontradas:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table['name'] . "</li>";
    }
    echo "</ul>";
    
    // Testar função formatTimeAgo
    echo "<h2>Teste da função formatTimeAgo:</h2>";
    echo "<p>Agora: " . formatTimeAgo(date('Y-m-d H:i:s')) . "</p>";
    echo "<p>1 hora atrás: " . formatTimeAgo(date('Y-m-d H:i:s', strtotime('-1 hour'))) . "</p>";
    echo "<p>1 dia atrás: " . formatTimeAgo(date('Y-m-d H:i:s', strtotime('-1 day'))) . "</p>";
    
    // Verificar se existem sorteios
    $sorteios = $db->fetchAll("SELECT COUNT(*) as count FROM sorteios");
    echo "<h2>Dados do sistema:</h2>";
    echo "<p>Total de sorteios: " . ($sorteios[0]['count'] ?? 0) . "</p>";
    
    $participantes = $db->fetchAll("SELECT COUNT(*) as count FROM participantes");
    echo "<p>Total de participantes: " . ($participantes[0]['count'] ?? 0) . "</p>";
    
    $resultados = $db->fetchAll("SELECT COUNT(*) as count FROM sorteio_resultados");
    echo "<p>Total de resultados: " . ($resultados[0]['count'] ?? 0) . "</p>";
    
    // Testar funções do histórico
    if (function_exists('getSorteiosRealizados')) {
        echo "<p>✅ Função getSorteiosRealizados: Existe</p>";
    } else {
        echo "<p>❌ Função getSorteiosRealizados: Não encontrada</p>";
    }
    
    if (class_exists('SorteioEngine')) {
        echo "<p>✅ Classe SorteioEngine: Existe</p>";
        
        $engine = new SorteioEngine();
        echo "<p>✅ Instância SorteioEngine: Criada</p>";
    } else {
        echo "<p>❌ Classe SorteioEngine: Não encontrada</p>";
    }
    
    echo "<h2>Sistema de Histórico</h2>";
    echo "<p><a href='historico.php'>Acessar Histórico</a></p>";
    echo "<p><a href='admin.php'>Acessar Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>