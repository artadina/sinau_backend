<?php

namespace App\Services;

class VideoUrlParser
{
    private const YOUTUBE_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtu.be',
        'www.youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    // FR-S07: recognize only supported YouTube hosts and extract a valid video ID for embedding.
    public function youtubeEmbedUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, self::YOUTUBE_HOSTS, true) || ($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');
        $videoId = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = $path;
        } elseif (str_contains($host, 'youtube-nocookie.com')) {
            if (preg_match('#^embed/([^/]+)$#', $path, $matches)) {
                $videoId = $matches[1];
            }
        } elseif ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
        } elseif (preg_match('#^(?:embed|shorts|live)/([^/]+)$#', $path, $matches)) {
            $videoId = $matches[1];
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }

    // FR-S07: distinguish malformed YouTube URLs from ordinary direct video file URLs.
    public function isYoutubeHost(string $url): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');

        return in_array($host, self::YOUTUBE_HOSTS, true);
    }
}
