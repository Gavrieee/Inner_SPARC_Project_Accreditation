<?php

require_once 'core/dbConfig.php';
// require_once 'core/models.php';

// Step 2: Fetch and build data structure
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
$dataTeamPerMonth = getToggleDataByTeamPerMonth($pdo);
$monthsIndex = [
    1 => "January",
    2 => "February",
    3 => "March",
    4 => "April",
    5 => "May",
    6 => "June",
    7 => "July",
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];
?>

<!-- Step 4: Display HTML Table -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Toggle Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>

</head>

<body class="p-6 bg-gray-100 ">




    <?php foreach ($dataTeamPerMonth as $toggle => $team_s): ?>


        <h2 class="text-xl font-bold mb-2">Toggle: <?= htmlspecialchars($toggle) ?></h2>
        <div class="w-full bg-white overflow-x-auto text-gray-700 hide-scrollbar rounded-xl">
            <div class="flex gap-4 min-w-max">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <div class="w-[130px] ">
                        <div class="bg-gray-50 border rounded-xl p-2 text-center flex flex-col gap-4">
                            <div class="font-bold"><?= $monthsIndex[$m] ?></div>

                            <?php
                            $monthTotal = 0;
                            foreach ($team_s as $team => $count_s):
                                $count = $count_s[$m] ?? 0;
                                $monthTotal += $count;
                                ?>
                                <p class=""><?= $count ?></p>
                            <?php endforeach; ?>
                        </div>


                        <div class="font-semibold border rounded-xl p-2 mt-4 text-center bg-white flex flex-col gap-4">
                            <?= $monthTotal ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endforeach; ?>










    <h2 class="text-xl font-bold mb-2">Toggle: <?= htmlspecialchars($toggle) ?></h2>
    <div class="overflow-auto">
        <table class="table-auto border-collapse border border-gray-400 mb-8 w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-400 p-2">Month</th>
                    <?php foreach (array_keys($team_s) as $team): ?>
                        <th class="border border-gray-400 p-2"><?= htmlspecialchars($team) ?></th>
                    <?php endforeach; ?>
                    <th class="border border-gray-400 p-2">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <tr class="text-center">
                        <td class="border border-gray-400 p-2 font-bold"><?= $monthsIndex[$m] ?></td>
                        <?php
                        $monthTotal = 0;
                        foreach ($team_s as $team => $count_s):
                            $count = $count_s[$m] ?? 0;
                            $monthTotal += $count;
                            ?>
                            <td class="border border-gray-400 p-2"><?= $count ?></td>
                        <?php endforeach; ?>
                        <td class="border border-gray-400 p-2 font-bold"><?= $monthTotal ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

</body>

</html>