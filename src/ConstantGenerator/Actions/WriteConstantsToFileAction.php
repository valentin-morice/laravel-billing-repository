<?php

namespace ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions;

class WriteConstantsToFileAction
{
    private const MARKER_BEGIN = '// BEGIN AUTO-GENERATED CONSTANTS - DO NOT EDIT MANUALLY';

    private const MARKER_END = '// END AUTO-GENERATED CONSTANTS';

    /**
     * @param  array<string, string>  $constants  ['key' => 'CONSTANT_NAME']
     */
    public function handle(string $filePath, array $constants): bool
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("Model file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$filePath}");
        }

        $constantsSection = $this->buildConstantsSection($constants);

        // Check if markers exist
        if (str_contains($content, self::MARKER_BEGIN)) {
            // Replace existing section including leading whitespace to avoid stacking indentation
            $pattern = '/^[ \t]*'.preg_quote(self::MARKER_BEGIN, '/').
                '.*?'.
                '^[ \t]*'.preg_quote(self::MARKER_END, '/').'/ms';
            $content = preg_replace($pattern, $constantsSection, $content);
        } else {
            // Insert new section after class declaration
            $content = $this->insertConstantsSection($content, $constantsSection);
        }

        // Atomic write: temp file + rename
        return $this->writeAtomic($filePath, $content);
    }

    /**
     * @param  array<string, string>  $constants
     */
    private function buildConstantsSection(array $constants): string
    {
        $lines = ['    '.self::MARKER_BEGIN];

        foreach ($constants as $key => $constantName) {
            $lines[] = sprintf("    public const %s = '%s';", $constantName, $key);
        }

        $lines[] = '    '.self::MARKER_END;

        return implode("\n", $lines);
    }

    private function insertConstantsSection(string $content, string $section): string
    {
        // Find class declaration and insert after opening brace
        $pattern = '/(class\s+\w+.*?\{)/s';
        $replacement = "$1\n{$section}\n";

        return preg_replace($pattern, $replacement, $content, 1);
    }

    private function writeAtomic(string $filePath, string $content): bool
    {
        $tempFile = $filePath.'.tmp';

        file_put_contents($tempFile, $content);

        // Preserve original permissions
        $permissions = fileperms($filePath);
        if ($permissions !== false) {
            chmod($tempFile, $permissions);
        }

        return rename($tempFile, $filePath);
    }
}
