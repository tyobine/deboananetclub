<?php include __DIR__ . '/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-plus-circle"></i> Novo Plano</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/plans/create">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nome do Plano</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Plano 1 Hora" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Preço (em centavos)</label>
                            <input type="number" name="price_cents" class="form-control" placeholder="Ex: 500" required>
                            <small class="text-muted"><i class="fa-solid fa-info-circle"></i> Ex: 500 = R$ 5,00 | 1000 = R$ 10,00</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Duração (em minutos)</label>
                            <input type="number" name="duration_minutes" class="form-control" placeholder="Ex: 60" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Criar Novo Plano</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-box-open"></i> Planos Ativos e Cadastrados</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Nome</th>
                                    <th>Preço</th>
                                    <th>Duração</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td class="ps-3"><?= $plan['id']; ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($plan['name']); ?></td>
                                        <td>R$ <?= number_format($plan['price_cents'] / 100, 2, ',', '.'); ?></td>
                                        <td><?= $plan['duration_minutes']; ?> min</td>
                                        <td>
                                            <form method="POST" action="/admin/plans/toggle" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $plan['id']; ?>">
                                                <?php if (isset($plan['ativo']) && $plan['ativo']): ?>
                                                    <button type="submit" class="btn btn-sm btn-success px-3 fw-bold">ON</button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-secondary px-3 fw-bold">OFF</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning fw-bold text-dark me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $plan['id']; ?>">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <form method="POST" action="/admin/plans/delete" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $plan['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza de que deseja excluir este plano?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal<?= $plan['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-edit"></i> Editar Plano</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="/admin/plans/update">
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="id" value="<?= $plan['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Nome do Plano</label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($plan['name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Preço (em centavos)</label>
                                                            <input type="number" name="price_cents" class="form-control" value="<?= $plan['price_cents']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Duração (em minutos)</label>
                                                            <input type="number" name="duration_minutes" class="form-control" value="<?= $plan['duration_minutes']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary fw-bold">Salvar Alterações</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>