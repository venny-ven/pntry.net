
export default class Ingredient {

    // Fixed, glossary variables
    #id;
    #name;
    #tableId;
    #measurementUnit;
    #recipes = []; // References to recipes that contain this ingredient so I can dynamically update them

    // Dynamic, instance variables, can be null
    #quantity;
    #remainingDays;

    // DOM elements that need to be accessed periodically
    #row;
    #nameP;
    #quantityP;
    #remainingDaysP;
    
    tableId() { return this.#tableId; }
    row() { return this.#row; }
    id() { return this.#id; }
    quantity() { return this.#quantity; }

    constructor(dbRow) {
	this.#id = dbRow.id;
	this.#name = dbRow.name;
	this.#tableId = dbRow.table_id;
	this.#measurementUnit = dbRow.measurement_unit;
	this.#quantity = dbRow.quantity; // DB gives 0 if there is no instance for this ingredient

	// If ingredient has an inventory instance, calculate remaining days
	// shelf_life check because some ingredients do not expire
	if (this.#quantity !== 0 && dbRow.shelf_life !== null) {
	    const daysSinceAcquire = Math.floor((dbRow.fetch_timestamp - dbRow.acquire_date) / (1000 * 60 * 60 * 24));
	    this.#remainingDays = dbRow.shelf_life; - daysSinceAcquire;
	}

	// Define DOM elements
	this.#row = document.createElement("tr");
	    const removeCell = document.createElement("td");
	    const addCell = document.createElement("td");
	    const quantityCell = document.createElement("td");
	    const nameCell = document.createElement("td");
	    const remainingDaysCell = document.createElement("td");
		const removeButton = document.createElement("button");
		const addButton = document.createElement("button");
		this.#quantityP = document.createElement("p");
		this.#nameP = document.createElement("p");
		this.#remainingDaysP = document.createElement("p");

	// Add classes for css control
	removeButton.classList.add("remove-button"); // Defines shape and color of buttons
	addButton.classList.add("add-button");
	quantityCell.classList.add("left-cell"); // Defines padding and borders
	nameCell.classList.add("center-cell");
	remainingDaysCell.classList.add("right-cell");

	// "+" button event listener
	addButton.textContent = "+";
	addButton.addEventListener("click", () => {		
	    this.#quantity++;

	    // Add new inventory instance
	    if (this.#quantity === 1) {
		this.#remainingDays = dbRow.shelf_life;
		for (const recipe of this.#recipes) {
		    recipe.updateRow(); // update recipe's missing count
		}
	    }
	    this.updateRow('add-effect');
	});

	// "-" button event listener
	removeButton.textContent = "−";
	removeButton.addEventListener("click", () => {
	    if (this.#quantity > 0) {
		this.#quantity--;
		this.updateRow('remove-effect');
	    }
	    // Remove inventory instance
	    if (this.#quantity === 0) {
		for (const recipe of this.#recipes) {
		    recipe.updateRow(); // update recipe's missing count
		}
	    }
	});

	// Attach everything
	    removeCell.appendChild(removeButton);
	    addCell.appendChild(addButton);
	    quantityCell.appendChild(this.#quantityP);
	    nameCell.appendChild(this.#nameP);
	    remainingDaysCell.appendChild(this.#remainingDaysP);
	this.#row.appendChild(removeCell);
	this.#row.appendChild(addCell);
	this.#row.appendChild(quantityCell);
	this.#row.appendChild(nameCell);
	this.#row.appendChild(remainingDaysCell);
	
	// Update dynamic elements
	this.updateRow();
    }

    // Updates all visible non static elements of the row to match internal object data
    // Additionally can pass a css effect along with the update
    updateRow(CssEffect) {
	
	this.#nameP.innerHTML = this.#quantity ? `<b>${this.#name}</b>` : this.#name;
	this.#quantityP.innerHTML = `${this.#quantity} ${this.#measurementUnit}`;
	this.#remainingDaysP.innerHTML = this.#remainingDays ? `${this.#remainingDays} d` : "";

	if (this.#remainingDays < 0) {
	    this.#remainingDaysP.classList.add('danger-text');
	} else if (this.#remainingDays < 2) {
	    this.#remainingDaysP.classList.add('warning-text');
	}
	
	this.#row.classList.remove('add-effect', 'remove-effect', 'available-effect');
	if (CssEffect) {
	    // Accessing offsetHeight value forces rescan
	    // I need a rescan otherwise the effect won't replay when clicked multiple times
	    void this.#row.offsetHeight; 
	    this.#row.classList.add(CssEffect);
	}
    }
    
    addRecipe(recipe) {
	this.#recipes.push(recipe);
    }
}