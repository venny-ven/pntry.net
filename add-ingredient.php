<?php include 'header.php'; ?>

<p>Add an ingredient to the database so you can track its amount in the future.</p>

<?php include 'ingredient-form.php' ?>

<?php
// If vieweing after a form was submitted
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING) === 'POST') { // === is strict equality (value AND type)
    // Prevent HTML tag injection
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    echo "<p>You have added a new ingredient <b>$name</b> under a category <b>$category</b></p>";
}
?>

</body>
</html>