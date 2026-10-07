<div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-stat-card h-100">

            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">

                    <div>

                        <div class="stat-label">
                            Baki Bawa Hadapan
                        </div>

                        <div class="stat-value">
                            RM <?= number_format($carriedForward, 2) ?>
                        </div>

                    </div>

                    <div class="stat-icon stat-icon-blue">
                        ↙
                    </div>

                </div>

                <div class="stat-footer">
                    Baki daripada tempoh sebelumnya
                </div>

            </div>

        </div>

    </div>


    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-stat-card h-100">

            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">

                    <div>

                        <div class="stat-label">
                            Jumlah Kutipan
                        </div>

                        <div class="stat-value">
                            RM <?= number_format($totalCollection, 2) ?>
                        </div>

                    </div>

                    <div class="stat-icon stat-icon-green">
                        ↗
                    </div>

                </div>

                <div class="stat-footer">
                    Jumlah kutipan caj pemajuan
                </div>

            </div>

        </div>

    </div>


    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-stat-card h-100">

            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">

                    <div>

                        <div class="stat-label">
                            Jumlah Perbelanjaan
                        </div>

                        <div class="stat-value">
                            RM <?= number_format($totalExpenditure, 2) ?>
                        </div>

                    </div>

                    <div class="stat-icon stat-icon-red">
                        ↓
                    </div>

                </div>

                <div class="stat-footer">
                    Jumlah perbelanjaan projek
                </div>

            </div>

        </div>

    </div>


    <div class="col-sm-6 col-xl-3">

        <div class="card dashboard-stat-card h-100">

            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">

                    <div>

                        <div class="stat-label">
                            Baki Akhir Semasa
                        </div>

                        <div class="stat-value">
                            RM <?= number_format($currentBalance, 2) ?>
                        </div>

                    </div>

                    <div class="stat-icon stat-icon-purple">
                        $
                    </div>

                </div>

                <div class="stat-footer">
                    Kedudukan kewangan semasa
                </div>

            </div>

        </div>

    </div>

</div>