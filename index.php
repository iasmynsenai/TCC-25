<?php
// index.php
require_once 'config.php';

try {
    $pdo = getDB();
    
    // Buscar estatísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM casos WHERE status = 'ativo'");
    $casosAtivos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM casos WHERE status = 'resolvido'");
    $casosResolvidos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM anotacoes");
    $totalAnotacoes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM dicas WHERE status = 'pendente'");
    $dicasPendentes = $stmt->fetch()['total'];
    
    // Buscar casos recentes
    $stmt = $pdo->query("SELECT * FROM casos ORDER BY data_criacao DESC LIMIT 4");
    $casosRecentes = $stmt->fetchAll();
    
} catch(Exception $e) {
    $casosAtivos = 0;
    $casosResolvidos = 0;
    $totalAnotacoes = 0;
    $dicasPendentes = 0;
    $casosRecentes = [];
    error_log("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCA - Missing Child Alert</title>
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
                    <li><a href="index.php" class="active">Início</a></li>
                    <li><a href="casos.php">Casos</a></li>
                    <li><a href="dicas.php">Dicas</a></li>
                    <li><a href="anotacoes.php">Anotações</a></li>
                    <li><a href="novo.php">Novo Caso</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Sistema de Alerta para Crianças Desaparecidas</h1>
                <p>Unindo esforços para trazer as crianças de volta para casa</p>
                <div class="hero-actions">
                    <a href="novo.php" class="btn btn-primary">Reportar Desaparecimento</a>
                    <a href="casos.php" class="btn btn-secondary">Ver Casos Ativos</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $casosAtivos; ?></h3>
                        <p>Casos Ativos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $casosResolvidos; ?></h3>
                        <p>Casos Resolvidos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalAnotacoes; ?></h3>
                        <p>Anotações</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $dicasPendentes; ?></h3>
                        <p>Dicas Pendentes</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Casos Recentes -->
    <section class="recent-cases">
        <div class="container">
            <h2>Casos Recentes</h2>
            <div class="cases-grid">
                <?php foreach($casosRecentes as $caso): ?>
                <div class="case-card">
                    <div class="case-photo">
                        <?php if($caso['foto']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($caso['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($caso['nome_crianca']); ?>">
                        <?php else: ?>
                            <div class="no-photo">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="case-info">
                        <h3><?php echo htmlspecialchars($caso['nome_crianca']); ?></h3>
                        <p class="case-age"><?php echo $caso['idade']; ?> anos</p>
                        <p class="case-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($caso['local_desaparecimento']); ?>
                        </p>
                        <p class="case-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($caso['data_desaparecimento'])); ?>
                        </p>
                        <div class="case-status status-<?php echo $caso['status']; ?>">
                            <?php echo ucfirst($caso['status']); ?>
                        </div>
                    </div>
                    <div class="case-actions">
                        <a href="caso.php?id=<?php echo $caso['id']; ?>" class="btn btn-sm">Ver Detalhes</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="casos.php" class="btn btn-outline">Ver Todos os Casos</a>
            </div>
        </div>
    </section>

    <!-- Como Ajudar -->
    <section class="help-section">
        <div class="container">
            <h2>Como Você Pode Ajudar</h2>
            <div class="help-grid">
                <div class="help-card">
                    <div class="help-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Fique Atento</h3>
                    <p>Observe as fotos e descrições das crianças desaparecidas. Sua atenção pode fazer a diferença.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Relate Informações</h3>
                    <p>Se você viu algo suspeito ou tem informações, entre em contato imediatamente.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">
                        <i class="fas fa-share"></i>
                    </div>
                    <h3>Compartilhe</h3>
                    <p>Divulgue os casos em suas redes sociais. Quanto mais pessoas souberem, maior a chance de encontrar.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>MCA - Missing Child Alert</h4>
                    <p>Sistema dedicado a ajudar famílias a reencontrar seus filhos.</p>
                </div>
                <div class="footer-section">
                    <h4>Contatos Importantes</h4>
                    <p><strong>Disque 100:</strong> Denúncias</p>
                    <p><strong>190:</strong> Polícia Militar</p>
                    <p><strong>197:</strong> Polícia Civil</p>
                </div>
                <div class="footer-section">
                    <h4>Links Úteis</h4>
                    <ul>
                        <li><a href="casos.php">Casos Ativos</a></li>
                        <li><a href="dicas.php">Enviar Dica</a></li>
                        <li><a href="novo.php">Reportar Caso</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MCA - Missing Child Alert. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>