<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\BarChart;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionPastWeek extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): BarChart
    {
        $records = ChoreLog::getChoreLogsCountByUsersAndWeekday(now()->startOfWeek(), now()->endOfWeek());
        if ($records->count() > 0) {
            $this->hasData = true;
        }
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
            ->setTitle('Current Week Counts')
            ->setSubtitle('Number of chores completed over the current week.')
            ->setXAxis(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        foreach ($userDaysStruct as $userName => $userChoreCount) {
            $chart->addData($userName, $userChoreCount);
        }
        return $chart;
    }
}
