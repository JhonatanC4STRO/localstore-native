<?php

session_start();
include("../config/conexion.php");
include("../config/role_sync.php");

if (isset($_POST['save'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // ── Admin hardcoded (se garantiza fila real en users para que pueda publicar, chatear, comprar, etc.) ──
    if ($email === 'admin@gmail.com' && $password === 'admin') {
        $adminRow = null;

        $qAdmin = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($qAdmin, 's', $email);
        mysqli_stmt_execute($qAdmin);
        $rAdmin = mysqli_stmt_get_result($qAdmin);
        if ($rAdmin && mysqli_num_rows($rAdmin) > 0) {
            $adminRow = mysqli_fetch_assoc($rAdmin);
        }

        if (!$adminRow) {
            /* Primer login: crear el usuario admin real */
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = mysqli_prepare($conn, "
                INSERT INTO users (full_name, email, password, role, status, created_at)
                VALUES ('Administrador', ?, ?, 'admin', 'active', NOW())
            ");
            mysqli_stmt_bind_param($ins, 'ss', $email, $hash);
            mysqli_stmt_execute($ins);
            $adminId = mysqli_insert_id($conn);

            $qAdmin = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($qAdmin, 'i', $adminId);
            mysqli_stmt_execute($qAdmin);
            $adminRow = mysqli_fetch_assoc(mysqli_stmt_get_result($qAdmin));
        } else {
            /* Asegura rol admin y estado activo por si alguien los cambió */
            if (($adminRow['role'] ?? '') !== 'super_admin') {
                mysqli_query($conn, "UPDATE users SET role='admin', status='active', deleted_at=NULL WHERE id=" . (int)$adminRow['id']);
                $q2 = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
                $aid = (int)$adminRow['id'];
                mysqli_stmt_bind_param($q2, 'i', $aid);
                mysqli_stmt_execute($q2);
                $adminRow = mysqli_fetch_assoc(mysqli_stmt_get_result($q2));
            }
        }

        $_SESSION['user'] = $adminRow;
        header("Location: ../views/admin/dashboard.php");
        exit();
    }

    $email_esc = mysqli_real_escape_string($conn, $email);
    $query  = "SELECT * FROM users WHERE email = '$email_esc' LIMIT 1";
    $result = mysqli_query($conn, $query);

    $user       = mysqli_num_rows($result) > 0 ? mysqli_fetch_assoc($result) : null;
    $authenticated = false;

    if ($user) {
        $stored = (string)($user['password'] ?? '');
        /* Soporta contraseñas con hash (bcrypt/argon2) y contraseñas en texto plano
           creadas por el flujo de registro antiguo. */
        if ($stored !== '' && $stored[0] === '$' && password_verify($password, $stored)) {
            $authenticated = true;
        } elseif (hash_equals($stored, $password)) {
            $authenticated = true;
        }
    }

    if ($authenticated) {
        /* Sincronizar rol antes de guardar en sesión */
        sync_user_role($conn, (int)$user['id']);

        /* Re-leer para obtener el rol actualizado */
        $refreshed = mysqli_query($conn, "SELECT * FROM users WHERE id = {$user['id']} LIMIT 1");
        $fresh = mysqli_fetch_assoc($refreshed);

        /* Bloquear cuentas suspendidas/bloqueadas/eliminadas */
        $acctStatus = $fresh['status'] ?? 'active';
        if (!empty($fresh['deleted_at'])) {
            header("Location: ../views/auth/login.php?error=deleted");
            exit();
        }
        if ($acctStatus === 'suspended' || $acctStatus === 'blocked') {
            header("Location: ../views/auth/login.php?error=$acctStatus");
            exit();
        }

        $_SESSION['user'] = $fresh;

        header("Location: ../views/home.php");
        exit();
    } else {
        header("Location: ../views/auth/login.php?error=1");
        exit();
    }
}





