<?php
/**
 * Notification Service - Real-time Notifications Integration
 * Handles financial alerts, insights notifications, and user engagement
 */

class NotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check for financial alerts and notifications
     */
    public function getFinancialAlerts($userId) {
        $alerts = [];
        
        // Check for unusual spending patterns
        $spendingAlerts = $this->checkSpendingPatterns($userId);
        $alerts = array_merge($alerts, $spendingAlerts);
        
        // Check for budget overruns
        $budgetAlerts = $this->checkBudgetOverruns($userId);
        $alerts = array_merge($alerts, $budgetAlerts);
        
        // Check for income irregularities
        $incomeAlerts = $this->checkIncomeIrregularities($userId);
        $alerts = array_merge($alerts, $incomeAlerts);
        
        // Check for goal achievements
        $goalAlerts = $this->checkGoalAchievements($userId);
        $alerts = array_merge($alerts, $goalAlerts);
        
        // Sort by priority and timestamp
        usort($alerts, function($a, $b) {
            $priorities = ['critical' => 4, 'warning' => 3, 'info' => 2, 'success' => 1];
            $aPriority = $priorities[$a['type']] ?? 0;
            $bPriority = $priorities[$b['type']] ?? 0;
            
            if ($aPriority === $bPriority) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            }
            return $bPriority - $aPriority;
        });
        
        return array_slice($alerts, 0, 10); // Return top 10 alerts
    }
    
    /**
     * Check for unusual spending patterns
     */
    private function checkSpendingPatterns($userId) {
        $alerts = [];
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        // Get current month spending
        $currentSpending = $this->getMonthlyExpenses($userId, $currentMonth);
        $lastMonthSpending = $this->getMonthlyExpenses($userId, $lastMonth);
        
        if ($lastMonthSpending > 0) {
            $changePercent = (($currentSpending - $lastMonthSpending) / $lastMonthSpending) * 100;
            
            if ($changePercent > 50) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Unusual Spending Increase',
                    'message' => sprintf('Your spending increased by %.1f%% this month. Consider reviewing your expenses.', $changePercent),
                    'amount' => $currentSpending,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-exclamation-triangle',
                    'action' => 'view_expenses'
                ];
            } elseif ($changePercent < -30) {
                $alerts[] = [
                    'type' => 'success',
                    'title' => 'Great Spending Reduction!',
                    'message' => sprintf('You reduced your spending by %.1f%% this month. Keep up the good work!', abs($changePercent)),
                    'amount' => $currentSpending,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-thumbs-up',
                    'action' => 'view_reports'
                ];
            }
        }
        
        // Check for daily spending spikes
        $dailySpending = $this->getDailySpending($userId, date('Y-m-d'));
        $avgDailySpending = $this->getAverageDailySpending($userId);
        
        if ($avgDailySpending > 0 && $dailySpending > ($avgDailySpending * 3)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Daily Spending',
                'message' => sprintf('Today\'s spending (₱%.2f) is significantly higher than your daily average (₱%.2f).', $dailySpending, $avgDailySpending),
                'amount' => $dailySpending,
                'timestamp' => date('Y-m-d H:i:s'),
                'icon' => 'fas fa-chart-line',
                'action' => 'view_today_expenses'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check for budget overruns (if budgets are implemented)
     */
    private function checkBudgetOverruns($userId) {
        $alerts = [];
        
        // For now, we'll use category-based spending limits
        // In a full implementation, this would check against user-defined budgets
        
        $categoryLimits = [
            'Food & Dining' => 15000,
            'Transportation' => 5000,
            'Entertainment' => 3000,
            'Shopping' => 8000,
            'Utilities' => 4000
        ];
        
        $currentMonth = date('Y-m');
        
        foreach ($categoryLimits as $categoryName => $limit) {
            $spending = $this->getCategorySpending($userId, $categoryName, $currentMonth);
            
            if ($spending > $limit) {
                $overrun = $spending - $limit;
                $alerts[] = [
                    'type' => 'critical',
                    'title' => 'Budget Overrun Alert',
                    'message' => sprintf('You\'ve exceeded your %s budget by ₱%.2f this month.', $categoryName, $overrun),
                    'amount' => $spending,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-exclamation-circle',
                    'action' => 'view_category_expenses',
                    'category' => $categoryName
                ];
            } elseif ($spending > ($limit * 0.8)) {
                $remaining = $limit - $spending;
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Approaching Budget Limit',
                    'message' => sprintf('You have ₱%.2f remaining in your %s budget this month.', $remaining, $categoryName),
                    'amount' => $spending,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-exclamation-triangle',
                    'action' => 'view_category_expenses',
                    'category' => $categoryName
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Check for income irregularities
     */
    private function checkIncomeIrregularities($userId) {
        $alerts = [];
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        $currentIncome = $this->getMonthlyIncome($userId, $currentMonth);
        $lastMonthIncome = $this->getMonthlyIncome($userId, $lastMonth);
        $avgIncome = $this->getAverageMonthlyIncome($userId);
        
        // Check for significant income drop
        if ($lastMonthIncome > 0) {
            $changePercent = (($currentIncome - $lastMonthIncome) / $lastMonthIncome) * 100;
            
            if ($changePercent < -25) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Income Decrease Detected',
                    'message' => sprintf('Your income decreased by %.1f%% this month. Monitor your income sources closely.', abs($changePercent)),
                    'amount' => $currentIncome,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-arrow-down',
                    'action' => 'view_income'
                ];
            } elseif ($changePercent > 25) {
                $alerts[] = [
                    'type' => 'success',
                    'title' => 'Income Boost!',
                    'message' => sprintf('Your income increased by %.1f%% this month. Great job!', $changePercent),
                    'amount' => $currentIncome,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-arrow-up',
                    'action' => 'view_income'
                ];
            }
        }
        
        // Check if income is below average
        if ($avgIncome > 0 && $currentIncome < ($avgIncome * 0.7)) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Below Average Income',
                'message' => sprintf('This month\'s income (₱%.2f) is below your average (₱%.2f).', $currentIncome, $avgIncome),
                'amount' => $currentIncome,
                'timestamp' => date('Y-m-d H:i:s'),
                'icon' => 'fas fa-info-circle',
                'action' => 'view_income_trends'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check for goal achievements
     */
    private function checkGoalAchievements($userId) {
        $alerts = [];
        
        // Savings rate achievement
        $currentMonth = date('Y-m');
        $income = $this->getMonthlyIncome($userId, $currentMonth);
        $expenses = $this->getMonthlyExpenses($userId, $currentMonth);
        $savings = $income - $expenses;
        $savingsRate = $income > 0 ? ($savings / $income) * 100 : 0;
        
        if ($savingsRate >= 20) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'Excellent Savings Rate!',
                'message' => sprintf('You\'re saving %.1f%% of your income this month. You\'re on track for financial success!', $savingsRate),
                'amount' => $savings,
                'timestamp' => date('Y-m-d H:i:s'),
                'icon' => 'fas fa-trophy',
                'action' => 'view_savings'
            ];
        }
        
        // Expense reduction achievement
        $lastMonth = date('Y-m', strtotime('-1 month'));
        $lastMonthExpenses = $this->getMonthlyExpenses($userId, $lastMonth);
        
        if ($lastMonthExpenses > 0) {
            $reductionPercent = (($lastMonthExpenses - $expenses) / $lastMonthExpenses) * 100;
            
            if ($reductionPercent >= 15) {
                $alerts[] = [
                    'type' => 'success',
                    'title' => 'Great Expense Control!',
                    'message' => sprintf('You reduced your expenses by %.1f%% this month. Excellent financial discipline!', $reductionPercent),
                    'amount' => $expenses,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-award',
                    'action' => 'view_expense_trends'
                ];
            }
        }
        
        // Milestone achievements
        $totalSavings = $this->getTotalSavings($userId);
        $milestones = [50000, 100000, 250000, 500000, 1000000];
        
        foreach ($milestones as $milestone) {
            if ($totalSavings >= $milestone && !$this->isMilestoneNotified($userId, $milestone)) {
                $alerts[] = [
                    'type' => 'success',
                    'title' => 'Savings Milestone Reached!',
                    'message' => sprintf('Congratulations! You\'ve reached ₱%s in total savings!', number_format($milestone)),
                    'amount' => $totalSavings,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'icon' => 'fas fa-star',
                    'action' => 'celebrate_milestone'
                ];
                $this->markMilestoneNotified($userId, $milestone);
                break; // Only show one milestone at a time
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get notification preferences (placeholder for user customization)
     */
    public function getNotificationPreferences($userId) {
        // This would come from a user_notification_preferences table
        return [
            'spending_alerts' => true,
            'budget_alerts' => true,
            'income_alerts' => true,
            'goal_alerts' => true,
            'daily_summary' => true,
            'weekly_report' => true,
            'monthly_insights' => true
        ];
    }
    
    /**
     * Generate smart insights based on user data
     */
    public function generateSmartInsights($userId) {
        $insights = [];
        
        // Spending pattern insight
        $topCategory = $this->getTopSpendingCategory($userId);
        if ($topCategory) {
            $insights[] = [
                'type' => 'insight',
                'title' => 'Top Spending Category',
                'message' => sprintf('You spend the most on %s (₱%.2f this month). Consider reviewing this category for savings opportunities.', 
                    $topCategory['name'], $topCategory['amount']),
                'icon' => 'fas fa-lightbulb',
                'action' => 'analyze_category',
                'category' => $topCategory['name']
            ];
        }
        
        // Best saving day insight
        $bestSavingDay = $this->getBestSavingDay($userId);
        if ($bestSavingDay) {
            $insights[] = [
                'type' => 'insight',
                'title' => 'Best Saving Day',
                'message' => sprintf('You tend to spend less on %s. Consider doing major purchases on other days.', $bestSavingDay),
                'icon' => 'fas fa-calendar-check',
                'action' => 'view_daily_patterns'
            ];
        }
        
        // Income source diversity
        $incomeStreams = $this->getIncomeStreamCount($userId);
        if ($incomeStreams < 2) {
            $insights[] = [
                'type' => 'insight',
                'title' => 'Income Diversification',
                'message' => 'Consider exploring additional income sources to improve financial stability and growth.',
                'icon' => 'fas fa-sitemap',
                'action' => 'explore_income_sources'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Helper methods for data retrieval
     */
    private function getMonthlyExpenses($userId, $month) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM expenses 
            WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
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
    
    private function getDailySpending($userId, $date) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM expenses 
            WHERE user_id = ? AND DATE(expense_date) = ?
        ");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchColumn();
    }
    
    private function getAverageDailySpending($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(AVG(daily_total), 0) FROM (
                SELECT DATE(expense_date) as expense_day, SUM(amount) as daily_total
                FROM expenses 
                WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(expense_date)
            ) daily_expenses
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getCategorySpending($userId, $categoryName, $month) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(e.amount), 0) 
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            WHERE e.user_id = ? AND c.name = ? AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $categoryName, $month]);
        return $stmt->fetchColumn();
    }
    
    private function getAverageMonthlyIncome($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(AVG(monthly_total), 0) FROM (
                SELECT DATE_FORMAT(trx_date, '%Y-%m') as month, SUM(amount) as monthly_total
                FROM transactions 
                WHERE user_id = ? AND trx_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(trx_date, '%Y-%m')
            ) monthly_income
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getTotalSavings($userId) {
        $totalIncome = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ?");
        $totalIncome->execute([$userId]);
        $income = $totalIncome->fetchColumn();
        
        $totalExpenses = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?");
        $totalExpenses->execute([$userId]);
        $expenses = $totalExpenses->fetchColumn();
        
        return max(0, $income - $expenses);
    }
    
    private function getTopSpendingCategory($userId) {
        $stmt = $this->pdo->prepare("
            SELECT c.name, SUM(e.amount) as amount
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            WHERE e.user_id = ? AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            GROUP BY c.id
            ORDER BY amount DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, date('Y-m')]);
        return $stmt->fetch();
    }
    
    private function getBestSavingDay($userId) {
        $stmt = $this->pdo->prepare("
            SELECT DAYNAME(expense_date) as day_name, AVG(amount) as avg_spending
            FROM expenses 
            WHERE user_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            GROUP BY DAYOFWEEK(expense_date)
            ORDER BY avg_spending ASC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['day_name'] : null;
    }
    
    private function getIncomeStreamCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT c.id)
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.trx_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function isMilestoneNotified($userId, $milestone) {
        // This would check a notifications log table
        // For now, return false to allow notifications
        return false;
    }
    
    private function markMilestoneNotified($userId, $milestone) {
        // This would insert into a notifications log table
        // Placeholder for implementation
    }
}
?>