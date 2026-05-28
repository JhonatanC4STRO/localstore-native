# 💬 Servidor de Chat y WebSockets

ComercioLocal ofrece una experiencia de mensajería instantánea en tiempo real. Esta funcionalidad se apoya en un servidor WebSocket asíncrono implementado en PHP nativo con la biblioteca **Ratchet**.

---

## 🛠️ Arquitectura del Servidor (`server.php`)

El WebSocket corre como un proceso persistente e independiente del servidor web estándar (Apache/Nginx). 

- **Estructura asíncrona**: Utiliza un bucle de eventos asíncrono para gestionar cientos de conexiones concurrentes de forma eficiente bajo un solo hilo de ejecución.
- **Acceso Dinámico**: Carga automáticamente las configuraciones de host (`WS_HOST`) y puerto (`WS_PORT`) definidas en tus variables de entorno para una portabilidad total.

---

## 🔒 Validación de Acceso Segura (`onMessage`)

Cuando un usuario inicia la conexión al chat desde su navegador, envía un evento de inicialización (`type: 'init'`) con su identificador y el id de la conversación:

```json
{
  "type": "init",
  "user_id": 4,
  "conversation_id": 12
}
```

Para evitar ataques de espionaje o suplantación de identidad (donde un usuario ajeno intente escuchar un chat privado de otras personas), el servidor WebSocket ejecuta una **consulta preparada segura** al instante:

```php
$stmt = mysqli_prepare(
    $this->db,
    "SELECT id FROM conversations
     WHERE id = ?
       AND (buyer_id = ? OR seller_id = ?)
     LIMIT 1"
);
```

### Flujo de Conexión:
1. Si el usuario pertenece a la conversación (como comprador o vendedor), el servidor WebSocket acepta la suscripción al canal privado.
2. Si el usuario no pertenece a la conversación, el servidor WebSocket **cierra inmediatamente la conexión (`$from->close()`)** y registra la alerta de acceso denegado en la consola del servidor de forma silenciosa.

---

## 🚀 Cómo Iniciar el Servidor de Chat

Ejecuta el siguiente comando en tu consola de terminal dentro del directorio raíz del proyecto:

```bash
php server.php
```

Salida esperada en consola:
```
Nueva conexión WS: 104
User 4 se unió a conv 12 en connection 104
```
