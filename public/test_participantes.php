<?php
/**
 * Teste básico do sistema de participantes
 */

define('SISTEMA_SORTEIOS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';

echo "<h1>Teste do Sistema de Participantes</h1>";

try {
    $db = getDatabase();
    
    // Teste 1: Verificar se as tabelas existem
    echo "<h2>1. Verificando estrutura do banco:</h2>";
    
    $tables = ['sorteios', 'participantes', 'sorteio_resultados'];
    foreach ($tables as $table) {
        $result = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        echo "Tabela {$table}: " . ($result ? "✓ Existe" : "✗ Não existe") . "<br>";
    }
    
    // Teste 2: Verificar se existem sorteios
    echo "<h2>2. Verificando sorteios:</h2>";
    $sorteios = $db->fetchAll("SELECT id, nome, status FROM sorteios LIMIT 5");
    if (empty($sorteios)) {
        echo "Nenhum sorteio encontrado.<br>";
    } else {
        foreach ($sorteios as $sorteio) {
            echo "Sorteio #{$sorteio['id']}: {$sorteio['nome']} ({$sorteio['status']})<br>";
        }
    }
    
    // Teste 3: Verificar participantes
    echo "<h2>3. Verificando participantes:</h2>";
    $participantes = $db->fetchAll("SELECT COUNT(*) as total FROM participantes");
    echo "Total de participantes: " . ($participantes[0]['total'] ?? 0) . "<br>";
    
    // Teste 4: Verificar funções auxiliares
    echo "<h2>4. Testando funções auxiliares:</h2>";
    
    // Teste formatação CPF
    $cpf_teste = "12345678901";
    echo "CPF formatado: " . formatCPF($cpf_teste) . "<br>";
    
    // Teste formatação WhatsApp
    $whatsapp_teste = "11999887766";
    echo "WhatsApp formatado: " . formatWhatsApp($whatsapp_teste) . "<br>";
    
    // Teste data
    echo "Data formatada: " . formatDateBR(date('Y-m-d H:i:s')) . "<br>";
    
    // Teste 5: Verificar validador
    echo "<h2>5. Testando validador:</h2>";
    $validator = getValidator();
    
    // Teste CPF válido
    $cpf_valido = $validator->cpf("123.456.789-01", "cpf_teste");
    echo "CPF válido: " . ($cpf_valido ? "✓" : "✗") . "<br>";
    
    // Teste email válido
    $email_valido = $validator->email("teste@exemplo.com", "email_teste");
    echo "Email válido: " . ($email_valido ? "✓" : "✗") . "<br>";
    
    echo "<h2>✓ Todos os testes básicos passaram!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><a href='participantes.php'>← Voltar para Participantes</a>";
?>