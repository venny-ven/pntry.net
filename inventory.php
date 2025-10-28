<?php

include "header.php"; 
$pdo = require_once 'db.php';

?>

<p>Add and remove items from the following list of available options to match what you have in the kitchen.</p>

<!-- Save button at the top of the list with a hidden response label that appears when pressed -->
<button class="save_button" type="button">Save</button> <!-- type='button' is default, 'submit' would do a form request -->
<span class="save_status" style="margin-left: 8px;"></span>

<!-- List of ingredients populated by JS -->
<div id="ingredientPanel" style="display: flex;"></div>

<!-- Same button at the bottom of the list -->
<button class="save_button" type="button">Save</button>
<span class="save_status" style="margin-left: 8px;"></span>

<!-- Retrieve ingredient names and quantities from the database -->
<?php
try
{
    $stmt = $pdo->query('SELECT id, name, quantity FROM ingredient;');
    $ingredientTable = $stmt->fetchAll(PDO::FETCH_ASSOC); // Array of associative arrays - myArr[index].name = 'Sugar', myArr[index].quantity = 1
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the table: " . $ex->getMessage());
}
?>

<!-- Generate ingredient list -->
<script>
    // Import array from php to js, encode to json to sanitize
    let ingredients = <?php echo json_encode($ingredientTable); ?>;
    
    // Define column height by dividing the list length into equal parts. Number of columns is defined as 3 for now
    const columnCount = 3;
    const columnHeight = Math.ceil(ingredients.length / columnCount);

    // Generate HTML for column containers
    const columns = Array(columnCount);
    for (let i = 0; i < columnCount; i++) {
        columns[i] = document.createElement("div");
        columns[i].style.flex = "1";
    }

    // This function runs every time a button is clicked and also once for each ingredient at initialization
    function updateHeading(heading, ingredientName, quantity)
    {
        heading.innerHTML = `${ingredientName} x ${quantity}`;
    }

    // Generate the HTML for each ingredient
    let currentColumn = 0;
    for (let i = 0; i < ingredients.length; i++) {

        // Heading
        const heading = document.createElement("h3");
        updateHeading(heading, ingredients[i].name, ingredients[i].quantity);

        // Containers for buttons
        const outerDiv = document.createElement("div");
            outerDiv.style.display = "flex";
        const innerDiv1 = document.createElement("div");
        const innerDiv2 = document.createElement("div");

        // "Add" button
        const addButton = document.createElement("button");
        addButton.textContent = "Add";
        addButton.style.background = "MediumSeaGreen";
        addButton.addEventListener("click", () => {
            ingredients[i].quantity++;
            updateHeading(heading, ingredients[i].name, ingredients[i].quantity)
        })

        // "Remove" button
        const removeButton = document.createElement("button");
        removeButton.textContent = "Remove";
        removeButton.style.background = "Salmon";
        removeButton.addEventListener("click", () => {
            if (ingredients[i].quantity > 0) {
		ingredients[i].quantity--;
	    }
            updateHeading(heading, ingredients[i].name, ingredients[i].quantity)
        })

        // Horizontal line
        const horizontal = document.createElement("hr");

        // Attach everything to the column
        innerDiv1.appendChild(addButton);
        innerDiv2.appendChild(removeButton);
        outerDiv.appendChild(innerDiv1);
        outerDiv.appendChild(innerDiv2);

        columns[currentColumn].appendChild(heading);
        columns[currentColumn].appendChild(outerDiv);
        columns[currentColumn].appendChild(horizontal);

        // When the column height is reached, move to the next
        if (i % columnHeight === columnHeight-1) {
            currentColumn++;
        }
    }

    // Attach all columns to the document
    container = document.getElementById("ingredientPanel")
    for (let i = 0; i < columnCount; i++) {
        container.appendChild(columns[i]);
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