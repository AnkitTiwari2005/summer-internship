<?php
require_once 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password cannot be empty.']);
        exit;
    }

    $sql_check = "SELECT id FROM users WHERE username = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose a different one.']);
            $stmt_check->close();
            $conn->close();
            exit;
        }
        $stmt_check->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error during username check: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $sql_insert = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
    
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $stmt_insert->bind_param("ss", $username, $password_hash);

        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt_insert->error]);
        }
        $stmt_insert->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
