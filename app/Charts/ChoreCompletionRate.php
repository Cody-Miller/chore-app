<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\HorizontalBar;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class ChoreCompletionRate extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($startDate, $endDate): HorizontalBar
    {
        $records = ChoreLog::getChoreCompletionRates($startDate, $endDate);

        if ($records->count() > 0) {
            $this->hasData = true;
        }

        $choreNames = [];
        $completionRates = [];

        foreach ($records as $record) {
            $choreNames[] = $record->name;
            // Target marker at 100% keeps the axis scaled to the expected rate
            $completionRates[] = [
                'x' => $record->name,
                'y' => round($record->completion_rate, 1),
                'goals' => [['name' => 'Expected', 'value' => 100, 'strokeColor' => '#775dd0', 'strokeWidth' => 3]],
            ];
        }

        return $this->chart->horizontalBarChart()
            ->setTitle('Chores Behind Schedule')
            ->setSubtitle('Recurring chores below their expected completion rate.')
            ->setXAxis($choreNames)
            ->addData($completionRates, 'Completion Rate (%)')
            ->setColors(['#FF6B6B'])
            ->setDataLabels(true)
            ->setGrid(true);
    }
}
