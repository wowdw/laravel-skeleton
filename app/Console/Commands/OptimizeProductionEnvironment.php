<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeProductionEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize production environment.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->getLaravel()->isProduction()) {
            return;
        }

        $this->call('config:clear');
        $this->call('config:cache');

        $this->call('route:clear');
        $this->call('route:cache');

        $this->call('view:clear');
        $this->call('view:cache');

        $this->call('clear-compiled');
        $this->call('optimize');

        passthru('composer dump-autoload --optimize');

        return self::SUCCESS;
    }
}