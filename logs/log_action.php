<?php
include_once "../includes/config.php";

function logAction($pdo, $user_id, $action_type, $table_name, $record_id = null, $record_name = null, $old_data = null, $new_data = null, $affected_fields = null) {
    try {
        // Validate required parameters
        if (empty($user_id)) {
            throw new Exception("User ID is required for audit logging.");
        }
        if (empty($action_type)) {
            throw new Exception("Action type is required for audit logging.");
        }
        if (empty($table_name)) {
            throw new Exception("Table name is required for audit logging.");
        }
        if (!in_array($action_type, ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'])) {
            throw new Exception("Invalid action type: $action_type. Must be CREATE, UPDATE, DELETE, LOGIN, or LOGOUT.");
        }

        // Check if user_id exists
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception("User with ID $user_id not found in users table.");
        }
        $username = $user['username'];
        $role = $user['role'];

        // Prepare details array
        $details = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'username' => $username,
            'role' => $role
        ];

        // Process changes for UPDATE actions
        if ($action_type === 'UPDATE' && $old_data && $new_data) {
            $changes = [];
            foreach ($new_data as $key => $newValue) {
                if (array_key_exists($key, $old_data)) {
                    $oldValue = $old_data[$key];
                    // Convert null to empty string for comparison
                    $oldValue = $oldValue === null ? '' : $oldValue;
                    $newValue = $newValue === null ? '' : $newValue;
                    if ($oldValue !== $newValue) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                    }
                }
            }
            if (!empty($changes)) {
                $details['changes'] = $changes;
            }
        }

        if ($record_name) {
            $details['record_name'] = $record_name;
        }

        $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if ($detailsJson === false) {
            throw new Exception("Failed to encode details JSON: " . json_last_error_msg());
        }

        // Insert log entry
        $stmt = $pdo->prepare("
            INSERT INTO actions_logs (user_id, action_type, table_name, record_id, record_name, affected_fields, details)
            VALUES (:user_id, :action_type, :table_name, :record_id, :record_name, :affected_fields, :details)
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':action_type' => $action_type,
            ':table_name' => $table_name,
            ':record_id' => $record_id,
            ':record_name' => $record_name,
            ':affected_fields' => $affected_fields,
            ':details' => $detailsJson
        ]);

        return true;
    } catch (Exception $e) {
        $errorInfo = $pdo->errorInfo();
        error_log("Audit log failed: " . $e->getMessage() . " | User ID: $user_id | Action: $action_type | Table: $table_name | SQL Error: " . json_encode($errorInfo));
        throw new Exception("Failed to log action: " . $e->getMessage());
    }
}
?>