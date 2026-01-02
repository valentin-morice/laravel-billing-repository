<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use Illuminate\Support\Str;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class GenerateProductKeyAction
{
    /**
     * Convert product name to snake_case key
     */
    public function handle(string $name): string
    {
        $normalized = $this->normalizeProductName($name);
        $baseKey = Str::snake($normalized);

        // Clean up multiple underscores and trim
        $baseKey = preg_replace('/_+/', '_', $baseKey);
        $baseKey = trim($baseKey, '_');

        return $this->ensureUnique($baseKey);
    }

    /**
     * Normalize product name before snake_case conversion
     * Handles: spaces around hyphens, mixed PascalCase+acronyms, standalone acronyms
     */
    protected function normalizeProductName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s*-\s*/', ' ', $name);

        // Match: Capital + lowercase(s) + consecutive capitals (e.g., "YouDJ", "MyAPI")
        // This must come before standalone acronym handling
        $name = preg_replace_callback('/([A-Z][a-z]+)([A-Z]{2,})/', function ($matches) {
            return ucfirst(strtolower($matches[1].$matches[2]));
        }, $name);

        // Handle standalone acronyms (e.g., "NIF" in "NIF Portugal")
        // Word boundary ensures we only match complete acronyms, not parts of words
        $name = preg_replace_callback('/\b([A-Z]{2,})\b/', function ($matches) {
            return ucfirst(strtolower($matches[0]));
        }, $name);

        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Ensure key is unique in database
     * Appends counter if collision detected
     */
    protected function ensureUnique(string $baseKey): string
    {
        $key = $baseKey;
        $counter = 1;

        while (BillingProduct::where('key', $key)->exists()) {
            $key = $baseKey.'_'.$counter;
            $counter++;
        }

        return $key;
    }
}
