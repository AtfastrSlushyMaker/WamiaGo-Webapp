<?php

// This is a small script to help debug authentication issues
echo '<h1>Authentication Debug</h1>';
echo '<pre>';

// Check if a session is started
echo "Session status: ";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "ACTIVE\n";
} else {
    echo "NOT ACTIVE\n";
    session_start();
    echo "Session started now.\n";
}

// Display session content
echo "\nSession content:\n";
print_r($_SESSION);

// Display cookies
echo "\nCookies:\n";
print_r($_COOKIE);

echo '</pre>';
echo '<p><a href="/admin/users-management">Go back to User Management</a></p>';
