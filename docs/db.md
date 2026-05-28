# 🗄️ Modelo y Estructura de Base de Datos

ComercioLocal utiliza un esquema relacional optimizado para indexar de manera ultra veloz productos, geolocalización, valoraciones y conversaciones locales.

---

## 📊 Tablas Clave de la Plataforma

### 1. Tabla `users`
Almacena la información de los usuarios registrados. Puede tener roles como `user` (compradores estándar), `seller` (tiendas y emprendedores independientes que publican productos) y `admin` / `super_admin` (administradores de la plataforma).

- **`password`**: Almacenado de forma cifrada mediante hashes modernos **BCRYPT** (`$2y$`).
- **`role`**: Gestionado dinámicamente mediante el servicio de sincronización automática de roles (`role_sync.php`).
- **`deleted_at`**: Registro lógico de borrado de perfiles (*Soft Delete*) para cumplir normativas de privacidad y retención.

### 2. Tabla `products`
Almacena todos los anuncios disponibles y activos. Cuenta con optimizaciones de rendimiento y geolocalización:

| Columna | Tipo de Datos | Propósito |
|---|---|---|
| `views` | `INT UNSIGNED` | Contador incremental de visualizaciones únicas para estadísticas del producto. |
| `city` | `VARCHAR(100)` | Ciudad exacta de publicación del producto resuelta vía *Reverse Geocoding* (independiente de la del perfil del vendedor). |
| `latitude` / `longitude` | `DOUBLE` | Coordenadas GPS del mapa para ubicarlo con Leaflet.js. |
| `status` | `ENUM` | Estado del artículo (`disponible`, `vendido`). |
| `admin_status` | `ENUM` | Moderación del anuncio (`active`, `inactive`). |

---

## ⚡ Índices y Optimización de Consultas

Para evitar colapsos y garantizar tiempos de carga menores a **150ms** en el listado principal de `home.php` y búsquedas parametrizadas, se han implementado índices estructurados:

- **`idx_city`** en `products(city)`: Optimiza las consultas SQL que filtran por la ciudad actual del usuario o búsqueda local de forma instantánea.
- **`idx_user`** en `favorites(user_id)` y `products(user_id)`: Optimiza los joins y listados personalizados.
