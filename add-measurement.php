<?php

include 'header.php';
require_once 'db.php';
$pdo = getPDO();

echo '<p>Add a new unit of measurement here.</p>';

include 'measurement-form.php';

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)
    // Prevent HTML tag injection
    $name = htmlspecialchars($_POST['name'] ?? '');

    try
    {
	$stmt = $pdo->prepare
	("
	    INSERT INTO measurement_unit (name)
	    VALUES (:name)
	");
	$stmt->execute(['name' => $name]);
	echo "<p>You have added a new unit of measurement <b>$name</b> to the database</p>";
    } catch (PDOException $ex)
    {
	// Check if the error code corresponds to a UNIQUE constraint violation
	if ($ex->getCode() == 23000) {
	    echo "<p>Error: Unit <b>$name</b> already exists!</p>";
	} else {
	    die("Failed to add the unit of measurement to the database: " . $ex->getMessage());
	}
    }    
}
include 'footer.php';