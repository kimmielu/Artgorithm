<?php
// Show errors while developing (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'error' => 'Only POST is allowed'
    ]);
    exit;
}

// Include DB file (your Database.php is in the same folder)
require_once __DIR__ . '/Database.php';

// Get PDO connection from your Database class
try {
    // Assuming your Database.php defines class Database with getConnection()
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit;
}

// Read input (supports JSON and form-urlencoded)
$raw = file_get_contents('php://input');
$input = [];
$ct = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($ct, 'application/json') !== false) {
    $input = json_decode($raw, true) ?? [];
} else {
    // fallback to normal POST form fields
    $input = $_POST;
}

$fullname = trim($input['fullname'] ?? '');
$email    = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');
$errors   = [];

// Validation
if ($fullname === '')               $errors[] = 'Full name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (strlen($password) < 6)          $errors[] = 'Password must be at least 6 characters';

if ($errors) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check unique email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$fullname, $email, $hash]);

    $userId = (int)$pdo->lastInsertId();

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => 'User registered',
        'data' => [
            'id' => $userId,
            'fullname' => $fullname,
            'email' => $email
        ]
    ]);

} catch (PDOException $e) {
    // Duplicate email can also come here if the UNIQUE index triggers (SQLSTATE 23000)
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
    }
    exit;
}
