<?php
require 'config.php';

try {
    // Create admin table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ");
    echo "✅ Admin table created successfully!<br>";

    // Create default admin account
    $adminUsername = 'admin';
    $adminEmail = 'admin@benta.com';
    $adminPassword = 'admin123'; // Change this in production!
    $adminFullName = 'System Administrator';
    
    // Check if admin already exists
    $checkAdmin = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
    $checkAdmin->execute([$adminUsername, $adminEmail]);
    
    if (!$checkAdmin->fetch()) {
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminUsername, $adminEmail, $hashedPassword, $adminFullName, 'super_admin']);
        echo "✅ Default admin account created successfully!<br>";
        echo "<strong>Admin Credentials:</strong><br>";
        echo "Username: <strong>$adminUsername</strong><br>";
        echo "Password: <strong>$adminPassword</strong><br>";
        echo "Email: <strong>$adminEmail</strong><br>";
        echo "<br><span style='color: red;'>⚠️ IMPORTANT: Change the default password after first login!</span><br>";
    } else {
        echo "ℹ️ Admin account already exists!<br>";
    }

    // Add admin role to users table if it doesn't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "✅ User table updated with admin fields!<br>";

    echo "<br><strong>🎉 Admin system setup completed!</strong><br>";
    echo "<a href='../admin/admin_login.php'>Go to Admin Login</a> | <a href='../index.php'>Go to Main App</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
