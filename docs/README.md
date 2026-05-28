# 🛍️ ComercioLocal — Documentación Técnica

Bienvenido a la documentación técnica de **ComercioLocal**, la plataforma web nativa diseñada para conectar de forma ágil y segura a compradores y vendedores de la misma ciudad. 

Esta documentación detalla la arquitectura, el modelo de datos, los flujos del servidor de chat en tiempo real y el robusto esquema de seguridad del sistema.

---

## 🚀 Vista General del Proyecto

ComercioLocal está construido utilizando un stack ligero y veloz enfocado en el rendimiento y la portabilidad:

- **Core**: PHP 8.1+ y HTML5.
- **Base de Datos**: MySQL / MariaDB.
- **Frontend / Diseño**: CSS Nativo y componentes dinámicos con fuentes tipográficas Premium.
- **Mensajería en tiempo real**: Servidor de WebSocket asíncrono con **Ratchet** y PHP.

---

## 🛠️ Instalación y Configuración Rápida

1. **Requisitos Previos**:
   - Servidor local compatible (Laragon, XAMPP o Docker con PHP 8.1+).
   - MySQL/MariaDB.
   - Composer (para dependencias de servidor WebSocket).

2. **Clonar e Instalar dependencias**:
   ```bash
   git clone https://github.com/JhonatanC4STRO/localstore-native.git
   cd localstore-native
   composer install
   ```

3. **Configurar Base de Datos**:
   - Crea una base de datos llamada `tiendalocal`.
   - Importa el archivo principal `database/tiendalocal.sql`.
   - Aplica las migraciones adicionales en el orden:
     1. `database/promotion_plans.sql`
     2. `database/add_views_column.sql`
     3. `database/add_product_city.sql`

4. **Correr Servidor de Chat**:
   ```bash
   php server.php
   ```

---

## 📖 Contenido de la Guía

Para conocer a fondo el sistema, te sugerimos explorar la barra lateral o los siguientes enlaces de interés:

* [⚙️ Configuración y Variables de Entorno](config.md)
* [🗄️ Estructura de Base de Datos y Columnas](db.md)
* [🛡️ Blindaje de Seguridad y CSRF](security.md)
* [💬 Flujo de Conexiones WebSocket](websocket.md)
