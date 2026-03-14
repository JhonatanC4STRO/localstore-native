<?php

session_start();
include("../config/conexion.php");

if (isset($_POST['save'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['user'] = mysqli_fetch_assoc($result);
        header("Location: ../app/dashboard.php");
    } else {
        $_SESSION['message'] = "Credenciales incorrectas";
        $_SESSION['message_type'] = "error";
        header("Location: ../app/auth/login.php");
    }
}
