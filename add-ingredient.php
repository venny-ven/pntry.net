<?php include "header.php"; ?>

    <p>Add an ingredient to the database so you can track its amount in the future. </p>

    <form action="submit-ingredient.php" method="POST">
        <label>Ingredient Name: <input type="text" name="name"></label>
        <select name="type" required>
            <option value="">-- Select Type --</option>
            <option value="protein">Protein</option>
            <option value="starch">Starch</option>
            <option value="fruit">Fruit</option>
            <option value="vegetable">Vegetable</option>
            <option value="dairy">Dairy</option>
            <option value="seasoning">Seasoning</option>
        </select>
        <br><br>
        <input type="submit" value="Submit Ingredient">
    </form>

</body>
</html>