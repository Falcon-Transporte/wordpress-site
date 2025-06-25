<?php
// Carrega automaticamente todos os arquivos .php dentro de subpastas de /inc
date_default_timezone_set('America/Sao_Paulo'); // ou o timezone correto do seu sistema

$folders = ['shortcodes', 'scripts', 'admin', 'auth', 'access-control', 'utils'];

foreach ($folders as $folder) {
    foreach (glob(__DIR__ . '/' . $folder . '/*.php') as $file) {
        require_once $file;
    }
}