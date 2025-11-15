<?php

include "header.php"; 

require_once 'db.php';
$pdo = getPDO();

require_once 'session.php';
$session_id = getSessionId();

?>



<!-- ------- HTML scaffold  ------- -->



<p>Use this tool to keep track of items in your kitchen and to see what may expire soon</p>


<!-- Headers are separate to keep them from scrolling away -->
<div class="header-grid">
    <div class="pane">
	<table><thead><tr><th><p>Protein & Dairy</p></th></tr></thead></table>
    </div>
    <div class="pane">
	<table><thead><tr><th><p>Starch & Seasonings</p></th></tr></thead></table>
    </div>
    <div class="pane">
	<table><thead><tr><th><p>Fruit & Vegetables</p></th></tr></thead></table>
    </div>
</div>

<!-- Ingredient tables -->
<div class="body-grid">
    
    <div class="pane"> <!-- Scrolling pane -->
	<table>
	    <colgroup> <!-- For width control with CSS -->
		<col class="column-0"><col class="column-1"><col class="column-2"><col class="column-3"><col class="column-4">
	    </colgroup>
	    <tbody id="table-body-0" data-categories="protein,dairy">
	    </tbody>
	</table>
    </div>
    
    <div class="pane">
	<table>
	    <colgroup>
		<col class="column-0"><col class="column-1"><col class="column-2"><col class="column-3"><col class="column-4">
	    </colgroup>
	    <tbody id="table-body-1" data-categories="starch,seasoning">
	    </tbody>
	</table>
    </div>
    
    <div class="pane">
	<table>
	    <colgroup>
		<col class="column-0"><col class="column-1"><col class="column-2"><col class="column-3"><col class="column-4">
	    </colgroup>
	    <tbody id="table-body-2" data-categories="fruit,vegetable">
	    </tbody>
	</table>
    </div>

</div>

<!-- Save button with a hidden response label that appears when pressed -->
<button class="save_button" type="button">Save</button> <!-- type='button' is default, 'submit' would do a form request -->
<span class="save_status" style="margin-left: 8px;"></span>



<!-- ------- Retrieve data from DB  ------- -->



