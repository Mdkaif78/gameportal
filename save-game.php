<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
[$values, $errors] = valid_game_input($_POST);
$rawId = trim((string)($_POST['id'] ?? ''));
$id = $rawId === '' ? 0 : filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) $errors[] = 'Invalid game id.';
if ($errors) {
    $game = $id ? ['game_id' => $id] : null;
    render('game-form.html.twig', ['game' => $game, 'values' => $values, 'errors' => $errors]); exit;
}
$mysqli = db();
if ($id > 0) {
    $stmt = $mysqli->prepare('UPDATE videogames SET game_name=?, game_description=?, released_date=?, rating=? WHERE game_id=?');
    $stmt->bind_param('sssii', $values['game_name'], $values['game_description'], $values['released_date'], $values['rating'], $id);
} else {
    $stmt = $mysqli->prepare('INSERT INTO videogames (game_name, game_description, released_date, rating) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $values['game_name'], $values['game_description'], $values['released_date'], $values['rating']);
}
$stmt->execute();
flash($id > 0 ? 'Game updated.' : 'Game added.');
header('Location: ' . ($id > 0 ? 'game.php?id=' . $id : 'index.php'), true, 303); exit;
