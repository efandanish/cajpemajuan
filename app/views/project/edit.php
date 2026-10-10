<div class="card shadow-sm">

    <div class="card-header bg-white">
        <h5 class="mb-0">
            Edit Projek
        </h5>
    </div>

    <div class="card-body">

        <form
            method="POST"
            action="<?= PUBLIC_ROOT ?>project/update/<?= $project['id'] ?>"
        >

            <h6 class="fw-bold">
                Maklumat Asas
            </h6>

            <hr>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        ID Projek
                    </label>

                    <input
                        type="text"
                        name="project_id"
                        class="form-control"
                        value="<?= htmlspecialchars($project['project_id']) ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Jabatan
                    </label>

                    <select
                        name="department"
                        class="form-select"
                        required
                    >
                        <?php
                        $departments = [
                            'Jabatan Kejuruteraan',
                            'Jabatan Khidmat Pengurusan',
                            'Jabatan Landskap',
                            'Jabatan Perancang Bandar'
                        ];
                        ?>

                        <?php foreach ($departments as $department): ?>
                            <option
                                value="<?= htmlspecialchars($department) ?>"
                                <?= $project['department'] === $department ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($department) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Nama Projek
                    </label>

                    <input
                        type="text"
                        name="project_name"
                        class="form-control"
                        value="<?= htmlspecialchars($project['project_name']) ?>"
                        required
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Penerangan Projek
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($project['description']) ?></textarea>
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

                        <select
                            name="approval_status"
                            class="form-select"
                        >
                            <?php
                            $approvalStatuses = [
                                'Tangguh',
                                'Diluluskan',
                                'Ditolak'
                            ];
                            ?>

                            <?php foreach ($approvalStatuses as $status): ?>
                                <option
                                    value="<?= $status ?>"
                                    <?= $project['approval_status'] === $status ? 'selected' : '' ?>
                                >
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Bilangan Mesyuarat
                        </label>

                        <input
                            type="number"
                            name="meeting_count"
                            class="form-control"
                            min="0"
                            value="<?= (int) $project['meeting_count'] ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Jenis Perolehan
                        </label>

                        <select
                            name="procurement_type"
                            class="form-select"
                        >
                            <?php
                            $procurementTypes = [
                                'Tender',
                                'Sebut Harga',
                                'Pembelian Terus',
                                'Rundingan Terus'
                            ];
                            ?>

                            <?php foreach ($procurementTypes as $type): ?>
                                <option
                                    value="<?= $type ?>"
                                    <?= $project['procurement_type'] === $type ? 'selected' : '' ?>
                                >
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Nilai SST (RM)
                        </label>

                        <input
                            type="number"
                            name="sst_value"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= $project['sst_value'] ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Nilai Kelulusan Mesyuarat (RM)
                        </label>

                        <input
                            type="number"
                            name="meeting_approval_value"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= $project['meeting_approval_value'] ?>"
                        >
                    </div>

                </div>

            </div>

            <div
                class="border rounded p-3 mt-4"
                style="background:#eff6ff;"
            >

                <h6 class="fw-bold text-primary">
                    Perancangan Jadual Pembayaran
                </h6>

                <hr>

                <label class="form-label">
                    Kaedah Pembayaran
                </label>

                <select
                    name="payment_method"
                    id="editPaymentMethod"
                    class="form-select mb-3"
                >
                    <?php
                    $paymentMethods = [
                        'One-off',
                        'Ansuran',
                        'Pajakan/Sewa'
                    ];
                    ?>

                    <?php foreach ($paymentMethods as $method): ?>
                        <option
                            value="<?= $method ?>"
                            <?= $project['payment_method'] === $method ? 'selected' : '' ?>
                        >
                            <?= $method ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="d-flex justify-content-between mb-2">

                    <strong>
                        Jadual Tuntutan / Pajakan
                    </strong>

                    <button
                        type="button"
                        class="btn btn-link btn-sm"
                        onclick="addEditScheduleRow()"
                    >
                        + Tambah Baris
                    </button>

                </div>

                <div id="editScheduleContainer">

                    <?php if (!empty($schedules)): ?>

                        <?php foreach ($schedules as $schedule): ?>

                            <div class="schedule-row border rounded bg-white p-3 mb-2">

                                <div class="row g-2">

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Tarikh Tuntutan
                                        </label>

                                        <input
                                            type="date"
                                            name="schedule_date[]"
                                            class="form-control"
                                            value="<?= $schedule['claim_date'] ?>"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Jumlah (RM)
                                        </label>

                                        <input
                                            type="number"
                                            name="schedule_amount[]"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                            value="<?= $schedule['amount'] ?>"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Jenis
                                        </label>

                                        <select
                                            name="schedule_type[]"
                                            class="form-select edit-schedule-type"
                                        >
                                            <?php foreach ($paymentMethods as $method): ?>
                                                <option
                                                    value="<?= $method ?>"
                                                    <?= $schedule['type'] === $method ? 'selected' : '' ?>
                                                >
                                                    <?= $method ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-1 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-danger"
                                            onclick="this.closest('.schedule-row').remove()"
                                        >
                                            ×
                                        </button>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="schedule-row border rounded bg-white p-3 mb-2">

                            <div class="row g-2">

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Tarikh Tuntutan
                                    </label>

                                    <input
                                        type="date"
                                        name="schedule_date[]"
                                        class="form-control"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Jumlah (RM)
                                    </label>

                                    <input
                                        type="number"
                                        name="schedule_amount[]"
                                        class="form-control"
                                        step="0.01"
                                        min="0"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Jenis
                                    </label>

                                    <select
                                        name="schedule_type[]"
                                        class="form-select edit-schedule-type"
                                    >
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <option
                                                value="<?= $method ?>"
                                                <?= $project['payment_method'] === $method ? 'selected' : '' ?>
                                            >
                                                <?= $method ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

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

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="<?= $project['start_date'] ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Tarikh Tamat
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="<?= $project['end_date'] ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Status Semasa Projek
                    </label>

                    <select
                        name="project_status"
                        class="form-select"
                    >
                        <?php
                        $projectStatuses = [
                            'Belum Mula',
                            'Dalam Proses',
                            'Selesai',
                            'Dibatalkan'
                        ];
                        ?>

                        <?php foreach ($projectStatuses as $status): ?>
                            <option
                                value="<?= $status ?>"
                                <?= $project['project_status'] === $status ? 'selected' : '' ?>
                            >
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">

                <a
                    href="<?= PUBLIC_ROOT ?>dashboard"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

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

