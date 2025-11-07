<?php

include "header.php"; 
require_once 'db.php';
$pdo = getPDO();

?>

<p>Use this tool to keep track of items in your kitchen and to see what may expire soon</p>

<!-- Save button at the top of the list with a hidden response label that appears when pressed -->
<button class="save_button" type="button">Save</button> <!-- type='button' is default, 'submit' would do a form request -->
<span class="save_status" style="margin-left: 8px;"></span>

<!-- Table version of the ingredient list -->
<div style="display: flex; gap: 8px; align-items: flex-start; padding-top: 8px; padding-bottom: 8px;">
<table> <tbody id="table0" data-categories="protein,dairy">
	<tr> <th colspan="4"> <p>Protein & Dairy</p> </th> </tr>
</tbody> </table>
<table> <tbody id="table1" data-categories="starch">
	<tr> <th colspan="4"> <p>Starch</p> </th> </tr>
 </tbody> </table>
<table> <tbody id="table2" data-categories="fruit,vegetable">
	<tr> <th colspan="4"> <p>Fruit & Vegetables</p> </th> </tr>
</tbody> </table>
<table> <tbody id="table3" data-categories="seasoning">
	<tr> <th colspan="4"> <p>Seasonings</p> </th> </tr>
</tbody> </table>
</div>

<!-- List of ingredients populated by JS -->
<div id="ingredientPanel" style="display: flex;"></div>

<!-- Same button at the bottom of the list -->
<button class="save_button" type="button">Save</button>
<span class="save_status" style="margin-left: 8px;"></span>

