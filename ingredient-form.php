<form action="add-ingredient.php" method="POST">
    <label>Ingredient Name: <input type="text" name="name"></label>
    <select name="category" required>
        <option value="">-- Select Category --</option>
        <option value="protein">Protein</option>
        <option value="starch">Starch</option>
        <option value="vegetable">Vegetable</option>
        <option value="fruit">Fruit</option>
        <option value="dairy">Dairy</option>
        <option value="seasoning">Seasoning</option>
    </select>
    <br><br>
    <input type="submit" value="Add Ingredient">
</form>