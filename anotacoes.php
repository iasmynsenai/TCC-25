<?php
// anotacoes.php
require_once 'config.php';

try {
    $pdo = getDB();
    
    // Buscar casos para filtros
    $stmt = $pdo->query("SELECT id, nome_crianca FROM casos ORDER BY nome_crianca");
    $casos = $stmt->fetchAll();
    
    // Filtros
    $categoria = $_GET['categoria'] ?? 'todas';
    $caso_id = $_GET['caso_id'] ?? 'todos';
    $busca = $_GET['busca'] ?? '';
    $ordenacao = $_GET['ordenacao'] ?? 'recente';
    
    // Construir query para anotações
    $sql = "SELECT a.*, c.nome_crianca 
            FROM anotacoes a 
            LEFT JOIN casos c ON a.caso_id = c.id 
            WHERE 1=1";
    $params = [];
    
    if ($categoria !== 'todas') {
        $sql .= " AND a.categoria = ?";
        $params[] = $categoria;
    }
    
    if ($caso_id !== 'todos') {
        $sql .= " AND a.caso_id = ?";
        $params[] = $caso_id;
    }
    
    if (!empty($busca)) {
        $sql .= " AND (a.titulo LIKE ? OR a.conteudo LIKE ? OR a.tags LIKE ?)";
        $busca_param = "%{$busca}%";
        $params[] = $busca_param;
        $params[] = $busca_param;
        $params[] = $busca_param;
    }
    
    // Ordenação
    switch($ordenacao) {
        case 'antiga':
            $sql .= " ORDER BY a.data_criacao ASC";
            break;
        case 'prioridade':
            $sql .= " ORDER BY a.urgente DESC, a.data_criacao DESC";
            break;
        default:
            $sql .= " ORDER BY a.data_criacao DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $anotacoes = $stmt->fetchAll();
    
    // Estatísticas
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM anotacoes")->fetchColumn(),
        'pistas' => $pdo->query("SELECT COUNT(*) FROM anotacoes WHERE categoria = 'pista'")->fetchColumn(),
        'avistamentos' => $pdo->query("SELECT COUNT(*) FROM anotacoes WHERE categoria = 'avistamento'")->fetchColumn(),
        'urgentes' => $pdo->query("SELECT COUNT(*) FROM anotacoes WHERE urgente = 1")->fetchColumn()
    ];
    
} catch(Exception $e) {
    $casos = [];
    $anotacoes = [];
    $stats = ['total' => 0, 'pistas' => 0, 'avistamentos' => 0, 'urgentes' => 0];
    error_log("Erro ao buscar anotações: " . $e->getMessage());
}

