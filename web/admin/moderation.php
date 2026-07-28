<?php
declare(strict_types=1);
$pageTitle = 'Moderación';
$activeAdminNav = 'moderation';
require __DIR__ . '/includes/admin_header.php';

$pendingBlog = db()->query('SELECT id, title, author_nick, created_at FROM blog_posts WHERE status = "pendiente" ORDER BY created_at')->fetchAll();
$pendingTopics = db()->query('SELECT id, title, author_nick, created_at FROM forum_topics WHERE status = "pendiente" ORDER BY created_at')->fetchAll();
$pendingReplies = db()->query(
    'SELECT fr.id, fr.author_nick, fr.created_at, ft.title AS topic_title, ft.id AS topic_id
     FROM forum_replies fr JOIN forum_topics ft ON ft.id = fr.topic_id
     WHERE fr.status = "pendiente" ORDER BY fr.created_at'
)->fetchAll();
$pendingProfiles = db()->query('SELECT id, nick, created_at FROM user_profiles WHERE status = "pendiente" ORDER BY created_at')->fetchAll();
$pendingStories = db()->query('SELECT id, title, author_nick, created_at FROM community_stories WHERE status = "pendiente" ORDER BY created_at')->fetchAll();

$totalPending = count($pendingBlog) + count($pendingTopics) + count($pendingReplies) + count($pendingProfiles) + count($pendingStories);
?>

<div class="admin-topbar">
  <h1>Moderación</h1>
</div>

<?php if ($totalPending === 0): ?>
  <div class="admin-card"><p class="empty-note">No hay nada pendiente de moderar. 🎉</p></div>
<?php endif; ?>

<?php if (!empty($pendingBlog)): ?>
  <div class="admin-card">
    <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Blog comunitario (<?= count($pendingBlog) ?>)</h2>
    <?php foreach ($pendingBlog as $p): ?>
      <p><a href="blog.php"><?= h($p['title']) ?></a> — <?= h($p['author_nick']) ?> · <?= h(date('d/m/Y', strtotime($p['created_at']))) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($pendingTopics)): ?>
  <div class="admin-card">
    <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Temas del foro (<?= count($pendingTopics) ?>)</h2>
    <?php foreach ($pendingTopics as $t): ?>
      <p><a href="forum_topic_view.php?id=<?= (int) $t['id'] ?>"><?= h($t['title']) ?></a> — <?= h($t['author_nick']) ?> · <?= h(date('d/m/Y', strtotime($t['created_at']))) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($pendingReplies)): ?>
  <div class="admin-card">
    <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Respuestas del foro (<?= count($pendingReplies) ?>)</h2>
    <?php foreach ($pendingReplies as $r): ?>
      <p>Respuesta de <?= h($r['author_nick']) ?> en <a href="forum_topic_view.php?id=<?= (int) $r['topic_id'] ?>"><?= h($r['topic_title']) ?></a> · <?= h(date('d/m/Y', strtotime($r['created_at']))) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($pendingProfiles)): ?>
  <div class="admin-card">
    <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Perfiles (<?= count($pendingProfiles) ?>)</h2>
    <?php foreach ($pendingProfiles as $p): ?>
      <p><a href="profiles.php">#<?= h($p['nick']) ?></a> · <?= h(date('d/m/Y', strtotime($p['created_at']))) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($pendingStories)): ?>
  <div class="admin-card">
    <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Historias (<?= count($pendingStories) ?>)</h2>
    <?php foreach ($pendingStories as $s): ?>
      <p><a href="stories.php"><?= h($s['title']) ?></a> — <?= h($s['author_nick']) ?> · <?= h(date('d/m/Y', strtotime($s['created_at']))) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
