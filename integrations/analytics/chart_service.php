<?php
/**
 * Chart Service - Advanced Analytics Integration
 * Enhanced chart configuration and business insights generation
 * Supports comprehensive filtering and AI-powered insights
 */

class ChartService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate comprehensive chart data with advanced filtering
     */
    public function getChartData($userId, $dateFrom, $dateTo, $period = 'monthly') {
        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => [],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Expenses',
                    'data' => [],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ]
            ]
        ];
        
        // Generate period data based on filter
        switch ($period) {
            case 'daily':
                return $this->getDailyData($userId, $dateFrom, $dateTo, $data);
            case 'weekly':
                return $this->getWeeklyData($userId, $dateFrom, $dateTo, $data);
            case 'bi-weekly':
                return $this->getBiWeeklyData($userId, $dateFrom, $dateTo, $data);
            case 'monthly':
                return $this->getMonthlyData($userId, $dateFrom, $dateTo, $data);
            case 'quarterly':
                return $this->getQuarterlyData($userId, $dateFrom, $dateTo, $data);
            case 'bi-yearly':
                return $this->getBiYearlyData($userId, $dateFrom, $dateTo, $data);
            case 'yearly':
                return $this->getYearlyData($userId, $dateFrom, $dateTo, $data);
            default:
                return $this->getMonthlyData($userId, $dateFrom, $dateTo, $data);
        }
    }
    
    /**
     * Get daily breakdown data
     */
    private function getDailyData($userId, $dateFrom, $dateTo, $data) {
        // Income data
        $incomeStmt = $this->pdo->prepare("
            SELECT DATE(trx_date) as period, COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY DATE(trx_date) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data
        $expenseStmt = $this->pdo->prepare("
            SELECT DATE(expense_date) as period, COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY DATE(expense_date) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate date range
        $period = new DatePeriod(
            new DateTime($dateFrom),
            new DateInterval('P1D'),
            new DateTime($dateTo . ' +1 day')
        );
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $data['labels'][] = $date->format('M j');
            $data['datasets'][0]['data'][] = $incomeData[$dateStr] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$dateStr] ?? 0;
        }
        
        return $data;
    }
    
    /**
     * Get weekly breakdown data
     */
    private function getWeeklyData($userId, $dateFrom, $dateTo, $data) {
        // Income data by week
        $incomeStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(trx_date), '-W', LPAD(WEEK(trx_date, 1), 2, '0')) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), WEEK(trx_date, 1) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data by week
        $expenseStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(expense_date), '-W', LPAD(WEEK(expense_date, 1), 2, '0')) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), WEEK(expense_date, 1) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate weekly periods
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        $start->modify('monday this week');
        
        while ($start <= $end) {
            $weekKey = $start->format('Y-\\WW');
            $data['labels'][] = 'Week ' . $start->format('W, Y');
            $data['datasets'][0]['data'][] = $incomeData[$weekKey] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$weekKey] ?? 0;
            $start->modify('+1 week');
        }
        
        return $data;
    }
    
    /**
     * Get bi-weekly breakdown data
     */
    private function getBiWeeklyData($userId, $dateFrom, $dateTo, $data) {
        // Income data by bi-week
        $incomeStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(trx_date), '-', CEIL(WEEK(trx_date, 1)/2)) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), CEIL(WEEK(trx_date, 1)/2) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data by bi-week
        $expenseStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(expense_date), '-', CEIL(WEEK(expense_date, 1)/2)) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), CEIL(WEEK(expense_date, 1)/2) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate bi-weekly periods
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        $biWeekCount = 1;
        
        while ($start <= $end) {
            $periodKey = $start->format('Y') . '-' . ceil($start->format('W')/2);
            $data['labels'][] = 'Bi-Week ' . $biWeekCount . ', ' . $start->format('Y');
            $data['datasets'][0]['data'][] = $incomeData[$periodKey] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$periodKey] ?? 0;
            $start->modify('+2 weeks');
            $biWeekCount++;
        }
        
        return $data;
    }
    
    /**
     * Get monthly breakdown data (existing logic enhanced)
     */
    private function getMonthlyData($userId, $dateFrom, $dateTo, $data) {
        // Income data
        $incomeStmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(trx_date, '%Y-%m') as period, COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), MONTH(trx_date) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data
        $expenseStmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(expense_date, '%Y-%m') as period, COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), MONTH(expense_date) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate monthly periods
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        $start->modify('first day of this month');
        
        while ($start <= $end) {
            $monthKey = $start->format('Y-m');
            $data['labels'][] = $start->format('M Y');
            $data['datasets'][0]['data'][] = $incomeData[$monthKey] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$monthKey] ?? 0;
            $start->modify('+1 month');
        }
        
        return $data;
    }
    
    /**
     * Get quarterly breakdown data
     */
    private function getQuarterlyData($userId, $dateFrom, $dateTo, $data) {
        // Income data by quarter
        $incomeStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(trx_date), '-Q', QUARTER(trx_date)) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), QUARTER(trx_date) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data by quarter
        $expenseStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(expense_date), '-Q', QUARTER(expense_date)) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), QUARTER(expense_date) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate quarterly periods
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        
        while ($start <= $end) {
            $quarter = ceil($start->format('n') / 3);
            $quarterKey = $start->format('Y') . '-Q' . $quarter;
            $data['labels'][] = 'Q' . $quarter . ' ' . $start->format('Y');
            $data['datasets'][0]['data'][] = $incomeData[$quarterKey] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$quarterKey] ?? 0;
            $start->modify('+3 months');
        }
        
        return $data;
    }
    
    /**
     * Get bi-yearly breakdown data
     */
    private function getBiYearlyData($userId, $dateFrom, $dateTo, $data) {
        // Income data by half-year
        $incomeStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(trx_date), '-H', CASE WHEN MONTH(trx_date) <= 6 THEN 1 ELSE 2 END) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), CASE WHEN MONTH(trx_date) <= 6 THEN 1 ELSE 2 END 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data by half-year
        $expenseStmt = $this->pdo->prepare("
            SELECT CONCAT(YEAR(expense_date), '-H', CASE WHEN MONTH(expense_date) <= 6 THEN 1 ELSE 2 END) as period, 
                   COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), CASE WHEN MONTH(expense_date) <= 6 THEN 1 ELSE 2 END 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate bi-yearly periods
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        
        while ($start <= $end) {
            $half = $start->format('n') <= 6 ? 1 : 2;
            $halfKey = $start->format('Y') . '-H' . $half;
            $periodLabel = ($half == 1 ? 'H1' : 'H2') . ' ' . $start->format('Y');
            $data['labels'][] = $periodLabel;
            $data['datasets'][0]['data'][] = $incomeData[$halfKey] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$halfKey] ?? 0;
            $start->modify('+6 months');
        }
        
        return $data;
    }
    
    /**
     * Get yearly breakdown data
     */
    private function getYearlyData($userId, $dateFrom, $dateTo, $data) {
        // Income data by year
        $incomeStmt = $this->pdo->prepare("
            SELECT YEAR(trx_date) as period, COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date) 
            ORDER BY period
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense data by year
        $expenseStmt = $this->pdo->prepare("
            SELECT YEAR(expense_date) as period, COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date) 
            ORDER BY period
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate yearly periods
        $startYear = (new DateTime($dateFrom))->format('Y');
        $endYear = (new DateTime($dateTo))->format('Y');
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $data['labels'][] = $year;
            $data['datasets'][0]['data'][] = $incomeData[$year] ?? 0;
            $data['datasets'][1]['data'][] = $expenseData[$year] ?? 0;
        }
        
        return $data;
    }
    
    /**
     * Generate advanced chart configuration
     */
    public function getChartConfig($chartType = 'bar') {
        $baseConfig = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                        'font' => [
                            'family' => 'Inter, sans-serif',
                            'size' => 12,
                            'weight' => '600'
                        ]
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.9)',
                    'titleColor' => 'white',
                    'bodyColor' => 'white',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + \': ₱\' + context.parsed.y.toLocaleString();
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)',
                        'drawBorder' => false
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return \'₱\' + value.toLocaleString();
                        }',
                        'font' => [
                            'size' => 12
                        ]
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12
                        ]
                    ]
                ]
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index'
            ],
            'animation' => [
                'duration' => 2000,
                'easing' => 'easeOutCubic'
            ]
        ];
        
        // Chart type specific configurations
        switch ($chartType) {
            case 'line':
                $baseConfig['elements'] = [
                    'line' => [
                        'tension' => 0.4,
                        'borderWidth' => 3
                    ],
                    'point' => [
                        'radius' => 6,
                        'hoverRadius' => 8,
                        'borderWidth' => 2
                    ]
                ];
                break;
            case 'doughnut':
                $baseConfig['plugins']['legend']['position'] = 'bottom';
                $baseConfig['cutout'] = '60%';
                unset($baseConfig['scales']);
                break;
        }
        
        return $baseConfig;
    }
    
    /**
     * Generate AI-powered business insights
     */
    public function generateBusinessInsights($userId, $dateFrom, $dateTo) {
        $insights = [];
        
        // Get current period data
        $currentIncome = $this->getTotalIncome($userId, $dateFrom, $dateTo);
        $currentExpense = $this->getTotalExpense($userId, $dateFrom, $dateTo);
        $currentNet = $currentIncome - $currentExpense;
        
        // Get previous period for comparison
        $periodDiff = (new DateTime($dateTo))->diff(new DateTime($dateFrom))->days;
        $prevDateTo = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
        $prevDateFrom = date('Y-m-d', strtotime($prevDateTo . ' -' . $periodDiff . ' days'));
        
        $prevIncome = $this->getTotalIncome($userId, $prevDateFrom, $prevDateTo);
        $prevExpense = $this->getTotalExpense($userId, $prevDateFrom, $prevDateTo);
        $prevNet = $prevIncome - $prevExpense;
        
        // Calculate trends
        $incomeChange = $prevIncome > 0 ? (($currentIncome - $prevIncome) / $prevIncome) * 100 : 0;
        $expenseChange = $prevExpense > 0 ? (($currentExpense - $prevExpense) / $prevExpense) * 100 : 0;
        $netChange = $prevNet != 0 ? (($currentNet - $prevNet) / abs($prevNet)) * 100 : 0;
        
        // Generate insights based on trends
        
        // Income trend insight
        if ($incomeChange > 15) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Excellent Income Growth!',
                'description' => sprintf('Your income increased by %.1f%% compared to the previous period. Keep up the great work!', $incomeChange),
                'value' => $currentIncome,
                'change' => $incomeChange,
                'icon' => 'fas fa-chart-line',
                'priority' => 'high'
            ];
        } elseif ($incomeChange < -10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Income Decline Detected',
                'description' => sprintf('Your income decreased by %.1f%%. Consider reviewing your income sources and exploring new opportunities.', abs($incomeChange)),
                'value' => $currentIncome,
                'change' => $incomeChange,
                'icon' => 'fas fa-exclamation-triangle',
                'priority' => 'high'
            ];
        }
        
        // Expense trend insight
        if ($expenseChange > 20) {
            $insights[] = [
                'type' => 'critical',
                'title' => 'High Expense Increase',
                'description' => sprintf('Your expenses increased by %.1f%%. Review your spending patterns and identify areas to optimize.', $expenseChange),
                'value' => $currentExpense,
                'change' => $expenseChange,
                'icon' => 'fas fa-exclamation-circle',
                'priority' => 'high'
            ];
        } elseif ($expenseChange < -15) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Great Expense Reduction!',
                'description' => sprintf('You reduced your expenses by %.1f%%. This improvement in financial discipline is paying off!', abs($expenseChange)),
                'value' => $currentExpense,
                'change' => $expenseChange,
                'icon' => 'fas fa-piggy-bank',
                'priority' => 'medium'
            ];
        }
        
        // Net income insight
        if ($currentNet > 0 && $netChange > 25) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Exceptional Financial Performance',
                'description' => sprintf('Your net income improved by %.1f%%. Consider investing this surplus for long-term growth.', $netChange),
                'value' => $currentNet,
                'change' => $netChange,
                'icon' => 'fas fa-trophy',
                'priority' => 'high'
            ];
        } elseif ($currentNet < 0) {
            $insights[] = [
                'type' => 'critical',
                'title' => 'Negative Cash Flow',
                'description' => 'Your expenses exceed your income. Immediate action is needed to balance your finances.',
                'value' => $currentNet,
                'change' => $netChange,
                'icon' => 'fas fa-exclamation-triangle',
                'priority' => 'high'
            ];
        }
        
        // Savings rate insight
        $savingsRate = $currentIncome > 0 ? (($currentNet / $currentIncome) * 100) : 0;
        if ($savingsRate > 20) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Excellent Savings Rate',
                'description' => sprintf('You\'re saving %.1f%% of your income. This puts you ahead of most people!', $savingsRate),
                'value' => $savingsRate,
                'change' => 0,
                'icon' => 'fas fa-percentage',
                'priority' => 'medium'
            ];
        } elseif ($savingsRate < 5 && $currentNet > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Savings Rate',
                'description' => sprintf('You\'re only saving %.1f%% of your income. Try to aim for at least 10-20%%.', $savingsRate),
                'value' => $savingsRate,
                'change' => 0,
                'icon' => 'fas fa-chart-pie',
                'priority' => 'medium'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Generate AI recommendations
     */
    public function generateRecommendations($userId, $dateFrom, $dateTo) {
        $recommendations = [];
        
        // Get spending by category
        $topExpenseCategories = $this->getTopExpenseCategories($userId, $dateFrom, $dateTo, 3);
        
        foreach ($topExpenseCategories as $category) {
            if ($category['total'] > 0) {
                $recommendations[] = [
                    'text' => sprintf('Review your %s expenses (₱%s). Look for optimization opportunities in this category.', 
                        $category['name'], number_format($category['total'], 2)),
                    'priority' => 'medium'
                ];
            }
        }
        
        // Income diversification recommendation
        $incomeStreams = $this->getIncomeStreams($userId, $dateFrom, $dateTo);
        if (count($incomeStreams) < 2) {
            $recommendations[] = [
                'text' => 'Consider diversifying your income sources to reduce financial risk and increase earning potential.',
                'priority' => 'high'
            ];
        }
        
        // Budget recommendation
        $currentIncome = $this->getTotalIncome($userId, $dateFrom, $dateTo);
        $currentExpense = $this->getTotalExpense($userId, $dateFrom, $dateTo);
        
        if ($currentIncome > 0 && ($currentExpense / $currentIncome) > 0.8) {
            $recommendations[] = [
                'text' => 'Your expenses are high relative to income. Consider implementing the 50/30/20 budgeting rule.',
                'priority' => 'high'
            ];
        }
        
        // Emergency fund recommendation
        $monthlyExpense = $currentExpense;
        $emergencyFundTarget = $monthlyExpense * 6;
        $recommendations[] = [
            'text' => sprintf('Build an emergency fund of ₱%s (6 months of expenses) for financial security.', 
                number_format($emergencyFundTarget, 2)),
            'priority' => 'medium'
        ];
        
        return $recommendations;
    }
    
    /**
     * Helper methods
     */
    private function getTotalIncome($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND trx_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        return $stmt->fetchColumn();
    }
    
    private function getTotalExpense($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        return $stmt->fetchColumn();
    }
    
    private function getTopExpenseCategories($userId, $dateFrom, $dateTo, $limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT c.name, COALESCE(SUM(e.amount),0) as total 
            FROM categories c 
            LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ? AND e.expense_date BETWEEN ? AND ?
            WHERE c.type = 'expense'
            GROUP BY c.id 
            ORDER BY total DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo, $limit]);
        return $stmt->fetchAll();
    }
    
    private function getIncomeStreams($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT c.name 
            FROM categories c 
            JOIN transactions t ON c.id = t.category_id 
            WHERE t.user_id = ? AND t.trx_date BETWEEN ? AND ? AND c.type = 'income'
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }
}
?>