// Mapear ícones das categorias
$categoria_icones = [
    'pista' => '🔍',
    'avistamento' => '👁️',
    'contato' => '📞',
    'observacao' => '📝',
    'urgente' => '⚠️'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anotações - MCA</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-search-location logo-icon"></i>
                <div>
                    <h1>MCA</h1>
                    <span>Missing Child Alert</span>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="casos.php">Casos</a></li>
                    <li><a href="dicas.php">Dicas</a></li>
                    <li><a href="anotacoes.php" class="active">Anotações</a></li>
                    <li><a href="novo.php">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Minhas Anotações</h1>
            <p>Organize informações e pistas importantes</p>
        </div>
    </section>

    <section class="anotacoes-section">
        <div class="container">
            <div class="anotacoes-layout">
                <!-- Sidebar de Filtros -->
                <div class="anotacoes-sidebar">
                    <button class="btn btn-primary full-width" onclick="abrirNovaAnotacao()">
                        ➕ Nova Anotação
                    </button>

                    <div class="filter-section">
                        <h3>Filtrar por</h3>
                        
                        <form method="GET" action="anotacoes.php" id="filtros-form">
                            <div class="filter-group">
                                <label>Categoria:</label>
                                <select name="categoria" onchange="document.getElementById('filtros-form').submit()">
                                    <option value="todas" <?php echo $categoria === 'todas' ? 'selected' : ''; ?>>Todas</option>
                                    <option value="pista" <?php echo $categoria === 'pista' ? 'selected' : ''; ?>>Pista</option>
                                    <option value="avistamento" <?php echo $categoria === 'avistamento' ? 'selected' : ''; ?>>Avistamento</option>
                                    <option value="contato" <?php echo $categoria === 'contato' ? 'selected' : ''; ?>>Contato</option>
                                    <option value="observacao" <?php echo $categoria === 'observacao' ? 'selected' : ''; ?>>Observação</option>
                                    <option value="urgente" <?php echo $categoria === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Caso:</label>
                                <select name="caso_id" onchange="document.getElementById('filtros-form').submit()">
                                    <option value="todos" <?php echo $caso_id === 'todos' ? 'selected' : ''; ?>>Todos os Casos</option>
                                    <?php foreach($casos as $caso): ?>
                                        <option value="<?php echo $caso['id']; ?>" <?php echo $caso_id == $caso['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($caso['nome_crianca']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Ordenar por:</label>
                                <select name="ordenacao" onchange="document.getElementById('filtros-form').submit()">
                                    <option value="recente" <?php echo $ordenacao === 'recente' ? 'selected' : ''; ?>>Mais Recente</option>
                                    <option value="antiga" <?php echo $ordenacao === 'antiga' ? 'selected' : ''; ?>>Mais Antiga</option>
                                    <option value="prioridade" <?php echo $ordenacao === 'prioridade' ? 'selected' : ''; ?>>Prioridade</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Buscar:</label>
                                <div class="search-input">
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Pesquisar...">
                                    <button type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="stats-mini">
                        <h4>Estatísticas</h4>
                        <div class="stat-mini">
                            <span class="stat-num"><?php echo $stats['total']; ?></span>
                            <span class="stat-text">Total de Anotações</span>
                        </div>
                        <div class="stat-mini">
                            <span class="stat-num"><?php echo $stats['pistas']; ?></span>
                            <span class="stat-text">Pistas</span>
                        </div>
                        <div class="stat-mini">
                            <span class="stat-num"><?php echo $stats['avistamentos']; ?></span>
                            <span class="stat-text">Avistamentos</span>
                        </div>
                        <div class="stat-mini">
                            <span class="stat-num"><?php echo $stats['urgentes']; ?></span>
                            <span class="stat-text">Urgentes</span>
                        </div>
                    </div>
                </div>

                <!-- Grid de Anotações -->
                <div class="anotacoes-grid" id="anotacoes-container">
                    <?php if (empty($anotacoes)): ?>
                        <div class="no-results">
                            <i class="fas fa-sticky-note"></i>
                            <h3>Nenhuma anotação encontrada</h3>
                            <p>Tente ajustar os filtros ou <a href="#" onclick="abrirNovaAnotacao()">criar uma nova anotação</a>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($anotacoes as $anotacao): ?>
                        <div class="anotacao-card" data-categoria="<?php echo $anotacao['categoria']; ?>" data-caso="<?php echo $anotacao['caso_id']; ?>">
                            <div class="anotacao-header">
                                <div class="anotacao-categoria categoria-<?php echo $anotacao['categoria']; ?>">
                                    <?php echo $categoria_icones[$anotacao['categoria']] ?? '📝'; ?> 
                                    <?php echo ucfirst($anotacao['categoria']); ?>
                                </div>
                                <div class="anotacao-data">
                                    <?php echo date('d/m/Y - H:i', strtotime($anotacao['data_criacao'])); ?>
                                </div>
                                <?php if($anotacao['urgente']): ?>
                                    <div class="anotacao-urgente">⚠️ URGENTE</div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="anotacao-titulo"><?php echo htmlspecialchars($anotacao['titulo']); ?></h3>
                            <p class="anotacao-caso">Caso: <?php echo htmlspecialchars($anotacao['nome_crianca'] ?? 'Não especificado'); ?></p>
                            <p class="anotacao-conteudo">
                                <?php echo nl2br(htmlspecialchars(substr($anotacao['conteudo'], 0, 200))); ?>
                                <?php echo strlen($anotacao['conteudo']) > 200 ? '...' : ''; ?>
                            </p>
                            
                            <?php if($anotacao['tags']): ?>
                                <div class="anotacao-tags">
                                    <?php 
                                    $tags = explode(',', $anotacao['tags']);
                                    foreach($tags as $tag): 
                                        $tag = trim($tag);
                                        if(!empty($tag)):
                                    ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="anotacao-actions">
                                <button class="btn-icon" onclick="editarAnotacao(<?php echo $anotacao['id']; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon" onclick="compartilharAnotacao(<?php echo $anotacao['id']; ?>)" title="Compartilhar">
                                    <i class="fas fa-share"></i>
                                </button>
                                <button class="btn-icon btn-danger" onclick="excluirAnotacao(<?php echo $anotacao['id']; ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Nova Anotação -->
    <div id="modal-anotacao" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h2 id="modal-titulo">Nova Anotação</h2>
            <form id="form-anotacao" action="salvar_anotacao.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="anotacao_id" name="anotacao_id" value="">
                
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" class="form-control" name="titulo" id="titulo" required placeholder="Digite um título para a anotação">
                </div>

                <div class="form-group">
                    <label>Categoria *</label>
                    <select class="form-control" name="categoria" id="categoria" required>
                        <option value="">Selecione...</option>
                        <option value="pista">🔍 Pista</option>
                        <option value="avistamento">👁️ Avistamento</option>
                        <option value="contato">📞 Contato</option>
                        <option value="observacao">📝 Observação</option>
                        <option value="urgente">⚠️ Urgente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Caso Relacionado *</label>
                    <select class="form-control" name="caso_id" id="caso" required>
                        <option value="">Selecione...</option>
                        <?php foreach($casos as $caso): ?>
                            <option value="<?php echo $caso['id']; ?>">
                                <?php echo htmlspecialchars($caso['nome_crianca']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Conteúdo *</label>
                    <textarea class="form-control" name="conteudo" id="conteudo" rows="6" required placeholder="Descreva detalhadamente a informação..."></textarea>
                </div>

                <div class="form-group">
                    <label>Tags (separadas por vírgula)</label>
                    <input type="text" class="form-control" name="tags" id="tags" placeholder="Ex: testemunha, local, horário">
                </div>

                <div class="form-group">
                    <label>Anexar Arquivos</label>
                    <input type="file" class="form-control" name="anexos[]" id="anexos" multiple accept="image/*,video/*,.pdf,.doc,.docx">
                    <small>Imagens, vídeos ou documentos relacionados</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="urgente" id="urgente" value="1"> Marcar como urgente
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar Anotação</button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 MCA - Missing Child Alert. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        function abrirNovaAnotacao() {
            document.getElementById('modal-titulo').textContent = 'Nova Anotação';
            document.getElementById('anotacao_id').value = '';
            document.getElementById('form-anotacao').reset();
            document.getElementById('modal-anotacao').style.display = 'block';
        }
        
        function editarAnotacao(id) {
            // Buscar dados da anotação via AJAX
            fetch(`buscar_anotacao.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal-titulo').textContent = 'Editar Anotação';
                        document.getElementById('anotacao_id').value = data.anotacao.id;
                        document.getElementById('titulo').value = data.anotacao.titulo;
                        document.getElementById('categoria').value = data.anotacao.categoria;
                        document.getElementById('caso').value = data.anotacao.caso_id;
                        document.getElementById('conteudo').value = data.anotacao.conteudo;
                        document.getElementById('tags').value = data.anotacao.tags || '';
                        document.getElementById('urgente').checked = data.anotacao.urgente == 1;
                        document.getElementById('modal-anotacao').style.display = 'block';
                    } else {
                        alert('Erro ao carregar anotação: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar anotação');
                });
        }
        
        function excluirAnotacao(id) {
            if (confirm('Tem certeza que deseja excluir esta anotação?')) {
                fetch(`excluir_anotacao.php?id=${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erro ao excluir anotação: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao excluir anotação');
                    });
            }
        }
        
        function compartilharAnotacao(id) {
            const url = `${window.location.origin}/anotacao.php?id=${id}`;
            if (navigator.share) {
                navigator.share({
                    title: 'Anotação - MCA',
                    text: 'Confira esta anotação importante:',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copiado para a área de transferência!');
                });
            }
        }
        
        function fecharModal() {
            document.getElementById('modal-anotacao').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modal-anotacao');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>