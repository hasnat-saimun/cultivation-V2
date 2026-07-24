<?php

namespace App\Exceptions;

use RuntimeException;

class ResultPublicationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 409,
        public readonly string $failure = 'PublicationTransitionConflict',
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function forbidden(string $message = 'Only a General or Super administrator may manage publication.'): self
    {
        return new self($message, 403, 'PublicationScopeUnauthorized');
    }

    public static function missing(string $message = 'The publication scope was not found.'): self
    {
        return new self($message, 404, 'PublicationScopeNotFound');
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
