<?php
/* ══════════════════════════════════════════════════════════════
   suggest.php
   Endpoint AJAX — devuelve sugerencias para el autocomplete del search.
   Secciones: history (si hay sesión), products, categories, cities.
   GET ?q=<término>  (puede venir vacío para mostrar solo historial)
══════════════════════════════════════════════════════════════ */

include(__DIR__ . '/../../config/conexion.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

mysqli_set_charset($conn, 'utf8mb4');

$q   = isset($_GET['q']) ? trim($_GET['q']) : '';
$uid = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;

$response = [
  'q'          => $q,
  'history'    => [],
  'products'   => [],
  'categories' => [],
  'cities'     => [],
];

/* ── Historial: solo logueados.
     Si q está vacío → últimas 6 búsquedas distintas.
     Si q tiene texto → últimas 4 que contengan q.        */
if ($uid > 0) {
    $hsql = "SELECT query, MAX(created_at) AS last_used
             FROM search_history
             WHERE user_id = $uid";
    if ($q !== '') {
        $qsafe = mysqli_real_escape_string($conn, $q);
        $hsql .= " AND query LIKE '%$qsafe%'";
    }
    $hsql .= " GROUP BY query ORDER BY last_used DESC LIMIT " . ($q !== '' ? 4 : 6);

    $hr = mysqli_query($conn, $hsql);
    if ($hr) while ($row = mysqli_fetch_assoc($hr)) $response['history'][] = $row['query'];
}

/* ── Productos / categorías / ciudades: solo si hay query no vacío. */
if ($q !== '') {
    $qsafe = mysqli_real_escape_string($conn, $q);

    /* Top 5 productos por relevancia. Ciudad: preferimos la del producto, fallback a la del vendedor. */
    $psql = "SELECT p.id, p.title, p.price,
                    cat.name AS category_name,
                    COALESCE(NULLIF(p.city,''), u.city) AS city,
                    (SELECT pi.image_url FROM product_images pi
                     WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
             FROM products p
             LEFT JOIN categories cat ON cat.id = p.category_id
             LEFT JOIN users u        ON u.id   = p.user_id
             WHERE p.status = 'disponible'
               AND p.admin_status = 'active'
               AND (
                  p.title       LIKE '%$qsafe%'
                  OR p.description LIKE '%$qsafe%'
                  OR cat.name   LIKE '%$qsafe%'
                  OR p.city     LIKE '%$qsafe%'
                  OR u.city     LIKE '%$qsafe%'
               )
             ORDER BY
               (CASE
                  WHEN p.title  LIKE '$qsafe%'  THEN 5
                  WHEN p.title  LIKE '%$qsafe%' THEN 4
                  WHEN cat.name LIKE '%$qsafe%' THEN 3
                  WHEN p.city   LIKE '%$qsafe%' THEN 2
                  WHEN u.city   LIKE '%$qsafe%' THEN 2
                  WHEN p.description LIKE '%$qsafe%' THEN 1
                  ELSE 0
                END) DESC,
               p.id DESC
             LIMIT 5";
    $pr = mysqli_query($conn, $psql);
    if ($pr) while ($row = mysqli_fetch_assoc($pr)) {
        $response['products'][] = [
          'id'       => (int)$row['id'],
          'title'    => $row['title'],
          'price'    => '$' . number_format((float)$row['price'], 0, ',', '.'),
          'category' => $row['category_name'],
          'city'     => $row['city'],
          'thumb'    => $row['thumb'] ?: null,
        ];
    }

    /* Categorías que matcheen */
    $cr = mysqli_query($conn,
        "SELECT id, name
         FROM categories
         WHERE name LIKE '%$qsafe%'
         ORDER BY (CASE WHEN name LIKE '$qsafe%' THEN 1 ELSE 2 END), name ASC
         LIMIT 4");
    if ($cr) while ($row = mysqli_fetch_assoc($cr)) {
        $response['categories'][] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }

    /* Ciudades distintas que matcheen — UNION de ciudades de productos y vendedores */
    $tr = mysqli_query($conn,
        "SELECT city FROM (
            SELECT DISTINCT city FROM products
              WHERE city IS NOT NULL AND city != '' AND city LIKE '%$qsafe%'
            UNION
            SELECT DISTINCT city FROM users
              WHERE city IS NOT NULL AND city != '' AND city LIKE '%$qsafe%'
         ) AS t
         ORDER BY (CASE WHEN city LIKE '$qsafe%' THEN 1 ELSE 2 END), city ASC
         LIMIT 4");
    if ($tr) while ($row = mysqli_fetch_assoc($tr)) {
        $response['cities'][] = $row['city'];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
