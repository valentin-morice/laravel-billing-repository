<?php

namespace ValentinMorice\LaravelBillingRepository\ConstantGenerator\Actions;

class ConvertToConstantNameAction
{
    private const RESERVED_KEYWORDS = [
        'CLASS', 'FUNCTION', 'CONST', 'TRAIT', 'INTERFACE', 'NAMESPACE',
        'USE', 'EXTENDS', 'IMPLEMENTS', 'PUBLIC', 'PRIVATE', 'PROTECTED',
        'STATIC', 'FINAL', 'ABSTRACT', 'RETURN', 'IF', 'ELSE', 'ELSEIF',
        'ENDIF', 'SWITCH', 'CASE', 'DEFAULT', 'BREAK', 'CONTINUE',
        'WHILE', 'DO', 'FOR', 'FOREACH', 'AS', 'TRY', 'CATCH', 'FINALLY',
        'THROW', 'NEW', 'CLONE', 'VAR', 'ECHO', 'PRINT', 'ISSET', 'EMPTY',
        'UNSET', 'EXIT', 'DIE', 'EVAL', 'INCLUDE', 'REQUIRE', 'PARENT', 'SELF',
    ];

    public function handle(string $key): string
    {
        // Normalize: replace separators with underscore
        $name = preg_replace('/[-\s.]+/', '_', $key);

        // Remove invalid characters
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);

        // Uppercase
        $name = strtoupper($name);

        // Handle starting with digit
        if (preg_match('/^[0-9]/', $name)) {
            $name = '_'.$name;
        }

        // Handle reserved keywords
        if (in_array($name, self::RESERVED_KEYWORDS, true)) {
            $name .= '_CONST';
        }

        return $name;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    public function handleMultiple(array $keys): array
    {
        $used = [];
        $result = [];

        foreach ($keys as $key) {
            $name = $this->handle($key);
            $original = $name;
            $counter = 2;

            // Handle collisions
            while (isset($used[$name])) {
                $name = $original.'_'.$counter++;
            }

            $used[$name] = true;
            $result[$key] = $name;
        }

        return $result;
    }
}
