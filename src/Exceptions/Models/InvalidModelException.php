<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\Models;

use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

class InvalidModelException extends BillingException
{
    public static function fileNotFound(string $filePath): self
    {
        return new self("Model file not found: {$filePath}");
    }

    public static function noClassFound(): self
    {
        return new self('No class found in file');
    }

    public static function anonymousClass(): self
    {
        return new self('Anonymous classes are not supported');
    }
}
