<?php
declare(strict_types=1);

function render_atelier_widget(?array $story, ?array $latest, array $config = []): void
{
    if (!$story || !$latest || !($config['enabled'] ?? true)) {
        return;
    }

    $status = workshop_status($story);
    if (!in_array($status, ['open', 'paused'], true)) {
        return;
    }
    $statusLabel = $status === 'paused' ? 'ATÖLYE BEKLEMEDE' : 'ATÖLYE AÇIK';
    $media = story_asset($story, (string) ($latest['media']['src'] ?? $story['cover'] ?? ''));
    $url = story_url($story) . '#update-' . rawurlencode((string) ($latest['_id'] ?? ''));
    ?>
    <button class="atelier-fab" type="button" aria-expanded="false" aria-controls="atelier-widget" data-atelier-widget-open>
        <span class="atelier-fab-dot" aria-hidden="true"></span>
        <span><small><?= e($statusLabel) ?></small><strong><?= e($story['title'] ?? 'Atölye') ?></strong></span>
    </button>

    <div class="atelier-widget-overlay" data-atelier-widget-overlay hidden></div>
    <aside class="atelier-widget" id="atelier-widget" aria-labelledby="atelier-widget-title" aria-hidden="true" data-atelier-widget>
        <div class="atelier-widget-head">
            <div><span><?= e($statusLabel) ?></span><small><?= count(load_updates($story)) ?> kayıt</small></div>
            <button type="button" aria-label="Atölye penceresini kapat" data-atelier-widget-close><?= icon('close') ?></button>
        </div>
        <?php if ($media !== ''): ?>
        <figure class="atelier-widget-media"><img src="<?= e($media) ?>" alt="<?= e($latest['media']['alt'] ?? $story['title'] ?? '') ?>" loading="lazy"></figure>
        <?php endif; ?>
        <div class="atelier-widget-copy">
            <p><?= e($latest['date_label'] ?? $latest['day'] ?? 'Son kayıt') ?></p>
            <h2 id="atelier-widget-title"><?= e($latest['title'] ?? $story['title'] ?? '') ?></h2>
            <span><?= e($latest['summary'] ?? '') ?></span>
            <dl>
                <div><dt>Kararım</dt><dd><?= e($latest['decision'] ?? '') ?></dd></div>
                <div><dt>Sıradaki</dt><dd><?= e($latest['next'] ?? '') ?></dd></div>
            </dl>
            <div class="atelier-widget-actions">
                <a class="button button-rust" href="<?= e($url) ?>">Atölyeye gir <?= icon('arrow') ?></a>
                <?php foreach (['instagram_url' => 'Instagram', 'youtube_url' => 'YouTube', 'github_url' => 'GitHub'] as $field => $label):
                    $socialUrl = trim((string) ($latest[$field] ?? ''));
                    if ($socialUrl === '') continue;
                ?>
                <a class="atelier-widget-social" href="<?= e($socialUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
    <?php
}
