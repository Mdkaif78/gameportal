<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Invalid game id.');
}
$stmt = db()->prepare('DELETE FROM videogames WHERE game_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
flash('Game deleted.');
header('Location: index.php', true, 303);
exit;
