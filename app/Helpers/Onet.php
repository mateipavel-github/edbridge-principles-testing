<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Onet
{

    public static function getJobTitles(array $socCodes) {
        $alternateTitles = DB::table('onet__alternate_titles')
            ->whereIn('onetsoc_code', $socCodes)
            ->get(['onetsoc_code', 'alternate_title as title'])
            ->groupBy('onetsoc_code')
            ->map(function($titles) {
                return $titles->pluck('title')->toArray();
            });

        $reportedTitles = DB::table('onet__sample_of_reported_titles')
            ->whereIn('onetsoc_code', $socCodes)
            ->get(['onetsoc_code', 'reported_job_title as title'])
            ->groupBy('onetsoc_code')
            ->map(function($titles) {
                return $titles->pluck('title')->toArray();
            });

        $result = [];
        foreach ($socCodes as $socCode) {
            $result[$socCode] = array_unique(
                array_merge(
                    $alternateTitles[$socCode] ?? [],
                    $reportedTitles[$socCode] ?? []
                )
            );
        }

        return $result;
    }

    public static function getOnetSocCode(string $careerTitle)
    {
        return DB::table('onet__occupation_data')
            ->where('title', $careerTitle)
            ->value('onetsoc_code');
    }

    public static function getCareerInfo(string $careerTitle): Builder|null
    {
        return DB::table('onet__occupation_data')
            ->select('title', 'description')
            ->where('title', $careerTitle)
            ->first();
    }

    public static function getInterests(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__interests as i')
            ->join('onet__content_model_reference as cmr', 'i.element_id', '=', 'cmr.element_id')
            ->where('i.onetsoc_code', $onetsocCode)
            ->get(['cmr.element_name as interest_title', 'cmr.description as interest_description']);
    }

    public static function getTasks(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__task_statements')
            ->where('onetsoc_code', $onetsocCode)
            ->pluck('task');
    }

    public static function getWorkActivities(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__work_activities as wa')
            ->join('onet__content_model_reference as cmr', 'wa.element_id', '=', 'cmr.element_id')
            ->where('wa.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getDetailedWorkActivities(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__dwa_reference')
            ->whereIn('element_id', function ($query) use ($onetsocCode) {
                $query->select('element_id')
                    ->from('onet__work_activities')
                    ->where('onetsoc_code', $onetsocCode);
            })
            ->pluck('dwa_title');
    }

    public static function getSalaryInfo(string $careerTitle): Builder|null
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__salary_data')
            ->where('onetsoc_code', $onetsocCode)
            ->first(['median_wage_hourly', 'median_wage_annual']);
    }

    public static function getWorkContext(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__work_context as wc')
            ->join('onet__content_model_reference as cmr', 'wc.element_id', '=', 'cmr.element_id')
            ->where('wc.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getSkills(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__skills as s')
            ->join('onet__content_model_reference as cmr', 's.element_id', '=', 'cmr.element_id')
            ->where('s.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getAbilities(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__abilities as a')
            ->join('onet__content_model_reference as cmr', 'a.element_id', '=', 'cmr.element_id')
            ->where('a.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getWorkValues(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__work_values as wv')
            ->join('onet__content_model_reference as cmr', 'wv.element_id', '=', 'cmr.element_id')
            ->where('wv.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getWorkStyles(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__work_styles as ws')
            ->join('onet__content_model_reference as cmr', 'ws.element_id', '=', 'cmr.element_id')
            ->where('ws.onetsoc_code', $onetsocCode)
            ->pluck('cmr.element_name')
            ->unique()
            ->values();
    }

    public static function getProjectedGrowthRate(string $careerTitle)
    {
        return DB::table('onet__occupation_data')
            ->where('title', $careerTitle)
            ->value('growth_rate');
    }

    public static function getRelatedOccupations(string $careerTitle): Collection
    {
        $onetsocCode = self::getOnetSocCode($careerTitle);

        return DB::table('onet__related_occupations as ro')
            ->join('onet__occupation_data as o2', 'ro.related_onetsoc_code', '=', 'o2.onetsoc_code')
            ->where('ro.onetsoc_code', $onetsocCode)
            ->pluck('o2.title');
    }

    public static function getKnowledge(string $careerTitle): Collection
    {
        return DB::table('onet__knowledge AS k')
            ->join('onet__occupation_data AS o', 'k.onetsoc_code', '=', 'o.onetsoc_code')
            ->join('onet__content_model_reference AS cmr', 'k.element_id', '=', 'cmr.element_id')
            ->where('o.title', 'LIKE', "%$careerTitle%")
            ->select('cmr.element_name AS knowledge_area', 'k.data_value AS importance')
            ->orderByDesc('k.data_value')
            ->get();
    }

    public static function getEducation(string $careerTitle): Collection
    {
        return DB::table('onet__education_training_experience AS e')
            ->join('onet__occupation_data AS o', 'e.onetsoc_code', '=', 'o.onetsoc_code')
            ->join('onet__ete_categories AS ec', 'e.category', '=', 'ec.category')
            ->join(
                DB::raw('(SELECT onetsoc_code, SUM(data_value) AS total_value FROM onet__education_training_experience GROUP BY onetsoc_code) AS t'),
                'e.onetsoc_code', '=', 't.onetsoc_code'
            )
            ->where('o.title', 'LIKE', "%$careerTitle%")
            ->whereNot(function ($query) {
                $query->where('ec.category_description', 'LIKE', "%Over % years%")
                    ->orWhere('ec.category_description', 'LIKE', "%Over % months%");
            }) // Exclude both "Over X years" and "Over X months"
            ->select(
                'ec.category_description AS education_requirement',
                DB::raw('ROUND(SUM(e.data_value) / MAX(t.total_value) * 100, 2) AS percentage')
            )
            ->groupBy('ec.category_description')
            ->orderByDesc('percentage')
            ->get();
    }

}
