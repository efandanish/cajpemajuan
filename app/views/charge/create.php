<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>charge">Kembali ke Senarai</a></div>
<div class="module-page">

    <div class="module-container">

        <div class="card">

            <form method="POST" action="<?= PUBLIC_ROOT ?>charge/create" id="addChargeForm">

                <div class="card-header">

                    <div>

                        <h5 class="card-title fw-bold">
                            Daftar Caj Pemajuan
                        </h5>

                        <div class="small text-muted">
                            Masukkan maklumat kelulusan dan caj pemajuan
                        </div>

                    </div>



                </div>


                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Pemaju
                            </label>

                            <input type="text" name="developer_name" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Projek
                            </label>

                            <input type="text" name="project_name" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                ID OSC
                            </label>

                            <input type="text" name="osc_id" class="form-control" placeholder="Contoh: OSC-2026-0148">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                No. Lot / PT
                            </label>

                            <input type="text" name="lot_number" class="form-control" placeholder="Contoh: PT 4587">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Mukim
                            </label>

                            <input type="text" name="mukim" class="form-control">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                No. Fail
                            </label>

                            <input type="text" name="file_number" class="form-control">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Jumlah Caj (RM)
                            </label>

                            <input type="number" name="charge_amount" class="form-control" min="0" step="0.01" required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Keputusan OSC
                            </label>

                            <select name="osc_decision" class="form-select">

                                <option value="Lulus">
                                    Lulus
                                </option>

                                <option value="Tangguh">
                                    Tangguh
                                </option>

                                <option value="Ditolak">
                                    Ditolak
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Pelan Bayaran
                            </label>

                            <select name="payment_plan" id="chargePaymentPlan" class="form-select" required>
                                <option value="Bayaran Penuh">
                                    Bayaran Penuh (1x)
                                </option>

                                <option value="3 Ansuran">
                                    3 Ansuran
                                </option>

                                <option value="4 Ansuran">
                                    4 Ansuran
                                </option>
                            </select>

                        </div>


                        <div class="col-12" id="chargeInstallmentSection" style="display:none;">

                            <div class="border rounded p-3 bg-light">

                                <div class="fw-semibold mb-3">
                                    Butiran Ansuran
                                </div>

                                <div id="chargeInstallmentRows"></div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-footer">



                    <button type="submit" class="btn btn-primary">
                        Daftar Caj
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
