<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportOnetDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-onet-database {file} {--prefix=onet__}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import O*NET database SQL files from a ZIP file into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = storage_path('app/' . $this->argument('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Extracting zip file...');

        $zip = new \ZipArchive;
        if ($zip->open($filePath) === true) {
            $extractPath = storage_path('app/' . pathinfo($filePath, PATHINFO_FILENAME));
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info("Extracted to: {$extractPath}");
        } else {
            $this->error('Failed to open zip file.');
            return 1;
        }

        $sqlFiles = collect(Storage::allFiles(str_replace(storage_path('app/'), '', $extractPath)))
            ->filter(fn($file) => Str::endsWith($file, '.sql'));

        if ($sqlFiles->isEmpty()) {
            $this->error('No SQL files found in the extracted contents.');
            $this->cleanupExtractedFiles($extractPath);
            return 1;
        }


        $prefix = $this->option('prefix');


        $this->dropExistingTables($sqlFiles->toArray(), $prefix);
        $this->importSqlFiles($sqlFiles->toArray(), $prefix);

        $this->info('All SQL files imported successfully.');
        $this->cleanupExtractedFiles($extractPath);

        return 0;
    }

    private function importSqlFiles(array $sqlFiles, string $prefix): void
    {
        $this->info("Found " . count($sqlFiles) . " SQL files. Starting import...");

        // Increase max_allowed_packet for the session
        DB::unprepared('SET GLOBAL max_allowed_packet=1073741824;');

        foreach ($sqlFiles as $file) {
            $this->info("Importing: " . basename($file));

            try {
                $sql = Storage::get($file);
                $sql = $this->prepareSqlStatements($sql, $prefix);

                DB::unprepared($sql);

                $this->info("Successfully imported: " . basename($file));
            } catch (\Exception $e) {
                $this->error("Failed to import " . basename($file) . ": " . $e->getMessage());
            }
        }
    }

    private function prepareSqlStatements(string $sql, string $prefix): string
    {
        // Add prefix to CREATE TABLE statements
        $sql = preg_replace_callback(
            '/CREATE TABLE `?([\w_]+)`?/i', // Match with or without quotes
            fn($matches) => "CREATE TABLE `{$prefix}{$matches[1]}`",
            $sql
        );

        // Add prefix to INSERT INTO statements
        $sql = preg_replace_callback(
            '/INSERT INTO `?([\w_]+)`?/i', // Match with or without quotes
            fn($matches) => "INSERT INTO `{$prefix}{$matches[1]}`",
            $sql
        );

        // Add prefix to FOREIGN KEY REFERENCES
        $sql = preg_replace_callback(
            '/FOREIGN KEY \\((.*?)\\) REFERENCES `?([\w_]+)`?\\((.*?)\\)/i',
            fn($matches) => "FOREIGN KEY ({$matches[1]}) REFERENCES `{$prefix}{$matches[2]}`({$matches[3]})",
            $sql
        );

        return $sql;
    }

    private function dropExistingTables(array $sqlFiles, string $prefix): void
    {
        $this->info("Dropping existing tables...");

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($sqlFiles as $file) {
            preg_match('/CREATE TABLE\s+[`"]?(\w+)[`"]?/', Storage::get($file), $matches);

            if (isset($matches[1])) {
                $tableName = $matches[1];
                $prefixedTableName = $prefix . $tableName;

                if (Schema::hasTable($tableName)) {
                    DB::unprepared("DROP TABLE IF EXISTS `$tableName`;");
                    $this->info("Dropped table: $tableName");
                }

                if (Schema::hasTable($prefixedTableName)) {
                    DB::unprepared("DROP TABLE IF EXISTS `$prefixedTableName`;");
                    $this->info("Dropped table: $prefixedTableName");
                }
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    private function cleanupExtractedFiles(string $path): void
    {
        $this->info("Cleaning up extracted files...");

        try {
            Storage::deleteDirectory(str_replace(storage_path('app/'), '', $path));
            $this->info("Successfully removed extracted files and folders.");
        } catch (\Exception $e) {
            $this->error("Failed to remove extracted files: " . $e->getMessage());
        }
    }
}
