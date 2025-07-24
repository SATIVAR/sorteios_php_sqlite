<?php
/**
 * Teste de responsividade em diferentes dispositivos
 * 
 * Este teste verifica se as páginas principais do sistema estão
 * configuradas corretamente para responsividade, verificando
 * a presença de meta tags viewport e classes CSS responsivas.
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE RESPONSIVIDADE ===\n\n";

// Lista de páginas para testar
$paginas = [
    '/index.php',
    '/login.php',
    '/admin.php',
    '/sorteios.php',
    '/participantes.php',
    '/participar.php',
    '/historico.php',
    '/relatorios.php'
];

// Lista de dispositivos para simular
$dispositivos = [
    'Mobile' => [
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
        'width' => 375,
        'height' => 812
    ],
    'Tablet' => [
        'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1',
        'width' => 768,
        'height' => 1024
    ],
    'Desktop' => [
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'width' => 1920,
        'height' => 1080
    ]
];

// Função para verificar responsividade
function verificarResponsividade($html) {
    $resultados = [
        'viewport' => false,
        'media_queries' => false,
        'tailwind_responsive' => false,
        'flowbite' => false
    ];
    
    // Verificar meta viewport
    if (strpos($html, '<meta name="viewport"') !== false) {
        $resultados['viewport'] = true;
    }
    
    // Verificar media queries
    if (strpos($html, '@media') !== false) {
        $resultados['media_queries'] = true;
    }
    
    // Verificar classes responsivas do Tailwind
    $tailwindClasses = ['sm:', 'md:', 'lg:', 'xl:', '2xl:'];
    foreach ($tailwindClasses as $class) {
        if (strpos($html, $class) !== false) {
            $resultados['tailwind_responsive'] = true;
            break;
        }
    }
    
    // Verificar Flowbite
    if (strpos($html, 'flowbite') !== false) {
        $resultados['flowbite'] = true;
    }
    
    return $resultados;
}

// Testar cada página em cada dispositivo
$resultadosGerais = [];

foreach ($paginas as $pagina) {
    echo "\nTestando página: $pagina\n";
    $resultadosGerais[$pagina] = [];
    
    foreach ($dispositivos as $dispositivo => $config) {
        echo "  Dispositivo: $dispositivo\n";
        
        // Simular requisição com User-Agent específico
        $_SERVER['HTTP_USER_AGENT'] = $config['user_agent'];
        
        // Simular dimensões da tela
        $_SERVER['HTTP_X_DEVICE_WIDTH'] = $config['width'];
        $_SERVER['HTTP_X_DEVICE_HEIGHT'] = $config['height'];
        
        // Obter conteúdo da página
        $output = simulateRequest($pagina);
        
        // Verificar responsividade
        $resultados = verificarResponsividade($output);
        $resultadosGerais[$pagina][$dispositivo] = $resultados;
        
        // Exibir resultados
        echo "    Meta Viewport: " . ($resultados['viewport'] ? "✓" : "✗") . "\n";
        echo "    Media Queries: " . ($resultados['media_queries'] ? "✓" : "✗") . "\n";
        echo "    Classes Tailwind Responsivas: " . ($resultados['tailwind_responsive'] ? "✓" : "✗") . "\n";
        echo "    Flowbite: " . ($resultados['flowbite'] ? "✓" : "✗") . "\n";
    }
}

// Análise final
echo "\n=== ANÁLISE FINAL DE RESPONSIVIDADE ===\n";

$totalPaginas = count($paginas);
$totalDispositivos = count($dispositivos);
$totalTestes = $totalPaginas * $totalDispositivos;

$sucessos = [
    'viewport' => 0,
    'media_queries' => 0,
    'tailwind_responsive' => 0,
    'flowbite' => 0
];

foreach ($resultadosGerais as $pagina => $resultadosPagina) {
    foreach ($resultadosPagina as $dispositivo => $resultados) {
        foreach ($resultados as $teste => $resultado) {
            if ($resultado) {
                $sucessos[$teste]++;
            }
        }
    }
}

echo "Total de páginas testadas: $totalPaginas\n";
echo "Total de dispositivos testados: $totalDispositivos\n";
echo "Total de testes realizados: $totalTestes\n\n";

echo "Meta Viewport: " . $sucessos['viewport'] . "/$totalTestes (" . round(($sucessos['viewport'] / $totalTestes) * 100) . "%)\n";
echo "Media Queries: " . $sucessos['media_queries'] . "/$totalTestes (" . round(($sucessos['media_queries'] / $totalTestes) * 100) . "%)\n";
echo "Classes Tailwind Responsivas: " . $sucessos['tailwind_responsive'] . "/$totalTestes (" . round(($sucessos['tailwind_responsive'] / $totalTestes) * 100) . "%)\n";
echo "Flowbite: " . $sucessos['flowbite'] . "/$totalTestes (" . round(($sucessos['flowbite'] / $totalTestes) * 100) . "%)\n";

// Conclusão
$mediaTotal = ($sucessos['viewport'] + $sucessos['media_queries'] + $sucessos['tailwind_responsive'] + $sucessos['flowbite']) / (4 * $totalTestes);
$porcentagemTotal = round($mediaTotal * 100);

echo "\nPorcentagem geral de responsividade: $porcentagemTotal%\n";

if ($porcentagemTotal >= 80) {
    echo "✓ O sistema está bem adaptado para diferentes dispositivos!\n";
} elseif ($porcentagemTotal >= 50) {
    echo "⚠ O sistema tem responsividade parcial. Melhorias são recomendadas.\n";
} else {
    echo "✗ O sistema tem problemas significativos de responsividade!\n";
}

echo "\n=== TESTE CONCLUÍDO! ===\n";