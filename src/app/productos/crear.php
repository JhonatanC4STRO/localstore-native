<?php require_once("../../config/conexion.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>

<body>

    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-top: 10px;
        }
    </style>

    <form action="./controller/process_create_product.php" method="POST" enctype="multipart/form-data">

        <label for="fotos">Fotos del producto:</label>
        <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple required>

        <label for="nombre">Titulo:</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="precio">Precio:</label>
        <input type="number" id="precio" name="precio" required>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion"></textarea>

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">


        <?php
        $query = "SELECT * FROM categories";
        $result = mysqli_query($conn, $query);
        ?>

        <label for="categoria">Categoría:</label>
        <select name="categoria" required>

            <option value="">Seleccionar categoría</option>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <option value="<?php echo $row['id']; ?>">
                    <?php echo $row['name']; ?>
                </option>

            <?php } ?>

        </select>
        <label for="estado">Estado:</label>
        <select name="estado" required>
            <option value="">Seleccionar estado</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select>

        <button type="submit">Crear Producto</button>

    </form>

    <Button type="button" id="btnUbicacion">Agregar ubicacion</Button>
    <p id="est"></p>
    <div id="map"> </div>

    <hr>
    <h2>Productos</h2>

    <?php

    $query = "SELECT products.*, categories.name as category_name
          FROM products
          LEFT JOIN categories ON products.category_id = categories.id";

    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_array($result)) {

        echo "<h3>" . $row['title'] . "</h3>";
        echo "<p>Precio: $" . $row['price'] . "</p>";
        echo "<p>Descripción: " . $row['description'] . "</p>";
        echo "<p>Categoría: " . $row['category_name'] . "</p>";
        echo "<p>Estado: " . $row['status'] . "</p>";
        echo "<p>Ubicación: " . $row['latitude'] . ", " . $row['longitude'] . "</p>";

        // Mostrar imágenes
        $product_id = $row['id'];
        $img_query = "SELECT * FROM product_images WHERE product_id = '$product_id'";
        $img_result = mysqli_query($conn, $img_query);

        while ($img_row = mysqli_fetch_array($img_result)) {
            echo "<img src='./uploads/" . $img_row['image_url'] . "' width='120'>";
        }

        // Mostrar mapa si tiene ubicación
        // ... (dentro de tu bucle while de productos)

        if ($row['latitude'] && $row['longitude']) {
            $lat = $row['latitude'];
            $lon = $row['longitude'];
            $map_id = "map_" . $row['id'];

            echo "<div id='$map_id' style='height:200px; width:300px; margin-top:10px; border:1px solid #ccc;'></div>";

            // Usamos un evento para asegurar que el DOM esté listo
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

        echo "<hr>";
    }

    ?>

    <script>
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        let map;

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                estado.textContent = "La geolocalización no es soportada por tu navegador.";
                return;
            }

            estado.textContent = "Obteniendo ubicación...";

            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;

                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;
                estado.textContent = `Ubicación obtenida: Lat ${lat}, Lon ${lon}`;

                // SI YA EXISTE UN MAPA, ELIMÍNALO ANTES DE CREAR OTRO
                if (map !== undefined && map !== null) {
                    map.remove();
                }

                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                L.marker([lat, lon]).addTo(map).bindPopup("📍 Estás aquí").openPopup();
            }, (error) => {
                estado.textContent = "Error al obtener ubicación: " + error.message;
            });
        });
    </script>

</body>

</html>