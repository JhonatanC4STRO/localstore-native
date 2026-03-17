<?php

session_start();
include("../config/conexion.php");

if (isset($_POST['save'])) {

    $nombre = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 🔥 Validar tipo de usuario
    $type_user = $_POST['type_user'] ?? null;

    if (!$type_user) {
        die("Selecciona el tipo de cuenta");
    }

    $query = "INSERT INTO users(full_name, email, password, type_user) 
              VALUES ('$nombre', '$email', '$password', '$type_user')";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error: " . mysqli_error($conn));
    }

    $_SESSION['message'] = "Usuario registrado correctamente";
    $_SESSION['message_type'] = "success";

    header("Location: ../app/inde.php"); // 🔥 ojo aquí, tenías inde.php
}
