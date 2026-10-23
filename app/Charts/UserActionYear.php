<?php

namespace App\Charts;

use App\Support\Timezone;
use ArielMejiaDev\LarapexCharts\PieChart;

class UserActionYear extends UserActionMonth
{
    public function build($startDate = null, $endDate = null): PieChart
    {
        $chart = parent::build(now(Timezone::viewer())->subYear(), now(Timezone::viewer()));
        $chart->setTitle('Last 365 Days')
            ->setSubtitle('Percentage of weighted chores completed');

        return $chart;
    }
}
