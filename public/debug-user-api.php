<?php
// Test script to help diagnose user management API issues

header('Content-Type: application/json');

// Allow cross-origin requests during testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = isset($_GET['items']) ? (int)$_GET['items'] : 10;

// Simulate a successful response for testing with more realistic data
$mockUsers = [
    [
        'id_user' => 1,
        'name' => 'Test User 1',
        'email' => 'test1@example.com', 
        'phone_number' => '123456789',
        'role' => 'ADMIN',
        'account_status' => 'ACTIVE',
        'gender' => 'MALE',
        'is_verified' => true,
        'profile_picture' => '/images/default-avatar.png',
        'date_of_birth' => '1990-01-01',
        'status' => 'OFFLINE'
    ],
    [
        'id_user' => 2,
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
        'phone_number' => '987654321',
        'role' => 'CLIENT',
        'account_status' => 'SUSPENDED',
        'gender' => 'FEMALE',
        'is_verified' => false,
        'profile_picture' => null,
        'date_of_birth' => '1995-05-15',
        'status' => 'ONLINE'
    ],
    [
        'id_user' => 3,
        'name' => 'Test User 3',
        'email' => 'test3@example.com',
        'phone_number' => '555111222',
        'role' => 'CLIENT',
        'account_status' => 'ACTIVE',
        'gender' => 'MALE',
        'is_verified' => true,
        'profile_picture' => '/images/default-avatar.png',
        'date_of_birth' => '1988-11-22',
        'status' => 'ONLINE'
    ],
    [
        'id_user' => 4,
        'name' => 'Test User 4',
        'email' => 'test4@example.com',
        'phone_number' => '777888999',
        'role' => 'DRIVER',
        'account_status' => 'BANNED',
        'gender' => 'MALE',
        'is_verified' => true,
        'profile_picture' => '/images/default-avatar.png',
        'date_of_birth' => '1992-03-17',
        'status' => 'OFFLINE'
    ],
    [
        'id_user' => 5,
        'name' => 'Test User 5',
        'email' => 'test5@example.com',
        'phone_number' => '333444555',
        'role' => 'CLIENT',
        'account_status' => 'ACTIVE',
        'gender' => 'FEMALE',
        'is_verified' => true,
        'profile_picture' => '/images/default-avatar.png',
        'date_of_birth' => '1997-07-30',
        'status' => 'ONLINE'
    ]
];

// Calculate statistics
$stats = [
    'total' => count($mockUsers),
    'active' => 0,
    'suspended' => 0,
    'banned' => 0
];

foreach ($mockUsers as $user) {
    if ($user['account_status'] === 'ACTIVE') {
        $stats['active']++;
    } elseif ($user['account_status'] === 'SUSPENDED') {
        $stats['suspended']++;
    } elseif ($user['account_status'] === 'BANNED') {
        $stats['banned']++;
    }
}

// Return a mock API response
echo json_encode([
    'users' => $mockUsers,
    'total' => count($mockUsers),
    'page' => $page,
    'pages' => 1,
    'itemsPerPage' => $itemsPerPage,
    'stats' => $stats,
    'filters' => [
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'role' => isset($_GET['role']) ? $_GET['role'] : '',
        'status' => isset($_GET['status']) ? $_GET['status'] : '',
        'verified' => isset($_GET['verified']) ? $_GET['verified'] : ''
    ]
]);
