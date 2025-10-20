<?php
date_default_timezone_set('Asia/Muscat');
include_once "includes/config.php";
include_once "includes/functions.php";
include_once "includes/navbar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$currentDate = date('Y-m-d');
$currentHour = (int)date('H');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        ob_end_clean();
        $search = isset($_GET['search']) ? sanitizeInput(trim($_GET['search'])) : '';
        $conditions = array();
        $params = array();
        
        if ($search) {
            $conditions[] = "(
                u.username LIKE ? OR 
                l.action_type LIKE ? OR 
                l.table_name LIKE ? OR 
                l.record_name LIKE ? OR 
                u.role LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 5, "%$search%"));
            
            if (is_numeric($search)) {
                $conditions[] = "(l.user_id = ? OR l.record_id = ?)";
                $params[] = (int)$search;
                $params[] = (int)$search;
            }
        }

        $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $exportQuery = $pdo->prepare("
            SELECT l.action_type, u.username, u.role, l.table_name, l.record_name, 
                   l.affected_fields, l.details, l.created_at
            FROM actions_logs l
            JOIN users u ON l.user_id = u.user_id
            $where
            ORDER BY l.created_at DESC
        ");
        
        $exportQuery->execute($params);
        
        $filename = "action_logs_export_" . date('Y-m-d_H-i-s') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, array(
            'Action', 
            'User', 
            'Role', 
            'Table', 
            'Record Name', 
            'Affected Fields', 
            'Old Value', 
            'New Value', 
            'Timestamp'
        ));
        
        while ($log = $exportQuery->fetch(PDO::FETCH_ASSOC)) {
            $details = json_decode($log['details'], true);
            if (!is_array($details)) {
                $details = array();
            }
            
            $old_value = '-';
            $new_value = '-';
            
            if ($log['action_type'] === 'UPDATE' && isset($details['changes'])) {
                $old_values = array();
                $new_values = array();
                foreach ($details['changes'] as $field => $change) {
                    $old_values[] = "$field: " . (isset($change['old']) ? $change['old'] : 'None');
                    $new_values[] = "$field: " . (isset($change['new']) ? $change['new'] : 'None');
                }
                $old_value = implode('; ', $old_values);
                $new_value = implode('; ', $new_values);
            } elseif ($log['action_type'] === 'DELETE') {
                $old_value = isset($log['record_name']) ? $log['record_name'] : 'Unknown';
            } elseif ($log['action_type'] === 'CREATE') {
                $new_value = isset($log['record_name']) ? $log['record_name'] : 'Unknown';
            }
            
            fputcsv($output, array(
                $log['action_type'],
                $log['username'],
                $log['role'],
                $log['table_name'],
                isset($log['record_name']) ? $log['record_name'] : 'Unknown',
                isset($log['affected_fields']) ? $log['affected_fields'] : '-',
                $old_value,
                $new_value,
                $log['created_at']
            ));
        }
        
        fclose($output);
        exit;
        
    } catch (PDOException $e) {
        error_log("Export error: " . $e->getMessage());
        $_SESSION['export_error'] = "Failed to export logs: Database error";
        header("Location: admin_dashboard.php");
        exit;
    } catch (Exception $e) {
        error_log("General export error: " . $e->getMessage());
        $_SESSION['export_error'] = "Failed to export logs: System error";
        header("Location: admin_dashboard.php");
        exit;
    }
}

// Fetch staff summary
try {
    $totalSN = $pdo->query("SELECT COUNT(*) FROM staff_sn")->fetchColumn();
    $totalOM = $pdo->query("SELECT COUNT(*) FROM staff_om")->fetchColumn();
    $totalStaff = $totalSN + $totalOM;

    $morningSN = $pdo->query("SELECT COUNT(*) FROM staff_sn WHERE shift = 'Morning'")->fetchColumn();
    $eveningSN = $pdo->query("SELECT COUNT(*) FROM staff_sn WHERE shift = 'Evening'")->fetchColumn();
    $morningOM = $pdo->query("SELECT COUNT(*) FROM staff_om WHERE shift = 'Morning'")->fetchColumn();
    $eveningOM = $pdo->query("SELECT COUNT(*) FROM staff_om WHERE shift = 'Evening'")->fetchColumn();

    $morningCoverage = $totalStaff > 0 ? round(($morningSN + $morningOM) / $totalStaff * 100, 1) : 0;
    $eveningCoverage = $totalStaff > 0 ? round(($eveningSN + $eveningOM) / $totalStaff * 100, 1) : 0;
} catch (PDOException $e) {
    error_log("Error fetching staff summary: " . $e->getMessage());
    $error = "System error. Please try again.";
}

