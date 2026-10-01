<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Configure de faux identifiants Slack pour les tests.
 */
function fakeSlackConfig(): void
{
    config([
        'services.slack.booking_webhook' => 'https://hooks.slack.com/services/T000/B000/test',
        'services.slack.bot_token' => 'xoxb-test-token',
        'services.slack.signing_secret' => 'test-signing-secret',
    ]);
}

/**
 * Envoie une requête POST form-urlencoded signée comme le ferait Slack.
 *
 * @param  array<string, mixed>  $data
 */
function postFromSlack(string $uri, array $data, ?int $timestamp = null, ?string $signature = null): TestResponse
{
    $body = http_build_query($data);
    $timestamp ??= now()->getTimestamp();
    $signature ??= 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", config('services.slack.signing_secret'));

    return test()->call('POST', $uri, $data, [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'HTTP_X_SLACK_REQUEST_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SLACK_SIGNATURE' => $signature,
    ], $body);
}
