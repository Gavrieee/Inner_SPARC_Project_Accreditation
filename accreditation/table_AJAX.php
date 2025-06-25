<?php
require_once 'core/models.php';

try {
    $pdo = getPDO();
    $rows = getFilteredManualData($pdo); // Get all data initially
    $teamOptions = getAllTeams($pdo);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Manual Data Table</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #aaa;
        padding: 8px;
        text-align: center;
    }

    select,
    button {
        padding: 4px 8px;
    }

    .filters {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filters input,
    .filters select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filters input:focus,
    .filters select:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    .loading {
        text-align: center;
        padding: 20px;
        color: #666;
    }

    .delete-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 3px;
        cursor: pointer;
    }

    .delete-btn:hover {
        background-color: #c82333;
    }
    </style>
</head>

<body>

    <h2>📋 Manual Data Table</h2>

    <div class="filters">
        <input type="text" id="search-name" placeholder="Search by name" />

        <select id="filter-team">
            <option value="">All Teams</option>
            <?php foreach ($teamOptions as $team): ?>
            <option value="<?= htmlspecialchars($team) ?>"><?= htmlspecialchars($team) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filter-toggle">
            <option value="">All Status</option>
            <option value="1">✅ Finished</option>
            <option value="0">❌ Cancelled</option>
            <option value="null">⬜ No Response</option>
        </select>
    </div>


    <table>
        <thead>
            <tr>
                <th>DateTime</th>
                <th>Name</th>
                <th>Team</th>
                <th>Toggle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="user-table-body">
            <?php foreach ($rows as $row): ?>
            <!-- Inside your <tbody> loop -->
            <tr data-id="<?= $row['id'] ?>">
                <td><?= htmlspecialchars($row['datetime']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>

                <!-- Inside table row -->
                <td>
                    <select class="team-select">
                        <?php foreach ($teamOptions as $teamOption): ?>
                        <option value="<?= $teamOption ?>" <?= $row['team'] === $teamOption ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teamOption) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>

                <td>
                    <select class="toggle-select">
                        <option value="1" <?= $row['toggle'] === '1' ? 'selected' : '' ?>>Finished</option>
                        <option value="0" <?= $row['toggle'] === '0' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="" <?= $row['toggle'] === '' ? 'selected' : '' ?>>No Response</option>
                    </select>
                </td>

                <td>
                    <button class="delete-btn">Delete</button>
                </td>
            </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const nameInput = document.getElementById("search-name");
        const teamFilter = document.getElementById("filter-team");
        const toggleFilter = document.getElementById("filter-toggle");

        let searchTimeout; // For debouncing the search

        function fetchFilteredData() {
            const name = nameInput.value;
            const team = teamFilter.value;
            const toggle = toggleFilter.value;

            // Show loading indicator
            const tableBody = document.getElementById("user-table-body");
            tableBody.innerHTML = '<tr><td colspan="5" class="loading">Loading...</td></tr>';

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax/fetch_filtered_data.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById("user-table-body").innerHTML = xhr.responseText;
                    // Reattach jQuery event handlers after content update
                    attachEventHandlers();
                } else {
                    tableBody.innerHTML =
                        '<tr><td colspan="5" class="loading">Error loading data</td></tr>';
                }
            };
            xhr.onerror = function() {
                tableBody.innerHTML = '<tr><td colspan="5" class="loading">Error loading data</td></tr>';
            };
            xhr.send(
                `name=${encodeURIComponent(name)}&team=${encodeURIComponent(team)}&toggle=${encodeURIComponent(toggle)}`
            );
        }

        // Debounced search function
        function debouncedSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchFilteredData, 500); // 500ms delay
        }

        // Add event listeners with different behaviors
        nameInput.addEventListener("input", debouncedSearch); // Debounced for typing
        teamFilter.addEventListener("change", fetchFilteredData); // Immediate for dropdowns
        toggleFilter.addEventListener("change", fetchFilteredData); // Immediate for dropdowns
    });
    </script>


    <script>
    function attachEventHandlers() {
        // Automatically update on change of team or toggle
        $('select.team-select, select.toggle-select').off('change').on('change', function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const team = row.find('.team-select').val();
            const toggle = row.find('.toggle-select').val();

            $.post('ajax/update_data.php', {
                id,
                team,
                toggle
            }, function(response) {
                console.log("✅ " + response);
            }).fail(function() {
                alert("❌ Failed to update.");
            });
        });

        // Delete button event handler
        $('.delete-btn').off('click').on('click', function() {
            if (!confirm("Are you sure you want to delete this entry?")) return;

            const row = $(this).closest('tr');
            const id = row.data('id');

            $.post('ajax/delete_data.php', {
                id
            }, function(response) {
                alert(response);
                row.remove();
            }).fail(function() {
                alert("❌ Failed to delete.");
            });
        });
    }

    $(document).ready(function() {
        // Initial attachment of event handlers
        attachEventHandlers();
    });
    </script>


</body>

</html>