<?php

session_start();
include("../config/conexion.php");

if (isset($_POST['save'])) {

    $nombre    = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password  = mysqli_real_escape_string($conn, $_POST['password']);
    $type_user = in_array($_POST['type_user'] ?? '', ['usuario', 'tienda'])
                 ? $_POST['type_user']
                 : 'usuario';

    /* Las tiendas nacen como seller; los usuarios nacen como user */
    $role = ($type_user === 'tienda') ? 'seller' : 'user';

    $query = "INSERT INTO users (full_name, email, password, type_user, role)
              VALUES ('$nombre', '$email', '$password', '$type_user', '$role')";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error: " . mysqli_error($conn));
    }

    /* Iniciar sesión automáticamente tras el registro */
    $new_id   = mysqli_insert_id($conn);
    $user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $new_id LIMIT 1"));
    $_SESSION['user'] = $user_row;

    header("Location: ../app/inde.php");
}
