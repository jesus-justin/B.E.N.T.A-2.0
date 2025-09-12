<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];

$type = $_GET['type'] ?? 'transactions';
$from = $_GET['from'] ?? '1970-01-01';
$to = $_GET['to'] ?? date('Y-m-d');

if ($type === 'transactions') {
    $stmt = $pdo->prepare("SELECT t.id, t.trx_date AS date, c.name AS category, t.amount, t.note
        FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ? AND t.trx_date BETWEEN ? AND ?
        ORDER BY t.trx_date ASC");
    $stmt->execute([$uid, $from, $to]);
    $rows = $stmt->fetchAll();
    $filename = "transactions_{$from}_to_{$to}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Date','Category','Amount','Note']);
    foreach($rows as $r) fputcsv($out, [$r['id'],$r['date'],$r['category'],$r['amount'],$r['note']]);
    fclose($out);
    exit;
} elseif ($type === 'expenses') {
    $stmt = $pdo->prepare("SELECT e.id, e.expense_date AS date, c.name AS category, e.vendor, e.amount, e.note
        FROM expenses e LEFT JOIN categories c ON c.id = e.category_id
        WHERE e.user_id = ? AND e.expense_date BETWEEN ? AND ?
        ORDER BY e.expense_date ASC");
    $stmt->execute([$uid, $from, $to]);
    $rows = $stmt->fetchAll();
    $filename = "expenses_{$from}_to_{$to}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Date','Category','Vendor','Amount','Note']);
    foreach($rows as $r) fputcsv($out, [$r['id'],$r['date'],$r['category'],$r['vendor'],$r['amount'],$r['note']]);
    fclose($out);
    exit;
} else {
    echo "Invalid export type.";
}
