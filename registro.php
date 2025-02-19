<?php
require_once 'includes/db.php';
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    try {
        if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
            throw new Exception('Por favor, preencha todos os campos.');
        }

        if ($senha !== $confirma_senha) {
            throw new Exception('As senhas não coincidem.');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres.');
        }

        // Verifica se o email já está cadastrado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado.');
        }

        // Cria o novo usuário
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);

        $mensagem = 'Conta criada com sucesso! Você já pode fazer login.';
        $tipo_mensagem = 'sucesso';
        
        // Redireciona para o login após 2 segundos
        header("refresh:2;url=login.php");
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Fluxo de Caixa</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-weight: 500;
        }
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-login {
            background: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        .btn-login:hover {
            background: #0056b3;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Registro</h1>
            <p>Sistema de Fluxo de Caixa</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required 
                       value="<?= htmlspecialchars($nome ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required 
                       minlength="6">
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirme a Senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" 
                       required minlength="6">
            </div>

            <button type="submit" class="btn-login">Registrar</button>
        </form>

        <div class="login-link">
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
        </div>
    </div>
</body>
</html> 