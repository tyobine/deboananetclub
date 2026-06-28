<?php include __DIR__ . '/header.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list-ul"></i> Histórico de Transações Recentes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Endereço MAC</th>
                            <th>WhatsApp (Lead)</th>
                            <th>Plano Contratado</th>
                            <th>Valor Pago</th>
                            <th>Status Sessão</th>
                            <th>Data de Vencimento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td class="ps-3"><?= $txn['id']; ?></td>
                                <td>
                                    <small class="font-monospace fw-bold"><?= htmlspecialchars($txn['mac_address']); ?></small><br>
                                    <small class="text-muted" style="font-size: 0.75rem;">IP: <?= htmlspecialchars($txn['ip_address']); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($txn['whatsapp'])): ?>
                                        <a href="https://wa.me/55<?= $txn['whatsapp'] ?>" target="_blank" class="badge bg-success text-decoration-none p-2">
                                            <i class="fa-brands fa-whatsapp"></i> <?= $txn['whatsapp']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($txn['plan_name'] ?? 'Plano Deletado'); ?></td>
                                <td class="fw-bold text-dark">R$ <?= isset($txn['amount_cents']) ? number_format($txn['amount_cents'] / 100, 2, ',', '.') : '0,00'; ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pendente' => 'warning text-dark',
                                        'processando' => 'info text-white',
                                        'ativo' => 'success text-white',
                                        'expirado' => 'secondary text-white',
                                        'estornado' => 'danger text-white'
                                    ];
                                    $statusLabels = [
                                        'pendente' => 'Pendente',
                                        'processando' => 'Processando',
                                        'ativo' => 'Ativo',
                                        'expirado' => 'Expirado',
                                        'estornado' => 'Estornado'
                                    ];
                                    $color = $statusColors[$txn['status']] ?? 'secondary';
                                    $label = $statusLabels[$txn['status']] ?? ucfirst($txn['status']);
                                    ?>
                                    <span class="badge bg-<?= $color; ?> px-2 py-1 fw-bold"><?= $label; ?></span>
                                </td>
                                <td>
                                    <small class="text-muted fw-bold">
                                        <?= !empty($txn['expira_em']) ? date('d/m/Y H:i', strtotime($txn['expira_em'])) : '-'; ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="alert alert-info text-center m-4">
                    <i class="fa-solid fa-circle-info"></i> Nenhuma transação financeira foi registrada até o momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>