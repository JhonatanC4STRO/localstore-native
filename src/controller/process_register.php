<?php

session_start();
include("../config/conexion.php");

if (isset($_POST['save'])) {

    $nombre = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "INSERT INTO users(full_name, email, password) 
              VALUES ('$nombre', '$email', '$password')";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error al guardar usuario");
    }

    $_SESSION['message'] = "Usuario registrado correctamente";
    $_SESSION['message_type'] = "success";

    header("Location: ../app/inde.php");
}
