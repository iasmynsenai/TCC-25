<?php
// casos.php
require_once 'config.php';

try {
    $pdo = getDB();
    
    // Filtros
    $status = $_GET['status'] ?? 'todos';
    $busca = $_GET['busca'] ?? '';
    $ordenacao = $_GET['ordenacao'] ?? 'recente';
    
    // Construir query
    $sql = "SELECT * FROM casos WHERE 1=1";
    $params = [];
    
    if ($status !== 'todos') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if (!empty($busca)) {
        $sql .= " AND (nome_crianca LIKE ? OR local_desaparecimento LIKE ? OR descricao LIKE ?)";
        $busca_param = "%{$busca}%";
        $params[] = $busca_param;
        $params[] = $busca_param;
        $params[] = $busca_param;
    }
    
    // Ordenação
    switch($ordenacao) {
        case 'antigo':
            $sql .= " ORDER BY data_desaparecimento ASC";
            break;
        case 'nome':
            $sql .= " ORDER BY nome_crianca ASC";
            break;
        default:
            $sql .= " ORDER BY data_desaparecimento DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $casos = $stmt->fetchAll();
    
} catch(Exception $e) {
    $casos = [];
    error_log("Erro ao buscar casos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casos - MCA</title>
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
                    <li><a href="casos.php" class="active">Casos</a></li>
                    <li><a href="dicas.php">Dicas</a></li>
                    <li><a href="anotacoes.php">Anotações</a></li>
                    <li><a href="novo.php">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Casos de Crianças Desaparecidas</h1>
            <p>Ajude-nos a encontrar essas crianças</p>
        </div>
    </section>

    <section class="casos-section">
        <div class="container">
            <!-- Filtros -->
            <div class="filters-bar">
                <form method="GET" action="casos.php" class="filters-form">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="resolvido" <?php echo $status === 'resolvido' ? 'selected' : ''; ?>>Resolvidos</option>
                            <option value="investigando" <?php echo $status === 'investigando' ? 'selected' : ''; ?>>Investigando</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Ordenar por:</label>
                        <select name="ordenacao" onchange="this.form.submit()">
                            <option value="recente" <?php echo $ordenacao === 'recente' ? 'selected' : ''; ?>>Mais Recente</option>
                            <option value="antigo" <?php echo $ordenacao === 'antigo' ? 'selected' : ''; ?>>Mais Antigo</option>
                            <option value="nome" <?php echo $ordenacao === 'nome' ? 'selected' : ''; ?>>Nome A-Z</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label>Buscar:</label>
                        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome, local ou descrição">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <input type="hidden" name="ordenacao" value="<?php echo htmlspecialchars($ordenacao); ?>">
                </form>
            </div>

            <!-- Lista de Casos -->
            <div class="casos-grid">
                <?php if (empty($casos)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Nenhum caso encontrado</h3>
                        <p>Tente ajustar os filtros de busca.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($casos as $caso): ?>
                    <div class="caso-card">
                        <div class="caso-photo">
                            <?php if($caso['foto']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($caso['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($caso['nome_crianca']); ?>">
                            <?php else: ?>
                                <div class="no-photo">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="caso-status status-<?php echo $caso['status']; ?>">
                                <?php echo ucfirst($caso['status']); ?>
                            </div>
                        </div>
                        
                        <div class="caso-info">
                            <h3><?php echo htmlspecialchars($caso['nome_crianca']); ?></h3>
                            <div class="caso-details">
                                <p><strong>Idade:</strong> <?php echo $caso['idade']; ?> anos</p>
                                <p><strong>Desaparecimento:</strong> <?php echo date('d/m/Y', strtotime($caso['data_desaparecimento'])); ?></p>
                                <p><strong>Local:</strong> <?php echo htmlspecialchars($caso['local_desaparecimento']); ?></p>
                            </div>
                            
                            <div class="caso-description">
                                <p><?php echo nl2br(htmlspecialchars(substr($caso['descricao'], 0, 150))); ?><?php echo strlen($caso['descricao']) > 150 ? '...' : ''; ?></p>
                            </div>
                            
                            <div class="caso-contact">
                                <p><strong>Contato:</strong> <?php echo htmlspecialchars($caso['contato_responsavel']); ?></p>
                                <?php if(isset($caso['telefone']) && !empty($caso['telefone'])): ?>
                                    <p><strong>Telefone:</strong> <?php echo htmlspecialchars($caso['telefone']); ?></p>
                                <?php endif; ?>
                                <?php if(isset($caso['email']) && !empty($caso['email'])): ?>
                                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($caso['email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="caso-actions">
                            <a href="caso.php?id=<?php echo $caso['id']; ?>" class="btn btn-primary">Ver Detalhes</a>
                            <a href="dicas.php?caso_id=<?php echo $caso['id']; ?>" class="btn btn-secondary">Enviar Dica</a>
                            <?php if($caso['status'] === 'ativo'): ?>
                                <button class="btn btn-share" onclick="compartilharCaso(<?php echo $caso['id']; ?>)">
                                    <i class="fas fa-share"></i> Compartilhar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($casos)): ?>
                <div class="casos-summary">
                    <p>Mostrando <?php echo count($casos); ?> caso(s)</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal de Compartilhamento -->
    <div id="modal-compartilhar" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h2>Compartilhar Caso</h2>
            <p>Ajude a divulgar este caso nas redes sociais:</p>
            <div class="share-buttons">
                <button class="btn btn-facebook" onclick="compartilharFacebook()">
                    <i class="fab fa-facebook"></i> Facebook
                </button>
                <button class="btn btn-twitter" onclick="compartilharTwitter()">
                    <i class="fab fa-twitter"></i> Twitter
                </button>
                <button class="btn btn-whatsapp" onclick="compartilharWhatsApp()">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="btn btn-secondary" onclick="copiarLink()">
                    <i class="fas fa-copy"></i> Copiar Link
                </button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 MCA - Missing Child Alert. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        let casoAtualId = null;
        
        function compartilharCaso(id) {
            casoAtualId = id;
            document.getElementById('modal-compartilhar').style.display = 'block';
        }
        
        function fecharModal() {
            document.getElementById('modal-compartilhar').style.display = 'none';
        }
        
        function compartilharFacebook() {
            const url = `${window.location.origin}/caso.php?id=${casoAtualId}`;
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
        }
        
        function compartilharTwitter() {
            const url = `${window.location.origin}/caso.php?id=${casoAtualId}`;
            const texto = 'Ajude a encontrar esta criança desaparecida:';
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(texto)}&url=${encodeURIComponent(url)}`, '_blank');
        }
        
        function compartilharWhatsApp() {
            const url = `${window.location.origin}/caso.php?id=${casoAtualId}`;
            const texto = `Ajude a encontrar esta criança desaparecida: ${url}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank');
        }
        
        function copiarLink() {
            const url = `${window.location.origin}/caso.php?id=${casoAtualId}`;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copiado para a área de transferência!');
            });
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modal-compartilhar');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>