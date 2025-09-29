<?php
require 'config.php';

try {
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Users table created successfully!<br>";

    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('income', 'expense') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Categories table created successfully!<br>";

    // Create transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            trx_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Transactions table created successfully!<br>";

    // Create expenses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            vendor VARCHAR(100),
            note TEXT,
            expense_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Expenses table created successfully!<br>";

    // Insert default categories
    $categories = [
        ['Salary', 'income'],
        ['Freelance', 'income'],
        ['Investment', 'income'],
        ['Other Income', 'income'],
        ['Food & Dining', 'expense'],
        ['Transportation', 'expense'],
        ['Shopping', 'expense'],
        ['Entertainment', 'expense'],
        ['Bills & Utilities', 'expense'],
        ['Healthcare', 'expense'],
        ['Education', 'expense'],
        ['Other Expense', 'expense']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, type) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✅ Default categories inserted successfully!<br>";

    echo "<br><strong>🎉 Database setup completed successfully!</strong><br>";
    echo "<a href='../auth/register.php'>Go to Registration</a> | <a href='../auth/login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
