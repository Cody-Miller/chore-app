<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionPastWeek
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\BarChart
    {
        $records = ChoreLog::getChoreLogsCountByUsers(now()->subWeek(), now());
        $userDaysStruct = [];
        // Prep the data to 0
        foreach ($records as $record) {
            for ($i = 0; $i < 7; $i++) {
                $userDaysStruct[$record->name][$i] = 0;
            }
        }
        foreach ($records as $record) {
            for ($i = 0; $i < 7; $i++) {
                if ($record->day == $i) {
                    $userDaysStruct[$record->name][$i] = $record->chore_count ?? 0;
                }
            }
        }
        $chart = $this->chart->barChart()
            ->setTitle('Current Week')
            ->setSubtitle('Number of chores complted over the current week.')
            ->setXAxis(['Monday', 'Tuesday', 'Wednesday', 'Thrusday', 'Friday', 'Satuerday', 'Sunday']);
        foreach ($userDaysStruct as $userName => $userChoreCount) {
            $chart->addData($userName, $userChoreCount);
        }
        return $chart;
    }
}
