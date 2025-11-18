<?php

include 'header.php';

require_once 'db.php';
$pdo = getPDO();

echo '<p>Add an ingredient to the database so you can track its amount in the future.</p>';
echo "<p>To remove an ingredient from the database use the <a href='remove-ingredient.php'>remove ingredient form</a>.</p>";
echo "<p>You can add a new unit of measurement using the <a href='add-measurement.php'>add unit form</a> or remove one with the <a href='remove-unit.php'>remove unit form</a>.</p>";

include 'ingredient-form.php';

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)

    // Prevent HTML tag injection
    $name = htmlspecialchars($_POST['name'] ?? '');
    $category = htmlspecialchars($_POST['category'] ?? '');
    $measurement_unit_id = htmlspecialchars($_POST['measurement_unit_id'] ?? '');
    $shelf_life = htmlspecialchars($_POST['shelf_life'] ?? '');
    if ($shelf_life == 0) { $shelf_life = null; } // Leave comparison as == instead of ===, otherwise it fails to compare correctly

    try
    {
	// Prepare-execute prevents SQL injection
	$stmt = $pdo->prepare
	("
	    INSERT INTO ingredient (name, category, shelf_life, measurement_unit_id)
	    VALUES (:name, :category, :shelf_life, :measurement_unit_id)
	");
	$stmt->execute(['name' => $name, 'category' => $category, 'shelf_life' => $shelf_life, 'measurement_unit_id' => $measurement_unit_id]);
	echo "<p>You have added a new ingredient <b>$name</b>";
    } catch (PDOException $ex)
    {
	// Check if the error code corresponds to a constraint violation
	if ($ex->getCode() == 23000) { // Leave comparison as == instead of ===, otherwise it fails to compare correctly
	    echo "<p>Error: Ingredient <b>$name</b> already exists!</p>";
	} else {
	    die("Failed to add the ingredient to the database: " . $ex->getMessage());
	}
    }
}

include 'footer.php';