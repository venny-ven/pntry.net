<?php

include "header.php"; 

require_once 'db.php';
$pdo = getPDO();

require_once 'session.php';
$session_id = getSessionId();

?>



<!-- ------- HTML scaffold  ------- -->



<p>Check what you can cook using ingredients you have in the kitchen</p>


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
	<table><thead><tr><th><p>Recipes</p></th></tr></thead></table>
    </div>
</div>

<!-- Ingredient tables -->
<div class="board bottom-board two-pane-board">
    
    <div class="pane"> <!-- Scrolling pane -->
	<table>
	    <colgroup> <!-- For width control with CSS -->
		<col class="quantity-column"> <!-- Quantity -->
		<col class="ingredient-name-column"> <!-- Name of ingredient -->
		<col class="expiration-column"> <!-- Expiration -->
	    </colgroup>
	    <tbody id="ingredient-table" class="fixed_height_rows"> <!-- To locate in JS -->
	    </tbody>
	</table>
    </div>
    
    <div class="pane">
	<table>
	    <colgroup>
		<col class="recipe-name-column"> <!-- Name of recipe -->
		<col class="recipe-matchrate-column"> <!-- Match rate -->
	    </colgroup>
	    <tbody id="recipe-table" class="fixed_height_rows">
	    </tbody>
	</table>
    </div>
    
</div>



<!-- ------- Retrieve data from DB  ------- -->