<!-- Retrieve ingredient names, quantities, etc from the database -->
<?php
try
{
    // Load ingredients into an associative array from the database
    // Make sure the column names selected have no identical names (avoid selecting both i.id and m.id), if they do, alias them
    $statement = $pdo->query
	("
	    SELECT i.id, i.name, i.quantity, i.category, i.shelf_life_days, i.acquire_date, i.shelf_life_days, m.unit
	    FROM ingredient AS i
	    JOIN measurement AS m
	    ON i.measurement_id = m.id;
	");
    
    $ingredientList = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the database: " . $ex->getMessage());
}
?>

<!-- Populate HTML tables and create button behavior -->
<script>
    
    // Import ingredients from php to js, encode to json to sanitize
    const ingredients = <?php echo json_encode($ingredientList); ?>;
    
    // Copy quantities into another array
    // When user adds / removes, I compare to these initial quantities
    // To determine the color of the row - green or red or none
    const initialQuantities = ingredients.map(ingr => ingr.quantity);

    // Get datetime from the server
    const serverTimestamp = "<?php echo date('Y-m-d H:i:s'); ?>";
    const currentDatetime = new Date(serverTimestamp.replace(" ", "T")); // Converts into a working format
    
    function setRemainingDays(label, acquireDate, shelfLifeDays) {
	if (shelfLifeDays === null) {
	    return;
	}
	    
	const acquireDatetime = new Date(acquireDate);
	const expirationDatetime = new Date(acquireDatetime.getTime() + shelfLifeDays * 24 * 60 * 60 * 1000);
	const remainingMs = expirationDatetime - currentDatetime;
	const remainingDays = Math.floor(remainingMs / (24 * 60 * 60 * 1000));

	if (remainingDays < 0) {
	    // color ingredient text red
	}
	else if (remainingDays < 2) {
	    // color ingredient text yellow
	}
	label.innerHTML = `${remainingDays} d`;
    }
    

    // This function runs every time a button is clicked and also once for each ingredient at initialization
    function updateLabel(label, ingredientName, quantity, measurementUnit)
    {
        label.innerHTML = `<b>${ingredientName}</b> x ${quantity} ${measurementUnit}`;
    }
    
    // Generate table items
    for (let i = 0; i < 4; i++) {
	
	// Locate the correct table
	const table = document.getElementById(`table${i}`);
	
	// Identify selected categories for that table
	const tableCategories = table.dataset.categories.split(',');
	
	// Create entries in the table
	for (let j = 0; j < ingredients.length; j++) {
	    
	    // Table containers, like rows and cells
	    const row = document.createElement("tr");
	    const removeCell = document.createElement("td");
	    const addCell = document.createElement("td");
	    const labelCell = document.createElement("td");
		const label = document.createElement("p");
	    const shelfLifeCell = document.createElement("td");
		const shelfLifeLabel = document.createElement("p");

	    // Add classes for css control
	    removeCell.classList.add("button_cell");
	    addCell.classList.add("button_cell");
	    labelCell.classList.add("label_cell", "multicell");
	    shelfLifeCell.classList.add("shelf_life_cell", "multicell");
	    
	    // Label - text that says the name of the ingredient and quantity
	    updateLabel(label, ingredients[j].name, ingredients[j].quantity, ingredients[j].unit);
	    
	    // "+" button
	    const addButton = document.createElement("button");
	    addButton.classList.add("add_button");
	    addButton.textContent = "+";
	    addButton.addEventListener("click", () => {
		ingredients[j].quantity++;
		updateLabel(label, ingredients[j].name, ingredients[j].quantity, ingredients[j].unit);
		// Color the row
		if (initialQuantities[j] < ingredients[j].quantity) {
		    row.classList.remove('red_row');
		    row.classList.add('green_row');
		}
		else if (initialQuantities[j] > ingredients[j].quantity) {
		    row.classList.remove('green_row');
		    row.classList.add('red_row');
		}
		else {
		    row.classList.remove('green_row', 'red_row');
		}
		
		// Add expiration time if the item has just been added
		if (ingredients[j].quantity === 1) {
		    setRemainingDays(shelfLifeLabel, ingredients[j].acquire_date, ingredients[j].shelf_life_days);
		}
	    });
	    
	    // "-" button
	    const removeButton = document.createElement("button");
	    removeButton.classList.add("remove_button");
	    removeButton.textContent = "−";
	    removeButton.addEventListener("click", () => {
		if (ingredients[j].quantity > 0) {
		    ingredients[j].quantity--;
		    updateLabel(label, ingredients[j].name, ingredients[j].quantity, ingredients[j].unit);
		    
		    // Color the row
		    if (initialQuantities[j] < ingredients[j].quantity) {
		        row.classList.remove('red_row');
		        row.classList.add('green_row');
			}
		    else if (initialQuantities[j] > ingredients[j].quantity) {
			row.classList.remove('green_row');
		        row.classList.add('red_row');
		    }
		    else {
		        row.classList.remove('green_row', 'red_row');
		    }
		    
		    // Remove the expiration date when the item is removed
		    if (ingredients[j].quantity === 0) {
			shelfLifeLabel.innerHTML = ``;
		    }
		}
	    });
	    
	    // Calculate remaining shelf life
	    if (ingredients[j].quantity !== 0) {
		setRemainingDays(shelfLifeLabel, ingredients[j].acquire_date, ingredients[j].shelf_life_days);
	    }
	    
	    // Attach everything
	    removeCell.appendChild(removeButton);
	    addCell.appendChild(addButton);
	    labelCell.appendChild(label);
	    shelfLifeCell.appendChild(shelfLifeLabel);
	    row.appendChild(removeCell);
	    row.appendChild(addCell);
	    row.appendChild(labelCell);
	    row.appendChild(shelfLifeCell);
	    table.appendChild(row);
	    
	    // Hide rows if they don't match table categories
	    let isVisible = false;
	    for (const tableCategory of tableCategories) {
		if (tableCategory === ingredients[j].category) {
		    isVisible = true;
		}
	    }
	    if (isVisible === false) {
		row.style.display = "none";
	    }
	}
    }

    // Assign behavior to "Save" button
    // This type of button processing is called AJAX (Async JS And XML)
    // Because it occurs in background, the page doesn't reload and the URL doesn't change
    document.querySelectorAll('.save_button').forEach(button => // .querySelectorAll returns a NodeList object
    {
	// Attach listener, async because Fetch returns a Promise, not immediate response, promise becomes Result when awaited
	button.addEventListener('click', async () =>
	{
	    // Locate HTML span associated with "Saved!" response text for later
	    const statusSpan = button.nextElementSibling; // This span is always right after the button

	    // Fetch API, used for all HTTP requests - send list as JSON to save-inventory.php
	    try {
		const response = await fetch('save-inventory.php', // This php file executes while the user remains on the current page
		{
		    method: 'POST',
		    headers: { 'Content-Type': 'application/json' }, // PHP receiving end will expect a JSON
		    body: JSON.stringify(ingredients) // Convert associtaive array into json
		});
		
		// Receive response as JSON
		const result = await response.json();
	    
		// Show a response text "Saved" for instant feedback, or an error
		if (result.success) {
		    statusSpan.textContent = 'Saved';
		    
		    // Remove row highlights
		    document.querySelectorAll('.green_row').forEach(element => {
			element.classList.remove('green_row');
		    });
		    document.querySelectorAll('.red_row').forEach(element => {
			element.classList.remove('red_row');
		    });
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