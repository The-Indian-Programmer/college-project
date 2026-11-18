<?php
declare(strict_types=1);
namespace Exam;
final class DomainError extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
