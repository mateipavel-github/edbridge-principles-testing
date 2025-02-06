<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ImportOnetDatabaseV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-onet-database-v2 {file} {--prefix=onet__}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ONET database SQL files from a zip archive with table prefix';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zipPath = $this->argument('file');
        $prefix = $this->option('prefix');

        // Check direct path first
        if (!file_exists($zipPath)) {
            // Try storage/app path
            $storagePath = storage_path('app/' . $zipPath);
            if (!file_exists($storagePath)) {
                $this->error("Zip file not found in either:\n- {$zipPath}\n- {$storagePath}");
                return 1;
            }
            $zipPath = $storagePath;
        }

        $this->info("Using zip file: {$zipPath}");

        $zip = new ZipArchive;
        $extractPath = storage_path('app/temp/onet_import');
        
        // Create temp directory if it doesn't exist
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
            $this->info("Created directory: {$extractPath}");
        }

        // Open and extract the zip file
        if ($zip->open($zipPath) !== true) {
            $this->error("Failed to open zip file");
            return 1;
        }

        $this->info("Extracting zip file to: {$extractPath}");
        $zip->extractTo($extractPath);
        $zip->close();

        // List contents of the directory
        $this->info("Contents of extract directory:");
        $contents = scandir($extractPath);
        foreach ($contents as $item) {
            if ($item != '.' && $item != '..') {
                $this->line("- {$item}");
            }
        }

        // Get database credentials
        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Recursively find all SQL files
        $files = $this->findSqlFiles($extractPath);
        $totalFiles = count($files);
        $this->info("Found {$totalFiles} SQL files to process");

        // First identify all tables that will be created
        $this->info("Identifying tables to be dropped...");
        $tablesToDrop = $this->identifyTablesToCreate($files, $prefix);
        
        if (!empty($tablesToDrop)) {
            if ($this->confirm('Found ' . count($tablesToDrop) . ' tables to drop. Continue?', true)) {
                // Disable foreign key checks and drop existing tables
                $this->dropExistingTables($tablesToDrop);
            } else {
                $this->info("Operation cancelled.");
                return 1;
            }
        }

        foreach ($files as $index => $sqlFile) {
            $fileName = basename($sqlFile);
            $this->info("Processing file " . ($index + 1) . "/{$totalFiles}: {$fileName}");

            try {
                // First, apply the sed transformation and save to a temporary file
                $tempFile = $extractPath . '/temp_' . $fileName;
                
                // Simpler sed commands, one per line for better readability and reliability
                $sedCommands = [
                    // Basic table operations
                    's/CREATE TABLE \([a-zA-Z0-9_]\+\)/CREATE TABLE ' . $prefix . '\1/g',
                    's/INSERT INTO \([a-zA-Z0-9_]\+\)/INSERT INTO ' . $prefix . '\1/g',
                    
                    // Foreign key references
                    's/REFERENCES \([a-zA-Z0-9_]\+\)/REFERENCES ' . $prefix . '\1/g',
                ];

                $sedCommand = "sed '" . implode('; ', $sedCommands) . "' \"{$sqlFile}\" > \"{$tempFile}\"";
                
                $this->line("Running sed command: " . $sedCommand);
                
                $process = Process::fromShellCommandline($sedCommand);
                $process->setTimeout(null);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException("Sed Error: " . ($process->getErrorOutput() ?: $process->getOutput()));
                }

                // Then import the temporary file
                $mysqlCommand = "mysql --host={$host} --user={$username} --password={$password} {$database} < \"{$tempFile}\" 2>&1";
                
                $process = Process::fromShellCommandline($mysqlCommand);
                $process->setTimeout(null);
                $process->run();

                // Clean up temp file
                unlink($tempFile);

                if (!$process->isSuccessful()) {
                    $error = $process->getErrorOutput() ?: $process->getOutput();
                    throw new \RuntimeException("MySQL Error: " . $error);
                }

                $this->info("Successfully imported {$fileName}");
            } catch (\Exception $e) {
                $this->error("Error processing {$fileName}: " . $e->getMessage());
                if ($this->confirm('Do you want to continue with the remaining files?')) {
                    continue;
                }
                return 1;
            }
        }

        // Clean up
        $this->info("Cleaning up temporary files...");
        $this->deleteDirectory($extractPath);

        $this->info("Import completed successfully!");
        return 0;
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    /**
     * Recursively find all SQL files in a directory and its subdirectories
     *
     * @param string $directory
     * @return array
     */
    private function findSqlFiles(string $directory): array
    {
        $sqlFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'sql') {
                $sqlFiles[] = $file->getPathname();
            }
        }

        // Sort files by their numeric prefix
        sort($sqlFiles, SORT_NATURAL);

        return $sqlFiles;
    }

    /**
     * Identify all tables that will be created from the SQL files
     */
    private function identifyTablesToCreate(array $files, string $prefix): array
    {
        $tables = [];
        $this->info("Starting table identification...");
        
        foreach ($files as $file) {
            $this->info("Scanning file: " . basename($file));
            
            // Use grep to find CREATE TABLE statements and sed to extract table names
            // This pattern will match "CREATE TABLE name" without requiring backticks
            $command = "grep -h 'CREATE TABLE' \"{$file}\" | sed -n 's/.*CREATE TABLE[ ]*\\([a-zA-Z0-9_]*\\).*/\\1/p'";
            $this->line("Running command: " . $command);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $this->line("Command output: " . ($output ?: 'No output'));
                
                if (!empty($output)) {
                    foreach (explode("\n", $output) as $table) {
                        $table = trim($table);
                        $tables[] = $prefix . $table;
                        $this->info("Found table: {$table} (will be prefixed as: {$prefix}{$table})");
                    }
                } else {
                    $this->warn("No CREATE TABLE statements found in " . basename($file));
                }
            } else {
                $this->error("Error running command: " . $process->getErrorOutput());
            }
        }

        $this->info("Total unique tables found: " . count(array_unique($tables)));
        return array_unique($tables);
    }

    /**
     * Drop the existing tables and handle foreign key constraints
     */
    private function dropExistingTables(array $tables): void
    {
        $this->info("Disabling foreign key checks...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $this->info("Dropping table if exists: {$table}");
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
            
            // Verify the table was dropped
            $exists = DB::select("SHOW TABLES LIKE '{$table}'");
            if (!empty($exists)) {
                $this->warn("Warning: Table {$table} still exists after drop attempt");
            }
        }

        $this->info("Re-enabling foreign key checks...");
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
