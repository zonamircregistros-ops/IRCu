<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['superadmin']);

$fieldGroups = [
    'Sitio' => [
        'site_name'       => 'Nombre del sitio',
        'tagline'         => 'Frase / tagline',
        'staff_email'     => 'Email de contacto del staff',
    ],
    'IRC' => [
        'webchat_url'     => 'URL del webchat',
        'irc_server'      => 'Servidor IRC',
        'irc_port_tls'    => 'Puerto TLS',
        'irc_port_plain'  => 'Puerto sin TLS',
        'general_channel' => 'Canal general',
    ],
    'Radio' => [
        'radio_stream_url'   => 'URL del stream de radio',
        'radio_station_name' => 'Nombre de la radio',
    ],
    'Natasha Bouncer' => [
        'bnc_connect_host'            => 'Host de conexión del bouncer',
        'bnc_connect_port'            => 'Puerto de conexión del bouncer',
        'natasha_mail_from'           => 'Email remitente de las notificaciones',
        'bnc_free_limit'              => 'Límite de redes en plan Free (total)',
        'bnc_free_own_choice'         => 'Redes a elección en plan Free (además de Chateanos)',
        'bnc_premium_price'           => 'Precio Premium (mensual)',
        'bnc_premium_extra_ip_price'  => 'Precio extra por IP privada',
        'bnc_service_status'          => 'Estado del servicio',
    ],
    'Donaciones' => [
        'donation_cafecito_url'   => 'URL de Cafecito',
        'donation_paypal_url'     => 'URL de PayPal',
        'donation_crypto_network' => 'Red cripto (ej: Bitcoin (BTC))',
        'donation_crypto_address' => 'Dirección de wallet',
        'donation_goal_amount'    => 'Meta mensual (USD, 0 para ocultar la barra)',
        'donation_goal_raised'    => 'Recaudado este mes (USD, actualización manual)',
    ],
    'Otros' => [
        'encuestas_url'       => 'URL de encuestas (LimeSurvey externo)',
        'blocked_domains'     => 'Dominios bloqueados en formularios (separados por coma)',
        'maintenance_mode'    => 'Modo mantenimiento',
        'maintenance_message' => 'Mensaje del banner de mantenimiento',
    ],
];

$fieldLabels = array_merge(...array_values($fieldGroups));

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
    if ($values['donation_goal_amount'] !== '' && !is_numeric($values['donation_goal_amount'])) {
        $errors[] = 'La meta de donación tiene que ser un número.';
    }
    if ($values['donation_goal_raised'] !== '' && !is_numeric($values['donation_goal_raised'])) {
        $errors[] = 'Lo recaudado tiene que ser un número.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute(['k' => $key, 'v' => $value]);
        }
        audit_log('Ajustes del sitio actualizados');
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

<form method="post" action="settings.php">
  <?= csrf_field() ?>
  <?php foreach ($fieldGroups as $groupLabel => $fields): ?>
    <div class="admin-card">
      <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;"><?= h($groupLabel) ?></h2>
      <div class="admin-form">
        <?php foreach ($fields as $key => $label): ?>
          <div class="form-group">
            <label for="<?= h($key) ?>"><?= h($label) ?></label>
            <?php if ($key === 'bnc_service_status'): ?>
              <select id="<?= h($key) ?>" name="<?= h($key) ?>">
                <option value="operativo" <?= ($current[$key] ?? '') === 'operativo' ? 'selected' : '' ?>>Operativo</option>
                <option value="no_operativo" <?= ($current[$key] ?? '') === 'no_operativo' ? 'selected' : '' ?>>No operativo</option>
              </select>
            <?php elseif ($key === 'maintenance_mode'): ?>
              <select id="<?= h($key) ?>" name="<?= h($key) ?>">
                <option value="0" <?= ($current[$key] ?? '0') === '0' ? 'selected' : '' ?>>Apagado</option>
                <option value="1" <?= ($current[$key] ?? '0') === '1' ? 'selected' : '' ?>>Encendido (muestra el banner en todo el sitio)</option>
              </select>
            <?php elseif ($key === 'maintenance_message'): ?>
              <textarea id="<?= h($key) ?>" name="<?= h($key) ?>"><?= h($current[$key] ?? '') ?></textarea>
            <?php else: ?>
              <input type="text" id="<?= h($key) ?>" name="<?= h($key) ?>" value="<?= h($current[$key] ?? '') ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Guardar ajustes</button>
  </div>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
