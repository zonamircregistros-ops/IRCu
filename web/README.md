# Sitio web de Chateanos (PHP + MySQL)

Landing page + directorio de salas + staff, con panel de administración
para editar todo sin tocar código.

## Requisitos

- PHP 8.1+ con extensión `pdo_mysql`
- MySQL o MariaDB
- Un MTA local (Postfix) configurado para que `sendmail_path` de PHP pueda
  entregar correo saliente — lo usan las notificaciones de Natasha Bouncer,
  apelaciones G-Line y tickets (`web/includes/mailer.php`, vía `mail()`).
  Sin esto los formularios siguen funcionando, pero el envío de emails
  queda registrado como fallido en el log de errores de PHP.

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
  rss.php                      feed RSS 2.0 de noticias publicadas
  servicios.php                Git/Wiki/Nube/Webmail/Natasha BNC
  natasha/                     Natasha Bouncer: index.php (ES), en.php (EN),
                                solicitar.php y tutorial.php (bilingües)
  faq.php                      preguntas frecuentes sobre IRC y la red
  buscar.php                   buscador (noticias, salas, FAQ)
  gestiones.php                 hub de trámites con el staff
  apelar.php, ircop.php, tickets.php, ticket.php   formularios de Gestiones
                                y seguimiento de tickets por token
  verificar.php                 confirmación de email (double opt-in) para
                                los formularios públicos
  donaciones.php, creditos.php, estado.php   donaciones, créditos y estado
                                del servicio
  privacidad.php, terminos.php  política de privacidad y términos de uso
  404.php                       página de error 404 personalizada
  sitemap.php, robots.txt       SEO: mapa del sitio dinámico y robots.txt
  .htaccess                     ErrorDocument 404, protección de config.php
  includes/       conexión a DB, helpers, mailer.php, header/footer,
                  radio_player.php y cookie_banner.php
  admin/          panel de administración (login requerido)
  css/, js/       estilos y JS del sitio público (incluye el reproductor de radio)
  assets/         favicons (PNG/SVG), og-image.png, site.webmanifest
config.example.php   plantilla de configuración (config.php NO se versiona)
tools/            scripts PHP+GD de un solo uso para regenerar favicons y
                  la imagen Open Graph (no accesibles desde la web)
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
  host/puerto de conexión de Natasha Bouncer, URL/nombre del stream de radio
  y los límites y precios del servicio (plan Free, precio Premium, extra
  por IP privada).
- **Apelaciones G-Line**: revisar y responder apelaciones de expulsión
  (aprobar/rechazar + respuesta, se notifica por email al usuario).
- **Postulaciones IRCop**: ver el detalle completo de cada postulación y
  cambiar su estado, con notas internas del staff.
- **Tickets**: hilo de conversación con el usuario, respuesta desde el panel
  (notifica por email) y estado (abierto/aprobado/rechazado/cerrado).
- **Testimonios**: frases de la comunidad que se muestran en el home.
- **Créditos**: fundadores, staff, colaboradores y donantes (`/creditos.php`).
- **Estado del servicio**: estado manual de Red IRC, Webchat, Natasha Bouncer,
  Git, Wiki, Nube y Webmail (`/estado.php`).
- **Analítica**: vistas por página, top de páginas y referrers, sin cookies
  ni scripts de terceros (`page_views` solo guarda ruta, referrer y fecha).
- Los listados largos (noticias, solicitudes, apelaciones, postulaciones,
  tickets) están paginados (20 filas por página).
- Una campana de notificaciones en la barra lateral del panel muestra la
  cantidad de solicitudes/apelaciones/postulaciones pendientes y tickets
  abiertos, con un desplegable de acceso directo.

### Notificaciones por email

Al aprobar o rechazar una solicitud de Natasha Bouncer se genera una
contraseña aleatoria (visible solo para el admin en el listado) y se le
envía al usuario el host, puerto, usuario y contraseña de conexión, junto
con el link al tutorial (`/natasha/tutorial.php`). Las apelaciones G-Line
mandan confirmación al enviarse y la respuesta del staff por email; los
tickets avisan por email en cada respuesta del staff, con el link privado
de seguimiento (`/ticket.php?token=...`).

Todo el acceso al panel requiere sesión iniciada; las consultas usan
sentencias preparadas (PDO) y los formularios administrativos llevan token
CSRF. Los formularios públicos (solicitud de Natasha, apelaciones,
postulaciones, tickets) llevan un honeypot básico contra bots.

### Legal, privacidad y anti-abuso

- **Privacidad y Términos** (`/privacidad.php`, `/terminos.php`) y un
  **aviso de cookies** (localStorage, sin cookies de terceros) que se
  muestra una sola vez por navegador.
- **Rate limiting** real (tabla `rate_limits`, por IP y por formulario) en
  solicitud de Natasha, apelaciones, postulaciones IRCop y creación/
  respuesta de tickets — máximo 5 envíos por hora (20 para respuestas de
  ticket), para frenar abuso sin bloquear el uso normal.
- **Verificación de email** (double opt-in no bloqueante): cada formulario
  público manda un link de confirmación (`/verificar.php`); el panel
  muestra una marca ✓/? de email verificado/sin verificar, pero la
  solicitud se puede gestionar igual sin esperar la confirmación.

### SEO y descubribilidad

Open Graph / Twitter Cards en todas las páginas (con `og-image.png`
generada), `sitemap.php` dinámico + `robots.txt`, favicons completos
(16/32/180/192/512 px) + `site.webmanifest` para instalar el sitio como
PWA, feed RSS de noticias, buscador interno y una página 404 propia.
