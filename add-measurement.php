<?php

include 'header.php';
require_once 'db.php';
$pdo = getPDO();

echo '<p>Add a new unit of measurement here.</p>';

include 'measurement-form.php';

// If vieweing after a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // === is strict equality (value AND type)
    // Prevent HTML tag injection
    $unit = htmlspecialchars($_POST['unit'] ?? '');

    try
    {
	$stmt = $pdo->prepare
	("
	    INSERT INTO `measurement` (`unit`)
	    VALUES (:unit)
	");
	$stmt->execute(['unit' => $unit]);
	echo "<p>You have added a new unit of measurement <b>$unit</b> to the database</p>";
    } catch (PDOException $ex)
    {
	// Check if the error code corresponds to a UNIQUE constraint violation
	if ($ex->getCode() == 23000) {
	    echo "<p>Error: Unit <b>$unit</b> already exists!</p>";
	} else {
	    die("Failed to add the unit of measurement to the database: " . $ex->getMessage());
	}
    }    
}
include 'footer.php';