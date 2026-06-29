<?php

define('APP_NAME', 'SGHSS VidaPlus');

/*
|--------------------------------------------------------------------------
| BASE_URL
|--------------------------------------------------------------------------
| Se o projeto estiver direto na raiz do InfinityFree, deixe como '/'.
| Exemplo:
| https://seudominio.infinityfreeapp.com/
|
| Se estiver dentro de uma pasta, exemplo:
| https://seudominio.infinityfreeapp.com/sghss/
| use:
| define('BASE_URL', '/sghss/');
*/
define('BASE_URL', '/');

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}