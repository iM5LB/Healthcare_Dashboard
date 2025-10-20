<?php
define('INCLUDED', true);
include_once "../includes/config.php";
include_once "../logs/log_action.php";
include_once "../includes/functions.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: /Healthcare_Dashboard/auth/login.php");
    exit;
}

$error = '';
$success = '';

// Process POST requests (add/edit staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['bulk_add'])) {
            // Handle bulk add (6 staff: 4 S/N, 2 O/M)
            $assignment_date = isset($_POST['bulk_assignment_date']) ? sanitizeInput($_POST['bulk_assignment_date']) : date('Y-m-d');
            $shift = isset($_POST['bulk_shift']) ? sanitizeInput($_POST['bulk_shift']) : '';
            $staff_data = isset($_POST['staff_data']) ? $_POST['staff_data'] : [];

            // Validate date
            $today = new DateTime('now', new DateTimeZone('UTC'));
            $selected_date = new DateTime($assignment_date, new DateTimeZone('UTC'));
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            if ($selected_date < $today) {
                throw new Exception("Assignment date cannot be in the past.");
            }

            // Validate staff data
            if (count($staff_data) !== 6) {
                throw new Exception("Exactly 6 staff members must be provided (4 S/N, 2 O/M).");
            }

            $sn_count = 0;
            $om_count = 0;
            $used_locations = []; // Track room_id:ward_id combinations
            $pdo->beginTransaction();

            foreach ($staff_data as $index => $staff) {
                $type = sanitizeInput($staff['type']);
                $name = trim(sanitizeInput($staff['staff_name']));
                $room_id = !empty($staff['room_id']) ? (int)$staff['room_id'] : null;
                $ward_id = !empty($staff['ward_id']) ? (int)$staff['ward_id'] : null;

                // Validate input
                if (empty($name) || !in_array($type, ['sn', 'om']) || empty($shift) || (!$room_id && !$ward_id)) {
                    throw new Exception("Invalid or missing data for staff member " . ($index + 1) . ".");
                }

                // Count S/N and O/M
                if ($type === 'sn') {
                    $sn_count++;
                } else {
                    $om_count++;
                }

                // Check location uniqueness
                $location_key = "$room_id:$ward_id";
                if (in_array($location_key, $used_locations)) {
                    throw new Exception("Duplicate room/ward assignment for staff member " . ($index + 1) . ".");
                }
                $used_locations[] = $location_key;

                // Check for duplicate name
                $table = ($type === 'sn') ? 'staff_sn' : 'staff_om';
                $id_field = ($type === 'sn') ? 'sn_id' : 'om_id';
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE LOWER(staff_name) = LOWER(?)");
                $stmt->execute([$name]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Staff name '$name' already exists.");
                }

                // Check for duplicate assignment
                $location_check = "";
                $params = [$assignment_date, $shift];
                if ($room_id) {
                    $location_check = " AND room_id = ?";
                    $params[] = $room_id;
                } elseif ($ward_id) {
                    $location_check = " AND ward_id = ?";
                    $params[] = $ward_id;
                }
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE assignment_date = ? AND shift = ? $location_check");
                $stmt->execute($params);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Location already assigned for staff member " . ($index + 1) . " on this date and shift.");
                }

                // Insert staff
                $stmt = $pdo->prepare("INSERT INTO $table (staff_name, shift, room_id, ward_id, assignment_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $shift, $room_id, $ward_id, $assignment_date]);
                $new_id = $pdo->lastInsertId();

                // Log action
                $new_data = [
                    'staff_name' => $name,
                    'shift' => $shift,
                    'room_id' => $room_id,
                    'ward_id' => $ward_id,
                    'assignment_date' => $assignment_date
                ];
                $affected_fields = implode(',', array_keys($new_data));
                logAction($pdo, $_SESSION['user_id'], 'CREATE', $table, $new_id, $name, null, $new_data, $affected_fields);
            }

            // Validate S/N and O/M counts
            if ($sn_count !== 4 || $om_count !== 2) {
                throw new Exception("Must include exactly 4 S/N and 2 O/M staff members.");
            }

            $pdo->commit();
            redirectWithMessage("6 staff members added successfully.", '', "staff_panel.php");
        } else {
            // Existing single staff add/edit logic
            $id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
            $type = isset($_POST['type']) ? sanitizeInput($_POST['type']) : '';
            $name = isset($_POST['staff_name']) ? trim(sanitizeInput($_POST['staff_name'])) : '';
            $shift = isset($_POST['shift']) ? sanitizeInput($_POST['shift']) : '';
            $room_id = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;
            $ward_id = !empty($_POST['ward_id']) ? (int)$_POST['ward_id'] : null;
            $assignment_date = isset($_POST['assignment_date']) ? sanitizeInput($_POST['assignment_date']) : date('Y-m-d');

            // Check if date is in the past
            $today = new DateTime('now', new DateTimeZone('UTC'));
            $selected_date = new DateTime($assignment_date, new DateTimeZone('UTC'));
            $today->setTime(0, 0, 0);
            $selected_date->setTime(0, 0, 0);
            if ($selected_date < $today) {
                throw new Exception("Assignment date cannot be in the past.");
            }

            $table = ($type === 'sn') ? 'staff_sn' : 'staff_om';
            $id_field = ($type === 'sn') ? 'sn_id' : 'om_id';

            // Check for duplicate name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE LOWER(staff_name) = LOWER(?) AND $id_field != ?");
            $stmt->execute([$name, $id ?: 0]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Staff name already exists.");
            }

            // Check for duplicate assignment
            $location_check = "";
            $params = [$assignment_date, $shift];
            if ($room_id) {
                $location_check = " AND room_id = ?";
                $params[] = $room_id;
            } elseif ($ward_id) {
                $location_check = " AND ward_id = ?";
                $params[] = $ward_id;
            } else {
                throw new Exception("Either room or ward must be selected.");
            }
            $params[] = $id ?: 0;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE assignment_date = ? AND shift = ? $location_check AND $id_field != ?");
            $stmt->execute($params);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("This location already has a staff assignment for the selected day and shift.");
            }

            // Prepare data for logging
            $new_data = [
                'staff_name' => $name,
                'shift' => $shift,
                'room_id' => $room_id,
                'ward_id' => $ward_id,
                'assignment_date' => $assignment_date
            ];
            $affected_fields = implode(',', array_keys($new_data));

            if ($id) {
                // Update existing staff
                $stmt = $pdo->prepare("SELECT staff_name, shift, room_id, ward_id, assignment_date FROM $table WHERE $id_field = ?");
                $stmt->execute([$id]);
                $old_data = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare("UPDATE $table SET staff_name = ?, shift = ?, room_id = ?, ward_id = ?, assignment_date = ? WHERE $id_field = ?");
                $stmt->execute([$name, $shift, $room_id, $ward_id, $assignment_date, $id]);
                logAction($pdo, $_SESSION['user_id'], 'UPDATE', $table, $id, $name, $old_data, $new_data, $affected_fields);
                $success = "Staff updated successfully.";
            } else {
                // Create new staff
                $stmt = $pdo->prepare("INSERT INTO $table (staff_name, shift, room_id, ward_id, assignment_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $shift, $room_id, $ward_id, $assignment_date]);
                $new_id = $pdo->lastInsertId();
                logAction($pdo, $_SESSION['user_id'], 'CREATE', $table, $new_id, $name, null, $new_data, $affected_fields);
                $success = "Staff added successfully.";
            }
            redirectWithMessage($success, '', "staff_panel.php");
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error in staff_panel.php: " . $e->getMessage());
        redirectWithMessage('', "A database error occurred. Please try again.", "staff_panel.php");
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirectWithMessage('', $e->getMessage(), "staff_panel.php");
    }
}

