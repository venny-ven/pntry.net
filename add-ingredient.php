<?php

include 'header.php';
$pdo = require_once 'db.php';

echo '<p>Add an ingredient to the database so you can track its amount in the future.</p>';

include 'ingredient-form.php';

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)
    // Prevent HTML tag injection
    $name = htmlspecialchars($_POST['name'] ?? '');
    $category = htmlspecialchars($_POST['category'] ?? '');
    

    
    try {
	$stmt = $pdo->prepare
		("
		    INSERT INTO `ingredient` (`name`, `category`)
		    VALUES (:name, :category)
		");
	$stmt->execute(['name' => $name, 'category' => $category]);
    } catch (Exception $ex) {
	die("Failed to add the ingredient to the database: " . $ex->getMessage());
    }
    
    echo "<p>You have added a new ingredient <b>$name</b> under a category <b>$category</b></p>";
}

include 'footer.php';