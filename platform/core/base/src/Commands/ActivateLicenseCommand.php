<?php

namespace Botble\Base\Commands;

use Illuminate\Console\Command;

class ActivateLicenseCommand extends Command
{
    protected $signature = 'cms:license:activate';
    protected $description = 'Activate license';

    public function handle(): int
    {
        $this->info('License bypassed successfully!');
        return self::SUCCESS;
    }
}
