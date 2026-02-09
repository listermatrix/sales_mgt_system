<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use App\Constants\HttpStatusCode;

/**
 * API Rate Limiting Middleware
 *
 * Implements sophisticated rate limiting with different tiers
 */
class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $tier);
        $maxAttempts = $this->getMaxAttempts($tier);
        $decayMinutes = $this->getDecayMinutes($tier);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting
     *
     * @param Request $request
     * @param string $tier
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $tier): string
    {
        // Use user ID if authenticated, otherwise use IP
        $identifier = $request->user()?->id ?? $request->ip();

        return sprintf(
            'api_rate_limit:%s:%s:%s',
            $tier,
            $identifier,
            $request->route()?->getName() ?? $request->path()
        );
    }

    /**
     * Get max attempts for tier
     *
     * @param string $tier
     * @return int
     */
    protected function getMaxAttempts(string $tier): int
    {
        return match ($tier) {
            'auth' => 5,        // Login/registration: 5 per minute
            'payment' => 10,    // Payment operations: 10 per minute
            'read' => 100,      // Read operations: 100 per minute
            'write' => 50,      // Write operations: 50 per minute
            'default' => 60,    // Default: 60 per minute
        };
    }

    /**
     * Get decay minutes for tier
     *
     * @param string $tier
     * @return int
     */
    protected function getDecayMinutes(string $tier): int
    {
        return match ($tier) {
            'auth' => 1,        // Reset after 1 minute
            'payment' => 1,     // Reset after 1 minute
            'read' => 1,        // Reset after 1 minute
            'write' => 1,       // Reset after 1 minute
            'default' => 1,     // Reset after 1 minute
        };
    }

    /**
     * Build rate limit exceeded response
     *
     * @param string $key
     * @param int $maxAttempts
     * @return Response
     */
    protected function buildRateLimitResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'error' => [
                'message' => 'Too many requests. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
            ],
        ], HttpStatusCode::TOO_MANY_REQUESTS)
            ->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
    }

    /**
     * Add rate limit headers to response
     *
     * @param Response $response
     * @param int $maxAttempts
     * @param int $remainingAttempts
     * @param int|null $retryAfter
     * @return Response
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts,
        ?int $retryAfter = null
    ): Response {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remainingAttempts);

        if ($retryAfter !== null) {
            $response->headers->set('Retry-After', $retryAfter);
            $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }
}
