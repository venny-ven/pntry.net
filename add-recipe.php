<?php

include "header.php"; 

require_once 'db.php';
$pdo = getPDO();

require_once 'session.php';
$session_id = getSessionId();

?>

<p>Add a new recipe using this form</p>


<!-- Headers are separate to keep them from scrolling away -->
<div class="recipe-header-grid">
    <div class="pane">
	<table><thead><tr><th><p>Inventory</p></th></tr></thead></table>
    </div>
    <div class="pane">
	<table><thead><tr><th class="editable-header"><p>New Recipe</p></th></tr></thead></table>
    </div>
</div>

<!-- Ingredient tables -->
<div class="recipe-body-grid">
    
    <div class="pane"> <!-- Scrolling pane -->
	<table>
	    <colgroup> <!-- For width control with CSS -->
		<col class="recipe-column-0">
		<col class="recipe-column-1">
	    </colgroup>
	    <tbody id="inventory-table">
	    </tbody>
	</table>
    </div>
    
    <div class="pane">
	<table>
	    <colgroup>
		<col class="recipe-column-1">
	    </colgroup>
	    <tbody id="recipe-table">
	    </tbody>
	</table>
    </div>

</div>



<!-- ------- Retrieve data from DB ------- -->



<!-- Retrieve ingredient names, quantities, etc from the database -->
<?php
try
{
    // Load all ingredients and user quantities into an associative array from the database
    // Make sure the column names selected have no identical names (dont select both i.id and m.id), if they do, alias them
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
    
    
    
    // ------- Fill rows with data -------
    
    
    
    // Prepare important variables before the loop
	// Import glossary and inventopry from php to js, encode to json to sanitize
	const ingredients = <?php echo json_encode($ingredients); ?>;
	
	// Locate the first table
	const inventoryTable = document.getElementById(`inventory-table`);
	
	// Loop between inredient items
	// Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
	for (let j = 0; j < ingredients.length; j++) {
	    
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
	    nameCellContent.innerHTML = `<b>${ingredients[j].name}</b>`;
	    
	    // Button
	    addAndRemoveButton.textContent = "+";
	    addAndRemoveButton.addEventListener("click", () => {
		if (addAndRemoveButton.textContent === "+")
		{
		    // Locate same ingredient row in the second table
		    const recipeRow = document.getElementById(`ingredient-${ingredients[j].id}`);
		    recipeRow.style.display = ""; // Shows the row
		    
		    addAndRemoveButton.textContent = "−";
		    row.classList.add('green_row');
		    
		    // No need to remove green_button because remove_button style overrides green_button style
		    addAndRemoveButton.classList.add("remove_button");
		} else 
		{
		    const recipeRow = document.getElementById(`ingredient-${ingredients[j].id}`);
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
	
	
	// Locate the second table
	const recipeTable = document.getElementById(`recipe-table`);
	
	// Loop between inredient items
	// Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
	for (let j = 0; j < ingredients.length; j++) {
	    
	    // Table containers, like rows and cells
	    const row = document.createElement("tr");
	    const nameCell = document.createElement("td");
	    const nameCellContent = document.createElement("p");

	    // Add classes for css control
	    nameCell.classList.add("label_cell");
	    row.classList.add("recipe_table_row");
	    row.id = `ingredient-${ingredients[j].id}`; // Used to locate them later
	    
	    // Fill in name and id
	    nameCellContent.innerHTML = `<b>${ingredients[j].name}</b>`;
	    
	    // Attach everything
		    nameCell.appendChild(nameCellContent);
		row.appendChild(nameCell);
	    recipeTable.appendChild(row);
	    
	    // Hide all rows in new recipe table
	    row.style.display = "none";
	}
    
    
    
    // ------- Editable header for recipe name -------
    
    
    
    // Select first item in array of elements of class editable header
    const th = document.querySelector(".editable-header");
    if (!th) console.error("No element with class 'editable-header' found.");

    th.addEventListener("click", () => {
	const p = th.querySelector("p"); // Find paragraph element inside table header
	if (!p) return; // If user is editing already then there's no paragraph element

	const input = document.createElement("input");
	input.type = "text"; // Other types include date or number
	input.value = p.textContent;

	th.replaceChild(input, p);
	input.focus();

	// Define a function inside the listener
	function save() {
	  const newP = document.createElement("p");
	  newP.textContent = input.value || "New Recipe";
	  th.replaceChild(newP, input);
	}

	// 'Blur' is opposite of focused. When element is unfocused it's blurred
	input.addEventListener("blur", save); // 'save' function is not called here, only registered. Tying save() would call it
	input.addEventListener("keydown", e => {
	  if (e.key === "Enter" || e.key === "Escape") input.blur();
	});
    });

</script>

<?php include 'footer.php';