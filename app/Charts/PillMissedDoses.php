<?php

namespace App\Charts;

use App\Models\PillLog;
use ArielMejiaDev\LarapexCharts\BarChart;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;

class PillMissedDoses extends Charts
{
    protected LarapexChart $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($startDate = null, $endDate = null): BarChart
    {
        // Default to last 1 month if no dates provided
        if (! $startDate || ! $endDate) {
            $endDate = now()->subDay(); // Yesterday (today isn't complete yet)
            $startDate = now()->subMonths();
        }

        $records = PillLog::getMissedDosesByPill($startDate, $endDate);

        if ($records->count() > 0) {
            $this->hasData = true;
        }

        // Group by pill and prepare data structure
        // pillKey => [date => missedCount]
        $pillDatesStruct = [];
        $dateLabels = [];

        // Generate all dates in range for X-axis
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateLabels[] = $date->format('M d');
        }

        // Initialize structure for each unique pill/pet combo
        foreach ($records as $record) {
            $key = $record['pill_name'].' ('.$record['pet_name'].')';
            if (! isset($pillDatesStruct[$key])) {
                $pillDatesStruct[$key] = array_fill(0, count($dateLabels), 0);
            }
        }

        // Populate the data
        foreach ($records as $record) {
            $key = $record['pill_name'].' ('.$record['pet_name'].')';
            $dateIndex = array_search(
                Carbon::parse($record['date'])->format('M d'),
                $dateLabels
            );
            if ($dateIndex !== false) {
                $pillDatesStruct[$key][$dateIndex] = $record['missed_count'];
            }
        }

        $dateRange = $startDate->format('M d').' - '.$endDate->format('M d, Y');
        $chart = $this->chart->barChart()
            ->setTitle('Missed Doses by Medication')
            ->setSubtitle("Scheduled doses that were not administered: {$dateRange}")
            ->setXAxis($dateLabels);

        foreach ($pillDatesStruct as $pillName => $missedCounts) {
            $chart->addData($pillName, array_values($missedCounts));
        }

        return $chart;
    }
}
