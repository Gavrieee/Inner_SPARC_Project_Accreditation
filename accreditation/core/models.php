<?php

require_once 'dbConfig.php';

// function insertManualData(PDO $pdo, $datetime = '', $name, $team, $toggle)
// {
//     try {
//         if (!empty($datetime)) {
//             $stmt = $pdo->prepare("INSERT INTO manual_data (datetime, name, team, toggle) VALUES (?, ?, ?, ?)");
//             $stmt->execute([$datetime, $name, $team, $toggle]);
//         } else {
//             $stmt = $pdo->prepare("INSERT INTO manual_data (datetime, name, team, toggle) VALUES (NOW(), ?, ?, ?)");
//             $stmt->execute([$name, $team, $toggle]);
//         }

//         return true; // success
//     } catch (PDOException $e) {
//         error_log("Insert error: " . $e->getMessage());
//         return false; // fail
//     }
// }


// function mergeGoogleSheetToDB(PDO $pdo, string $url): void
// {
//     // Get CSV data from Google Sheets
//     $csvData = file_get_contents($url);
//     if ($csvData === false)
//         return;

//     $rows = array_map('str_getcsv', explode("\n", $csvData));
//     if (count($rows) <= 1)
//         return;

//     // Prepare statement to avoid duplicates
//     $stmt = $pdo->prepare("
//         INSERT INTO manual_data (datetime, name, team, toggle)
//         SELECT ?, ?, ?, ?
//         FROM DUAL
//         WHERE NOT EXISTS (
//             SELECT 1 FROM manual_data
//             WHERE datetime = ? AND name = ? AND team = ? AND toggle = ?
//         )
//     ");

//     for ($i = 1; $i < count($rows); $i++) {
//         $row = $rows[$i];

//         // Skip empty or malformed rows
//         if (!isset($row[0], $row[1], $row[2]))
//             continue;

//         $datetimeRaw = trim($row[0]);

//         // Convert Sheet format '1/29/2025 17:02:54' → MySQL format '2025-01-29 17:02:54'
//         // $datetimeObj = DateTime::createFromFormat('n/j/Y H:i:s', $datetimeRaw);
//         $datetimeObj = DateTime::createFromFormat('n/j/Y h:i:s A', $datetimeRaw);

//         if (!$datetimeObj)
//             continue;

//         $datetime = $datetimeObj->format('Y-m-d H:i:s');
//         $name = trim($row[1] ?? '');
//         $team = trim($row[2] ?? '');
//         $toggle = trim($row[3] ?? '');

//         if (empty($datetime) || empty($team))
//             continue;

//         // Execute prepared insert with duplication check
//         $stmt->execute([
//             $datetime,
//             $name,
//             $team,
//             $toggle,  // for insert
//             $datetime,
//             $name,
//             $team,
//             $toggle   // for duplication check
//         ]);
//     }
// }

// function mergeGoogleSheetToDB(PDO $pdo, string $url): void
// {
//     if (($handle = fopen($url, 'r')) === false) {
//         die("Failed to open Google Sheets CSV.");
//     }

//     fgetcsv($handle); // Skip header

//     while (($row = fgetcsv($handle)) !== false) {
//         $datetimeStr = $row[0] ?? '';
//         $name = trim($row[1] ?? '');
//         $team = trim($row[2] ?? '');
//         $toggle = trim($row[3] ?? '');

//         // Try to parse the datetime string in the sheet format
//         $date = DateTime::createFromFormat('n/j/Y H:i:s', $datetimeStr);
//         if (!$date)
//             continue;

//         $datetime = $date->format('Y-m-d H:i:s'); // Convert to DB format

//         // Avoid duplicate inserts
//         $stmt = $pdo->prepare("SELECT COUNT(*) FROM manual_data WHERE datetime = ? AND team = ?");
//         $stmt->execute([$datetime, $team]);
//         if ($stmt->fetchColumn() > 0)
//             continue;

//         // Insert with name even if not shown in frontend
//         $insert = $pdo->prepare("INSERT INTO manual_data (datetime, name, team, toggle) VALUES (?, ?, ?, ?)");
//         $insert->execute([$datetime, $name, $team, $toggle]);
//     }

//     fclose($handle);
// }

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