<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: blog.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $post = $stmt->fetch();

    if ($post && $post['status'] !== $status) {
        if ($status === 'aprobado') {
            $stmt = db()->prepare('UPDATE blog_posts SET status = :status, published_at = COALESCE(published_at, NOW()) WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            $body = "Hola {$post['author_nick']},\n\n"
                . "Tu artículo \"{$post['title']}\" fue publicado en el blog comunitario:\n"
                . "https://chateanos.com/blog-post.php?slug={$post['slug']}\n\n"
                . "Gracias por sumar contenido.\n\nSaludos,\n" . setting('site_name');
            send_mail($post['author_email'], 'Tu artículo fue publicado — ' . setting('site_name'), $body);
        } else {
            $stmt = db()->prepare('UPDATE blog_posts SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            if ($status === 'rechazado') {
                $body = "Hola {$post['author_nick']},\n\n"
                    . "Tu artículo \"{$post['title']}\" no fue aprobado para el blog comunitario en esta oportunidad.\n"
                    . "Si tenés dudas, escribinos a " . setting('staff_email') . ".\n\nSaludos,\n" . setting('site_name');
                send_mail($post['author_email'], 'Sobre tu artículo — ' . setting('site_name'), $body);
            }
        }
        audit_log('Blog comunitario actualizado', $post['title'] . ' -> ' . $status);
        flash_set('Artículo actualizado.');
    }
}

header('Location: blog.php');
exit;
