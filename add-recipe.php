<?php

include "header.php"; 

require_once 'db.php';
$pdo = getPDO();

?>

<p>Add a new recipe using this form</p>


<!-- Structure is
     - One board for header, one board for body, board spans full width, separate to prevent top from scrolling
     - Two or three panes inside each board, panes needed for scroll
     - Table inside each pane
-->
<div class="board top-board two-pane-board">
    <div class="pane">
	<table><thead><tr><th><p>Ingredients</p></th></tr></thead></table>
    </div>
    <div class="pane">
	<table><thead><tr><th class="editable-header"><p>New Recipe</p></th></tr></thead></table>
    </div>
</div>

<!-- Ingredient tables -->
<div class="board bottom-board two-pane-board">
    
    <div class="pane"> <!-- Scrolling pane -->
	<table>
	    <colgroup> <!-- For width control with CSS -->
		<col class="ingredient-name-column">
		<col class="button-column">
	    </colgroup>
	    <tbody id="inventory-table">
	    </tbody>
	</table>
    </div>
    
    <div class="pane">
	<table>
	    <colgroup>
		<col class="ingredient-name-column">
	    </colgroup>
	    <tbody id="recipe-table">
	    </tbody>
	</table>
    </div>
    
</div>

<!-- Save button with a hidden response label that appears if an error occurs -->
<div style="text-align: right;">
    <span class="save_status" style="margin-right: 8px;"></span>
    <button class="save_button" type="button">Save</button> <!-- type='button' is default, 'submit' would do a form request -->
</div>



<!-- ------- Retrieve data from DB ------- -->



