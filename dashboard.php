<?php
date_default_timezone_set('Asia/Muscat');
include_once "includes/config.php";
include_once "includes/navbar.php";

$currentDate = date('Y-m-d');
$currentDay = date('l');
$currentTime = date('h:i A');
$currentHour = (int)date('H');
$currentShift = ($currentHour >= 7 && $currentHour < 15) ? 'Morning' : 'Evening';

function renderStaffTable($pdo, $shift, $currentDate) {
    $output = '';
    try {
        $staffQuery = $pdo->prepare("SELECT staff_name, room_name, ward_name 
            FROM staff_sn 
            LEFT JOIN room_assignments ON staff_sn.room_id = room_assignments.room_id 
            LEFT JOIN ward_assignments ON staff_sn.ward_id = ward_assignments.ward_id 
            WHERE shift = ? AND assignment_date = ?");
        $staffQuery->execute([$shift, $currentDate]);
        $staffAssignments = $staffQuery->fetchAll(PDO::FETCH_ASSOC);

        $omQuery = $pdo->prepare("SELECT staff_name, room_name, ward_name 
            FROM staff_om 
            LEFT JOIN room_assignments ON staff_om.room_id = room_assignments.room_id 
            LEFT JOIN ward_assignments ON staff_om.ward_id = ward_assignments.ward_id 
            WHERE shift = ? AND assignment_date = ?");
        $omQuery->execute([$shift, $currentDate]);
        $omAssignments = $omQuery->fetchAll(PDO::FETCH_ASSOC);

        if (empty($staffAssignments) && empty($omAssignments)) {
            $output .= "<tr><td colspan='4' class='text-center'>No assignments found for $shift shift on $currentDate</td></tr>";
            return $output;
        }

        foreach ($staffAssignments as $assignment) {
            $output .= "<tr>
                <td>S/N</td>
                <td>" . htmlspecialchars($assignment['staff_name']) . "</td>
                <td>" . ($assignment['room_name'] ? htmlspecialchars($assignment['room_name']) : '-') . "</td>
                <td>" . ($assignment['ward_name'] ? htmlspecialchars($assignment['ward_name']) : '-') . "</td>
            </tr>";
        }
        foreach ($omAssignments as $assignment) {
            $output .= "<tr>
                <td>O/M</td>
                <td>" . htmlspecialchars($assignment['staff_name']) . "</td>
                <td>" . ($assignment['room_name'] ? htmlspecialchars($assignment['room_name']) : '-') . "</td>
                <td>" . ($assignment['ward_name'] ? htmlspecialchars($assignment['ward_name']) : '-') . "</td>
            </tr>";
        }
    } catch (PDOException $e) {
        $output .= "<tr><td colspan='4' class='text-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    }
    return $output;
}
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
            background-color: #f8f9fa;
            color: #2c3e50;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 1rem; /* Match admin_dashboard.php default */
        }
        .dashboard-content {
            font-size: 1.25rem; /* Larger font for content */
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            font-weight: 700;
            font-size: 3rem;
            margin: 2.5rem 0;
        }
        .info-section {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            padding: 2rem;
            flex-wrap: wrap;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2.5rem;
        }
        .info-section p {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .info-section .fas {
            font-size: 2rem;
            margin-right: 0.75rem;
            color: #2c3e50;
        }
        .card {
            background-color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2.5rem;
        }
        .card-header {
            background-color: #2c3e50;
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            font-size: 1.75rem;
        }
        .card-header .fas {
            font-size: 1.75rem;
            margin-right: 0.5rem;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table {
            margin: 0;
            background-color: #fff;
        }
        .table th {
            background-color: #f1f3f5;
            color: #2c3e50;
            font-weight: 600;
            padding: 1.25rem;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 1.5rem;
        }
        .table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
            font-size: 1.4rem;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .container-xl {
            max-width: 1600px;
        }
        @media (min-width: 1400px) {
            .container-xl {
                max-width: 1800px;
            }
        }
        @media (max-width: 767px) {
            .dashboard-content {
                font-size: 1rem;
            }
            h1 {
                font-size: 2rem;
            }
            .info-section {
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
                padding: 1.5rem;
            }
            .info-section p {
                font-size: 1.2rem;
            }
            .info-section .fas {
                font-size: 1.5rem;
            }
            .card-header {
                font-size: 1.4rem;
                padding: 1rem;
            }
            .card-header .fas {
                font-size: 1.4rem;
            }
            .table th {
                font-size: 1.2rem;
                padding: 0.75rem;
            }
            .table td {
                font-size: 1.1rem;
                padding: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .dashboard-content {
                font-size: 0.9rem;
            }
            h1 {
                font-size: 1.8rem;
            }
            .info-section p {
                font-size: 1rem;
            }
            .info-section .fas {
                font-size: 1.2rem;
            }
            .card-header {
                font-size: 1.2rem;
                padding: 0.75rem;
            }
            .card-header .fas {
                font-size: 1.2rem;
            }
            .table th {
                font-size: 1rem;
                padding: 0.5rem;
            }
            .table td {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
<?php include_once "includes/navbar.php"; ?>
<div class="dashboard-content">
    <div class="container-xl my-4">
        <h1>Nizwa Hospital Day Care Unit</h1>
        <div class="info-section">
            <p><i class="fas fa-calendar"></i><strong>Date: </strong><?= htmlspecialchars($currentDate) ?></p>
            <p><i class="fas fa-calendar-day"></i><strong>Day: </strong><?= htmlspecialchars($currentDay) ?></p>
            <p><i class="fas fa-clock"></i><strong>Time: </strong><?= htmlspecialchars($currentTime) ?></p>
            <p><i class="fas fa-<?= $currentShift === 'Morning' ? 'sun' : 'moon' ?>"></i><strong>Shift: </strong><?= htmlspecialchars($currentShift) ?></p>
        </div>
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-sun"></i> Morning Staff Assignments</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" aria-label="Morning Staff Assignments">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Staff Name</th>
                                <th>Room Name</th>
                                <th>Ward Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderStaffTable($pdo, 'Morning', $currentDate) ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-moon"></i> Evening Staff Assignments</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" aria-label="Evening Staff Assignments">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Staff Name</th>
                                <th>Room Name</th>
                                <th>Ward Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderStaffTable($pdo, 'Evening', $currentDate) ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(function() {
        location.reload();
    }, 60000);
    
    function updateTime() {
        const now = new Date();
        const timeElement = document.querySelector('.info-section p:nth-child(3)');
        if (timeElement) {
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = hours % 12 || 12;
            const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.innerHTML = `<i class="fas fa-clock"></i><strong>Time: </strong>${formattedHours}:${formattedMinutes} ${ampm}`;
        }
    }
    
    setInterval(updateTime, 1000);
</script>
</body>
</html>