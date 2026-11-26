<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
start_secure_session();
$cfg = app_config(); $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $captchaOk = !$cfg['captcha'] || (isset($_SESSION['captcha_answer']) && (string)($_POST['captcha'] ?? '') === (string)$_SESSION['captcha_answer']);
    unset($_SESSION['captcha_answer']);
    if (!$captchaOk) $error = 'Security answer is incorrect. Try again.';
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username) || strlen($password) > 4096) $error = 'Invalid username or password.';
    else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->bind_param('s', $username); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); $_SESSION['user_id'] = (int)$user['id']; $_SESSION['username'] = $user['username'];
            header('Location: index.php', true, 303); exit;
        }
        $error = 'Invalid username or password.';
    }
}
$a = random_int(2, 9); $b = random_int(1, 9); $_SESSION['captcha_answer'] = $a + $b;
render('login.html.twig', ['error' => $error, 'captcha_enabled' => $cfg['captcha'], 'captcha_question' => "$a + $b = ?", 'allow_registration' => $cfg['allow_registration']]);
