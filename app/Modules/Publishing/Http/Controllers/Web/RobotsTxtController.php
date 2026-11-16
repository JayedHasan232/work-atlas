<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\RobotsTxt;
use Illuminate\Http\Response;

/**
 * `/robots.txt` (PLAN M14).
 */
final class RobotsTxtController extends Controller
{
    public function __invoke(RobotsTxt $robots): Response
    {
        return response($robots->text(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
