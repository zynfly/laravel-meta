<?php

namespace Zynfly\LaravelMeta\Commands;

use Illuminate\Console\Command;

class LaravelMetaCommand extends Command
{
    public $signature = 'laravel-meta';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
