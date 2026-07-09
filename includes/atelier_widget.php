<?php
declare(strict_types=1);

function render_atelier_widget(array $workshops, array $config=[]): void
{
    if(!($config['enabled'] ?? true)) return;
    if(!$workshops){
        ?>
        <button class="atelier-fab" type="button" aria-expanded="false" aria-controls="atelier-widget" data-atelier-widget-open>
            <span class="atelier-fab-dot" aria-hidden="true"></span>
            <span><small>ATÖLYE SAKİN</small><strong>Açık iş yok</strong></span>
        </button>
        <div class="atelier-widget-overlay" data-atelier-widget-overlay hidden></div>
        <aside class="atelier-widget" id="atelier-widget" aria-labelledby="atelier-widget-title" aria-hidden="true" data-atelier-widget hidden>
            <div class="atelier-widget-head">
                <div><span>ATÖLYE SAKİN</span><small>0 atölye izleniyor</small></div>
                <button type="button" aria-label="Atölye penceresini kapat" data-atelier-widget-close><?= icon('close') ?></button>
            </div>
            <div class="atelier-widget-copy">
                <p>Boş durum</p>
                <h2 id="atelier-widget-title">Şu anda açık Atölye kaydı yok.</h2>
                <span>Yeni denemeler oldukça buraya düşecek.</span>
            </div>
        </aside>
        <?php
        return;
    }
    $first=$workshops[0];
    $count=count($workshops);
    $statusLabel=$count > 1 ? 'ATÖLYEDE '.$count.' İŞ' : (($first['project']['workshop_status'] ?? '')==='paused' ? 'ATÖLYE BEKLEMEDE' : 'ATÖLYE AÇIK');
    ?>
    <button class="atelier-fab" type="button" aria-expanded="false" aria-controls="atelier-widget" data-atelier-widget-open>
        <span class="atelier-fab-dot" aria-hidden="true"></span>
        <span><small><?= e($statusLabel) ?></small><strong><?= e($first['project']['title']) ?></strong></span>
    </button>
    <div class="atelier-widget-overlay" data-atelier-widget-overlay hidden></div>
    <aside class="atelier-widget" id="atelier-widget" aria-labelledby="atelier-widget-title" aria-hidden="true" data-atelier-widget hidden>
        <div class="atelier-widget-head">
            <div><span><?= e($statusLabel) ?></span><small><?= $count ?> atölye izleniyor</small></div>
            <button type="button" aria-label="Atölye penceresini kapat" data-atelier-widget-close><?= icon('close') ?></button>
        </div>
        <div class="atelier-widget-copy atelier-widget-copy--list">
            <p><?= $count > 1 ? 'Açık işler' : 'Açık iş' ?></p>
            <h2 id="atelier-widget-title">Atölyede şimdi</h2>
            <span>Takip etmek istediğin işi seç; ilgili atölye kaydına gidersin.</span>
            <div class="atelier-widget-list">
                <?php foreach($workshops as $item): $project=$item['project']; $latest=$item['latest']; $media=$latest['media'][0] ?? null; $url='atolye.php?slug='.rawurlencode((string)$project['slug']).((string)($latest['slug'] ?? '')!=='' ? '#update-'.rawurlencode((string)$latest['slug']) : ''); $storyUrl=(($project['story_status'] ?? '')==='published' && in_array($project['story_visibility'] ?? '', ['public','unlisted'], true)) ? 'hikaye.php?slug='.rawurlencode((string)$project['slug']) : ''; $itemStatus=($project['workshop_status'] ?? '')==='paused' ? 'Beklemede' : 'Açık'; ?>
                <article class="atelier-widget-item <?= $media ? '' : 'atelier-widget-item--text' ?>">
                    <?php if($media): ?><a class="atelier-widget-thumb" href="<?= e($url) ?>"><?php if(($media['media_type'] ?? '')==='video'): ?><video muted playsinline preload="metadata"><source src="<?= e(media_url($media['relative_path'])) ?>"></video><?php else: ?><img src="<?= e(media_url($media['relative_path'])) ?>" alt="<?= e($media['alt_text'] ?? $project['title']) ?>" loading="lazy"><?php endif; ?></a><?php endif; ?>
                    <div>
                        <p><?= e($itemStatus) ?> · <?= (int)($project['update_count'] ?? 0) ?> kayıt</p>
                        <h3><?= e($project['title']) ?></h3>
                        <strong><?= e($latest['title']) ?></strong>
                        <span><?= e($latest['summary']) ?></span>
                        <div class="atelier-widget-actions">
                            <a class="button button-rust" href="<?= e($url) ?>">Atölyeye gir <?= icon('arrow') ?></a>
                            <?php if($storyUrl!==''): ?><a class="button secondary" href="<?= e($storyUrl) ?>">Hikâyeyi oku</a><?php endif; ?>
                            <?php foreach($latest['links'] ?? [] as $link): ?><a class="atelier-widget-social" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['title']) ?></a><?php endforeach; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
    <?php
}
