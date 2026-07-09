<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

const DEPLOY_REMOTE_MANIFEST_URL = 'https://www.acetin.com.tr/deploy-manifest.json';
const DEPLOY_LIVE_DB_TARGET = '/var/www/vhosts/acetin.com.tr/acetinweb_private/storage/fikrimvar.sqlite';

function deploy_rel(string $path): string
{
    $path = str_replace('\\', '/', $path);
    foreach ([FV7_PUBLIC, FV7_ROOT] as $rootPath) {
        $root = rtrim(str_replace('\\', '/', $rootPath), '/') . '/';
        if (str_starts_with($path, $root)) return substr($path, strlen($root));
    }
    return $path;
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
    foreach ([FV7_ROOT . '/app', FV7_PUBLIC . '/assets', FV7_PUBLIC . '/includes'] as $dir) {
        $records = array_merge($records, deploy_collect_tree($dir, ['uploads']));
    }
    foreach ([
        FV7_ROOT . '/config/config.php',
        FV7_PUBLIC . '/.htaccess',
        FV7_PUBLIC . '/_bootstrap.php',
        FV7_PUBLIC . '/_config.php',
        FV7_PUBLIC . '/_private.php',
        FV7_PUBLIC . '/atolye.php',
        FV7_PUBLIC . '/hikaye.php',
        FV7_PUBLIC . '/hikayeler.php',
        FV7_PUBLIC . '/index.php',
        FV7_PUBLIC . '/robots.txt',
        FV7_PUBLIC . '/sitemap.xml',
        FV7_PUBLIC . '/yorum-kaydet.php',
    ] as $path) {
        $record = deploy_file_record($path);
        if ($record) $records[] = $record;
    }
    $records = array_values(array_filter($records, fn($r) => $r['path'] !== 'deploy-manifest.json'));
    usort($records, fn($a, $b) => $a['path'] <=> $b['path']);
    return $records;
}

