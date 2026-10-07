<div class="modal fade" id="addProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <div class="modal-content">

            <form method="POST" action="/cajpemajuan/project/create" id="addProjectForm">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Projek Baru
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>


                <div class="modal-body">

                    <h6 class="fw-bold">
                        Maklumat Asas
                    </h6>

                    <hr>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                ID Projek
                            </label>

                            <input type="text" name="project_id" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Jabatan
                            </label>

                            <select name="department" class="form-select" required>

                                <option value="">
                                    Pilih Jabatan
                                </option>

                                <option value="Jabatan Kejuruteraan">
                                    Jabatan Kejuruteraan
                                </option>

                                <option value="Jabatan Khidmat Pengurusan">
                                    Jabatan Khidmat Pengurusan
                                </option>

                                <option value="Jabatan Landskap">
                                    Jabatan Landskap
                                </option>

                                <option value="Jabatan Perancang Bandar">
                                    Jabatan Perancang Bandar
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Nama Projek
                            </label>

                            <input type="text" name="project_name" class="form-control" required>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Penerangan Projek
                            </label>

                            <textarea name="description" class="form-control" rows="3"></textarea>

                        </div>

                    </div>


                    <div class="border rounded p-3 mt-4 bg-light">

                        <h6 class="fw-bold">
                            Kewangan & Kelulusan
                        </h6>

                        <hr>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Status Kelulusan
                                </label>

                                <select name="approval_status" class="form-select">

                                    <option value="Tangguh">
                                        Tangguh
                                    </option>

                                    <option value="Diluluskan">
                                        Diluluskan
                                    </option>

                                    <option value="Ditolak">
                                        Ditolak
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Bilangan Mesyuarat
                                </label>

                                <input type="number" name="meeting_count" class="form-control" min="0" value="0">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Jenis Perolehan
                                </label>

                                <select name="procurement_type" class="form-select">

                                    <option value="Tender">
                                        Tender
                                    </option>

                                    <option value="Sebut Harga">
                                        Sebut Harga
                                    </option>

                                    <option value="Pembelian Terus">
                                        Pembelian Terus
                                    </option>

                                    <option value="Rundingan Terus">
                                        Rundingan Terus
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Nilai SST (RM)
                                </label>

                                <input type="number" name="sst_value" class="form-control" step="0.01" min="0">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Nilai Kelulusan Mesyuarat (RM)
                                </label>

                                <input type="number" name="meeting_approval_value" class="form-control" step="0.01"
                                    min="0">

                            </div>

                        </div>

                    </div>


                    <div class="border rounded p-3 mt-4" style="background:#eff6ff;">

                        <h6 class="fw-bold text-primary">
                            Perancangan Jadual Pembayaran
                        </h6>

                        <hr>


                        <label class="form-label">
                            Kaedah Pembayaran
                        </label>

                        <select name="payment_method" id="paymentMethod" class="form-select mb-3">

                            <option value="One-off">
                                One-off
                            </option>

                            <option value="Ansuran">
                                Ansuran
                            </option>

                            <option value="Pajakan/Sewa">
                                Pajakan/Sewa
                            </option>

                        </select>


                        <div class="d-flex justify-content-between mb-2">

                            <strong>
                                Jadual Tuntutan / Pajakan
                            </strong>

                            <button type="button" class="btn btn-link btn-sm" onclick="addScheduleRow()"
                                id="addScheduleRowButton">
                                + Tambah Baris
                            </button>

                        </div>


                        <div id="scheduleContainer">

                            <div class="schedule-row border rounded bg-white p-3 mb-2">

                                <div class="row g-2">

                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Tarikh Tuntutan
                                        </label>

                                        <input type="date" name="schedule_date[]" class="form-control">

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jumlah (RM)
                                        </label>

                                        <input type="number" name="schedule_amount[]" class="form-control" step="0.01"
                                            min="0">

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Jenis
                                        </label>

                                        <select name="schedule_type[]" class="form-select schedule-type">

                                            <option value="One-off">
                                                One-off
                                            </option>

                                            <option value="Ansuran">
                                                Ansuran
                                            </option>

                                            <option value="Pajakan/Sewa">
                                                Pajakan/Sewa
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <h6 class="fw-bold mt-4">
                        Garis Masa & Status Projek
                    </h6>

                    <hr>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Tarikh Mula
                            </label>

                            <input type="date" name="start_date" class="form-control">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Tarikh Tamat
                            </label>

                            <input type="date" name="end_date" class="form-control">

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Status Semasa Projek
                            </label>

                            <select name="project_status" class="form-select">

                                <option value="Belum Mula">
                                    Belum Mula
                                </option>

                                <option value="Dalam Proses">
                                    Dalam Proses
                                </option>

                                <option value="Selesai">
                                    Selesai
                                </option>

                                <option value="Dibatalkan">
                                    Dibatalkan
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-success">
                        Tambah Projek
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>