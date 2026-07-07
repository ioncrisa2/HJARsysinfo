<?php

namespace App\Exceptions;

use RuntimeException;

class P2pkImportRowProcessingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $failureCode,
        public readonly bool $retryable = false,
        public readonly ?int $conflictingPembandingId = null,
    ) {
        parent::__construct($message);
    }
}
