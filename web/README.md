# Sitio web de Chateanos (PHP + MySQL)

Landing page + directorio de salas + staff, con panel de administración
para editar todo sin tocar código.

## Requisitos

- PHP 8.1+ con extensión `pdo_mysql`
- MySQL o MariaDB

## Instalación

1. Creá la base de datos y cargá el esquema + datos de ejemplo:
   ```
   mysql -u root -p -e "CREATE DATABASE chateanos CHARACTER SET utf8mb4"
   mysql -u root -p chateanos < ../sql/schema.sql
   mysql -u root -p chateanos < ../sql/seed.sql
   ```
2. Copiá `config.example.php` a `config.php` y completá los datos de conexión:
   ```
   cp config.example.php config.php
   ```
3. Apuntá tu servidor web (Apache/Nginx/PHP-FPM) a este directorio, o para
   probar rápido en local:
   ```
   php -S 127.0.0.1:8000
   ```
4. Entrá a `/admin/login.php` con el usuario de ejemplo:
   - Usuario: `admin`
   - Contraseña: `ChangeMe123!`

   **Cambiá esa contraseña de inmediato** desde "Mi cuenta" en el panel
   antes de publicar el sitio.

## Estructura

```
web/
  index.php, salas.php, staff.php, normas.php, conectar.php   páginas públicas
  includes/       conexión a DB, helpers, header/footer compartidos
  admin/          panel de administración (login requerido)
  css/, js/       estilos y JS del sitio público
  assets/         favicon, etc.
config.example.php   plantilla de configuración (config.php NO se versiona)
```

## Editable desde el panel (`/admin`)

- **Salas**: nombre, categoría (general/regional/adultos/ayuda), descripción,
  marca NSFW, orden y si está activa.
- **Staff**: nick, rol (administrador/ircop/soporte), bio, avatar y orden.
- **Ajustes del sitio**: nombre del sitio, tagline, URL del webchat, servidor
  IRC, puertos, canal general y email de contacto — todo lo que las páginas
  públicas muestran dinámicamente.

Todo el acceso al panel requiere sesión iniciada; las consultas usan
sentencias preparadas (PDO) y los formularios llevan token CSRF.
