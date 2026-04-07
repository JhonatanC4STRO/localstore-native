<?php
session_start();
include(__DIR__ . '/../config/conexion.php');

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = (int)$user['id'];
$isLoggedIn  = true;
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName = explode(' ', $user['full_name'])[0];

/* Handle form submission */
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    /* ── 1. Profile text fields ── */
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));

    /* ── 2. Photo upload ── */
    $profile_photo_set = '';
    if (!empty($_FILES['profile_photo']['tmp_name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['profile_photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errorMsg = 'Formato no permitido. Usa JPG, PNG o WEBP.';
        }
        elseif ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
            $errorMsg = 'La imagen no debe superar 2 MB.';
        }
        else {
            $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $extMap[$mime];
            $filename = 'u' . $user_id . '_' . time() . '.' . $ext;
            $avatarDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($avatarDir))
                mkdir($avatarDir, 0755, true);
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $avatarDir . $filename)) {
                $profile_photo_set = ", profile_photo='" . mysqli_real_escape_string($conn, $filename) . "'";
            }
            else {
                $errorMsg = 'No se pudo guardar la imagen (permisos de directorio).';
            }
        }
    }

    /* ── 3. Password change ── */
    $pw_set = '';
    if (empty($errorMsg) && !empty($_POST['new_password'])) {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        if (strlen($new_pw) < 8) {
            $errorMsg = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }
        elseif ($new_pw !== $confirm_pw) {
            $errorMsg = 'Las contraseñas nuevas no coinciden.';
        }
        else {
            $res_pw = mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id LIMIT 1");
            $row_pw = $res_pw ? mysqli_fetch_assoc($res_pw) : null;
            if (!$row_pw || !password_verify($current_pw, $row_pw['password'])) {
                $errorMsg = 'La contraseña actual no es correcta.';
            }
            else {
                $new_hash = mysqli_real_escape_string($conn, password_hash($new_pw, PASSWORD_DEFAULT));
                $pw_set = ", password='$new_hash'";
            }
        }
    }

    /* ── 4. Build and run UPDATE ── */
    if (empty($errorMsg)) {
        /* Detect if optional columns exist */
        $has_extra = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'city'")) > 0;
        if ($has_extra) {
            $sql_upd = "UPDATE users
                        SET full_name='$full_name', phone='$phone',
                            city='$city', address='$address', bio='$bio'
                            {$profile_photo_set} {$pw_set}
                        WHERE id=$user_id";
        }
        else {
            $sql_upd = "UPDATE users
                        SET full_name='$full_name', phone='$phone'
                            {$profile_photo_set} {$pw_set}
                        WHERE id=$user_id";
        }

        $ok = mysqli_query($conn, $sql_upd);
        if ($ok) {
            $_SESSION['user']['full_name'] = $full_name;
            $user = $_SESSION['user'];
            $userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
            $userName = explode(' ', $user['full_name'])[0];
            $successMsg = empty($pw_set)
                ? 'Perfil actualizado correctamente'
                : 'Perfil y contraseña actualizados correctamente';
        }
        else {
            $errorMsg = 'Error al guardar: ' . mysqli_error($conn);
        }
    }
}


/* Fresh user data from DB */
$res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($res) ?: $user;

/* Product count for sidebar */
$prod_count = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE user_id = $user_id");
if ($r) { $row = mysqli_fetch_assoc($r); $prod_count = $row['c']; }

/* Profile completion */
$completionFields = [
    'full_name' => !empty($user['full_name']),
    'email' => !empty($user['email']),
    'phone' => !empty($user['phone']),
    'city' => !empty($user['city']),
    'address' => !empty($user['address']),
    'bio' => !empty($user['bio']),
    'photo' => !empty($user['profile_photo']),
];
$completionPct = (int)round(array_sum($completionFields) / count($completionFields) * 100);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil – ComercioLocal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../style/perfil.css">
</head>

