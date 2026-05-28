# ComercioLocal

Marketplace local en PHP con chat en tiempo real, geolocalización, planes de promoción y panel de administración.

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Railway](https://img.shields.io/badge/Deploy-Railway-0B0D0E?logo=railway)](https://railway.app)
[![Docs](https://img.shields.io/badge/Docs-Starlight-FF5D01?logo=astro)](./docs)

---

## Características

- **Marketplace local** — publicación de productos con categorías, imágenes, precio y ubicación GPS
- **Chat en tiempo real** — WebSocket con Ratchet, historial persistente por conversación
- **Geolocalización** — mapa interactivo con Leaflet.js + Nominatim, filtro por ciudad
- **Planes de promoción** — productos Básico / Recomendado / Premium con badges y prioridad
- **Sistema de reportes** — reportar productos y usuarios, moderación por admin
- **Panel de administración** — CRUD de usuarios y productos, gestión de reportes, log de actividad
- **Verificación de vendedores** — solicitud y aprobación de badge verificado
- **Reseñas** — valoraciones con estrellas por compra/venta
- **Favoritos** — guardar productos para después
- **CSRF + SQL injection** — tokens por formulario, prepared statements en todas las queries

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 (sin framework) |
| Base de datos | MySQL 8.0 |
| WebSocket | Ratchet 0.4 (Cboden) |
| CSS | Tailwind CSS v4 |
| Mapas | Leaflet.js + Nominatim |
| Sliders | Swiper.js |
| Animaciones | Animate.css + AOS |
| Deploy | Railway.app + Docker |
| Docs | Astro Starlight |

---

## Estructura

```
localstore-native/
├── public/                 # Archivos públicos (CSS, JS, uploads)
│   ├── css/
│   ├── js/
│   └── uploads/products/
├── src/
│   ├── api/                # Endpoints JSON (AJAX)
│   │   ├── admin/
│   │   ├── chat/
│   │   ├── favorites/
│   │   ├── products/
│   │   └── payments/
│   ├── config/             # DB, CSRF, logger, role sync
│   ├── controllers/        # Acciones POST (auth, productos)
│   └── views/              # Páginas PHP (auth, products, admin)
├── database/               # SQL: schema + migraciones
├── docs/                   # Documentación Astro Starlight
├── server.php              # Servidor WebSocket Ratchet
├── index.php               # Router principal
├── Dockerfile
└── railway.toml
```

---

## Inicio Rápido

### Requisitos

- PHP 8.2+ con extensión `mysqli`
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (solo para compilar Tailwind)

**Recomendado en Windows:** [Laragon](https://laragon.org/)

### Instalación

```bash
# 1. Clonar
git clone https://github.com/JhonatanC4STRO/localstore-native.git
cd localstore-native

# 2. Dependencias PHP
composer install

# 3. Crear base de datos
mysql -u root -p -e "CREATE DATABASE tiendalocal CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
mysql -u root -p tiendalocal < database/tiendalocal.sql
mysql -u root -p tiendalocal < database/promotion_plans.sql
mysql -u root -p tiendalocal < database/add_views_column.sql
mysql -u root -p tiendalocal < database/add_product_city.sql

# 4. Variables de entorno (opcional — Laragon usa defaults)
cp .env.example .env
```

Con Laragon, la app queda disponible en `http://localstore-native.test/` automáticamente.

### Servidor WebSocket (chat)

```bash
php server.php
```

Escucha en `ws://localhost:8080`.

### Compilar CSS

```bash
# Build único
npx @tailwindcss/cli -i public/css/input.css -o public/css/output.css --minify

# Watch mode
npx @tailwindcss/cli -i public/css/input.css -o public/css/output.css --watch
```

---

## Variables de Entorno

| Variable | Descripción | Default |
|---|---|---|
| `DB_HOST` | Host MySQL | `localhost` |
| `DB_USER` | Usuario MySQL | `root` |
| `DB_PASS` | Contraseña MySQL | _(vacío)_ |
| `DB_NAME` | Nombre de BD | `tiendalocal` |
| `DB_PORT` | Puerto MySQL | `3306` |
| `ADMIN_EMAIL` | Email del admin inicial | — |
| `ADMIN_PASSWORD` | Contraseña del admin | — |
| `WS_HOST` | Host WebSocket | `localhost` |
| `WS_PORT` | Puerto WebSocket | `8080` |
| `MYSQL_URL` | URL completa (Railway) | — |

---

## Despliegue en Railway

1. Crear proyecto en [railway.app](https://railway.app) desde este repo
2. Agregar plugin MySQL → Railway inyecta `MYSQL_URL` automáticamente
3. Configurar variables: `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `WS_HOST=0.0.0.0`
4. Importar BD:

```bash
railway run mysql < database/tiendalocal.sql
railway run mysql < database/promotion_plans.sql
railway run mysql < database/add_views_column.sql
railway run mysql < database/add_product_city.sql
```

Railway detecta el `Dockerfile` y despliega automáticamente en cada push a `main`.

---

## Documentación

Documentación técnica completa en [`/docs`](./docs) (Astro Starlight):

| Sección | Contenido |
|---|---|
| [Vista General](./docs/src/content/docs/00-overview/) | Stack, arquitectura, features |
| [Producto](./docs/src/content/docs/01-product/) | Flujos de usuario, roadmap |
| [Arquitectura](./docs/src/content/docs/02-architecture/) | Capas, WebSocket, request flow |
| [Base de Datos](./docs/src/content/docs/03-database/) | 14 tablas, índices, relaciones |
| [API Reference](./docs/src/content/docs/04-api/) | Todos los endpoints con ejemplos |
| [Frontend](./docs/src/content/docs/05-frontend/) | Componentes, mapas, chat JS |
| [Seguridad](./docs/src/content/docs/07-security/) | CSRF, SQL injection, uploads |
| [Despliegue](./docs/src/content/docs/08-deployment/) | Docker, Railway, checklist |
| [Desarrollo](./docs/src/content/docs/09-development/) | Setup local, debugging, git |

Para ver la docs en local:

```bash
cd docs
npm install
npm run dev
# → http://localhost:4321
```

---

## Credenciales de Prueba

> Solo para desarrollo local — no usar en producción.

| Email | Contraseña | Rol |
|---|---|---|
| `shonano@gmail.com` | `123` | seller |
| `maria@gmail.com` | `123` | seller |
| `topo@gmail.com` | `123` | seller |
| `admin@gmail.com` | (ver BD) | admin |

---

## Licencia

Uso privado / propietario. No licenciado para redistribución o uso comercial sin autorización del autor.

© [JhonatanC4STRO](https://github.com/JhonatanC4STRO)
