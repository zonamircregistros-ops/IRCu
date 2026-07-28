<?php
declare(strict_types=1);
$pageTitle = 'Solicitudes BNC';
$activeAdminNav = 'bnc_requests';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = [
    'pendiente' => 'Pendiente',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM bnc_requests')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT * FROM bnc_requests ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Solicitudes de Natasha Bouncer</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nick</th>
        <th>Contacto</th>
        <th>Plan</th>
        <th>Datacenter</th>
        <th>Redes pedidas</th>
        <th>Contraseña BNC</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="9">Todavía no llegaron solicitudes.</td></tr>
      <?php endif; ?>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td><?= h($r['nick']) ?></td>
          <td><?= h($r['contact']) ?> <?= $r['email_verified'] ? '<span class="pill pill-on" title="Email verificado">✓</span>' : '<span class="pill pill-off" title="Email sin verificar">?</span>' ?></td>
          <td><?= $r['plan'] === 'premium' ? 'Premium' : 'Free' ?></td>
          <td><?= h($r['datacenter'] ?: '—') ?></td>
          <td><?= h($r['networks_wanted'] ?: '—') ?></td>
          <td><?= $r['bnc_password'] ? '<code>' . h($r['bnc_password']) . '</code>' : '—' ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
          <td>
            <form method="post" action="bnc_requests_update.php" style="display:flex; gap:6px; align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $r['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-ghost btn-sm" type="submit">Guardar</button></noscript>
            </form>
          </td>
          <td class="actions">
            <form method="post" action="bnc_requests_delete.php" onsubmit="return confirm('¿Eliminar esta solicitud?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php if (!empty($r['notes'])): ?>
          <tr>
            <td colspan="9" style="color: var(--text-dimmer); font-size: 0.85rem;">📝 <?= h($r['notes']) ?></td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
