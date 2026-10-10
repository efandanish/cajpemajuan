<?php if (!empty($developmentCharges)): ?>

    <?php foreach ($developmentCharges as $charge): ?>

        <?php

        $payments =
            $this->controller
                ->developmentCharge
                ->getPayments(
                    $charge['id']
                );

        $hasPayments =
            !empty($payments);

        $hasPaidPayments =
            $this->controller
                ->developmentCharge
                ->hasPaidPayments(
                    $charge['id']
                );

        ?>

        <div
            class="modal fade"
            id="editChargeModal<?= $charge['id'] ?>"
            tabindex="-1"
            data-bs-focus="false"
        >

            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                <div class="modal-content">

                    <form
                        method="POST"
                        action="<?= PUBLIC_ROOT ?>charge/update/<?= $charge['id'] ?>"
                        class="edit-charge-form"
                    >

                        <div class="modal-header">

                            <div>

                                <h5 class="modal-title fw-bold">
                                    Edit Caj Pemajuan
                                </h5>

                                <div class="small text-muted">
                                    <?= htmlspecialchars($charge['osc_id']) ?>
                                </div>

                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>

                        </div>


                        <div class="modal-body">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Nama Pemaju
                                    </label>

                                    <input
                                        type="text"
                                        name="developer_name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['developer_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Nama Projek
                                    </label>

                                    <input
                                        type="text"
                                        name="project_name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['project_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        ID OSC
                                    </label>

                                    <input
                                        type="text"
                                        name="osc_id"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['osc_id']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        No. Lot / PT
                                    </label>

                                    <input
                                        type="text"
                                        name="lot_number"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['lot_number']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Mukim
                                    </label>

                                    <input
                                        type="text"
                                        name="mukim"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['mukim']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        No. Fail
                                    </label>

                                    <input
                                        type="text"
                                        name="file_number"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['file_number']) ?>"
                                    >

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Jumlah Caj (RM)
                                    </label>

                                    <input
                                        type="number"
                                        name="charge_amount"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        value="<?= htmlspecialchars($charge['charge_amount']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Keputusan OSC
                                    </label>

                                    <select
                                        name="osc_decision"
                                        class="form-select"
                                    >

                                        <option
                                            value="Lulus"
                                            <?= $charge['osc_decision'] === 'Lulus' ? 'selected' : '' ?>
                                        >
                                            Lulus
                                        </option>

                                        <option
                                            value="Tangguh"
                                            <?= $charge['osc_decision'] === 'Tangguh' ? 'selected' : '' ?>
                                        >
                                            Tangguh
                                        </option>

                                        <option
                                            value="Ditolak"
                                            <?= $charge['osc_decision'] === 'Ditolak' ? 'selected' : '' ?>
                                        >
                                            Ditolak
                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Pelan Bayaran
                                    </label>

                                    <?php if ($hasPaidPayments): ?>

                                        <input
                                            type="hidden"
                                            name="payment_plan"
                                            value="<?= htmlspecialchars($charge['payment_plan']) ?>"
                                        >

                                        <select
                                            class="form-select"
                                            disabled
                                        >

                                            <option selected>
                                                <?= htmlspecialchars($charge['payment_plan']) ?>
                                            </option>

                                        </select>

                                        <div class="form-text">
                                            Pelan bayaran dikunci kerana jadual bayaran telah diwujudkan.
                                        </div>

                                    <?php else: ?>

                                        <select
                                            name="payment_plan"
                                            class="form-select edit-payment-plan"
                                            required
                                        >

                                            <option
                                                value="Bayaran Penuh"
                                                <?= $charge['payment_plan'] === 'Bayaran Penuh' ? 'selected' : '' ?>
                                            >
                                                Bayaran Penuh
                                            </option>

                                            <option
                                                value="3 Ansuran"
                                                <?= $charge['payment_plan'] === '3 Ansuran' ? 'selected' : '' ?>
                                            >
                                                3 Ansuran
                                            </option>

                                            <option
                                                value="4 Ansuran"
                                                <?= $charge['payment_plan'] === '4 Ansuran' ? 'selected' : '' ?>
                                            >
                                                4 Ansuran
                                            </option>

                                        </select>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <?php if ($hasPayments): ?>

                                <hr class="my-4">


                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <div>

                                        <h6 class="fw-bold mb-1">
                                            Jadual Ansuran
                                        </h6>

                                        <div class="small text-muted">
                                            Ansuran yang telah dibayar tidak boleh diubah.
                                        </div>

                                    </div>

                                </div>


                                <div class="d-flex flex-column gap-3">

                                <div class="edit-installment-container">

                                        <?php foreach ($payments as $payment): ?>

                                            <div class="border rounded p-3">

                                                <div class="d-flex justify-content-between align-items-center mb-3">

                                                    <div class="fw-bold">
                                                        Ansuran #<?= (int) $payment['installment_no'] ?>
                                                    </div>


                                                    <?php if ($payment['status'] === 'Dibayar'): ?>

                                                        <span class="status-badge status-approved">
                                                            Telah Dibayar
                                                        </span>

                                                    <?php else: ?>

                                                        <span class="status-badge status-pending">
                                                            Menunggu
                                                        </span>

                                                    <?php endif; ?>

                                                </div>


                                                <?php if ($payment['status'] === 'Dibayar'): ?>

                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Jumlah (RM)
                                                            </label>

                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                value="<?= number_format($payment['amount'], 2, '.', '') ?>"
                                                                disabled
                                                            >

                                                        </div>


                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tarikh Akhir
                                                            </label>

                                                            <input
                                                                type="date"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($payment['due_date'] ?: '') ?>"
                                                                disabled
                                                            >

                                                        </div>


                                                        <div class="col-12">

                                                            <div class="small text-muted">
                                                                Dibayar pada:
                                                                <strong>
                                                                    <?= $payment['payment_date']
                                                                        ? date(
                                                                            'd/m/Y',
                                                                            strtotime($payment['payment_date'])
                                                                        )
                                                                        : '-'
                                                                    ?>
                                                                </strong>
                                                            </div>

                                                        </div>

                                                    </div>


                                                <?php else: ?>

                                                    <input
                                                        type="hidden"
                                                        name="payment_schedule_id[]"
                                                        value="<?= (int) $payment['id'] ?>"
                                                    >


                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Jumlah (RM)
                                                            </label>

                                                            <input
                                                                type="number"
                                                                name="payment_schedule_amount[]"
                                                                class="form-control"
                                                                min="0.01"
                                                                step="0.01"
                                                                value="<?= htmlspecialchars($payment['amount']) ?>"
                                                                required
                                                            >

                                                        </div>


                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tarikh Akhir
                                                            </label>

                                                            <input
                                                                type="date"
                                                                name="payment_schedule_due_date[]"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($payment['due_date'] ?: '') ?>"
                                                                required
                                                            >

                                                        </div>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-light border"
                                data-bs-dismiss="modal"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Perubahan
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>