<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\PieChart;

class UserActionYear extends UserActionMonth
{
    public function build($startDate = null, $endDate = null): PieChart
    {
        $chart = parent::build(now()->subYear(), now());
        $chart->setTitle('Last 365 Days')
            ->setSubtitle('Percentage of weighted chores completed');

        return $chart;
    }
}
