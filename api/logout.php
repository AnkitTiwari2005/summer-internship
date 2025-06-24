<?php
// This PHP script handles user logout.

// Start or resume the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page (or homepage)
header("Location: ../login.html"); // Redirect to your login page
exit;
?>
