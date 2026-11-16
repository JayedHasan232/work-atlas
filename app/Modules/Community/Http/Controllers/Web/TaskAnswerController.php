<?php

namespace App\Modules\Community\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\AnswerVerificationTask;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

/**
 * Takes an answer to a micro-task (PLAN M17) and moves on to the next question. A task that closed or
 * was already answered meanwhile (another tab, a slow network) is not an error: the next question
 * follows with a short note.
 */
final class TaskAnswerController extends Controller
{
    public function __invoke(int $task, Request $request, AnswerVerificationTask $answer): RedirectResponse
    {
        $data = $request->validate([
            'answer' => ['required', Rule::enum(TaskAnswer::class)],
        ]);

        $next = redirect()->to(LocalizedRoutes::path('tasks.show', [], App::getLocale()));

        try {
            $answer->handle($task, (int) $request->user()?->getAuthIdentifier(), TaskAnswer::from($data['answer']));
        } catch (ContributionRejected $rejected) {
            if ($rejected->reason === 'task') {
                abort(404);
            }

            return $next->with('notice', 'tasks.closed');
        }

        return $next->with('notice', $data['answer'] === TaskAnswer::Skip->value ? 'tasks.skipped' : 'tasks.thanks');
    }
}
