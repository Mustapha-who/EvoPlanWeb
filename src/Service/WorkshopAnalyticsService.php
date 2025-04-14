<?php

namespace App\Service;

class WorkshopAnalyticsService 
{
    public function calculateAnalytics(array $data): array
    {
        $predictions = [];

        // Workshop Distribution Analysis
        $predictions['Workshop Distribution'] = $this->analyzeWorkshopDistribution($data['workshopsPerEvent']);

        // Session Analysis
        $predictions['Session Analysis'] = $this->analyzeSessionData($data['sessionsData']);

        // Capacity and Attendance Analysis
        $capacityAnalysis = $this->analyzeCapacityAndAttendance(
            $data['capacityData'],
            $data['attendanceRates']
        );
        $predictions['Capacity Analysis'] = $capacityAnalysis;

        // Performance Metrics
        $predictions['Performance Metrics'] = $this->calculatePerformanceMetrics($data);

        return $predictions;
    }

    private function analyzeWorkshopDistribution(array $eventData): array
    {
        $total = array_sum(array_column($eventData, 'workshop_count'));
        $distribution = [];
        $maxWorkshops = 0;
        $mostActiveEvent = '';

        foreach ($eventData as $event) {
            $percentage = ($event['workshop_count'] / $total) * 100;
            $distribution[$event['event_name']] = round($percentage, 2) . '%';

            if ($event['workshop_count'] > $maxWorkshops) {
                $maxWorkshops = $event['workshop_count'];
                $mostActiveEvent = $event['event_name'];
            }
        }

        return [
            'most_active_event' => $mostActiveEvent,
            'workshop_count' => $maxWorkshops . ' workshops',
            'distribution' => $distribution,
            'total_workshops' => $total . ' workshops'
        ];
    }

    private function analyzeSessionData(array $sessionsData): array
    {
        $totalSessions = array_sum(array_column($sessionsData, 'session_count'));
        $avgSessionsPerWorkshop = $totalSessions / count($sessionsData);
        
        // Find workshop with most sessions
        $maxSessions = 0;
        $busyWorkshop = '';
        foreach ($sessionsData as $data) {
            if ($data['session_count'] > $maxSessions) {
                $maxSessions = $data['session_count'];
                $busyWorkshop = $data['workshop_title'];
            }
        }

        return [
            'total_sessions' => $totalSessions . ' sessions',
            'avg_sessions_per_workshop' => round($avgSessionsPerWorkshop, 2) . ' sessions/workshop',
            'most_sessions' => [
                'workshop' => $busyWorkshop,
                'count' => $maxSessions . ' sessions'
            ]
        ];
    }

    private function analyzeCapacityAndAttendance(array $capacityData, array $attendanceRates): array
    {
        $totalCapacity = array_sum(array_column($capacityData, 'workshop_capacity'));
        $totalAttendance = array_sum(array_column($capacityData, 'actual_attendance'));
        $avgUtilization = ($totalAttendance / $totalCapacity) * 100;

        // Find workshops with highest and lowest attendance rates
        $highest = ['rate' => 0, 'workshop' => ''];
        $lowest = ['rate' => 100, 'workshop' => ''];

        foreach ($attendanceRates as $data) {
            if ($data['attendance_rate'] > $highest['rate']) {
                $highest['rate'] = $data['attendance_rate'];
                $highest['workshop'] = $data['title'];
            }
            if ($data['attendance_rate'] < $lowest['rate']) {
                $lowest['rate'] = $data['attendance_rate'];
                $lowest['workshop'] = $data['title'];
            }
        }

        return [
            'total_capacity' => $totalCapacity . ' places',
            'total_attendance' => $totalAttendance . ' attendees',
            'avg_utilization' => round($avgUtilization, 2) . '%',
            'highest_attendance' => [
                'workshop' => $highest['workshop'],
                'rate' => round($highest['rate'], 2) . '%'
            ],
            'lowest_attendance' => [
                'workshop' => $lowest['workshop'],
                'rate' => round($lowest['rate'], 2) . '%'
            ]
        ];
    }

    private function calculatePerformanceMetrics(array $data): array
    {
        // Initialize arrays to store all workshop metrics
        $efficiencyScores = [];
        $successRates = [];
        $totalWorkshops = count($data['attendanceRates']);
        $targetAttendance = 60; // 60 attendance target

        // Calculate metrics for each workshop
        foreach ($data['attendanceRates'] as $workshop) {
            $workshopTitle = $workshop['title'];
            $actualRate = round($workshop['attendance_rate'], 2);
            $participants = $workshop['total_participants'];
            $capacity = $workshop['total_capacity'];

            // Calculate efficiency score (actual attendance vs capacity)
            $efficiencyScore = round(($participants / $capacity) * 100, 2);
            $efficiencyScores[$workshopTitle] = [
                'score' => $efficiencyScore . '%',
                'details' => sprintf(
                    '%d/%d participants (%s%%)',
                    $participants,
                    $capacity,
                    $efficiencyScore
                ),
                'status' => $this->getEfficiencyStatus($efficiencyScore)
            ];

            // Calculate success rate against target
            $successRates[$workshopTitle] = [
                'rate' => $actualRate . '%',
                'achieved' => $actualRate >= $targetAttendance,
                'variance' => round($actualRate - $targetAttendance, 2) . '%',
                'status' => $this->getSuccessStatus($actualRate, $targetAttendance)
            ];
        }

        // Sort arrays by scores (descending)
        uasort($efficiencyScores, fn($a, $b) => (float)$b['score'] <=> (float)$a['score']);
        uasort($successRates, fn($a, $b) => (float)$b['rate'] <=> (float)$a['rate']);

        // Calculate overall metrics
        $totalEfficiency = array_sum(array_map(fn($score) => (float)$score['score'], $efficiencyScores));
        $successfulWorkshops = count(array_filter($successRates, fn($rate) => $rate['achieved']));

        return [
            'efficiency_scores' => $efficiencyScores,
            'success_rates' => $successRates,
            'overall_performance' => [
                'average_efficiency' => round($totalEfficiency / $totalWorkshops, 2) . '%',
                'success_rate' => round(($successfulWorkshops / $totalWorkshops) * 100, 2) . '%',
                'total_workshops' => $totalWorkshops,
                'workshops_on_target' => $successfulWorkshops,
                'workshops_below_target' => $totalWorkshops - $successfulWorkshops
            ]
        ];
    }

    private function getEfficiencyStatus(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Average';
        return 'Needs Improvement';
    }

    private function getSuccessStatus(float $actual, float $target): string
    {
        $variance = $actual - $target;
        if ($variance >= 10) return 'Exceeding Target';
        if ($variance >= 0) return 'On Target';
        if ($variance >= -10) return 'Near Target';
        return 'Below Target';
    }
}