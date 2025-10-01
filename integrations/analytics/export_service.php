<?php
/**
 * Export Service - Advanced Data Export Integration
 * Supports multiple export formats with comprehensive data filtering
 */

class ExportService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Export data to CSV format
     */
    public function exportToCSV($userId, $dateFrom, $dateTo, $type = 'all') {
        $filename = "BENTA_export_" . date('Y-m-d_H-i-s') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'transactions':
                $this->exportTransactionsCSV($output, $userId, $dateFrom, $dateTo);
                break;
            case 'expenses':
                $this->exportExpensesCSV($output, $userId, $dateFrom, $dateTo);
                break;
            case 'summary':
                $this->exportSummaryCSV($output, $userId, $dateFrom, $dateTo);
                break;
            default:
                $this->exportAllDataCSV($output, $userId, $dateFrom, $dateTo);
                break;
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Export data to PDF format
     */
    public function exportToPDF($userId, $dateFrom, $dateTo, $type = 'summary') {
        // This would require a PDF library like TCPDF or mPDF
        // For now, we'll return HTML that can be converted to PDF
        $html = $this->generatePDFContent($userId, $dateFrom, $dateTo, $type);
        
        header('Content-Type: text/html');
        header('Content-Disposition: inline; filename="BENTA_report_' . date('Y-m-d') . '.html"');
        
        echo $html;
        exit();
    }
    
    /**
     * Export transactions to CSV
     */
    private function exportTransactionsCSV($output, $userId, $dateFrom, $dateTo) {
        // Header
        fputcsv($output, [
            'Date', 'Category', 'Amount', 'Description', 'Type', 'Created At'
        ]);
        
        // Data
        $stmt = $this->pdo->prepare("
            SELECT t.trx_date, c.name as category, t.amount, t.description, 'Income' as type, t.created_at
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.trx_date BETWEEN ? AND ?
            ORDER BY t.trx_date DESC
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['trx_date'],
                $row['category'] ?: 'Uncategorized',
                number_format($row['amount'], 2),
                $row['description'],
                $row['type'],
                $row['created_at']
            ]);
        }
    }
    
    /**
     * Export expenses to CSV
     */
    private function exportExpensesCSV($output, $userId, $dateFrom, $dateTo) {
        // Header
        fputcsv($output, [
            'Date', 'Category', 'Amount', 'Vendor', 'Note', 'Type', 'Created At'
        ]);
        
        // Data
        $stmt = $this->pdo->prepare("
            SELECT e.expense_date, c.name as category, e.amount, e.vendor, e.note, 'Expense' as type, e.created_at
            FROM expenses e
            LEFT JOIN categories c ON e.category_id = c.id
            WHERE e.user_id = ? AND e.expense_date BETWEEN ? AND ?
            ORDER BY e.expense_date DESC
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['expense_date'],
                $row['category'] ?: 'Uncategorized',
                number_format($row['amount'], 2),
                $row['vendor'],
                $row['note'],
                $row['type'],
                $row['created_at']
            ]);
        }
    }
    
    /**
     * Export summary data to CSV
     */
    private function exportSummaryCSV($output, $userId, $dateFrom, $dateTo) {
        // Summary Header
        fputcsv($output, ['BENTA Financial Summary Report']);
        fputcsv($output, ['Period: ' . $dateFrom . ' to ' . $dateTo]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Totals
        $totals = $this->getSummaryTotals($userId, $dateFrom, $dateTo);
        fputcsv($output, ['Summary']);
        fputcsv($output, ['Total Income', number_format($totals['income'], 2)]);
        fputcsv($output, ['Total Expenses', number_format($totals['expenses'], 2)]);
        fputcsv($output, ['Net Income', number_format($totals['net'], 2)]);
        fputcsv($output, ['Savings Rate', number_format($totals['savings_rate'], 1) . '%']);
        fputcsv($output, []);
        
        // Monthly breakdown
        fputcsv($output, ['Monthly Breakdown']);
        fputcsv($output, ['Month', 'Income', 'Expenses', 'Net']);
        
        $monthlyData = $this->getMonthlyBreakdown($userId, $dateFrom, $dateTo);
        foreach ($monthlyData as $month) {
            fputcsv($output, [
                $month['month'],
                number_format($month['income'], 2),
                number_format($month['expense'], 2),
                number_format($month['net'], 2)
            ]);
        }
        
        fputcsv($output, []);
        
        // Category breakdown
        fputcsv($output, ['Top Expense Categories']);
        fputcsv($output, ['Category', 'Amount', 'Percentage']);
        
        $categories = $this->getCategoryBreakdown($userId, $dateFrom, $dateTo);
        foreach ($categories as $category) {
            fputcsv($output, [
                $category['name'],
                number_format($category['amount'], 2),
                number_format($category['percentage'], 1) . '%'
            ]);
        }
    }
    
    /**
     * Export all data to CSV
     */
    private function exportAllDataCSV($output, $userId, $dateFrom, $dateTo) {
        // Combined header
        fputcsv($output, [
            'Date', 'Type', 'Category', 'Amount', 'Description/Vendor', 'Note', 'Created At'
        ]);
        
        // Get all transactions and expenses combined
        $stmt = $this->pdo->prepare("
            (SELECT trx_date as date, 'Income' as type, c.name as category, amount, description as desc_vendor, '' as note, created_at
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.trx_date BETWEEN ? AND ?)
            UNION ALL
            (SELECT expense_date as date, 'Expense' as type, c.name as category, amount, vendor as desc_vendor, note, created_at
             FROM expenses e
             LEFT JOIN categories c ON e.category_id = c.id
             WHERE e.user_id = ? AND e.expense_date BETWEEN ? AND ?)
            ORDER BY date DESC, created_at DESC
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo, $userId, $dateFrom, $dateTo]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['date'],
                $row['type'],
                $row['category'] ?: 'Uncategorized',
                number_format($row['amount'], 2),
                $row['desc_vendor'],
                $row['note'],
                $row['created_at']
            ]);
        }
    }
    
    /**
     * Generate PDF content
     */
    private function generatePDFContent($userId, $dateFrom, $dateTo, $type) {
        $totals = $this->getSummaryTotals($userId, $dateFrom, $dateTo);
        $monthlyData = $this->getMonthlyBreakdown($userId, $dateFrom, $dateTo);
        $categories = $this->getCategoryBreakdown($userId, $dateFrom, $dateTo);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>BENTA Financial Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .summary { margin: 20px 0; }
                .summary-item { display: flex; justify-content: space-between; padding: 5px 0; }
                .positive { color: green; }
                .negative { color: red; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .chart-placeholder { height: 200px; background: #f8f9fa; border: 1px solid #ddd; 
                                   display: flex; align-items: center; justify-content: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>BENTA Financial Report</h1>
                <p>Period: ' . $dateFrom . ' to ' . $dateTo . '</p>
                <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
            </div>
            
            <div class="summary">
                <h2>Financial Summary</h2>
                <div class="summary-item">
                    <span>Total Income:</span>
                    <span class="positive">₱' . number_format($totals['income'], 2) . '</span>
                </div>
                <div class="summary-item">
                    <span>Total Expenses:</span>
                    <span class="negative">₱' . number_format($totals['expenses'], 2) . '</span>
                </div>
                <div class="summary-item">
                    <span>Net Income:</span>
                    <span class="' . ($totals['net'] >= 0 ? 'positive' : 'negative') . '">₱' . number_format($totals['net'], 2) . '</span>
                </div>
                <div class="summary-item">
                    <span>Savings Rate:</span>
                    <span>' . number_format($totals['savings_rate'], 1) . '%</span>
                </div>
            </div>
            
            <h2>Monthly Breakdown</h2>
            <table>
                <thead>
                    <tr><th>Month</th><th>Income</th><th>Expenses</th><th>Net</th></tr>
                </thead>
                <tbody>';
        
        foreach ($monthlyData as $month) {
            $html .= '<tr>
                        <td>' . $month['month'] . '</td>
                        <td>₱' . number_format($month['income'], 2) . '</td>
                        <td>₱' . number_format($month['expense'], 2) . '</td>
                        <td class="' . ($month['net'] >= 0 ? 'positive' : 'negative') . '">₱' . number_format($month['net'], 2) . '</td>
                      </tr>';
        }
        
        $html .= '</tbody></table>
            
            <h2>Top Expense Categories</h2>
            <table>
                <thead>
                    <tr><th>Category</th><th>Amount</th><th>Percentage</th></tr>
                </thead>
                <tbody>';
        
        foreach ($categories as $category) {
            $html .= '<tr>
                        <td>' . $category['name'] . '</td>
                        <td>₱' . number_format($category['amount'], 2) . '</td>
                        <td>' . number_format($category['percentage'], 1) . '%</td>
                      </tr>';
        }
        
        $html .= '</tbody></table>
            
            <div class="chart-placeholder">
                <p>Chart visualization would appear here in full PDF version</p>
            </div>
            
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Helper methods
     */
    private function getSummaryTotals($userId, $dateFrom, $dateTo) {
        $income = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND trx_date BETWEEN ? AND ?");
        $income->execute([$userId, $dateFrom, $dateTo]);
        $totalIncome = $income->fetchColumn();
        
        $expense = $this->pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
        $expense->execute([$userId, $dateFrom, $dateTo]);
        $totalExpenses = $expense->fetchColumn();
        
        $net = $totalIncome - $totalExpenses;
        $savingsRate = $totalIncome > 0 ? ($net / $totalIncome) * 100 : 0;
        
        return [
            'income' => $totalIncome,
            'expenses' => $totalExpenses,
            'net' => $net,
            'savings_rate' => $savingsRate
        ];
    }
    
    private function getMonthlyBreakdown($userId, $dateFrom, $dateTo) {
        $months = [];
        
        // Income by month
        $incomeStmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(trx_date, '%Y-%m') as month, COALESCE(SUM(amount),0) as total 
            FROM transactions 
            WHERE user_id = ? AND trx_date BETWEEN ? AND ? 
            GROUP BY YEAR(trx_date), MONTH(trx_date) 
            ORDER BY month
        ");
        $incomeStmt->execute([$userId, $dateFrom, $dateTo]);
        $incomeData = $incomeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Expense by month
        $expenseStmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, COALESCE(SUM(amount),0) as total 
            FROM expenses 
            WHERE user_id = ? AND expense_date BETWEEN ? AND ? 
            GROUP BY YEAR(expense_date), MONTH(expense_date) 
            ORDER BY month
        ");
        $expenseStmt->execute([$userId, $dateFrom, $dateTo]);
        $expenseData = $expenseStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Combine data
        $allMonths = array_unique(array_merge(array_keys($incomeData), array_keys($expenseData)));
        sort($allMonths);
        
        foreach ($allMonths as $month) {
            $income = $incomeData[$month] ?? 0;
            $expense = $expenseData[$month] ?? 0;
            $months[] = [
                'month' => date('M Y', strtotime($month . '-01')),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense
            ];
        }
        
        return $months;
    }
    
    private function getCategoryBreakdown($userId, $dateFrom, $dateTo) {
        $stmt = $this->pdo->prepare("
            SELECT c.name, COALESCE(SUM(e.amount),0) as amount
            FROM categories c 
            LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ? AND e.expense_date BETWEEN ? AND ?
            WHERE c.type = 'expense'
            GROUP BY c.id 
            HAVING amount > 0
            ORDER BY amount DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId, $dateFrom, $dateTo]);
        $categories = $stmt->fetchAll();
        
        // Calculate total for percentage
        $total = array_sum(array_column($categories, 'amount'));
        
        // Add percentage
        foreach ($categories as &$category) {
            $category['percentage'] = $total > 0 ? ($category['amount'] / $total) * 100 : 0;
        }
        
        return $categories;
    }
}
?>