<?php
require_once 'db.php';
$pdo = getPDO();

try
{
    // Load ingredients into associative array
    $stmt = $pdo->query("SELECT id, name FROM ingredient");
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the database: " . $ex->getMessage());
}
?>

<form action="remove-ingredient.php" method="POST">
    
    <select name="id" required>
	<option value="">-- Select ingredient --</option>
	<!--PHP loop -->
	<?php
	foreach ($ingredients as $row) {
	    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
	}
	?>
    </select>
    
    <!-- Submit button -->
    <br><br>
    <input class="red-button" type="submit" value="Delete">
</form>