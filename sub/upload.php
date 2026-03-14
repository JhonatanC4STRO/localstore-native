
<?php include("../src/config/conexion.php");

$title = $_POST['title'];

$query = "INSERT INTO produc(title) VALUES('$title')";
mysqli_query($conn,$query);

$product_id = mysqli_insert_id($conn);

foreach($_FILES['images']['tmp_name'] as $key => $tmp){

    $image_name = time()."_".$_FILES['images']['name'][$key];
    $path = "uploads/".$image_name;

    move_uploaded_file($tmp,$path);

    $query = "INSERT INTO produ_image(product_id,image_path) 
              VALUES('$product_id','$image_name')";

    mysqli_query($conn,$query);
}

header("Location: index.php");

?>