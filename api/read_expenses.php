<?php
// This PHP script handles reading expense records for the logged-in user, with optional filters.

session_start(); // Start the session to access user info

// Include the database configuration file
require_once 'db_config.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to view transactions.']);
    exit;
}

$user_id = $_SESSION['id']; // Get the logged-in user's ID

// Get filter parameters from GET request
$filterCategory = htmlspecialchars(trim($_GET['category'] ?? 'all'));
$filterType = htmlspecialchars(trim($_GET['type'] ?? 'all'));
$filterStartDate = htmlspecialchars(trim($_GET['startDate'] ?? ''));
$filterEndDate = htmlspecialchars(trim($_GET['endDate'] ?? ''));

// Start building the SQL query, always filtering by user_id
$sql = "SELECT id, description, amount, category, date FROM expenses WHERE user_id = ?";
$params = [$user_id]; // user_id is always the first parameter
$types = "i";         // 'i' for integer (user_id)

// Add Category filter
if ($filterCategory !== 'all' && !empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= "s"; // 's' for string
}

// Add Type filter (Income/Expense)
if ($filterType !== 'all' && !empty($filterType)) {
    if ($filterType === 'Income') {
        $sql .= " AND category = 'Salary'"; // Assuming 'Salary' is the only income category
    } else if ($filterType === 'Expense') {
        $sql .= " AND category != 'Salary'";
    }
}

// Add Date Range filters
if (!empty($filterStartDate)) {
    $sql .= " AND date >= ?";
    $params[] = $filterStartDate;
    $types .= "s"; // 's' for string
}
if (!empty($filterEndDate)) {
    $sql .= " AND date <= ?";
    $params[] = $filterEndDate;
    $types .= "s"; // 's' for string
}

// Add ordering
$sql .= " ORDER BY date DESC, id DESC";

// Prepare the statement
if ($stmt = $conn->prepare($sql)) {
    // Use call_user_func_array to bind parameters dynamically
    // The first argument to bind_param is the types string, followed by parameters
    $stmt->bind_param($types, ...$params);

    // Execute the statement
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
