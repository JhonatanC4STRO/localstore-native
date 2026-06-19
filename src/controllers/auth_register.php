<?php

session_start();
require_once __DIR__ . "/../config/conexion.php";

require_once __DIR__ . "/../config/csrf.php";

if (isset($_POST['save'])) {
    // Validar token CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        log_error("Fallo de validación de token CSRF en registro.", "SECURITY");
        header("Location: ../views/auth/register.php?error=csrf");
        exit();
    }

    $nombre    = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];

    // 1. Validar formato de correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../views/auth/register.php?error=invalid_email");
        exit();
    }

    // 2. Validar que el correo no esté ya registrado
    $check_query = "SELECT id FROM users WHERE email = ? LIMIT 1";
    $stmt_check = mysqli_prepare($conn, $check_query);
    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        $email_exists = mysqli_stmt_num_rows($stmt_check) > 0;
        mysqli_stmt_close($stmt_check);
        
        if ($email_exists) {
            header("Location: ../views/auth/register.php?error=email_exists");
            exit();
        }
    }

    // Cifrar la contraseña con password_hash (BCRYPT)
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    /* Las tiendas nacen como seller; los usuarios nacen como user */
    $formType = $_POST['type_user'] ?? 'usuario';
    $role     = ($formType === 'tienda') ? 'seller' : 'user';

    // Emplear consulta preparada para la inserción
    $query = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Error de preparación: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssss", $nombre, $email, $hashed_password, $role);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        die("Error de ejecución: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);

    /* Iniciar sesión automáticamente tras el registro */
    $new_id = mysqli_insert_id($conn);
    
    // Emplear consulta preparada para obtener los datos del usuario recién registrado
    $query_user = "SELECT * FROM users WHERE id = ? LIMIT 1";
    $stmt_user = mysqli_prepare($conn, $query_user);
    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "i", $new_id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        $user_row = mysqli_fetch_assoc($result_user);
        mysqli_stmt_close($stmt_user);
    } else {
        die("Error de preparación al obtener usuario: " . mysqli_error($conn));
    }
    
    session_regenerate_id(true);
    $_SESSION['user'] = $user_row;

    header("Location: ../views/home.php");
    exit();
}





