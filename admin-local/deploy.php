<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

const DEPLOY_REMOTE_MANIFEST_URL = 'https://www.acetin.com.tr/deploy-manifest.json';
const DEPLOY_REMOTE_MANIFEST_FALLBACK_URL = 'https://www.acetin.com.tr/public/deploy-manifest.json';
const DEPLOY_LIVE_DB_TARGET = '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite';

function deploy_rel(string $path): string
{
    $root = rtrim(str_replace('\\', '/', FV7_ROOT), '/') . '/';
    $path = str_replace('\\', '/', $path);
    return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
}

function deploy_file_record(string $path): ?array
{
    if (!is_file($path)) return null;
    return [
        'path' => deploy_rel($path),
        'size' => filesize($path) ?: 0,
        'mtime' => filemtime($path) ?: 0,
        'sha256' => hash_file('sha256', $path) ?: '',
    ];
}

function deploy_collect_tree(string $dir, array $skipDirs=[]): array
{
    if (!is_dir($dir)) return [];
    $records = [];
    $base = str_replace('\\', '/', $dir);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        foreach ($skipDirs as $skip) {
            if (str_starts_with($path, $base . '/' . trim($skip, '/'))) continue 2;
        }
        if ($file->isFile()) {
            $record = deploy_file_record($path);
            if ($record) $records[] = $record;
        }
    }
    usort($records, fn($a, $b) => $a['path'] <=> $b['path']);
    return $records;
}

function deploy_collect_code_files(): array
{
    $records = [];
    foreach ([FV7_ROOT . '/app', FV7_ROOT . '/public'] as $dir) {
        $records = array_merge($records, deploy_collect_tree($dir, ['uploads']));
    }
    foreach ([
        FV7_ROOT . '/config/config.php',
        FV7_ROOT . '/.htaccess',
        FV7_ROOT . '/index.php',
        FV7_ROOT . '/robots.txt',
        FV7_ROOT . '/sitemap.xml',
        FV7_ROOT . '/VERSION.txt',
        FV7_ROOT . '/DEPLOY_CHECKLIST.md',
    ] as $path) {
        $record = deploy_file_record($path);
        if ($record) $records[] = $record;
    }
    $records = array_values(array_filter($records, fn($r) => $r['path'] !== 'public/deploy-manifest.json'));
    usort($records, fn($a, $b) => $a['path'] <=> $b['path']);
    return $records;
}

function deploy_collect_upload_files(): array
{
    return array_values(array_filter(
        deploy_collect_tree(FV7_UPLOAD_ROOT),
        fn($r) => $r['path'] !== 'public/uploads/.htaccess'
    ));
}

function deploy_git_commit(): string
{
    $cmd = 'git -C ' . escapeshellarg(FV7_ROOT) . ' rev-parse --short HEAD 2>NUL';
    $out = trim((string)@shell_exec($cmd));
    return preg_match('/^[a-f0-9]{7,}$/', $out) ? $out : '';
}

function deploy_manifest(): array
{
    $codeFiles = deploy_collect_code_files();
    $uploadFiles = deploy_collect_upload_files();
    $db = is_file(FV7_DB) ? [
        'size' => filesize(FV7_DB) ?: 0,
        'mtime' => filemtime(FV7_DB) ?: 0,
        'sha256' => hash_file('sha256', FV7_DB) ?: '',
    ] : ['size' => 0, 'mtime' => 0, 'sha256' => ''];

    $manifest = [
        'site' => 'fikrimvar',
        'schema' => 1,
        'generated_at' => date('c'),
        'version' => is_file(FV7_ROOT . '/VERSION.txt') ? trim((string)file_get_contents(FV7_ROOT . '/VERSION.txt')) : '',
        'code_commit' => deploy_git_commit(),
        'db' => $db,
        'code_files' => $codeFiles,
        'uploads' => $uploadFiles,
    ];
    $manifest['local_hash'] = hash('sha256', json_encode([
        'db' => $db['sha256'],
        'code' => array_column($codeFiles, 'sha256', 'path'),
        'uploads' => array_column($uploadFiles, 'sha256', 'path'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $manifest;
}

function deploy_public_manifest(array $manifest): array
{
    return [
        'site' => $manifest['site'],
        'schema' => $manifest['schema'],
        'generated_at' => $manifest['generated_at'],
        'version' => $manifest['version'],
        'code_commit' => $manifest['code_commit'],
        'local_hash' => $manifest['local_hash'],
        'db' => $manifest['db'],
        'code_files' => $manifest['code_files'],
        'uploads' => $manifest['uploads'],
    ];
}

function deploy_http_status(array $headers): string
{
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string)$header, $m)) return (string)$m[1];
    }
    return '';
}

