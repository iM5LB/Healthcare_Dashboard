<?php
// Database configuration
$host = "localhost";
$db = "healthcare_staff"; // Replace with your database name
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

function insertUser($pdo, $username, $plainPassword) {
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            return "Error: Username '$username' already exists.";
        }

        // Hash the password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        // Insert user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);

        return "User '$username' inserted successfully.\nPlain-text password: $plainPassword\nHashed password: $hashedPassword";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

// Insert a test user
$username = 'admin';
$plainPassword = 'admin123';
echo insertUser($pdo, $username, $plainPassword) . "\n";

// Display all users for verification
try {
    echo "\nCurrent Users:\n";
    $stmt = $pdo->query("SELECT user_id, username FROM users");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['user_id']}, Username: {$row['username']}\n";
    }
} catch (PDOException $e) {
    echo "Error fetching users: " . $e->getMessage() . "\n";
}
?>