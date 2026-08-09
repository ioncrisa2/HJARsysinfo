<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class IncompleteUploadsRollbackException extends RuntimeException
{
    public function __construct(
        public readonly string $recoveryPath,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            "Rollback uploads tidak lengkap. Data recovery dipertahankan di {$recoveryPath}.",
            previous: $previous,
        );
    }
}
