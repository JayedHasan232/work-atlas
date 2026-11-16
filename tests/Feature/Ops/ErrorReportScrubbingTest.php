<?php

use App\Models\User;
use App\Support\Monitoring\ErrorReportScrubber;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Sentry\UserDataBag;

// PLAN M22 and CLAUDE.md: error reports leave the server without personal data.

/**
 * Turns the error monitor on with a transport that keeps the events here instead of sending them.
 *
 * @return ArrayObject<int, Event>
 */
function captureErrorReports(): ArrayObject
{
    $sent = new ArrayObject;
    $transport = new class($sent) implements TransportInterface
    {
        /** @param ArrayObject<int, Event> $sent */
        public function __construct(private readonly ArrayObject $sent) {}

        public function send(Event $event): Result
        {
            $this->sent->append($event);

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };

    Config::set('sentry.dsn', 'https://public@o0.ingest.sentry.io/1');
    app()->extend(ClientBuilder::class, fn (ClientBuilder $builder): ClientBuilder => $builder->setTransport($transport));
    app()->forgetInstance(HubInterface::class);
    SentrySdk::setCurrentHub(app(HubInterface::class));

    return $sent;
}

it('keeps personal data out of the error monitor', function () {
    $sent = captureErrorReports();
    Route::middleware('web')->get('/_test/boom', function () {
        throw new RuntimeException('Mail to rahim@example.com from 203.0.113.9 failed');
    });

    $this->actingAs(User::factory()->create(['email' => 'karim@example.com']))
        ->withHeader('X-Forwarded-For', '198.51.100.7')
        ->withUnencryptedCookie('appearance', 'dark')
        ->get('/_test/boom?work_time=night&email=karim@example.com')
        ->assertServerError();

    expect($sent)->toHaveCount(1);
    $event = $sent[0];
    $json = json_encode([
        $event->getRequest(), $event->getUser(), $event->getExtra(), $event->getContexts(),
        array_map(fn ($exception) => $exception->getValue(), $event->getExceptions()),
        array_map(fn ($crumb) => [$crumb->getMessage(), $crumb->getMetadata()], $event->getBreadcrumbs()),
    ]);

    expect($event->getUser())->toBeNull()
        ->and(array_diff(array_keys($event->getRequest()), ['url', 'method']))->toBe([])
        ->and($event->getExceptions()[0]->getValue())->toBe('Mail to [scrubbed] from [scrubbed] failed')
        ->and($json)->not->toContain('example.com')
        ->not->toContain('198.51.100.7')
        ->not->toContain('203.0.113.9')
        ->not->toContain('night')
        ->not->toContain('appearance');
});

it('scrubs every part of a hand-made report', function () {
    $event = Event::createEvent();
    $event->setUser(UserDataBag::createFromUserIdentifier(7)->setEmail('a@example.org'));
    $event->setServerName('web-1');
    $event->setRequest([
        'url' => 'https://kaj.example/guide?work_time=night',
        'method' => 'POST',
        'query_string' => 'work_time=night',
        'cookies' => ['session' => 'secret'],
        'headers' => ['authorization' => 'Bearer secret'],
        'data' => ['email' => 'a@example.org'],
        'env' => ['REMOTE_ADDR' => '192.0.2.1'],
    ]);
    $event->setExtra(['link' => 'https://kaj.example/x?token=secret', 'ip' => '2001:db8::1:2:3']);
    $event->setMessage('From a@example.org at 12:30:45');

    $event = ErrorReportScrubber::scrub($event);

    expect($event->getUser())->toBeNull()
        ->and($event->getServerName())->toBeNull()
        ->and($event->getRequest())->toBe(['url' => 'https://kaj.example/guide', 'method' => 'POST'])
        ->and($event->getExtra())->toBe(['link' => 'https://kaj.example/x', 'ip' => '[scrubbed]'])
        ->and($event->getMessage())->toBe('From [scrubbed] at 12:30:45');
});

it('cannot be configured to send personal data', function () {
    expect(Config::get('sentry.send_default_pii'))->toBeFalse()
        ->and(Config::get('sentry.before_send'))->toBe([ErrorReportScrubber::class, 'scrub'])
        ->and(Config::get('sentry.breadcrumbs.sql_bindings'))->toBeFalse()
        ->and(Config::get('sentry.tracing.sql_bindings'))->toBeFalse();
});