// Process DELETE requests
if (isset($_GET['delete']) && isset($_GET['type'])) {
    try {
        $id = (int)$_GET['delete'];
        $type = sanitizeInput($_GET['type']);
        if (!in_array($type, ['sn', 'om'])) {
            throw new Exception("Invalid staff type.");
        }
        $table = ($type === 'sn') ? 'staff_sn' : 'staff_om';
        $id_field = ($type === 'sn') ? 'sn_id' : 'om_id';
        $stmt = $pdo->prepare("SELECT staff_name FROM $table WHERE $id_field = ?");
        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$staff) {
            throw new Exception("Staff not found.");
        }
        $staff_name = $staff['staff_name'];
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to delete staff. It may have already been deleted.");
        }
        logAction($pdo, $_SESSION['user_id'], 'DELETE', $table, $id, $staff_name, ['staff_name' => $staff_name], null, null);
        redirectWithMessage("Staff deleted successfully.", '', "staff_panel.php");
    } catch (PDOException $e) {
        error_log("Database error in staff_panel.php (delete): " . $e->getMessage());
        redirectWithMessage('', "A database error occurred while deleting staff.", "staff_panel.php");
    } catch (Exception $e) {
        redirectWithMessage('', $e->getMessage(), "staff_panel.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/all.min.css" rel="stylesheet">
    <link href="../css/fontawesome.min.css" rel="stylesheet">
    <link href="../includes/styles.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../includes/scripts.js"></script>
</head>
<body>
<?php include_once "../includes/navbar.php"; ?>
<div class="container my-4">
    <h1>Staff Management</h1>
    
    <?php include_once "../includes/toast_notifications.php"; ?>
    
    <!-- Staff List Card -->
    <div class="card">
        <div class="card-header">
            <span>Staff List</span>
            <div>
                <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#staffModal">
                    <i class="fas fa-plus-circle"></i> Add Staff
                </button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkStaffModal">
                    <i class="fas fa-users"></i> Bulk Add Staff
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Shift</th>
                            <th>Room</th>
                            <th>Ward</th>
                            <th>Assignment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sn = $pdo->query("SELECT sn_id AS id, 'sn' AS type, staff_name, shift, room_name, ward_name, assignment_date, staff_sn.room_id, staff_sn.ward_id FROM staff_sn LEFT JOIN room_assignments ON staff_sn.room_id = room_assignments.room_id LEFT JOIN ward_assignments ON staff_sn.ward_id = ward_assignments.ward_id ORDER BY assignment_date DESC, staff_name");
                            $om = $pdo->query("SELECT om_id AS id, 'om' AS type, staff_name, shift, room_name, ward_name, assignment_date, staff_om.room_id, staff_om.ward_id FROM staff_om LEFT JOIN room_assignments ON staff_om.room_id = room_assignments.room_id LEFT JOIN ward_assignments ON staff_om.ward_id = ward_assignments.ward_id ORDER BY assignment_date DESC, staff_name");
                            $staff_list = array_merge($sn->fetchAll(), $om->fetchAll());
                            if (empty($staff_list)) {
                                echo "<tr><td colspan='7' class='text-center py-4'>No staff members found. Click 'Add Staff' to create one.</td></tr>";
                            } else {
                                foreach ($staff_list as $staff) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars(strtoupper($staff['type'])) . "</td>
                                        <td class='text-truncate'>" . htmlspecialchars($staff['staff_name']) . "</td>
                                        <td>" . htmlspecialchars($staff['shift']) . "</td>
                                        <td>" . ($staff['room_name'] ? htmlspecialchars($staff['room_name']) : '-') . "</td>
                                        <td>" . ($staff['ward_name'] ? htmlspecialchars($staff['ward_name']) : '-') . "</td>
                                        <td>" . htmlspecialchars($staff['assignment_date']) . "</td>
                                        <td class='actions'>
                                            <button class='btn btn-sm btn-warning me-1' onclick='editStaff(" . json_encode($staff) . ")' data-bs-toggle='modal' data-bs-target='#staffModal'>
                                                <i class='fas fa-edit'></i> Edit
                                            </button>
                                            <button class='btn btn-sm btn-danger' onclick='confirmDelete({$staff['id']}, \"{$staff['type']}\", \"" . htmlspecialchars($staff['staff_name'], ENT_QUOTES) . "\")' data-bs-toggle='modal' data-bs-target='#deleteStaffModal'>
                                                <i class='fas fa-trash-alt'></i> Delete
                                            </button>
                                        </td>
                                    </tr>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='7' class='text-center text-danger py-4'>Error loading staff: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="staffForm" method="POST" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staffModalLabel">Add Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="staff_name" class="form-label">Name <span class="red-star">*</span></label>
                            <input type="text" id="staff_name" name="staff_name" class="form-control" maxlength="191" required>
                            <div class="invalid-feedback">Please enter a valid staff name (max 191 characters).</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="shift" class="form-label">Shift <span class="red-star">*</span></label>
                            <select id="shift" name="shift" class="form-select" required>
                                <option value="" disabled selected>Select shift</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                            </select>
                            <div class="invalid-feedback">Please select a shift.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Type <span class="red-star">*</span></label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="sn">S/N</option>
                                <option value="om">O/M</option>
                            </select>
                            <div class="invalid-feedback">Please select a staff type.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="assignment_date" class="form-label">Assignment Date <span class="red-star">*</span></label>
                            <input type="date" id="assignment_date" name="assignment_date" class="form-control" required>
                            <div class="invalid-feedback">Please select a valid date (YYYY-MM-DD).</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="room_id" class="form-label">Room <span class="red-star">*</span></label>
                            <select id="room_id" name="room_id" class="form-select" required>
                                <option value="">Select Room</option>
                                <?php
                                try {
                                    $rooms = $pdo->query("SELECT room_id, room_name FROM room_assignments ORDER BY room_name")->fetchAll();
                                    foreach ($rooms as $room) {
                                        echo "<option value='{$room['room_id']}'>" . htmlspecialchars($room['room_name']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value='' disabled>Error loading rooms</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ward_id" class="form-label">Ward <span class="red-star">*</span></label>
                            <select id="ward_id" name="ward_id" class="form-select" required>
                                <option value="">Select Ward</option>
                                <?php
                                try {
                                    $wards = $pdo->query("SELECT ward_id, ward_name FROM ward_assignments ORDER BY ward_name")->fetchAll();
                                    foreach ($wards as $ward) {
                                        echo "<option value='{$ward['ward_id']}'>" . htmlspecialchars($ward['ward_name']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value='' disabled>Error loading wards</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Add Staff Modal -->
<div class="modal fade" id="bulkStaffModal" tabindex="-1" aria-labelledby="bulkStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="bulkStaffForm" method="POST" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkStaffModalLabel">Bulk Add Staff (4 S/N, 2 O/M)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bulk_add" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bulk_assignment_date" class="form-label">Assignment Date <span class="red-star">*</span></label>
                            <input type="date" id="bulk_assignment_date" name="bulk_assignment_date" class="form-control" required>
                            <div class="invalid-feedback">Please select a valid date (YYYY-MM-DD).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_shift" class="form-label">Shift <span class="red-star">*</span></label>
                            <select id="bulk_shift" name="bulk_shift" class="form-select" required>
                                <option value="" disabled selected>Select shift</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                            </select>
                            <div class="invalid-feedback">Please select a shift.</div>
                        </div>
                    </div>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="card mb-3">
                            <div class="card-header">Staff Member <?php echo $i; ?> (<?php echo $i <= 4 ? 'S/N' : 'O/M'; ?>)</div>
                            <div class="card-body">
                                <input type="hidden" name="staff_data[<?php echo $i-1; ?>][type]" value="<?php echo $i <= 4 ? 'sn' : 'om'; ?>">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="staff_name_<?php echo $i; ?>" class="form-label">Name <span class="red-star">*</span></label>
                                        <input type="text" id="staff_name_<?php echo $i; ?>" name="staff_data[<?php echo $i-1; ?>][staff_name]" class="form-control" maxlength="191" required>
                                        <div class="invalid-feedback">Please enter a valid staff name (max 191 characters).</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="room_id_<?php echo $i; ?>" class="form-label">Room <span class="red-star">*</span></label>
                                        <select id="room_id_<?php echo $i; ?>" name="staff_data[<?php echo $i-1; ?>][room_id]" class="form-select" required>
                                            <option value="">Select Room</option>
                                            <?php
                                            try {
                                                $rooms = $pdo->query("SELECT room_id, room_name FROM room_assignments ORDER BY room_name")->fetchAll();
                                                foreach ($rooms as $room) {
                                                    echo "<option value='{$room['room_id']}'>" . htmlspecialchars($room['room_name']) . "</option>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<option value='' disabled>Error loading rooms</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a room.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="ward_id_<?php echo $i; ?>" class="form-label">Ward <span class="red-star">*</span></label>
                                        <select id="ward_id_<?php echo $i; ?>" name="staff_data[<?php echo $i-1; ?>][ward_id]" class="form-select" required>
                                            <option value="">Select Ward</option>
                                            <?php
                                            try {
                                                $wards = $pdo->query("SELECT ward_id, ward_name FROM ward_assignments ORDER BY ward_name")->fetchAll();
                                                foreach ($wards as $ward) {
                                                    echo "<option value='{$ward['ward_id']}'>" . htmlspecialchars($ward['ward_name']) . "</option>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<option value='' disabled>Error loading wards</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a ward.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save All
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStaffModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteStaffName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone and will permanently remove this staff member.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    initializeFormValidation('staffForm', 'staffModal', 'staffModalLabel', 'Add Staff');
    initializeFormValidation('bulkStaffForm', 'bulkStaffModal', 'bulkStaffModalLabel', 'Bulk Add Staff (4 S/N, 2 O/M)');

    function editStaff(staff) {
        document.getElementById('staffModalLabel').textContent = 'Edit Staff';
        document.getElementById('edit_id').value = staff.id;
        document.getElementById('staff_name').value = staff.staff_name;
        document.getElementById('shift').value = staff.shift;
        document.getElementById('type').value = staff.type;
        document.getElementById('assignment_date').value = staff.assignment_date || '';
        document.getElementById('room_id').value = staff.room_id || '';
        document.getElementById('ward_id').value = staff.ward_id || '';
    }

    function confirmDelete(id, type, name) {
        document.getElementById('deleteStaffName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = `staff_panel.php?delete=${id}&type=${type}`;
    }

    // Client-side validation for unique room/ward combinations
    document.getElementById('bulkStaffForm').addEventListener('submit', function(event) {
        const roomSelects = document.querySelectorAll('[name*="room_id"]');
        const wardSelects = document.querySelectorAll('[name*="ward_id"]');
        const locations = new Set();
        let isValid = true;

        for (let i = 0; i < roomSelects.length; i++) {
            const roomId = roomSelects[i].value;
            const wardId = wardSelects[i].value;
            const locationKey = `${roomId}:${wardId}`;

            if (locations.has(locationKey)) {
                isValid = false;
                roomSelects[i].classList.add('is-invalid');
                wardSelects[i].classList.add('is-invalid');
                roomSelects[i].nextElementSibling.textContent = 'This room/ward combination is already used.';
                wardSelects[i].nextElementSibling.textContent = 'This room/ward combination is already used.';
            } else {
                locations.add(locationKey);
                roomSelects[i].classList.remove('is-invalid');
                wardSelects[i].classList.remove('is-invalid');
            }
        }

        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
</script>
</body>
</html>