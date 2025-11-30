<?php

namespace ValentinMorice\LaravelPriceRepository\Commands;

use Illuminate\Console\Command;

class LaravelPriceRepositoryCommand extends Command
{
    public $signature = 'laravel-price-repository';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
