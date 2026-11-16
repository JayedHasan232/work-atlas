<?php

namespace App\Modules\Publishing;

use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Support\Localization\Locales;
use App\Support\Localization\LocalizedRoutes;
use XMLWriter;

/**
 * `/sitemap.xml` (rule 12, PLAN M14, D57). Empty while the index gate is off (the Phase 1 beta).
 * When on, it lists the main public pages and every indexable occupation page, each in every
 * language with `hreflang` alternates.
 */
final class Sitemap
{
    /** Localized routes without parameters that are always listed once the gate is on. */
    private const array PAGES = ['home', 'occupations.index', 'contribution-terms'];

    public function __construct(
        private readonly IndexGate $gate,
        private readonly PublicOccupations $occupations,
    ) {}

    public function xml(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        if ($this->gate->enabled()) {
            foreach (self::PAGES as $name) {
                $this->page($xml, $name, []);
            }

            foreach ($this->occupations->indexableSlugs() as $slug) {
                $this->page($xml, 'occupations.show', ['slug' => $slug]);
            }
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * One `<url>` per language, each naming every language's URL as an alternate.
     *
     * @param  array<string, string>  $parameters
     */
    private function page(XMLWriter $xml, string $name, array $parameters): void
    {
        $urls = [];

        foreach (Locales::codes() as $locale) {
            $urls[$locale] = url(LocalizedRoutes::path($name, $parameters, $locale));
        }

        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url);

            foreach ($urls as $locale => $alternate) {
                $xml->startElement('xhtml:link');
                $xml->writeAttribute('rel', 'alternate');
                $xml->writeAttribute('hreflang', $locale);
                $xml->writeAttribute('href', $alternate);
                $xml->endElement();
            }

            $xml->endElement();
        }
    }
}
