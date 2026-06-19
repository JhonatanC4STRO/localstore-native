<?php
require_once('../../config/conexion.php');
session_start();

/* ── LOGIC PRESERVED ── */
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Procesar guardado del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    require_once __DIR__ . '/../../config/csrf.php';
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        log_error("Fallo de validación de token CSRF al editar producto.", "SECURITY");
        die("Fallo de validación de seguridad (CSRF). Intente nuevamente.");
    }

    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user']['id'];

    // Verificar que el producto pertenece al usuario logueado
    $verify_sql = "SELECT user_id FROM products WHERE id = $product_id";
    $verify_result = mysqli_query($conn, $verify_sql);
    $verify_row = mysqli_fetch_assoc($verify_result);

    if (!$verify_row || $verify_row['user_id'] != $user_id) {
        die("No tienes permiso para editar este producto");
    }

    // Recolectar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $condicion = $_POST['condicion'] ?? 'usado';
    $allowedCond = ['nuevo', 'usado', 'reacondicionado'];
    if (!in_array($condicion, $allowedCond, true)) $condicion = 'usado';
    $categoria = intval($_POST['categoria'] ?? 0);
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $cityPost = trim($_POST['city'] ?? '');
    $cityVal  = ($cityPost !== '') ? mb_substr($cityPost, 0, 100) : null;
    $estadoRaw = (int)($_POST['estado'] ?? 1);
    $adminStatus = ($estadoRaw === 0) ? 'inactive' : 'active';

    // Actualizar producto (prepared statement)
    $update_stmt = mysqli_prepare($conn,
        "UPDATE products
         SET title=?, description=?, price=?, condition_type=?, category_id=?,
             latitude=?, longitude=?, city=?, admin_status=?
         WHERE id=? AND user_id=?"
    );
    mysqli_stmt_bind_param(
        $update_stmt,
        'ssdsissssii',
        $nombre, $descripcion, $precio, $condicion, $categoria,
        $latitude, $longitude, $cityVal, $adminStatus, $product_id, $user_id
    );

    if (mysqli_stmt_execute($update_stmt)) {
        
        // ── MANEJO DE IMÁGENES ──
        
        // 1. Eliminar imágenes que el usuario quitó
        $keep_images = isset($_POST['keep_images']) ? array_map('intval', $_POST['keep_images']) : [];
        
        // Obtener imágenes actuales para saber cuáles borrar físicamente
        $upload_dir = __DIR__ . '/../../../public/uploads/products/';
        $current_imgs_res = mysqli_query($conn, "SELECT id, image_url FROM product_images WHERE product_id = $product_id");
        while ($img_row = mysqli_fetch_assoc($current_imgs_res)) {
            if (!in_array($img_row['id'], $keep_images)) {
                $file_to_delete = $upload_dir . $img_row['image_url'];
                if (file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
                $img_id = $img_row['id'];
                mysqli_query($conn, "DELETE FROM product_images WHERE id = $img_id");
            }
        }

        // 2. Procesar nuevas imágenes
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

            foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                    
                    // A. Validar tamaño máximo (5MB)
                    $size = $_FILES['fotos']['size'][$key] ?? 0;
                    if ($size > 5 * 1024 * 1024) {
                        header("Location: edit.php?id=" . $product_id . "&error=" . urlencode('Las fotos no deben superar los 5MB de tamaño.'));
                        exit();
                    }

                    // B. Validar tipo MIME real de forma segura
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowed_mimes, true)) {
                        header("Location: edit.php?id=" . $product_id . "&error=" . urlencode('Formato de imagen no permitido. Solo se admiten JPG, PNG o WebP.'));
                        exit();
                    }

                    $file_name = $_FILES['fotos']['name'][$key];
                    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
                    $dest_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($tmp_name, $dest_path)) {
                        $new_file_name_db = mysqli_real_escape_string($conn, $new_file_name);
                        mysqli_query($conn, "INSERT INTO product_images (product_id, image_url) VALUES ($product_id, '$new_file_name_db')");
                    }
                }
            }
        }

        // Redirigir al dashboard
        header("Location: ../dashboard.php?success=actualizado");
        exit();
    } else {
        die("Error al actualizar: " . mysqli_error($conn));
    }
}

// GET: Mostrar formulario
if (!isset($_GET['id'])) {
    echo "Producto no encontrado";
    exit();
}

