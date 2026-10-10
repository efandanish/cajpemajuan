<?php if (!empty($developmentCharges)): ?>

    <?php foreach ($developmentCharges as $charge): ?>

        <?php

        $payments =
            $this->controller
                ->developmentCharge
                ->getPayments(
                    $charge['id']
                );

        ?>

        <div class="modal fade charge-payment-modal" id="chargePaymentModal<?= $charge['id'] ?>" tabindex="-1"
            data-bs-focus="false">

            <div class="modal-dialog modal-lg modal-dialog-scrollable">

                <div class="modal-content">

                    <div class="modal-header">

                        <div>

                            <h5 class="modal-title fw-bold">
                                Rekod Bayaran
                            </h5>

                            <div class="text-muted small">
                                <?= htmlspecialchars($charge['developer_name']) ?>
                            </div>

                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>


                    <div class="modal-body">

                        <h6 class="fw-bold mb-3">
                            Senarai Ansuran
                        </h6>


                        <?php if (!empty($payments)): ?>

                            <?php foreach ($payments as $payment): ?>

                                <div class="card mb-3">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between">

                                            <div>

                                                <div class="fw-bold">
                                                    Ansuran #<?= $payment['installment_no'] ?>
                                                </div>

                                                <div class="small text-muted">

                                                    Tarikh Akhir:

                                                    <?= $payment['due_date']
                                                        ? date(
                                                            'd/m/Y',
                                                            strtotime(
                                                                $payment['due_date']
                                                            )
                                                        )
                                                        : '-'
                                                        ?>

                                                </div>


                                                <div class="fw-semibold mt-2">

                                                    RM <?= number_format(
                                                        $payment['amount'],
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                            <div>

                                                <?php if (
                                                    $payment['status'] === 'Dibayar'
                                                ): ?>

                                                    <span class="status-badge status-approved">
                                                        Telah Dibayar
                                                    </span>

                                                <?php else: ?>

                                                    <span class="status-badge status-pending">
                                                        Menunggu
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <?php if (
                                            $payment['status'] !== 'Dibayar'
                                        ): ?>

                                            <hr>


                                            <form method="POST" action="<?= PUBLIC_ROOT ?>charge/payment/<?= $payment['id'] ?>"
                                                enctype="multipart/form-data">

                                                <div class="row g-3">

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Tarikh Bayaran
                                                        </label>

                                                        <input type="date" name="payment_date" class="form-control" required>

                                                    </div>


                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Resit Bayaran
                                                        </label>

                                                        <input type="file" name="receipt" class="form-control"
                                                            accept=".jpg,.jpeg,.png,.pdf,image/*" required>

                                                        <div class="form-text">
                                                            JPG, PNG atau PDF. Maksimum 5 MB.
                                                        </div>

                                                    </div>


                                                    <div class="col-12">

                                                        <button type="submit" class="btn btn-primary">
                                                            Rekod Bayaran
                                                        </button>

                                                    </div>

                                                </div>

                                            </form>


                                        <?php else: ?>


                                            <div class="mt-3">

                                                <div class="small text-muted mb-3">

                                                    Dibayar pada:

                                                    <strong>

                                                        <?= $payment['payment_date']
                                                            ? date(
                                                                'd/m/Y',
                                                                strtotime(
                                                                    $payment['payment_date']
                                                                )
                                                            )
                                                            : '-'
                                                            ?>

                                                    </strong>

                                                </div>


                                                <div class="d-flex flex-wrap gap-2">

                                                    <?php if (
                                                        !empty(
                                                        $payment['receipt_file']
                                                    )
                                                    ): ?>

                                                        <a href="<?=
                                                            PUBLIC_ROOT .
                                                            htmlspecialchars(
                                                                $payment['receipt_file']
                                                            )
                                                            ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            Lihat Resit
                                                        </a>

                                                    <?php endif; ?>


                                                    <form method="POST" action="<?= PUBLIC_ROOT ?>charge/cancelPayment/<?= $payment['id'] ?>"
                                                        class="cancel-charge-payment-form">

                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Batalkan Bayaran
                                                        </button>

                                                    </form>

                                                </div>

                                            </div>


                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="text-muted mb-3">
                                Tiada jadual bayaran direkodkan.
                            </div>


                            <form method="POST" action="<?= PUBLIC_ROOT ?>charge/generatePaymentSchedule/<?= $charge['id'] ?>">

                                <button type="submit" class="btn btn-outline-primary">
                                    Jana Jadual Bayaran
                                </button>

                            </form>


                        <?php endif; ?>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Tutup
                        </button>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>