<!-- Retrieve ingredient names, quantities, etc from the database -->
<?php
try
{
    // Load all ingredients and user quantities into an associative array from the database
    // Make sure the column names selected have no identical names (dont select both i.id and m.id), if they do, alias them
    $stmt = $pdo->prepare
    ("
	SELECT
	    ingr.id,
	    ingr.name AS ingr_name,
	    ingr.category,
	    ingr.shelf_life,
	    unit.name AS unit_name,
	    COALESCE(inst.quantity, 0) AS quantity,
	    inst.acquire_date
	FROM ingredient AS ingr
	JOIN measurement_unit AS unit
	    ON unit.id = ingr.measurement_unit_id
	LEFT JOIN instance AS inst
	    ON inst.ingredient_id = ingr.id 
	    AND inst.session_id = :session_id;
    ");
    $stmt->execute([':session_id' => $session_id]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the database: " . $ex->getMessage());
}
?>

<script>
    
    
    
    // ------- Utility functions -------
    
    
    
    // Calculates how many days left before expiration and puts that into a label
    function setDuration(contentField, acquireDate, shelfLifeInDays) {
	if (shelfLifeInDays === null) {
	    contentField.innerHTML = ``;
	    return;
	}
	
	if (!acquireDate) {
	    acquireDate = currentDatetime;
	}
	
	const acquiredOn = new Date(acquireDate);
	const expiresOn = new Date(acquiredOn.getTime() + shelfLifeInDays * 24 * 60 * 60 * 1000);
	const remainingMs = expiresOn - currentDatetime;
	const remainingDays = Math.floor(remainingMs / (24 * 60 * 60 * 1000));

	if (remainingDays < 0) {
	    // color text red
	}
	else if (remainingDays < 2) {
	    // color text yellow
	}
	contentField.innerHTML = `${remainingDays} d`;
    }
        
    // Runs every time a button is clicked and also once for each ingredient at initialization
    function setQuantity(contentField, quantity, measurementUnitName) {
	contentField.innerHTML = `${quantity} ${measurementUnitName}`;
    }
    
    // Colors the row green, red, or transparent depending on if the item is being added or substracted from the initial
    function setRowColor(row, initailQuantity, currentQuantity) {
		    console.log(`${initailQuantity}, ${currentQuantity}`);
	if (initailQuantity < currentQuantity) {
	    row.classList.remove('red_row');
	    row.classList.add('green_row');
	}
	else if (initailQuantity > currentQuantity) {
	    row.classList.remove('green_row');
	    row.classList.add('red_row');
	}
	else {
	    row.classList.remove('green_row', 'red_row');
	}
    }
    
    
    
    // ------- Fill rows with data -------
    
    
    
    // Prepare important variables before the loop
	// Import glossary and inventopry from php to js, encode to json to sanitize
	const ingredients = <?php echo json_encode($ingredients); ?>;

	// Copy quantities into another array
	// When user adds / removes, I compare to these initialQuantities
	// To determine the color of the row: green or red or none
	let initialQuantities = ingredients.map(item => item.quantity);

	// Get datetime from the server
	const serverTimestamp = "<?php echo date('Y-m-d H:i:s'); ?>";
	const currentDatetime = new Date(serverTimestamp.replace(" ", "T")); // Converts into a working format
    
    // Loop between tables
    for (let i = 0; i < 3; i++) {
	
	// Locate the correct table
	const table = document.getElementById(`table-body-${i}`);
	
	// Identify selected categories for that table
	const tableCategories = table.dataset.categories.split(',');
	
	// Loop between inredient items
	// Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
	for (let j = 0; j < ingredients.length; j++) {
	    
	    // Table containers, like rows and cells
	    const row = document.createElement("tr");
		const removeCell = document.createElement("td");
		const addCell = document.createElement("td");
		const quantityCell = document.createElement("td");
		const nameCell = document.createElement("td");
		const durationCell = document.createElement("td");
		    const removeButton = document.createElement("button");
		    const addButton = document.createElement("button");
		    const quantityCellContent = document.createElement("p");
		    const nameCellContent = document.createElement("p");
		    const durationCellContent = document.createElement("p");

	    // Add classes for css control
	    removeCell.classList.add("button_cell");
	    removeButton.classList.add("remove_button");
	    addCell.classList.add("button_cell");
	    addButton.classList.add("add_button");
	    quantityCell.classList.add("quantity_cell")
	    nameCell.classList.add("label_cell");
	    durationCell.classList.add("shelf_life_cell");
	    
	    // Fill in name, quantity and duration
	    nameCellContent.innerHTML = `<b>${ingredients[j].ingr_name}</b>`;
	    setQuantity(quantityCellContent, ingredients[j].quantity, ingredients[j].unit_name);
	    
	    if (ingredients[j].quantity !== 0) {
		setDuration(durationCellContent, ingredients[j].acquire_date, ingredients[j].shelf_life);
	    }
	    
	    // "+" button
	    addButton.textContent = "+";
	    addButton.addEventListener("click", () => {		
		ingredients[j].quantity++;
		setQuantity(quantityCellContent, ingredients[j].quantity, ingredients[j].unit_name);
		setRowColor(row, initialQuantities[j], ingredients[j].quantity);
		
		// Add expiration time if the item has just been added
		if (ingredients[j].quantity === 1) {
		    setDuration(durationCellContent, ingredients[j].acquire_date, ingredients[j].shelf_life);
		}
	    });
	    
	    // "-" button
	    removeButton.textContent = "−";
	    removeButton.addEventListener("click", () => {
		if (ingredients[j].quantity > 0) {
		    ingredients[j].quantity--;
		    setQuantity(quantityCellContent, ingredients[j].quantity, ingredients[j].unit_name);
		    setRowColor(row, initialQuantities[j], ingredients[j].quantity);

		    // Remove the expiration date when the item is removed
		    if (ingredients[j].quantity === 0) {
			durationCellContent.innerHTML = ``;
		    }
		}
	    });
	    
	    // Attach everything
		    removeCell.appendChild(removeButton);
		    addCell.appendChild(addButton);
		    quantityCell.appendChild(quantityCellContent);
		    nameCell.appendChild(nameCellContent);
		    durationCell.appendChild(durationCellContent);
		row.appendChild(removeCell);
		row.appendChild(addCell);
		row.appendChild(quantityCell);
		row.appendChild(nameCell);
		row.appendChild(durationCell);
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



    // ------- 'Save' button ------- 



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
		    
		    // Reset initial quantities to new ones
		    initialQuantities = ingredients.map(item => item.quantity);
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