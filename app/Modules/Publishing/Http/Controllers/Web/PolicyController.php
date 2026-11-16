<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Enums\Policy;
use App\Modules\Publishing\PageRobots;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/policies` and `/policies/{policy}` (PLAN M23): code of conduct, editorial policy, privacy, not
 * advice, neutrality and funding. Drafts until the maintainer approves them; the page says so.
 */
final class PolicyController extends Controller
{
    public function index(Request $request, PageRobots $robots): Response
    {
        $robots->decide($request, true);

        return Inertia::render('publishing/policies', [
            'policies' => $this->list(),
        ]);
    }

    public function show(Request $request, PageRobots $robots, string $policy): Response
    {
        $robots->decide($request, true);
        $current = Policy::from($policy);

        return Inertia::render('publishing/policy', [
            'policy' => ['slug' => $current->value, 'key' => $current->key()],
            'policies' => $this->list(),
        ]);
    }

    /** @return list<array{slug: string, key: string}> */
    private function list(): array
    {
        return array_map(
            fn (Policy $policy): array => ['slug' => $policy->value, 'key' => $policy->key()],
            Policy::cases(),
        );
    }
}
