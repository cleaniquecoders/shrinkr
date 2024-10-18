<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use Illuminate\Console\Command;

class ShrinkrCommand extends Command
{
    public $signature = 'shrinkr';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
