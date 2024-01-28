<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionYear
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\PieChart
    {
        return $this->chart->pieChart()
            ->setTitle('Last 365 Days')
            ->setSubtitle('Percentage of weighted chores completed')
            ->addData([76, 10])
            ->setLabels(['Cody Miller', 'Katelyn Miller']);
    }
}
