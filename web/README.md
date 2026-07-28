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
  noticias.php, noticia.php    listado y detalle de noticias
  servicios.php                Git/Wiki/Nube/Webmail/Natasha BNC
  natasha/                     Natasha Bouncer: index.php (ES), en.php (EN),
                                solicitar.php (formulario bilingüe de solicitud)
  includes/       conexión a DB, helpers, header/footer + radio_player.php
  admin/          panel de administración (login requerido)
  css/, js/       estilos y JS del sitio público (incluye el reproductor de radio)
  assets/         favicon, etc.
config.example.php   plantilla de configuración (config.php NO se versiona)
```

## Editable desde el panel (`/admin`)

- **Salas**: nombre, categoría (general/regional/adultos/ayuda), descripción,
  marca NSFW, orden y si está activa.
- **Staff**: nick, rol (administrador/ircop/soporte), bio, avatar y orden.
- **Noticias**: título, slug, resumen, contenido, fecha y si está publicada.
- **Servicios**: nombre, URL, descripción e ícono (tarjetas de servicios.php).
- **Redes BNC**: las redes donde está Natasha (host, IP, puerto, SSL, estado
  operativo/no operativo y motivo si está prohibida).
- **Solicitudes BNC**: pedidos del formulario de `/natasha/solicitar.php`,
  con cambio de estado (pendiente/aprobado/rechazado).
- **Ajustes del sitio**: nombre del sitio, tagline, datos de conexión IRC,
  URL/nombre del stream de radio y los límites y precios del servicio
  Natasha Bouncer (plan Free, precio Premium, extra por IP privada).

Todo el acceso al panel requiere sesión iniciada; las consultas usan
sentencias preparadas (PDO) y los formularios llevan token CSRF. El
formulario público de solicitud de Natasha lleva además un honeypot básico
contra bots.
