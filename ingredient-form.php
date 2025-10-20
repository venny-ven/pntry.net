<form action="add-ingredient.php" method="POST">
    <label>Ingredient Name: <input type="text" name="name"></label>
    <select name="category" required>
        <option value="">-- Select Category --</option>
        <option value="Protein">Protein</option>
        <option value="Starch">Starch</option>
        <option value="Fruit">Fruit</option>
        <option value="Vegetable">Vegetable</option>
        <option value="Dairy">Dairy</option>
        <option value="Seasoning">Seasoning</option>
    </select>
    <br><br>
    <input type="submit" value="Add Ingredient">
</form>