<?php
$host = 'localhost';
$dbname = 'inner_sparc_accreditation';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch counts per team per year-month
    $stmt = $pdo->query("
        SELECT team, DATE_FORMAT(datetime, '%Y-%m') AS ym, COUNT(*) AS total
        FROM manual_data
        GROUP BY team, DATE_FORMAT(datetime, '%Y-%m')
        ORDER BY ym, team
    ");

    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group data by month then team
    $monthlyData = [];
    foreach ($rawData as $row) {
        $month = $row['ym'];
        $team = $row['team'];
        $count = (int) $row['total'];

        if (!isset($monthlyData[$month])) {
            $monthlyData[$month] = [];
        }

        $monthlyData[$month][$team] = $count;
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Team Toggle Count per Month</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="p-6">

    <?php foreach ($monthlyData as $month => $teams): ?>
        <div style="margin-bottom: 40px;">
            <h2 style="font-size: 1.5rem; margin-bottom: 10px;">
                <?= date('F Y', strtotime($month . '-01')) ?>
            </h2>
            <canvas id="chart_<?= $month ?>"></canvas>
        </div>
    <?php endforeach; ?>

    <script>
        <?php foreach ($monthlyData as $month => $teams): ?>
            const ctx_<?= str_replace('-', '_', $month) ?> = document.getElementById('chart_<?= $month ?>').getContext('2d');
            new Chart(ctx_<?= str_replace('-', '_', $month) ?>, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($teams)) ?>,
                    datasets: [{
                        label: 'Total Toggles',
                        data: <?= json_encode(array_values($teams)) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        <?php endforeach; ?>
    </script>

</body>

</html>