<?php

namespace ValentinMorice\LaravelStripeRepository\Commands;

use Illuminate\Console\Command;

class LaravelStripeRepositoryCommand extends Command
{
    public $signature = 'laravel-stripe-repository';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
