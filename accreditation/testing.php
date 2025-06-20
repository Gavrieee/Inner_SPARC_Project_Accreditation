<?php
require_once 'core/dbConfig.php';
require_once 'core/models.php';

$sheetId = '1jAxVlt4_tM1s2db22RbBobyxi6Wqla2kHb6UTRZDEJA';
$gid = '1410707098';
$url = "https://docs.google.com/spreadsheets/d/$sheetId/gviz/tq?tqx=out:csv&gid=$gid";

// USE THIS TO RESET INDEX
// TRUNCATE TABLE manual_data;

// Fetch all data from database
$stmt = $pdo->query("SELECT * FROM manual_data ORDER BY datetime ASC");
$dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for grouped view
$data = [];

foreach ($dbResults as $row) {
    $datetimeStr = $row['datetime'];
    $team = trim($row['team']);
    $toggle = trim($row['toggle']);

    if (!$datetimeStr || !$team)
        continue;

    $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetimeStr);
    if (!$date)
        continue;

    $month = $date->format('F');
    $day = (int) $date->format('j');

    $quarter = match (true) {
        $day <= 7 => 'Week 1',
        $day <= 14 => 'Week 2',
        $day <= 21 => 'Week 3',
        default => 'Week 4',
    };

    if (!isset($data[$month][$team])) {
        $data[$month][$team] = [
            'Week 1' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Week 2' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Week 3' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Week 4' => ['1' => 0, '0' => 0, 'blank' => 0],
        ];
    }

    $key = ($toggle === '1' || $toggle === '0') ? $toggle : 'blank';
    $data[$month][$team][$quarter][$key]++;
}

$svg = [
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="size-6 text-green-500">
                                                    <path fill-rule="evenodd"
                                                        d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                                        clip-rule="evenodd" />
                                                </svg>',
    'x_mark' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="size-6 text-red-500">
                                                    <path fill-rule="evenodd"
                                                        d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                                                        clip-rule="evenodd" />',
    'signal_lost' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-yellow-500">
  <path fill-rule="evenodd" d="M2.47 2.47a.75.75 0 0 1 1.06 0l8.407 8.407a1.125 1.125 0 0 1 1.186 1.186l1.462 1.461a3.001 3.001 0 0 0-.464-3.645.75.75 0 1 1 1.061-1.061 4.501 4.501 0 0 1 .486 5.79l1.072 1.072a6.001 6.001 0 0 0-.497-7.923.75.75 0 0 1 1.06-1.06 7.501 7.501 0 0 1 .505 10.05l1.064 1.065a9 9 0 0 0-.508-12.176.75.75 0 0 1 1.06-1.06c3.923 3.922 4.093 10.175.512 14.3l1.594 1.594a.75.75 0 1 1-1.06 1.06l-2.106-2.105-2.121-2.122h-.001l-4.705-4.706a.747.747 0 0 1-.127-.126L2.47 3.53a.75.75 0 0 1 0-1.061Zm1.189 4.422a.75.75 0 0 1 .326 1.01 9.004 9.004 0 0 0 1.651 10.462.75.75 0 1 1-1.06 1.06C1.27 16.12.63 11.165 2.648 7.219a.75.75 0 0 1 1.01-.326ZM5.84 9.134a.75.75 0 0 1 .472.95 6 6 0 0 0 1.444 6.159.75.75 0 0 1-1.06 1.06A7.5 7.5 0 0 1 4.89 9.606a.75.75 0 0 1 .95-.472Zm2.341 2.653a.75.75 0 0 1 .848.638c.088.62.37 1.218.849 1.696a.75.75 0 0 1-1.061 1.061 4.483 4.483 0 0 1-1.273-2.546.75.75 0 0 1 .637-.848Z" clip-rule="evenodd" />
</svg>
'
];

$default_blue_number = '800';
$hover_blue_number = $default_blue_number + 100;

$dataToggleTeamMonth = getToggleDataByTeamAndMonth($pdo);

$months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec'
];

// Optional: labels for toggles
$toggleLabels = [
    '1' => '<div class="flex pt-2 items-center gap-2 text-green-600 font-semibold">'
        . $svg['check'] . '<span>Finished Accreditation</span></div>',

    '0' => '<div class="flex pt-2 items-center gap-2 text-red-600 font-semibold">'
        . $svg['x_mark'] . '<span>Cancelled Accreditation</span></div>',

    '' => '<div class="flex pt-2 items-center gap-2 text-yellow-600 font-semibold">'
        . $svg['signal_lost'] . '<span>No Response from Agent</span></div>',
];



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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Toggle Tracker</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function filterByMonth(month) {
            document.querySelectorAll('.month-group').forEach(el => {
                el.classList.add('hidden');
            });
            document.getElementById(`month-${month}`).classList.remove('hidden');
        }
    </script>
