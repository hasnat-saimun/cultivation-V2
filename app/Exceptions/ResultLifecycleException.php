<?php

namespace App\Exceptions;

use RuntimeException;

class ResultLifecycleException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 409,
        public readonly string $failure = 'LifecycleTransitionConflict',
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function forbidden(string $message = 'You are not authorized for this marks scope.'): self
    {
        return new self($message, 403, 'ScopeNotAuthorized');
    }

    public static function missing(string $message = 'The academic marks scope was not found.'): self
    {
        return new self($message, 404, 'ScopeNotFound');
    }

    public static function conflict(string $failure, string $message): self
    {
        return new self($message, 409, $failure);
    }

    public static function invalid(string $failure, string $message, array $details = []): self
    {
        return new self($message, 422, $failure, $details);
    }
}