$id = $_GET['id'];

$sql     = "SELECT * FROM products WHERE id = '$id'";
$result  = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Producto no existe";
    exit();
}

/* Fetch existing images */
$img_sql    = "SELECT * FROM product_images WHERE product_id = '$id' ORDER BY id ASC";
$img_result = mysqli_query($conn, $img_sql);
$existingImages = [];
while ($img = mysqli_fetch_assoc($img_result)) $existingImages[] = $img;

/* Categories */
$cat_sql    = "SELECT * FROM categories ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_sql);

/* Session user */
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto – ComercioLocal</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/css/output.css">

    <link rel="stylesheet" href="../../../public/css/edit_product.css">
    <link rel="stylesheet" href="../../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../../public/css/output.css">
</head>

<body>

    <!-- ══ TOPBAR ══ -->
    <?php $basePath = "../../../"; include __DIR__ . '/../../components/header.php'; ?>

    <div class="page-shell">

        <!-- ══ SIDEBAR ══ -->
        <?php $activeTab = "productos"; include __DIR__ . "/../../components/sidebar.php"; ?>

        <!-- ══ MAIN ══ -->
        <main class="main-content">

            <!-- Page header -->
            <div class="pg-header">
                <div>
                    <div class="pg-breadcrumb">
                        <a href="../dashboard.php">Dashboard</a>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <a href="./all.php">Mis productos</a>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <span>Editar</span>
                    </div>
                    <h1 class="pg-title">Editar <span>producto</span></h1>
                    <p class="pg-subtitle">Actualiza la información de tu anuncio y guarda los cambios.</p>
                    <div class="pg-id-badge"><i class="bi bi-hash"></i> ID <?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="action-header">
                    <button class="btn-save" form="editForm" type="submit">
                        <i class="bi bi-floppy-fill"></i> Guardar cambios
                    </button>
                    <a class="btn-cancel" href="./all.php">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </a>
                    <button class="btn-delete" type="button" onclick="document.getElementById('deleteModal').classList.add('show')">
                        <i class="bi bi-trash-fill"></i> Eliminar
                    </button>
                </div>
            </div>

            <!-- Success toast (shown after save) -->
            <div class="success-toast" id="successToast">
                <div class="toast-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="toast-text">
                    <strong>¡Producto actualizado!</strong>
                    <span>Los cambios se guardaron correctamente.</span>
                </div>
                <button class="toast-close" onclick="document.getElementById('successToast').classList.remove('show')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.08); border: 1.5px solid rgba(239, 68, 68, 0.25); border-radius: 12px; padding: 14px; margin-bottom: 20px; color: #b91c1c; font-size: 0.88rem; display: flex; gap: 10px; align-items: center;">
                    <i class="bi bi-exclamation-circle-fill" style="color:#ef4444; font-size:1.15rem;"></i>
                    <span><strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            <?php endif; ?>

            <!-- ══ FORM — LOGIC PRESERVED ══ -->
            <form id="editForm" action="./edit.php?id=<?php echo htmlspecialchars($id); ?>" method="POST" enctype="multipart/form-data">
                <?php require_once __DIR__ . "/../../config/csrf.php"; insert_csrf_input(); ?>
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($product['latitude'] ?? ''); ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($product['longitude'] ?? ''); ?>">
                <input type="hidden" name="city" id="city" value="<?php echo htmlspecialchars($product['city'] ?? ''); ?>">

                <div class="edit-layout">

                    <!-- ── LEFT COLUMN ── -->
                    <div class="left-col">

                        <!-- Step 1: Images -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-purple"><i class="bi bi-images"></i></div>
                                <div class="card-header-text">
                                    <h3>Imágenes del producto</h3>
                                    <p>Gestiona las fotos actuales o agrega nuevas.</p>
                                </div>
                                <div class="card-step-badge">1</div>
                            </div>
                            <div class="card-body">

                                <!-- Existing images — LOGIC PRESERVED -->
                                <?php if (!empty($existingImages)): ?>
                                    <div style="font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                        <i class="bi bi-images" style="color:var(--g500);"></i>
                                        Fotos actuales (<?php echo count($existingImages); ?>)
                                    </div>
                                    <div class="existing-images-grid">
                                        <?php foreach ($existingImages as $idx => $img): ?>
                                            <div class="existing-img-card">
                                                <img src="../../../public/uploads/products/<?php echo htmlspecialchars($img['image_url']); ?>"
                                                    alt="Imagen <?php echo $idx + 1; ?>">
                                                <?php if ($idx === 0): ?>
                                                    <span class="main-tag">Principal</span>
                                                <?php endif; ?>
                                                <button type="button" class="del-img-btn"
                                                    onclick="removeExistingImg(this, <?php echo $img['id']; ?>)"
                                                    title="Eliminar imagen">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                                <!-- Hidden input to track deletions -->
                                                <input type="hidden" name="keep_images[]" value="<?php echo $img['id']; ?>" id="keep_img_<?php echo $img['id']; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="height:1px;background:var(--border);margin:16px 0;"></div>
                                <?php endif; ?>

                                <!-- Upload new images -->
                                <div style="font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                    <i class="bi bi-plus-circle" style="color:var(--g500);"></i>
                                    Agregar nuevas fotos
                                </div>
                                <div class="drop-zone" id="dropZone">
                                    <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple>
                                    <div class="drop-icon"><i class="bi bi-cloud-upload"></i></div>
                                    <div class="drop-title">Arrastra nuevas fotos aquí</div>
                                    <div class="drop-sub">o haz clic para seleccionar desde tu dispositivo</div>
                                    <div class="drop-formats">
                                        <span class="fmt-tag">JPG</span>
                                        <span class="fmt-tag">PNG</span>
                                        <span class="fmt-tag">WEBP</span>
                                        <span class="fmt-tag">Máx. 5MB</span>
                                    </div>
                                </div>
                                <div class="image-preview-grid" id="imgPreviewGrid" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- Step 2: Product info -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-green"><i class="bi bi-pencil-square"></i></div>
                                <div class="card-header-text">
                                    <h3>Información del producto</h3>
                                    <p>Edita los detalles del anuncio.</p>
                                </div>
                                <div class="card-step-badge">2</div>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label class="form-label" for="nombre">
                                        <i class="bi bi-type"></i> Título del anuncio <span class="required">*</span>
                                    </label>
                                    <!-- LOGIC PRESERVED: name="nombre", prefilled -->
                                    <input type="text" id="nombre" name="nombre" class="form-control"
                                        value="<?php echo htmlspecialchars($product['title'] ?? ''); ?>"
                                        placeholder="Ej: iPhone 13 128GB Negro – Perfecto estado" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="descripcion">
                                        <i class="bi bi-card-text"></i> Descripción
                                    </label>
                                    <!-- LOGIC PRESERVED: name="descripcion", prefilled -->
                                    <textarea id="descripcion" name="descripcion" class="form-control"
                                        placeholder="Describe el estado, características, accesorios incluidos..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="precio">
                                            <i class="bi bi-currency-dollar"></i> Precio <span class="required">*</span>
                                        </label>
                                        <div class="price-wrapper">
                                            <span class="price-prefix">$</span>
                                            <!-- LOGIC PRESERVED: name="precio", prefilled -->
                                            <input type="number" id="precio" name="precio" class="form-control"
                                                value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>"
                                                placeholder="0" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-tag"></i> Estado del anuncio <span class="required">*</span>
                                        </label>
                                        <!-- LOGIC PRESERVED: name="estado" -->
                                        <?php $isActive = (($product['admin_status'] ?? 'active') !== 'inactive'); ?>
                                        <select name="estado" class="form-control" required>
                                            <option value="1" <?php echo $isActive ? 'selected' : ''; ?>>✅ Activo</option>
                                            <option value="0" <?php echo !$isActive ? 'selected' : ''; ?>>⏸ Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="categoria">
                                            <i class="bi bi-grid"></i> Categoría <span class="required">*</span>
                                        </label>
                                        <!-- LOGIC PRESERVED: category query -->
                                        <select name="categoria" id="categoria" class="form-control" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                                                <option value="<?php echo $cat['id']; ?>"
                                                    <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-award"></i> Condición
                                        </label>
                                        <?php
                                        $cond = strtolower($product['condition_type'] ?? 'usado');
                                        ?>
                                        <div class="condition-toggle">
                                            <div class="cond-opt <?php echo ($cond === 'nuevo') ? 'active' : ''; ?>"
                                                onclick="selectCondition(this,'nuevo')">
                                                <i class="bi bi-star-fill"></i>
                                                <span>Nuevo</span>
                                                <small>Sin uso, en caja</small>
                                            </div>
                                            <div class="cond-opt <?php echo ($cond === 'usado') ? 'active' : ''; ?>"
                                                onclick="selectCondition(this,'usado')">
                                                <i class="bi bi-recycle"></i>
                                                <span>Usado</span>
                                                <small>Buen estado</small>
                                            </div>
                                            <div class="cond-opt <?php echo ($cond === 'reacondicionado') ? 'active' : ''; ?>"
                                                onclick="selectCondition(this,'reacondicionado')">
                                                <i class="bi bi-tools"></i>
                                                <span>Reacondicionado</span>
                                                <small>Restaurado, como nuevo</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="condicion" id="condicionHidden" value="<?php echo htmlspecialchars($product['condition_type'] ?? 'usado'); ?>">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Step 3: Location -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-blue"><i class="bi bi-geo-alt-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Ubicación del producto</h3>
                                    <p>Actualiza dónde está disponible para el comprador.</p>
                                </div>
                                <div class="card-step-badge">3</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label"><i class="bi bi-signpost-split"></i> Sector / Barrio</label>
                                    <input type="text" class="form-control"
                                        name="sector"
                                        value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>"
                                        placeholder="Ej: Chapinero, Bogotá">
                                </div>

                                <!-- LOGIC PRESERVED: map with id="map" -->
                                <div id="map"></div>

                                <div class="map-action-row">
                                    <!-- LOGIC PRESERVED: id="btnUbicacion" -->
                                    <button type="button" id="btnUbicacion" class="btn-map-loc">
                                        <i class="bi bi-crosshair"></i>
                                        Actualizar mi ubicación
                                    </button>
                                    <!-- LOGIC PRESERVED: id="est" -->
                                    <p id="est"></p>
                                </div>

                                <!-- Ciudad detectada (auto, reverse geocoding) -->
                                <div id="cityChip" class="city-chip" style="<?php echo !empty($product['city']) ? '' : 'display:none;'; ?>">
                                    <i class="bi bi-geo-fill"></i>
                                    <span>Ciudad detectada:</span>
                                    <strong id="cityName"><?php echo htmlspecialchars($product['city'] ?? '—'); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom action bar (repeated for convenience) -->
                        <div style="display:flex;gap:10px;padding-bottom:8px;flex-wrap:wrap;">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-floppy-fill"></i> Guardar cambios
                            </button>
                            <a class="btn-cancel" href="./all.php">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                        </div>

                    </div><!-- /left-col -->

                    <!-- ── RIGHT COLUMN ── -->
                    <div class="right-col">

                        <!-- Live preview -->
                        <div class="preview-card">
                            <div class="preview-header">
                                <i class="bi bi-eye-fill"></i>
                                <h4>Vista previa</h4>
                                <div class="live-badge">
                                    <div class="live-dot"></div> En vivo
                                </div>
                            </div>
                            <div class="preview-img-slot" id="previewImgSlot">
                                <?php if (!empty($existingImages)): ?>
                                    <img id="previewMainImg" src="../../../public/uploads/products/<?php echo htmlspecialchars($existingImages[0]['image_url']); ?>" alt="">
                                <?php else: ?>
                                    <i class="bi bi-image" id="previewImgIcon"></i>
                                <?php endif; ?>
                                <div class="preview-cond-badge" id="previewCond">
                                    <?php echo ucfirst($product['condition_type'] ?? 'Usado'); ?>
                                </div>
                            </div>
                            <div class="preview-body">
                                <div class="preview-price" id="previewPrice">
                                    $<?php echo number_format($product['price'] ?? 0, 0, ',', '.'); ?>
                                </div>
                                <div class="preview-title" id="previewTitle">
                                    <?php echo htmlspecialchars($product['title'] ?? 'Título del producto'); ?>
                                </div>
                                <div class="preview-meta">
                                    <div class="preview-meta-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
                                    <div class="preview-meta-row"><i class="bi bi-clock-fill"></i> Recientemente</div>
                                </div>
                            </div>
                            <div class="preview-footer">
                                <div class="preview-avatar"><?php echo $userInitial; ?></div>
                                <span class="preview-seller"><?php echo htmlspecialchars($userName); ?></span>
                                <div class="preview-seller-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="checklist-card">
                            <div class="cl-header">
                                <i class="bi bi-check2-circle" style="color:var(--g500);font-size:1.05rem;"></i>
                                <h4>Checklist del anuncio</h4>
                            </div>
                            <div class="cl-body">
                                <div class="check-item <?php echo !empty($product['title']) ? 'done' : ''; ?>" id="chk-titulo">
                                    <i class="bi <?php echo !empty($product['title']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Título del producto
                                </div>
                                <div class="check-item <?php echo !empty($product['price']) ? 'done' : ''; ?>" id="chk-precio">
                                    <i class="bi <?php echo !empty($product['price']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Precio definido
                                </div>
                                <div class="check-item <?php echo !empty($product['description']) ? 'done' : ''; ?>" id="chk-descripcion">
                                    <i class="bi <?php echo !empty($product['description']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Descripción completa
                                </div>
                                <div class="check-item <?php echo !empty($product['category_id']) ? 'done' : ''; ?>" id="chk-categoria">
                                    <i class="bi <?php echo !empty($product['category_id']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Categoría seleccionada
                                </div>
                                <div class="check-item <?php echo !empty($existingImages) ? 'done' : ''; ?>" id="chk-fotos">
                                    <i class="bi <?php echo !empty($existingImages) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Fotos del producto
                                </div>
                                <div class="check-item <?php echo (!empty($product['latitude']) && !empty($product['longitude'])) ? 'done' : ''; ?>" id="chk-ubicacion">
                                    <i class="bi <?php echo (!empty($product['latitude']) && !empty($product['longitude'])) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Ubicación en el mapa
                                </div>
                            </div>
                        </div>

                        <!-- Product stats -->
                        <div class="status-card">
                            <div class="sc-header">
                                <i class="bi bi-bar-chart-line-fill"></i>
                                <h4>Estado del anuncio</h4>
                            </div>
                            <div class="sc-body">
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-circle-fill"></i> Estado actual</span>
                                    <?php if (($product['admin_status'] ?? 'active') !== 'inactive'): ?>
                                        <span class="sc-status-active">● Activo</span>
                                    <?php else: ?>
                                        <span class="sc-status-inactive">○ Inactivo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-eye-fill"></i> Vistas</span>
                                    <span class="sc-stat-val">142</span>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-heart-fill"></i> Favoritos</span>
                                    <span class="sc-stat-val">8</span>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-images"></i> Fotos</span>
                                    <span class="sc-stat-val"><?php echo count($existingImages); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Danger zone -->
                        <div class="danger-zone">
                            <div class="dz-header">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <h4>Zona de peligro</h4>
                            </div>
                            <div class="dz-body">
                                <p class="dz-text">Al eliminar este producto se borrarán todas sus imágenes, mensajes e historial de vistas. Esta acción no se puede deshacer.</p>
                                <button type="button" class="btn-delete" style="width:100%;justify-content:center;"
                                    onclick="document.getElementById('deleteModal').classList.add('show')">
                                    <i class="bi bi-trash-fill"></i> Eliminar producto permanentemente
                                </button>
                            </div>
                        </div>

                    </div><!-- /right-col -->
                </div><!-- /edit-layout -->
            </form>

        </main>
    </div><!-- /page-shell -->

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="bi bi-trash-fill"></i></div>
            <div class="modal-title">¿Eliminar este producto?</div>
            <div class="modal-text">
                Estás a punto de eliminar permanentemente
                <span class="modal-product-name">"<?php echo htmlspecialchars($product['title'] ?? 'este producto'); ?>"</span>.
                Esta acción no se puede deshacer.
            </div>
            <div class="modal-btns">
                <button class="modal-btn-cancel" onclick="document.getElementById('deleteModal').classList.remove('show')">
                    Cancelar
                </button>
                <a href="../../controllers/product_delete_action.php?id=<?php echo htmlspecialchars($id); ?>" class="modal-btn-delete"
                    style="display:flex;align-items:center;justify-content:center;gap:7px;">
                    <i class="bi bi-trash-fill"></i> Sí, eliminar
                </a>
            </div>
        </div>
    </div>

    <script>
        window.PRODUCT_LAT = <?php echo !empty($product['latitude'])  ? floatval($product['latitude'])  : 'null'; ?>;
        window.PRODUCT_LON = <?php echo !empty($product['longitude']) ? floatval($product['longitude']) : 'null'; ?>;
    </script>
    <script src="../../../public/js/edit.js"></script>

</body>

</html>

















