<?php
require_once 'core/dbConfig.php';
require_once 'core/models.php';

$data = getToggleDataByTeamAndMonth($pdo);
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
    '1' => '✅',
    '0' => '❌',
    '' => '⬜',
];
?>



<?php foreach ($toggleLabels as $toggle => $label): ?>
    <h2 class="text-xl font-bold mb-2">Toggle: <?= $label ?></h2>

<?php endforeach; ?>

<?php foreach ($toggleLabels as $toggle => $label): ?>
    <?php if (!isset($data[$toggle]))
        continue; ?>

    <h2 class="text-xl font-bold mb-2">Toggle: <?= $label ?></h2>
    <div class="overflow-x-auto mb-8">
        <table class="min-w-full border border-gray-300">
            <thead>
                <tr>
                    <th class="border px-4 py-2">Team</th>
                    <?php foreach ($months as $month): ?>
                        <th class="border px-4 py-2"><?= $month ?></th>
                    <?php endforeach; ?>
                    <th class="border px-4 py-2">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data[$toggle] as $team => $counts): ?>
                    <tr>
                        <td class="border px-4 py-2"><?= $team ?></td>
                        <?php
                        $total = 0;
                        for ($m = 1; $m <= 12; $m++):
                            $count = $counts[$m] ?? 0;
                            $total += $count;
                            ?>
                            <td class="border px-4 py-2"><?= $count ?></td>
                        <?php endfor; ?>
                        <td class="border px-4 py-2 font-bold"><?= $total ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>