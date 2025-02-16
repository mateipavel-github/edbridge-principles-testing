<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DataTransformer
{
    /**
     * Extracts specific keys from a nested array and formats them into a new structure.
     *
     * @param array $data The input data.
     * @param string $sourceKey The key to extract values from (e.g., "ppmScore").
     * @param string $targetKey The key to use in the transformed array (e.g., "ea_").
     * @param string $valueKey The key to extract from each nested array (e.g., "rawScore").
     * @return array The transformed array.
     */
    public static function extractNestedValues(array $data, string $sourceKey, string $targetKey, string $valueKey): array
    {
        $transformedData = [];

        if (isset($data[$sourceKey])) {
            foreach ($data[$sourceKey] as $key => $values) {
                if (isset($values[$valueKey])) {
                    $transformedData[$targetKey][$key] = strval($values[$valueKey]); // Convert to string
                }
            }
        }

        return $transformedData;
    }

    public static function transformRecords($records): array
    {
        // Map ONET element IDs to your RIASEC labels
        $mapping = [
            '1.B.1.a' => 'doing',      // Realistic
            '1.B.1.b' => 'thinking',   // Investigative
            '1.B.1.c' => 'creating',   // Artistic
            '1.B.1.d' => 'engaging',   // Social
            '1.B.1.e' => 'persuading', // Enterprising
            '1.B.1.f' => 'organizing', // Conventional
        ];

        // Prepare the default output structure
        $output = [
            'occupationWeightings' => [
                'ea_' => [
                    'doing'      => null,
                    'thinking'   => null,
                    'creating'   => null,
                    'engaging'   => null,
                    'persuading' => null,
                    'organizing' => null,
                ],
            ],
        ];

        // If $records is an array, convert to a collection for consistency
        if (is_array($records)) {
            $records = collect($records);
        }

        // Loop through each record and map the element_id to the RIASEC label
        foreach ($records as $record) {
            if (isset($record->element_id, $record->data_value) && isset($mapping[$record->element_id])) {
                $riasecKey = $mapping[$record->element_id];
                // Convert to string if you want string output
                $output['occupationWeightings']['ea_'][$riasecKey] = (string) $record->data_value;
            }
        }

        Log::info("Job weights", $output);

        return $output;
    }
}
