<?php

// Executes when user saves their inventory in inventory.php
// This file runs entirely in background, user remains on inventory page
// Receives JSON of ingredients, strips the empty ones, and updates instance entries
// Returns a JSON with a boolean for success and failure, and forwards an error message if it occured

require_once 'db.php';
$pdo = getPDO();

require_once 'session.php';
$session_id = getSessionId();

// Pre-emptively set default return values
$success = true;
$message = '';

// Get content of HTTP Post request. Because I sent JSON, $_POST is empty, the payload needs to be accessed raw
$input = file_get_contents('php://input');

// Reverse of JSON.stringify(), true because array is associative. This returns an associative array
$ingredients = json_decode($input, true);

// Remove ingredients with 0 quantity
$instances = array_filter($ingredients, fn($item) => $item['quantity'] > 0);



// --------- Consruct two maps to compare changes against original, and for each change perform an SQL query ---------


try {
    // Map of changes
    $changesMap = [];
    foreach ($instances as $item) {
	$changesMap[$item['id']] = $item;
    }

    // Map of originals
    $stmt = $pdo->prepare
    ("
	SELECT ingredient_id AS id, acquire_date, quantity
	FROM instance
	WHERE session_id = :session_id
    ");
    $stmt->execute([':session_id' => $session_id]);
    $originals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $originalsMap = [];
    foreach ($originals as $item) {
	$originalsMap[$item['id']] = $item;
    }

    // Compare and delete negative changes
    $itemsToDelete = array_diff(array_keys($originalsMap), array_keys($changesMap));
    if ($itemsToDelete) {
	// Create an array of characters '?' of the same size as itemsToDelete
	// Turn that into a string where each character is separated by a comma
	// The question marks are part of how PDO prepared statements handle wildcard entries
	$placeholders = implode(',', array_fill(0, count($itemsToDelete), '?'));
	// Add one more '?' for session ID, this comes before the placeholders string
	$stmt = $pdo->prepare("DELETE FROM instance WHERE session_id = ? AND ingredient_id IN ($placeholders)");
	// On execute the elements inside itemsToDelete go in place of question marks
	// But the first question mark is a session ID, so "push" session ID into itemsToDelete array using array_merge()
	$stmt->execute(array_merge([$session_id], $itemsToDelete));
    }

    // Insert and update positive changes, no comparison needed
    $stmt = $pdo->prepare
    ('
	INSERT INTO instance (session_id, ingredient_id, acquire_date, quantity)
	VALUES (:session_id, :ingredient_id, CURRENT_TIMESTAMP, :quantity)
	ON DUPLICATE KEY UPDATE
	    quantity = VALUES(quantity),
	    acquire_date = VALUES(acquire_date)
    ');
    foreach ($changesMap as $id => $item) { // $id and $item are variables given to me to access this iteration's key and value
	$stmt->execute
	([
	    ':session_id' => $session_id,
	    ':ingredient_id' => $id,
	    ':quantity' => $item['quantity']
	]);
    }
} catch (PDOException $ex) {
    $success = false;
    $message = $ex->getMessage();
}

//// Update DB
//try {
//    // acquire_date only changes if the quantity used to be 0 but is no longer
//    // quantity and new_quantity are the same but must be separate variables because no repetition rule
//    $stmt = $pdo->prepare
//    ("
//	UPDATE ingredient
//	SET
//	    acquire_date = CASE WHEN quantity = 0 AND :new_quantity != 0 THEN NOW() ELSE acquire_date END,
//	    quantity = :quantity
//	WHERE id = :id
//    ");
//    foreach ($ingredients as $item) { 
//	$stmt->execute(['quantity' => $item['quantity'], 'new_quantity' => $item['quantity'], 'id' => $item['id']]);
//    }
//} catch (PDOException $ex) {
//    $success = false;
//    $message = $ex->getMessage();
//}

// Respond
echo json_encode(['success' => $success, 'message' => $message]);