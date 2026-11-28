<?php

function getPlayers(mysqli $mysqli, ?string $status = null): array
{
    $sql = "SELECT id, name, status, queue_order,
                   CASE
                       WHEN status = 'waiting'
                       THEN ROW_NUMBER() OVER (
                           PARTITION BY status ORDER BY queue_order
                       )
                       ELSE NULL
                   END AS queue_position
            FROM players";

    if ($status === null) {
        return $mysqli->query($sql . ' ORDER BY id')
            ->fetch_all(MYSQLI_ASSOC);
    }

    $orderBy = $status === 'waiting' ? 'queue_order' : 'id';
    $stmt = $mysqli->prepare($sql . " WHERE status = ? ORDER BY $orderBy");
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

        
        if ($freeSlots > 0) { // Если место свободно сначала пропускаем ожидающих по порядку queue_order
            $mysqli->query(
                "UPDATE players
                 SET status = 'active'
                 WHERE status = 'waiting'
                 ORDER BY queue_order
                 LIMIT $freeSlots"
            );

            $freeSlots -= (int) $mysqli->affected_rows;
        }

        
        $status = $freeSlots > 0 ? 'active' : 'waiting'; // Новый игрок получает только оставшееся после очереди место

        $queueOrder = (int) $mysqli->query( //номер заявки 
            'SELECT COALESCE(MAX(queue_order), 0) + 1 FROM players'
        )->fetch_row()[0];

        $stmt = $mysqli->prepare(
            'INSERT INTO players (name, status, queue_order) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('ssi', $name, $status, $queueOrder);
        $stmt->execute();

        $id = (int) $mysqli->insert_id;
        $mysqli->commit();

        return [
            'id' => $id,
            'name' => $name,
            'status' => $status,
            'queue_order' => $queueOrder,
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
                     ORDER BY queue_order
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

    function rejoinPlayer(mysqli $mysqli, int $id): ?array
{
    $mysqli->begin_transaction();

    try {
        $world = $mysqli->query(
            'SELECT capacity FROM world WHERE id = 1 FOR UPDATE'
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
            throw new DomainException('Игрок уже находится в мире или очереди');
        }

        $activeCount = (int) $mysqli->query(
            "SELECT COUNT(*) FROM players WHERE status = 'active'"
        )->fetch_row()[0];

        $freeSlots = max(0, (int) $world['capacity'] - $activeCount);

        if ($freeSlots > 0) {
            $mysqli->query(
                "UPDATE players
                 SET status = 'active'
                 WHERE status = 'waiting'
                 ORDER BY queue_order
                 LIMIT $freeSlots"
            );

            $freeSlots -= (int) $mysqli->affected_rows;
        }

        $status = $freeSlots > 0 ? 'active' : 'waiting';

        $queueOrder = (int) $mysqli->query(
            'SELECT COALESCE(MAX(queue_order), 0) + 1 FROM players'
        )->fetch_row()[0];

        $update = $mysqli->prepare(
            'UPDATE players SET status = ?, queue_order = ? WHERE id = ?'
        );
        $update->bind_param('sii', $status, $queueOrder, $id);
        $update->execute();

        $mysqli->commit();

        return [
            'id' => (int) $player['id'],
            'name' => $player['name'],
            'status' => $status,
            'queue_order' => $queueOrder,
        ];
    } catch (Throwable $error) {
        $mysqli->rollback();
        throw $error;
    }
}