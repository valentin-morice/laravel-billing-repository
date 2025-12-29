<?php

namespace ValentinMorice\LaravelBillingRepository\Data\Enum;

enum ChangeTypeEnum: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Unchanged = 'unchanged';
    case Archived = 'archived';

    public function getSymbol(): string
    {
        return match ($this) {
            self::Created => '+',
            self::Updated => '~',
            self::Archived => '-',
            self::Unchanged => ' ',
        };
    }
}
