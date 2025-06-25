<?php
require_once '../core/models.php';

try {
    $pdo = getPDO();

    // Get filter parameters
    $filters = [
        'name' => $_POST['name'] ?? '',
        'team' => $_POST['team'] ?? '',
        'toggle' => $_POST['toggle'] ?? '',
        'month' => $_POST['month'] ?? ''
    ];

    // Get filtered data
    $rows = getFilteredManualData($pdo, $filters);

    // Get team options for dropdowns
    $teamOptions = getAllTeams($pdo);

    // Output the filtered table rows
    foreach ($rows as $row): ?>
        <tr data-id="<?= $row['id'] ?>">


            <td class="p-0 max-w-[160px]">
                <div class="bg-gray-100 rounded-lg px-3 py-2 text-sm truncate whitespace-nowrap">
                    <?= htmlspecialchars($row['datetime']) ?>

                </div>
            </td>


            <td class="p-0 max-w-[160px]">
                <div class="bg-gray-100 rounded-lg px-3 py-2 text-sm truncate whitespace-nowrap">
                    <?= htmlspecialchars($row['name']) ?>
                </div>
            </td>


            <td class="p-0">
                <select class="team-select w-full block bg-gray-100 rounded-lg px-3 py-2 focus:outline-none">
                    <?php foreach ($teamOptions as $teamOption): ?>
                        <option value="<?= $teamOption ?>" <?= $row['team'] === $teamOption ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teamOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="p-0">
                <select class="toggle-select w-full block bg-gray-100 rounded-lg px-3 py-2 focus:outline-none">
                    <option value="1" <?= $row['toggle'] === '1' ? 'selected' : '' ?>>Finished</option>
                    <option value="0" <?= $row['toggle'] === '0' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="" <?= $row['toggle'] === '' ? 'selected' : '' ?>>No Response</option>
                </select>
            </td>
            <td class="p-0">
                <button
                    class="delete-btn w-full block bg-red-500 text-white rounded-lg px-3 py-2 hover:bg-red-600 transition">Delete</button>
            </td>
        </tr>
    <?php endforeach;

} catch (Exception $e) {
    echo '<tr><td colspan="5" class="loading">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>