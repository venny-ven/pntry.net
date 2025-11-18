<?php
require_once 'db.php';
$pdo = getPDO();

try
{
    // Load units into associative array
    $stmt = $pdo->query("SELECT id, name FROM measurement_unit");
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve units from the database: " . $ex->getMessage());
}
?>

<form action="remove-unit.php" method="POST">
    
    <select name="id" required>
	<option value="">-- Select unit --</option>
	<!--PHP loop -->
	<?php
	foreach ($units as $row) {
	    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
	}
	?>
    </select>
    
    <!-- Submit button -->
    <br><br>
    <input class="red-button" type="submit" value="Delete">
</form>