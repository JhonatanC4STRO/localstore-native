# 🛡️ Blindaje de Seguridad y CSRF

ComercioLocal cuenta con una arquitectura de seguridad por capas para evitar inyecciones, ataques de suplantación y accesos no autorizados.

---

## 1. Prevención de Inyección SQL (Consultas Preparadas)

Todas las transacciones en los controladores críticos se realizan exclusivamente mediante sentencias preparadas parametrizadas en lugar de concatenar cadenas directo en SQL:

```php
$stmt = mysqli_prepare($conn, "SELECT id, buyer_id, seller_id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?) LIMIT 1");
mysqli_stmt_bind_param($stmt, "iii", $conversation_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
```

Este enfoque garantiza que el motor SQL trate las variables de usuario estrictamente como datos inertes, previniendo inyecciones de código malicioso.

---

## 2. Protección contra Falsificación de Solicitud en Sitios Cruzados (CSRF)

Implementamos tokens de seguridad criptográficos para validar el origen auténtico de cada formulario `POST` en la aplicación:

- **Generador/Verificador (`src/config/csrf.php`)**: Genera un token aleatorio y robusto (`bin2hex(random_bytes(32))`) único por sesión de usuario y valida su origen.
- **Inserción en formularios**:
  ```html
  <form action="..." method="POST">
      <?php require_once "../../config/csrf.php"; insert_csrf_input(); ?>
  </form>
  ```
- **Validación obligatoria**: Todos los POST de inicio de sesión, registro, restablecimiento y publicación de productos comprueban la correspondencia del token antes de realizar cualquier operación.

---

## 3. Robustecimiento de Sesiones (Fijación de Sesión)

Para prevenir que un atacante robe cookies de sesión activas o explote IDs fijos de sesión, se aplica el refresco del identificador tras iniciar sesión o registrarse con éxito:

```php
session_regenerate_id(true);
$_SESSION['user'] = $user_row;
```

---

## 4. Validación de Subida de Archivos (Filtro Antivirus y RCE)

Para evitar que se suban archivos ejecutables del lado del servidor (como scripts maliciosos `.php` disfrazados de fotos), el backend valida directamente la cabecera real de los archivos:

1. **Lectura de Tipo MIME Real**: Se utiliza la librería nativa de PHP `finfo` para leer el tipo binario real del archivo temporal:
   ```php
   $finfo = finfo_open(FILEINFO_MIME_TYPE);
   $mime = finfo_file($finfo, $tmp_name);
   ```
2. **Formatos Permitidos**: Exclusivamente JPG (`image/jpeg`), PNG (`image/png`) y WebP (`image/webp`).
3. **Control de Tamaño**: Límite estricto de **5MB** por imagen.

---

## 5. Exclusiones de Conexión y Logger Interno (`src/config/logger.php`)

Se erradicaron por completo los enunciados de error que exponían la estructura técnica interna del servidor (`die(mysqli_error())`).
- **Logger**: Los errores se graban discretamente en `src/logs/error.log` con una IP y fecha precisas.
- **Usuario Final**: Se despliega una interfaz de error amigable, genérica e inocua para resguardar la privacidad de tu infraestructura.
