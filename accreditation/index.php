<?php

require_once 'core/dbConfig.php';
require_once 'core/models.php';

$months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];

// $teams = [
//     'Marketing' => ['Q1' => [], 'Q2' => [], 'Q3' => [], 'Q4' => []],
//     'Sales' => ['Q1' => [], 'Q2' => [], 'Q3' => [], 'Q4' => []],
//     'Tech' => ['Q1' => [], 'Q2' => [], 'Q3' => [], 'Q4' => []]
// ];

$sheetId = '1jAxVlt4_tM1s2db22RbBobyxi6Wqla2kHb6UTRZDEJA';
$gid = '1410707098';
$url = "https://docs.google.com/spreadsheets/d/$sheetId/gviz/tq?tqx=out:csv&gid=$gid";

// Optional: prevent merging again in the same session
session_start();
if (!isset($_SESSION['sheet_merged'])) {
    // mergeGoogleSheetToDB($pdo, $url);
    $_SESSION['sheet_merged'] = true;
}

// Now fetch merged data
$stmt = $pdo->query("SELECT * FROM manual_data ORDER BY datetime DESC");
$data = $stmt->fetchAll();


// UNCOMMENT IF YOU WANT TO USE THE CSV FILE DIRECTLY

if (($handle = fopen($url, 'r')) === false) {
    die("Cannot open Google Sheets CSV.");
}

// skip header
fgetcsv($handle);

$data = [];

while (($row = fgetcsv($handle)) !== false) {
    // excel columns
    $datetimeStr = $row[0] ?? '';
    $team = trim($row[2] ?? '');
    $toggle = trim($row[3] ?? '');

    if (!$datetimeStr || !$team)
        continue;

    $date = DateTime::createFromFormat('n/j/Y H:i:s', $datetimeStr);
    if (!$date)
        continue;

    $month = $date->format('F');
    $day = (int) $date->format('j');

    if ($day >= 1 && $day <= 7)
        $quarter = 'Q1';
    elseif ($day <= 14)
        $quarter = 'Q2';
    elseif ($day <= 21)
        $quarter = 'Q3';
    else
        $quarter = 'Q4';

    if (!isset($data[$month])) {
        $data[$month] = [];
    }

    if (!isset($data[$month][$team])) {
        $data[$month][$team] = [
            'Q1' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Q2' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Q3' => ['1' => 0, '0' => 0, 'blank' => 0],
            'Q4' => ['1' => 0, '0' => 0, 'blank' => 0],
        ];
    }

    if ($toggle === '1')
        $data[$month][$team][$quarter]['1']++;
    elseif ($toggle === '0')
        $data[$month][$team][$quarter]['0']++;
    else
        $data[$month][$team][$quarter]['blank']++;
}

$quarterTotals = [
    'Q1' => ['1' => 0, '0' => 0, 'blank' => 0],
    'Q2' => ['1' => 0, '0' => 0, 'blank' => 0],
    'Q3' => ['1' => 0, '0' => 0, 'blank' => 0],
    'Q4' => ['1' => 0, '0' => 0, 'blank' => 0],
];

// THIS TOO

fclose($handle);

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

?>

<!DOCTYPE html>
<html>

