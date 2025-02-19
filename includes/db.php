<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';  // default XAMPP username
$db_pass = '';      // default XAMPP password
$db_name = 'fluxocx';

// Create connection without database selected
try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $pdo->exec("USE `$db_name`");
    
    // Create usuarios table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nome` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL UNIQUE,
        `senha` varchar(255) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Criar usuário admin se não existir
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@admin.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute(['Administrador', 'admin@admin.com', $senha_hash]);
        $admin_id = $pdo->lastInsertId();
    } else {
        $admin_id = $admin['id'];
    }
    
    // Verificar se a tabela transacoes existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'transacoes'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        // Verificar se a coluna usuario_id existe
        $stmt = $pdo->query("SHOW COLUMNS FROM transacoes LIKE 'usuario_id'");
        if ($stmt->rowCount() == 0) {
            // Adicionar coluna usuario_id
            $pdo->exec("ALTER TABLE transacoes ADD COLUMN usuario_id int(11) NOT NULL DEFAULT {$admin_id} AFTER id");
            
            // Após definir os valores padrão, adicionar a chave estrangeira
            $pdo->exec("ALTER TABLE transacoes ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE");
        }
    } else {
        // Criar a tabela transacoes com a coluna usuario_id
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transacoes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `usuario_id` int(11) NOT NULL,
            `data` date NOT NULL,
            `descricao` varchar(255) NOT NULL,
            `tipo` enum('entrada','saida') NOT NULL,
            `valor` decimal(10,2) NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 