<?php
declare(strict_types=1);

final class LinkViewModel
{
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly string $type,
        public readonly string $provider,
        public readonly string $host,
        public readonly string $displayUrl,
        public readonly string $embedKind = '',
        public readonly string $embedUrl = '',
    ) {}

    public function isEmbeddable(): bool
    {
        return $this->embedKind !== '' && $this->embedUrl !== '';
    }
}

final class LinkRenderer
{
    public static function renderList(array $links): void
    {
        $models = [];
        foreach ($links as $link) {
            $model = self::fromRow(is_array($link) ? $link : []);
            if ($model) $models[] = $model;
        }

        if ($models === []) return;

        echo '<div class="content-link-cards" aria-label="Ilgili baglantilar">';
        foreach ($models as $model) self::renderCard($model);
        echo '</div>';
    }

    public static function fromRow(array $row): ?LinkViewModel
    {
        $url = safe_external_url((string)($row['url'] ?? ''));
        if ($url === '') return null;

        $type = strtolower(trim((string)($row['link_type'] ?? 'external'))) ?: 'external';
        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $provider = self::provider($url, $type, $host);
        $title = self::title((string)($row['title'] ?? ''), $provider, $host, $type);
        [$embedKind, $embedUrl] = self::embed($url, $provider);

        return new LinkViewModel(
            url: $url,
            title: $title,
            type: $type,
            provider: $provider,
            host: $host,
            displayUrl: self::displayUrl($url, $host),
            embedKind: $embedKind,
            embedUrl: $embedUrl,
        );
    }

    private static function renderCard(LinkViewModel $model): void
    {
        $classes = 'content-link-card content-link-card--' . preg_replace('/[^a-z0-9-]/', '', $model->provider);
        echo '<article class="' . e($classes) . '">';

        if ($model->embedKind === 'iframe') {
            echo '<div class="content-link-player">';
            echo '<iframe src="' . e($model->embedUrl) . '" title="' . e($model->title) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
            echo '</div>';
        } elseif ($model->embedKind === 'video') {
            echo '<div class="content-link-player">';
            echo '<video controls playsinline preload="metadata"><source src="' . e($model->embedUrl) . '"></video>';
            echo '</div>';
        } elseif ($model->embedKind === 'audio') {
            echo '<div class="content-link-audio">';
            echo '<audio controls preload="metadata"><source src="' . e($model->embedUrl) . '"></audio>';
            echo '</div>';
        }

        echo '<a class="content-link-card-main" href="' . e($model->url) . '" target="_blank" rel="noopener noreferrer">';
        echo '<span>' . e(self::providerLabel($model)) . '</span>';
        echo '<strong>' . e($model->title) . '</strong>';
        echo '<small>' . e($model->displayUrl) . '</small>';
        echo '<em>Baglantiyi ac ' . icon('arrow') . '</em>';
        echo '</a>';
        echo '</article>';
    }

    private static function provider(string $url, string $type, string $host): string
    {
        if (str_contains($host, 'youtube.com') || $host === 'youtu.be') return 'youtube';
        if (str_contains($host, 'vimeo.com')) return 'vimeo';
        if (str_contains($host, 'soundcloud.com')) return 'soundcloud';
        if (str_contains($host, 'instagram.com')) return 'instagram';
        if (str_contains($host, 'github.com')) return 'github';

        $path = strtolower((string)(parse_url($url, PHP_URL_PATH) ?: ''));
        if (preg_match('/\.(mp4|webm|mov|m4v)$/', $path)) return 'video';
        if (preg_match('/\.(mp3|wav|ogg|m4a)$/', $path)) return 'audio';

        return in_array($type, ['youtube', 'instagram', 'github', 'download', 'website'], true) ? $type : 'external';
    }

    private static function title(string $rawTitle, string $provider, string $host, string $type): string
    {
        $title = trim($rawTitle);
        $generic = ['', 'baglanti', 'bağlantı', 'link', 'external'];
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
        if (!in_array($normalized, $generic, true)) return $title;

        return match ($provider) {
            'youtube' => 'YouTube videosu',
            'vimeo' => 'Vimeo videosu',
            'soundcloud' => 'SoundCloud sesi',
            'instagram' => 'Instagram kaydi',
            'github' => 'GitHub deposu',
            'video' => 'Video kaydi',
            'audio' => 'Ses kaydi',
            'download' => 'Indirme dosyasi',
            'website' => 'Web sitesi',
            default => $host !== '' ? $host . ' baglantisi' : ucfirst($type),
        };
    }

    private static function embed(string $url, string $provider): array
    {
        return match ($provider) {
            'youtube' => self::youtubeEmbed($url),
            'vimeo' => self::vimeoEmbed($url),
            'soundcloud' => ['iframe', 'https://w.soundcloud.com/player/?url=' . rawurlencode($url)],
            'video' => ['video', $url],
            'audio' => ['audio', $url],
            default => ['', ''],
        };
    }

    private static function youtubeEmbed(string $url): array
    {
        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
        $path = trim((string)(parse_url($url, PHP_URL_PATH) ?: ''), '/');
        $query = [];
        parse_str((string)(parse_url($url, PHP_URL_QUERY) ?: ''), $query);

        $id = '';
        if ($host === 'youtu.be') {
            $id = explode('/', $path)[0] ?? '';
        } elseif (isset($query['v']) && is_string($query['v'])) {
            $id = $query['v'];
        } elseif (preg_match('~(?:embed|shorts)/([A-Za-z0-9_-]{6,})~', $path, $m)) {
            $id = $m[1];
        }

        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $id)
            ? ['iframe', 'https://www.youtube-nocookie.com/embed/' . $id]
            : ['', ''];
    }

    private static function vimeoEmbed(string $url): array
    {
        $path = trim((string)(parse_url($url, PHP_URL_PATH) ?: ''), '/');
        return preg_match('~(?:video/)?([0-9]{6,})~', $path, $m)
            ? ['iframe', 'https://player.vimeo.com/video/' . $m[1]]
            : ['', ''];
    }

    private static function providerLabel(LinkViewModel $model): string
    {
        return match ($model->provider) {
            'youtube', 'vimeo' => 'Video',
            'soundcloud', 'audio' => 'Ses',
            'github' => 'Kod',
            'instagram' => 'Sosyal medya',
            'video' => 'Medya',
            'download' => 'Dosya',
            default => $model->host !== '' ? $model->host : 'Kaynak',
        };
    }

    private static function displayUrl(string $url, string $host): string
    {
        $path = trim((string)(parse_url($url, PHP_URL_PATH) ?: ''), '/');
        if ($path === '') return $host;
        $short = $host . '/' . $path;
        return strlen($short) > 72 ? substr($short, 0, 69) . '...' : $short;
    }
}
