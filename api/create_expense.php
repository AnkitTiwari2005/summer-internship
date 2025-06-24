<?php
// This PHP script handles the creation of new expense records for the logged-in user.

session_start(); // Start the session to access user info

// Include the database configuration file
require_once 'db_config.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to add transactions.']);
    exit;
}

$user_id = $_SESSION['id']; // Get the logged-in user's ID

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    $date = htmlspecialchars(trim($_POST['date'] ?? ''));

    // Server-side validation
    if (empty($description) || $amount === false || $amount <= 0 || empty($category) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Please provide valid description, amount, category, and date.']);
        exit;
    }

    // Prepare an SQL INSERT statement, including user_id
    $sql = "INSERT INTO expenses (user_id, description, amount, category, date) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters: 'isdss' for user_id(int), description(string), amount(double), category(string), date(string)
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
