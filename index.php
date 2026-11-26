<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
try {
    $result = db()->query('SELECT game_id, game_name, game_description, released_date, rating FROM videogames ORDER BY released_date DESC LIMIT 200');
    render('home.html.twig', ['games' => $result->fetch_all(MYSQLI_ASSOC)]);
} catch (Throwable $e) {
    http_response_code(500);
    render('error.html.twig', ['message' => 'The catalogue is not ready. Follow README.md to configure MySQL and import schema.sql.']);
}
