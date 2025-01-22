<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PrinciplesService;
use App\Exceptions\PrinciplesApiException;

class ShowPrinciplesInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'principles:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get information about the current user from the Principles API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = new PrinciplesService();

        try {
            $info = $service->info();
            $this->info('User Information:');
            $this->line(print_r($info, true));
        } catch (PrinciplesApiException $e) {
            $this->error("Error: " . $e->getMessage());
        }

        return 0;
    }
} 