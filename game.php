<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(404); render('error.html.twig', ['message' => 'Game not found.']); exit; }
$stmt = db()->prepare('SELECT game_id, game_name, game_description, released_date, rating FROM videogames WHERE game_id = ?');
$stmt->bind_param('i', $id); $stmt->execute(); $game = $stmt->get_result()->fetch_assoc();
if (!$game) { http_response_code(404); render('error.html.twig', ['message' => 'Game not found.']); exit; }
render('detail.html.twig', ['game' => $game]);
