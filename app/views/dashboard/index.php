<div>
    <h2 class="mb-1">
        Papan Pemuka Kewangan
    </h2>

    <p class="text-muted mb-0">
        Ringkasan Akaun Caj Pemajuan
    </p>
</div>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/summary-cards.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/development-charge-table.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/project-table.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/edit-project-modal.php';
?>