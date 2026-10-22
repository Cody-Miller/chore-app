<?php

namespace App\Charts;

use App\Models\ChoreLog;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use ArielMejiaDev\LarapexCharts\PieChart;

class UserActionMonth extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($startDate = null, $endDate = null): PieChart
    {
        // Default to last 30 days if no dates provided
        if (!$startDate || !$endDate) {
            $startDate = now()->subMonth();
            $endDate = now();
        }

        $weights = [];
        $names = [];
        $records = ChoreLog::getChoreLogsWeightByUsers($startDate, $endDate);
        // Pie computes the percentages itself, raw weights keep the tooltips meaningful
        if ($records->sum('total_weight') > 0) {
            $this->hasData = true;
        }
        foreach ($records as $record) {
            $weights[] = round((float) $record->total_weight, 1);
            $names[] = $record->name;
        }
        $dateRange = $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
        return $this->chart->pieChart()
            ->setTitle('User Contribution Breakdown')
            ->setSubtitle("Percentage of weighted chores completed: {$dateRange}")
            ->addData($weights)
            ->setLabels($names);
    }
}
