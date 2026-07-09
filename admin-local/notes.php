<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
admin_require_login();

if (is_post()) {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? 'save');
    $name = trim((string)($_POST['name'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($name === '' || text_length($name) > 60 || $message === '' || text_length($message) > 300) {
        flash('error', 'Ad ve not metni gerekli. Not en fazla 300 karakter olabilir.');
        redirect('notes.php?status=' . rawurlencode((string)($_GET['status'] ?? 'pending')));
    }

    $status = match ($action) {
        'publish' => 'published',
        'reject' => 'rejected',
        'trash' => 'trash',
        'restore' => 'pending',
        default => (string)($_POST['current_status'] ?? 'pending'),
    };

    $st = db()->prepare("UPDATE notes SET name=?, message=?, status=?, published_at=CASE WHEN ?='published' AND published_at IS NULL THEN CURRENT_TIMESTAMP ELSE published_at END WHERE id=?");
    $st->execute([$name, $message, $status, $status, $id]);
    flash('success', $action === 'save' ? 'Not düzenlendi.' : 'Not durumu güncellendi.');
    redirect('notes.php?status=' . rawurlencode((string)($_GET['status'] ?? 'pending')));
}

$status = (string)($_GET['status'] ?? 'pending');
$st = db()->prepare('SELECT * FROM notes WHERE status=? ORDER BY created_at DESC');
$st->execute([$status]);
$notes = $st->fetchAll();
admin_head('Kenar notları');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">MODERASYON</p>
        <h1>Kenar notları</h1>
        <p>Public siteden gelen notlar önce Bekleyen kuyruğuna düşer. Buradan düzenleyip yayımlayabilir, reddedebilir veya çöpe taşıyabilirsin.</p>
    </div>
</div>
<p class="help">Spam için notlar doğrudan yayınlanmaz; formda gizli alan, karakter sınırı ve kısa süre limiti vardır. Yine de şüpheli notları yayımlamadan silebilirsin.</p>
<div class="tabs">
    <?php foreach (['pending' => 'Bekleyen', 'published' => 'Yayımlanan', 'rejected' => 'Reddedilen', 'trash' => 'Çöp'] as $k => $v): ?>
        <a class="<?= $status === $k ? 'active' : '' ?>" href="?status=<?= e($k) ?>"><?= e($v) ?></a>
    <?php endforeach; ?>
</div>
<div class="list">
    <?php foreach ($notes as $n): ?>
        <article class="panel">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <input type="hidden" name="current_status" value="<?= e((string)$n['status']) ?>">
                <div class="form-grid">
                    <div class="field">
                        <label>Ad</label>
                        <input name="name" value="<?= e($n['name']) ?>" maxlength="60" required>
                    </div>
                    <div class="field">
                        <label>Tarih</label>
                        <input value="<?= e($n['created_at']) ?>" readonly>
                    </div>
                    <div class="field full">
                        <label>Not</label>
                        <textarea name="message" maxlength="300" required><?= e($n['message']) ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="secondary" name="action" value="save">Düzenlemeyi kaydet</button>
                    <?php if ($status !== 'published'): ?><button name="action" value="publish">Yayımla</button><?php endif; ?>
                    <?php if ($status !== 'rejected'): ?><button class="secondary" name="action" value="reject">Reddet</button><?php endif; ?>
                    <?php if ($status === 'trash'): ?><button name="action" value="restore">Bekleyene al</button><?php endif; ?>
                    <?php if ($status !== 'trash'): ?><button class="danger" name="action" value="trash">Çöpe taşı</button><?php endif; ?>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$notes): ?><div class="empty">Bu bölümde not yok.</div><?php endif; ?>
<?php admin_foot(); ?>
