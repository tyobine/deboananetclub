<?php include __DIR__ . '/header.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-gauge"></i> Dashboard Novo MikroTik</h2>
        <span class="badge bg-primary p-2"><i class="fa-solid fa-user-shield"></i> Admin Logado</span>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                        <i class="fa-solid fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Faturamento Hoje</h6>
                        <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoHoje, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                        <i class="fa-solid fa-chart-line fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Faturamento Mês</h6>
                        <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoMes, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                        <i class="fa-solid fa-users fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Clientes Conectados</h6>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($clientesAtivos) ?> <small class="text-muted fs-6">no Hotspot</small></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="card-title mb-4"><i class="fa-solid fa-chart-bar"></i> Desempenho de Vendas (Últimos 7 dias)</h5>
                <div style="height: 350px; position: relative;">
                    <canvas id="graficoVendas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labelsDias = <?= json_encode($graficoDados['dias'] ?? ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']) ?>;
    const valoresVendas = <?= json_encode($graficoDados['valores'] ?? [0, 0, 0, 0, 0, 0, 0]) ?>;

    const ctx = document.getElementById('graficoVendas').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsDias,
            datasets: [{
                label: 'Faturamento Diário (R$)',
                data: valoresVendas,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(2).replace('.', ',');
                        }
                    }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>