<head>
    <title>Month Filtered Quarter Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-4f flex flex-row">

    <!-- <nav title="DIV 1"
        class="bg-red-500 w-[20%] h-screen hidden sm:block transition-all duration-300 ease-in-out sm:transition-all">
        <button title="Small" onclick="toggleWidth()">Small</button>
    </nav> -->

    <main class="overflow-y-auto w-full h-screen flex1 items-center1 justify-center">

        <!-- STICKY nav -->
        <!-- <div title="DIV 3 Panel" class="w-[100%] h-auto bg-green-500 p-4 sticky top-0 z-50 flex justify-between">
            <input type="text">
            <h1>
                profile
            </h1>
        </div> -->

        <section class="min-w-[900px] py-6 mx-6">

            <!-- ENCODING form -->
            <form method="post" action="core/handleForms.php" class="mb-6">
                <label for="datetime" class="block mb-4">
                    <input type="datetime-local" name="datetime">
                    <span class="text italic text-gray-700">Optional</span>
                </label>

                <input type="text" name="name" placeholder="Full Name" class="pl-2" required>

                <select name="team" required>
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

                <select name="toggle">
                    <option value="1">✅ Present</option>
                    <option value="0">❌ Absent</option>
                    <option value="">⬜ Unknown</option>
                </select>

                <button type="submit" name="insertData">Add Entry</button>
            </form>

            <div class="mb-4">
                <select id="monthSelect"
                    class="p-2 bg-gray-50 border border-gray-200 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">Select Month</option>
                    <?php foreach ($data as $month => $_): ?>
                        <option value="<?= htmlspecialchars($month) ?>"><?= htmlspecialchars($month) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p class="py-4 text-gray-700">
                <strong>Note</strong>: Tables only show what data is available; <span class="font-semibold">months
                    without
                    data</span> and <span class="font-semibold">teams without
                    data per month</span> are <span class="font-semibold">not included</span>.
            </p>


            <div class="flex flex-wrap items-center text-gray-700 gap-4 mb-4">
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

            <?php foreach ($data as $month => $teams): ?>
                <?php
                // Will reset per-month quarter totals
                $quarterTotals = [
                    'Q1' => ['1' => 0, '0' => 0, 'blank' => 0],
                    'Q2' => ['1' => 0, '0' => 0, 'blank' => 0],
                    'Q3' => ['1' => 0, '0' => 0, 'blank' => 0],
                    'Q4' => ['1' => 0, '0' => 0, 'blank' => 0],
                ];
                ?>
                <div class="month-table py-6" id="month-<?= htmlspecialchars($month) ?>" style="">

                    <!-- Display month -->
                    <div class="flex justify-center bg-red-3001 py-4 text-blue-800">
                        <h2 class="text-[32px] font-bold"><?= htmlspecialchars($month) ?></h2>
                    </div>

                    <!-- <hr class="border-1.5 border-b border-black"> -->

                    <section class="px-[10%]">
                        <div
                            class="overflow-auto text-center hover:shadow-xl hover:-translate-y-1 transition-all ease-in-out">
                            <table class="min-w-full border-collapse bg-white shadow-xl rounded-xl p-4">
                                <thead class="bg-blue-3001">
                                    <tr>
                                        <th rowspan="2" class="px-4 py-6 text-2xl">Team</th>
                                        <?php foreach (["Q1", "Q2", "Q3", "Q4"] as $q): ?>
                                            <th colspan="3" class="p-2 border1 text-lg"><?= $q ?></th>
                                        <?php endforeach; ?>
                                        <!-- ✅ Move "Total (✅)" here with rowspan -->

                                        <th rowspan="2" class="px-4 py-6 rounded-tr-xl bg-blue-800 text-white text-2xl">
                                            Total
                                            <span class="flex justify-center items-center">
                                                <?= $svg['check'] ?>
                                            </span>
                                        </th>
                                    </tr>
                                    <tr>
                                        <?php for ($i = 0; $i < 4; $i++): ?>
                                            <th class="p-1 border1">
                                                <!-- check icon -->
                                                <span class="flex justify-center items-center">
                                                    <?= $svg['check'] ?>
                                                </span>
                                            </th>
                                            <th class="p-1 border1 ">
                                                <span class="flex justify-center items-center">
                                                    <?= $svg['x_mark'] ?>
                                                </span>

                                            </th>
                                            <th class="p-1 border1">
                                                <span class="flex justify-center items-center">
                                                    <?= $svg['signal_lost'] ?>
                                                </span>
                                            </th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($teams as $team => $quarters): ?>
                                        <tr class="border-t1">
                                            <!-- Will show all teams -->
                                            <td class="px-4 py-5 font-semibold"><?= htmlspecialchars($team) ?></td>
                                            <?php
                                            $total = 0;
                                            foreach (["Q1", "Q2", "Q3", "Q4"] as $q):
                                                $yes = $quarters[$q]['1'];
                                                $no = $quarters[$q]['0'];
                                                $blank = $quarters[$q]['blank'];

                                                // Accumulate only from filtered data
                                                $quarterTotals[$q]['1'] += $yes;
                                                $quarterTotals[$q]['0'] += $no;
                                                $quarterTotals[$q]['blank'] += $blank;

                                                $total += $yes;
                                                ?>
                                                <td class="px-4 py-2"><?= $yes ?></td>
                                                <td class="px-4 py-2"><?= $no ?></td>
                                                <td class="px-4 py-2"><?= $blank ?></td>
                                            <?php endforeach; ?>
                                            <td colspan="3" class="px-4 py-2 bg-blue-800 text-white font-bold text-xl">
                                                <?= $total ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Total per Quarter Row -->
                                    <tr class="font-bold h-6">
                                        <td class="text-white text-1xl rounded-bl-xl bg-blue-800 py-4 text-center">
                                            Total per
                                            Quarter</td>
                                        <?php foreach (["Q1", "Q2", "Q3", "Q4"] as $q): ?>

                                            <td class="text-white bg-blue-800"><?= $quarterTotals[$q]['1'] ?></td>
                                            <td class="text-white bg-blue-800"><?= $quarterTotals[$q]['0'] ?></td>
                                            <td class="text-white bg-blue-800"><?= $quarterTotals[$q]['blank'] ?></td>
                                        <?php endforeach; ?>
                                        <td colspan="3" class="px-4 py-2 text-white rounded-br-xl bg-blue-800">
                                            <?= array_sum(array_column($quarterTotals, '1')) ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>




                </div>
            <?php endforeach; ?>

        </section>

    </main>



    <script>
        document.getElementById('monthSelect').addEventListener('change', function () {
            const selected = this.value;
            document.querySelectorAll('.month-table').forEach(el => el.style.display = 'none');
            if (selected) {
                const table = document.getElementById('month-' + selected);
                if (table) table.style.display = 'block';
            }
        });
    </script>

    <script>
        function toggleWidth() {
            const div1 = document.querySelector('[title="DIV 1"]');
            div1.classList.toggle('w-32'); // width: 8rem
            div1.classList.toggle('w-16'); // width: 4rem
        }
    </script>
</body>

</html>