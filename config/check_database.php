<?php
require 'config.php';

echo "<h2>Database Connection Check</h2>";

try {
    // Test connection
    echo "✅ Database connection successful!<br>";
    echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "<br><br>";

    // Check if tables exist
    $tables = ['users', 'categories', 'transactions', 'expenses'];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "✅ Table '$table' exists<br>";
            
            // Show table structure
            $columns = $pdo->query("DESCRIBE $table")->fetchAll();
            echo "&nbsp;&nbsp;Columns: ";
            foreach ($columns as $column) {
                echo $column['Field'] . " (" . $column['Type'] . ") ";
            }
            echo "<br>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    }

    echo "<br><a href='setup_database.php'>Setup Database</a> | <a href='../index.php'>Go to App</a>";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?>
