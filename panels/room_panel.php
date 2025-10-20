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

// Process POST requests (add/edit room)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
        $room_name = sanitizeInput($_POST['room_name']);
        
        if (empty($room_name)) {
            throw new Exception("Room name is required.");
        } elseif (strlen($room_name) > 191) {
            throw new Exception("Room name cannot exceed 191 characters.");
        }

        // Check for duplicate name
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_assignments WHERE room_name = ? AND room_id != ?");
        $stmt->execute([$room_name, $id ?: 0]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Room name already exists.");
        }

        if ($id) {
            // Update existing room
            $stmt = $pdo->prepare("SELECT room_name FROM room_assignments WHERE room_id = ?");
            $stmt->execute([$id]);
            $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("UPDATE room_assignments SET room_name = ? WHERE room_id = ?");
            $stmt->execute([$room_name, $id]);
            logAction($pdo, $_SESSION['user_id'], 'UPDATE', 'room_assignments', $id, $room_name, ['room_name' => $old_data['room_name']], ['room_name' => $room_name], 'room_name');
            $success = "Room updated successfully.";
        } else {
            // Create new room
            $stmt = $pdo->prepare("INSERT INTO room_assignments (room_name) VALUES (?)");
            $stmt->execute([$room_name]);
            $new_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'CREATE', 'room_assignments', $new_id, $room_name, null, ['room_name' => $room_name], 'room_name');
            $success = "Room added successfully.";
        }
        
        redirectWithMessage($success, '', "room_panel.php");
        
    } catch (PDOException $e) {
        error_log("Database error in room_panel.php: " . $e->getMessage());
        redirectWithMessage('', "A database error occurred. Please try again.", "room_panel.php");
    } catch (Exception $e) {
        redirectWithMessage('', $e->getMessage(), "room_panel.php");
    }
}

// Process DELETE requests
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Check if room is assigned to staff
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_sn WHERE room_id = ?");
        $stmt->execute([$id]);
        $sn_count = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SHIPCOUNT(*) FROM staff_om WHERE room_id = ?");
        $stmt->execute([$id]);
        $om_count = $stmt->fetchColumn();

        if ($sn_count > 0 || $om_count > 0) {
            throw new Exception("Cannot delete room because it is assigned to staff.");
        }

        // Get room details for logging
        $stmt = $pdo->prepare("SELECT room_name FROM room_assignments WHERE room_id = ?");
        $stmt->execute([$id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            throw new Exception("Room not found.");
        }
        
        $room_name = $room['room_name'];
        
        // Delete the room
        $stmt = $pdo->prepare("DELETE FROM room_assignments WHERE room_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to delete room. It may have already been deleted.");
        }
        
        logAction($pdo, $_SESSION['user_id'], 'DELETE', 'room_assignments', $id, $room_name, ['room_name' => $room_name], null, null);
        redirectWithMessage("Room deleted successfully.", '', "room_panel.php");
        
    } catch (PDOException $e) {
        error_log("Database error in room_panel.php (delete): " . $e->getMessage());
        redirectWithMessage('', "A database error occurred while deleting room.", "room_panel.php");
    } catch (Exception $e) {
        redirectWithMessage('', $e->getMessage(), "room_panel.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
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
    <h1>Room Management</h1>
    
    <?php include_once "../includes/toast_notifications.php"; ?>
    
    <!-- Room List Card -->
    <div class="card">
        <div class="card-header">
            <span>Room List</span>
            <div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roomModal">
                    <i class="fas fa-plus-circle"></i> Add Room
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $rooms = $pdo->query("SELECT room_id, room_name FROM room_assignments ORDER BY room_name")->fetchAll();
                            if (empty($rooms)) {
                                echo "<tr><td colspan='2' class='text-center py-4'>No rooms found. Click 'Add Room' to create one.</td></tr>";
                            } else {
                                foreach ($rooms as $room) {
                                    echo "<tr>
                                        <td class='text-truncate'>" . htmlspecialchars($room['room_name']) . "</td>
                                        <td class='actions'>
                                            <button class='btn btn-sm btn-warning me-1' 
                                                onclick='editRoom({$room['room_id']}, \"" . htmlspecialchars($room['room_name'], ENT_QUOTES) . "\")' 
                                                data-bs-toggle='modal' 
                                                data-bs-target='#roomModal'>
                                                <i class='fas fa-edit'></i> Edit
                                            </button>
                                            <button class='btn btn-sm btn-danger' 
                                                onclick='confirmDelete({$room['room_id']}, \"" . htmlspecialchars($room['room_name'], ENT_QUOTES) . "\")' 
                                                data-bs-toggle='modal' 
                                                data-bs-target='#deleteRoomModal'>
                                                <i class='fas fa-trash-alt'></i> Delete
                                            </button>
                                        </td>
                                    </tr>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='2' class='text-center text-danger py-4'>Error loading rooms: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="roomForm" method="POST" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">Add Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="room_name" class="form-label">Room Name <span class="red-star">*</span></label>
                            <input type="text" id="room_name" name="room_name" class="form-control" maxlength="191" required>
                            <div class="invalid-feedback">Please enter a valid room name (max 191 characters).</div>
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
<div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRoomModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteRoomName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone and will permanently remove this room.</small></p>
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
    initializeFormValidation('roomForm', 'roomModal', 'roomModalLabel', 'Add Room');

    function editRoom(id, room_name) {
        document.getElementById('roomModalLabel').textContent = 'Edit Room';
        document.getElementById('edit_id').value = id;
        document.getElementById('room_name').value = room_name;
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteRoomName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = `room_panel.php?delete=${id}`;
    }
</script>
</body>
</html>