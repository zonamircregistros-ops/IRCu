<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$lang = ($_GET['lang'] ?? $_POST['lang'] ?? 'es') === 'en' ? 'en' : 'es';

$t = $lang === 'en' ? [
    'title' => 'Request Natasha Bouncer',
    'intro' => 'Fill out the form and staff will reach out to activate your account.',
    'back' => '← Back to Natasha Bouncer',
    'nick' => 'IRC nick',
    'contact' => 'Contact email',
    'plan' => 'Plan',
    'plan_free' => 'Free (up to 5 networks)',
    'plan_premium' => 'Premium ($' . setting('bnc_premium_price') . '/month, unlimited)',
    'datacenter' => 'Preferred datacenter',
    'dc_any' => 'No preference',
    'dc_au' => 'Oceania — Sydney, Australia',
    'dc_eu' => 'Europe — Vilnius, Lithuania',
    'dc_sa' => 'South America — Buenos Aires, Argentina',
    'networks' => 'Networks you want besides Chateanos',
    'networks_hint' => 'Free plan: up to 4 more networks. Premium: unlimited.',
    'notes' => 'Anything else we should know?',
    'submit' => 'Send request',
    'success_title' => 'Request received',
    'success_body' => 'Thanks! Staff will get in touch using the contact info you provided to finish setting up your Natasha account.',
    'success_back' => 'Back to Natasha Bouncer',
    'error_required' => 'Nick and contact email are required.',
    'error_email' => 'Enter a valid email address.',
    'error_rate_limit' => 'Too many requests from your connection. Try again in a while.',
] : [
    'title' => 'Solicitar Natasha Bouncer',
    'intro' => 'Completá el formulario y el staff te va a contactar para activar tu cuenta.',
    'back' => '← Volver a Natasha Bouncer',
    'nick' => 'Nick de IRC',
    'contact' => 'Email de contacto',
    'plan' => 'Plan',
    'plan_free' => 'Free (hasta 5 redes)',
    'plan_premium' => 'Premium ($' . setting('bnc_premium_price') . '/mes, ilimitado)',
    'datacenter' => 'Datacenter preferido',
    'dc_any' => 'Sin preferencia',
    'dc_au' => 'Oceanía — Sídney, Australia',
    'dc_eu' => 'Europa — Vilna, Lituania',
    'dc_sa' => 'América del Sur — Buenos Aires, Argentina',
    'networks' => 'Redes que querés además de Chateanos',
    'networks_hint' => 'Plan Free: hasta 4 redes más. Premium: ilimitadas.',
    'notes' => '¿Algo más que debamos saber?',
    'submit' => 'Enviar solicitud',
    'success_title' => 'Solicitud recibida',
    'success_body' => '¡Gracias! El staff se va a poner en contacto usando el dato que dejaste para terminar de activar tu cuenta de Natasha.',
    'success_back' => 'Volver a Natasha Bouncer',
    'error_required' => 'El nick y el email de contacto son obligatorios.',
    'error_email' => 'Ingresá un email válido.',
    'error_rate_limit' => 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.',
];

$backHref = '/natasha/' . ($lang === 'en' ? 'en.php' : 'index.php');

$errors = [];
$success = false;