function deploy_collect_upload_files(): array
{
    return array_values(array_filter(
        deploy_collect_tree(FV7_UPLOAD_ROOT),
        fn($r) => $r['path'] !== 'uploads/.htaccess'
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
        'version' => is_file(FV7_ROOT . '/docs/VERSION.txt') ? trim((string)file_get_contents(FV7_ROOT . '/docs/VERSION.txt')) : '',
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
    $manifest['public_assets_hash'] = hash('sha256', json_encode([
        'code' => array_column(array_filter($codeFiles, fn($r) => !str_starts_with((string)$r['path'], 'app/') && !str_starts_with((string)$r['path'], 'config/')), 'sha256', 'path'),
        'uploads' => array_column($uploadFiles, 'sha256', 'path'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $manifest['release_hash'] = hash('sha256', (string)$manifest['local_hash']);
    return $manifest;
}

function deploy_public_manifest(array $manifest): array
{
    return [
        'site' => $manifest['site'],
        'schema' => 2,
        'generated_at' => $manifest['generated_at'],
        'version' => $manifest['version'],
        'code_commit' => $manifest['code_commit'],
        'release_hash' => $manifest['release_hash'],
        'public_assets_hash' => $manifest['public_assets_hash'],
        'code_file_count' => count($manifest['code_files']),
        'upload_file_count' => count($manifest['uploads']),
    ];
}

function deploy_read_json_file(string $path): ?array
{
    if (!is_file($path)) return null;
    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

function deploy_public_manifest_status(array $expected, ?array $current): array
{
    if (!$current) {
        return ['ok' => false, 'title' => 'Yok veya bozuk', 'message' => 'Kok dizindeki deploy-manifest.json okunamiyor. Once Yayin paketini hazirla butonuna bas.'];
    }
    if (empty($current['release_hash']) || empty($current['public_assets_hash'])) {
        return ['ok' => false, 'title' => 'Eski/bos', 'message' => 'Kok dizindeki deploy-manifest.json eski veya bos hash iceriyor. Once Yayin paketini hazirla.'];
    }
    if (($current['release_hash'] ?? '') !== ($expected['release_hash'] ?? '')) {
        return ['ok' => false, 'title' => 'Guncel degil', 'message' => 'Kok dizindeki deploy-manifest.json son local durumdan eski. Once Yayin paketini hazirla, sonra bu dosyayi canliya gonder.'];
    }
    return ['ok' => true, 'title' => 'Guncel', 'message' => 'Kok dizindeki deploy-manifest.json son local durumla uyumlu. Canliya gonderilecek public kontrol dosyasi budur.'];
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
    foreach ([DEPLOY_REMOTE_MANIFEST_URL] as $url) {
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

function deploy_private_manifest_files(): array
{
    $dir = FV7_STORAGE . '/deploy-manifests';
    $files = array_merge(
        glob($dir . '/detail-deploy-*.json') ?: [],
        glob($dir . '/deploy-*.json') ?: []
    );
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return $files;
}

function deploy_manifest_hash(array $manifest): string
{
    return (string)($manifest['release_hash'] ?? $manifest['local_hash'] ?? '');
}

function deploy_find_private_manifest_by_hash(string $hash): ?array
{
    if ($hash === '') return null;
    foreach (deploy_private_manifest_files() as $file) {
        $manifest = deploy_read_json_file($file);
        if (!$manifest || !isset($manifest['code_files'], $manifest['uploads'], $manifest['db'])) continue;
        if (hash_equals(deploy_manifest_hash($manifest), $hash)) {
            $manifest['_source_file'] = $file;
            return $manifest;
        }
    }
    return null;
}

function deploy_outgoing_files(?array $diff): array
{
    if (!$diff) return [];
    return array_merge($diff['new'] ?? [], $diff['changed'] ?? []);
}

function deploy_path_is_private_code(string $path): bool
{
    return str_starts_with($path, 'app/') || str_starts_with($path, 'config/');
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
        'local_hash' => (string)($localManifest['release_hash'] ?? $localManifest['local_hash'] ?? ''),
        'remote_hash' => (string)($remoteManifest['release_hash'] ?? $remoteManifest['local_hash'] ?? ''),
        'note' => $note,
    ];
    file_put_contents(deploy_log_file(), json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$localManifest = deploy_manifest();
$expectedPublicManifest = deploy_public_manifest($localManifest);
$localPublicManifestPath = FV7_PUBLIC . '/deploy-manifest.json';
$localPublicManifest = deploy_read_json_file($localPublicManifestPath);
$localPublicManifestStatus = deploy_public_manifest_status($expectedPublicManifest, $localPublicManifest);
$privateManifestDir = FV7_STORAGE . '/deploy-manifests';
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
            $dir = FV7_STORAGE . '/deploy-manifests';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            file_put_contents(FV7_PUBLIC . '/deploy-manifest.json', $json . PHP_EOL);
            file_put_contents($dir . '/public-deploy-' . date('Ymd-His') . '.json', $json . PHP_EOL);
            file_put_contents($dir . '/detail-deploy-' . date('Ymd-His') . '.json', json_encode($localManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL);

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

$remoteReleaseHash = (string)($remoteManifest['release_hash'] ?? $remoteManifest['local_hash'] ?? '');
$baselineManifest = null;
$baselineSource = '';
if ($remoteManifest && isset($remoteManifest['code_files'], $remoteManifest['uploads'], $remoteManifest['db'])) {
    $baselineManifest = $remoteManifest;
    $baselineSource = 'Canli detay manifest';
} else {
    $baselineManifest = deploy_find_private_manifest_by_hash($remoteReleaseHash);
    $baselineSource = $baselineManifest ? 'Local private arsiv: ' . basename((string)$baselineManifest['_source_file']) : '';
}
$hasBaseline = is_array($baselineManifest) && isset($baselineManifest['code_files'], $baselineManifest['uploads'], $baselineManifest['db']);
$codeDiff = $hasBaseline ? deploy_compare_files($localManifest['code_files'], $baselineManifest['code_files'] ?? []) : null;
$uploadDiff = $hasBaseline ? deploy_compare_files($localManifest['uploads'], $baselineManifest['uploads'] ?? []) : null;
$dbDifferent = $hasBaseline ? (($localManifest['db']['sha256'] ?? '') !== ($baselineManifest['db']['sha256'] ?? '')) : null;
$dbDirection = '';
if ($remoteManifest && $dbDifferent) {
    $localMtime = (int)($localManifest['db']['mtime'] ?? 0);
    $remoteMtime = (int)($baselineManifest['db']['mtime'] ?? 0);
    $dbDirection = $localMtime > $remoteMtime ? 'Local DB daha yeni gorunuyor.' : ($localMtime < $remoteMtime ? 'Canli DB daha yeni gorunuyor.' : 'DB hash farkli, tarih ayni gorunuyor.');
}
$sameAsLive = $remoteManifest && $remoteReleaseHash !== '' && (($localManifest['release_hash'] ?? '') === $remoteReleaseHash || ($localManifest['local_hash'] ?? '') === $remoteReleaseHash);
$remotePublicAssetsHash = (string)($remoteManifest['public_assets_hash'] ?? '');
$publicAssetsDifferent = $remoteManifest && $remotePublicAssetsHash !== '' && ($remotePublicAssetsHash !== (string)$localManifest['public_assets_hash']);
$onlyDbOrManifestDifferent = $remoteManifest && !$sameAsLive && !$publicAssetsDifferent;
$codeChangeCount = $codeDiff ? count($codeDiff['new']) + count($codeDiff['changed']) + count($codeDiff['remote_only']) : 0;
$uploadChangeCount = $uploadDiff ? count($uploadDiff['new']) + count($uploadDiff['changed']) + count($uploadDiff['remote_only']) : 0;
$codeOutgoingFiles = deploy_outgoing_files($codeDiff);
$uploadOutgoingFiles = deploy_outgoing_files($uploadDiff);
$codePublicOutgoingFiles = array_values(array_filter($codeOutgoingFiles, fn($file) => !deploy_path_is_private_code((string)$file['path'])));
$codePrivateOutgoingFiles = array_values(array_filter($codeOutgoingFiles, fn($file) => deploy_path_is_private_code((string)$file['path'])));
$dbShouldSend = !$sameAsLive && $dbDifferent !== false;
$codeShouldSend = count($codeOutgoingFiles) > 0;
$uploadShouldSend = count($uploadOutgoingFiles) > 0;
$deployStatusTitle = !$remoteManifest ? 'Canli kontrol dosyasi okunamiyor' : ($sameAsLive ? 'Local ve canli ayni' : 'Local ve canli farkli');
$deployStatusClass = !$remoteManifest ? 'flash-warning' : ($sameAsLive ? 'flash-success' : 'flash-error');
if (!$remoteManifest) {
    $nextStep = 'Once canlidaki kontrol dosyasinin acildigini dogrula. Bu dosya local ile canliyi karsilastirmak icin kullanilir.';
} elseif ($sameAsLive) {
    $nextStep = 'Su anda ekstra gonderim gerekmiyor.';
} elseif ($onlyDbOrManifestDifferent) {
    $nextStep = 'Site dosyalari ve uploads ayni gorunuyor. Sadece SQLite DB ve kokteki deploy-manifest.json dosyasini canliya gonder.';
} elseif ($dbDifferent === true && $codeChangeCount === 0 && $uploadChangeCount === 0) {
    $nextStep = 'Sadece SQLite DB farkli gorunuyor. Yayin paketini hazirla, sonra fikrimvar.sqlite ve deploy-manifest.json dosyasini birlikte canliya gonder.';
} else {
    $nextStep = $hasBaseline ? 'Asagidaki dosya listesine gore ilerle.' : 'Canli hash ile eslesen local detay manifest bulunamadi; once canlidaki deploy-manifest.json dosyasinin guncel oldugunu dogrula.';
}
if (!$localPublicManifestStatus['ok']) {
    $nextStep = 'Once 1. Paketi hazirla ve manifesti yenile butonuna bas. Sonra kokteki deploy-manifest.json dosyasini canli httpdocs/ altina gonder.';
}

$logs = deploy_read_logs();

admin_head('Yayin Merkezi');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">YAYIN MERKEZI</p>
        <h1>Yayin paketi kontrolu</h1>
        <p>Localdeki son hali canli sunucuyla karsilastirir. Once yayin paketini hazirla; sonra sadece burada yazan dosyalari belirtilen canli konumlara gonder.</p>
    </div>
    <a class="button secondary" href="../deploy-manifest.json" target="_blank">Kontrol dosyasini ac</a>
</div>

<div class="stat-grid">
    <div class="stat"><strong><?= e($sameAsLive ? 'Ayni' : ($remoteManifest ? 'Fark var' : 'Kontrol yok')) ?></strong><span>Local / canli</span></div>
    <div class="stat"><strong><?= count($localManifest['code_files']) ?></strong><span>Site + app dosyasi</span></div>
    <div class="stat"><strong><?= count($localManifest['uploads']) ?></strong><span>Medya dosyasi</span></div>
    <div class="stat"><strong><?= e(deploy_size((int)$localManifest['db']['size'])) ?></strong><span>SQLite DB</span></div>
</div>

<section class="panel" style="margin-top:20px">
    <h2>Durum</h2>
    <p class="flash <?= e($deployStatusClass) ?>"><?= e($deployStatusTitle) ?></p>
    <div class="list">
        <div class="list-row"><span>Canli kontrol dosyasi</span><strong><?= $remoteManifest ? 'Okundu' : 'Okunamadi' ?></strong><small><?= e($remoteUrl ?: 'Calisan kontrol dosyasi adresi bulunamadi') ?></small></div>
        <div class="list-row"><span>Local kontrol dosyasi</span><strong><?= e($localPublicManifestStatus['title']) ?></strong><small><?= e($localPublicManifestStatus['message']) ?></small></div>
        <div class="list-row"><span>Karsilastirma temeli</span><strong><?= e($baselineSource ?: 'Bulunamadi') ?></strong><small><?= $hasBaseline ? 'Canli hash ile eslesen detay kayit bulundu.' : 'Net dosya listesi cikarmak icin eslesen detay manifest gerekli.' ?></small></div>
        <div class="list-row"><span>DB</span><strong><?= $dbDifferent === null ? 'Karsilastirilamadi' : ($dbDifferent ? 'Farkli' : 'Ayni') ?></strong><small><?= e($dbDirection ?: ($dbDifferent === false ? 'DB ayni.' : 'DB karari icin detay manifest gerekir.')) ?></small></div>
        <div class="list-row"><span>Site/uploads ozeti</span><strong><?= !$remoteManifest || $remotePublicAssetsHash === '' ? 'Canlida eski kontrol' : ($publicAssetsDifferent ? 'Farkli' : 'Ayni') ?></strong><small>Bu ozet hash site dosyalarini ve uploads icerigini public liste acmadan karsilastirir.</small></div>
        <div class="list-row"><span>Kod</span><strong><?= $codeDiff ? ($codeChangeCount . ' fark') : 'Karsilastirilamadi' ?></strong><small><?= $codeDiff ? 'Asagida degisen dosya listesi var.' : 'Kod karari icin detay manifest gerekir.' ?></small></div>
        <div class="list-row"><span>Medya</span><strong><?= $uploadDiff ? ($uploadChangeCount . ' fark') : 'Karsilastirilamadi' ?></strong><small><?= $uploadDiff ? 'Asagida degisen upload listesi var.' : 'Medya karari icin detay manifest gerekir.' ?></small></div>
        <div class="list-row"><span>Sonraki adim</span><strong><?= e($nextStep) ?></strong><small></small></div>
    </div>
</section>

<section class="panel">
    <h2>Yayin kurallari</h2>
    <p class="help">Local ana kaynaktir. Yayin ve sira ekraninda yapilan gorunurluk kararlari SQLite DB icindedir; canliya yansimasi icin fikrimvar.sqlite da gonderilmelidir. Canli DB uzerinde elle degisiklik yapilmaz. Canliya gondermeden once DB ve uploads/ yedegi alinir. admin-local canliya gonderilmez.</p>
    <div class="list">
        <div class="list-row"><span>DB local</span><strong><?= e(FV7_DB) ?></strong><small>Hash sadece admin/local ekranda gorunur; public manifestte yoktur.</small></div>
        <div class="list-row"><span>DB canli hedef</span><strong><?= e(DEPLOY_LIVE_DB_TARGET) ?></strong><small>Bu dosya elle/SFTP ile gonderilir.</small></div>
        <div class="list-row"><span>Gonderilecek manifest</span><strong><?= e($localPublicManifestPath) ?></strong><small>Kok dizindeki dosya. Private storage altindaki detayli manifest canliya gonderilmez.</small></div>
        <div class="list-row"><span>Private detay manifestleri</span><strong><?= e($privateManifestDir) ?></strong><small>Local arsiv/log icindir; canliya gonderme.</small></div>
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
        <p class="help">Canli kontrol dosyasi okunamadigi icin local ile sunucu ayni mi kesin soylenemiyor. Once deploy-manifest.json dosyasinin canlida sade JSON olarak acilmasini sagla.</p>
    <?php elseif ($sameAsLive): ?>
        <p class="flash flash-success">Local ve canli ayni gorunuyor.</p>
    <?php else: ?>
        <p class="flash flash-warning">Local ve canli arasinda fark var. Asagidaki listeleri kontrol et.</p>
    <?php endif; ?>
    <?php if ($dbDifferent === true): ?><p class="flash flash-error">SQLite DB farkli. <?= e($dbDirection) ?></p><?php endif; ?>
</section>

<div class="grid grid-2" style="margin-top:20px">
    <section class="panel">
        <h2>Canliya gonderilecek dosyalar</h2>
        <div class="list">
            <?php if ($dbShouldSend): ?><div class="list-row"><span>DB</span><strong>SQLite veritabani</strong><small>Kaynak: <?= e(FV7_DB) ?>. Hedef: <?= e(DEPLOY_LIVE_DB_TARGET) ?>. Once canli DB yedegi al.</small></div><?php endif; ?>
            <?php if ($uploadShouldSend): ?><div class="list-row"><span>MEDYA</span><strong>Degisen upload dosyalari</strong><small>Kaynak: <?= e(FV7_UPLOAD_ROOT) ?>. Hedef: httpdocs/uploads/. Dosya sayisi: <?= count($uploadOutgoingFiles) ?>.</small></div><?php endif; ?>
            <?php if ($codePublicOutgoingFiles): ?><div class="list-row"><span>SITE</span><strong>Public site dosyalari</strong><small>Kaynak: kok PHP dosyalari, assets/, includes/. Hedef: httpdocs/. Dosya sayisi: <?= count($codePublicOutgoingFiles) ?>.</small></div><?php endif; ?>
            <?php if ($codePrivateOutgoingFiles): ?><div class="list-row"><span>PRIVATE</span><strong>app/ ve config/ dosyalari</strong><small>Kaynak: C:/xampp/htdocs/acetinweb/app ve config. Hedef: /var/www/vhosts/acetin.com.tr/acetinweb_private/app ve config. Dosya sayisi: <?= count($codePrivateOutgoingFiles) ?>.</small></div><?php endif; ?>
            <div class="list-row"><span>MANIFEST</span><strong>Public kontrol dosyasi</strong><small>Kaynak: <?= e($localPublicManifestPath) ?>. Hedef: httpdocs/deploy-manifest.json. Bu dosya son kontrol imzasidir.</small></div>
        </div>
        <p class="help">Bu listede gorunmeyen dosyayi bu turda gonderme. Yayin/sira/metin/icerik degisikligi genelde sadece SQLite DB ve deploy-manifest.json uretir.</p>
        <p class="help">admin-local/, docs/, .git/, storage/ ve C:/xampp/acetinweb_private/storage/deploy-manifests/ canli yayin paketine dahil degildir.</p>
    </section>
    <section class="panel">
        <h2>Yayin paketi hazirla</h2>
        <p class="help">1) Once bu butona bas: localdeki son DB/kod/uploads durumunu kayda alir. 2) Kok dizindeki deploy-manifest.json dosyasini gunceller. 3) Private storage altinda sadece local arsiv icin detay manifest saklar; onu canliya gonderme.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="field"><label>Not</label><textarea name="note" placeholder="Orn: ai-context hikaye guncellendi, 2 medya eklendi"></textarea></div>
            <div class="form-actions">
                <button class="secondary" type="submit" name="action" value="write_manifest">1. Paketi hazirla ve manifesti yenile</button>
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
    <h2>Kod/site dosya farklari</h2>
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
