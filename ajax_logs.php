<?php
// Start output buffering
ob_start();

// Set error reporting to avoid outputting warnings/errors to the response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include_once "includes/config.php";
include_once "includes/functions.php";

// Ensure Content-Type is set before any output
header('Content-Type: application/json');

// Clear any previous output
ob_clean();

try {
    // Ensure sanitizeInput is defined
    if (!function_exists('sanitizeInput')) {
        function sanitizeInput($input) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }

    $search = isset($_GET['search']) ? sanitizeInput(trim($_GET['search'])) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 10;
    $offset = ($page - 1) * $per_page;

    $conditions = array();
    $params = array();
    
    if ($search) {
        $conditions[] = "(
            u.username LIKE :search1 OR 
            l.action_type LIKE :search2 OR 
            l.table_name LIKE :search3 OR 
            l.record_name LIKE :search4 OR 
            l.affected_fields LIKE :search5 OR 
            u.role LIKE :search6
        )";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
        $params[':search4'] = "%$search%";
        $params[':search5'] = "%$search%";
        $params[':search6'] = "%$search%";
        
        if (is_numeric($search)) {
            $conditions[] = "(l.user_id = :search_id OR l.record_id = :search_record)";
            $params[':search_id'] = (int)$search;
            $params[':search_record'] = (int)$search;
        }
    }

    $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Count total records
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM actions_logs l JOIN users u ON l.user_id = u.user_id $where");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Fetch logs
    $logsQuery = $pdo->prepare("
        SELECT l.id, l.user_id, l.action_type, l.table_name, l.record_id, l.record_name, l.affected_fields, l.details, l.created_at, u.role, u.username
        FROM actions_logs l
        JOIN users u ON l.user_id = u.user_id
        $where
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $value) {
        $logsQuery->bindValue($key, $value);
    }
    $logsQuery->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $logsQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
    $logsQuery->execute();
    $logs = $logsQuery->fetchAll(PDO::FETCH_ASSOC);

    $output = '';
    if (empty($logs)) {
        $output .= "<tr><td colspan='8' class='text-center'>No action logs found</td></tr>";
    } else {
        foreach ($logs as $log) {
            $details = json_decode($log['details'], true);
            if ($details === null) {
                $details = array();
            }
            $old_value = '-';
            $new_value = '-';

            if ($log['action_type'] === 'UPDATE' && isset($details['changes']) && is_array($details['changes'])) {
                $old_values = array();
                $new_values = array();
                foreach ($details['changes'] as $field => $change) {
                    $old_val = isset($change['old']) ? $change['old'] : 'None';
                    $new_val = isset($change['new']) ? $change['new'] : 'None';
                    $old_display = is_null($old_val) ? 'None' : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $old_val) ? date('M d, Y', strtotime($old_val)) : htmlspecialchars($old_val));
                    $new_display = is_null($new_val) ? 'None' : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_val) ? date('M d, Y', strtotime($new_val)) : htmlspecialchars($new_val));
                    if ($old_display !== $new_display) {
                        $old_values[] = "$field: $old_display";
                        $new_values[] = "$field: $new_display";
                    }
                }
                $old_value = empty($old_values) ? '-' : implode(', ', $old_values);
                $new_value = empty($new_values) ? '-' : implode(', ', $new_values);
            } elseif ($log['action_type'] === 'DELETE') {
                $old_value = $log['record_name'] ? htmlspecialchars($log['record_name']) : 'Unknown';
            } elseif ($log['action_type'] === 'CREATE') {
                $new_value = $log['record_name'] ? htmlspecialchars($log['record_name']) : 'Unknown';
            }

            $output .= "<tr>
                <td>" . htmlspecialchars($log['action_type']) . "</td>
                <td>" . htmlspecialchars($log['username']) . " (" . htmlspecialchars($log['role']) . ")</td>
                <td>" . htmlspecialchars($log['table_name']) . "</td>
                <td>" . ($log['record_name'] ? htmlspecialchars($log['record_name']) : 'Unknown') . "</td>
                <td>" . ($log['affected_fields'] ? htmlspecialchars($log['affected_fields']) : '-') . "</td>
                <td>" . $old_value . "</td>
                <td>" . $new_value . "</td>
                <td>" . date('Y-m-d h:i A', strtotime($log['created_at'])) . "</td>
            </tr>";
        }
    }

    $json_output = json_encode(array(
        'output' => $output,
        'total_pages' => $total_pages,
        'total_records' => $total_records
    ));
    if ($json_output === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg());
    }
    echo $json_output;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'error' => true,
        'message' => "Database error occurred",
        'output' => "<tr><td colspan='8' class='text-danger'>System error: Database operation failed</td></tr>",
        'total_pages' => 1,
        'total_records' => 0
    ));
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'error' => true,
        'message' => htmlspecialchars($e->getMessage()),
        'output' => "<tr><td colspan='8' class='text-danger'>System error: " . htmlspecialchars($e->getMessage()) . "</td></tr>",
        'total_pages' => 1,
        'total_records' => 0
    ));
}

// Flush output buffer
ob_end_flush();
?>