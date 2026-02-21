<?php

require_once 'db.php';

// Joins glossary and inventory together
// Returns all ingredients in the database
// Most will have 0 quantity and some undefined properties like acquire date
// A few will have] positive quantities
// Check quantity to know if the user has this ingredient
function requestInventory(int $session_id) {
    $pdo = getPDO();
    try
    {
	// Load all ingredients and user quantities into an associative array from the database
	// Make sure the column names selected have no identical names (dont select both i.id and m.id), if they do, alias them
	$stmt = $pdo->prepare
	("
	    SELECT
		ingr.id,
		ingr.name,
		ingr.table_id,
		ingr.shelf_life,
		unit.name AS measurement_unit,
		COALESCE(inst.quantity, 0) AS quantity,
		inst.acquire_date,
		CURRENT_TIMESTAMP AS fetch_timestamp
	    FROM ingredient AS ingr
	    JOIN measurement_unit AS unit
		ON unit.id = ingr.measurement_unit_id
	    LEFT JOIN instance AS inst
		ON inst.ingredient_id = ingr.id 
		AND inst.session_id = :session_id;
	");
	$stmt->execute([':session_id' => $session_id]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
	die("Could not retrieve inventory from the database: " . $ex->getMessage());
    }
}

// Returns an array with repeating rows of the same recipe id with each ingredient on new lines
// Ingredients in this case only hold data relevant to the recipe: id, quantity in recipe, etc.
function requestRecipes() {
    $pdo = getPDO();
    
    try {
	
	$stmt = $pdo->prepare(' 
	    SELECT 
		recipe.id AS recipe_id,
		recipe.name AS recipe_name,
		junction.ingredient_id,
		junction.quantity
	    FROM recipe
	    LEFT JOIN recipe_ingredient AS junction
	    ON recipe.id = junction.recipe_id
	    ORDER BY recipe.id
	');
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
	die("Could not retrieve ingredients from the database: " . $ex->getMessage());
    }
}