<?php
require_once '/var/www/db/db.php';
header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
   case 'GET':
    $status = $_GET['status'] ?? null;
    $allowedStatuses = ['active', 'waiting'];

    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(
            ['error' => 'Неизвестный статус. Допустимы: active, waiting'],
            JSON_UNESCAPED_UNICODE
        );
        break;
    }

    if ($status !== null) {
        $stmt = $mysqli->prepare(
            'SELECT id, name, status, is_bot
             FROM players
             WHERE status = ?
             ORDER BY id'
        );
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query(
            'SELECT id, name, status, is_bot FROM players ORDER BY id'
        );
    }

    $players = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($players, JSON_UNESCAPED_UNICODE);
    break;
}