$form = [
    'nick' => '',
    'contact' => '',
    'plan' => in_array($_GET['plan'] ?? '', ['free', 'premium'], true) ? $_GET['plan'] : 'free',
    'datacenter' => '',
    'networks_wanted' => '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nick'] = trim((string) ($_POST['nick'] ?? ''));
    $form['contact'] = trim((string) ($_POST['contact'] ?? ''));
    $form['plan'] = in_array($_POST['plan'] ?? '', ['free', 'premium'], true) ? $_POST['plan'] : 'free';
    $form['datacenter'] = trim((string) ($_POST['datacenter'] ?? ''));
    $form['networks_wanted'] = trim((string) ($_POST['networks_wanted'] ?? ''));
    $form['notes'] = trim((string) ($_POST['notes'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        // Bot detectado por el honeypot: simulamos éxito sin guardar nada.
        $success = true;
    } elseif (!rate_limit_check('bnc_solicitar', 5, 3600)) {
        $errors[] = $t['error_rate_limit'];
    } else {
        if ($form['nick'] === '' || $form['contact'] === '') {
            $errors[] = $t['error_required'];
        } elseif (!filter_var($form['contact'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $t['error_email'];
        }

        if (empty($errors)) {
            $verifyToken = random_token();
            $stmt = db()->prepare(
                'INSERT INTO bnc_requests (nick, contact, plan, datacenter, networks_wanted, notes, email_verify_token)
                 VALUES (:nick, :contact, :plan, :datacenter, :networks_wanted, :notes, :token)'
            );
            $stmt->execute([
                'nick' => $form['nick'],
                'contact' => $form['contact'],
                'plan' => $form['plan'],
                'datacenter' => $form['datacenter'] ?: null,
                'networks_wanted' => $form['networks_wanted'] ?: null,
                'notes' => $form['notes'] ?: null,
                'token' => $verifyToken,
            ]);

            $verifyUrl = 'https://chateanos.com/verificar.php?type=bnc&token=' . $verifyToken;
            $confirmBody = "Hola {$form['nick']},\n\n"
                . "Recibimos tu solicitud de Natasha Bouncer. Confirmá tu email haciendo clic acá:\n"
                . "{$verifyUrl}\n\n"
                . "El staff la va a revisar y te va a contactar por este mismo email.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['contact'], 'Confirmá tu solicitud de Natasha Bouncer', $confirmBody, setting('natasha_mail_from'), 'Natasha Bouncer');

            $success = true;
        }
    }
}

$pageTitle = $t['title'];
$activeNav = 'servicios';
$pageDescription = $t['intro'];
$extraStyles = ['/css/admin.css'];

require __DIR__ . '/../includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="<?= h($backHref) ?>"><?= h($t['back']) ?></a>
    <h1><?= h($t['title']) ?></h1>
    <p><?= h($t['intro']) ?></p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);"><?= h($t['success_title']) ?></h2>
        <p><?= h($t['success_body']) ?></p>
        <a class="btn btn-ghost" href="<?= h($backHref) ?>"><?= h($t['success_back']) ?></a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/natasha/solicitar.php">
          <input type="hidden" name="lang" value="<?= h($lang) ?>">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="nick"><?= h($t['nick']) ?></label>
            <input type="text" id="nick" name="nick" value="<?= h($form['nick']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="contact"><?= h($t['contact']) ?></label>
            <input type="email" id="contact" name="contact" value="<?= h($form['contact']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="plan"><?= h($t['plan']) ?></label>
            <select id="plan" name="plan">
              <option value="free" <?= $form['plan'] === 'free' ? 'selected' : '' ?>><?= h($t['plan_free']) ?></option>
              <option value="premium" <?= $form['plan'] === 'premium' ? 'selected' : '' ?>><?= h($t['plan_premium']) ?></option>
            </select>
          </div>

          <div class="form-group">
            <label for="datacenter"><?= h($t['datacenter']) ?></label>
            <select id="datacenter" name="datacenter">
              <option value="" <?= $form['datacenter'] === '' ? 'selected' : '' ?>><?= h($t['dc_any']) ?></option>
              <option value="sydney" <?= $form['datacenter'] === 'sydney' ? 'selected' : '' ?>><?= h($t['dc_au']) ?></option>
              <option value="vilnius" <?= $form['datacenter'] === 'vilnius' ? 'selected' : '' ?>><?= h($t['dc_eu']) ?></option>
              <option value="buenos_aires" <?= $form['datacenter'] === 'buenos_aires' ? 'selected' : '' ?>><?= h($t['dc_sa']) ?></option>
            </select>
          </div>

          <div class="form-group">
            <label for="networks_wanted"><?= h($t['networks']) ?></label>
            <input type="text" id="networks_wanted" name="networks_wanted" value="<?= h($form['networks_wanted']) ?>" maxlength="255" placeholder="<?= h($t['networks_hint']) ?>">
          </div>

          <div class="form-group">
            <label for="notes"><?= h($t['notes']) ?></label>
            <textarea id="notes" name="notes" maxlength="500"><?= h($form['notes']) ?></textarea>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= h($t['submit']) ?></button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
