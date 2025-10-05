<?php
require 'config.php';

echo "<h2>Admin Account Check</h2>";

try {
    // Check if admins table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'admins'")->fetch();
    
    if (!$tableExists) {
        echo "❌ Admins table does not exist. Creating it...<br>";
        
        // Create admins table
        $pdo->exec("
            CREATE TABLE admins (
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
        echo "✅ Admins table created successfully!<br>";
    } else {
        echo "✅ Admins table exists!<br>";
    }
    
    // Check current admin account
    $adminEmail = 'admin@example.com';
    $adminPassword = 'admin123';
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "✅ Admin account found!<br>";
        echo "Email: " . $existingAdmin['email'] . "<br>";
        echo "Username: " . ($existingAdmin['username'] ?? 'Not set') . "<br>";
        echo "Full Name: " . ($existingAdmin['full_name'] ?? 'Not set') . "<br>";
        echo "Role: " . ($existingAdmin['role'] ?? 'Not set') . "<br>";
        echo "Active: " . ($existingAdmin['is_active'] ? 'Yes' : 'No') . "<br>";
        
        // Test password
        if (password_verify($adminPassword, $existingAdmin['password'])) {
            echo "✅ Password verification successful!<br>";
        } else {
            echo "❌ Password verification failed. Updating password...<br>";
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
            $updateStmt->execute([$hashedPassword, $adminEmail]);
            echo "✅ Password updated successfully!<br>";
        }
    } else {
        echo "❌ Admin account not found. Creating it...<br>";
        
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Administrator', $adminEmail, $hashedPassword, 'Administrator', 'super_admin']);
        echo "✅ Admin account created successfully!<br>";
    }
    
    echo "<br><strong>🎉 Admin account is ready!</strong><br>";
    echo "<strong>Login Credentials:</strong><br>";
    echo "Email: <strong>$adminEmail</strong><br>";
    echo "Password: <strong>$adminPassword</strong><br>";
    echo "<br><a href='../admin/admin_login.php'>Go to Admin Login</a> | <a href='../index.php'>Go to Main App</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