// Get selected date for staff list
$selectedDate = isset($_GET['staff_date']) ? sanitizeInput(trim($_GET['staff_date'])) : $currentDate;

// Fetch staff list for the selected date
try {
    $staffList = array();
    $stmt = $pdo->prepare("
        SELECT 'sn' AS type, sn_id AS id, staff_name, shift, room_name, ward_name, assignment_date
        FROM staff_sn
        LEFT JOIN room_assignments ON staff_sn.room_id = room_assignments.room_id
        LEFT JOIN ward_assignments ON staff_sn.ward_id = ward_assignments.ward_id
        WHERE assignment_date = ?
        UNION
        SELECT 'om' AS type, om_id AS id, staff_name, shift, room_name, ward_name, assignment_date
        FROM staff_om
        LEFT JOIN room_assignments ON staff_om.room_id = room_assignments.room_id
        LEFT JOIN ward_assignments ON staff_om.ward_id = ward_assignments.ward_id
        WHERE assignment_date = ?
        ORDER BY type, staff_name
    ");
    $stmt->execute([$selectedDate, $selectedDate]);
    $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching staff list: " . $e->getMessage());
    $staffError = "Failed to load staff list: Database error";
}

$search = isset($_GET['search']) ? sanitizeInput(trim($_GET['search'])) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nizwa Hospital Day Care Unit</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/fontawesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7fa;
            color: #34495e;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #2c3e50;
            color: #fff;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .card-header .title {
            font-size: 1.1rem;
            margin: 0;
        }
        .card-header .search-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 600px;
            margin: 0 auto;
        }
        .card-header .search-container .input-group {
            flex-grow: 1;
        }
        .card-header .search-container input {
            font-size: 0.9rem;
            padding: 0.4rem;
            border-radius: 4px 0 0 4px;
        }
        .card-header .search-container .clear-btn {
            cursor: pointer;
            padding: 0.4rem;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-left: none;
            border-radius: 0 4px 4px 0;
            color: #6c757d;
            transition: background-color 0.2s;
        }
        .card-header .search-container .clear-btn:hover {
            background-color: #dee2e6;
        }
        .card-header .btn-success {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
            white-space: nowrap;
        }
        .card-body {
            padding: 1rem;
        }
        .table-responsive {
            border-radius: 0 0 8px 8px;
            overflow-x: auto;
        }
        .table {
            margin: 0;
        }
        .table-sticky th {
            position: sticky;
            top: 0;
            background: #f1f3f5;
            z-index: 1;
            font-size: 1rem;
            padding: 0.75rem;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            padding: 0.75rem;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-success {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        .progress-bar {
            background-color: #27ae60;
        }
        .progress-bar.bg-info {
            background-color: #2980b9;
        }
        .summary-card {
            padding: 1rem;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .summary-card h5 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .summary-card p {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .pagination {
            justify-content: center;
            margin-top: 1rem;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin: 1rem 0;
        }
        .date-filter-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: center;
        }
        .date-filter-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 680px;
            margin: 0 auto;
        }
        .date-filter-container input {
            text-align: center;
            font-size: 0.9rem;
            padding: 0.4rem;
            border-radius: 8px;
            width: 500px;
        }
        .date-filter-container .btn-nav {
            font-size: 0.9rem;
            padding: 0.6rem 0.6rem;
        }
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            .card-header .search-container {
                width: 100%;
                max-width: 100%;
            }
            .card-header .title {
                font-size: 1rem;
                text-align: center;
                margin-bottom: 0.5rem;
            }
            .card-header .btn-success {
                font-size: 0.85rem;
                padding: 0.3rem 0.6rem;
            }
            .table-sticky th {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
            .table td {
                font-size: 0.85rem;
                padding: 0.5rem;
            }
            .date-filter-container input {
                font-size: 0.85rem;
                width: 120px;
            }
            .date-filter-container .btn-nav {
                font-size: 0.85rem;
                padding: 0.3rem 0.5rem;
            }
        }
        @media (max-width: 576px) {
            .card-header .search-container input {
                font-size: 0.8rem;
            }
            .card-header .btn-success {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }
            .table-sticky th {
                font-size: 0.85rem;
                padding: 0.4rem;
            }
            .table td {
                font-size: 0.8rem;
                padding: 0.4rem;
            }
            .date-filter-container input {
                font-size: 0.8rem;
                width: 100px;
            }
            .date-filter-container .btn-nav {
                font-size: 0.8rem;
                padding: 0.25rem 0.4rem;
            }
        }
    </style>
</head>
<body>
<?php include_once "includes/navbar.php"; ?>
<div class="container my-3">
    <h1>Admin Dashboard</h1>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($staffError)): ?>
        <div class="error-message"><?php echo htmlspecialchars($staffError); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header">
            <span class="title">Shift Summary</span>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <div class="summary-card flex-fill">
                    <h5>Total Staff</h5>
                    <p><?php echo htmlspecialchars($totalStaff); ?></p>
                    <p class="text-muted">S/N: <?php echo htmlspecialchars($totalSN); ?>, O/M: <?php echo htmlspecialchars($totalOM); ?></p>
                </div>
                <div class="summary-card flex-fill">
                    <h5>Morning Shift</h5>
                    <p><?php echo htmlspecialchars($morningSN + $morningOM); ?></p>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar" style="width: <?php echo htmlspecialchars($morningCoverage); ?>%;"><?php echo htmlspecialchars($morningCoverage); ?>%</div>
                    </div>
                </div>
                <div class="summary-card flex-fill">
                    <h5>Evening Shift</h5>
                    <p><?php echo htmlspecialchars($eveningSN + $eveningOM); ?></p>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar bg-info" style="width: <?php echo htmlspecialchars($eveningCoverage); ?>%;"><?php echo htmlspecialchars($eveningCoverage); ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <span class="title">Staff List for <?php echo htmlspecialchars($selectedDate); ?></span>
            <div class="date-filter-wrapper">
                <div class="date-filter-container">
                    <button class="btn btn-primary btn-nav" id="prevDayBtn" title="Previous Day"><i class="fas fa-chevron-left"></i></button>
                    <input type="date" id="staffDateFilter" value="<?php echo htmlspecialchars($selectedDate); ?>">
                    <button class="btn btn-primary btn-nav" id="nextDayBtn" title="Next Day"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-sticky">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Shift</th>
                            <th>Room</th>
                            <th>Ward</th>
                            <th>Assignment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staffList)): ?>
                            <tr><td colspan="6" class="text-center">No staff found for this date.</td></tr>
                        <?php else: ?>
                            <?php foreach ($staffList as $staff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(strtoupper($staff['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($staff['staff_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['shift']); ?></td>
                                    <td><?php echo $staff['room_name'] ? htmlspecialchars($staff['room_name']) : '-'; ?></td>
                                    <td><?php echo $staff['ward_name'] ? htmlspecialchars($staff['ward_name']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($staff['assignment_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="title">Action Logs</span>
            <div class="search-container">
                <div class="input-group">
                    <input type="text" id="logSearch" class="form-control" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>" aria-label="Search action logs">
                    <span class="clear-btn" id="clearSearch"><i class="fas fa-times"></i></span>
                </div>
                <a href="?export=csv&search=<?php echo urlencode($search); ?>" class="btn btn-success btn-sm"><i class="fas fa-download"></i> Export</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-sticky" id="logsTable">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>User (Role)</th>
                            <th>Table</th>
                            <th>Record Name</th>
                            <th>Affected Fields</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="8" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Page navigation" id="pagination">
                <ul class="pagination"></ul>
            </nav>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('logSearch');
    var clearSearch = document.getElementById('clearSearch');
    var logsTable = document.getElementById('logsTable').querySelector('tbody');
    var pagination = document.getElementById('pagination');
    var staffDateFilter = document.getElementById('staffDateFilter');
    var prevDayBtn = document.getElementById('prevDayBtn');
    var nextDayBtn = document.getElementById('nextDayBtn');
    var debounceTimeout;
    var currentDate = '<?php echo $currentDate; ?>';

    function fetchLogs(search, page) {
        if (typeof search === 'undefined') search = '';
        if (typeof page === 'undefined') page = 1;
        var perPage = 10;
        logsTable.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
        try {
            if (search && /[<>{}]/.test(search)) {
                logsTable.innerHTML = '<tr><td colspan="8" class="text-danger">Invalid search characters</td></tr>';
                pagination.innerHTML = '';
                return;
            }

            var url = 'ajax_logs.php?search=' + encodeURIComponent(search) + '&page=' + page + '&per_page=' + perPage;
            fetch(url, {
                headers: { 'Accept': 'application/json' }
            }).then(function(response) {
                return response.text().then(function(rawText) {
                    console.log('Raw AJAX response:', rawText);
                    if (!response.ok) {
                        throw new Error('HTTP error! Status: ' + response.status + ', Response: ' + rawText.substring(0, 100) + '...');
                    }
                    var data;
                    try {
                        data = JSON.parse(rawText);
                    } catch (e) {
                        throw new Error('Invalid JSON: ' + e.message + '. Raw response: ' + rawText.substring(0, 100) + '...');
                    }
                    return data;
                });
            }).then(function(data) {
                if (data.error) {
                    logsTable.innerHTML = '<tr><td colspan="8" class="text-danger">Error: ' + data.message + '</td></tr>';
                    pagination.innerHTML = '';
                    return;
                }

                logsTable.innerHTML = data.output || '<tr><td colspan="8" class="text-center">No action logs found</td></tr>';
                pagination.innerHTML = data.total_pages > 1 ? (
                    '<ul class="pagination">' +
                    Array.from({ length: data.total_pages }, function(_, i) {
                        return (
                            '<li class="page-item' + (page === i + 1 ? ' active' : '') + '">' +
                            '<a class="page-link" href="#" data-page="' + (i + 1) + '">' + (i + 1) + '</a>' +
                            '</li>'
                        );
                    }).join('') +
                    '</ul>'
                ) : '';

                var pageLinks = document.querySelectorAll('.page-link');
                for (var i = 0; i < pageLinks.length; i++) {
                    pageLinks[i].addEventListener('click', function(e) {
                        e.preventDefault();
                        var newPage = parseInt(this.dataset.page);
                        fetchLogs(searchInput.value, newPage);
                    });
                }
            }).catch(function(error) {
                console.error('Fetch error:', error);
                logsTable.innerHTML = '<tr><td colspan="8" class="text-danger">Failed to load logs: ' + error.message + '</td></tr>';
                pagination.innerHTML = '';
            });
        } catch (error) {
            console.error('Fetch error:', error);
            logsTable.innerHTML = '<tr><td colspan="8" class="text-danger">Failed to load logs: ' + error.message + '</td></tr>';
            pagination.innerHTML = '';
        }
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(function() {
            fetchLogs(searchInput.value, 1);
        }, 300);
    });

    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        fetchLogs('', 1);
    });

    // Handle date filter change
    staffDateFilter.addEventListener('change', function() {
        var selectedDate = this.value;
        if (selectedDate) {
            window.location.href = 'admin_dashboard.php?staff_date=' + encodeURIComponent(selectedDate) + '&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>';
        }
    });

    // Handle previous day button
    prevDayBtn.addEventListener('click', function() {
        var currentDateValue = staffDateFilter.value;
        if (currentDateValue) {
            var date = new Date(currentDateValue);
            date.setDate(date.getDate() - 1);
            var newDate = date.toISOString().split('T')[0];
            window.location.href = 'admin_dashboard.php?staff_date=' + encodeURIComponent(newDate) + '&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>';
        }
    });

    // Handle next day button
    nextDayBtn.addEventListener('click', function() {
        var currentDateValue = staffDateFilter.value;
        if (currentDateValue) {
            var date = new Date(currentDateValue);
            date.setDate(date.getDate() + 1);
            var newDate = date.toISOString().split('T')[0];
            window.location.href = 'admin_dashboard.php?staff_date=' + encodeURIComponent(newDate) + '&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>';
        }
    });

    fetchLogs('<?php echo htmlspecialchars($search); ?>', <?php echo $page; ?>);
});
</script>
</body>
</html>