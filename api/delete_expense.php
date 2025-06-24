<?php
// This PHP script handles the deletion of an expense record for the logged-in user.

session_start(); // Start the session to access user info

// Include the database configuration file
require_once 'db_config.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to delete transactions.']);
    exit;
}

$user_id = $_SESSION['id']; // Get the logged-in user's ID

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate the input ID
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    // Server-side validation for the ID
    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID provided.']);
        exit;
    }

    // Prepare an SQL DELETE statement, ensuring it's for the logged-in user's expense
    $sql = "DELETE FROM expenses WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters: 'ii' for ID(int), user_id(int)
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully.']);
            } else {
                // If 0 rows affected, either ID/user_id mismatch or already deleted
                echo json_encode(['success' => false, 'message' => 'Transaction not found or not owned by this user.']);
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
