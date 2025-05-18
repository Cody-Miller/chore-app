<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use ArielMejiaDev\LarapexCharts\PieChart;

class UserActionYear
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): PieChart
    {
        $weights = [];
        $names = [];
        $records = ChoreLog::getChoreLogsWeightByUsers(now()->subYear(), now());
        $totalWeight = $records->sum('total_weight');
        foreach ($records as $record) {
            $weights[] = round($record->total_weight / $totalWeight * 100, 2);
            $names[] = $record->name;
        }
        return $this->chart->pieChart()
            ->setTitle('Last 365 Days')
            ->setSubtitle('Percentage of weighted chores completed')
            ->addData($weights)
            ->setLabels($names);
    }
}
