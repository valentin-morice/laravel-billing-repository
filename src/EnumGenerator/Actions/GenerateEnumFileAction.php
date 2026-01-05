<?php

namespace ValentinMorice\LaravelBillingRepository\EnumGenerator\Actions;

use ValentinMorice\LaravelBillingRepository\Data\Enum\ModelType;

class GenerateEnumFileAction
{
    /**
     * Generate an enum file for the given type and cases
     *
     * @param  array<string, string>  $cases  ['key' => 'CASE_NAME']
     */
    public function handle(ModelType $type, array $cases): bool
    {
        $enumName = match ($type) {
            ModelType::Product => 'ProductKey',
            ModelType::Price => 'PriceKey',
        };

        $filePath = $this->getFilePath($enumName);
        $content = $this->generateEnumContent($enumName, $cases);

        return $this->writeAtomic($filePath, $content);
    }

    /**
     * Generate the enum file content
     *
     * @param  array<string, string>  $cases
     */
    private function generateEnumContent(string $enumName, array $cases): string
    {
        $caseLines = '';
        foreach ($cases as $key => $caseName) {
            $caseLines .= "    case {$caseName} = '{$key}';\n";
        }

        return <<<PHP
<?php

namespace ValentinMorice\LaravelBillingRepository\Data\Enum\Consumer;

/**
 * Auto-generated enum for billing {$enumName}
 *
 * Do not edit manually - this file is regenerated when you run:
 * php artisan billing:deploy or php artisan billing:import
 */
enum {$enumName}: string
{
{$caseLines}}

PHP;
    }

    /**
     * Get the file path for the enum
     */
    private function getFilePath(string $enumName): string
    {
        $basePath = dirname(__DIR__, 2);

        return $basePath.'/Data/Enum/Consumer/'.$enumName.'.php';
    }

    /**
     * Write file atomically
     */
    private function writeAtomic(string $filePath, string $content): bool
    {
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $tempFile = $filePath.'.tmp';

        file_put_contents($tempFile, $content);

        if (file_exists($filePath)) {
            $permissions = fileperms($filePath);
            if ($permissions !== false) {
                chmod($tempFile, $permissions);
            }
        }

        $success = rename($tempFile, $filePath);

        // Run Pint if available
        if ($success && file_exists(base_path('vendor/bin/pint'))) {
            exec('vendor/bin/pint '.escapeshellarg($filePath).' 2>&1');
        }

        return $success;
    }
}
