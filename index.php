<?php
require_once 'includes/db.php';

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';

// Configuração da busca
$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_STRING) ?? '';
$tipo_filtro = filter_input(INPUT_GET, 'tipo_filtro', FILTER_SANITIZE_STRING) ?? '';
$data_inicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_STRING) ?? '';
$data_fim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_STRING) ?? '';

// Processar exclusão
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception("ID inválido");
        }

        $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensagem = "Transação excluída com sucesso!";
        $tipo_mensagem = "sucesso";
        
        // Redirecionar após excluir para evitar reenvio do formulário
        header("Location: " . $_SERVER['PHP_SELF'] . "?pagina=" . $pagina_atual);
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro ao excluir: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Processar atualização
else if (isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
        $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING);

        if (!$id || empty($descricao) || empty($valor) || empty($tipo) || empty($data)) {
            throw new Exception("Todos os campos são obrigatórios");
        }

        if (!in_array($tipo, ['entrada', 'saida'])) {
            throw new Exception("Tipo de transação inválido");
        }

        $stmt = $pdo->prepare("UPDATE transacoes SET descricao = ?, valor = ?, tipo = ?, data = ? WHERE id = ?");
        $stmt->execute([$descricao, $valor, $tipo, $data, $id]);
        
        $mensagem = "Transação atualizada com sucesso!";
        $tipo_mensagem = "sucesso";
        
        // Redirecionar após atualizar para evitar reenvio do formulário
        header("Location: " . $_SERVER['PHP_SELF'] . "?pagina=" . $pagina_atual);
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro ao atualizar: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Adicionar nova transação
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação e sanitização dos dados
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $valor = filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
        $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING);

        // Validações adicionais
        if (empty($descricao) || empty($valor) || empty($tipo) || empty($data)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        if (!in_array($tipo, ['entrada', 'saida'])) {
            throw new Exception("Tipo de transação inválido.");
        }

        if (!strtotime($data)) {
            throw new Exception("Data inválida.");
        }

        $stmt = $pdo->prepare("INSERT INTO transacoes (descricao, valor, tipo, data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$descricao, $valor, $tipo, $data]);
        
        $mensagem = "Transação registrada com sucesso!";
        $tipo_mensagem = "sucesso";
        
        // Redirecionar após inserir para evitar reenvio do formulário
        header("Location: " . $_SERVER['PHP_SELF'] . "?pagina=1");
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Calcular saldo
try {
    $stmt = $pdo->query("SELECT 
        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) AS saldo,
        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) AS total_entradas,
        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) AS total_saidas
        FROM transacoes");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $saldo = $resultado['saldo'] ?? 0;
    $total_entradas = $resultado['total_entradas'] ?? 0;
    $total_saidas = $resultado['total_saidas'] ?? 0;
} catch (PDOException $e) {
    $mensagem = "Erro ao calcular saldo: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Atualizar a query de contagem total com os filtros
$sql_count = "SELECT COUNT(*) FROM transacoes WHERE 1=1";
$sql_params = [];

if (!empty($busca)) {
    $sql_count .= " AND descricao LIKE ?";
    $sql_params[] = "%{$busca}%";
}

if (!empty($tipo_filtro)) {
    $sql_count .= " AND tipo = ?";
    $sql_params[] = $tipo_filtro;
}

if (!empty($data_inicio)) {
    $sql_count .= " AND data >= ?";
    $sql_params[] = $data_inicio;
}

if (!empty($data_fim)) {
    $sql_count .= " AND data <= ?";
    $sql_params[] = $data_fim;
}

try {
    $stmt = $pdo->prepare($sql_count);
    $stmt->execute($sql_params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $total_registros = 0;
    $total_paginas = 1;
}

// Atualizar os links de paginação para incluir os parâmetros de busca
$params = [];
if (!empty($busca)) $params['busca'] = $busca;
if (!empty($tipo_filtro)) $params['tipo_filtro'] = $tipo_filtro;
if (!empty($data_inicio)) $params['data_inicio'] = $data_inicio;
if (!empty($data_fim)) $params['data_fim'] = $data_fim;

$query_string = http_build_query($params);
$base_url = '?' . ($query_string ? $query_string . '&' : '');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Fluxo de Caixa</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Sistema de Fluxo de Caixa</h1>
        </header>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="resumo">
            <div class="card">
                <h3>Saldo Atual</h3>
                <p class="valor <?= $saldo >= 0 ? 'positivo' : 'negativo' ?>">
                    R$ <?= number_format($saldo, 2, ',', '.') ?>
                </p>
            </div>
            <div class="card">
                <h3>Total Entradas</h3>
                <p class="valor positivo">R$ <?= number_format($total_entradas, 2, ',', '.') ?></p>
            </div>
            <div class="card">
                <h3>Total Saídas</h3>
                <p class="valor negativo">R$ <?= number_format($total_saidas, 2, ',', '.') ?></p>
            </div>
        </div>

        <section class="formulario">
            <h2>Nova Transação</h2>
            <form method="POST" class="form-transacao">
                <div class="form-grupo">
                    <label for="descricao">Descrição:</label>
                    <input type="text" name="descricao" id="descricao" required 
                           maxlength="255" placeholder="Descreva a transação">
                </div>

                <div class="form-grupo">
                    <label for="valor">Valor:</label>
                    <input type="number" step="0.01" name="valor" id="valor" required 
                           min="0.01" placeholder="0,00">
                </div>

                <div class="form-grupo">
                    <label for="tipo">Tipo:</label>
                    <select name="tipo" id="tipo" required>
                        <option value="">Selecione o tipo</option>
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                    </select>
                </div>

                <div class="form-grupo">
                    <label for="data">Data:</label>
                    <input type="date" name="data" id="data" required>
                </div>

                <button type="submit" class="btn-principal">Adicionar Transação</button>
            </form>
        </section>

        <section class="busca">
            <form method="GET" class="form-busca">
                <div class="form-grupo">
                    <input type="text" name="busca" placeholder="Buscar por descrição..." 
                           value="<?= htmlspecialchars($busca) ?>">
                </div>
                
                <div class="form-grupo">
                    <select name="tipo_filtro">
                        <option value="">Todos os tipos</option>
                        <option value="entrada" <?= $tipo_filtro === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                        <option value="saida" <?= $tipo_filtro === 'saida' ? 'selected' : '' ?>>Saída</option>
                    </select>
                </div>
                
                <div class="form-grupo">
                    <input type="date" name="data_inicio" placeholder="Data início"
                           value="<?= htmlspecialchars($data_inicio) ?>">
                </div>
                
                <div class="form-grupo">
                    <input type="date" name="data_fim" placeholder="Data fim"
                           value="<?= htmlspecialchars($data_fim) ?>">
                </div>
                
                <button type="submit" class="btn-busca">Buscar</button>
                <?php if (!empty($busca) || !empty($tipo_filtro) || !empty($data_inicio) || !empty($data_fim)): ?>
                    <a href="?" class="btn-limpar">Limpar Filtros</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="transacoes">
            <h2>Histórico de Transações</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Atualizar a query principal com os filtros
                    $sql = "SELECT * FROM transacoes WHERE 1=1";

                    if (!empty($busca)) {
                        $sql .= " AND descricao LIKE ?";
                    }

                    if (!empty($tipo_filtro)) {
                        $sql .= " AND tipo = ?";
                    }

                    if (!empty($data_inicio)) {
                        $sql .= " AND data >= ?";
                    }

                    if (!empty($data_fim)) {
                        $sql .= " AND data <= ?";
                    }

                    $sql .= " ORDER BY data DESC, id DESC LIMIT ? OFFSET ?";

                    try {
                        $stmt = $pdo->prepare($sql);
                        $param_index = 1;
                        
                        if (!empty($busca)) {
                            $stmt->bindValue($param_index++, "%{$busca}%", PDO::PARAM_STR);
                        }
                        if (!empty($tipo_filtro)) {
                            $stmt->bindValue($param_index++, $tipo_filtro, PDO::PARAM_STR);
                        }
                        if (!empty($data_inicio)) {
                            $stmt->bindValue($param_index++, $data_inicio, PDO::PARAM_STR);
                        }
                        if (!empty($data_fim)) {
                            $stmt->bindValue($param_index++, $data_fim, PDO::PARAM_STR);
                        }
                        
                        $stmt->bindValue($param_index++, $itens_por_pagina, PDO::PARAM_INT);
                        $stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $valorFormatado = number_format($row['valor'], 2, ',', '.');
                            $tipoClasse = $row['tipo'] === 'entrada' ? 'positivo' : 'negativo';
                            $dataFormatada = date('d/m/Y', strtotime($row['data']));
                    ?>
                        <tr>
                            <td><?= $dataFormatada ?></td>
                            <td><?= htmlspecialchars($row['descricao']) ?></td>
                            <td><?= ucfirst($row['tipo']) ?></td>
                            <td class="<?= $tipoClasse ?>">R$ <?= $valorFormatado ?></td>
                            <td class="acoes">
                                <button onclick="editarTransacao(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                        class="btn-acao btn-editar">
                                    Editar
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-acao btn-excluir">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>Erro ao carregar transações.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php if ($total_paginas > 1): ?>
                <div class="paginacao">
                    <?php if ($pagina_atual > 1): ?>
                        <a href="<?= $base_url ?>pagina=1" class="btn-pagina" title="Primeira página">
                            &laquo;
                        </a>
                        <a href="<?= $base_url ?>pagina=<?= $pagina_atual - 1 ?>" class="btn-pagina" title="Página anterior">
                            &lsaquo;
                        </a>
                    <?php endif; ?>

                    <?php
                    // Determinar quais páginas mostrar
                    $inicio = max(1, $pagina_atual - 2);
                    $fim = min($total_paginas, $pagina_atual + 2);

                    if ($inicio > 1) {
                        echo '<span class="paginacao-ellipsis">...</span>';
                    }

                    for ($i = $inicio; $i <= $fim; $i++) {
                        $classe_ativa = $i === $pagina_atual ? 'ativa' : '';
                        echo "<a href='$base_urlpagina=$i' class='btn-pagina $classe_ativa'>$i</a>";
                    }

                    if ($fim < $total_paginas) {
                        echo '<span class="paginacao-ellipsis">...</span>';
                    }
                    ?>

                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="<?= $base_url ?>pagina=<?= $pagina_atual + 1 ?>" class="btn-pagina" title="Próxima página">
                            &rsaquo;
                        </a>
                        <a href="<?= $base_url ?>pagina=<?= $total_paginas ?>" class="btn-pagina" title="Última página">
                            &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Modal de Edição -->
    <div id="modalEdicao" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Transação</h2>
            <form method="POST" class="form-transacao">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-grupo">
                    <label for="edit-descricao">Descrição:</label>
                    <input type="text" name="descricao" id="edit-descricao" required 
                           maxlength="255" placeholder="Descreva a transação">
                </div>

                <div class="form-grupo">
                    <label for="edit-valor">Valor:</label>
                    <input type="number" step="0.01" name="valor" id="edit-valor" required 
                           min="0.01" placeholder="0,00">
                </div>

                <div class="form-grupo">
                    <label for="edit-tipo">Tipo:</label>
                    <select name="tipo" id="edit-tipo" required>
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                    </select>
                </div>

                <div class="form-grupo">
                    <label for="edit-data">Data:</label>
                    <input type="date" name="data" id="edit-data" required>
                </div>

                <button type="submit" class="btn-principal">Atualizar Transação</button>
            </form>
        </div>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>