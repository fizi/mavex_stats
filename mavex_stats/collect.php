<?php
/** Public, minimal page-view collector. Does not store IP addresses. */
// This endpoint is a background beacon, not a page visit. Do not let it
// enter e107's online-user tracking or flood-protection page-hit counter.
$_E107['no_online'] = true;
require_once('../../class2.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !defined('e107_INIT')) {
    http_response_code(405);
    exit;
}

if (defined('e_ADMIN_AREA') && e_ADMIN_AREA) {
    http_response_code(204);
    exit;
}

$statsPrefs = (array) e107::pref('mavex_stats');
if (isset($statsPrefs['tracking_enabled']) && empty($statsPrefs['tracking_enabled'])) {
    http_response_code(204);
    exit;
}
if (empty($statsPrefs['count_admin']) && function_exists('getperms') && getperms('P')) {
    http_response_code(204);
    exit;
}

$rawPath = isset($_POST['path']) ? (string) $_POST['path'] : '/';
$parsedPath = parse_url($rawPath);
$path = is_array($parsedPath) && isset($parsedPath['path']) ? (string) $parsedPath['path'] : '/';
$path = substr(preg_replace('/[^\pL\pN\/_\-.%]/u', '', $path), 0, 400);
if ($path === '') { $path = '/'; }

// Keep ordinary query parameters so non-SEF e107 pages remain distinguishable.
// Activation and password-reset endpoints may contain private one-time tokens;
// their query strings are deliberately never stored.
$queryString = '';
$scriptName = strtolower(basename($path));
if (!in_array($scriptName, array('signup.php', 'fpw.php'), true) && !empty($parsedPath['query'])) {
    $queryParams = array();
    parse_str((string) $parsedPath['query'], $parsedParams);
    $blockedParamPattern = '/(pass(word)?|token|secret|session|sessid|sid|activation|activate|reset|confirm|auth|csrf|nonce)/i';
    $paramCount = 0;
    foreach ($parsedParams as $paramKey => $paramValue) {
        if ($paramCount >= 20 || preg_match($blockedParamPattern, (string) $paramKey)) { continue; }
        if (!is_scalar($paramValue)) { continue; }
        $safeKey = substr((string) $paramKey, 0, 80);
        $safeValue = substr((string) $paramValue, 0, 160);
        if ($safeKey === '') { continue; }
        $queryParams[$safeKey] = $safeValue;
        $paramCount++;
    }
    if ($queryParams) { $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986); }
}
if ($queryString !== '') { $path .= '?' . $queryString; }
$path = substr($path, 0, 500);

$referrer = isset($_POST['referrer']) ? strtolower((string) $_POST['referrer']) : '';
$referrer = preg_replace('/[^a-z0-9.\-:]/', '', $referrer);
$referrer = substr($referrer, 0, 255);

$screen = isset($_POST['screen']) ? (string) $_POST['screen'] : '';
if (!preg_match('/^\d{1,5}x\d{1,5}$/', $screen)) { $screen = ''; }

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : '';

// Exclude well-known automated crawlers and monitoring agents. This is a
// User-Agent based filter; it cannot identify every bot or spoofed client.
$botPattern = '/(bot|crawler|spider|slurp|search|preview|facebookexternalhit|facebot|ia_archiver|bingpreview|google-inspectiontool|googleother|apis-google|adsbot|mediapartners-google|yandex|baiduspider|duckduckbot|applebot| ahrefs|semrush|mj12bot|dotbot|petalbot|bytespider|sogou|exabot|crawler4j|uptimerobot|pingdom|statuscake)/i';
if ($userAgent === '' || preg_match($botPattern, $userAgent)) {
    http_response_code(204);
    exit;
}

$cookieName = 'mavex_stats_vid';
$visitorId = isset($_COOKIE[$cookieName]) ? (string) $_COOKIE[$cookieName] : '';
if (!preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
    $visitorId = bin2hex(random_bytes(16));
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie($cookieName, $visitorId, array(
        'expires' => time() + (180 * 86400),
        'path' => defined('e_HTTP') ? e_HTTP : '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ));
}

$record = array(
    'event_time' => time(),
    'visitor_hash' => hash('sha256', $visitorId),
    'page_path' => $path,
    'referrer_host' => $referrer,
    'user_agent' => $userAgent,
    'screen_size' => $screen
);

try {
    e107::getDb()->insert('mavex_stats_events', $record);
} catch (Throwable $e) {
    // Collector failures must never affect the public page.
}

http_response_code(204);
exit;
