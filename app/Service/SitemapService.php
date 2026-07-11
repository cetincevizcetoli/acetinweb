<?php
declare(strict_types=1);

final class SitemapService
{
    public static function publicUrls(): array
    {
        $urls = [
            [
                'loc' => self::url('/'),
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => self::url('/hikayeler.php'),
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
        ];

        $projectVisibility = VisibilityService::publicProjectSql('p');
        $storyVisibility = VisibilityService::publishedPublicStorySql('s');
        $sql = "SELECT p.slug, p.sort_order, p.updated_at project_updated_at,
                       s.updated_at story_updated_at, s.published_at story_published_at
                FROM projects p
                JOIN stories s ON s.project_id=p.id
                WHERE $projectVisibility
                  AND p.show_in_archive=1
                  AND $storyVisibility
                ORDER BY p.is_pinned DESC, p.sort_order ASC, COALESCE(s.published_at,s.updated_at,p.updated_at,p.created_at) DESC";

        foreach (db()->query($sql)->fetchAll() as $row) {
            $urls[] = [
                'loc' => self::url('/hikaye.php?slug=' . rawurlencode((string)$row['slug'])),
                'lastmod' => self::dateOnly((string)($row['story_updated_at'] ?: $row['story_published_at'] ?: $row['project_updated_at'] ?: '')),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        return $urls;
    }

    public static function xml(): string
    {
        $out = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach (self::publicUrls() as $url) {
            $out[] = '  <url>';
            $out[] = '    <loc>' . self::xmlEscape($url['loc']) . '</loc>';
            if (!empty($url['lastmod'])) $out[] = '    <lastmod>' . self::xmlEscape($url['lastmod']) . '</lastmod>';
            $out[] = '    <changefreq>' . self::xmlEscape($url['changefreq']) . '</changefreq>';
            $out[] = '    <priority>' . self::xmlEscape($url['priority']) . '</priority>';
            $out[] = '  </url>';
        }
        $out[] = '</urlset>';
        return implode("\n", $out) . "\n";
    }

    private static function url(string $path): string
    {
        return FV7_SITE_URL . '/' . ltrim($path, '/');
    }

    private static function dateOnly(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
