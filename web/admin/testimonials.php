<?php
declare(strict_types=1);
$pageTitle = 'Testimonios';
$activeAdminNav = 'testimonials';
require __DIR__ . '/includes/admin_header.php';

$testimonials = db()->query(
    'SELECT id, author_nick, quote, years_in_network, is_active, sort_order FROM testimonials ORDER BY sort_order, id'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Testimonios</h1>
  <a class="btn btn-primary" href="testimonials_form.php">+ Nuevo testimonio</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nick</th>
        <th>Frase</th>
        <th>Años</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($testimonials)): ?>
        <tr><td colspan="5">Todavía no hay testimonios cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($testimonials as $t): ?>
        <tr>
          <td><?= h($t['author_nick']) ?></td>
          <td><?= h(mb_substr($t['quote'], 0, 60)) ?><?= mb_strlen($t['quote']) > 60 ? '…' : '' ?></td>
          <td><?= $t['years_in_network'] !== null ? (int) $t['years_in_network'] : '—' ?></td>
          <td><?= $t['is_active'] ? '<span class="pill pill-on">Activo</span>' : '<span class="pill pill-off">Inactivo</span>' ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="testimonials_form.php?id=<?= (int) $t['id'] ?>">Editar</a>
            <form method="post" action="testimonials_delete.php" onsubmit="return confirm('¿Eliminar este testimonio?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
