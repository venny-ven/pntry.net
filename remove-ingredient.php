<?php

include 'header.php';
require_once 'db.php';
$pdo = getPDO();

echo '<p>Remove an ingredient from the database using this form.</p>';

// Processing a deletion must occur first, but I want the response messages to appear below the form, so I use this
$feedback_code = 0;

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)

    // Prevent HTML tag injection
    $id = htmlspecialchars($_POST['id'] ?? '');
    
    try
    {
	// Prepare-execute prevents SQL injection
	$stmt = $pdo->prepare
	("
	    DELETE FROM ingredient
	    WHERE id = :id;
	");
	$stmt->execute(['id' => $id]);
	$feedback_code = 3; // Success code
    } catch (PDOException $ex) {
	if ($ex->getCode() == 23000) { // Table constraint error, can't delete item because its referenced in someone's inventory
	    $feedback_code = 1;
	} else {
	    $feedback_code = 2;
	}
    }
}

include 'remove-ingredient-form.php';

if ($feedback_code === 1) {
    echo "<p>Error: This ingredient is being used!</p>";
} else if ($feedback_code === 2) {
    die("Failed to remove the ingredient from the database: " . $ex->getMessage());
} else if ($feedback_code === 3) {
    echo "<p>You have removed an ingredient at ID <b>$id</b>";
}

include 'footer.php';