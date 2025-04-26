<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionMonth
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\PieChart
    {
        $weights = [];
        $names = [];
        $records = ChoreLog::getChoreLogsWeightByUsers(now()->subMonth(), now());
        $totalWeight = $records->sum('total_weight');
        foreach ($records as $record) {
            $weights[] = round($record->total_weight / $totalWeight * 100, 2);
            $names[] = $record->name;
        }
        return $this->chart->pieChart()
            ->setTitle('Last 30 Days')
            ->setSubtitle('Percentage of weighted chores completed')
            ->addData($weights)
            ->setLabels($names);
    }
}
