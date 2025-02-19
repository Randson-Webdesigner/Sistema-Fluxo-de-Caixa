<?php
require_once 'includes/db.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT nome, email, created_at FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao carregar dados do usuário: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'atualizar_perfil') {
        try {
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            
            if (empty($nome) || empty($email)) {
                throw new Exception("Nome e email são obrigatórios.");
            }
            
            // Verificar se o email já está em uso por outro usuário
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['usuario_id']]);
            if ($stmt->fetch()) {
                throw new Exception("Este email já está em uso por outro usuário.");
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $_SESSION['usuario_id']]);
            
            $_SESSION['usuario_nome'] = $nome;
            $usuario['nome'] = $nome;
            $usuario['email'] = $email;
            
            $mensagem = "Perfil atualizado com sucesso!";
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $mensagem = $e->getMessage();
            $tipo_mensagem = "erro";
        }
    } elseif ($acao === 'alterar_senha') {
        try {
            $senha_atual = $_POST['senha_atual'] ?? '';
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirma_senha = $_POST['confirma_senha'] ?? '';
            
            if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
                throw new Exception("Todos os campos são obrigatórios.");
            }
            
            if ($nova_senha !== $confirma_senha) {
                throw new Exception("As senhas não coincidem.");
            }
            
            if (strlen($nova_senha) < 6) {
                throw new Exception("A nova senha deve ter pelo menos 6 caracteres.");
            }
            
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario_senha = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($senha_atual, $usuario_senha['senha'])) {
                throw new Exception("Senha atual incorreta.");
            }
            
            // Atualizar senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$senha_hash, $_SESSION['usuario_id']]);
            
            $mensagem = "Senha alterada com sucesso!";
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $mensagem = $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema de Fluxo de Caixa</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .perfil-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .perfil-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .perfil-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .perfil-form {
            display: grid;
            gap: 15px;
        }
        .perfil-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .btn-voltar {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-voltar:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="perfil-container">
        <div class="perfil-header">
            <h1>Meu Perfil</h1>
            <a href="index.php" class="btn-voltar">Voltar ao Dashboard</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="perfil-info">
            <h2>Informações Pessoais</h2>
            <form method="POST" class="perfil-form">
                <input type="hidden" name="acao" value="atualizar_perfil">
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?= htmlspecialchars($usuario['nome']) ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($usuario['email']) ?>">
                </div>

                <div class="form-group">
                    <label>Membro desde:</label>
                    <div><?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?></div>
                </div>

                <button type="submit" class="btn-principal">Atualizar Perfil</button>
            </form>
        </div>

        <div class="perfil-info">
            <h2>Alterar Senha</h2>
            <form method="POST" class="perfil-form">
                <input type="hidden" name="acao" value="alterar_senha">
                
                <div class="form-group">
                    <label for="senha_atual">Senha Atual:</label>
                    <input type="password" id="senha_atual" name="senha_atual" required>
                </div>

                <div class="form-group">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" 
                           required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirma_senha">Confirme a Nova Senha:</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" 
                           required minlength="6">
                </div>

                <button type="submit" class="btn-principal">Alterar Senha</button>
            </form>
        </div>

        <div class="perfil-stats">
            <?php
            // Buscar estatísticas do usuário
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_transacoes,
                        SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) as total_entradas,
                        SUM(CASE WHEN tipo = 'saida' THEN 1 ELSE 0 END) as total_saidas
                    FROM transacoes 
                    WHERE usuario_id = ?
                ");
                $stmt->execute([$_SESSION['usuario_id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="stat-card">
                    <h3>Total de Transações</h3>
                    <p><?= number_format($stats['total_transacoes']) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total de Entradas</h3>
                    <p><?= number_format($stats['total_entradas']) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total de Saídas</h3>
                    <p><?= number_format($stats['total_saidas']) ?></p>
                </div>
            <?php
            } catch (PDOException $e) {
                echo "<div class='mensagem erro'>Erro ao carregar estatísticas</div>";
            }
            ?>
        </div>
    </div>
</body>
</html> 