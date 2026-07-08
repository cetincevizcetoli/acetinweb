<?php
declare(strict_types=1);

final class VisibilityService
{
    public const PUBLIC_VISIBILITY = 'public';
    public const PUBLISHED_STATUS = 'published';
    public const HOME_SECTIONS = ['focus', 'trace'];
    public const WORKSHOP_WIDGET_STATUSES = ['open', 'paused'];

    public static function publicProjectSql(string $alias = 'p'): string
    {
        return $alias . ".deleted_at IS NULL AND " . $alias . ".visibility='public'";
    }

    public static function publishedPublicStorySql(string $alias = 's'): string
    {
        return $alias . ".deleted_at IS NULL AND " . $alias . ".status='published' AND " . $alias . ".visibility='public'";
    }

    public static function widgetProjectSql(string $alias = 'p'): string
    {
        return self::publicProjectSql($alias)
            . " AND " . $alias . ".workshop_status IN ('open','paused')"
            . " AND " . $alias . ".show_in_widget=1";
    }

    public static function projectIsPublic(array $project): bool
    {
        return ($project['visibility'] ?? '') === self::PUBLIC_VISIBILITY && empty($project['deleted_at']);
    }

    public static function storyIsPublishedPublic(?array $story): bool
    {
        return is_array($story)
            && ($story['status'] ?? '') === self::PUBLISHED_STATUS
            && ($story['visibility'] ?? '') === self::PUBLIC_VISIBILITY
            && empty($story['deleted_at']);
    }

    public static function homeSectionIsVisible(string $homeSection): bool
    {
        return in_array($homeSection, self::HOME_SECTIONS, true);
    }

    public static function workshopStatusAllowsWidget(string $workshopStatus): bool
    {
        return in_array($workshopStatus, self::WORKSHOP_WIDGET_STATUSES, true);
    }

    public static function homeVisible(array $project, ?array $story): bool
    {
        return self::projectIsPublic($project)
            && !empty($project['show_on_home'])
            && self::homeSectionIsVisible((string)($project['home_section'] ?? 'none'))
            && self::storyIsPublishedPublic($story);
    }

    public static function archiveVisible(array $project, ?array $story): bool
    {
        return self::projectIsPublic($project)
            && !empty($project['show_in_archive'])
            && self::storyIsPublishedPublic($story);
    }

    public static function widgetVisible(array $project): bool
    {
        return self::projectIsPublic($project)
            && self::workshopStatusAllowsWidget((string)($project['workshop_status'] ?? 'none'))
            && !empty($project['show_in_widget']);
    }

    public static function homeReason(array $project, ?array $story): string
    {
        if (self::homeVisible($project, $story)) return 'Ana sayfada gorunur.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje public degil';
        if (empty($project['show_on_home'])) $reasons[] = 'ana sayfada goster kapali';
        if (!self::homeSectionIsVisible((string)($project['home_section'] ?? 'none'))) $reasons[] = 'ana sayfadaki yeri kapali';
        if (!$story) $reasons[] = 'hikaye yok';
        elseif (!self::storyIsPublishedPublic($story)) $reasons[] = 'hikaye yayimlanmis/public degil';

        return 'Gorunmez: ' . implode(', ', $reasons) . '.';
    }

    public static function archiveReason(array $project, ?array $story): string
    {
        if (self::archiveVisible($project, $story)) return 'Hikayeler sayfasinda gorunur.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje public degil';
        if (empty($project['show_in_archive'])) $reasons[] = 'Hikayeler sayfasinda goster kapali';
        if (!$story) $reasons[] = 'hikaye yok';
        elseif (!self::storyIsPublishedPublic($story)) $reasons[] = 'hikaye yayimlanmis/public degil';

        return 'Gorunmez: ' . implode(', ', $reasons) . '.';
    }

    public static function widgetReason(array $project): string
    {
        if (self::widgetVisible($project)) return 'Atolye penceresinde gorunur.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje public degil';
        if (!self::workshopStatusAllowsWidget((string)($project['workshop_status'] ?? 'none'))) $reasons[] = 'Atolye durumu Acik/Beklemede degil';
        if (empty($project['show_in_widget'])) $reasons[] = 'Atolye penceresi kapali';

        return 'Gorunmez: ' . implode(', ', $reasons) . '.';
    }
}
