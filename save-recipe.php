<?php

// Executes when user saves a new recipe in add-recipe.php
// This file runs entirely in background, user remains on recipe page
// Receives JSON of ingredients...
// Returns a JSON with a boolean for success and failure, and forwards an error message if it occured

require_once 'db.php';
$pdo = getPDO();

// Pre-emptively set default return values
$success = true;
$message = '';

// Get content of HTTP Post request. Because I sent JSON, $_POST is empty, the payload needs to be accessed raw
$input = file_get_contents('php://input');

// Reverse of JSON.stringify(), true because array is associative. This returns an associative array
$recipe = json_decode($input, true);

// $recipe is an associative array that works like this:
// echo $recipe['name'] = bread
// echo $recipe['ingredients'][0]['name'] = flour

try {
    
    // Insert recipe name into recipe table
    $stmt = $pdo->prepare
    ('
        INSERT INTO recipe (name)
        VALUES (:name)
    ');
    $stmt->execute([':name' => $recipe['name']]);
    
    // Save the generated key
    $recipe_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare
    ('
        INSERT INTO recipe_ingredient
        VALUES (:recipe_id, :ingredient_id, :quantity)
    ');

    // Loop through each ingredient and add ingredient id and recipe key into a row
    foreach ($recipe['ingredients'] as $ingredient) {
	$stmt->execute
	([
	    ':recipe_id' => $recipe_id,
	    ':ingredient_id' => $ingredient['id'],
	    ':quantity' => 1
	]);
    }
} catch (PDOException $ex) {
    $success = false;
    $message = $ex->getMessage();
}

// Respond
echo json_encode(['success' => $success, 'message' => $message]);