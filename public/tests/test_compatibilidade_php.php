<?php
/**
 * Teste de compatibilidade com diferentes versões do PHP
 * 
 * Este teste verifica se o código é compatível com diferentes versões do PHP
 * através da análise estática do código e verificação de funções/recursos utilizados.
 */

require_once __DIR__ . '/config.php';

echo "=== TESTE DE COMPATIBILIDADE COM DIFERENTES VERSÕES DO PHP ===\n\n";

// Definir versões do PHP para testar
$phpVersions = [
    '7.0',
    '7.2',
    '7.4',
    '8.0',
    '8.1',
    '8.2'
];

// Obter versão atual do PHP
$currentVersion = phpversion();
echo "Versão atual do PHP: $currentVersion\n\n";

// Diretórios para analisar
$directories = [
    ROOT_PATH . '/includes',
    ROOT_PATH . '/includes/classes',
    ROOT_PATH
];

// Funções/recursos introduzidos em versões específicas do PHP
$phpFeatures = [
    '7.1' => [
        'features' => ['void', 'nullable_types', 'symmetric_array_destructuring', 'class_constant_visibility'],
        'functions' => []
    ],
    '7.2' => [
        'features' => ['object_type', 'abstract_method_overriding'],
        'functions' => ['stream_isatty', 'sapi_windows_vt100_support']
    ],
    '7.3' => [
        'features' => ['flexible_heredoc_nowdoc', 'array_destructuring_reference'],
        'functions' => ['array_key_first', 'array_key_last', 'is_countable']
    ],
    '7.4' => [
        'features' => ['typed_properties', 'arrow_functions', 'null_coalescing_assignment', 'spread_operator_in_arrays'],
        'functions' => ['mb_str_split', 'password_algos']
    ],
    '8.0' => [
        'features' => ['union_types', 'named_arguments', 'attributes', 'match_expression', 'nullsafe_operator'],
        'functions' => ['str_contains', 'str_starts_with', 'str_ends_with', 'fdiv']
    ],
    '8.1' => [
        'features' => ['enums', 'readonly_properties', 'first_class_callable_syntax', 'pure_intersection_types'],
        'functions' => ['array_is_list', 'fsync']
    ],
    '8.2' => [
        'features' => ['readonly_classes', 'null_false_true_types', 'disjunctive_normal_form_types'],
        'functions' => ['memory_reset_peak_usage', 'curl_upkeep']
    ]
];

// Padrões para detectar recursos específicos de versão
$featurePatterns = [
    'void' => '/function\s+\w+\s*\([^)]*\)\s*:\s*void\b/i',
    'nullable_types' => '/\?[A-Za-z_][A-Za-z0-9_]*\s+\$\w+/i',
    'typed_properties' => '/\bprivate\s+[A-Za-z_][A-Za-z0-9_]*\s+\$\w+|protected\s+[A-Za-z_][A-Za-z0-9_]*\s+\$\w+|public\s+[A-Za-z_][A-Za-z0-9_]*\s+\$\w+/i',
    'arrow_functions' => '/\bfn\s*\([^)]*\)\s*=>/i',
    'null_coalescing_assignment' => '/\$\w+\s*\?\?=\s*/i',
    'union_types' => '/\b(string|int|float|bool|array|object|callable|iterable|self|parent)\s*\|\s*(string|int|float|bool|array|object|callable|iterable|self|parent)\b/i',
    'nullsafe_operator' => '/\$\w+\?->\w+/i',
    'match_expression' => '/\bmatch\s*\(/i',
    'readonly_properties' => '/\breadonly\s+(public|protected|private)\s+/i',
    'readonly_classes' => '/\breadonly\s+class\s+/i',
    'enums' => '/\benum\s+\w+/i'
];

// Resultados da análise
$results = [];

// Analisar arquivos PHP
echo "Analisando arquivos PHP para compatibilidade...\n";
$phpFiles = [];

