<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to update transactions.']);
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));

    if ($id === false || empty($description) || $amount === false || $amount <= 0 || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input for update. Please provide valid ID, description, amount, and category.']);
        exit;
    }

    $sql = "UPDATE expenses SET description = ?, amount = ?, category = ? WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdsii", $description, $amount, $category, $id, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Transaction updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or transaction not found for this user.']);
            }
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
