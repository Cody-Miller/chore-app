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

    public function build($startDate = null, $endDate = null): BarChart
    {
        // Default to current week if no dates provided
        if (!$startDate || !$endDate) {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        }

        $records = ChoreLog::getChoreLogsCountByUsersAndWeekday($startDate, $endDate);
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
        $dateRange = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
        $chart = $this->chart->barChart()
            ->setTitle('Week Counts')
            ->setSubtitle("Number of chores completed: {$dateRange}")
            ->setXAxis(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        foreach ($userDaysStruct as $userName => $userChoreCount) {
            $chart->addData($userName, $userChoreCount);
        }
        return $chart;
    }
}
