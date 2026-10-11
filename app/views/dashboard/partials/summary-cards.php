<div class="dashboard-summary-grid" role="group" aria-label="Ringkasan kewangan">
    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Bawa Hadapan</div>
            <div class="stat-icon stat-icon-blue" aria-hidden="true">↙</div>
        </div>

        <div class="stat-value">RM <?= number_format($carriedForward, 2) ?></div>
        <div class="stat-footer">Baki daripada tempoh sebelumnya</div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Kutipan</div>
            <div class="stat-icon stat-icon-green" aria-hidden="true">↗</div>
        </div>

        <div class="stat-value">RM <?= number_format($totalCollection, 2) ?></div>
        <div class="stat-footer">Jumlah kutipan caj pemajuan</div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Perbelanjaan</div>
            <div class="stat-icon stat-icon-red" aria-hidden="true">↓</div>
        </div>

        <div class="stat-value">RM <?= number_format($totalExpenditure, 2) ?></div>
        <div class="stat-footer">Jumlah perbelanjaan projek</div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Akhir Semasa</div>
            <div class="stat-icon stat-icon-purple" aria-hidden="true">$</div>
        </div>

        <div class="stat-value">RM <?= number_format($currentBalance, 2) ?></div>
        <div class="stat-footer">Kedudukan kewangan semasa</div>
    </div>
</div>
