<?php

require_once 'db.php';
$pdo = getPDO();

require_once 'session.php';
$sessionId = getSessionId();

require_once 'db-request.php';
$dbInventory = requestInventory($sessionId);
$dbRecipes = requestRecipes();

?>



<!-- ------- HTML scaffold  ------- -->



<!-- For now the header is built in -->
<!DOCTYPE html> <!-- Use HTML 5 standard -->
<html lang="en"> <!-- Indicate that this page is an english version -->
<head>
    <meta charset="UTF-8"> <!-- Use modern character set -->
    <title>Pntry.net</title>
    <link rel="icon" type="image/x-icon" href="favicon.svg">
    <link rel="stylesheet" href="new-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="grid-body"> <!-- One body for the entire screen, defines the layout of elements inside it. Grid body divides internal elements into 3 equal columns -->
    <div class="vertical-pane"> <!-- One of three equally spaced columns separated by a gap. Each column divides internal elements into 2 equal rows -->
	<div class="horizontal-pane"> <!-- One of two equally spaced rows separated by a gap. Each row is a pane that contains a table -->
	    <table>
		<colgroup> <!-- Column group is used to control column width in style.css -->
		    <col class="ingredient-remove-column button-column">
		    <col class="ingredient-add-column button-column">
		    <col class="ingredient-quantity-column">
		    <col class="ingredient-name-column">
		    <col class="ingredient-remaining-days-column">
		</colgroup>
		<thead><tr><th colspan="5"><p>Meat, Eggs, Dairy, & Tofu</p></th></tr></thead>
		<tbody id="ingredient-table-body-1">
		</tbody>
	    </table>
	</div>
	<div class="horizontal-pane"> <!-- Second row of the first column -->
	    <table>
		<colgroup>
		    <col class="ingredient-remove-column button-column">
		    <col class="ingredient-add-column button-column">
		    <col class="ingredient-quantity-column">
		    <col class="ingredient-name-column">
		    <col class="ingredient-remaining-days-column">
		</colgroup>
		<thead><tr><th colspan="5"><p>Pasta, Rice, & Starchy Vegetables</p></th></tr></thead>
		<tbody id="ingredient-table-body-2">
		</tbody>
	    </table>
	</div>
    </div>
    <div class="vertical-pane"> <!-- Second column -->
	<div class="horizontal-pane"> <!-- First row of the second column -->
	    <table>
		<colgroup>
		    <col class="ingredient-remove-column button-column">
		    <col class="ingredient-add-column button-column">
		    <col class="ingredient-quantity-column">
		    <col class="ingredient-name-column">
		    <col class="ingredient-remaining-days-column">
		</colgroup>
		<thead><tr><th colspan="5"><p>Fruits & Vegetables</p></th></tr></thead>
		<tbody id="ingredient-table-body-3">
		</tbody>
	    </table>
	</div>
	<div class="horizontal-pane"> <!-- Second row of the second column -->
	    <table>
		<colgroup>
		    <col class="ingredient-remove-column button-column">
		    <col class="ingredient-add-column button-column">
		    <col class="ingredient-quantity-column">
		    <col class="ingredient-name-column">
		    <col class="ingredient-remaining-days-column">
		</colgroup>
		<thead><tr><th colspan="5"><p>Seasoning & Baking</p></th></tr></thead>
		<tbody id="ingredient-table-body-4">
		</tbody>
	    </table>
	</div>
    </div>
    <div class="vertical-pane dashboard-pane"> <!-- Third column is unique, it has a dashboard at the top and a single column below it -->
	<div class="dashboard"> <!-- Dashboard -->
	
	</div>
	<div class="horizontal-pane">
	    <table>
		<colgroup>
		    <col class="recipe-name-column">
		    <col class="recipe-missing-count-column">
		</colgroup>
		<thead><tr><th><p>Recipes</p></th></tr></thead>
		<tbody id="recipe-table-body">
		</tbody>
	    </table>
	</div>
    </div>
</body>

<script>
    // Pass PHP data to JavaScript
    window.dbInventory = <?php echo json_encode($dbInventory); ?>;
    window.dbRecipes = <?php echo json_encode($dbRecipes); ?>;
</script>
<script type="module" src="./dashboard.js"></script>