<div class="dashboard-summary-grid" role="group" aria-label="Ringkasan kewangan">
    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Bawa Hadapan</div>
        </div>

        <div class="stat-value">RM <?= number_format($carriedForward, 2) ?></div>
        <div class="stat-footer">Baki daripada tempoh sebelumnya</div>
        <div class="stat-icon stat-icon-blue" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-down-left"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Kutipan</div>
        </div>

        <div class="stat-value">RM <?= number_format($totalCollection, 2) ?></div>
        <div class="stat-footer">Jumlah kutipan caj pemajuan</div>
        <div class="stat-icon stat-icon-green" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-up-right"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Perbelanjaan</div>
        </div>

        <div class="stat-value">RM <?= number_format($totalExpenditure, 2) ?></div>
        <div class="stat-footer">Jumlah perbelanjaan projek</div>
        <div class="stat-icon stat-icon-red" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-down"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Akhir Semasa</div>
        </div>

        <div class="stat-value">RM <?= number_format($currentBalance, 2) ?></div>
        <div class="stat-footer">Kedudukan kewangan semasa</div>
        <div class="stat-icon stat-icon-purple" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#wallet"></use></svg></div>
    </div>
</div>
