<?php

namespace App\Helpers;

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
}
