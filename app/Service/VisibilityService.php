<?php
declare(strict_types=1);

final class VisibilityService
{
    public const PUBLIC_VISIBILITY = 'public';
    public const UNLISTED_VISIBILITY = 'unlisted';
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

    public static function publicReadableProjectSql(string $alias = 'p'): string
    {
        return $alias . ".deleted_at IS NULL AND " . $alias . ".visibility IN ('public','unlisted')";
    }

    public static function publishedReadableStorySql(string $alias = 's'): string
    {
        return $alias . ".deleted_at IS NULL AND " . $alias . ".status='published' AND " . $alias . ".visibility IN ('public','unlisted')";
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

    public static function projectIsPublicReadable(array $project): bool
    {
        return in_array((string)($project['visibility'] ?? ''), [self::PUBLIC_VISIBILITY, self::UNLISTED_VISIBILITY], true)
            && empty($project['deleted_at']);
    }

    public static function storyIsPublishedReadable(?array $story): bool
    {
        return is_array($story)
            && ($story['status'] ?? '') === self::PUBLISHED_STATUS
            && in_array((string)($story['visibility'] ?? ''), [self::PUBLIC_VISIBILITY, self::UNLISTED_VISIBILITY], true)
            && empty($story['deleted_at']);
    }

    public static function storyDetailReadable(array $project, ?array $story): bool
    {
        return self::projectIsPublicReadable($project) && self::storyIsPublishedReadable($story);
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
        if (self::homeVisible($project, $story)) return 'Ana sayfada görünür.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje herkese açık değil';
        if (empty($project['show_on_home'])) $reasons[] = 'Ana sayfada göster kapalı';
        if (!self::homeSectionIsVisible((string)($project['home_section'] ?? 'none'))) $reasons[] = 'Ana sayfadaki yeri kapalı';
        if (!$story) $reasons[] = 'hikâye yok';
        elseif (!self::storyIsPublishedPublic($story)) $reasons[] = 'hikâye yayımlanmış/herkese açık değil';

        return 'Görünmez: ' . implode(', ', $reasons) . '.';
    }

    public static function archiveReason(array $project, ?array $story): string
    {
        if (self::archiveVisible($project, $story)) return 'Hikâyeler sayfasında görünür.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje herkese açık değil';
        if (empty($project['show_in_archive'])) $reasons[] = 'Hikâyeler sayfasında göster kapalı';
        if (!$story) $reasons[] = 'hikâye yok';
        elseif (!self::storyIsPublishedPublic($story)) $reasons[] = 'hikâye yayımlanmış/herkese açık değil';

        return 'Görünmez: ' . implode(', ', $reasons) . '.';
    }

    public static function widgetReason(array $project): string
    {
        if (self::widgetVisible($project)) return 'Atölye penceresinde görünür.';

        $reasons = [];
        if (!self::projectIsPublic($project)) $reasons[] = 'proje herkese açık değil';
        if (!self::workshopStatusAllowsWidget((string)($project['workshop_status'] ?? 'none'))) $reasons[] = 'Atölye durumu Açık/Beklemede değil';
        if (empty($project['show_in_widget'])) $reasons[] = 'Atölye penceresinde göster kapalı';

        return 'Görünmez: ' . implode(', ', $reasons) . '.';
    }
}
