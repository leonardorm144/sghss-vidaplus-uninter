<?php

define('APP_NAME', 'SGHSS VidaPlus');

define('BASE_URL', '/');

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