<!-- Retrieve ingredient names, quantities, etc from the database -->
<?php
try
{
    // Load all ingredients into an associative array from the database
    $stmt = $pdo->prepare
    ("
	SELECT
	    id,
	    name,
	    category
	FROM ingredient;
    ");
    $stmt->execute();
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the database: " . $ex->getMessage());
}
?>

<script>
	// Import inventopry from php to js, encode to json to sanitize
	const inventory_ingredients = <?php echo json_encode($ingredients); ?>;
	// Prepare internal list to send to server later
	const recipe_ingredients = [];
	
	
	
	// ------- Inventory table -------
    
    
    
	
	// Locate the first table
	const inventoryTable = document.getElementById(`inventory-table`);
	
	// Loop between inredient items
	// Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
	for (let j = 0; j < inventory_ingredients.length; j++) {
	    
	    // Table containers, like rows and cells
	    const row = document.createElement("tr");
	    const nameCell = document.createElement("td");
	    const nameCellContent = document.createElement("p");
	    const addAndRemoveCell = document.createElement("td");
	    const addAndRemoveButton = document.createElement("button");

	    // Add classes for css control
	    addAndRemoveCell.classList.add("button_cell");
	    addAndRemoveButton.classList.add("add_button");
	    nameCell.classList.add("label_cell");
	    
	    // Fill in name and id
	    nameCellContent.innerHTML = `<b>${inventory_ingredients[j].name}</b>`;
	    
	    // Button
	    addAndRemoveButton.textContent = "+";
	    addAndRemoveButton.addEventListener("click", () => {
		if (addAndRemoveButton.textContent === "+")
		{
		    recipe_ingredients[j] = inventory_ingredients[j]; // Creates a sparse array that contains missing indexes
		    
		    // Locate same ingredient row in the second table
		    const recipeRow = document.getElementById(`ingredient-${inventory_ingredients[j].id}`);
		    recipeRow.style.display = ""; // Shows the row
		    
		    addAndRemoveButton.textContent = "−";
		    row.classList.add('green_row');
		    
		    // No need to remove green_button because remove_button style overrides green_button style
		    addAndRemoveButton.classList.add("remove_button");
		} else 
		{
		    delete recipe_ingredients[j];
		    
		    // Locate same ingredient row in the second table
		    const recipeRow = document.getElementById(`ingredient-${inventory_ingredients[j].id}`);
		    recipeRow.style.display = "none"; // Hide the row
		    
		    addAndRemoveButton.textContent = "+";
		    row.classList.remove('green_row');
		    addAndRemoveButton.classList.remove("remove_button");
		}
	    });
	    
	    // Attach everything
		    nameCell.appendChild(nameCellContent);
		    addAndRemoveCell.appendChild(addAndRemoveButton);
		row.appendChild(nameCell);
		row.appendChild(addAndRemoveCell);
	    inventoryTable.appendChild(row);
	}
	
	
	
	// ------- Recipe table -------
	
	
	
	// Locate the second table
	const recipeTable = document.getElementById(`recipe-table`);
	
	// Loop between inredient items
	// Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
	for (let j = 0; j < inventory_ingredients.length; j++) {
	    
	    // Table containers, like rows and cells
	    const row = document.createElement("tr");
	    const nameCell = document.createElement("td");
	    const nameCellContent = document.createElement("p");

	    // Add classes for css control
	    nameCell.classList.add("label_cell");
	    row.classList.add("fixed_height_row");
	    row.id = `ingredient-${inventory_ingredients[j].id}`; // Used to locate them later
	    
	    // Fill in name and id
	    nameCellContent.innerHTML = `<b>${inventory_ingredients[j].name}</b>`;
	    
	    // Attach everything
		    nameCell.appendChild(nameCellContent);
		row.appendChild(nameCell);
	    recipeTable.appendChild(row);
	    
	    // Hide all rows in new recipe table
	    row.style.display = "none";
	}
    
    
    
    // ------- Editable header for recipe name -------
    
    
    
    // Select first element of class editable header then select first paragraph
    const header = document.querySelector(".editable-header").querySelector("p");
    if (!header) console.error("No paragraph element in 'editable-header' found.");

    header.addEventListener("click", () => {
	const inputField = document.createElement("input");
	inputField.type = "text"; // Other types include date or number
	inputField.value = header.textContent;

	header.replaceWith(inputField);
	inputField.focus();
	inputField.select();  // Select all text

	// Define a function inside the listener
	function save()
	{
	    header.textContent = inputField.value;
	    console.log(`inputField.value: ${inputField.value}. newHeader.textContent: ${header.textContent}`)
	    inputField.replaceWith(header);
	}

	// 'Blur' is opposite of focused. When element is unfocused it's blurred
	inputField.addEventListener("blur", save); // 'save' function is not called here, only registered. Tying save() would call it
	inputField.addEventListener("keydown", e => {
	  if (e.key === "Enter" || e.key === "Escape") inputField.blur();
	});
    });
    
    
    
    // ------- 'Save' button ------- 



    // This type of button processing is called AJAX (Async JS And XML)
    // Because it occurs in background, the page doesn't reload and the URL doesn't change
    document.querySelectorAll('.save_button').forEach(button => // .querySelectorAll returns a NodeList object
    {
	// Attach listener, async because Fetch returns a Promise, not immediate response, promise becomes Result when awaited
	button.addEventListener('click', async () =>
	{	    
	    // Define data to be sent
	    const recipe = {
		name: header.textContent,
		ingredients: recipe_ingredients.filter(Boolean) // Remove missing gaps in a sparse array
	    };
	    
	    
	    
	    // Locate HTML span associated with feedback text for later
	    const statusSpan = document.querySelector('.save_status');
	    
	    // Fetch API, used for all HTTP requests - send list as JSON to save-inventory.php
	    try {
		const response = await fetch('save-recipe.php', // This php file executes while the user remains on the current page
		{
		    method: 'POST',
		    headers: { 'Content-Type': 'application/json' }, // PHP receiving end will expect a JSON
		    body: JSON.stringify(recipe) // Convert into json
		});
		
		// Receive response as JSON
		const result = await response.json();
	    
		// Show a response text "Saved" for instant feedback, or an error
		if (result.success) {
		    window.location.href = "view-recipes.php";
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