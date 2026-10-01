<?php
// Configuración del formulario. Edita SOLO este archivo.
// No lo subas a repositorios públicos. En Apache queda protegido por .htaccess.
return [
    // URL que recibe el lead (Zapier "Catch Hook", Make, HubSpot, Slack...). OBLIGATORIA.
    'webhook_url' => '',

    // Solo si la página y lead.php están en dominios distintos. Ej.: ['https://fivo.ai', 'https://www.fivo.ai']
    'allowed_origins' => [],

    // Solo si estás detrás de un proxy/CDN que reenvía la IP real. Ej.: 'HTTP_CF_CONNECTING_IP' (Cloudflare)
    'ip_header' => '',
];
