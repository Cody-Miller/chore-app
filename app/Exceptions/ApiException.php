<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * An expected API failure, rendered as {success: false, error, code, ...extra}.
 * The message is a short sentence a voice assistant can read out.
 */
class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
        public readonly array $extra = [],
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $this->getMessage(),
            'code' => $this->errorCode,
            ...$this->extra,
        ], $this->status);
    }
}
