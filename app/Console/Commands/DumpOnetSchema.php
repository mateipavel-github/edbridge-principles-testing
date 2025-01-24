<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DumpOnetSchema extends Command
{
    protected $signature = 'app:dump-onet-schema {output_file?} {--prefix=onet__}';
    protected $description = 'Dump MySQL schema for tables starting with specified prefix';

    public function handle()
    {
        $prefix = $this->option('prefix');
        $outputFile = $this->argument('output_file');

        if (!$outputFile) {
            $timestamp = date('Y-m-d_His');
            $outputFile = storage_path("app/schema_dumps/onet_schema_{$timestamp}.sql");
        }

        // Ensure the directory exists
        $directory = dirname($outputFile);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        // Get all tables with the specified prefix
        $tables = DB::select("SHOW TABLES LIKE '{$prefix}%'");
        
        if (empty($tables)) {
            $this->error("No tables found with prefix '{$prefix}'");
            return 1;
        }

        $schemaContent = "-- Generated MySQL Schema Dump\n";
        $schemaContent .= "-- Prefix: {$prefix}\n";
        $schemaContent .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $tableName = current((array)$table);
            
            // Get CREATE TABLE statement
            $createTableQuery = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createStatement = $createTableQuery[0]->{'Create Table'} . ";\n\n";
            
            $schemaContent .= $createStatement;
        }

        // Save to file
        File::put($outputFile, $schemaContent);

        $this->info("Schema dump successfully created at: {$outputFile}");
        $this->info("Total tables exported: " . count($tables));
        
        return 0;
    }
} 