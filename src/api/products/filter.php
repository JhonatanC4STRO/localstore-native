<?php
/* ══════════════════════════════════════════════════════════════
   filter_products.php
   Endpoint AJAX — devuelve JSON con productos filtrados.
   Usado por home.php y all.php.

   Cambios v2:
     · Agrega LEFT JOIN con product_promotions (activas) para
       obtener promotion_type por producto.
     · Ordena: premium → recommended → basic → normal,
       luego por el criterio elegido por el usuario.
     · Retorna campo promotion_type (null si sin promoción).
     · Todas las entradas de usuario siguen escapadas /
       validadas; las cláusulas dinámicas no aceptan valores
       libres del cliente.
══════════════════════════════════════════════════════════════ */

include(__DIR__ . '/../../config/conexion.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

/* ── 1. Leer y sanear parámetros ──────────────────────────── */
$search     = isset($_GET['search'])     ? trim($_GET['search'])             : '';
$categories = isset($_GET['categories']) ? (array) $_GET['categories']       : [];
$price_min  = isset($_GET['price_min'])  ? max(0,   (int) $_GET['price_min']): 0;
$price_max  = isset($_GET['price_max'])  ? max(1,   (int) $_GET['price_max']): 999999999;
$condition  = isset($_GET['condition'])  ? trim($_GET['condition'])           : 'all';
$order      = isset($_GET['order'])      ? trim($_GET['order'])               : 'recent';
$limit      = isset($_GET['limit'])      ? min((int) $_GET['limit'], 100)    : 12;
$offset     = isset($_GET['offset'])     ? max(0,   (int) $_GET['offset'])   : 0;
$location   = isset($_GET['location'])   ? trim($_GET['location'])            : '';

if ($price_max < $price_min) $price_max = $price_min + 1;

/* ── 2. Cláusula WHERE (base siempre con prepared statement) ─ */
/*
 * Construimos la lista de condiciones como string seguro:
 *  - Valores de usuario se agregan como bind params.
 *  - Los IDs de categoría se castean a entero dentro del array.
 */
$where_parts  = ["p.status = 'disponible'", "p.admin_status = 'active'"];
$bind_types   = '';
$bind_values  = [];

/* Excluir productos del usuario logueado */
if (isset($_SESSION['user'])) {
    $where_parts[] = 'p.user_id != ?';
    $bind_types   .= 'i';
    $bind_values[] = (int) $_SESSION['user']['id'];
}

/* Búsqueda por texto */
if ($search !== '') {
    $where_parts[] = '(p.title LIKE ? OR p.description LIKE ?)';
    $like          = '%' . $search . '%';
    $bind_types   .= 'ss';
    $bind_values[] = $like;
    $bind_values[] = $like;
}

/* Categorías — se castean a INT; solo se usan en IN() numérico */
if (!empty($categories)) {
    $safe_cats     = implode(',', array_map('intval', $categories));
    $where_parts[] = "p.category_id IN ($safe_cats)";
    /* No necesitamos bind param porque son enteros puros */
}

/* Precio */
$where_parts[] = 'p.price BETWEEN ? AND ?';
$bind_types   .= 'ii';
$bind_values[] = $price_min;
$bind_values[] = $price_max;

/* Condición */
if ($condition !== 'all' && $condition !== '') {
    $where_parts[] = 'LOWER(p.condition_type) = LOWER(?)';
    $bind_types   .= 's';
    $bind_values[] = $condition;
}

/* Ubicación */
if ($location !== '') {
    $where_parts[] = 'u.city = ?';
    $bind_types   .= 's';
    $bind_values[] = $location;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_parts);

/* ── 3. ORDER BY ──────────────────────────────────────────── */
/*
 * Primer criterio: prioridad de promoción activa.
 *   premium=3 > recommended=2 > basic=1 > sin promo=0
 * Segundo criterio: preferencia del usuario.
 */
$user_order_sql = match ($order) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'random'     => 'RAND()',
    default      => 'p.id DESC',   /* recent */
};

$order_sql = "promo_rank DESC, $user_order_sql";

/* ── 4. Sub-select de promoción activa ────────────────────── */
/*
 * Usamos un sub-select correlacionado para traer el plan_type
 * de la promoción activa (si existe).  La condición end_date >
 * NOW() y status = 'active' garantiza que no mostramos promos
 * expiradas.
 *
 * También calculamos promo_rank para el ORDER BY:
 *   premium → 3 | recommended → 2 | basic → 1 | null → 0
 */
