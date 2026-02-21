export default class Recipe {
	
    #ingredients = []; // Direct references to ingredient objects, able to manipulate them from here
    #name;
    #row;
    #missingCountP;
    #nameP;
    #id;
    
    id() { return this.#id; }
    row() { return this.#row; }
	
    constructor(dbRow) {

	this.#id = dbRow.recipe_id;
	this.#name = dbRow.recipe_name;
	
	// Define DOM elements
	this.#row = document.createElement("tr");
	    const nameCell = document.createElement("td");
	    const missingCountCell = document.createElement("td");
		this.#nameP = document.createElement("p");
		this.#missingCountP = document.createElement("p");

	// Add classes for css control
	nameCell.classList.add("left-cell"); // Defines padding and borders
	missingCountCell.classList.add("right-cell");
	
	// Effects
	this.#row.addEventListener("mouseenter", () => {
	// Locate all ingredient rows
	// Resort
	// Color them green if ingredient row has positive quantity
	// Color them red if ingredient row has 0 quantity
	    for (const ingredient of this.#ingredients) {
		ingredient.updateRow('available-effect');
	    }
	});
	this.#row.addEventListener("mouseleave", () => {
	    for (const ingredient of this.#ingredients) {
		ingredient.updateRow(null); // Remove effects
	    }
	});
	this.#row.addEventListener('click', (element) => {
	    // Document click would occur after this function finishes, stopPropagation() prevents this click from bubbling up
	    // And yet I do still need this event to behave like document click as well as row click
	    // So I recreate all actions that document click would do
//	    element.stopPropagation(); 
//	    isHighlightFrozen = false;
	    
	    // If this row is already selected then I want to remove selection, so I will stop here
//	    if (row.classList.contains('selected_row')) {
//		removeHighlightFromAllRows();
//		return;
//	    }
	    
	    // If selecting a different row then re-highlight
//	    removeHighlightFromAllRows();
//	    row.dispatchEvent(new MouseEvent('mouseenter'));
//	    highlightRecipeRow(row);
//	    isHighlightFrozen = true;
	});

	// Attach everything
	    missingCountCell.appendChild(this.#missingCountP);
	    nameCell.appendChild(this.#nameP);
	this.#row.appendChild(nameCell);
	this.#row.appendChild(missingCountCell);
	
	// Update dynamic elements
	// No need because the generation process outside (tables.js) updates all of them
	//updateRow();
    }
    
    updateRow() {
	// update missing count
	let missingCount = 0;
	for (const ingredient of this.#ingredients) {
	    if (ingredient.quantity() === 0) { missingCount++; }
	}
	this.#missingCountP.innerHTML = missingCount ? `${missingCount} missing` : "";
	this.#nameP.innerHTML = missingCount ? this.#name : `<b>${this.#name}<b>`;
    }
    
    addIngredient(ingredient) {
	this.#ingredients.push(ingredient);
    }
}

