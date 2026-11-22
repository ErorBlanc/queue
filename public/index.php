<?php
header('Content-Type: text/html; charset=utf-8');

$db = new mysqli('db', 'appuser', 'apppassword', 'appDB');
$db->set_charset('utf8mb4');

$world = $db->query(
    'SELECT name, capacity FROM world'
)->fetch_assoc();

$players = $db->query(
    'SELECT id, name, status, is_bot FROM players ORDER BY id'
)->fetch_all(MYSQLI_ASSOC);

$occupied = 0; #игрок активный

foreach ($players as $player) {
    if ($player['status'] === 'active') {
        $occupied++;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Очередь в игровой мир</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Очередь в игровой мир</h1>

    <?php if ($world === null): ?>
        <p>Мир не найден в базе данных.</p>
    <?php else: ?>
        <h2><?= htmlspecialchars($world['name'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>
            Занято мест: <?= $occupied ?> /
            <?= (int) $world['capacity'] ?>
        </p>
    <?php endif; ?>

    <div class="tables-grid">
 <section>

     <h2>Участники в мире</h2>
     <table border="1">
         <thead>
             <tr>
                 <th>ID</th>
                 <th>Имя</th>
                 <th>Статус</th>
                 <th>Тип</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $player): ?>
                    <?php if (($player['status'] ?? '') === 'active'): ?>
                        <tr>
                            <td><?= (int) $player['id'] ?></td>
                            <td><?= htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($player['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $player['is_bot'] === 1 ? 'Бот' : 'Игрок' ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
             <section>
            
                 <h2>Участники в очереди</h2>
                 <table border="1">
                     <thead>
                         <tr>
                             <th>ID</th>
                             <th>Имя</th>
                             <th>Статус</th>
                             <th>Тип</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <?php if (($player['status'] ?? '') === 'waiting'): ?>
                                    <tr>
                                        <td><?= (int) $player['id'] ?></td>
                                        <td><?= htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($player['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $player['is_bot'] === 1 ? 'Бот' : 'Игрок' ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                 <section>

                     <h2> Все участники</h2>
                     
                     <table border="1">
                         <thead>
                             <tr>
                                 <th>ID</th>
                                 <th>Имя</th>
                                 <th>Статус</th>
                                 <th>Тип</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                    <tr>
                                        <td><?= (int) $player['id'] ?></td>
                                        <td><?= htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($player['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $player['is_bot'] === 1 ? 'Бот' : 'Игрок' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>    
                        </section>
                        </div>
                    </body>
                    </html>
