<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ApiException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // API errors use the same {success, error, code} shape as ApiException
        $this->renderable(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => $this->apiError($e->getMessage(), 'validation_failed', 422, ['errors' => $e->errors()]),
                $e instanceof AuthenticationException => $this->apiError('The API token is missing or invalid.', 'unauthenticated', 401),
                $e instanceof HttpExceptionInterface => $this->apiHttpError($e),
                config('app.debug') => null,
                default => $this->apiError('Something went wrong on the server.', 'server_error', 500),
            };
        });
    }

    private function apiHttpError(HttpExceptionInterface $e): JsonResponse
    {
        [$message, $code] = match ($e->getStatusCode()) {
            404 => ['That API endpoint does not exist.', 'not_found'],
            405 => ['That HTTP method is not allowed for this endpoint.', 'method_not_allowed'],
            429 => ['Too many requests. Try again in '.($e->getHeaders()['Retry-After'] ?? 60).' seconds.', 'rate_limited'],
            default => [$e->getMessage() ?: 'The request failed.', 'http_error'],
        };

        return $this->apiError($message, $code, $e->getStatusCode(), [], $e->getHeaders());
    }

    private function apiError(string $message, string $code, int $status, array $extra = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => $code,
            ...$extra,
        ], $status, $headers);
    }
}
