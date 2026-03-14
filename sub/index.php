<?php include("../src/config/conexion.php"); ?>

<h2>Subir producto con varias imágenes</h2>

<form action="upload.php" method="POST" enctype="multipart/form-data">

    <input type="text" name="title" placeholder="Titulo del producto" required>

    <br><br>

    <input type="file" name="images[]" multiple required>

    <br><br>

    <button type="submit">Guardar</button>

</form>

<hr>

<h2>Productos</h2>

<?php

$query = "SELECT * FROM produc";
$result = mysqli_query($conn, $query);

while ($product = mysqli_fetch_assoc($result)) {

    echo "<h3>" . $product['title'] . "</h3>";

    $product_id = $product['id'];

    $img_query = "SELECT * FROM produ_image WHERE product_id = $product_id";
    $img_result = mysqli_query($conn, $img_query);

    while ($img = mysqli_fetch_assoc($img_result)) {
        echo "<img src='uploads/" . $img['image_path'] . "' width='120'>";
    }

    echo "<hr>";
}

?>