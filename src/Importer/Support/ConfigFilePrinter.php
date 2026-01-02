<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Support;

use PhpParser\Node\Expr\Array_;
use PhpParser\PrettyPrinter\Standard;

class ConfigFilePrinter extends Standard
{
    protected function pExpr_Array(Array_ $node): string
    {
        $syntax = $node->getAttribute('kind', Array_::KIND_SHORT);

        if (empty($node->items)) {
            return $syntax === Array_::KIND_SHORT ? '[]' : 'array()';
        }

        $this->indentLevel++;
        $itemIndent = str_repeat('    ', $this->indentLevel);

        $formattedItems = [];
        foreach ($node->items as $item) {
            /** @phpstan-ignore-next-line */
            if ($item === null) {
                continue;
            }

            $formattedItems[] = $itemIndent.$this->p($item);
        }

        $this->indentLevel--;
        $closeIndent = str_repeat('    ', $this->indentLevel);

        $items = implode(",\n", $formattedItems);

        if ($syntax === Array_::KIND_SHORT) {
            return "[\n{$items},\n{$closeIndent}]";
        } else {
            return "array(\n{$items},\n{$closeIndent})";
        }
    }
}