$promo_subselect = "
    (SELECT pp.plan_type
     FROM product_promotions pp
     WHERE pp.product_id = p.id
       AND pp.status     = 'active'
       AND pp.end_date   > NOW()
     ORDER BY FIELD(pp.plan_type,'premium','recommended','basic') ASC
     LIMIT 1)";

$promo_rank_expr = "
    CASE
        WHEN $promo_subselect = 'premium'     THEN 3
        WHEN $promo_subselect = 'recommended' THEN 2
        WHEN $promo_subselect = 'basic'       THEN 1
        ELSE 0
    END";

/* ── 5. Contar total (para paginación) ────────────────────── */
$count_sql = "SELECT COUNT(DISTINCT p.id) AS total
              FROM products p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              $where_sql";

$count_stmt = mysqli_prepare($conn, $count_sql);
if ($count_stmt && !empty($bind_values)) {
    mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_values);
}
$total = 0;
if ($count_stmt) {
    mysqli_stmt_execute($count_stmt);
    $cr = mysqli_stmt_get_result($count_stmt);
    if ($cr) $total = (int) mysqli_fetch_assoc($cr)['total'];
}

/* ── 6. Query principal ────────────────────────────────────── */
$sql = "SELECT
            p.id,
            p.user_id,
            p.title,
            p.price,
            p.condition_type,
            p.created_at,
            u.full_name,
            c.name AS category_name,
            (SELECT pi.image_url
             FROM product_images pi
             WHERE pi.product_id = p.id
             ORDER BY pi.id ASC
             LIMIT 1) AS image_url,
            $promo_subselect AS promotion_type,
            $promo_rank_expr AS promo_rank,
            COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS total_reviews
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        $where_sql
        GROUP BY p.id
        ORDER BY $order_sql
        LIMIT ? OFFSET ?";

/* Agregar limit y offset a los parámetros */
$bind_types_full   = $bind_types . 'ii';
$bind_values_full  = array_merge($bind_values, [$limit, $offset]);

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $bind_types_full, ...$bind_values_full);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    /* Fallback graceful */
    echo json_encode(['ok' => false, 'error' => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── 7. Construir respuesta ────────────────────────────────── */
$data = [];

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {

        /* Tiempo relativo */
        $diff = $row['created_at'] ? time() - strtotime($row['created_at']) : 0;
        if      ($diff < 60)     $time_label = 'Hace unos segundos';
        elseif  ($diff < 3600)   $time_label = 'Hace ' . floor($diff / 60)   . ' min';
        elseif  ($diff < 86400)  $time_label = 'Hace ' . floor($diff / 3600) . ' h';
        elseif  ($diff < 604800) $time_label = 'Hace ' . floor($diff / 86400) . ' días';
        else                     $time_label = date('d/m/Y', strtotime($row['created_at']));

        /* Iniciales del vendedor */
        $initials = '';
        foreach (explode(' ', trim($row['full_name'] ?? 'U')) as $part) {
            if ($part) $initials .= strtoupper($part[0]);
        }
        $initials = substr($initials, 0, 2) ?: 'U';

        $data[] = [
            'id'              => (int) $row['id'],
            'seller_id'       => (int) $row['user_id'],
            'title'           => $row['title'],
            'price'           => number_format($row['price'], 0, ',', '.'),
            'price_raw'       => (int) $row['price'],
            'condition_type'  => $row['condition_type'],
            'time_label'      => $time_label,
            'seller_name'     => $row['full_name'] ?? 'Usuario',
            'seller_initials' => $initials,
            'category_name'   => $row['category_name'] ?? 'General',
            'image_url'       => $row['image_url'] ?? '',
            'image_path'      => $row['image_url']
                                    ? '../../public/uploads/products/' . $row['image_url']
                                    : '',
            'avg_rating'      => (float) ($row['avg_rating'] ?? 0),
            'total_reviews'   => (int) ($row['total_reviews'] ?? 0),
            /* ── NUEVO: tipo de promoción activa (null si ninguna) ── */
            'promotion_type'  => $row['promotion_type'],  /* 'premium'|'recommended'|'basic'|null */
        ];
    }
}

echo json_encode([
    'ok'       => true,
    'total'    => $total,
    'count'    => count($data),
    'offset'   => $offset,
    'limit'    => $limit,
    'products' => $data,
], JSON_UNESCAPED_UNICODE);



