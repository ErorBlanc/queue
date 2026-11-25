<?php

function getPlayers(mysqli $mysqli, ?string $status = null): array
{
    if ($status === null) {
        return $mysqli->query(
            'SELECT id, name, status FROM players ORDER BY id'
        )->fetch_all(MYSQLI_ASSOC);
    }

    $stmt = $mysqli->prepare(
        'SELECT id, name, status
         FROM players
         WHERE status = ?
         ORDER BY id'
    );
    $stmt->bind_param('s', $status);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function joinPlayer(mysqli $mysqli, string $name): array
{
    $mysqli->begin_transaction();

    try {
        $world = $mysqli->query(
            'SELECT capacity FROM world WHERE id = 1 FOR UPDATE'
        )->fetch_assoc();

        if ($world === null) {
            throw new RuntimeException('Игровой мир не найден');
        }

        $capacity = (int) $world['capacity'];

        $activeCount = (int) $mysqli->query(
            "SELECT COUNT(*) FROM players WHERE status = 'active'"
        )->fetch_row()[0];

        $freeSlots = max(0, $capacity - $activeCount);

        // Если место свободно сначала пропускаем ожидающих по порядку ID
        if ($freeSlots > 0) {
            $mysqli->query(
                "UPDATE players
                 SET status = 'active'
                 WHERE status = 'waiting'
                 ORDER BY id
                 LIMIT $freeSlots"
            );

            $freeSlots -= (int) $mysqli->affected_rows;
        }

        // Новый игрок получает только оставшееся после очереди место
        $status = $freeSlots > 0 ? 'active' : 'waiting';

        $stmt = $mysqli->prepare(
            'INSERT INTO players (name, status) VALUES (?, ?)'
        );
        $stmt->bind_param('ss', $name, $status);
        $stmt->execute();

        $id = (int) $mysqli->insert_id;
        $mysqli->commit();

        return [
            'id' => $id,
            'name' => $name,
            'status' => $status,
        ];
    } catch (Throwable $error) {
        $mysqli->rollback();
        throw $error;
    }
}

function leavePlayer(mysqli $mysqli, int $id): ?array
{
    $mysqli->begin_transaction();

    try {
        $world = $mysqli->query(
            'SELECT id FROM world WHERE id = 1 FOR UPDATE'
        )->fetch_assoc();

        if ($world === null) {
            throw new RuntimeException('Игровой мир не найден');
        }

        $stmt = $mysqli->prepare(
            'SELECT id, name, status FROM players WHERE id = ? FOR UPDATE'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $player = $stmt->get_result()->fetch_assoc();

        if ($player === null) {
            $mysqli->commit();
            return null;
        }

        if ($player['status'] !== 'offline') {
            $wasActive = $player['status'] === 'active';

            $update = $mysqli->prepare(
                "UPDATE players SET status = 'offline' WHERE id = ?"
            );
            $update->bind_param('i', $id);
            $update->execute();

            if ($wasActive) {
                $mysqli->query(
                    "UPDATE players
                     SET status = 'active'
                     WHERE status = 'waiting'
                     ORDER BY id
                     LIMIT 1"
                );
            }

            $player['status'] = 'offline';
        }

        $mysqli->commit();

        return [
            'id' => (int) $player['id'],
            'name' => $player['name'],
            'status' => $player['status'],
        ];
    } catch (Throwable $error) {
        $mysqli->rollback();
        throw $error;
    }
}