<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\BarChart;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionPastWeekWeighted
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): BarChart
    {
        $records = ChoreLog::getChoreLogsWeightByUsersAndWeekday(now()->startOfWeek(), now()->endOfWeek());
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
                    $userDaysStruct[$record->name][$i] = $record->total_weight ?? 0;
                }
            }
        }
        $chart = $this->chart->barChart()
            ->setTitle('Current Week Weighted')
            ->setSubtitle('Total of weight from chores completed over the current week.')
            ->setXAxis(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        foreach ($userDaysStruct as $userName => $userWeightCount) {
            $chart->addData($userName, $userWeightCount);
        }
        return $chart;
    }
}
