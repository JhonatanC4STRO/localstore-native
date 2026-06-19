<?php
session_start();
require_once __DIR__ . "/../config/conexion.php";

require_once __DIR__ . "/../config/csrf.php";

if (!isset($_SESSION['migrate_user_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    // Validar token CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        log_error("Fallo de validación de token CSRF en cambio de contraseña.", "SECURITY");
        header("Location: ../views/auth/reset_password.php?error=1");
        exit();
    }
    $user_id = (int)$_SESSION['migrate_user_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validar contraseña
    if (empty($password) || strlen($password) < 6 || $password !== $confirm_password) {
        header("Location: ../views/auth/reset_password.php?error=1");
        exit();
    }

    // Cifrar la contraseña con password_hash (BCRYPT)
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Actualizar la contraseña en la base de datos usando consulta preparada
    $query = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Error de preparación: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        header("Location: ../views/auth/reset_password.php?error=1");
        exit();
    }

    // Obtener los datos del usuario para guardarlos en sesión y loguearlo
    $query_user = "SELECT * FROM users WHERE id = ? LIMIT 1";
    $stmt_user = mysqli_prepare($conn, $query_user);
    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "i", $user_id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        $user_row = mysqli_fetch_assoc($result_user);
        mysqli_stmt_close($stmt_user);
        
        // Limpiar la variable de sesión de migración
        unset($_SESSION['migrate_user_id']);
        
        // Loguear usuario
        session_regenerate_id(true);
        $_SESSION['user'] = $user_row;
        
        // Redirigir dinámicamente según el rol
        $role = $user_row['role'] ?? 'user';
        if ($role === 'admin' || $role === 'super_admin') {
            header("Location: ../views/admin/dashboard.php");
        } else {
            header("Location: ../views/home.php");
        }
        exit();
    } else {
        die("Error al leer el usuario tras la migración.");
    }
} else {
    header("Location: ../views/auth/login.php");
    exit();
}
