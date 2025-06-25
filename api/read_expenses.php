<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to view transactions.']);
    exit;
}

$user_id = $_SESSION['id'];
$filterCategory = htmlspecialchars(trim($_GET['category'] ?? 'all'));
$filterType = htmlspecialchars(trim($_GET['type'] ?? 'all'));
$filterStartDate = htmlspecialchars(trim($_GET['startDate'] ?? ''));
$filterEndDate = htmlspecialchars(trim($_GET['endDate'] ?? ''));

$sql = "SELECT id, description, amount, category, date FROM expenses WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filterCategory !== 'all' && !empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}

if ($filterType !== 'all' && !empty($filterType)) {
    if ($filterType === 'Income') {
        $sql .= " AND category = 'Salary'";
    } else if ($filterType === 'Expense') {
        $sql .= " AND category != 'Salary'";
    }
}

if (!empty($filterStartDate)) {
    $sql .= " AND date >= ?";
    $params[] = $filterStartDate;
    $types .= "s";
}

if (!empty($filterEndDate)) {
    $sql .= " AND date <= ?";
    $params[] = $filterEndDate;
    $types .= "s";
}

$sql .= " ORDER BY date DESC, id DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $expenses]);
        } else {
            echo json_encode(['success' => true, 'data' => [], 'message' => 'No expenses found.']);
        }
        $result->free();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
}

$conn->close();
?>
