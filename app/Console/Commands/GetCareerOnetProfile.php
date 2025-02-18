<?php

namespace App\Console\Commands;

use App\Helpers\ConsoleOutput;
use App\Helpers\Onet;
use Illuminate\Console\Command;

class GetCareerOnetProfile extends Command
{
    protected $signature = 'app:get-career-onet-profile {onetsoc_code} {--sections=all : Comma-separated list of sections to display}';
    protected $description = 'Get detailed O*NET profile for a career by its SOC code';

    private array $availableSections = [
        'tasks',
        'work_activities',
        'detailed_work_activities',
        'work_context',
        'skills',
        'abilities',
        'work_values',
        'work_styles',
        'knowledge',
        'education',
        'interests',
        'related'
    ];

    public function handle(): void
    {
        $socCode = $this->argument('onetsoc_code');
        $requestedSections = $this->option('sections');
        
        $sections = $requestedSections === 'all' 
            ? $this->availableSections 
            : array_intersect(
                explode(',', str_replace(' ', '', $requestedSections)),
                $this->availableSections
            );

        $title = Onet::getOnetJobTitleByCode($socCode);
        $this->info("\nCareer Profile for: {$title} ({$socCode})");
        $this->line(str_repeat('-', 50));

        // Get and display only requested sections
        foreach ($sections as $section) {
            switch ($section) {
                case 'work_context':
                    $data = Onet::getWorkContext($socCode);
                    $this->info("\nWork Context:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'tasks':
                    $data = Onet::getTasks($socCode);
                    $this->info("\nTasks:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'work_activities':
                    $data = Onet::getWorkActivities($socCode);
                    $this->info("\nWork Activities:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'detailed_work_activities':
                    $data = Onet::getDetailedWorkActivities($socCode);
                    $this->info("\nDetailed Work Activities:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'skills':
                    $data = Onet::getSkills($socCode);
                    $this->info("\nSkills:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'abilities':
                    $data = Onet::getAbilities($socCode);
                    $this->info("\nAbilities:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'work_values':
                    $data = Onet::getWorkValues($socCode);
                    $this->info("\nWork Values:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'work_styles':
                    $data = Onet::getWorkStyles($socCode);
                    $this->info("\nWork Styles:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'knowledge':
                    $data = Onet::getKnowledge($socCode);
                    $this->info("\nKnowledge Areas:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'education':
                    $data = Onet::getEducation($socCode);
                    $this->info("\nEducation Requirements:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'interests':
                    $data = Onet::getInterests($socCode);
                    $this->info("\nInterests:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;

                case 'related':
                    $data = Onet::getRelatedOccupations($socCode);
                    $this->info("\nRelated Occupations:");
                    $this->line(ConsoleOutput::arrayToTable($data));
                    break;
            }
        }
    }
}
