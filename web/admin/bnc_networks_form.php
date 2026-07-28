<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$net = [
    'network_name' => '',
    'host' => '',
    'ip_address' => '',
    'port' => 6667,
    'use_ssl' => 1,
    'status' => 'operativo',
    'banned_reason' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM bnc_networks WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró esa red.', 'error');
        header('Location: bnc_networks.php');
        exit;
    }
    $net = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $net['network_name'] = trim((string) ($_POST['network_name'] ?? ''));
    $net['host'] = trim((string) ($_POST['host'] ?? ''));
    $net['ip_address'] = trim((string) ($_POST['ip_address'] ?? ''));
    $net['port'] = (int) ($_POST['port'] ?? 6667);
    $net['use_ssl'] = isset($_POST['use_ssl']) ? 1 : 0;
    $net['status'] = ($_POST['status'] ?? 'operativo') === 'no_operativo' ? 'no_operativo' : 'operativo';
    $net['banned_reason'] = trim((string) ($_POST['banned_reason'] ?? ''));
    $net['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $net['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($net['network_name'] === '') {
        $errors[] = 'El nombre de la red es obligatorio.';
    }
    if ($net['host'] === '') {
        $errors[] = 'El host es obligatorio.';
    }
    if ($net['port'] < 1 || $net['port'] > 65535) {
        $errors[] = 'El puerto tiene que estar entre 1 y 65535.';
    }

    if (empty($errors)) {
        $params = [
            'network_name' => $net['network_name'],
            'host' => $net['host'],
            'ip_address' => $net['ip_address'] ?: null,
            'port' => $net['port'],
            'use_ssl' => $net['use_ssl'],
            'status' => $net['status'],
            'banned_reason' => $net['status'] === 'no_operativo' ? ($net['banned_reason'] ?: null) : null,
            'sort_order' => $net['sort_order'],
            'is_active' => $net['is_active'],
        ];

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE bnc_networks SET network_name = :network_name, host = :host, ip_address = :ip_address,
                 port = :port, use_ssl = :use_ssl, status = :status, banned_reason = :banned_reason,
                 sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $params['id'] = $id;
            $stmt->execute($params);
            flash_set('Red actualizada.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO bnc_networks (network_name, host, ip_address, port, use_ssl, status, banned_reason, sort_order, is_active)
                 VALUES (:network_name, :host, :ip_address, :port, :use_ssl, :status, :banned_reason, :sort_order, :is_active)'
            );
            $stmt->execute($params);
            flash_set('Red creada.');
        }
        header('Location: bnc_networks.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar red' : 'Nueva red';
$activeAdminNav = 'bnc_networks';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="bnc_networks.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="bnc_networks_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="network_name">Nombre de la red</label>
      <input type="text" id="network_name" name="network_name" value="<?= h($net['network_name']) ?>" required maxlength="80">
    </div>

    <div class="form-group">
      <label for="host">Host</label>
      <input type="text" id="host" name="host" value="<?= h($net['host']) ?>" required maxlength="120">
    </div>

    <div class="form-group">
      <label for="ip_address">IP (opcional)</label>
      <input type="text" id="ip_address" name="ip_address" value="<?= h($net['ip_address']) ?>" maxlength="45">
    </div>

    <div class="form-group">
      <label for="port">Puerto</label>
      <input type="number" id="port" name="port" value="<?= (int) $net['port'] ?>" min="1" max="65535" required>
    </div>

    <label class="form-check">
      <input type="checkbox" name="use_ssl" <?= $net['use_ssl'] ? 'checked' : '' ?>>
      Usa SSL/TLS
    </label>

    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <option value="operativo" <?= $net['status'] === 'operativo' ? 'selected' : '' ?>>Operativo</option>
        <option value="no_operativo" <?= $net['status'] === 'no_operativo' ? 'selected' : '' ?>>No operativo</option>
      </select>
    </div>

    <div class="form-group">
      <label for="banned_reason">Motivo (si no está operativo, ej. prohibido por la red)</label>
      <textarea id="banned_reason" name="banned_reason"><?= h($net['banned_reason']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $net['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $net['is_active'] ? 'checked' : '' ?>>
      Visible en la página de Natasha
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear red' ?></button>
      <a class="btn btn-ghost" href="bnc_networks.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
