<?php
// This PHP script handles updating an existing expense record for the logged-in user.

session_start(); // Start the session to access user info

// Include the database configuration file
require_once 'db_config.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to update transactions.']);
    exit;
}

$user_id = $_SESSION['id']; // Get the logged-in user's ID

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT); // ID of the expense to update
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = htmlspecialchars(trim($_POST['category'] ?? ''));
    // Date is not directly editable in the current UI, so it's not expected here,
    // but if added, it would be $_POST['date'].

    // Server-side validation
    if ($id === false || empty($description) || $amount === false || $amount <= 0 || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input for update. Please provide valid ID, description, amount, and category.']);
        exit;
    }

    // Prepare an SQL UPDATE statement, ensuring it's for the logged-in user's expense
    $sql = "UPDATE expenses SET description = ?, amount = ?, category = ? WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters: 'sdsii' for description(s), amount(d), category(s), id(int), user_id(int)
        $stmt->bind_param("sdsii", $description, $amount, $category, $id, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Transaction updated successfully.']);
            } else {
                // If 0 rows affected, either ID/user_id mismatch or no changes were made
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
