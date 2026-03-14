<?php
include("../../../config/conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];
    $estado = $_POST['estado'];
    // Insertar el producto en la base de datos
    $query = "INSERT INTO products (title, price, description, longitude, latitude, category_id, status)
          VALUES ('$nombre', '$precio', '$descripcion', '$longitude', '$latitude', '$categoria', '$estado')";

    if (mysqli_query($conn, $query)) {
        $product_id = mysqli_insert_id($conn);

        // Manejar la subida de fotos
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {

            $image_name = time() . "_" . $_FILES['fotos']['name'][$key];
            $path = "../uploads/" . $image_name;

            if (move_uploaded_file($tmp_name, $path)) {

                $image_query = "INSERT INTO product_images (product_id, image_url) 
                        VALUES ('$product_id', '$image_name')";

                mysqli_query($conn, $image_query);
            }
        }

        header("Location: ../crear.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
