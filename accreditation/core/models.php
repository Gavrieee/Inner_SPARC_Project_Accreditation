<?php

require_once 'dbConfig.php';

function mergeGoogleSheetToDB($pdo, $url)
{
    if (($handle = fopen($url, 'r')) === false)
        return;

    fgetcsv($handle); // skip header

    $stmt = $pdo->prepare("
        INSERT INTO manual_data (datetime, name, team, toggle)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE toggle = VALUES(toggle)
    ");

    while (($row = fgetcsv($handle)) !== false) {
        $datetimeStr = $row[0] ?? '';
        $name = trim($row[1] ?? '');
        $team = trim($row[2] ?? '');
        $toggle = trim($row[3] ?? '');

        $date = DateTime::createFromFormat('n/j/Y H:i:s', $datetimeStr);
        if (!$date)
            continue;

        $datetime = $date->format('Y-m-d H:i:s');

        $stmt->execute([$datetime, $name, $team, $toggle]);
    }

    fclose($handle);
}

function getToggleDataByTeamAndMonth(PDO $pdo)
{
    $query = "
        SELECT 
            team,
            MONTH(datetime) AS month,
            toggle,
            COUNT(*) AS count
        FROM manual_data
        GROUP BY team, month, toggle
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $toggle = $row['toggle'];
        $team = $row['team'];
        $month = (int) $row['month']; // 1 to 12
        $count = (int) $row['count'];

        // Initialize structure if not set
        if (!isset($data[$toggle][$team])) {
            $data[$toggle][$team] = array_fill(1, 12, 0);
        }

        $data[$toggle][$team][$month] = $count;
    }

    return $data;
}

function getToggleDataByTeamPerMonth(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT 
            toggle,
            team,
            MONTH(datetime) AS month,
            COUNT(*) AS count
        FROM manual_data
        GROUP BY toggle, team, MONTH(datetime)
        ORDER BY toggle, team, MONTH(datetime)
    ");

    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $toggle = $row['toggle'];
        $team = $row['team'];
        $month = (int) $row['month'];
        $count = (int) $row['count'];

        // Initialize team if not set
        if (!isset($data[$toggle][$team])) {
            $data[$toggle][$team] = array_fill(1, 12, 0);
        }

        $data[$toggle][$team][$month] = $count;
    }

    return $data;
}