<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$fieldLabels = [
    'site_name'       => 'Nombre del sitio',
    'tagline'         => 'Frase / tagline',
    'webchat_url'     => 'URL del webchat',
    'irc_server'      => 'Servidor IRC',
    'irc_port_tls'    => 'Puerto TLS',
    'irc_port_plain'  => 'Puerto sin TLS',
    'general_channel' => 'Canal general',
    'staff_email'     => 'Email de contacto del staff',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $values = [];
    foreach (array_keys($fieldLabels) as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($values['site_name'] === '') {
        $errors[] = 'El nombre del sitio no puede quedar vacío.';
    }
    if ($values['webchat_url'] !== '' && !filter_var($values['webchat_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL del webchat no es válida.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute(['k' => $key, 'v' => $value]);
        }
        flash_set('Ajustes guardados.');
        header('Location: settings.php');
        exit;
    }
}

$current = get_settings();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $current = array_merge($current, $values);
}

$pageTitle = 'Ajustes del sitio';
$activeAdminNav = 'settings';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Ajustes del sitio</h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="settings.php">
    <?= csrf_field() ?>
    <?php foreach ($fieldLabels as $key => $label): ?>
      <div class="form-group">
        <label for="<?= h($key) ?>"><?= h($label) ?></label>
        <input type="text" id="<?= h($key) ?>" name="<?= h($key) ?>" value="<?= h($current[$key] ?? '') ?>">
      </div>
    <?php endforeach; ?>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar ajustes</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
