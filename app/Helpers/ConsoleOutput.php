<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ConsoleOutput
{
    private static bool $debugMode = false;

    public static function setDebugMode(bool $mode): void
    {
        self::$debugMode = $mode;
    }

    public static function log(string $message, array $context = [], string $level = 'info'): void
    {
        if (!self::$debugMode && $level === 'debug') {
            return;
        }

        // Truncate long messages
        $truncatedMessage = Str::limit($message, 500);
        
        match ($level) {
            'error' => Log::error($truncatedMessage, $context),
            'warning' => Log::warning($truncatedMessage, $context),
            'debug' => Log::debug($truncatedMessage, $context),
            default => Log::info($truncatedMessage, $context)
        };
    }

    /**
     * Convert an array or collection to a formatted console table string
     *
     * @param array|Collection $data The data to format
     * @param array $headers Optional custom headers (defaults to array keys)
     * @param int $maxColWidth Maximum width for each column (default 30)
     * @return string
     */
    public static function arrayToTable($data, array $headers = [], int $maxColWidth = 30): string
    {
        // Convert Collection to array if needed
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        // Convert objects to arrays
        $data = array_map(function($item) {
            return is_object($item) ? (array)$item : $item;
        }, $data);

        // If data is empty, return early
        if (empty($data)) {
            return "Empty dataset\n";
        }

        // Get headers from first row if not provided
        if (empty($headers)) {
            $headers = is_array(reset($data)) ? array_keys(reset($data)) : array_keys($data);
        }

        // Initialize column widths with header lengths
        $colWidths = array_map(function($header) {
            return strlen($header);
        }, $headers);

        // Process data to get maximum column widths and ensure all values are strings
        $rows = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                $row = [$row];
            }
            $processedRow = [];
            foreach ($headers as $i => $header) {
                $value = $row[$header] ?? '';
                $value = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
                $value = Str::limit($value, $maxColWidth);
                $processedRow[] = $value;
                $colWidths[$i] = max($colWidths[$i], mb_strlen($value));
            }
            $rows[] = $processedRow;
        }

        // Build the table
        $output = "\n";
        
        // Add top border
        $output .= '+' . implode('+', array_map(function($width) {
            return str_repeat('-', $width + 2);
        }, $colWidths)) . "+\n";

        // Add headers
        $output .= '|';
        foreach ($headers as $i => $header) {
            $output .= ' ' . str_pad($header, $colWidths[$i]) . ' |';
        }
        $output .= "\n";

        // Add header separator
        $output .= '+' . implode('+', array_map(function($width) {
            return str_repeat('-', $width + 2);
        }, $colWidths)) . "+\n";

        // Add rows
        foreach ($rows as $row) {
            $output .= '|';
            foreach ($row as $i => $value) {
                $output .= ' ' . str_pad($value, $colWidths[$i]) . ' |';
            }
            $output .= "\n";
        }

        // Add bottom border
        $output .= '+' . implode('+', array_map(function($width) {
            return str_repeat('-', $width + 2);
        }, $colWidths)) . "+\n";

        return $output;
    }
}
