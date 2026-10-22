<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\HorizontalBar;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class ChoreCompletionFrequency extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($startDate, $endDate): HorizontalBar
    {
        $records = ChoreLog::getChoreCompletionCounts($startDate, $endDate);

        if ($records->count() > 0) {
            $this->hasData = true;
        }

        $choreNames = [];
        $completionCounts = [];

        foreach ($records as $record) {
            $choreNames[] = $record->name;
            $completionCounts[] = $record->completion_count;
        }

        return $this->chart->horizontalBarChart()
            ->setTitle('Most Frequently Completed Chores')
            ->setSubtitle('Top 10 chores by number of completions in the selected period.')
            ->setXAxis($choreNames)
            ->addData($completionCounts, 'Completions');
    }
}
