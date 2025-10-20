<?php
date_default_timezone_set('Asia/Muscat');
include_once "../includes/config.php";
include_once "../logs/log_action.php";

// Initialize username to avoid undefined variable notice
$username = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../admin_dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                logAction($pdo, $user['user_id'], 'LOGIN', 'users', $user['user_id'], $username, null, null, null);
                header("Location: ../admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nizwa Hospital Day Care Unit</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/all.min.css" rel="stylesheet">
    <link href="../css/fontawesome.min.css" rel="stylesheet">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f4f7fa;
            color: rgb(59, 65, 72);
            display: flex;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .card-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #2c3e50;
            border-color: rgb(66, 92, 119);
        }
        .btn-primary:hover {
            background-color: rgb(68, 97, 125);
        }
        .form-control {
            border-color: #2c3e50;
        }
        .form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
        }
        .alert-danger {
            background-color: #e74c3c;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <h2 class="card-title text-center">Login</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input name="username" type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>