<body><?php $basePath = '../';
include __DIR__ . '/../components/header.php'; ?><div class="page-shell">

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar">
            <div class="sb-section-label">Principal</div>
            <a class="sb-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
            <a class="sb-link" href="./misProductos.php">
                <i class="bi bi-box-seam"></i> Mis productos
                <span class="sb-badge"><?php echo $prod_count ?? 0; ?></span>
            </a>
            <a class="sb-link" href="./chat.php">
                <i class="bi bi-chat-dots"></i> Mensajes
                <span class="sb-badge yellow">5</span>
            </a>
            <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>
            <div class="sb-divider"></div>
            <div class="sb-section-label">Cuenta</div>
            <a class="sb-link active" href="./perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
            <a class="sb-link" href="#"><i class="bi bi-star"></i> Reseñas</a>
            <a class="sb-link" href="#"><i class="bi bi-gear"></i> Configuración</a>
            <a class="sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </a>
            <div class="sb-divider"></div>
            <div class="sb-promo">
                <div class="sb-promo-icon">⭐</div>
                <p>Destaca tu anuncio y llega a 10× más compradores hoy.</p>
                <a href="#">Ver planes</a>
            </div>
        </aside>

        <!-- ══ MAIN ══ -->
        <main class="main-content">

            <div class="pg-header">
                <div class="pg-breadcrumb">
                    <a href="./inde.php">Inicio</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Mi perfil</span>
                </div>
                <h1 class="pg-title">Mi <span>perfil</span></h1>
                <p class="pg-subtitle">Administra tu información personal y configuración de cuenta.</p>
            </div>

            <?php if ($successMsg): ?>
                <div class="toast success">
                    <div class="toast-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="toast-text">
                        <strong>¡Listo!</strong>
                        <span><?php echo htmlspecialchars($successMsg); ?></span>
                    </div>
                </div>
            <?php
elseif ($errorMsg): ?>
                <div class="toast error">
                    <div class="toast-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                    <div class="toast-text">
                        <strong>Error</strong>
                        <span><?php echo htmlspecialchars($errorMsg); ?></span>
                    </div>
                </div>
            <?php
endif; ?>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="save_profile" value="1">

                <div class="profile-grid">

                    <!-- ── LEFT COLUMN ── -->
                    <div class="left-col">

                        <!-- Avatar & identity -->
                        <div class="card avatar-card">
                            <div class="card-body" style="text-align:center;padding:28px 22px;">

                                <div class="avatar-wrap">
                                    <div class="avatar-lg">
                                        <?php if (!empty($user['profile_photo'])): ?>
                                            <img src="./uploads/avatars/<?php echo htmlspecialchars($user['profile_photo']); ?>" id="avatarPreview" alt="">
                                        <?php
else: ?>
                                            <span id="avatarInitial"><?php echo $userInitial; ?></span>
                                            <img src="" id="avatarPreview" alt="" style="display:none;">
                                        <?php
