<?php
/**
 * Unified Filter Sidebar Component — ComercioLocal
 * Shared between home.php and products/all.php
 *
 * Requirements:
 *   - $conn must be available (DB connection)
 *
 * Optional variables (set before including):
 *   @var bool   $showPromo   - Show the promo/CTA card at the bottom (default: false)
 *   @var string $promoHref   - Link for the promo CTA (default: crear.php)
 *   @var string $promoTitle  - Promo card title (default: '¿Vendes algo?')
 *   @var string $promoText   - Promo card description
 */

$showPromo  = $showPromo  ?? false;
$promoHref  = $promoHref  ?? '../app/crear.php';
$promoTitle = $promoTitle ?? '¿Vendes algo?';
$promoText  = $promoText  ?? 'Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.';

/* ── Icon map for categories ── */
$_filterIconMap = [
    'Vehículos'                 => 'bi-car-front-fill',
    'Propiedades en venta'      => 'bi-house-door-fill',
    'Propiedades en alquiler'   => 'bi-key-fill',
    'Electrónica'               => 'bi-lightning-charge-fill',
    'Celulares y accesorios'    => 'bi-phone-fill',
    'Computadores y tablets'    => 'bi-laptop-fill',
    'Hogar y muebles'           => 'bi-house-fill',
    'Electrodomésticos'         => 'bi-plug-fill',
    'Ropa y accesorios'         => 'bi-bag-heart-fill',
    'Calzado'                   => 'bi-bootstrap-fill',
    'Belleza y cuidado personal'=> 'bi-stars',
    'Deportes y fitness'        => 'bi-trophy-fill',
    'Juguetes y juegos'         => 'bi-controller',
    'Mascotas'                  => 'bi-heart-fill',
    'Herramientas'              => 'bi-tools',
    'Jardín y exterior'         => 'bi-leaf-fill',
    'Instrumentos musicales'    => 'bi-music-note-beamed',
    'Arte y coleccionables'     => 'bi-palette-fill',
    'Libros y revistas'         => 'bi-book-fill',
    'Videojuegos y consolas'    => 'bi-controller',
    'Bicicletas'                => 'bi-bicycle',
    'Motocicletas'              => 'bi-bicycle',
    'Servicios'                 => 'bi-briefcase-fill',
];

/* Fuzzy fallback map (for partial keyword matching) */
$_filterIconFuzzy = [
    'tecnolog' => 'bi-cpu-fill',
    'ropa'     => 'bi-bag-heart-fill',
    'moda'     => 'bi-bag-heart-fill',
    'vehicul'  => 'bi-car-front-fill',
    'vehícul'  => 'bi-car-front-fill',
    'hogar'    => 'bi-house-door-fill',
    'deporte'  => 'bi-trophy-fill',
    'herramie' => 'bi-tools',
    'libro'    => 'bi-book-fill',
    'electron' => 'bi-lightning-charge-fill',
    'mascota'  => 'bi-heart-fill',
    'jardín'   => 'bi-leaf-fill',
    'jardin'   => 'bi-leaf-fill',
    'belleza'  => 'bi-stars',
];

if (!function_exists('_filterSidebarIcon')) {
    /**
     * Resolve a Bootstrap Icon class for a category name.
     * Tries exact match first, then fuzzy keyword match.
     */
    function _filterSidebarIcon(string $name, array $exact, array $fuzzy): string
    {
        if (isset($exact[$name])) return $exact[$name];
        $k = strtolower(trim($name));
        foreach ($fuzzy as $keyword => $icon) {
            if (str_contains($k, $keyword)) return $icon;
        }
        return 'bi-grid-fill';
    }
}

/* ── Load categories from DB ── */
$_filterCatsResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$_filterCategories = [];
if ($_filterCatsResult) {
    while ($c = mysqli_fetch_assoc($_filterCatsResult)) {
        $_filterCategories[] = $c;
    }
}
?>

<!-- ══════════════════════════════════
   UNIFIED FILTER SIDEBAR
══════════════════════════════════ -->
<aside class="sidebar">
    <div class="filter-card">
        <div class="filter-title"><i class="bi bi-sliders"></i> Filtros</div>

        <!-- Categorías -->
        <div class="filter-group">
            <div class="filter-group-label">Categoría</div>
            <div class="filter-options" id="filterCategories">
                <?php if (!empty($_filterCategories)): ?>
                    <?php foreach ($_filterCategories as $cat): ?>
                        <label class="filter-opt">
                            <input type="checkbox" value="<?= (int)$cat['id'] ?>">
                            <span class="filter-opt-label">
                                <i class="bi <?= _filterSidebarIcon($cat['name'], $_filterIconMap, $_filterIconFuzzy) ?>" style="color:var(--green-500,#25883f);margin-right:4px;font-size:.8rem;"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size:.82rem;color:var(--text-soft,#888);">Sin categorías</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rango de precio -->
        <div class="filter-group">
            <div class="filter-group-label">Rango de precio</div>
            <div class="price-range">
                <div class="price-input-wrapper">
                    <span class="currency-symbol">$</span>
                    <input type="text" class="price-input price-min" id="priceMinInput" placeholder="Mín" value="0">
                </div>
                <span class="price-sep">–</span>
                <div class="price-input-wrapper">
                    <span class="currency-symbol">$</span>
                    <input type="text" class="price-input price-max" id="priceMaxInput" placeholder="Máx" value="10.000.000">
                </div>
            </div>
            <input type="range" class="range-slider" id="rangeSlider" min="0" max="10000000" value="10000000">
        </div>

        <!-- Estado del producto -->
        <div class="filter-group">
            <div class="filter-group-label">Estado del producto</div>
            <div class="condition-tags" id="filterCondition">
                <div class="cond-tag active" data-condition="all">Todos</div>
                <div class="cond-tag" data-condition="Nuevo">Nuevo</div>
                <div class="cond-tag" data-condition="Usado">Usado</div>
                <div class="cond-tag" data-condition="Reacondicionado">Reacondicionado</div>
            </div>
        </div>

        <button class="btn-apply-filter" id="applyFiltersBtn"><i class="bi bi-funnel-fill"></i> Aplicar filtros</button>
        <button class="btn-apply-filter" id="resetFiltersBtn" style="background:transparent;color:var(--text-soft,#888);border:1.5px solid #ddd;margin-top:6px;">Limpiar filtros</button>
    </div>

    <?php if ($showPromo): ?>
    <div class="sb-promo">
        <div class="pt">🚀 Premium</div>
        <h4><?= htmlspecialchars($promoTitle) ?></h4>
        <p><?= htmlspecialchars($promoText) ?></p>
        <a href="./crear.php">Publicar ahora <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php endif; ?>
</aside>
