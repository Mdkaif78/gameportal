<?php
declare(strict_types=1);

// Send security headers from PHP so responses do not depend on Apache
// mod_headers being enabled for this user's .htaccess file.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; upgrade-insecure-requests");
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Browsers only honor HSTS over HTTPS, including when TLS terminates at a proxy.
    header('Strict-Transport-Security: max-age=31536000');
}

// Set these as environment variables on mi-linux. For local development, copy
// .env.example to .env and set equivalent values in your PHP server config.
function app_config(): array
{
    static $config;
    if ($config !== null) {
        return $config;
    }

    // Keep production secrets outside public_html when possible. When the app
    // is in ~/public_html/game-portal, the private file is in the home folder.
    // The parent candidate also supports installing directly in public_html.
    $envCandidates = [dirname(__DIR__, 2) . '/.game-portal.env', dirname(__DIR__) . '/.game-portal.env', __DIR__ . '/.env'];
    $envFile = __DIR__ . '/.env';
    foreach ($envCandidates as $candidate) {
        if (is_file($candidate)) { $envFile = $candidate; break; }
    }
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                putenv($key . '=' . trim($value, " \t\n\r\0\x0B\"'"));
            }
        }
    }

    $config = [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'game_portal',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'captcha' => getenv('CAPTCHA_ENABLED') !== '0',
        'allow_registration' => getenv('ALLOW_REGISTRATION') === '1',
    ];
    return $config;
}

function db(): mysqli
{
    static $mysqli;
    if ($mysqli instanceof mysqli) return $mysqli;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $cfg = app_config();
    $mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax']);
    session_start();
}

function csrf_token(): string
{
    start_secure_session();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(): void
{
    start_secure_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('The form expired. Go back, reload the page, and try again.');
    }
}

function require_login(): void
{
    start_secure_session();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function valid_game_input(array $source): array
{
    $name = trim((string)($source['game_name'] ?? ''));
    $description = trim((string)($source['game_description'] ?? ''));
    $date = trim((string)($source['released_date'] ?? ''));
    $rating = filter_var($source['rating'] ?? null, FILTER_VALIDATE_INT);
    $errors = [];
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Game name is required (up to 120 characters).';
    if (mb_strlen($description) > 5000) $errors[] = 'Description must be 5,000 characters or fewer.';
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) $errors[] = 'Enter a valid release date.';
    if ($rating === false || $rating < 0 || $rating > 100) $errors[] = 'Rating must be a whole number from 0 to 100.';
    return [['game_name' => $name, 'game_description' => $description, 'released_date' => $date, 'rating' => $rating], $errors];
}