<script>
function addEditScheduleRow() {

    const container =
        document.getElementById('editScheduleContainer');

    const paymentMethod =
        document.getElementById('editPaymentMethod').value;

    const row =
        document.createElement('div');

    row.className =
        'schedule-row border rounded bg-white p-3 mb-2';

    row.innerHTML = `
        <div class="row g-2">

            <div class="col-md-4">
                <input
                    type="date"
                    name="schedule_date[]"
                    class="form-control"
                >
            </div>

            <div class="col-md-4">
                <input
                    type="number"
                    name="schedule_amount[]"
                    class="form-control"
                    step="0.01"
                    min="0"
                >
            </div>

            <div class="col-md-3">
                <select
                    name="schedule_type[]"
                    class="form-select edit-schedule-type"
                >
                    <option value="${paymentMethod}">
                        ${paymentMethod}
                    </option>
                </select>
            </div>

            <div class="col-md-1">
                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="this.closest('.schedule-row').remove()"
                >
                    ×
                </button>
            </div>

        </div>
    `;

    container.appendChild(row);
}

document
    .getElementById('editPaymentMethod')
    .addEventListener('change', function () {

        document
            .querySelectorAll('.edit-schedule-type')
            .forEach(function(select) {

                select.innerHTML =
                    '<option value="' +
                    this.value +
                    '">' +
                    this.value +
                    '</option>';

            }, this);

    });
</script>