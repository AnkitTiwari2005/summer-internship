<?php
// This PHP script handles user logins.

// Start a PHP session. This is crucial for maintaining user login state across pages.
session_start();

// Include the database configuration file
require_once 'db_config.php'; // Corrected path

// Set the response header to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? ''; // Password to be verified

    // Server-side validation
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password cannot be empty.']);
        exit;
    }

    // Prepare an SQL SELECT statement to fetch the user's password hash
    $sql = "SELECT id, username, password_hash FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User found, fetch the row
            $user = $result->fetch_assoc();
            // Verify the provided password against the stored hash
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                echo json_encode(['success' => true, 'message' => 'Login successful!', 'username' => $user['username'], 'user_id' => $user['id']]);
            } else {
                // Password is incorrect
                echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            }
        } else {
            // User not found
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
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
