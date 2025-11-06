<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\BarChart;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class ChoreCompletionRate extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($startDate, $endDate): BarChart
    {
        $records = ChoreLog::getChoreCompletionRates($startDate, $endDate);

        if ($records->count() > 0) {
            $this->hasData = true;
        }

        $choreNames = [];
        $completionRates = [];

        foreach ($records as $record) {
            $choreNames[] = $record->name . ' (' . $record->actual_completions . '/' . $record->expected_completions . ')';
            $completionRates[] = round($record->completion_rate, 1);
        }

        return $this->chart->barChart()
            ->setTitle('Chores Behind Schedule')
            ->setSubtitle('Top 10 recurring chores with lowest completion rate vs expected frequency. (actual/expected)')
            ->setXAxis($choreNames)
            ->addData('Completion Rate (%)', $completionRates)
            ->setColors(['#FF6B6B'])
            ->setGrid(true);
    }
}
