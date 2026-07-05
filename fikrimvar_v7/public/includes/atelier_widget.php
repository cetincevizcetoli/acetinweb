<?php
declare(strict_types=1);

function render_atelier_widget(?array $project, ?array $latest, array $config=[]): void
{
    if(!$project || !$latest || !($config['enabled'] ?? true)) return;
    if(!in_array($project['workshop_status'] ?? 'none',['open','paused'],true)) return;
    $statusLabel=($project['workshop_status'] ?? '')==='paused' ? 'ATÖLYE BEKLEMEDE' : 'ATÖLYE AÇIK';
    $media=$latest['media'][0] ?? null;
    $url='atolye.php?slug='.rawurlencode((string)$project['slug']).'#update-'.rawurlencode((string)$latest['slug']);
    ?>
    <button class="atelier-fab" type="button" aria-expanded="false" aria-controls="atelier-widget" data-atelier-widget-open>
        <span class="atelier-fab-dot" aria-hidden="true"></span>
        <span><small><?= e($statusLabel) ?></small><strong><?= e($project['title']) ?></strong></span>
    </button>
    <div class="atelier-widget-overlay" data-atelier-widget-overlay hidden></div>
    <aside class="atelier-widget" id="atelier-widget" aria-labelledby="atelier-widget-title" aria-hidden="true" data-atelier-widget>
        <div class="atelier-widget-head">
            <div><span><?= e($statusLabel) ?></span><small><?= count(project_updates((int)$project['id'])) ?> kayıt</small></div>
            <button type="button" aria-label="Atölye penceresini kapat" data-atelier-widget-close><?= icon('close') ?></button>
        </div>
        <?php if($media): ?>
        <figure class="atelier-widget-media">
            <?php if(($media['media_type'] ?? '')==='video'): ?><video muted playsinline preload="metadata"><source src="<?= e(media_url($media['relative_path'])) ?>"></video>
            <?php else: ?><img src="<?= e(media_url($media['relative_path'])) ?>" alt="<?= e($media['alt_text'] ?? $project['title']) ?>" loading="lazy"><?php endif; ?>
        </figure>
        <?php endif; ?>
        <div class="atelier-widget-copy">
            <p><?= e($latest['date_label'] ?: 'Son kayıt') ?></p>
            <h2 id="atelier-widget-title"><?= e($latest['title']) ?></h2>
            <span><?= e($latest['summary']) ?></span>
            <dl><div><dt>Kararım</dt><dd><?= e($latest['decision']) ?></dd></div><div><dt>Sıradaki</dt><dd><?= e($latest['next_step']) ?></dd></div></dl>
            <div class="atelier-widget-actions">
                <a class="button button-rust" href="<?= e($url) ?>">Atölyeye gir <?= icon('arrow') ?></a>
                <?php foreach($latest['links'] ?? [] as $link): ?><a class="atelier-widget-social" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['title']) ?></a><?php endforeach; ?>
            </div>
        </div>
    </aside>
    <?php
}
