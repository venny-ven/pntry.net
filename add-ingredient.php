<?php

include 'header.php';
require_once 'db.php';
$pdo = getPDO();

echo '<p>Add an ingredient to the database so you can track its amount in the future.</p>';
echo "<p>You can add a new unit of measurement using <a href='add-measurement.php'>this form</a>.</p>";

include 'ingredient-form.php';

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)
    // Prevent HTML tag injection
    $name = htmlspecialchars($_POST['name'] ?? '');
    $category = htmlspecialchars($_POST['category'] ?? '');
    $measurement = htmlspecialchars($_POST['measurement'] ?? '');
    
    try
    {
	$stmt = $pdo->prepare
	("
	    INSERT INTO ingredient (name, category, measurement_id)
	    VALUES (:name, :category, :measurement)
	");
	$stmt->execute(['name' => $name, 'category' => $category, 'measurement' => $measurement]);
	echo "<p>You have added a new ingredient <b>$name</b>";
    } catch (PDOException $ex)
    {
	// Check if the error code corresponds to a UNIQUE constraint violation
	if ($ex->getCode() == 23000) {
	    echo "<p>Error: Ingredient <b>$name</b> already exists!</p>";
	} else {
	    die("Failed to add the ingredient to the database: " . $ex->getMessage());
	}
    }
}
include 'footer.php';