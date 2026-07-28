<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Comparar clientes IRC';
$activeNav = '';
$pageDescription = 'mIRC vs HexChat vs Irssi vs Webchat: cuál te conviene usar en ' . setting('site_name') . '.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>¿Con qué me conecto?</h1>
    <p>Todas estas opciones te conectan a la misma red. Elegí según lo que más te cómodo te resulte.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="admin-card" style="padding:0; overflow-x:auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Plataforma</th>
            <th>Precio</th>
            <th>Curva de aprendizaje</th>
            <th>Sin instalar nada</th>
            <th>Bueno para</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Webchat</strong></td>
            <td>Navegador (cualquier SO)</td>
            <td>Gratis</td>
            <td>Ninguna</td>
            <td><span class="pill pill-on">Sí</span></td>
            <td>Empezar ya, sin compromiso</td>
          </tr>
          <tr>
            <td><strong>HexChat</strong></td>
            <td>Windows, Linux</td>
            <td>Gratis</td>
            <td>Baja</td>
            <td><span class="pill pill-off">No</span></td>
            <td>Uso diario con interfaz gráfica simple</td>
          </tr>
          <tr>
            <td><strong>mIRC</strong></td>
            <td>Windows</td>
            <td>Shareware (con período de prueba)</td>
            <td>Media</td>
            <td><span class="pill pill-off">No</span></td>
            <td>Scripts y personalización avanzada</td>
          </tr>
          <tr>
            <td><strong>Irssi</strong></td>
            <td>Linux, macOS (terminal)</td>
            <td>Gratis</td>
            <td>Alta</td>
            <td><span class="pill pill-off">No</span></td>
            <td>Uso en servidor, dentro de tmux/screen, con Natasha Bouncer siempre conectado</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="rules-footnote">
      Para clientes de escritorio necesitás los datos de conexión: mirá la página de
      <a href="/conectar.php">Conectar</a>. Si querés mantenerte conectado incluso con el cliente
      cerrado, sumá <a href="/natasha/">Natasha Bouncer</a> (gratis).
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
