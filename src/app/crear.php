<?php require_once("../config/conexion.php");
?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/register.php");
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName = $isLoggedIn ? explode(' ', $user['full_name'])[0] : ''; // First name only
$user_id = $_SESSION['user']['id'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Producto – ComercioLocal</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/crear.css">


    <style>
        :root {
            --green-900: #0d3a1e;
            --green-800: #145228;
            --green-700: #1a6b33;
            --green-600: #1f8340;
            --green-500: #25a050;
            --green-400: #36c465;
            --green-300: #5dd988;
            --green-100: #d0f5df;
            --green-50: #edfaf3;

            --yellow-600: #c47a00;
            --yellow-500: #e8930a;
            --yellow-400: #f7ab1e;
            --yellow-300: #fcc945;
            --yellow-200: #fde08a;
            --yellow-100: #fef3cc;
            --yellow-50: #fffae8;

            --text-dark: #0f1f15;
            --text-mid: #344a3b;
            --text-soft: #6b8070;
            --bg-page: #f5faf7;
            --bg-card: #ffffff;
            --radius-card: 18px;
            --shadow-card:
                0 4px 20px rgba(13, 58, 30, 0.1), 0 1px 4px rgba(13, 58, 30, 0.06);
            --shadow-hover:
                0 10px 36px rgba(13, 58, 30, 0.16), 0 2px 8px rgba(13, 58, 30, 0.08);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            padding: 5px 14px 5px 5px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .user-chip:hover {
            background: rgba(255, 255, 255, 0.14);
            border-color: var(--yellow-400);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--yellow-400), var(--yellow-300));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Syne", sans-serif;
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--green-900);
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(247, 171, 30, 0.4);
        }

        .user-chip-info {
            line-height: 1.2;
        }

        .user-chip-greeting {
            font-size: 0.68rem;
            color: rgba(255, 255, 255, 0.45);
        }

        .user-chip-name {
            font-size: 0.84rem;
            font-weight: 600;
            color: #fff;
            max-width: 100px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .user-chip-arrow {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.45);
            transition: transform 0.2s;
        }

        .user-menu-wrap.open .user-chip-arrow {
            transform: rotate(180deg);
        }
    </style>

</head>

