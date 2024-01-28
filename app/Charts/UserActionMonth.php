<?php

namespace App\Charts;

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
        return $this->chart->pieChart()
            ->setTitle('Last 30 Days')
            ->setSubtitle('Percentage of weighted chores completed')
            ->addData([40, 60])
            ->setLabels(['Cody Miller', 'Katelyn Miller']);
    }
}
