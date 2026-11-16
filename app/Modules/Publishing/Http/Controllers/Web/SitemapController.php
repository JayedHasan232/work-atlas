<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Sitemap;
use Illuminate\Http\Response;

/**
 * `/sitemap.xml` (PLAN M14).
 */
final class SitemapController extends Controller
{
    public function __invoke(Sitemap $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
