<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$pageTitle = 'Página no encontrada';
$activeNav = '';
$pageDescription = 'Esta página no existe o fue movida.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>404 — No encontramos esta página</h1>
    <p>El link puede estar roto o la página se movió. Probá alguno de estos atajos:</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="category-grid">
      <a class="category-card" href="/index.php">
        <div class="cat-icon">🏠</div>
        <h3>Inicio</h3>
        <p>Volver a la página principal</p>
      </a>
      <a class="category-card" href="/salas.php">
        <div class="cat-icon">💬</div>
        <h3>Salas</h3>
        <p>Ver todos los canales</p>
      </a>
      <a class="category-card" href="/buscar.php">
        <div class="cat-icon">🔎</div>
        <h3>Buscar</h3>
        <p>Buscar en todo el sitio</p>
      </a>
      <a class="category-card" href="/gestiones.php">
        <div class="cat-icon">🎫</div>
        <h3>Gestiones</h3>
        <p>Soporte, apelaciones y más</p>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
