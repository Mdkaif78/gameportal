<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
try {
    $q = trim((string)($_GET['q'] ?? ''));
    $year = trim((string)($_GET['year'] ?? ''));
    $minRating = trim((string)($_GET['min_rating'] ?? ''));
    if (mb_strlen($q) > 120) throw new InvalidArgumentException();
    if ($year !== '' && (!ctype_digit($year) || (int)$year < 1950 || (int)$year > 2100)) throw new InvalidArgumentException();
    if ($minRating !== '' && (!ctype_digit($minRating) || (int)$minRating > 100)) throw new InvalidArgumentException();
    $sql = 'SELECT game_id, game_name, LEFT(game_description, 180) AS short_description, released_date, rating FROM videogames WHERE 1=1';
    $types = ''; $params = [];
    if ($q !== '') { $sql .= ' AND game_name LIKE ?'; $types .= 's'; $params[] = '%' . $q . '%'; }
    if ($year !== '') { $sql .= ' AND released_date >= ? AND released_date < ?'; $types .= 'ss'; $params[] = $year . '-01-01'; $params[] = ((int)$year + 1) . '-01-01'; }
    if ($minRating !== '') { $sql .= ' AND rating >= ?'; $types .= 'i'; $params[] = (int)$minRating; }
    $sql .= ' ORDER BY released_date DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (InvalidArgumentException $e) {
    http_response_code(400); echo json_encode(['error' => 'Invalid search filter.']);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => 'Search is temporarily unavailable.']);
}