<!-- Retrieve ingredient names, quantities, etc from the database -->
<?php
try
{
    // Load all ingredients and user quantities into an associative array from the database
    // Make sure the column names selected have no identical names (dont select both i.id and m.id), if they do, alias them
    // COALESCE will turn any rows with missing data into 0 in this case
    $stmt = $pdo->prepare
    ("
	SELECT
	    ingr.id,
	    ingr.name AS ingr_name,
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
    
    // Load recipes
    // Table will contain repeating rows of the same recipe with each ingredient on new line
    $stmt = $pdo->prepare('
	SELECT 
	    recipe.id AS recipe_id,
	    recipe.name AS recipe_name,
	    junction.ingredient_id,
	    junction.quantity
	FROM recipe
	LEFT JOIN recipe_ingredient AS junction
	ON recipe.id = junction.recipe_id
	ORDER BY recipe.id
    ');

    $stmt->execute();
    $table = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform into nested arrays
    // They work like:
    // recipes[0].name
    // recipes[0].ingredients[0].name
    $recipes = [];
    foreach ($table as $row) {
	
	// For convenience
	$r_id = $row['recipe_id'];
	$i_id = $row['ingredient_id'];

	// If recipe_id is new, create an "object" (assoc. array) at same index as recipe_id
	// This makes the $recipes[] array a sparse index array
	if (!isset($recipes[$r_id]))
	{
	    $recipes[$r_id] = [
		'id' => $r_id,
		'name' => $row['recipe_name'],
		'ingredients' => []
	    ];
	}
    
	// Add ingredient as "object" if the row isn't null
	if ($row['ingredient_id'] !== null) {
	    $recipes[$r_id]['ingredients'][] = [
		'id' => $i_id,
		'quantity' => $row['quantity']
	    ];
	}
    }

    // Re-index array to turn it into a dense array
    $recipes = array_values($recipes);
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the database: " . $ex->getMessage());
}
?>

<script>
    
    
    
    // ------- Utility functions -------
    // These are copied from index.php
    
    
    
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
    function setQuantity(quantityContentField, nameContentField, quantity, measurementUnitName) {
	quantityContentField.innerHTML = `${quantity} ${measurementUnitName}`;
	
	// Make bold
	if (quantity === 1) {
	    nameContentField.innerHTML = "<b>" + nameContentField.textContent + "</b>";
	}
	// Unbold
	else if (quantity === 0) {
	    // Replace the element <b> with the content of the element, thus removing the bold effect
	    if (nameContentField.querySelector('b')) {
		nameContentField.innerHTML = nameContentField.querySelector('b').textContent;
	    }
	}
    }
    
    function highlightRecipeRow(row) {
	row.classList.add('selected_row');
    }
    
    function highlightIngredientRow(ingredientId) {
	// Locate the row with ingredient from the recipe
	const ingredientRow = document.getElementById(`ingredient-row-${ingredientId}`);
	// Locate the first paragraph in the first column, which coincides with the quantity text
	const paragraph = ingredientRow.querySelector('td p');
	// Extract the leading number from something like "0 grams" or "15 grams"
	// parseFloat() stops parsing at the first non-numeric character
	const quantity = parseFloat(paragraph.innerHTML);
	if (quantity === 0) {
	    ingredientRow.classList.add('red_row');
	} else {
	    ingredientRow.classList.add('green_row');
	}
    }
    
    function getIngredientQuantity(ingredientId)
    {
    	// Locate the row with ingredient from the recipe
	const ingredientRow = document.getElementById(`ingredient-row-${ingredientId}`);
	// Locate the first paragraph in the first column, which coincides with the quantity text
	const paragraph = ingredientRow.querySelector('td p');
	// Extract the leading number from something like "0 grams" or "15 grams"
	// parseFloat() stops parsing at the first non-numeric character
	return parseFloat(paragraph.innerHTML);
    }
    
    function removeHighlightFromAllRows() {
	document.querySelectorAll('.red_row, .green_row, .selected_row').forEach(element => {
	    element.classList.remove('red_row', 'green_row', 'selected_row');
	});
    }
    
    
    
    // ------- Fill rows with data -------
    
    
    
    // Prepare important variables before the loop
    // Import from php to js, encode to json to sanitize
    const ingredients = <?php echo json_encode($ingredients); ?>;
    const recipes = <?php echo json_encode($recipes); ?>;

    // Get datetime from the server
    const serverTimestamp = "<?php echo date('Y-m-d H:i:s'); ?>";
    const currentDatetime = new Date(serverTimestamp.replace(" ", "T")); // Converts into a working format
    
    // Freeze row highlights when clicking on a row
    let isHighlightFrozen = false;
    document.addEventListener('click', () => {
	isHighlightFrozen = false;
	removeHighlightFromAllRows();
    });


    // ------- Table 1 -------



    // Locate the ingredient table
    const ingr_table = document.getElementById(`ingredient-table`);

    // Loop between inredient items
    // Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
    for (let j = 0; j < ingredients.length; j++) {

	// Table containers, like rows and cells
	const row = document.createElement("tr");
	    const quantityCell = document.createElement("td");
	    const nameCell = document.createElement("td");
	    const durationCell = document.createElement("td");
		const quantityCellContent = document.createElement("p");
		const nameCellContent = document.createElement("p");
		const durationCellContent = document.createElement("p");

	// Add classes for css control
	quantityCell.classList.add("quantity_cell");
	nameCell.classList.add("label_cell");
	durationCell.classList.add("shelf_life_cell");
	row.id = `ingredient-row-${ingredients[j].id}`;

	// Fill in name, quantity and duration
	nameCellContent.innerHTML = `<b>${ingredients[j].ingr_name}</b>`;
	setQuantity(quantityCellContent, nameCellContent, ingredients[j].quantity, ingredients[j].unit_name);

	if (ingredients[j].quantity !== 0) {
	    setDuration(durationCellContent, ingredients[j].acquire_date, ingredients[j].shelf_life);
	}

	// Attach everything
		quantityCell.appendChild(quantityCellContent);
		nameCell.appendChild(nameCellContent);
		durationCell.appendChild(durationCellContent);
	    row.appendChild(quantityCell);
	    row.appendChild(nameCell);
	    row.appendChild(durationCell);
	ingr_table.appendChild(row);
    }
    
    
    
    // ------- Table 2 -------
    
    
    
    // Locate the ingredient table
    const recipe_table = document.getElementById(`recipe-table`);

    // Loop between inredient items
    // Each table contains a full list of ingredients and then a lot of them are hidden based on the filters applied to the table
    for (let j = 0; j < recipes.length; j++) {

	// Table containers, like rows and cells
	const row = document.createElement("tr");
	    const nameCell = document.createElement("td");
	    const matchrateCell = document.createElement("td");
		const nameCellContent = document.createElement("p");
		const matchrateCellContent = document.createElement("p");

	// Add classes for css control
	nameCell.classList.add("label_cell");
	matchrateCell.classList.add("shelf_life_cell");


	
	// Fill in missing ingredient quantity
	let missingIngredientsCount = 0;
	for (let i = 0; i < recipes[j].ingredients.length; i++) {
	    if (getIngredientQuantity(recipes[j].ingredients[i].id) === 0) {
		missingIngredientsCount++;
	    }
	}
	if (missingIngredientsCount !== 0) {
	    matchrateCellContent.innerHTML = `${missingIngredientsCount} missing`;
	    nameCellContent.innerHTML = `${recipes[j].name}`;
	} else {
	    nameCellContent.innerHTML = `<b>${recipes[j].name}<b>`;
	}
	
	// Hover effect
	row.addEventListener("mouseenter", () => {
	    // Locate all ingredient rows
	    // Color them green if ingredient row has positive quantity
	    // Color them red if ingredient row has 0 quantity
	    if (!isHighlightFrozen) {
		for (let i = 0; i < recipes[j].ingredients.length; i++)
		{
		    highlightIngredientRow(recipes[j].ingredients[i].id);
		}
	    }
	});
	
	// On hover away
	row.addEventListener("mouseleave", () => {
	    if (!isHighlightFrozen) { removeHighlightFromAllRows(); }
	});
	
	// On click
	row.addEventListener('click', (element) => {
	    // Document click would occur after this function finishes, stopPropagation() prevents this click from bubbling up
	    // And yet I do still need this event to behave like document click as well as row click
	    // So I recreate all actions that document click would do
	    element.stopPropagation(); 
	    isHighlightFrozen = false;
	    
	    // If this row is already selected then I want to remove selection, so I will stop here
	    if (row.classList.contains('selected_row')) {
		removeHighlightFromAllRows();
		return;
	    }
	    
	    // If selecting a different row then re-highlight
	    removeHighlightFromAllRows();
	    row.dispatchEvent(new MouseEvent('mouseenter'));
	    highlightRecipeRow(row);
	    isHighlightFrozen = true;
	});

	// Attach everything
		matchrateCell.appendChild(matchrateCellContent);
		nameCell.appendChild(nameCellContent);
	    row.appendChild(nameCell);
	    row.appendChild(matchrateCell);
	recipe_table.appendChild(row);
    }

</script>

<?php include 'footer.php';