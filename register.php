<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
start_secure_session();
if (!app_config()['allow_registration']) { http_response_code(404); exit('Registration is disabled.'); }
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? '')); $email = trim((string)($_POST['email'] ?? '')); $password = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) $error = 'Username must be 3–50 letters, numbers, dots, dashes, or underscores.';
    elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
    elseif (strlen($password) < 12 || strlen($password) > 4096) $error = 'Password must be at least 12 characters.';
    else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $email, $hash); $stmt->execute();
            flash('Account created. Please log in.'); header('Location: login.php', true, 303); exit;
        } catch (mysqli_sql_exception $e) { $error = $e->getCode() === 1062 ? 'That username or email is already in use.' : 'Account could not be created.'; }
    }
}
render('register.html.twig', ['error' => $error]);
