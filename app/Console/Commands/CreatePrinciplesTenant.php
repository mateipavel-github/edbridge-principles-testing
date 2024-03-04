<?php

namespace App\Console\Commands;

use App\Services\PrinciplesService;
use Illuminate\Console\Command;

class CreatePrinciplesTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'principles:create-tenant {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new tenant for the principles api.';

    /**
     * Execute the console command.
     */
    public function handle(PrinciplesService $principlesService): void
    {
        $name = $this->argument('name');

        $this->info("Creating tenant {$name}...");

        $tenantId = $principlesService->createTenant($name);

        $this->info("Tenant {$name} created. Add the tenant ID to the environment configuration.");
        $this->info("PRINCIPLES_TENANT_ID={$tenantId}");
    }
}

