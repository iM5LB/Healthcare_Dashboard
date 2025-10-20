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

// Process POST requests (add/edit ward)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
        $ward_name = sanitizeInput($_POST['ward_name']);
        
        if (empty($ward_name)) {
            throw new Exception("Ward name is required.");
        } elseif (strlen($ward_name) > 191) {
            throw new Exception("Ward name cannot exceed 191 characters.");
        }

        // Check for duplicate name
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ward_assignments WHERE ward_name = ? AND ward_id != ?");
        $stmt->execute([$ward_name, $id ?: 0]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ward name already exists.");
        }

        if ($id) {
            // Update existing ward
            $stmt = $pdo->prepare("SELECT ward_name FROM ward_assignments WHERE ward_id = ?");
            $stmt->execute([$id]);
            $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("UPDATE ward_assignments SET ward_name = ? WHERE ward_id = ?");
            $stmt->execute([$ward_name, $id]);
            logAction($pdo, $_SESSION['user_id'], 'UPDATE', 'ward_assignments', $id, $ward_name, ['ward_name' => $old_data['ward_name']], ['ward_name' => $ward_name], 'ward_name');
            $success = "Ward updated successfully.";
        } else {
            // Create new ward
            $stmt = $pdo->prepare("INSERT INTO ward_assignments (ward_name) VALUES (?)");
            $stmt->execute([$ward_name]);
            $new_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'CREATE', 'ward_assignments', $new_id, $ward_name, null, ['ward_name' => $ward_name], 'ward_name');
            $success = "Ward added successfully.";
        }
        
        redirectWithMessage($success, '', "ward_panel.php");
        
    } catch (PDOException $e) {
        error_log("Database error in ward_panel.php: " . $e->getMessage());
        redirectWithMessage('', "A database error occurred. Please try again.", "ward_panel.php");
    } catch (Exception $e) {
        redirectWithMessage('', $e->getMessage(), "ward_panel.php");
    }
}

// Process DELETE requests
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Check if ward is assigned to staff
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_sn WHERE ward_id = ?");
        $stmt->execute([$id]);
        $sn_count = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_om WHERE ward_id = ?");
        $stmt->execute([$id]);
        $om_count = $stmt->fetchColumn();

        if ($sn_count > 0 || $om_count > 0) {
            throw new Exception("Cannot delete ward because it is assigned to staff.");
        }

        // Get ward details for logging
        $stmt = $pdo->prepare("SELECT ward_name FROM ward_assignments WHERE ward_id = ?");
        $stmt->execute([$id]);
        $ward = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ward) {
            throw new Exception("Ward not found.");
        }
        
        $ward_name = $ward['ward_name'];
        
        // Delete the ward
        $stmt = $pdo->prepare("DELETE FROM ward_assignments WHERE ward_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to delete ward. It may have already been deleted.");
        }
        
        logAction($pdo, $_SESSION['user_id'], 'DELETE', 'ward_assignments', $id, $ward_name, ['ward_name' => $ward_name], null, null);
        redirectWithMessage("Ward deleted successfully.", '', "ward_panel.php");
        
    } catch (PDOException $e) {
        error_log("Database error in ward_panel.php (delete): " . $e->getMessage());
        redirectWithMessage('', "A database error occurred while deleting ward.", "ward_panel.php");
    } catch (Exception $e) {
        redirectWithMessage('', $e->getMessage(), "ward_panel.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward Management</title>
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
    <h1>Ward Management</h1>
    
    <?php include_once "../includes/toast_notifications.php"; ?>
    
    <!-- Ward List Card -->
    <div class="card">
        <div class="card-header">
            <span>Ward List</span>
            <div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#wardModal">
                    <i class="fas fa-plus-circle"></i> Add Ward
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ward Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $wards = $pdo->query("SELECT ward_id, ward_name FROM ward_assignments ORDER BY ward_name")->fetchAll();
                            if (empty($wards)) {
                                echo "<tr><td colspan='2' class='text-center py-4'>No wards found. Click 'Add Ward' to create one.</td></tr>";
                            } else {
                                foreach ($wards as $ward) {
                                    echo "<tr>
                                        <td class='text-truncate'>" . htmlspecialchars($ward['ward_name']) . "</td>
                                        <td class='actions'>
                                            <button class='btn btn-sm btn-warning me-1' 
                                                onclick='editWard({$ward['ward_id']}, \"" . htmlspecialchars($ward['ward_name'], ENT_QUOTES) . "\")' 
                                                data-bs-toggle='modal' 
                                                data-bs-target='#wardModal'>
                                                <i class='fas fa-edit'></i> Edit
                                            </button>
                                            <button class='btn btn-sm btn-danger' 
                                                onclick='confirmDelete({$ward['ward_id']}, \"" . htmlspecialchars($ward['ward_name'], ENT_QUOTES) . "\")' 
                                                data-bs-toggle='modal' 
                                                data-bs-target='#deleteWardModal'>
                                                <i class='fas fa-trash-alt'></i> Delete
                                            </button>
                                        </td>
                                    </tr>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='2' class='text-center text-danger py-4'>Error loading wards: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Ward Modal -->
<div class="modal fade" id="wardModal" tabindex="-1" aria-labelledby="wardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="wardForm" method="POST" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wardModalLabel">Add Ward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="ward_name" class="form-label">Ward Name <span class="red-star">*</span></label>
                            <input type="text" id="ward_name" name="ward_name" class="form-control" maxlength="191" required>
                            <div class="invalid-feedback">Please enter a valid ward name (max 191 characters).</div>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteWardModal" tabindex="-1" aria-labelledby="deleteWardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteWardModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-book">
                <p>Are you sure you want to delete <strong id="deleteWardName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone and will permanently remove this ward.</small></p>
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
    initializeFormValidation('wardForm', 'wardModal', 'wardModalLabel', 'Add Ward');

    function editWard(id, ward_name) {
        document.getElementById('wardModalLabel').textContent = 'Edit Ward';
        document.getElementById('edit_id').value = id;
        document.getElementById('ward_name').value = ward_name;
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteWardName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = `ward_panel.php?delete=${id}`;
    }
</script>
</body>
</html>