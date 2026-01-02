<?php

namespace ValentinMorice\LaravelBillingRepository\Exceptions\IO;

use PhpParser\Error;
use ValentinMorice\LaravelBillingRepository\Exceptions\BillingException;

class FileParsingException extends BillingException
{
    public static function unableToRead(string $filePath): self
    {
        return new self("Unable to read file: {$filePath}");
    }

    public static function failedToParse(string $filePath): self
    {
        return new self("Failed to parse file: {$filePath}");
    }

    public static function parseError(string $filePath, Error $error): self
    {
        return new self(
            "Parse error in {$filePath} at line {$error->getStartLine()}: {$error->getMessage()}"
        );
    }
}
