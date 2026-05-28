# ⚙️ Arquitectura y Configuración

ComercioLocal utiliza variables de entorno para flexibilizar el despliegue tanto en servidores locales (Laragon) como en plataformas cloud (Docker, Railway, AWS).

---

## 🔌 Conexión a la Base de Datos (`src/config/conexion.php`)

El sistema detecta automáticamente si el entorno provee un string de conexión de base de datos integrado de algún proveedor Cloud (`MYSQL_URL` o `DATABASE_URL`) o lee variables individuales:

| Variable | Descripción | Valor por Defecto |
|---|---|---|
| `DB_HOST` / `MYSQLHOST` | Host de base de datos | `localhost` |
| `DB_USER` / `MYSQLUSER` | Usuario de base de datos | `root` |
| `DB_PASS` / `MYSQLPASSWORD` | Contraseña | (vacío) |
| `DB_NAME` / `MYSQLDATABASE` | Nombre de base de datos | `tiendalocal` |
| `DB_PORT` / `MYSQLPORT` | Puerto de escucha | `3306` |

---

## 🔑 Credenciales Dinámicas de Administrador

Para facilitar los despliegues limpios y proteger el acceso a roles de administración en producción, ComercioLocal no tiene contraseñas estáticas ni quemadas en código para su cuenta administrador por defecto. El inicio de sesión las valida dinámicamente desde el entorno:

| Variable | Propósito | Fallback Predeterminado |
|---|---|---|
| `ADMIN_EMAIL` | Correo electrónico de inicio del Administrador principal | `admin@gmail.com` |
| `ADMIN_PASSWORD` | Contraseña del Administrador principal en el primer inicio | `admin` |

### 🔒 Forzar Cambio de Contraseña en Primer Inicio:
Al iniciar sesión por primera vez con los valores definidos en `ADMIN_EMAIL` y `ADMIN_PASSWORD`, el sistema:
1. Intercepta el inicio de sesión.
2. Identifica que es el primer inicio técnico de esa cuenta.
3. Bloquea el panel de administración y **redirige obligatoriamente** al usuario a `reset_password.php?migrate=1`.
4. El administrador debe definir una contraseña robusta personalizada. Esta se hashea en la base de datos con **BCRYPT** y las credenciales por defecto quedan invalidadas.

---

## 💬 Servidor WebSocket (`server.php`)

El servidor WebSocket asíncrono también lee variables de entorno para su unión y transporte público en redes locales o de internet:

| Variable | Propósito | Fallback Predeterminado |
|---|---|---|
| `WS_HOST` | Host para escuchar conexiones websocket | `localhost` |
| `WS_PORT` | Puerto para escuchar conexiones websocket | `8080` |