foreach ($directories as $directory) {
    if (is_dir($directory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    } elseif (is_file($directory) && pathinfo($directory, PATHINFO_EXTENSION) === 'php') {
        $phpFiles[] = $directory;
    }
}

echo "Encontrados " . count($phpFiles) . " arquivos PHP para análise.\n\n";

// Inicializar resultados
foreach ($phpVersions as $version) {
    $results[$version] = [
        'compatible' => true,
        'issues' => []
    ];
}

// Analisar cada arquivo
foreach ($phpFiles as $file) {
    $relativePath = str_replace(ROOT_PATH, '', $file);
    $content = file_get_contents($file);
    
    echo "Analisando: $relativePath\n";
    
    // Verificar funções utilizadas
    $usedFunctions = [];
    preg_match_all('/\b([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/m', $content, $matches);
    if (!empty($matches[1])) {
        $usedFunctions = array_unique($matches[1]);
    }
    
    // Verificar recursos de linguagem utilizados
    $usedFeatures = [];
    foreach ($featurePatterns as $feature => $pattern) {
        if (preg_match($pattern, $content)) {
            $usedFeatures[] = $feature;
        }
    }
    
    // Verificar compatibilidade com cada versão
    foreach ($phpVersions as $version) {
        // Verificar funções incompatíveis
        foreach ($phpFeatures as $reqVersion => $features) {
            if (version_compare($version, $reqVersion, '<')) {
                // Verificar funções introduzidas em versões mais recentes
                foreach ($features['functions'] as $function) {
                    if (in_array($function, $usedFunctions)) {
                        $results[$version]['compatible'] = false;
                        $results[$version]['issues'][] = "Arquivo $relativePath usa função '$function' disponível apenas no PHP $reqVersion+";
                    }
                }
                
                // Verificar recursos introduzidos em versões mais recentes
                foreach ($features['features'] as $feature) {
                    if (in_array($feature, $usedFeatures)) {
                        $results[$version]['compatible'] = false;
                        $results[$version]['issues'][] = "Arquivo $relativePath usa recurso '$feature' disponível apenas no PHP $reqVersion+";
                    }
                }
            }
        }
    }
}

// Exibir resultados
echo "\nResultados da análise de compatibilidade:\n";
echo "----------------------------------------\n\n";

foreach ($phpVersions as $version) {
    echo "PHP $version: ";
    if ($results[$version]['compatible']) {
        echo "✓ Compatível\n";
    } else {
        echo "✗ Incompatível\n";
        foreach ($results[$version]['issues'] as $issue) {
            echo "  - $issue\n";
        }
    }
    echo "\n";
}

// Verificar requisitos do sistema
echo "Verificando requisitos do sistema...\n";

// Verificar extensões necessárias
$requiredExtensions = [
    'pdo',
    'pdo_sqlite',
    'json',
    'mbstring',
    'fileinfo'
];

$missingExtensions = [];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (empty($missingExtensions)) {
    echo "✓ Todas as extensões necessárias estão disponíveis.\n";
} else {
    echo "✗ Extensões ausentes: " . implode(', ', $missingExtensions) . "\n";
}

// Verificar configurações do PHP
echo "\nVerificando configurações do PHP...\n";

$requiredSettings = [
    'file_uploads' => true,
    'allow_url_fopen' => true,
    'display_errors' => false,
    'error_reporting' => E_ALL
];

$incompatibleSettings = [];
foreach ($requiredSettings as $setting => $value) {
    $currentValue = ini_get($setting);
    if (is_bool($value)) {
        $currentValue = (bool)$currentValue;
    }
    
    if ($currentValue != $value) {
        $incompatibleSettings[$setting] = [
            'expected' => $value,
            'actual' => $currentValue
        ];
    }
}

if (empty($incompatibleSettings)) {
    echo "✓ Todas as configurações do PHP estão adequadas.\n";
} else {
    echo "⚠ Configurações inadequadas:\n";
    foreach ($incompatibleSettings as $setting => $values) {
        echo "  - $setting: esperado " . var_export($values['expected'], true) . ", atual " . var_export($values['actual'], true) . "\n";
    }
}

// Resumo final
echo "\n=== RESUMO DE COMPATIBILIDADE ===\n\n";

$compatibleVersions = [];
foreach ($phpVersions as $version) {
    if ($results[$version]['compatible']) {
        $compatibleVersions[] = $version;
    }
}

if (!empty($compatibleVersions)) {
    echo "✓ O sistema é compatível com as seguintes versões do PHP: " . implode(', ', $compatibleVersions) . "\n";
} else {
    echo "✗ O sistema não é compatível com nenhuma das versões testadas!\n";
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";