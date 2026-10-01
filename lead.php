<?php
/**
 * POST lead.php
 *
 * Recibe el formulario "Enviarme mi informe" de la calculadora, lo valida y lo reenvía (JSON)
 * al webhook configurado en lead-config.php (Zapier, Make, HubSpot, Slack, tu CRM...).
 * Desde ahí tu automatización envía el email al lead y avisa a ventas.
 *
 * Requisitos: PHP 7.4 o superior. Usa cURL si está disponible y, si no, allow_url_fopen.
 *
 * Protecciones: solo POST, comprobación de origen, campo trampa (honeypot), límite de 5 envíos
 * por IP cada 10 min, validación y saneado de todos los campos, y 8 s máximo hacia el webhook.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cfg      = is_file(__DIR__ . '/lead-config.php') ? (require __DIR__ . '/lead-config.php') : [];
$webhook  = (string)($cfg['webhook_url'] ?? '');
$allowed  = array_values(array_filter(array_map('trim', (array)($cfg['allowed_origins'] ?? []))));
$ipHeader = (string)($cfg['ip_header'] ?? '');   // ej. HTTP_CF_CONNECTING_IP si estás detrás de Cloudflare

const WINDOW_SECONDS = 600;
const MAX_HITS       = 5;
const MAX_BODY       = 20000;

function out(int $code, array $body = []): void {
    http_response_code($code);
    if ($code !== 204) { echo json_encode($body); }
    exit;
}

function host_of(string $url): string {
    $p = parse_url($url);
    if (!is_array($p) || empty($p['host'])) { return ''; }
    return strtolower($p['host']) . (isset($p['port']) ? ':' . $p['port'] : '');
}

function origin_allowed(string $origin, string $reqHost, array $allowed): bool {
    if ($origin === '') { return false; }
    $host = host_of($origin);
    if ($host === '') { return false; }
    if ($host === strtolower($reqHost)) { return true; }
    return in_array($origin, $allowed, true) || in_array($host, $allowed, true);
}

function clamp_int($v, float $max, bool $signed = false): int {
    $f = is_numeric($v) ? (float)$v : 0.0;
    if (!is_finite($f)) { return 0; }
    $f = round($f);
    $f = max($signed ? -$max : 0.0, min($max, $f));
    return (int)$f;
}

function clamp_float($v, float $max): float {
    $f = is_numeric($v) ? (float)$v : 0.0;
    return is_finite($f) ? max(0.0, min($max, $f)) : 0.0;
}

function rate_limited(string $ip): bool {
    $dir = sys_get_temp_dir() . '/fivo-calc-rl';
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    $file = $dir . '/' . sha1($ip) . '.json';
    $now  = time();
    $hits = [];
    if (is_file($file)) {
        $d = json_decode((string)@file_get_contents($file), true);
        if (is_array($d)) { $hits = $d; }
    }
    $hits   = array_values(array_filter($hits, function ($t) use ($now) { return $now - (int)$t < WINDOW_SECONDS; }));
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return count($hits) > MAX_HITS;
}

function post_json(string $url, string $json): int {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $res === false ? 0 : $code;
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\n",
        'content'       => $json,
        'timeout'       => 8,
        'ignore_errors' => true,
    ]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false || empty($http_response_header[0])) { return 0; }
    return preg_match('#\s(\d{3})\s#', $http_response_header[0], $m) ? (int)$m[1] : 0;
}

$origin  = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
$reqHost = (string)($_SERVER['HTTP_HOST'] ?? '');
$okOrigin = origin_allowed($origin, $reqHost, $allowed);

if ($okOrigin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'OPTIONS') { out($okOrigin ? 204 : 403); }
if ($method !== 'POST')    { header('Allow: POST, OPTIONS'); out(405, ['ok' => false, 'error' => 'method']); }
if (!$okOrigin)            { out(403, ['ok' => false, 'error' => 'origin']); }
if ($webhook === '')       { out(503, ['ok' => false, 'error' => 'not_configured']); }

$raw = (string)file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if (strlen($raw) > MAX_BODY) { out(413, ['ok' => false, 'error' => 'too_large']); }
$body = json_decode($raw, true);
if (!is_array($body))        { out(400, ['ok' => false, 'error' => 'body']); }

if (!empty($body['hp'])) { out(200, ['ok' => true]); }   // campo trampa: responde "bien" sin hacer nada

$ip = (string)($ipHeader !== '' && !empty($_SERVER[$ipHeader]) ? $_SERVER[$ipHeader] : ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$ip = trim(explode(',', $ip)[0]);
if (rate_limited($ip)) { out(429, ['ok' => false, 'error' => 'rate_limit']); }

$email = strtolower(trim((string)($body['email'] ?? '')));
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { out(400, ['ok' => false, 'error' => 'email']); }

$i = isset($body['inputs'])  && is_array($body['inputs'])  ? $body['inputs']  : [];
$r = isset($body['results']) && is_array($body['results']) ? $body['results'] : [];

$link = '';
if (!empty($body['link']) && is_string($body['link'])) {
    $lh = host_of($body['link']);
    $allowedHosts = array_map(function ($o) { return host_of($o) ?: $o; }, $allowed);
    if ($lh !== '' && ($lh === strtolower($reqHost) || in_array($lh, $allowedHosts, true))) {
        $link = substr($body['link'], 0, 2000);
    }
}

$lead = [
    'source'      => 'fivo-calculadora',
    'submittedAt' => gmdate('c'),
    'email'       => $email,
    'lang'        => (($body['lang'] ?? '') === 'en') ? 'en' : 'es',
    'inputs'      => [
        'people'  => clamp_int($i['people']  ?? 0, 100000),
        'team'    => clamp_int($i['team']    ?? 0, 1e10),
        'calls'   => clamp_int($i['calls']   ?? 0, 1e11),
        'loom'    => clamp_int($i['loom']    ?? 0, 1e9),
        'others'  => clamp_int($i['others']  ?? 0, 1e9),
        'coach'   => clamp_int($i['coach']   ?? 0, 1e7),
        'analyst' => clamp_int($i['analyst'] ?? 0, 1e7),
        'rec'     => clamp_float($i['rec']    ?? 0, 100),
        'uplift'  => clamp_float($i['uplift'] ?? 0, 100),
        'subst'   => clamp_float($i['subst']  ?? 0, 100),
        'plan'    => (($i['plan'] ?? '') === 'annual') ? 'annual' : 'monthly',
        'audOn'   => !empty($i['audOn']),
        'revOn'   => !empty($i['revOn']),
    ],
    'results'     => [
        'tools'    => clamp_int($r['tools']    ?? 0, 1e11),
        'audit'    => clamp_int($r['audit']    ?? 0, 1e11),
        'value'    => clamp_int($r['value']    ?? 0, 1e12),
        'benefits' => clamp_int($r['benefits'] ?? 0, 1e12),
        'cost'     => clamp_int($r['cost']     ?? 0, 1e11),
        'net'      => clamp_int($r['net']      ?? 0, 1e12, true),
    ],
    'link'        => $link,
];

$status = post_json($webhook, json_encode($lead, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
if ($status < 200 || $status >= 300) {
    error_log('[lead] fallo al reenviar al webhook (estado ' . $status . ')');
    out(502, ['ok' => false, 'error' => 'upstream']);
}
out(200, ['ok' => true]);
