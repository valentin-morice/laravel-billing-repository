<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Support;

use PhpParser\Node\Expr\Array_;
use PhpParser\PrettyPrinter\Standard;

class ConfigFilePrinter extends Standard
{
    private int $arrayDepth = 0;

    private const BASE_INDENT_LEVEL = 1;

    protected function pExpr_Array(Array_ $node): string
    {
        $syntax = $node->getAttribute('kind', Array_::KIND_SHORT);

        if (empty($node->items)) {
            return $syntax === Array_::KIND_SHORT ? '[]' : 'array()';
        }

        $this->arrayDepth++;
        $itemIndent = str_repeat('    ', self::BASE_INDENT_LEVEL + $this->arrayDepth);

        $formattedItems = [];
        foreach ($node->items as $item) {
            /** @phpstan-ignore-next-line */
            if ($item === null) {
                continue;
            }

            $formattedItems[] = $itemIndent.$this->p($item);
        }

        $closeIndent = str_repeat('    ', self::BASE_INDENT_LEVEL + $this->arrayDepth - 1);
        $this->arrayDepth--;

        $items = implode(",\n", $formattedItems);

        if ($syntax === Array_::KIND_SHORT) {
            return "[\n{$items},\n{$closeIndent}]";
        } else {
            return "array(\n{$items},\n{$closeIndent})";
        }
    }
}