function deploy_fetch_remote_manifest(): array
{
    $context = stream_context_create([
        'http' => ['timeout' => 4, 'ignore_errors' => true],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $attempts = [];
    foreach ([DEPLOY_REMOTE_MANIFEST_URL, DEPLOY_REMOTE_MANIFEST_FALLBACK_URL] as $url) {
        $raw = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        $status = deploy_http_status($headers);
        $preview = is_string($raw) ? trim(substr($raw, 0, 160)) : '';
        $attempts[] = ['url' => $url, 'status' => $status, 'preview' => $preview];
        if (!is_string($raw) || trim($raw) === '') continue;
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ($decoded['site'] ?? '') === 'fikrimvar') {
            return ['manifest' => $decoded, 'error' => '', 'url' => $url, 'attempts' => $attempts];
        }
    }
    return [
        'manifest' => null,
        'error' => 'Canli kontrol dosyasi JSON olarak okunamadi.',
        'url' => '',
        'attempts' => $attempts,
    ];
}

function deploy_map(array $items): array
{
    $out = [];
    foreach ($items as $item) $out[(string)$item['path']] = $item;
    return $out;
}

function deploy_compare_files(array $local, array $remote): array
{
    $localMap = deploy_map($local);
    $remoteMap = deploy_map($remote);
    $new = $changed = $remoteOnly = [];
    foreach ($localMap as $path => $item) {
        if (!isset($remoteMap[$path])) $new[] = $item;
        elseif (($remoteMap[$path]['sha256'] ?? '') !== ($item['sha256'] ?? '')) $changed[] = $item;
    }
    foreach ($remoteMap as $path => $item) {
        if (!isset($localMap[$path])) $remoteOnly[] = $item;
    }
    return ['new' => $new, 'changed' => $changed, 'remote_only' => $remoteOnly];
}

function deploy_size(int|float $bytes): string
{
    if ($bytes >= 1024 * 1024) return number_format($bytes / 1024 / 1024, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function deploy_render_file_list(array $files, int $limit=12): void
{
    if (!$files) {
        echo '<p class="help">Liste bos.</p>';
        return;
    }
    echo '<div class="list">';
    foreach (array_slice($files, 0, $limit) as $file) {
        echo '<div class="list-row"><span>-</span><strong>' . e((string)$file['path']) . '</strong><small>' . e(deploy_size((int)($file['size'] ?? 0))) . '</small></div>';
    }
    if (count($files) > $limit) {
        echo '<div class="list-row"><span>...</span><strong>' . (count($files) - $limit) . ' dosya daha</strong><small>Liste kisaltildi</small></div>';
    }
    echo '</div>';
}

function deploy_log_file(): string
{
    return FV7_STORAGE . '/deploy-log.jsonl';
}

function deploy_read_logs(int $limit=8): array
{
    $path = deploy_log_file();
    if (!is_file($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return [];
    $logs = [];
    foreach (array_reverse($lines) as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) $logs[] = $row;
        if (count($logs) >= $limit) break;
    }
    return $logs;
}

function deploy_append_log(string $status, array $localManifest, ?array $remoteManifest, string $note): void
{
    $dir = FV7_STORAGE;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $row = [
        'status' => $status,
        'created_at' => now_sql(),
        'local_hash' => (string)$localManifest['local_hash'],
        'remote_hash' => (string)($remoteManifest['local_hash'] ?? ''),
        'note' => $note,
    ];
    file_put_contents(deploy_log_file(), json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$localManifest = deploy_manifest();
$remoteResult = deploy_fetch_remote_manifest();
$remoteManifest = is_array($remoteResult['manifest']) ? $remoteResult['manifest'] : null;
$remoteError = (string)$remoteResult['error'];
$remoteUrl = (string)($remoteResult['url'] ?? '');
$remoteAttempts = is_array($remoteResult['attempts'] ?? null) ? $remoteResult['attempts'] : [];

if (is_post()) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $note = trim((string)($_POST['note'] ?? ''));
    try {
        if ($action === 'write_manifest' || $action === 'mark_deployed') {
            $publicManifest = deploy_public_manifest($localManifest);
            $json = json_encode($publicManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            file_put_contents(FV7_PUBLIC . '/deploy-manifest.json', $json . PHP_EOL);
            $dir = FV7_STORAGE . '/deploy-manifests';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            file_put_contents($dir . '/deploy-' . date('Ymd-His') . '.json', $json . PHP_EOL);

            $status = $action === 'mark_deployed' ? 'marked_deployed' : 'prepared';
            deploy_append_log($status, $localManifest, $remoteManifest, $note);
            flash('success', $status === 'marked_deployed' ? 'Gonderildi olarak isaretlendi ve kontrol dosyasi yenilendi.' : 'Yayin paketi hazirlandi; kontrol dosyasi yenilendi.');
            redirect('deploy.php');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('deploy.php');
    }
}

$codeDiff = $remoteManifest ? deploy_compare_files($localManifest['code_files'], $remoteManifest['code_files'] ?? []) : null;
$uploadDiff = $remoteManifest ? deploy_compare_files($localManifest['uploads'], $remoteManifest['uploads'] ?? []) : null;
$dbDifferent = $remoteManifest ? (($localManifest['db']['sha256'] ?? '') !== ($remoteManifest['db']['sha256'] ?? '')) : null;
$dbDirection = '';
if ($remoteManifest && $dbDifferent) {
    $localMtime = (int)($localManifest['db']['mtime'] ?? 0);
    $remoteMtime = (int)($remoteManifest['db']['mtime'] ?? 0);
    $dbDirection = $localMtime > $remoteMtime ? 'Local DB daha yeni gorunuyor.' : ($localMtime < $remoteMtime ? 'Canli DB daha yeni gorunuyor.' : 'DB hash farkli, tarih ayni gorunuyor.');
}
$sameAsLive = $remoteManifest && (($localManifest['local_hash'] ?? '') === ($remoteManifest['local_hash'] ?? ''));
$codeChangeCount = $codeDiff ? count($codeDiff['new']) + count($codeDiff['changed']) + count($codeDiff['remote_only']) : 0;
$uploadChangeCount = $uploadDiff ? count($uploadDiff['new']) + count($uploadDiff['changed']) + count($uploadDiff['remote_only']) : 0;
$deployStatusTitle = !$remoteManifest ? 'Canli kontrol dosyasi okunamiyor' : ($sameAsLive ? 'Local ve canli ayni' : 'Local ve canli farkli');
$deployStatusClass = !$remoteManifest ? 'flash-warning' : ($sameAsLive ? 'flash-success' : 'flash-error');
if (!$remoteManifest) {
    $nextStep = 'Once canlidaki kontrol dosyasinin acildigini dogrula. Bu dosya local ile canliyi karsilastirmak icin kullanilir.';
} elseif ($sameAsLive) {
    $nextStep = 'Su anda ekstra gonderim gerekmiyor.';
} elseif ($dbDifferent === true && $codeChangeCount === 0 && $uploadChangeCount === 0) {
    $nextStep = 'Sadece SQLite DB farkli gorunuyor. Yayin paketini hazirla, sonra fikrimvar.sqlite ve public/deploy-manifest.json dosyasini birlikte canliya gonder.';
} else {
    $nextStep = 'Fark listesine bak; degisen DB, medya veya kod dosyalarini canliya gonder.';
}

$logs = deploy_read_logs();

admin_head('Yayin Merkezi');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">YAYIN MERKEZI</p>
        <h1>Yayin paketi kontrolu</h1>
        <p>Localdeki son hali canli sunucuyla karsilastirir. Yayinlamadan once paketi hazirla; sonra ekranda yazan dosyalari canliya gonder.</p>
    </div>
    <a class="button secondary" href="../public/deploy-manifest.json" target="_blank">Kontrol dosyasini ac</a>
</div>

<div class="stat-grid">
    <div class="stat"><strong><?= e($sameAsLive ? 'Ayni' : ($remoteManifest ? 'Fark var' : 'Kontrol yok')) ?></strong><span>Local / canli</span></div>
    <div class="stat"><strong><?= count($localManifest['code_files']) ?></strong><span>Kod/public dosyasi</span></div>
    <div class="stat"><strong><?= count($localManifest['uploads']) ?></strong><span>Medya dosyasi</span></div>
    <div class="stat"><strong><?= e(deploy_size((int)$localManifest['db']['size'])) ?></strong><span>SQLite DB</span></div>
</div>

<section class="panel" style="margin-top:20px">
    <h2>Durum</h2>
    <p class="flash <?= e($deployStatusClass) ?>"><?= e($deployStatusTitle) ?></p>
    <div class="list">
        <div class="list-row"><span>Canli kontrol dosyasi</span><strong><?= $remoteManifest ? 'Okundu' : 'Okunamadi' ?></strong><small><?= e($remoteUrl ?: 'Calisan kontrol dosyasi adresi bulunamadi') ?></small></div>
        <div class="list-row"><span>DB</span><strong><?= $dbDifferent === null ? 'Karsilastirilamadi' : ($dbDifferent ? 'Farkli' : 'Ayni') ?></strong><small><?= e($dbDirection ?: 'Kontrol dosyasi okunursa DB hash karsilastirilir') ?></small></div>
        <div class="list-row"><span>Kod</span><strong><?= $codeDiff ? ($codeChangeCount . ' fark') : 'Karsilastirilamadi' ?></strong><small>app, public ve kok yayin dosyalari</small></div>
        <div class="list-row"><span>Medya</span><strong><?= $uploadDiff ? ($uploadChangeCount . ' fark') : 'Karsilastirilamadi' ?></strong><small>public/uploads icindeki dosyalar</small></div>
        <div class="list-row"><span>Sonraki adim</span><strong><?= e($nextStep) ?></strong><small></small></div>
    </div>
</section>

<section class="panel">
    <h2>Yayin kurallari</h2>
    <p class="help">Local ana kaynaktir. Canli DB uzerinde elle degisiklik yapilmaz. Canliya gondermeden once DB ve `public/uploads/` yedegi alinir. Admin panel canlida tutulmaz.</p>
    <div class="list">
        <div class="list-row"><span>DB local</span><strong><?= e(FV7_DB) ?></strong><small><?= e(substr((string)$localManifest['db']['sha256'], 0, 16)) ?></small></div>
        <div class="list-row"><span>DB canli hedef</span><strong><?= e(DEPLOY_LIVE_DB_TARGET) ?></strong><small>Bu dosya elle/SFTP ile gonderilir.</small></div>
        <div class="list-row"><span>Canli kontrol dosyasi</span><strong><?= e($remoteUrl ?: DEPLOY_REMOTE_MANIFEST_URL) ?></strong><small><?= e($remoteError ?: 'Okundu') ?></small></div>
    </div>
</section>

<?php if (!$remoteManifest && $remoteAttempts): ?>
<section class="panel" style="margin-top:20px">
    <h2>Kontrol dosyasi adresi</h2>
    <p class="help">Bu iki adres denendi. Birisi tarayicida saf JSON olarak acilmali; HTML hata sayfasi gelirse karsilastirma yapilamaz.</p>
    <div class="list">
        <?php foreach ($remoteAttempts as $attempt): ?>
            <div class="list-row">
                <span><?= e((string)($attempt['status'] ?: 'yok')) ?></span>
                <strong><?= e((string)$attempt['url']) ?></strong>
                <small><?= e((string)($attempt['preview'] ?: 'cevap yok')) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="panel" style="margin-top:20px">
    <h2>Sonuc</h2>
    <?php if (!$remoteManifest): ?>
        <p class="help">Canli kontrol dosyasi okunamadigi icin local ile sunucu ayni mi kesin soylenemiyor. Once public/deploy-manifest.json dosyasinin canlida JSON olarak acilmasini sagla.</p>
    <?php elseif ($sameAsLive): ?>
        <p class="flash flash-success">Local ve canli ayni gorunuyor.</p>
    <?php else: ?>
        <p class="flash flash-warning">Local ve canli arasinda fark var. Asagidaki listeleri kontrol et.</p>
    <?php endif; ?>
    <?php if ($dbDifferent === true): ?><p class="flash flash-error">SQLite DB farkli. <?= e($dbDirection) ?></p><?php endif; ?>
</section>

<div class="grid grid-2" style="margin-top:20px">
    <section class="panel">
        <h2>Canliya gonderilecekler</h2>
        <div class="list">
            <div class="list-row"><span>1</span><strong>SQLite DB</strong><small><?= $dbDifferent === false ? 'Ayni gorunuyor' : 'Gondermeden once yedek al' ?></small></div>
            <div class="list-row"><span>2</span><strong>public/uploads/</strong><small><?= $uploadDiff ? (count($uploadDiff['new']) . ' yeni, ' . count($uploadDiff['changed']) . ' degisen') : 'Canli kontrol dosyasi yok; tum gerekli medya kontrol edilmeli' ?></small></div>
            <div class="list-row"><span>3</span><strong>Kod/public dosyalari</strong><small><?= $codeDiff ? (count($codeDiff['new']) . ' yeni, ' . count($codeDiff['changed']) . ' degisen') : 'Git/FTP ile guncel kod gonderilmeli' ?></small></div>
            <div class="list-row"><span>4</span><strong>public/deploy-manifest.json</strong><small>Yayin paketini hazirla butonu uretir; canliya bunu da gonder.</small></div>
        </div>
    </section>
    <section class="panel">
        <h2>Yayin paketi hazirla</h2>
        <p class="help">Bu buton mevcut local durumu kayda alir ve public/deploy-manifest.json kontrol dosyasini yeniler. Sonra degisen dosyalari ve bu kontrol dosyasini canliya gonderirsin.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="field"><label>Not</label><textarea name="note" placeholder="Orn: ai-context hikaye guncellendi, 2 medya eklendi"></textarea></div>
            <div class="form-actions">
                <button class="secondary" type="submit" name="action" value="write_manifest">Yayin paketini hazirla</button>
                <button class="accent" type="submit" name="action" value="mark_deployed" data-confirm="Bu islem otomatik yukleme yapmaz. Dosyalari gercekten canliya gonderdiysen isaretle.">Dosyalari canliya gonderdim</button>
            </div>
        </form>
    </section>
</div>

<?php if ($uploadDiff): ?>
<section class="panel" style="margin-top:20px">
    <h2>Medya farklari</h2>
    <h3>Localde yeni medya</h3><?php deploy_render_file_list($uploadDiff['new']); ?>
    <h3>Localde degisen medya</h3><?php deploy_render_file_list($uploadDiff['changed']); ?>
    <h3>Canlida localde olmayan medya</h3><?php deploy_render_file_list($uploadDiff['remote_only']); ?>
</section>
<?php endif; ?>

<?php if ($codeDiff): ?>
<section class="panel" style="margin-top:20px">
    <h2>Kod/public dosya farklari</h2>
    <h3>Localde yeni dosyalar</h3><?php deploy_render_file_list($codeDiff['new']); ?>
    <h3>Localde degisen dosyalar</h3><?php deploy_render_file_list($codeDiff['changed']); ?>
    <h3>Canlida localde olmayan dosyalar</h3><?php deploy_render_file_list($codeDiff['remote_only']); ?>
</section>
<?php endif; ?>

<section class="panel" style="margin-top:20px">
    <h2>Yayin hazirlik logu</h2>
    <?php if (!$logs): ?><p class="help">Henuz deploy kaydi yok.</p><?php else: ?>
        <div class="list">
            <?php foreach ($logs as $log): ?>
                <div class="list-row">
                    <span><?= e((string)$log['status']) ?></span>
                    <strong><?= e((string)$log['created_at']) ?></strong>
                    <small><?= e((string)($log['note'] ?: $log['local_hash'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php admin_foot(); ?>
