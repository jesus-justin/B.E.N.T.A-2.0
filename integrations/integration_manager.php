<?php
/**
 * Integration Manager - Central hub for all integrations
 * Manages and coordinates all integration services
 */

// Include all integration services
require_once __DIR__ . '/analytics/chart_service.php';
require_once __DIR__ . '/analytics/export_service.php';
require_once __DIR__ . '/analytics/notification_service.php';

class IntegrationManager {
    private $pdo;
    private $chartService;
    private $exportService;
    private $notificationService;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeServices();
    }
    
    /**
     * Initialize all integration services
     */
    private function initializeServices() {
        $this->chartService = new ChartService($this->pdo);
        $this->exportService = new ExportService($this->pdo);
        $this->notificationService = new NotificationService($this->pdo);
    }
    
    /**
     * Get chart service instance
     */
    public function charts() {
        return $this->chartService;
    }
    
    /**
     * Get export service instance
     */
    public function exports() {
        return $this->exportService;
    }
    
    /**
     * Get notification service instance
     */
    public function notifications() {
        return $this->notificationService;
    }
    
    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData($userId, $period = 'monthly') {
        // Determine date range based on period
        $dateRanges = $this->getDateRangeForPeriod($period);
        
        return [
            'kpis' => $this->getKPIData($userId, $dateRanges['from'], $dateRanges['to']),
            'charts' => $this->chartService->getChartData($userId, $dateRanges['from'], $dateRanges['to'], $period),
            'insights' => $this->chartService->generateBusinessInsights($userId, $dateRanges['from'], $dateRanges['to']),
            'recommendations' => $this->chartService->generateRecommendations($userId, $dateRanges['from'], $dateRanges['to']),
            'alerts' => $this->notificationService->getFinancialAlerts($userId),
            'smart_insights' => $this->notificationService->generateSmartInsights($userId)
        ];
    }
    
    /**
     * Get KPI data for dashboard
     */
    private function getKPIData($userId, $dateFrom, $dateTo) {
        // Current period totals
        $currentIncome = $this->getTotalIncome($userId, $dateFrom, $dateTo);
        $currentExpenses = $this->getTotalExpenses($userId, $dateFrom, $dateTo);
        $currentNet = $currentIncome - $currentExpenses;
        $currentSavingsRate = $currentIncome > 0 ? ($currentNet / $currentIncome) * 100 : 0;
        
        // Previous period for comparison
        $periodDiff = (new DateTime($dateTo))->diff(new DateTime($dateFrom))->days;
        $prevDateTo = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
        $prevDateFrom = date('Y-m-d', strtotime($prevDateTo . ' -' . $periodDiff . ' days'));
        
        $prevIncome = $this->getTotalIncome($userId, $prevDateFrom, $prevDateTo);
        $prevExpenses = $this->getTotalExpenses($userId, $prevDateFrom, $prevDateTo);
        $prevNet = $prevIncome - $prevExpenses;
        $prevSavingsRate = $prevIncome > 0 ? ($prevNet / $prevIncome) * 100 : 0;
        
        // Calculate changes
        $incomeChange = $prevIncome > 0 ? (($currentIncome - $prevIncome) / $prevIncome) * 100 : 0;
        $expenseChange = $prevExpenses > 0 ? (($currentExpenses - $prevExpenses) / $prevExpenses) * 100 : 0;
        $netChange = $prevNet != 0 ? (($currentNet - $prevNet) / abs($prevNet)) * 100 : 0;
        $savingsRateChange = $prevSavingsRate != 0 ? $currentSavingsRate - $prevSavingsRate : 0;
        
        return [
            'total_income' => [
                'value' => $currentIncome,
                'change' => $incomeChange,
                'trend' => $incomeChange >= 0 ? 'up' : 'down',
                'formatted_value' => '₱' . number_format($currentIncome, 2)
            ],
            'total_expenses' => [
                'value' => $currentExpenses,
                'change' => $expenseChange,
                'trend' => $expenseChange <= 0 ? 'up' : 'down', // Lower expenses = positive trend
                'formatted_value' => '₱' . number_format($currentExpenses, 2)
            ],
            'net_income' => [
                'value' => $currentNet,
                'change' => $netChange,
                'trend' => $currentNet >= 0 ? 'up' : 'down',
                'formatted_value' => '₱' . number_format($currentNet, 2)
            ],
            'savings_rate' => [
                'value' => $currentSavingsRate,
                'change' => $savingsRateChange,
                'trend' => $savingsRateChange >= 0 ? 'up' : 'down',
                'formatted_value' => number_format($currentSavingsRate, 1) . '%'
            ]
        ];
    }
    
    /**
     * Get date range for different periods
     */
    private function getDateRangeForPeriod($period) {
        $today = new DateTime();
        
        switch ($period) {
            case 'daily':
                return [
                    'from' => $today->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'weekly':
                $start = clone $today;
                $start->modify('monday this week');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'bi-weekly':
                $start = clone $today;
                $start->modify('-2 weeks');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'monthly':
                $start = clone $today;
                $start->modify('first day of this month');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'quarterly':
                $quarter = ceil($today->format('n') / 3);
                $start = new DateTime($today->format('Y') . '-' . (($quarter - 1) * 3 + 1) . '-01');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'bi-yearly':
                $half = $today->format('n') <= 6 ? 1 : 2;
                $start = new DateTime($today->format('Y') . '-' . ($half == 1 ? '01' : '07') . '-01');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            case 'yearly':
                $start = new DateTime($today->format('Y') . '-01-01');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
            
            default:
                // Default to last 6 months
                $start = clone $today;
                $start->modify('-6 months');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $today->format('Y-m-d')
                ];
        }
    }
    
    /**
     * Handle export requests
     */
    public function handleExport($userId, $format, $type, $dateFrom, $dateTo) {
        switch ($format) {
            case 'csv':
                $this->exportService->exportToCSV($userId, $dateFrom, $dateTo, $type);
                break;
            case 'pdf':
                $this->exportService->exportToPDF($userId, $dateFrom, $dateTo, $type);
                break;
            default:
                throw new InvalidArgumentException("Unsupported export format: $format");
        }
    }
    
    /**
     * Get comprehensive analytics for reports page
     */
    public function getAnalyticsData($userId, $dateFrom, $dateTo, $period = 'monthly') {
        return [
            'summary' => $this->getKPIData($userId, $dateFrom, $dateTo),
            'chart_data' => $this->chartService->getChartData($userId, $dateFrom, $dateTo, $period),
            'chart_config' => $this->chartService->getChartConfig(),
            'insights' => $this->chartService->generateBusinessInsights($userId, $dateFrom, $dateTo),
            'recommendations' => $this->chartService->generateRecommendations($userId, $dateFrom, $dateTo),
            'period_info' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'period' => $period,
                'days' => (new DateTime($dateTo))->diff(new DateTime($dateFrom))->days + 1
            ]
        ];
    }
    
    /**
     * Get financial health score
     */
    public function getFinancialHealthScore($userId) {
        $score = 0;
        $maxScore = 100;
        
        // Current month data
        $currentMonth = date('Y-m');
        $income = $this->getMonthlyIncome($userId, $currentMonth);
        $expenses = $this->getMonthlyExpenses($userId, $currentMonth);
        $net = $income - $expenses;
        
        // Savings rate (30 points)
        if ($income > 0) {
            $savingsRate = ($net / $income) * 100;
            if ($savingsRate >= 20) $score += 30;
            elseif ($savingsRate >= 10) $score += 20;
            elseif ($savingsRate >= 5) $score += 10;
            elseif ($savingsRate >= 0) $score += 5;
        }
        
        // Income consistency (25 points)
        $incomeConsistency = $this->getIncomeConsistency($userId);
        $score += min(25, $incomeConsistency * 25);
        
        // Expense control (25 points)
        $expenseControl = $this->getExpenseControl($userId);
        $score += min(25, $expenseControl * 25);
        
        // Debt-to-income ratio (20 points) - simplified version
        if ($income > 0 && $net >= 0) {
            $score += 20; // No debt assumed if net is positive
        } elseif ($income > 0 && $net < 0) {
            $debtRatio = abs($net) / $income;
            if ($debtRatio <= 0.3) $score += 15;
            elseif ($debtRatio <= 0.5) $score += 10;
            elseif ($debtRatio <= 0.7) $score += 5;
        }
        
        return [
            'score' => min($maxScore, $score),
            'grade' => $this->getFinancialGrade($score),
            'recommendations' => $this->getScoreRecommendations($score)
        ];
    }
    
    /**
     * Helper methods
     */
    private function getTotalIncome($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND trx_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        return $stmt->fetchColumn();
    }
    
    private function getTotalExpenses($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        return $stmt->fetchColumn();
    }
    
    private function getMonthlyIncome($userId, $month) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(trx_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        return $stmt->fetchColumn();
    }
    
    private function getMonthlyExpenses($userId, $month) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM expenses 
            WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        return $stmt->fetchColumn();
    }
    
    private function getIncomeConsistency($userId) {
        // Calculate coefficient of variation for income over last 6 months
        $stmt = $this->pdo->prepare("
            SELECT STDDEV(monthly_income) / AVG(monthly_income) as cv
            FROM (
                SELECT DATE_FORMAT(trx_date, '%Y-%m') as month, SUM(amount) as monthly_income
                FROM transactions 
                WHERE user_id = ? AND trx_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(trx_date, '%Y-%m')
                HAVING monthly_income > 0
            ) monthly_data
        ");
        $stmt->execute([$userId]);
        $cv = $stmt->fetchColumn();
        
        // Lower coefficient of variation = higher consistency
        // Return a score between 0 and 1
        return $cv ? max(0, 1 - min(1, $cv)) : 0;
    }
    
    private function getExpenseControl($userId) {
        // Calculate how stable expense patterns are
        $stmt = $this->pdo->prepare("
            SELECT STDDEV(monthly_expense) / AVG(monthly_expense) as cv
            FROM (
                SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as monthly_expense
                FROM expenses 
                WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
                HAVING monthly_expense > 0
            ) monthly_data
        ");
        $stmt->execute([$userId]);
        $cv = $stmt->fetchColumn();
        
        // Lower coefficient of variation = better expense control
        return $cv ? max(0, 1 - min(1, $cv)) : 0;
    }
    
    private function getFinancialGrade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B+';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C+';
        if ($score >= 40) return 'C';
        if ($score >= 30) return 'D+';
        if ($score >= 20) return 'D';
        return 'F';
    }
    
    private function getScoreRecommendations($score) {
        $recommendations = [];
        
        if ($score < 30) {
            $recommendations[] = "Urgent: Create a basic budget and track all expenses";
            $recommendations[] = "Focus on increasing income or drastically reducing expenses";
            $recommendations[] = "Consider seeking financial counseling";
        } elseif ($score < 50) {
            $recommendations[] = "Build an emergency fund covering 1-2 months of expenses";
            $recommendations[] = "Reduce unnecessary spending and increase savings rate";
            $recommendations[] = "Create a debt reduction plan if applicable";
        } elseif ($score < 70) {
            $recommendations[] = "Aim to save 10-15% of your income consistently";
            $recommendations[] = "Diversify your income sources";
            $recommendations[] = "Consider investing surplus funds";
        } elseif ($score < 90) {
            $recommendations[] = "Excellent progress! Maintain your savings discipline";
            $recommendations[] = "Explore investment opportunities for long-term growth";
            $recommendations[] = "Consider advanced financial planning strategies";
        } else {
            $recommendations[] = "Outstanding financial health! You're in the top tier";
            $recommendations[] = "Focus on wealth building and advanced investment strategies";
            $recommendations[] = "Consider helping others with their financial journey";
        }
        
        return $recommendations;
    }
}
?>