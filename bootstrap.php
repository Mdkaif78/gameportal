<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function twig(): \Twig\Environment
{
    static $twig;
    if (!$twig) {
        if (!is_file(__DIR__ . '/vendor/autoload.php')) {
            http_response_code(500);
            exit('Twig is not installed. Run "composer install" in the Assignment folder.');
        }
        require_once __DIR__ . '/vendor/autoload.php';
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
        $twig = new \Twig\Environment($loader, ['autoescape' => 'html', 'cache' => false]);
    }
    return $twig;
}

function render(string $template, array $data = []): void
{
    start_secure_session();
    $data += [
        'logged_in' => !empty($_SESSION['user_id']),
        'username' => $_SESSION['username'] ?? '',
        'csrf_token' => csrf_token(),
        'flash' => $_SESSION['flash'] ?? null,
    ];
    unset($_SESSION['flash']);
    echo twig()->render($template, $data);
}

function flash(string $message): void
{
    start_secure_session();
    $_SESSION['flash'] = $message;
}
