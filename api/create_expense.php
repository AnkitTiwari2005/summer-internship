<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to add transactions.']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    $date = htmlspecialchars(trim($_POST['date'] ?? ''));

    if (empty($description) || $amount === false || $amount <= 0 || empty($category) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Please provide valid description, amount, category, and date.']);
        exit;
    }

    $sql = "INSERT INTO expenses (user_id, description, amount, category, date) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isdss", $user_id, $description, $amount, $category, $date);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Expense added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
