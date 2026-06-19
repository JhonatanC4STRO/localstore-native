<?php require_once("../../config/conexion.php"); ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName = $isLoggedIn ? explode(' ', $user['full_name'])[0] : ''; // First name only
$user_id = $_SESSION['user']['id'];

// contar productos totales para mostrar en el sidebar
$totalProducts = 0;
$sql_count = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'";
$r = mysqli_query($conn, $sql_count);
if ($r) {
    $row_c = mysqli_fetch_assoc($r);
    $totalProducts = $row_c['total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Producto – ComercioLocal</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/css/output.css">
    <link rel="stylesheet" href="../../../public/css/crear.css">
    <link rel="stylesheet" href="../../../public/css/sidebar.css">
</head>

<body>

    <!-- header -->
    <?php $basePath = "../../../"; include __DIR__ . '/../../components/header.php'; ?>

    <div class="page-shell">

        <!-- ══ SIDEBAR ══ -->
        <?php $activeTab = 'crear'; include __DIR__ . '/../../components/sidebar.php'; ?>

        <!-- ── MAIN ── -->
        <main class="main-content">

            <?php if (isset($_GET['success'])): ?>
            <div class="success-toast-banner" id="successBanner">
                <div class="stb-glow"></div>
                <div class="stb-inner">
                    <div class="stb-icon-wrap">
                        <div class="stb-icon-ring"></div>
                        <div class="stb-icon-ring stb-ring2"></div>
                        <div class="stb-icon">
                            <i class="bi bi-rocket-takeoff-fill"></i>
                        </div>
                    </div>
                    <div class="stb-content">
                        <div class="stb-label">¡Publicado con éxito!</div>
                        <div class="stb-title">Tu anuncio ya está en línea 🎉</div>
                        <div class="stb-sub">Los compradores de tu ciudad ya pueden ver tu producto. ¡Buena suerte con la venta!</div>
                        <div class="stb-actions">
                            <a href="mis_productos.php" class="stb-btn stb-btn-primary">
                                <i class="bi bi-grid-fill"></i> Ver mis productos
                            </a>
                            <a href="crear.php" class="stb-btn stb-btn-ghost">
                                <i class="bi bi-plus-circle-fill"></i> Publicar otro
                            </a>
                        </div>
                    </div>
                    <button class="stb-close" onclick="document.getElementById('successBanner').style.display='none'" title="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="stb-progress"></div>
            </div>
            <style>
            .success-toast-banner {
                position: relative;
                background: linear-gradient(135deg, #0b2e17 0%, #103d1e 50%, #185228 100%);
                border-radius: 20px;
                margin-bottom: 28px;
                overflow: hidden;
                box-shadow: 0 8px 40px rgba(37,136,63,.35), 0 2px 12px rgba(37,136,63,.18);
                animation: stbSlideIn .55s cubic-bezier(.22,.9,.36,1) both;
                border: 1.5px solid rgba(52,179,87,.35);
            }
            @keyframes stbSlideIn {
                from { opacity:0; transform:translateY(-28px) scale(.97); }
                to   { opacity:1; transform:translateY(0)   scale(1);    }
            }
            .stb-glow {
                position: absolute;
                top: -60px; left: 50%;
                transform: translateX(-50%);
                width: 320px; height: 160px;
                background: radial-gradient(ellipse, rgba(52,179,87,.28) 0%, transparent 70%);
                pointer-events: none;
            }
            .stb-inner {
                display: flex;
                align-items: center;
                gap: 22px;
                padding: 24px 28px;
                position: relative;
                z-index: 1;
            }
            .stb-icon-wrap {
                position: relative;
                flex-shrink: 0;
                width: 72px;
                height: 72px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .stb-icon-ring {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                border: 2px solid rgba(52,179,87,.5);
                animation: stbRing 2.4s ease-in-out infinite;
            }
            .stb-ring2 {
                inset: -8px;
                border-color: rgba(52,179,87,.2);
                animation-delay: .4s;
            }
            @keyframes stbRing {
                0%,100% { transform: scale(1);   opacity:.8; }
                50%      { transform: scale(1.08); opacity:.3; }
            }
            .stb-icon {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--g400,#34b357), var(--g500,#25883f));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.6rem;
                color: #fff;
                box-shadow: 0 4px 20px rgba(52,179,87,.5);
                animation: stbIconPop .6s .2s cubic-bezier(.34,1.56,.64,1) both;
            }
            @keyframes stbIconPop {
                from { transform: scale(0) rotate(-20deg); }
                to   { transform: scale(1) rotate(0deg);   }
            }
            .stb-content { flex: 1; min-width: 0; }
            .stb-label {
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .1em;
                text-transform: uppercase;
                color: var(--g300,#55d475);
                margin-bottom: 4px;
            }
            .stb-title {
                font-family: 'Outfit', sans-serif;
                font-weight: 800;
                font-size: 1.25rem;
                color: #fff;
                margin-bottom: 5px;
                line-height: 1.2;
            }
            .stb-sub {
                font-size: .84rem;
                color: rgba(255,255,255,.6);
                line-height: 1.5;
                margin-bottom: 16px;
            }
            .stb-actions { display: flex; gap: 10px; flex-wrap: wrap; }
            .stb-btn {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 9px 18px;
                border-radius: 50px;
                font-family: 'Outfit', sans-serif;
                font-weight: 700;
                font-size: .84rem;
                text-decoration: none;
                transition: all .2s;
                white-space: nowrap;
            }
            .stb-btn-primary {
                background: linear-gradient(135deg, var(--g400,#34b357), var(--g300,#55d475));
                color: #0b2e17;
                box-shadow: 0 3px 14px rgba(52,179,87,.45);
            }
            .stb-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(52,179,87,.55); }
            .stb-btn-ghost {
                background: rgba(255,255,255,.1);
                border: 1.5px solid rgba(255,255,255,.25);
                color: #fff;
            }
            .stb-btn-ghost:hover { background: rgba(255,255,255,.18); }
            .stb-close {
                flex-shrink: 0;
                width: 32px; height: 32px;
                border-radius: 50%;
                border: none;
                background: rgba(255,255,255,.1);
                color: rgba(255,255,255,.6);
                display: flex; align-items: center; justify-content: center;
                cursor: pointer;
                font-size: .85rem;
                transition: all .2s;
                align-self: flex-start;
            }
            .stb-close:hover { background: rgba(255,255,255,.2); color: #fff; }
            .stb-progress {
                height: 3px;
                background: linear-gradient(90deg, var(--g300,#55d475), var(--g400,#34b357));
                animation: stbBar 6s linear forwards;
                transform-origin: left;
            }
            @keyframes stbBar {
                from { transform: scaleX(1); }
                to   { transform: scaleX(0); }
            }
            @media (max-width: 600px) {
                .stb-inner { flex-direction: column; align-items: flex-start; padding: 20px 18px; gap: 14px; }
                .stb-title { font-size: 1.05rem; }
                .stb-actions { width: 100%; }
                .stb-btn { flex: 1; justify-content: center; }
                .stb-close { position: absolute; top: 14px; right: 14px; }
            }
            </style>
            <script>
            // Auto-cerrar después de 6s (coincide con la barra de progreso)
            setTimeout(() => {
                const b = document.getElementById('successBanner');
                if (b) { b.style.transition = 'opacity .5s, transform .5s'; b.style.opacity = '0'; b.style.transform = 'translateY(-12px)'; setTimeout(() => b.style.display = 'none', 500); }
            }, 6000);
            </script>
            <?php endif; ?>


            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 animate__animated animate__shakeX" role="alert">
                    <p class="font-bold"><i class="bi bi-exclamation-triangle-fill"></i> Error</p>
                    <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            <?php endif; ?>


            <form action="../../controllers/product_create_action.php" method="POST" enctype="multipart/form-data" id="mainForm">
                <?php require_once __DIR__ . "/../../config/csrf.php"; insert_csrf_input(); ?>

                <!-- Hidden inputs (logic preserved) -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="city" id="city">
                <input type="hidden" name="condicion" id="condicionHidden" value="nuevo">

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
                                            <input type="text" id="precio" name="precio" class="form-control"
                                                inputmode="numeric" autocomplete="off"
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
                                            <div class="cond-opt" onclick="selectCondition(this,'reacondicionado')">
                                                <i class="bi bi-tools"></i>
                                                <span>Reacondicionado</span>
                                                <small>Restaurado, como nuevo</small>
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
                                    <h3>Ubicación del producto <span class="required">*</span></h3>
                                    <p>Marcá el punto en el mapa o usá tu ubicación actual. Es obligatorio para publicar.</p>
                                </div>
                                <div class="card-step-badge">3</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label"><i class="bi bi-signpost-split"></i> Sector / Barrio</label>
                                    <input type="text" class="form-control" name="location" placeholder="Ej: Chapinero, Bogotá">

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

                                <!-- Ciudad detectada (auto, reverse geocoding) -->
                                <div id="cityChip" class="city-chip" style="display:none;">
                                    <i class="bi bi-geo-fill"></i>
                                    <span>Ciudad detectada:</span>
                                    <strong id="cityName">—</strong>
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

        </main>
    </div><!-- /page-shell -->

    <script src="../../../public/js/crear.js"></script>

</body>

</html>














