<?php
require_once 'db.php';
$pdo = getPDO();

try
{
    // Load measurements into associative array
    $stmt = $pdo->query("SELECT id, unit FROM measurement");
    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve units of measurement from the database: " . $ex->getMessage());
}
?>

<form action="add-ingredient.php" method="POST">
    
    <!-- Name of the ingredient -->
    <label>Ingredient name: <input type="text" name="name" required></label>
    
    <!-- Unit of measurement -->
    <select name="measurement" required>
	<option value="">-- Select measurement --</option>
	<!--PHP loop -->
        <?php
	foreach ($measurements as $row) {
	    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['unit']) . "</option>";
	}
	?>
    </select>
    
    <!-- Category -->
    <select name="category" required>
        <option value="">-- Select category --</option>
        <option value="protein">Protein</option>
        <option value="starch">Starch</option>
        <option value="vegetable">Vegetable</option>
        <option value="fruit">Fruit</option>
        <option value="dairy">Dairy</option>
        <option value="seasoning">Seasoning</option>
    </select>
    
    <!-- Shelf life -->
    <br><br>
    <label>
	Shelf life in days (leave at 0 for indefinite):
	<input type="number" name="shelf_life_days" value="0" min="0" max="1460" required>
    </label>
    
    <!-- Submit button -->
    <br><br>
    <input type="submit" value="Add Ingredient">
</form>