<?php

session_start();
include("../config/conexion.php");
include("../config/role_sync.php");

if (isset($_POST['save'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // ── Admin hardcoded ──
    if ($email === 'admin@gmail.com' && $password === 'admin') {
        $_SESSION['user'] = [
            'id'        => 0,
            'full_name' => 'Administrador',
            'email'     => 'admin@gmail.com',
            'role'      => 'admin',
        ];
        header("Location: ../app/admin_dashboard.php");
        exit();
    }

    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        /* Sincronizar rol antes de guardar en sesión */
        sync_user_role($conn, (int)$user['id']);

        /* Re-leer para obtener el rol actualizado */
        $refreshed = mysqli_query($conn, "SELECT * FROM users WHERE id = {$user['id']} LIMIT 1");
        $_SESSION['user'] = mysqli_fetch_assoc($refreshed);

        header("Location: ../app/inde.php");
    }
}
