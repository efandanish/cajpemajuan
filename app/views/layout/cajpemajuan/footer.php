</main>

</div>

</div>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/edit-development-charge-modal.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/add-development-charge-modal.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/charge-payment-modal.php';
?>

<?php
require Config::get('VIEWS_PATH') .
    'dashboard/partials/add-project-modal.php';
?>

<?php
if (
    isset($_SESSION['user_role']) &&
    $_SESSION['user_role'] === 'admin'
) {

    // The shared sidebar exposes user management outside the dashboard too.
    if (!isset($users)) {
        $users = (new User())->getAll();
    }

    require Config::get('VIEWS_PATH') .
        'dashboard/partials/manage-user-modal.php';

    require Config::get('VIEWS_PATH') .
        'dashboard/partials/add-user-modal.php';

    require Config::get('VIEWS_PATH') .
        'dashboard/partials/edit-user-modal.php';
}
?>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>window.APP_ROOT = <?= json_encode(PUBLIC_ROOT, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>

<script src="<?= PUBLIC_ROOT ?>js/dashboard.js"></script>

<script src="<?= PUBLIC_ROOT ?>js/project-finance.js"></script>

<script src="<?= PUBLIC_ROOT ?>js/table-filters.js?v=1"></script>

</body>

</html>