</head>

<body class="bg-gray-100 text-gray-800 py-6 px-[20%]">

    <!-- ENCODING form -->
    <section class="bg-white rounded-2xl shadow p-6 mb-6 mx-[20%] min-w-[30%]">
        <div class="flex justify-center items-center bg-blue-1001 pt-4">

            <div class="bg-red-3001 w-full px-10 h-fit flex justify-center items-center">
                <form method="post" action="core/handleForms.php" class="mb-6">

                    <!-- FIRST DIV -->
                    <div class="text-center">
                        <h1 class="font-bold text-xl">Accreditation </h1>
                    </div>

                    <!-- LINE -->
                    <hr class="h-px my-4 bg-gray-200 border-0 dark:bg-blue-<?= $default_blue_number; ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <!-- SECOND DIV -->
                        <input type="text" name="name" placeholder="Full Name"
                            class="pl-2 w-full border-b border-1.5 p-[4px] border-blue-<?= $default_blue_number; ?> focus:outline-none focus:border-blue-<?= $default_blue_number; ?> bg-transparent"
                            required>

                        <select name="team"
                            class="w-full p-[4px] rounded-md pl-2 border border-1 border-blue-<?= $default_blue_number; ?> focus:outline-none1 focus:outline- focus:border-blue-<?= $default_blue_number; ?> bg-transparent focus-within:outline-2 focus-within:outline-blue-<?= $default_blue_number; ?>"
                            required>
                            <?php
                            $printedTeams = [];
                            foreach ($data as $month => $teams):
                                foreach ($teams as $team => $quarters):
                                    if (in_array($team, $printedTeams))
                                        continue;
                                    $printedTeams[] = $team;
                                    ?>
                                    <option value="<?= htmlspecialchars($team) ?>"><?= htmlspecialchars($team) ?></option>
                                    <?php
                                endforeach;
                            endforeach;
                            ?>
                        </select>

                        <!-- THIRD DIV -->
                        <label for="datetime" class="block">
                            <input type="datetime-local" name="datetime"
                                class="w-full p-[4px] rounded-md pl-2 border border-1 border-blue-<?= $default_blue_number; ?> focus:outline-none1 focus:outline- focus:border-blue-<?= $default_blue_number; ?> bg-transparent focus-within:outline-2 focus-within:outline-blue-<?= $default_blue_number; ?> appearance-none">
                        </label>

                        <select name="toggle"
                            class="w-full p-[4px] rounded-md pl-2 border border-1 border-blue-<?= $default_blue_number; ?> focus:outline-none1 focus:outline- focus:border-blue-<?= $default_blue_number; ?> bg-transparent focus-within:outline-2 focus-within:outline-blue-<?= $default_blue_number; ?>">
                            <option value="1">✅ Finished</option>
                            <option value="0">❌ Cancelled</option>
                            <option value="">⬜ No Response</option>
                        </select>
                    </div>

                    <!-- FOURTH DIV -->
                    <button type="submit" name="insertData"
                        class="py-2 mt-4 bg-blue-<?= $default_blue_number; ?> w-full text-white rounded-lg hover:bg-blue-<?= $hover_blue_number; ?> hover:shadow-mg">Add
                        this
                        Entry
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center text-gray-700 gap-4 mb-4 py-3">
        <span class="text-lg font-bold">Team</span>
        <?php
        $printedTeams = [];
        foreach ($data as $month => $teams):
            foreach ($teams as $team => $quarters):
                if (in_array($team, $printedTeams))
                    continue;
                $printedTeams[] = $team;
                ?>
                <span
                    class="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg shadow-sm hover:-translate-y-1 transition-all ease-in-out"><?= htmlspecialchars($team) ?></span>
                <?php
            endforeach;
        endforeach;
        ?>
    </div>

    <label class="block mb-4">
        <div class="flex justify-between items-center gap-2 mb-2 text-md">
            <div>
                <span class="text-gray-700 font-bold">Filter by Month:
                    <select onchange="filterByMonth(this.value)"
                        class="font-bold mb-6 text-center text-blue-<?= $default_blue_number; ?> bg-transparent appearance-none1">
                        <?php foreach (array_keys($data) as $month): ?>
                            <option value="<?= $month ?>"><?= $month ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </div>

            <div>
                <form method="post" action="core/sync_sheets.php">
                    <button type="submit"
                        class="py-2 px-4 mt-4 border border-blue-<?= $default_blue_number; ?> text-blue-<?= $default_blue_number; ?> hover:bg-blue-<?= $default_blue_number; ?> w-full hover:text-white rounded-lg hover:bg-blue-<?= $default_blue_number; ?> hover:shadow-mg transition-all ease-in-out">Re-Sync
                        from Google
                        Sheet</button>
                </form>
            </div>
        </div>
    </label>

    <?php foreach ($data as $month => $teams): ?>
        <div id="month-<?= $month ?>"
            class="month-group <?= $month !== array_key_first($data) ? 'hidden' : '' ?> text-gray-700">

            <!-- display month -->
            <h2 class="text-[32px] font-bold mb-6 text-center text-blue-<?= $default_blue_number; ?>"><?= $month ?></h2>
            <div class="bg-white rounded-2xl shadow pt-2 pb-1 px-4 mb-6 ">
                <h3 class="text-xl font-semibold my-2">Legend</h3>
                <hr>
                <div class="flex flex-col justify-center items-center gap-4 my-4 md:flex-row sm:gap-4">
                    <div class="flex flex-col justify-center items-center font-semibold border rounded-xl p-2 py-4 text-center gap-1"
                        title="Finished Accreditation">
                        <?= $svg['check'] ?>
                        <span class="ml-2">Finished Accreditation</span>
                    </div>
                    <div class="flex flex-col justify-center items-center font-semibold border rounded-xl p-2 py-4 text-center gap-1"
                        title="Cancelled Accreditation">
                        <?= $svg['x_mark'] ?>
                        <span class="ml-2">Cancelled Accreditation</span>
                    </div>
                    <div class="flex flex-col justify-center items-center font-semibold border rounded-xl p-2 py-4 text-center gap-1"
                        title="No response from Agent">
                        <?php echo str_replace('bg-red-500', 'text-gray-500', $svg['signal_lost']);
                        ?>
                        <span class="ml-2">No response from Agent</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow pt-2 pb-1 px-4 mb-6">

                <?php foreach ($dataTeamPerMonth as $toggle => $team_s): ?>

                    <!-- Full-width Toggle label -->
                    <div class="mb-2">
                        <h2 class="text-xl font-bold">
                            <?= $toggleLabels[$toggle] ?? 'Unknown' ?>
                        </h2>
                        <hr class="h-px my-4 mt-2">
                    </div>


                    <!-- 3-column grid -->
                    <div class="grid grid-cols-[auto,3fr,auto] gap-4 mb-4">
                        <!-- Teams per toggle -->
                        <div class="">
                            <div class="bg-white border rounded-xl p-2 px-4 text-center flex flex-col gap-4 overflow-x-auto">
                                <div class="font-bold">Teams</div>
                                <?php foreach (array_keys($team_s) as $team): ?>
                                    <div class=""><?= htmlspecialchars($team) ?></div>
                                <?php endforeach; ?>
                            </div>

                            <div class="font-bold border rounded-xl p-2 mt-4 text-center bg-white flex flex-col gap-4">
                                Total
                            </div>
                        </div>

                        <!-- Month grid -->
                        <div class="w-full bg-white overflow-x-auto text-gray-700 hide-scrollbar rounded-xl">
                            <div class="flex gap-4 min-w-max">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <div class="w-[130px]">
                                        <div
                                            class="bg-gray-50 border rounded-xl p-2 text-center flex flex-col justify-evenly items-center gap-4">
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

                                        <div
                                            class="font-semibold border rounded-xl p-2 mt-4 text-center bg-white flex flex-col gap-4">
                                            <?= $monthTotal ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Grand total -->
                        <div class="text-white">
                            <div
                                class="bg-blue-<?= $default_blue_number; ?> border rounded-xl p-2 px-4 text-center flex flex-col gap-4 overflow-x-auto">
                                <div class="font-bold">Year Total</div>
                                <?php foreach ($team_s as $team => $count_s): ?>
                                    <div class=""><?= array_sum($count_s) ?></div>
                                <?php endforeach; ?>
                            </div>

                            <div
                                class="font-bold border rounded-xl p-2 mt-4 text-center bg-blue-<?= $default_blue_number; ?> flex flex-col gap-4">
                                <?= array_sum(array_map('array_sum', $team_s)) ?>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

            <?php foreach ($teams as $team => $quarters): ?>
                <div class="bg-white rounded-2xl shadow pt-2 pb-1 px-4 mb-6 ">

                    <!-- display team -->
                    <h3 class="text-xl font-semibold my-2"><?= htmlspecialchars($team) ?></h3>
                    <hr>
                    <div class="grid grid-cols-[80px_repeat(5,_1fr)] gap-4 py-4 hidden md:grid">
                        <div class="border rounded-xl p-2 text-center flex flex-col gap-4">
                            <div class="font-bold">Marks</div>
                            <div class="flex justify-center items-center gap-2">
                                <button class="flex justify-center items-center font-bold" title="Finished Accreditation">
                                    <?= $svg['check'] ?>
                                </button>
                            </div>
                            <div class="flex justify-center items-center gap-2">
                                <button class="flex justify-center items-center font-bold" title="Cancelled Accreditation">
                                    <?= $svg['x_mark'] ?>
                                </button>
                            </div>
                            <div class="flex justify-center items-center gap-2">
                                <button class="flex justify-center items-center font-bold" title="No Response from Agent">
                                    <?= $svg['signal_lost'] ?>
                                </button>
                            </div>
                        </div>

                        <?php foreach (['Week 1', 'Week 2', 'Week 3', 'Week 4'] as $q): ?>
                            <div class="border rounded-xl p-2 text-center bg-gray-50 flex flex-col gap-4 ">
                                <div class="font-bold"><?= $q ?></div>

                                <div class=""><?= $quarters[$q]['1'] ?? 0 ?></div>
                                <div class=""><?= $quarters[$q]['0'] ?? 0 ?></div>
                                <div class=""><?= $quarters[$q]['blank'] ?? 0 ?></div>
                            </div>
                        <?php endforeach; ?>

                        <!-- this will show TOTAL per marks -->
                        <div
                            class="rounded-xl py-2 text-center flex w-full h-full flex-col gap-4 bg-blue-<?= $default_blue_number; ?> text-white">
                            <div class="font-bold">Month Total</div>

                            <?php
                            $totals_by_quarter = [
                                'Week 1' => 0,
                                'Week 2' => 0,
                                'Week 3' => 0,
                                'Week 4' => 0,
                            ];

                            $total_check = 0;
                            $total_x = 0;
                            $total_blank = 0;

                            foreach ($quarters as $quarter_name => $counts) {
                                $total_check += $counts['1'] ?? 0;
                                $total_x += $counts['0'] ?? 0;
                                $total_blank += $counts['blank'] ?? 0;

                                $totals_by_quarter[$quarter_name] += ($counts['1'] ?? 0) + ($counts['0'] ?? 0) + ($counts['blank'] ?? 0);
                            }

                            // Grand total (sum of all marks)
                            $grand_total = $total_check + $total_x + $total_blank;
                            ?>

                            <div class="hover:bg-white group">
                                <span
                                    class="font-semibold flex justify-center items-center group-hover:text-blue-<?= $default_blue_number; ?>">
                                    <?= $total_check ?>
                                </span>
                            </div>
                            <div class="hover:bg-white group">
                                <span
                                    class="font-semibold flex justify-center items-center group-hover:text-blue-<?= $default_blue_number; ?>">
                                    <?= $total_x ?>
                                </span>
                            </div>
                            <div class="hover:bg-white group">
                                <span
                                    class="font-semibold flex justify-center items-center group-hover:text-blue-<?= $default_blue_number; ?>">
                                    <?= $total_blank ?>
                                </span>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="border rounded-xl p-2 text-center flex flex-col gap-4">
                            <div class="font-bold">Total</div>
                        </div>
                        <div class="border font-semibold rounded-xl p-2 text-center">
                            <?= $totals_by_quarter['Week 1'] ?>
                        </div>
                        <div class="border font-semibold rounded-xl p-2 text-center">
                            <?= $totals_by_quarter['Week 2'] ?>
                        </div>
                        <div class="border font-semibold rounded-xl p-2 text-center">
                            <?= $totals_by_quarter['Week 3'] ?>
                        </div>
                        <div class="border font-semibold rounded-xl p-2 text-center">
                            <?= $totals_by_quarter['Week 4'] ?>
                        </div>

                        <div
                            class="border rounded-xl p-2 text-center flex flex-col gap-4 bg-blue-<?= $default_blue_number; ?> text-white font-semibold">
                            <?= $grand_total ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</body>

</html>