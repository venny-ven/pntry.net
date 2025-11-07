<?php

// Executes when user saves their inventory in inventory.php
// This file runs entirely in background, user remains on inventory page
// Receives JSON of inventory, updates qunatity values in ingredients table of the database
// Returns a JSON with a boolean for sucess and failure, and forwards an error message if it occured

require_once 'db.php';
$pdo = getPDO();
$success = true;
$message = '';

// Get content of HTTP Post request. Because I sent JSON, $_POST is empty, the payload needs to be accessed raw
$input = file_get_contents('php://input');

// Reverse of JSON.stringify(), true because array is associative. This returns an associative array
$ingredients = json_decode($input, true);

// Update DB
try {
    $stmt = $pdo->prepare("UPDATE ingredient SET quantity = :quantity WHERE id = :id");
    foreach ($ingredients as $item) {
	$stmt->execute(['quantity' => $item['quantity'], 'id' => $item['id']]);
    }
} catch (PDOException $ex) {
    $success = false;
    $message = $ex->getMessage();
}

// Respond
echo json_encode(['success' => $success, 'message' => $message]);