endif; ?>
                                    </div>
                                    <div class="avatar-online"></div>
                                    <label class="avatar-upload-btn" title="Cambiar foto">
                                        <i class="bi bi-camera-fill"></i>
                                        <input type="file" name="profile_photo" accept="image/*" onchange="previewAvatar(this)" style="position:absolute;inset:0;opacity:0;cursor:pointer;border-radius:50%;">
                                    </label>
                                </div>

                                <div class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Tu nombre'); ?></div>
                                <div class="profile-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>

                                <div class="account-badge">
                                    <i class="bi bi-person-fill"></i>
                                    Cuenta Personal
                                </div>

                                <div class="profile-stats">
                                    <div class="ps-item">
                                        <?php /* $prod_count already computed in header */ ?>
                                        <div class="ps-num"><?php echo $prod_count; ?></div>
                                        <div class="ps-lbl">Anuncios</div>
                                    </div>
                                    <div class="ps-item">
                                        <div class="ps-num">4.8</div>
                                        <div class="ps-lbl">Rating</div>
                                    </div>
                                    <div class="ps-item">
                                        <div class="ps-num">0</div>
                                        <div class="ps-lbl">Ventas</div>
                                    </div>
                                </div>

                                <div style="font-size:.78rem;color:var(--ink3);display:flex;align-items:center;gap:5px;justify-content:center;">
                                    <i class="bi bi-calendar3" style="color:var(--g400);"></i>
                                    Miembro desde <?php echo date('Y', strtotime($user['created_at'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Profile completion -->
                        <div class="card completion-card">
                            <div class="card-header">
                                <div class="ch-icon chi-yellow"><i class="bi bi-stars"></i></div>
                                <div class="card-header-text">
                                    <h3>Completa tu perfil</h3>
                                    <p>Un perfil completo genera más confianza</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="completion-pct-wrap">
                                    <span class="completion-pct-label">Completado</span>
                                    <span class="completion-pct-num"><?php echo $completionPct; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?php echo $completionPct; ?>%;"></div>
                                </div>
                                <div class="completion-items">
                                    <?php
$completionLabels = [
    'full_name' => ['Nombre completo', 'bi-person-fill'],
    'email' => ['Correo electrónico', 'bi-envelope-fill'],
    'phone' => ['Número de teléfono', 'bi-telephone-fill'],
    'city' => ['Ciudad', 'bi-geo-alt-fill'],
    'address' => ['Dirección', 'bi-house-fill'],
    'bio' => ['Descripción / Bio', 'bi-card-text'],
    'photo' => ['Foto de perfil', 'bi-camera-fill'],
];
foreach ($completionLabels as $key => [$label, $icon]):
    $done = $completionFields[$key];
?>
                                        <div class="ci-row">
                                            <div class="ci-icon <?php echo $done ? 'ci-done' : 'ci-missing'; ?>">
                                                <i class="bi <?php echo $done ? 'bi-check-lg' : 'bi-dash-lg'; ?>"></i>
                                            </div>
                                            <span class="ci-label">
                                                <i class="bi <?php echo $icon; ?>" style="color:var(--g400);margin-right:4px;font-size:.8rem;"></i>
                                                <?php echo $label; ?>
                                            </span>
                                            <?php if (!$done): ?>
                                                <span class="ci-action">Agregar</span>
                                            <?php
    else: ?>
                                                <i class="bi bi-check-circle-fill" style="color:var(--g400);font-size:.8rem;"></i>
                                            <?php
    endif; ?>
                                        </div>
                                    <?php
endforeach; ?>
                                </div>
                            </div>
                        </div>

                    </div><!-- /left-col -->

                    <!-- ── RIGHT COLUMN ── -->
                    <div class="right-col">

                        <!-- Personal information -->
                        <div class="card">
                            <div class="card-header">
                                <div class="ch-icon chi-green"><i class="bi bi-person-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Información personal</h3>
                                    <p>Actualiza tus datos de contacto y perfil</p>
                                </div>
                            </div>
                            <div class="card-body">

                                <div class="form-section-label">
                                    <i class="bi bi-person-lines-fill"></i> Datos básicos
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label class="form-label" for="full_name">
                                            <i class="bi bi-person-fill"></i> Nombre completo
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" id="full_name" name="full_name" class="form-control"
                                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                                placeholder="Tu nombre completo">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="email">
                                            <i class="bi bi-envelope-fill"></i> Correo electrónico
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-envelope input-icon"></i>
                                            <input type="email" id="email" name="email" class="form-control"
                                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                                readonly>
                                        </div>
                                        <span class="form-hint warn"><i class="bi bi-info-circle"></i> El email no se puede cambiar por seguridad</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="bio">
                                        <i class="bi bi-card-text"></i> Bio / Descripción <span class="opt">(opcional)</span>
                                    </label>
                                    <div class="input-wrap">
                                        <i class="bi bi-chat-square-quote input-icon" style="top:14px;transform:none;"></i>
                                        <textarea id="bio" name="bio" class="form-control"
                                            placeholder="Cuéntanos un poco sobre ti o tu negocio..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-section-label" style="margin-top:6px;">
                                    <i class="bi bi-geo-alt-fill"></i> Ubicación y contacto
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label class="form-label" for="phone">
                                            <i class="bi bi-telephone-fill"></i> Teléfono
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-telephone input-icon"></i>
                                            <input type="tel" id="phone" name="phone" class="form-control"
                                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                                placeholder="+57 300 000 0000">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="city">
                                            <i class="bi bi-geo-alt-fill"></i> Ciudad
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-geo-alt input-icon"></i>
                                            <input type="text" id="city" name="city" class="form-control"
                                                value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                                placeholder="Ej: Bogotá">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="address">
                                        <i class="bi bi-house-fill"></i> Dirección <span class="opt">(opcional)</span>
                                    </label>
                                    <div class="input-wrap">
                                        <i class="bi bi-house input-icon"></i>
                                        <input type="text" id="address" name="address" class="form-control"
                                            value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                            placeholder="Barrio, calle, etc.">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Password change -->
                        <div class="card">
                            <div class="card-header">
                                <div class="ch-icon chi-blue"><i class="bi bi-lock-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Cambiar contraseña</h3>
                                    <p>Actualiza tu contraseña de acceso</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label" for="current_pw">
                                        <i class="bi bi-lock-fill"></i> Contraseña actual
                                    </label>
                                    <div class="input-wrap">
                                        <i class="bi bi-lock input-icon"></i>
                                        <input type="password" id="current_pw" name="current_password" class="form-control"
                                            placeholder="Tu contraseña actual">
                                        <button type="button" class="pw-toggle-btn" onclick="togglePw('current_pw',this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label class="form-label" for="new_pw">
                                            <i class="bi bi-key-fill"></i> Nueva contraseña
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-key input-icon"></i>
                                            <input type="password" id="new_pw" name="new_password" class="form-control"
                                                placeholder="Mínimo 8 caracteres" oninput="checkPwStrength(this)">
                                            <button type="button" class="pw-toggle-btn" onclick="togglePw('new_pw',this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="pw-strength" id="pwStrength" style="display:none;">
                                            <div class="pw-bar" id="pb1"></div>
                                            <div class="pw-bar" id="pb2"></div>
                                            <div class="pw-bar" id="pb3"></div>
                                            <div class="pw-bar" id="pb4"></div>
                                            <span class="pw-label" id="pwLabel">Débil</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="confirm_pw">
                                            <i class="bi bi-shield-lock-fill"></i> Confirmar contraseña
                                        </label>
                                        <div class="input-wrap">
                                            <i class="bi bi-shield-lock input-icon"></i>
                                            <input type="password" id="confirm_pw" name="confirm_password" class="form-control"
                                                placeholder="Repite la nueva contraseña" oninput="checkPwMatch(this)">
                                        </div>
                                        <span class="form-hint" id="pwMatchHint"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preferences -->
                        <div class="card">
                            <div class="card-header">
                                <div class="ch-icon chi-purple"><i class="bi bi-sliders"></i></div>
                                <div class="card-header-text">
                                    <h3>Preferencias</h3>
                                    <p>Notificaciones y configuración de cuenta</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="pref-row">
                                    <div class="pref-info">
                                        <h4>Notificaciones por email</h4>
                                        <p>Recibe alertas de mensajes y nuevas ofertas</p>
                                    </div>
                                    <label class="toggle">
                                        <input type="checkbox" name="notif_email" checked>
                                        <span class="toggle-track"></span>
                                    </label>
                                </div>
                                <div class="pref-row">
                                    <div class="pref-info">
                                        <h4>Mostrar perfil en búsquedas</h4>
                                        <p>Otros usuarios pueden encontrar tu perfil</p>
                                    </div>
                                    <label class="toggle">
                                        <input type="checkbox" name="public_profile" checked>
                                        <span class="toggle-track"></span>
                                    </label>
                                </div>
                                <div class="pref-row">
                                    <div class="pref-info">
                                        <h4>Cuenta verificada por WhatsApp</h4>
                                        <p>Verifica tu cuenta con tu número de teléfono</p>
                                    </div>
                                    <label class="toggle">
                                        <input type="checkbox" name="whatsapp_verify">
                                        <span class="toggle-track"></span>
                                    </label>
                                </div>
                                <div class="pref-row">
                                    <div class="pref-info">
                                        <h4>Boletín de ofertas</h4>
                                        <p>Recibe las mejores ofertas de tu ciudad</p>
                                    </div>
                                    <label class="toggle">
                                        <input type="checkbox" name="newsletter">
                                        <span class="toggle-track"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Danger zone -->
                        <div class="card" style="border-color:#fca5a5;">
                            <div class="card-header" style="background:linear-gradient(to right,#fef2f2,transparent);">
                                <div class="ch-icon chi-red"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Zona peligrosa</h3>
                                    <p>Acciones irreversibles sobre tu cuenta</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <p style="font-size:.83rem;color:var(--ink3);margin-bottom:14px;line-height:1.55;">
                                    Al desactivar o eliminar tu cuenta se ocultarán todos tus anuncios y perderás acceso a tus mensajes y conversaciones.
                                </p>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <button type="button" class="danger-btn">
                                        <i class="bi bi-pause-circle"></i> Desactivar cuenta temporalmente
                                    </button>
                                    <button type="button" class="danger-btn" onclick="if(confirm('¿Estás seguro? Esta acción es permanente.')){}">
                                        <i class="bi bi-trash-fill"></i> Eliminar cuenta permanentemente
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action bar -->
                        <div class="action-bar">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-floppy-fill"></i> Guardar cambios
                            </button>
                            <a href="./inde.php" class="btn-cancel">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                            <div class="sec-badge">
                                <i class="bi bi-shield-check-fill"></i>
                                Cifrado SSL 256 bits
                            </div>
                        </div>

                    </div><!-- /right-col -->
                </div><!-- /profile-grid -->
            </form>

        </main>
    </div><!-- /page-shell -->

    <script src="../js/perfil.js"></script>

</body>

</html>