<?php

use App\Models\User;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Enums\TaskState;
use App\Modules\Community\Models\VerificationAnswer;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M17: the "Check facts" page shows one question to a signed-in person and takes the answer.
 */

beforeEach(function () {
    wave1Setup();
});

it('sends a guest to the login page', function () {
    $task = taskFor();

    $this->get('/en/tasks')->assertRedirect('/login');
    $this->post("/en/tasks/{$task->id}", ['answer' => 'agree'])->assertRedirect('/login');
});

it('shows one question with the value and how consensus works', function () {
    $task = taskFor(value: 4);
    $this->actingAs(User::factory()->create());

    $this->get('/en/tasks')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/show')
            ->where('task.id', $task->id)
            ->where('task.field.key', 'physical_workload')
            ->where('task.field.label', 'Physical workload')
            ->where('task.value.kind', 'scale')
            ->where('task.value.point', 4)
            ->where('task.status', 'ai_draft')
            ->where('task.occupation.name', 'Plumber')
            ->where('task.answerUrl', "/en/tasks/{$task->id}")
            ->where('agreementsNeeded', 3)
            ->where('answered', 0));
});

it('says when no question is left', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/tasks')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('tasks/show')->where('task', null));
});

it('takes an answer and moves on to the next question', function (string $answer, string $notice) {
    $task = taskFor();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post("/en/tasks/{$task->id}", ['answer' => $answer])
        ->assertRedirect('/en/tasks')
        ->assertSessionHas('notice', $notice);

    expect(VerificationAnswer::query()->where('user_id', $user->id)->sole()->answer)->toBe(TaskAnswer::from($answer));
})->with([
    'agree' => ['agree', 'tasks.thanks'],
    'disagree' => ['disagree', 'tasks.thanks'],
    'skip' => ['skip', 'tasks.skipped'],
]);

it('says so when the question was already settled', function () {
    $task = taskFor();
    answerTask($task, TaskAnswer::Disagree);

    $this->actingAs(User::factory()->create())
        ->post("/en/tasks/{$task->id}", ['answer' => 'agree'])
        ->assertRedirect('/en/tasks')
        ->assertSessionHas('notice', 'tasks.closed');

    expect($task->refresh()->state)->toBe(TaskState::Disputed);
});

it('rejects an unknown answer or question', function () {
    $task = taskFor();
    $this->actingAs(User::factory()->create());

    $this->post("/en/tasks/{$task->id}", ['answer' => 'maybe'])->assertSessionHasErrors('answer');
    $this->post('/en/tasks/999999', ['answer' => 'agree'])->assertNotFound();
});

it('limits how fast one person answers', function () {
    config(['atlas.community.answers_per_minute' => 2]);
    $this->actingAs(User::factory()->create());
    $tasks = [taskFor(), taskFor(), taskFor()];

    $this->post("/en/tasks/{$tasks[0]->id}", ['answer' => 'skip'])->assertRedirect();
    $this->post("/en/tasks/{$tasks[1]->id}", ['answer' => 'skip'])->assertRedirect();
    $this->post("/en/tasks/{$tasks[2]->id}", ['answer' => 'skip'])->assertTooManyRequests();
});
