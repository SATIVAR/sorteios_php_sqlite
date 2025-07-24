<?php
/**
 * Roteador universal para URLs amigáveis (Front Controller)
 * Redireciona URLs como /participar/SEUTOKEN para participar.php?url=SEUTOKEN
 * Funciona em qualquer ambiente (Apache, Nginx, Docker, hospedagem compartilhada)
 */

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Roteamento para /participar/SEUTOKEN
if (preg_match('#^/participar/([a-zA-Z0-9]+)$#', $request_uri, $matches)) {
    $_GET['url'] = $matches[1];
    require __DIR__ . '/participar.php';
    exit;
}

// Fallback: se o arquivo existe, deixa o servidor servir normalmente
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . $request_uri;
    if (is_file($file)) {
        return false;
    }
}

// Inclui o index.php original para manter o fluxo padrão
require __DIR__ . '/index.php';
