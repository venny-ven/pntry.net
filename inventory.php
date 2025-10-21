<?php

include "header.php"; 
$pdo = require_once 'db.php';

?>

<p>Add and remove items from the following list of available options to match what you have in the kitchen.</p>
<div id="ingredientPanel" style="display: flex;"></div>

<!-- Generate ingredient list -->
<?php

try
{
    $stmt = $pdo->query('SELECT name, quantity FROM ingredient;');
    $ingredientTable = $stmt->fetchAll(PDO::FETCH_ASSOC); // Array of associative arrays - ['name' => 'Sugar', 'quantity' => '3'], ['name' =>...
} catch (PDOException $ex) {
    die("Could not retrieve ingredients from the table: " . $ex->getMessage());
}
?>

<script>
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
            ingredients[i].quantity--;
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

    // Attach all columns to the HTML document
    container = document.getElementById("ingredientPanel")
    for (let i = 0; i < columnCount; i++) {
        container.appendChild(columns[i]);
	console.log('child appended');
    }

</script>

<?php include 'footer.php';