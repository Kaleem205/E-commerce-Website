<?php
require '../includes/db.php';

// Get the typed letters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query === '') {
    echo json_encode([]);
    exit;
}

// Add the % wildcard to the END so it searches for things "starting with" those letters
$searchTerm = $query . '%'; 

$stmt = $conn->prepare("SELECT id, name, image FROM products WHERE name LIKE ? LIMIT 5");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Return the result as JSON data
header('Content-Type: application/json');
echo json_encode($result);
?>