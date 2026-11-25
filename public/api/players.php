<?php
require_once '/var/www/db/db.php';
require_once '/var/www/db/player_model.php';

header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {

   case 'GET':
    $status = $_GET['status'] ?? null;
    $allowedStatuses = ['active', 'waiting', 'offline'];

    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(
            ['error' => 'Неизвестный статус. Допустимы: active, waiting, offline'],
            JSON_UNESCAPED_UNICODE
        );
        break;
    }

    $players = getPlayers($mysqli, $status);

    echo json_encode($players, JSON_UNESCAPED_UNICODE);
    break;

    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? null;

        if ($name === null) {
            http_response_code(400);
            echo json_encode(
                ['error' => 'Имя игрока не указано'],
                JSON_UNESCAPED_UNICODE
            );
            break;
        }
        $player = joinPlayer($mysqli, trim($name));
        http_response_code(201);
        echo json_encode($player, JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === null || $id === false) {
            http_response_code(400);
            echo json_encode(
                ['error' => 'Укажите корректный id игрока'],
                JSON_UNESCAPED_UNICODE
            );
            break;
        }

        $player = leavePlayer($mysqli, $id);

        if ($player === null) {
            http_response_code(404);
            echo json_encode(
                ['error' => 'Игрок не найден'],
                JSON_UNESCAPED_UNICODE
            );
            break;
        }

        echo json_encode($player, JSON_UNESCAPED_UNICODE);
        break;
          
        default:
            http_response_code(405);
            header('Allow: GET, POST, PATCH, DELETE');
            echo json_encode(
                ['error' => 'Метод не поддерживается'],
                JSON_UNESCAPED_UNICODE
        );
            break;
}