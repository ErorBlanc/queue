<?php
require_once '/var/www/db/db.php';
header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $result = $mysqli->query(
            'SELECT id, name, capacity FROM world'
        );
        $world = $result->fetch_assoc();
        echo json_encode($world, JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
}
