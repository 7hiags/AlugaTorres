<?php
// Debug file to test admin access
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Admin Access</h1>";

// Test 1: Check if session works
echo "<h2>Test 1: Session Status</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "Session started successfully<br>";
} else {
    echo "Session already active<br>";
}

// Test 2: Check session variables
echo "<h2>Test 2: Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test 3: Try to include check_admin.php with error catching
echo "<h2>Test 3: Including check_admin.php</h2>";
try {
    require_once __DIR__ . '/../backend/check_admin.php';
    echo "check_admin.php included successfully!<br>";
    echo "Admin ID: " . $admin_id . "<br>";
    echo "Admin Nome: " . $admin_nome . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if we can connect to database
echo "<h2>Test 4: Database Connection</h2>";
try {
    require_once __DIR__ . '/../backend/db.php';
    if ($conn) {
        echo "Database connected successfully!<br>";
    } else {
        echo "Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<a href='index.php'>Try accessing admin index</a>";
