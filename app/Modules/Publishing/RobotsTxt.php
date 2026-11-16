<?php

namespace App\Modules\Publishing;

/**
 * `/robots.txt` (PLAN M14, D57). Crawlers may fetch public pages, so they can read each page's
 * `noindex`; a page blocked here could still be indexed from links without its content. The
 * admin, the queue dashboard, personal pages and search results (one page per query) are blocked.
 */
final class RobotsTxt
{
    /** @var list<string> */
    private const array DISALLOW = ['/admin', '/horizon', '/dashboard', '/settings', '/search', '/en/search'];

    public function text(): string
    {
        $lines = ['User-agent: *', ...array_map(fn (string $path): string => "Disallow: {$path}", self::DISALLOW)];
        $lines[] = '';
        $lines[] = 'Sitemap: '.url('sitemap.xml');

        return implode("\n", $lines)."\n";
    }
}
