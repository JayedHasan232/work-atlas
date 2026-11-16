<?php

namespace App\Support\Monitoring;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Strips personal data from an error report before it leaves the server (CLAUDE.md: Sentry with PII
 * scrubbed; PLAN M22). The report keeps what is needed to fix a bug — the exception, the stack, the
 * route and the method — and loses who sent the request and what they typed: no user, no cookies,
 * no headers, no body, no query string (the guide's answers live there, D73). Email addresses and IP
 * addresses inside messages are masked as well.
 */
final class ErrorReportScrubber
{
    public const string MASK = '[scrubbed]';

    private const string EMAIL = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    private const string IPV4 = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';

    /** Anything that looks like an IPv6 address; only real ones are masked, so times like 12:30:45 stay. */
    private const string IPV6_CANDIDATE = '/(?<![0-9a-f:])[0-9a-f]{0,4}(?::[0-9a-f]{0,4}){2,7}(?![0-9a-f:])/i';

    /**
     * The `before_send` hook (config/sentry.php).
     */
    public static function scrub(Event $event, ?EventHint $hint = null): Event
    {
        $event->setUser(null);
        $event->setServerName(null);
        $event->setRequest(self::request($event->getRequest()));
        $event->setExtra(self::values($event->getExtra()));

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::text($exception->getValue()));
        }

        $message = $event->getMessage();

        if ($message !== null) {
            $event->setMessage(self::text($message), [], self::nullableText($event->getMessageFormatted()));
        }

        $event->setBreadcrumb(array_map(self::breadcrumb(...), $event->getBreadcrumbs()));

        return $event;
    }

    /**
     * Masks email and IP addresses in free text.
     */
    public static function text(string $text): string
    {
        $text = preg_replace([self::EMAIL, self::IPV4], self::MASK, $text);
        $text = is_string($text) ? preg_replace_callback(
            self::IPV6_CANDIDATE,
            fn (array $match): string => filter_var($match[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ? $match[0] : self::MASK,
            $text,
        ) : null;

        return is_string($text) ? $text : self::MASK;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private static function request(array $request): array
    {
        $url = $request['url'] ?? null;

        return array_filter([
            'url' => is_string($url) ? explode('?', $url, 2)[0] : null,
            'method' => $request['method'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== false);
    }

    private static function breadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        $message = $breadcrumb->getMessage();

        if ($message !== null) {
            $breadcrumb = $breadcrumb->withMessage(self::text($message));
        }

        foreach ($breadcrumb->getMetadata() as $name => $value) {
            $breadcrumb = $breadcrumb->withMetadata($name, self::value($value));
        }

        return $breadcrumb;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private static function values(array $values): array
    {
        return array_map(self::value(...), $values);
    }

    private static function value(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::text(self::withoutQuery($value));
        }

        return is_array($value) ? self::values($value) : $value;
    }

    private static function withoutQuery(string $value): string
    {
        return preg_match('#^https?://#i', $value) === 1 ? explode('?', $value, 2)[0] : $value;
    }

    private static function nullableText(?string $text): ?string
    {
        return $text === null ? null : self::text($text);
    }
}
