<?php

include "header.php"; 
$pdo = require_once 'db.php';

?>

<p>Add and remove items from the following list of available options to match what you have in the kitchen.</p>

<!-- Save button at the top of the list with a hidden response label that appears when pressed -->
<button class="save_button" type="button">Save</button> <!-- type='button' is default, 'submit' would do a form request -->
<span class="save_status" style="margin-left: 8px;"></span>

<!-- Table version of the ingredient list -->
<div style="display: flex; gap: 8px; align-items: flex-start; padding-top: 8px; padding-bottom: 8px;">
<table> <tbody id="table0">
	<tr> <th colspan="3"> <p>Protein & Dairy</p> </th> </tr>
</tbody> </table>
<table> <tbody id="table1">
	<tr> <th colspan="3"> <p>Starch</p> </th> </tr>
 </tbody> </table>
<table> <tbody id="table2">
	<tr> <th colspan="3"> <p>Fruit & Vegetables</p> </th> </tr>
</tbody> </table>
<table> <tbody id="table3">
	<tr> <th colspan="3"> <p>Seasonings</p> </th> </tr>
</tbody> </table>
</div>

<!-- List of ingredients populated by JS -->
<div id="ingredientPanel" style="display: flex;"></div>

<!-- Same button at the bottom of the list -->
<button class="save_button" type="button">Save</button>
<span class="save_status" style="margin-left: 8px;"></span>

<!-- Retrieve ingredient names and quantities from the database -->
<?php
try
{
    // Prepare a statement for each table (category) of ingredient
    $statements = array();
    $statements[0] = $pdo->query("SELECT id, name, quantity FROM ingredient WHERE category IN ('protein', 'dairy');");
    $statements[1] = $pdo->query("SELECT id, name, quantity FROM ingredient WHERE category IN ('starch');");
    $statements[2] = $pdo->query("SELECT id, name, quantity FROM ingredient WHERE category IN ('fruit', 'vegetable');");
    $statements[3] = $pdo->query("SELECT id, name, quantity FROM ingredient WHERE category IN ('seasoning');");
    
    // Load ingredients to separate tables, each table is an array of items, tables are aggregated into super array
    $ingredientTables = array();
    for ($i = 0; $i < count($statements); $i++) {
	$ingredientTables[$i] = $statements[$i]->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the table: " . $ex->getMessage());
}
?>

<!-- Populate tables and create button behavior -->
<script>
    
    // Import array from php to js, encode to json to sanitize
    const ingredients = <?php echo json_encode($ingredientTables); ?>;

    // This function runs every time a button is clicked and also once for each ingredient at initialization
    function updateLabel(label, ingredientName, quantity)
    {
        label.innerHTML = `<b>${ingredientName}</b> x ${quantity}`;
    }
    
    // Generate table items
    for (let i = 0; i < ingredients.length; i++) {
	
	// Locate the right table
	table = document.getElementById(`table${i}`);
	
	// Create entries in the table
	for (let j = 0; j < ingredients[i].length; j++) {
	    
	    // Table containers
	    const row = document.createElement("tr");
	    const removeCell = document.createElement("td");
	    const addCell = document.createElement("td");
	    const labelCell = document.createElement("td");
	    removeCell.classList.add("button_cell");
	    addCell.classList.add("button_cell");
	    labelCell.classList.add("label_cell");
	    
	    // Label
	    const label = document.createElement("p");
	    updateLabel(label, ingredients[i][j].name, ingredients[i][j].quantity);
	    
	    // "Add" button
	    const addButton = document.createElement("button");
	    addButton.classList.add("add_button");
	    addButton.textContent = "+";
	    addButton.addEventListener("click", () => {
		ingredients[i][j].quantity++;
		updateLabel(label, ingredients[i][j].name, ingredients[i][j].quantity);
	    });
	    
	    // "Remove" button
	    const removeButton = document.createElement("button");
	    removeButton.classList.add("remove_button");
	    removeButton.textContent = "−";
	    removeButton.addEventListener("click", () => {
		if (ingredients[i][j].quantity > 0) {
		    ingredients[i][j].quantity--;
		}
		updateLabel(label, ingredients[i][j].name, ingredients[i][j].quantity);
	    });
	    
	    // Attach everything
	    removeCell.appendChild(removeButton);
	    addCell.appendChild(addButton);
	    labelCell.appendChild(label);
	    row.appendChild(removeCell);
	    row.appendChild(addCell);
	    row.appendChild(labelCell);
	    table.appendChild(row);
	}
    }

    // Assign behavior to save buttons
    // This type of button processing is called AJAX (Async JS And XML)
    // Because occurs in background, the page doesn't reload
    document.querySelectorAll('.save_button').forEach(button => // .querySelectorAll returns a NodeList object
    {
	// Attach listener, async because Fetch returns a Promise, not immediate response, promise becomes result when awaited
	button.addEventListener('click', async () =>
	{
	    // Locate HTML span associated with "Saved!" response text for later
	    const statusSpan = button.nextElementSibling; // The span is always right after the button

	    // Fetch API, used for all HTTP requests, send list as JSON to save-inventory.php
	    try {
		const response = await fetch('save-inventory.php', // This php file executes while the user remains on the current page
		{
		    method: 'POST',
		    headers: { 'Content-Type': 'application/json' }, // PHP receiving end will expect a JSON
		    body: JSON.stringify(ingredients) // Convert assoc array into json
		});
		
		// Receive response as JSON
		const result = await response.json();
	    
		if (result.success) {
		    statusSpan.textContent = 'Saved';
		} else {
		    statusSpan.textContent = `DB Error: ${result.message}`;
		}
	    } catch (err) {
		statusSpan.textContent = `PHP Error: ${err.message}`;
	    }
	});
    });

</script>

<?php include 'footer.php';