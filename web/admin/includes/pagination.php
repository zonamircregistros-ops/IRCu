<?php
declare(strict_types=1);

/**
 * Partial reutilizable de paginación para listados de admin.
 * Espera en scope: int $page, int $totalPages.
 */
if ($totalPages > 1):
?>
<div class="admin-pagination">
  <?php if ($page > 1): ?>
    <a class="btn btn-ghost btn-sm" href="?page=<?= $page - 1 ?>">← Anterior</a>
  <?php endif; ?>
  <span class="admin-pagination-status">Página <?= $page ?> de <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a class="btn btn-ghost btn-sm" href="?page=<?= $page + 1 ?>">Siguiente →</a>
  <?php endif; ?>
</div>
<?php endif; ?>
