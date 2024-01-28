<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class UserActionPastWeek
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\BarChart
    {
        return $this->chart->barChart()
            ->setTitle('Current Week')
            ->setSubtitle('Number of chores complted over the current week.')
            ->addData('Cody Miller', [6, 9, 3, 4, 10, 8, 0])
            ->addData('Katelyn Miller', [7, 3, 8, 2, 6, 4, 1])
            ->setXAxis(['Monday', 'Tuesday', 'Wednesday', 'Thrusday', 'Friday', 'Satuerday', 'Sunday']);
    }
}
