<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Onet
{

    public static function getJobTitles(array $socCodes)
    {
        $alternateTitles = DB::table('onet__alternate_titles')
            ->whereIn('onetsoc_code', $socCodes)
            ->get(['onetsoc_code', 'alternate_title as title'])
            ->groupBy('onetsoc_code')
            ->map(function ($titles) {
                return $titles->pluck('title')->toArray();
            });

        $reportedTitles = DB::table('onet__sample_of_reported_titles')
            ->whereIn('onetsoc_code', $socCodes)
            ->get(['onetsoc_code', 'reported_job_title as title'])
            ->groupBy('onetsoc_code')
            ->map(function ($titles) {
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

    public static function getOnetJobTitleByCode(string $onetSocCode)
    {
        return DB::table('onet__occupation_data')
            ->where('onetsoc_code', $onetSocCode)
            ->value('title');
    }

    public static function getCareerInfo(string $careerTitle): Builder|null
    {
        return DB::table('onet__occupation_data')
            ->select('title', 'description')
            ->where('title', $careerTitle)
            ->first();
    }

    public static function getInterests(string $onetsocCode): Collection
    {
        return DB::table('onet__interests as i')
            ->join('onet__content_model_reference as cmr', 'i.element_id', '=', 'cmr.element_id')
            ->where('i.onetsoc_code', $onetsocCode)
            ->where('i.scale_id', 'OI')
            ->select([
                'cmr.element_name as name',
                'cmr.description as description', 
                'i.data_value as score',
                DB::raw('ROUND(((i.data_value-1) / 6) * 100, 2) as percentage')
            ])
            ->get();
    }

    public static function getOnetJobWeights(string $onetsocCode): Collection
    {
        return DB::table('onet__interests as i')
            ->where('i.onetsoc_code', $onetsocCode)
            ->get(['element_id', 'data_value']);
    }

    public static function getTasks(string $onetsocCode): Collection
    {
        return DB::table('onet__task_statements')
            ->where('onetsoc_code', $onetsocCode)
            ->where('task_type', 'Core')
            ->pluck('task');
    }

    public static function getWorkActivities(string $onetsocCode): Collection
    {
        return DB::table('onet__work_activities as a')
            ->join('onet__content_model_reference as cmr', 'a.element_id', '=', 'cmr.element_id')
            ->where('a.onetsoc_code', $onetsocCode)
            ->where('a.recommend_suppress', '<>', 'Y') // Exclude suppressed rows
            ->where('a.not_relevant', '<>', 'Y')
            ->select(
                'cmr.element_name AS name, cmr.description as description',
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "IM" THEN (a.data_value / 5 * 100) END), 2) AS im_percentage'),
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "LV" THEN (a.data_value / 7 * 100) END), 2) AS lv_percentage')
            )
            ->groupBy('cmr.element_name', 'cmr.description')
            ->orderByDesc('im_percentage') // Order by IM percentage descending
            ->get();
    }

    public static function getDetailedWorkActivities(string $onetsocCode): Collection
    {
        return DB::table('onet__dwa_reference as dwa')
            ->join('onet__work_activities as wa', 'dwa.element_id', '=', 'wa.element_id')
            ->join('onet__content_model_reference as cmr', 'dwa.element_id', '=', 'cmr.element_id')
            ->where('wa.onetsoc_code', $onetsocCode)
            ->where('wa.scale_id', 'IM')
            ->where('wa.not_relevant', '<>', 'Y')
            ->where('wa.recommend_suppress', '<>', 'Y')
            ->select(
                'dwa.dwa_title as name, cmr.description as description',
                DB::raw('ROUND(((wa.data_value-1) / 4) * 100, 2) as im_percentage')
            )
            ->orderByDesc('im_percentage')
            ->get();
    }

    public static function getSalaryInfo(string $onetsocCode): Builder|null
    {
        return DB::table('onet__salary_data')
            ->where('onetsoc_code', $onetsocCode)
            ->first(['median_wage_hourly', 'median_wage_annual']);
    }

    /**
     * Get a list of most relevant work contexts for the given OnetSocCode 
     * @param string $onetsocCode
     * @return Collection
     */
    public static function getWorkContext(string $onetsocCode): Collection
    {

        // work contexts that have 3 categories (schedule (irregular, regular, seasonal) and weekly hours (under 40, 40, more than 50)
        $workContexts_CTP = $results = DB::table('onet__work_context AS wc')
            ->join('onet__content_model_reference AS cmr', 'wc.element_id', '=', 'cmr.element_id')
            ->join('onet__work_context_categories AS wcc', function ($join) {
                $join->on('wc.element_id', '=', 'wcc.element_id')
                    ->on('wc.scale_id', '=', 'wcc.scale_id')
                    ->on('wc.category', '=', 'wcc.category');
            })
            ->where('wc.scale_id', 'CTP')
            ->where('wc.onetsoc_code', $onetsocCode)
            ->select(
                'cmr.element_name AS name',
                DB::raw("GROUP_CONCAT(CONCAT(wc.data_value, '% say: ', wcc.category_description) ORDER BY wc.data_value DESC SEPARATOR ', ') AS context_description")
            )
            ->groupBy('wc.element_id')
            ->orderBy('name')
            ->get();

        // work contexts rated from 1 (not at all frequent) to 5 (very frequent)
        $workContexts_CXP = DB::table('onet__work_context AS wc')
            ->join('onet__content_model_reference AS cmr', 'wc.element_id', '=', 'cmr.element_id')
            ->join('onet__work_context_categories AS wcc', function ($join) {
                $join->on('wc.element_id', '=', 'wcc.element_id')
                    ->on('wc.scale_id', '=', 'wcc.scale_id')
                    ->on('wc.category', '=', 'wcc.category');
            })
            ->where('wc.scale_id', 'CXP')
            ->whereIn('wc.category', [4, 5])
            ->where('wc.onetsoc_code', $onetsocCode)
            ->where('wc.data_value', '>', 0)
            ->select(
                'cmr.element_name AS name',
                DB::raw('SUM(wc.data_value) AS total_data_value'),
                DB::raw('CONCAT(SUM(wc.data_value), " say ", MAX(wcc.category_description)) AS context_description')
            )
            ->groupBy('wc.element_id')
            ->orderBy('total_data_value', 'DESC')
            ->get();

        return $workContexts_CTP->merge($workContexts_CXP);
    }

    /**
     * Get a list of skills required for the given OnetSocCode
     * sorted descending by the importance level (IM)
     * @param string $onetsocCode
     * @return Collection
     */
    public static function getSkills(string $onetsocCode): Collection
    {
        return DB::table('onet__skills as a')
            ->join('onet__content_model_reference as cmr', 'a.element_id', '=', 'cmr.element_id')
            ->where('a.onetsoc_code', $onetsocCode)
            ->where('a.recommend_suppress', '<>', 'Y')
            ->where('a.not_relevant', '<>', 'Y')
            ->select(
                'cmr.element_name AS name',
                'cmr.description as description',
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "IM" THEN (a.data_value / 5 * 100) END), 2) AS im_percentage'),
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "LV" THEN (a.data_value / 7 * 100) END), 2) AS lv_percentage')
            )
            ->groupBy('cmr.element_name', 'cmr.description')
            ->orderByDesc('im_percentage')
            ->get();
    }

    /**
     * Get a concatenated list of skills required for the given OnetSocCode
     * sorted descending by the importance level (IM)
     * @param string $onetsocCode
     * @param string $separator
     * @return string
     */
    public static function getSkillsAsString(string $onetsocCode, string $separator = ','): string
    {
        $skills = self::getSkills($onetsocCode);
        return $skills->map(function ($skill) {
            return $skill->name;
        })->implode($separator);
    }

    /**
     * Get a list of abilities required for the given OnetSocCode
     * sorted descending by the importance level (IM)
     * @param string $onetsocCode
     * @return Collection
     */
    public static function getAbilities(string $onetsocCode): Collection
    {
        return DB::table('onet__abilities as a')
            ->join('onet__content_model_reference as cmr', 'a.element_id', '=', 'cmr.element_id')
            ->where('a.onetsoc_code', $onetsocCode)
            ->where('a.recommend_suppress', '<>', 'Y') 
            ->where('a.not_relevant', '<>', 'Y')
            ->select(
                'cmr.element_name AS name',
                'cmr.description as description',
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "IM" THEN (a.data_value / 5 * 100) END), 2) AS im_percentage'),
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "LV" THEN (a.data_value / 7 * 100) END), 2) AS lv_percentage')
            )
            ->groupBy('cmr.element_name', 'cmr.description')
            ->orderByDesc('im_percentage') // Order by IM percentage descending
            ->get();
    }

    /**
     * Get a concatenated string of abilities required for the given OnetSocCode
     * sorted descending by the importance level (IM)
     * @param string $onetsocCode
     * @param string $separator
     * @return string
     */
    public static function getAbilitiesAsString(string $onetsocCode, string $separator = ','): string
    {
        $abilities = self::getAbilities($onetsocCode);
        return $abilities->map(function ($ability) {
            return $ability->name;
        })->implode($separator);
    }

    public static function getWorkValues(string $onetsocCode): Collection
    {

        return DB::table('onet__work_values as i')
            ->join('onet__content_model_reference as cmr', 'i.element_id', '=', 'cmr.element_id')
            ->where('i.onetsoc_code', $onetsocCode)
            ->where('i.scale_id', 'EX')
            ->select([
                'cmr.element_name as name',
                'cmr.description as description', 
                'i.data_value as score',
                DB::raw('ROUND(((i.data_value-1) / 6) * 100, 2) as percentage')
            ])
            ->get();

    }

    public static function getWorkStyles(string $onetsocCode): Collection
    {
        return DB::table('onet__work_styles as ws')
            ->join('onet__content_model_reference as cmr', 'ws.element_id', '=', 'cmr.element_id')
            ->where('ws.onetsoc_code', $onetsocCode)
            ->where('ws.scale_id', 'IM')
            ->select(['cmr.element_name as name', 'cmr.description as description', 'ws.data_value as importance', DB::raw('ROUND(((ws.data_value-1) / 4) * 100, 2) as importance_percentage')])
            ->get();
    }

    public static function getProjectedGrowthRate(string $careerTitle)
    {
        return null;
    }

    public static function getRelatedOccupations(string $onetsocCode): Collection
    {
        return DB::table('onet__related_occupations as ro')
            ->join('onet__occupation_data as o2', 'ro.related_onetsoc_code', '=', 'o2.onetsoc_code')
            ->where('ro.onetsoc_code', $onetsocCode)
            ->select('o2.title as title', 'o2.onetsoc_code as onetsoc_code')
            ->orderBy('ro.related_index', 'ASC')
            ->get();
    }

    public static function getKnowledge(string $onetsocCode): Collection
    {
        return DB::table('onet__knowledge as a')
            ->join('onet__content_model_reference as cmr', 'a.element_id', '=', 'cmr.element_id')
            ->where('a.onetsoc_code', $onetsocCode)
            ->where('a.recommend_suppress', '<>', 'Y') 
            ->where('a.not_relevant', '<>', 'Y')
            ->select(
                'cmr.element_name AS name',
                'cmr.description as description',
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "IM" THEN (a.data_value / 5 * 100) END), 2) AS im_percentage'),
                DB::raw('ROUND(MAX(CASE WHEN a.scale_id = "LV" THEN (a.data_value / 7 * 100) END), 2) AS lv_percentage')
            )
            ->groupBy('cmr.element_name', 'cmr.description')
            ->orderByDesc('im_percentage') // Order by IM percentage descending
            ->get();
    }

    /**
     * Get a list of formal education requirements (RL) for the given OnetSocCode
     * sorted descending by the percentage of people who believe it is required
     * @param string $onetsocCode
     * @return Collection
     */
    public static function getEducation(string $onetsocCode): Collection
    {
        return DB::table('onet__education_training_experience AS e')
            ->join('onet__ete_categories AS ec', function ($join) {
                $join->on('e.category', '=', 'ec.category')
                    ->where('ec.scale_id', '=', 'RL');
            })
            ->where('e.onetsoc_code', $onetsocCode)
            ->where('e.scale_id', '=', 'RL')
            ->where('e.data_value', '>', 0)
            ->select(
                'ec.category_description AS education_requirement',
                'e.data_value AS percentage'
            )
            ->orderByDesc('e.data_value')
            ->get();
    }

}
