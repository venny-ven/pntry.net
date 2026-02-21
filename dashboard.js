'use strict';
import Ingredient from './ingredient.js';
import Recipe from './recipe.js';

// Receive data from index.php
const dbInventory = window.dbInventory;
const dbRecipes = window.dbRecipes;

// Define core objects
const ingredientTables = new Map(); // Key: table id, value: <tbody>
    for (let i = 1; i <= 4; i++) {
	ingredientTables.set(i, document.getElementById(`ingredient-table-body-${i}`));
    }
const recipeTable = document.getElementById(`recipe-table-body`);
const ingredients = new Map(); // Key: ingredient id, value: ingredient object
const recipes = new Map(); // Key: recipe id, value: recipe object

// Initialize ingredients and populate tables
for (const dbRow of dbInventory) {
    const ingredient = new Ingredient(dbRow);

    // Add to maps
    ingredients.set(ingredient.id(), ingredient);
    ingredientTables.get(ingredient.tableId()).appendChild(ingredient.row());
}

// Initialize recipes, populate table, and add references to ingredients
for (const dbRow of dbRecipes) {
    // If recipe_id is new, create a new Recipe
    if (!recipes.has(dbRow.recipe_id)) {
	const recipe = new Recipe(dbRow);
	recipes.set(dbRow.recipe_id, recipe);
	recipeTable.appendChild(recipe.row());
    }
    // Add ingredient to the recipe and add recipe to the ingredient
    const ingredient = ingredients.get(dbRow.ingredient_id);
    const recipe = recipes.get(dbRow.recipe_id);
    ingredient.addRecipe(recipe);
    recipe.addIngredient(ingredient);
}

// Re-run recipe list and update visuals
for (const recipe of recipes.values()) {
    recipe.updateRow();
}

    // Sort: missing first, then have, then others
    // Within each group, sort by name
//    rows.sort((a, b) => {
//        if (a.sortPriority !== b.sortPriority) {
//            return a.sortPriority - b.sortPriority;
//        }
//        return a.name.localeCompare(b.name);
//    });

// Freeze row highlights when clicking on a row
//let isHighlightFrozen = false;
//document.addEventListener('click', () => {
//    isHighlightFrozen = false;
//    removeHighlightFromAllRows();
//});