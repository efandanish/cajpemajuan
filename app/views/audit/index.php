<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$typeOptions = [
    'project' => 'Projek Pembangunan', 'project_payment_schedule' => 'Jadual Bayaran Projek',
    'project_expense' => 'Perbelanjaan Projek', 'project_variation' => 'Variasi Projek',
    'development_charge' => 'Caj Pemajuan', 'development_charge_payment' => 'Bayaran Caj',
    'receipt' => 'Resit Bayaran (muat naik / ganti / buang)', 'user' => 'Pengguna', 'notification' => 'Notifikasi'
];
$actionBadge = ['CREATE' => 'status-approved', 'UPDATE' => 'status-review', 'DELETE' => 'status-rejected'];
$pageUrl = function ($page) use ($filters) {
    return ReportingYear::url('audit?' . http_build_query(array_filter($filters + ['page' => $page], function ($v) { return $v !== '' && $v !== 1; })));
};
?>
<div class="module-page">
    <div class="module-container">
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="card-title fw-bold">Log Audit</h5>
                    <div class="small text-muted">
                        <?= $isGlobalAdmin ? 'Semua Jabatan' : 'Jabatan: ' . $e($scopeDepartment) ?>
                        · Jumlah rekod: <?= (int) $total ?>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="<?= PUBLIC_ROOT ?>audit" class="row g-2 mb-3">
                    <input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">
                    <div class="col-md-3">
                        <input type="text" name="q" value="<?= $e($filters['q']) ?>" maxlength="100" class="form-control form-control-sm" placeholder="Cari nama atau emel pengguna">
                    </div>
                    <?php if ($isGlobalAdmin): ?>
                    <div class="col-md-2">
                        <select name="department" class="form-select form-select-sm">
                            <option value="">Semua Jabatan</option>
                            <?php foreach (Departments::options() as $department): ?>
                                <option value="<?= $e($department) ?>" <?= $filters['department'] === $department ? 'selected' : '' ?>><?= $e($department) ?></option>
                            <?php endforeach; ?>
                            <option value="unassigned" <?= $filters['department'] === 'unassigned' ? 'selected' : '' ?>><?= $e(AuditPresenter::UNASSIGNED) ?> / tiada Jabatan</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <select name="action" class="form-select form-select-sm">
                            <option value="">Semua Tindakan</option>
                            <?php foreach (['CREATE', 'UPDATE', 'DELETE'] as $action): ?>
                                <option value="<?= $action ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= $action ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua Jenis Rekod</option>
                            <?php foreach ($typeOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1"><input type="date" name="from" value="<?= $e($filters['from']) ?>" class="form-control form-control-sm" title="Dari tarikh"></div>
                    <div class="col-md-1"><input type="date" name="to" value="<?= $e($filters['to']) ?>" class="form-control form-control-sm" title="Hingga tarikh"></div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary">Tapis</button>
                        <a href="<?= ReportingYear::url('audit') ?>" class="btn btn-sm btn-outline-secondary">Set Semula</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tarikh &amp; Masa</th>
                                <th>Pengguna</th>
                                <th>Emel</th>
                                <th>Tindakan</th>
                                <th>Jenis Rekod</th>
                                <th>Rujukan Rekod</th>
                                <th>Jabatan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$audits): ?>
                            <tr><td colspan="8" class="text-center text-muted">Tiada rekod audit untuk tapisan ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($audits as $audit):
                            $details = AuditPresenter::details($audit['details']);
                            $receipt = AuditPresenter::receiptEvent($audit['entity_type'], $details);
                            $snapshot = AuditPresenter::snapshot($audit['action'], $details);
                            $fields = AuditPresenter::fieldRows($details);
                        ?>
                            <tr>
                                <td class="text-nowrap"><?= $e(date('d/m/Y H:i:s', strtotime($audit['created_at']))) ?></td>
                                <td><?= $e($audit['actor_name'] !== null ? $audit['actor_name'] : 'Sistem') ?></td>
                                <td><?= $e($audit['actor_email'] !== null ? $audit['actor_email'] : '—') ?></td>
                                <td><span class="status-badge <?= $actionBadge[$audit['action']] ?? '' ?>"><?= $e($audit['action']) ?></span></td>
                                <td>
                                    <?= $e(AuditPresenter::typeLabel($audit['entity_type'], $receipt)) ?>
                                    <?php if ($receipt !== null): ?><div><span class="badge bg-info text-dark"><?= $e(AuditPresenter::receiptLabel($receipt)) ?></span></div><?php endif; ?>
                                </td>
                                <td><?= $e(AuditPresenter::reference($audit['entity_type'], (int) $audit['entity_id'], $snapshot, $details)) ?></td>
                                <td><?= $e(AuditPresenter::departmentLabel($audit['entity_type'], $audit['resolved_department'], $audit['previous_department'])) ?></td>
                                <td>
                                    <?= $e(AuditPresenter::description($audit, $details)) ?>
                                    <?php if ($fields['rows']): ?>
                                    <details class="mt-1">
                                        <summary class="small"><?= $audit['action'] === 'DELETE' ? 'Butiran rekod sebelum dipadam' : ($fields['has_before'] ? 'Nilai sebelum &amp; selepas' : 'Butiran rekod') ?></summary>
                                        <table class="table table-sm small mb-0">
                                            <thead><tr><th>Medan</th><?php if ($fields['has_before']): ?><th>Sebelum</th><?php endif; ?><?php if ($fields['has_after']): ?><th>Selepas</th><?php endif; ?></tr></thead>
                                            <tbody>
                                            <?php foreach ($fields['rows'] as $row): ?>
                                                <tr class="<?= $row['changed'] ? 'table-warning' : '' ?>">
                                                    <td><?= $e($row['field']) ?></td>
                                                    <?php if ($fields['has_before']): ?><td><?= $e($row['before']) ?></td><?php endif; ?>
                                                    <?php if ($fields['has_after']): ?><td><?= $e($row['after']) ?></td><?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pages > 1): ?>
                <nav aria-label="Halaman log audit">
                    <ul class="pagination pagination-sm">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $e($pageUrl(max(1, $page - 1))) ?>">&laquo;</a></li>
                        <li class="page-item disabled"><span class="page-link">Halaman <?= (int) $page ?> / <?= (int) $pages ?></span></li>
                        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $e($pageUrl(min($pages, $page + 1))) ?>">&raquo;</a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
