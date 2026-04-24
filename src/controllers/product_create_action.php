<?php
include("../config/conexion.php");
include("../config/role_sync.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

function redirect_error(string $msg): void
{
    header("Location: ../views/products/crear.php?error=" . urlencode($msg));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_error('Método no permitido');
}

$nombre       = trim($_POST['nombre'] ?? '');
$precio       = (float)($_POST['precio'] ?? 0);
$descripcion  = trim($_POST['descripcion'] ?? '');
$categoria    = (int)($_POST['categoria'] ?? 0);
$location     = trim($_POST['location'] ?? '');
$city         = trim($_POST['city'] ?? '');
if ($city !== '') $city = mb_substr($city, 0, 100);
$longitude    = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$latitude     = isset($_POST['latitude'])  && $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
$estadoRaw    = (int)($_POST['estado'] ?? 1);
$status       = 'disponible'; // nuevo producto siempre disponible para venta
$adminStatus  = ($estadoRaw === 0) ? 'inactive' : 'active';
$condicion    = $_POST['condicion'] ?? 'nuevo';
$allowed      = ['nuevo', 'usado', 'reacondicionado'];
if (!in_array($condicion, $allowed, true)) $condicion = 'nuevo';


/* ── Validaciones básicas ── */
if ($nombre === '')                    redirect_error('El título es obligatorio');
if ($precio <= 0)                      redirect_error('Ingresa un precio válido');
if ($categoria <= 0)                   redirect_error('Selecciona una categoría');

/* ── Ubicación: opcional — si no se marcó, se guarda como NULL ── */
if ($latitude !== null && $longitude !== null) {
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        redirect_error('La ubicación indicada no es válida. Vuelve a marcar el punto en el mapa');
    }
    if ((float)$latitude === 0.0 && (float)$longitude === 0.0) {
        $latitude  = null;
        $longitude = null;
    }
}

/* ── Resolver user_id real (auto-repara sesiones admin legacy con id=0) ── */
$user_id    = (int)($_SESSION['user']['id'] ?? 0);
$user_email = (string)($_SESSION['user']['email'] ?? '');

if ($user_id <= 0 && $user_email !== '') {
    $fix = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($fix, 's', $user_email);
    mysqli_stmt_execute($fix);
    $fixRow = mysqli_fetch_assoc(mysqli_stmt_get_result($fix));
    if ($fixRow) {
        $_SESSION['user'] = $fixRow;
        $user_id = (int)$fixRow['id'];
    }
}

if ($user_id <= 0) {
    redirect_error('Tu sesión está desactualizada. Cierra sesión y vuelve a iniciar sesión para poder publicar.');
}

$chk = mysqli_prepare($conn, "SELECT id, deleted_at FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($chk, 'i', $user_id);
mysqli_stmt_execute($chk);
$chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
if (!$chkRow) {
    session_destroy();
    redirect_error('Tu cuenta no existe. Inicia sesión nuevamente.');
}
if (!empty($chkRow['deleted_at'])) {
    session_destroy();
    redirect_error('Tu cuenta fue eliminada. Inicia sesión con otra cuenta.');
}

/* ── Insertar producto (prepared) ── */
$cityVal = ($city !== '') ? $city : null;
$sql = "INSERT INTO products (title, price, description, longitude, latitude, city, category_id, status, admin_status, condition_type, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt,
    'sdsddsisssi',
    $nombre, $precio, $descripcion, $longitude, $latitude, $cityVal, $categoria, $status, $adminStatus, $condicion, $user_id
);

try {
    if (!mysqli_stmt_execute($stmt)) {
        redirect_error('No se pudo guardar el producto: ' . mysqli_stmt_error($stmt));
    }
} catch (mysqli_sql_exception $e) {
    redirect_error('No se pudo guardar el producto: ' . $e->getMessage());
}

$product_id = mysqli_insert_id($conn);

/* ── Guardar location si la columna existe (silencioso si no existe) ── */
if ($location !== '') {
    $loc_stmt = @mysqli_prepare($conn,
        "UPDATE products SET location = ? WHERE id = ?"
    );
    if ($loc_stmt) {
        mysqli_stmt_bind_param($loc_stmt, 'si', $location, $product_id);
        @mysqli_stmt_execute($loc_stmt);
    }
}

/* ── Subida de imágenes ── */
if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../../public/uploads/products/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
        if (($_FILES['fotos']['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $origName  = $_FILES['fotos']['name'][$key] ?? '';
        $image_name = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
        $path = $uploadDir . $image_name;
        if (move_uploaded_file($tmp_name, $path)) {
            $imgStmt = mysqli_prepare($conn, "INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
            mysqli_stmt_bind_param($imgStmt, 'is', $product_id, $image_name);
            mysqli_stmt_execute($imgStmt);
        }
    }
}

/* Actualizar rol si pasó de 0 a N productos */
sync_user_role($conn, $user_id);

/* Refrescar sesión */
$updated = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
if ($updated && mysqli_num_rows($updated) > 0) {
    $_SESSION['user'] = mysqli_fetch_assoc($updated);
}

header("Location: ../views/products/crear.php?success=1");
exit();