<body>

    <!-- ── TOP BAR ── -->
    <header class="topbar">
        <a href="./inde.php" class="topbar-logo">
            <div cass="logo-box"><i class="bi bi-shop"></i></div>
            Comercio<span class="logo-em">Local</span>
        </a>
        <div class="topbar-spacer"></div>
        <!-- <nav class="topbar-nav">
            <a href="#" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="#"><i class="bi bi-box-seam"></i> Mis productos</a>
            <a href="#"><i class="bi bi-chat-dots"></i> Mensajes</a>
        </nav> -->
        <a href="#" class="topbar-btn"><i class="bi bi-plus-circle-fill"></i> Publicar</a>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-chip" id="userChip">
                <div style="position:relative;">
                    <div class="user-avatar"><?php echo $userInitial; ?></div>
                    <div class="online-dot"></div>
                </div>
                <div class="user-chip-info">
                    <div class="user-chip-greeting">Hola,</div>
                    <div class="user-chip-name"><?php echo htmlspecialchars($userName); ?></div>
                </div>
                <i class="bi bi-chevron-down user-chip-arrow"></i>
            </div>
        <?php endif; ?>
    </header>


    <div class="page-shell">

        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <div class="sidebar-section-label">Menú vendedor</div>

            <a class="sidebar-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Panel principal</a>
            <a class="sidebar-link active" href="#"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
            <a class="sidebar-link" href="#"><i class="bi bi-box-seam"></i> Mis anuncios <span class="badge">3</span></a>
            <a class="sidebar-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>
            <a class="sidebar-link" href="#"><i class="bi bi-chat-dots"></i> Mensajes <span class="badge">5</span></a>

            <div class="sidebar-divider"></div>
            <div class="sidebar-section-label">Cuenta</div>

            <a class="sidebar-link" href="#"><i class="bi bi-person-circle"></i> Mi perfil</a>
            <a class="sidebar-link" href="#"><i class="bi bi-star"></i> Reseñas</a>
            <a class="sidebar-link" href="#"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
            <a class="sidebar-link" href="#"><i class="bi bi-gear"></i> Configuración</a>

            <div class="sidebar-divider"></div>

            <div class="sidebar-promo">
                <div class="promo-icon">⭐</div>
                <p>Destaca tu anuncio y llega a 10× más compradores.</p>
                <a href="#">Ver planes</a>
            </div>
        </aside>

        <!-- ── MAIN ── -->
        <main class="main-content">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="page-breadcrumb">
                        <a href="#">Inicio</a>
                        <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
                        <a href="#">Dashboard</a>
                        <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
                        <span>Publicar producto</span>
                    </div>
                    <h1 class="page-title">Publicar <span>producto</span></h1>
                    <p class="page-subtitle">Completa todos los campos para que tu anuncio sea más visible.</p>
                </div>
                <div class="page-header-actions">
                    <a class="btn-outline" href="#"><i class="bi bi-eye"></i> Vista previa</a>
                    <a class="btn-outline" href="#"><i class="bi bi-save"></i> Guardar borrador</a>
                </div>
            </div>

            <!-- FORM wrapper — preserves original action/method/enctype -->
            <form action="./productos/controller/process_create_product.php" method="POST" enctype="multipart/form-data" id="mainForm">

                <!-- Hidden inputs (logic preserved) -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="publish-layout">

                    <!-- LEFT COLUMN -->
                    <div class="left-col">

                        <!-- Step 1: Images -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-purple"><i class="bi bi-images"></i></div>
                                <div class="card-header-text">
                                    <h3>Fotos del producto</h3>
                                    <p>Agrega hasta 8 fotos. La primera será la principal.</p>
                                </div>
                                <div class="card-step-badge">1</div>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="dropZone">
                                    <!-- LOGIC PRESERVED: original file input -->
                                    <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple required>
                                    <div class="drop-icon"><i class="bi bi-cloud-upload"></i></div>
                                    <div class="drop-title">Arrastra tus fotos aquí</div>
                                    <div class="drop-sub">o haz clic para seleccionar archivos desde tu dispositivo</div>
                                    <div class="drop-formats">
                                        <span class="fmt-tag">JPG</span>
                                        <span class="fmt-tag">PNG</span>
                                        <span class="fmt-tag">WEBP</span>
                                        <span class="fmt-tag">Máx. 5MB c/u</span>
                                    </div>
                                </div>
                                <div class="image-preview-grid" id="imgPreviewGrid">
                                    <div class="img-thumb"><i class="bi bi-plus-lg"></i></div>
                                    <div class="img-thumb"><i class="bi bi-image" style="font-size:1rem;color:#ddd;"></i></div>
                                    <div class="img-thumb"><i class="bi bi-image" style="font-size:1rem;color:#ddd;"></i></div>
                                    <div class="img-thumb"><i class="bi bi-image" style="font-size:1rem;color:#ddd;"></i></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Product info -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-green"><i class="bi bi-pencil-square"></i></div>
                                <div class="card-header-text">
                                    <h3>Información del producto</h3>
                                    <p>Describe tu producto con el mayor detalle posible.</p>
                                </div>
                                <div class="card-step-badge">2</div>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label class="form-label" for="nombre">
                                        <i class="bi bi-type"></i> Título del anuncio <span class="required">*</span>
                                    </label>
                                    <!-- LOGIC PRESERVED: name="nombre" -->
                                    <input type="text" id="nombre" name="nombre" class="form-control"
                                        placeholder="Ej: iPhone 13 128GB Negro – Perfecto estado" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="descripcion">
                                        <i class="bi bi-card-text"></i> Descripción
                                    </label>
                                    <!-- LOGIC PRESERVED: name="descripcion" -->
                                    <textarea id="descripcion" name="descripcion" class="form-control"
                                        placeholder="Describe el estado, características, accesorios incluidos, motivo de venta..."></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="precio">
                                            <i class="bi bi-currency-dollar"></i> Precio <span class="required">*</span>
                                        </label>
                                        <div class="price-wrapper">
                                            <span class="price-prefix">$</span>
                                            <!-- LOGIC PRESERVED: name="precio" -->
                                            <input type="number" id="precio" name="precio" class="form-control"
                                                placeholder="0" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-tag"></i> Estado del producto <span class="required">*</span>
                                        </label>
                                        <!-- LOGIC PRESERVED: name="estado" -->
                                        <select name="estado" class="form-control" required>
                                            <option value="">Seleccionar estado</option>
                                            <option value="1">✅ Activo</option>
                                            <option value="0">⏸ Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="categoria">
                                            <i class="bi bi-grid"></i> Categoría <span class="required">*</span>
                                        </label>
                                        <?php
                                        /* LOGIC PRESERVED: exact original query */
                                        $query = "SELECT * FROM categories";
                                        $result = mysqli_query($conn, $query);
                                        ?>
                                        <select name="categoria" id="categoria" class="form-control" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <option value="<?php echo $row['id']; ?>">
                                                    <?php echo $row['name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-award"></i> Condición
                                        </label>
                                        <div class="condition-toggle">
                                            <div class="cond-opt active" onclick="selectCondition(this,'nuevo')">
                                                <i class="bi bi-star-fill"></i>
                                                <span>Nuevo</span>
                                                <small>Sin uso, en caja</small>
                                            </div>
                                            <div class="cond-opt" onclick="selectCondition(this,'usado')">
                                                <i class="bi bi-recycle"></i>
                                                <span>Usado</span>
                                                <small>Buen estado</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Step 3: Location / Map -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-blue"><i class="bi bi-geo-alt-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Ubicación del producto</h3>
                                    <p>Indica dónde está disponible para el comprador.</p>
                                </div>
                                <div class="card-step-badge">3</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label"><i class="bi bi-signpost-split"></i> Sector / Barrio</label>
                                    <input type="text" class="form-control" placeholder="Ej: Chapinero, Bogotá">
                                </div>

                                <!-- LOGIC PRESERVED: map div with id="map" -->
                                <div id="map"></div>

                                <div class="map-action-row">
                                    <!-- LOGIC PRESERVED: id="btnUbicacion" -->
                                    <button type="button" id="btnUbicacion" class="btn-map-loc">
                                        <i class="bi bi-crosshair"></i>
                                        Usar mi ubicación actual
                                    </button>
                                    <!-- LOGIC PRESERVED: id="est" -->
                                    <p id="est"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Publish button inside form -->
                        <div style="margin-top:4px;">
                            <button type="submit" class="btn-publish">
                                <div class="publish-icon"><i class="bi bi-rocket-takeoff-fill"></i></div>
                                Publicar mi anuncio ahora
                            </button>
                        </div>

                    </div><!-- /left-col -->

                    <!-- RIGHT COLUMN -->
                    <div class="right-col">

                        <!-- Live Preview Card -->
                        <div class="preview-card">
                            <div class="preview-header">
                                <i class="bi bi-eye-fill"></i>
                                <h4>Vista previa del anuncio</h4>
                                <span>En vivo</span>
                            </div>
                            <div class="preview-img-slot" id="previewImgSlot">
                                <i class="bi bi-image"></i>
                                <div class="preview-cond-badge" id="previewCond">Nuevo</div>
                            </div>
                            <div class="preview-body">
                                <div class="preview-price" id="previewPrice">
                                    <span class="preview-price-placeholder">$0</span>
                                </div>
                                <div class="preview-title" id="previewTitle" style="color:#ccc;">Título del producto...</div>
                                <div class="preview-meta">
                                    <div class="preview-meta-row"><i class="bi bi-geo-alt-fill"></i> <span id="previewLoc">Ciudad, Colombia</span></div>
                                    <div class="preview-meta-row"><i class="bi bi-clock-fill"></i> Hace unos segundos</div>
                                </div>
                            </div>
                            <div class="preview-footer">
                                <div class="preview-avatar">TÚ</div>
                                <span class="preview-seller">Tu nombre de vendedor</span>
                                <i class="bi bi-star-fill" style="color:var(--y400);margin-left:auto;font-size:.8rem;"></i>
                                <span style="font-size:.75rem;color:var(--ink3);">Nuevo</span>
                            </div>
                        </div>

                        <!-- Tips Panel -->
                        <div class="tips-card">
                            <div class="tips-header">
                                <i class="bi bi-lightbulb-fill" style="font-size:1.1rem;color:var(--g900);"></i>
                                <h4>Consejos para un buen anuncio</h4>
                            </div>
                            <div class="tips-body">
                                <div class="tip-item">
                                    <div class="tip-num">1</div>
                                    <div class="tip-text">
                                        <strong>Fotos de calidad</strong>
                                        <p>Toma fotos con buena luz desde varios ángulos. Los anuncios con fotos claras venden 3× más rápido.</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-num">2</div>
                                    <div class="tip-text">
                                        <strong>Título claro y específico</strong>
                                        <p>Incluye la marca, modelo y estado. Ej: "iPhone 13 128GB Negro – Perfecto estado".</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-num">3</div>
                                    <div class="tip-text">
                                        <strong>Precio justo</strong>
                                        <p>Investiga el precio de productos similares. Un precio competitivo atrae más compradores.</p>
                                    </div>
                                </div>
                                <div class="tip-item">
                                    <div class="tip-num">4</div>
                                    <div class="tip-text">
                                        <strong>Descripción detallada</strong>
                                        <p>Menciona el estado real, accesorios incluidos y motivo de venta para generar confianza.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="checklist-card">
                            <div class="checklist-header">
                                <i class="bi bi-check2-circle" style="color:var(--g500);font-size:1.1rem;"></i>
                                <h4>Checklist del anuncio</h4>
                            </div>
                            <div class="checklist-body">
                                <div class="check-item" id="chk-fotos"><i class="bi bi-circle"></i> Fotos agregadas</div>
                                <div class="check-item" id="chk-titulo"><i class="bi bi-circle"></i> Título del producto</div>
                                <div class="check-item" id="chk-precio"><i class="bi bi-circle"></i> Precio definido</div>
                                <div class="check-item" id="chk-descripcion"><i class="bi bi-circle"></i> Descripción completa</div>
                                <div class="check-item" id="chk-categoria"><i class="bi bi-circle"></i> Categoría seleccionada</div>
                                <div class="check-item" id="chk-ubicacion"><i class="bi bi-circle"></i> Ubicación en el mapa</div>
                            </div>
                        </div>

                    </div><!-- /right-col -->

                </div><!-- /publish-layout -->

            </form><!-- /form -->

            <!-- ══════════════════════════════════════ -->
            <!--   PRODUCTS LIST — LOGIC 100% PRESERVED  -->
            <!-- ══════════════════════════════════════ -->
            <section class="products-section">

                <div class="products-section-header">
                    <h2 class="products-section-title">Mis productos <span>publicados</span></h2>
                    <a class="btn-outline" href="#"><i class="bi bi-arrow-repeat"></i> Actualizar</a>
                </div>

                <div class="products-grid">
                    <?php
                    /* LOGIC PRESERVED: exact original query */
                    $user_id = $_SESSION['user']['id'];

                    $query = "SELECT products.*, categories.name as category_name
                      FROM products 
                      LEFT JOIN categories ON products.category_id = categories.id
                      WHERE products.user_id = '$user_id'";
                    $result = mysqli_query($conn, $query);

                    while ($row = mysqli_fetch_array($result)) {
                        $product_id = $row['id'];

                        /* Collect images first */
                        $img_query = "SELECT * FROM product_images WHERE product_id = '$product_id'";
                        $img_result_inner = mysqli_query($conn, $img_query);
                        $images = [];
                        while ($img_row = mysqli_fetch_array($img_result_inner)) {
                            $images[] = $img_row['image_url'];
                        }

                        $status_class = ($row['status'] == 1) ? 'status-1' : 'status-0';
                        $status_label = ($row['status'] == 1) ? 'Activo' : 'Inactivo';
                    ?>
                        <div class="php-product-card">
                            <div class="php-product-img-slot">
                                <?php if (!empty($images)): ?>
                                    <img src="./productos/uploads/<?php echo $images[0]; ?>" alt="">
                                <?php else: ?>
                                    <i class="bi bi-box-seam"></i>
                                <?php endif; ?>
                            </div>
                            <div class="php-product-body">
                                <div class="php-product-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>
                                <div class="php-product-title"><?php echo $row['title']; ?></div>
                                <div class="php-product-meta">
                                    <div class="php-meta-row"><i class="bi bi-grid"></i> <?php echo $row['category_name']; ?></div>
                                    <div class="php-meta-row">
                                        <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                    </div>
                                    <?php if ($row['latitude'] && $row['longitude']): ?>
                                        <div class="php-meta-row"><i class="bi bi-geo-alt-fill"></i> <?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php
                            /* LOGIC PRESERVED: per-product map */
                            if ($row['latitude'] && $row['longitude']) {
                                $lat = $row['latitude'];
                                $lon = $row['longitude'];
                                $map_id = "map_" . $row['id'];
                                echo "<div class='php-product-map'><div id='$map_id' style='height:140px;width:100%;'></div></div>";
                                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            if (typeof L !== 'undefined') {
                                var map{$row['id']} = L.map('$map_id').setView([$lat, $lon], 15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: 'OSM'
                                }).addTo(map{$row['id']});
                                L.marker([$lat, $lon]).addTo(map{$row['id']});
                            } else {
                                console.error('Leaflet no se ha cargado correctamente');
                            }
                        });
                        </script>";
                            }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </section>

        </main>

    </div><!-- /page-shell -->

    <script>
        /* ── LOGIC PRESERVED: geolocation + Leaflet map ── */
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        let map;

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                estado.textContent = "La geolocalización no es soportada por tu navegador.";
                return;
            }
            estado.innerHTML = '<i class="bi bi-hourglass-split"></i> Obteniendo ubicación...';

            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;

                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;
                estado.innerHTML = `<i class="bi bi-geo-alt-fill" style="color:var(--g500);"></i> Lat ${lat.toFixed(5)}, Lon ${lon.toFixed(5)}`;

                /* LOGIC PRESERVED: remove old map before creating new */
                if (map !== undefined && map !== null) {
                    map.remove();
                }

                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lon]).addTo(map).bindPopup("📍 Estás aquí").openPopup();

                /* checklist update */
                markDone('chk-ubicacion');
            }, (error) => {
                estado.innerHTML = `<i class="bi bi-exclamation-circle" style="color:#dc2626;"></i> ${error.message}`;
            });
        });

        /* ── LIVE PREVIEW ── */
        const titleInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');
        const previewTitle = document.getElementById('previewTitle');
        const previewPrice = document.getElementById('previewPrice');
        const previewImgSlot = document.getElementById('previewImgSlot');
        const previewCond = document.getElementById('previewCond');

        titleInput.addEventListener('input', () => {
            previewTitle.textContent = titleInput.value || 'Título del producto...';
            previewTitle.style.color = titleInput.value ? 'var(--ink)' : '#ccc';
            toggle('chk-titulo', !!titleInput.value);
        });

        precioInput.addEventListener('input', () => {
            const v = parseInt(precioInput.value) || 0;
            previewPrice.innerHTML = v ?
                `$${v.toLocaleString('es-CO')}` :
                `<span class="preview-price-placeholder">$0</span>`;
            toggle('chk-precio', v > 0);
        });

        document.getElementById('descripcion').addEventListener('input', function() {
            toggle('chk-descripcion', this.value.length > 10);
        });

        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
            toggle('chk-categoria', !!this.value);
        });

        /* ── CONDITION TOGGLE ── */
        function selectCondition(el, val) {
            document.querySelectorAll('.cond-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
            previewCond.textContent = val === 'nuevo' ? 'Nuevo' : 'Usado';
            previewCond.style.background = val === 'nuevo' ? 'var(--g500)' : 'var(--y400)';
            previewCond.style.color = val === 'nuevo' ? '#fff' : 'var(--g900)';
        }

        /* ── DRAG & DROP VISUAL ── */
        const dropZone = document.getElementById('dropZone');
        const fotosInput = document.getElementById('fotos');
        const gridEl = document.getElementById('imgPreviewGrid');

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fotosInput.addEventListener('change', () => handleFiles(fotosInput.files));

        function handleFiles(files) {
            if (!files.length) return;
            gridEl.innerHTML = '';
            Array.from(files).slice(0, 8).forEach((f, i) => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const div = document.createElement('div');
                    div.className = 'img-thumb';
                    div.innerHTML = `<img src="${ev.target.result}" alt="">` +
                        (i === 0 ? '<div class="main-badge">Principal</div>' : '');
                    gridEl.appendChild(div);
                    if (i === 0) {
                        previewImgSlot.innerHTML =
                            `<img src="${ev.target.result}" alt=""><div class="preview-cond-badge" id="previewCond">${previewCond.textContent}</div>`;
                    }
                };
                reader.readAsDataURL(f);
            });
            markDone('chk-fotos');
        }

        /* ── CHECKLIST HELPERS ── */
        function markDone(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('done');
            el.querySelector('i').className = 'bi bi-check-circle-fill';
        }

        function toggle(id, done) {
            const el = document.getElementById(id);
            if (!el) return;
            if (done) {
                el.classList.add('done');
                el.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('done');
                el.querySelector('i').className = 'bi bi-circle';
            }
        }
    </script>

</body>

</html>