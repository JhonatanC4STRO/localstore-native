<?php

session_start();
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/role_sync.php";

require_once __DIR__ . "/../config/csrf.php";

if (isset($_POST['save'])) {
    // Validar token CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        log_error("Fallo de validación de token CSRF en login.", "SECURITY");
        header("Location: ../views/auth/login.php?error=csrf");
        exit();
    }

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // ── Admin desde variables de entorno (definidas en el despliegue) o fallbacks por defecto ──
    $admin_email = getenv('ADMIN_EMAIL') ?: 'admin@gmail.com';
    $admin_pass  = getenv('ADMIN_PASSWORD') ?: 'admin';

    if ($email === $admin_email && $password === $admin_pass) {
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

        // Forzar cambio de contraseña al primer inicio (cuando ingresa con las credenciales por defecto de despliegue)
        $_SESSION['migrate_user_id'] = $adminRow['id'];
        header("Location: ../views/auth/reset_password.php?migrate=1");
        exit();
    }

    // Usar consulta preparada para buscar el usuario por email de forma segura
    $stmt_user = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    if (!$stmt_user) {
        die("Error de preparación: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_user, "s", $email);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);

    $authenticated = false;

    if ($user) {
        $stored = (string)($user['password'] ?? '');
        
        // Comprobar si la contraseña guardada está encriptada (comienza con $)
        if ($stored !== '' && $stored[0] === '$') {
            if (password_verify($password, $stored)) {
                $authenticated = true;
            }
        } else {
            // Contraseña antigua en texto plano.
            // NO permitimos el inicio de sesión directo, pero si coincide, forzamos su restablecimiento/migración.
            if ($stored !== '' && hash_equals($stored, $password)) {
                $_SESSION['migrate_user_id'] = $user['id'];
                header("Location: ../views/auth/reset_password.php?migrate=1");
                exit();
            }
        }
    }

    if ($authenticated) {
        /* Sincronizar rol antes de guardar en sesión */
        sync_user_role($conn, (int)$user['id']);

        /* Re-leer para obtener el rol actualizado usando consulta preparada */
        $stmt_fresh = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
        if ($stmt_fresh) {
            $user_id = (int)$user['id'];
            mysqli_stmt_bind_param($stmt_fresh, "i", $user_id);
            mysqli_stmt_execute($stmt_fresh);
            $res_fresh = mysqli_stmt_get_result($stmt_fresh);
            $fresh = mysqli_fetch_assoc($res_fresh);
            mysqli_stmt_close($stmt_fresh);
        } else {
            $fresh = $user;
        }

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

        session_regenerate_id(true);
        $_SESSION['user'] = $fresh;

        header("Location: ../views/home.php");
        exit();
    } else {
        header("Location: ../views/auth/login.php?error=1");
        exit();
    }
}





