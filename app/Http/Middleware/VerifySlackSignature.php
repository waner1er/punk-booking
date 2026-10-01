<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que la requête vient bien de Slack.
 *
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
class VerifySlackSignature
{
    /** Ancienneté maximale acceptée, contre le rejeu de requêtes. */
    private const MAX_AGE_SECONDS = 300;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.slack.signing_secret');
        $timestamp = (string) $request->header('X-Slack-Request-Timestamp');
        $signature = (string) $request->header('X-Slack-Signature');

        if ($secret === '' || $signature === '' || ! ctype_digit($timestamp)) {
            return $this->reject();
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return $this->reject();
        }

        $expected = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$request->getContent()}", $secret);

        if (! hash_equals($expected, $signature)) {
            return $this->reject();
        }

        return $next($request);
    }

    private function reject(): Response
    {
        return response('Signature Slack invalide.', Response::HTTP_UNAUTHORIZED);
    }
}
