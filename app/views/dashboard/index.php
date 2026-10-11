<div class="dashboard-overview">
<div class="dashboard-page-header">
    <h2 class="mb-1">
        Papan Pemuka Kewangan
    </h2>

    <p class="text-muted mb-0">
        Ringkasan Akaun Caj Pemajuan bagi tahun <?= (int) $selectedYear ?>
    </p>
</div>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/summary-cards.php';
?>
</div>
