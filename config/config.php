<?php
// config/config.php

// 1. Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'moveisjb_database');
define('DB_USER', 'moveisjb_hotspot_db');
define('DB_PASS', 'vtgd65aoty');

// 2. Configurações do MikroTik (Acesso à API para criar usuário)
//define('MK_HOST', '206.42.25.15');
define('MK_HOST', 'api.deboananet.club');
define('MK_USER', 'admin');
define('MK_PASS', 'xtz900af');
define('MK_PORT', '8080');

// 3. Credenciais de Acesso ao Painel Administrativo
define('ADMIN_USER', 'thiago');
define('ADMIN_PASS', 'vtgd65aoty');

// 4. IP do Gateway Hotspot do MikroTik (Para login automático do cliente)
define('MK_HOTSPOT_IP', 'api.deboananet.club');

// 5. Configurações do Mercado Pago
define('MP_TOKEN', 'APP_USR-1238557524864247-090421-5066188abd1e8361a8a839231b517f29-101398970');
?>