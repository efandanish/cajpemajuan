<?php

class ProjectController extends Controller
{

    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('Project');
        $this->loadModel('Notification');
        if (
            empty($_SESSION['user_role']) ||
            $_SESSION['user_role'] !== 'admin'
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=unauthorized'
            );

            exit;
        }
    }


    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $data = $this->getFormData();

        $projectId = $this->project->create($data);

        if (!$projectId) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $this->saveSchedules($projectId);

        $this->notification->create([
            'category' => 'project',

            'action' => 'created',

            'title' => 'Projek Baru',

            'message' =>
                $data['project_id'] .
                ' - ' .
                $data['project_name'] .
                ' telah didaftarkan.',

            'entity_type' => 'project',

            'entity_id' => $projectId
        ]);

        header('Location: ' . PUBLIC_ROOT . 'dashboard');
        exit;
    }


    public function edit($id)
    {
        $project = $this->project->getById($id);

        if (!$project) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $schedules = $this->project->getSchedules($id);

        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') . 'layout/cajpemajuan/',
            Config::get('VIEWS_PATH') . 'project/edit.php',
            [
                'project' => $project,
                'schedules' => $schedules
            ]
        );
    }


    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $oldProject =
            $this->project->getById($id);


        if (!$oldProject) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=project_not_found'
            );

            exit;
        }

        $data =
            $this->getFormData();

        $changes = [];


        if (
            $oldProject['project_id'] !==
            $data['project_id']
        ) {
            $changes[] = 'ID Projek';
        }


        if (
            $oldProject['department'] !==
            $data['department']
        ) {
            $changes[] = 'Jabatan';
        }


        if (
            $oldProject['project_name'] !==
            $data['project_name']
        ) {
            $changes[] = 'Nama Projek';
        }


        if (
            $oldProject['description'] !==
            $data['description']
        ) {
            $changes[] = 'Penerangan Projek';
        }


        if (
            $oldProject['approval_status'] !==
            $data['approval_status']
        ) {
            $changes[] = 'Status Kelulusan';
        }


        if (
            (int) $oldProject['meeting_count'] !==
            (int) $data['meeting_count']
        ) {
            $changes[] = 'Bilangan Mesyuarat';
        }


        if (
            $oldProject['procurement_type'] !==
            $data['procurement_type']
        ) {
            $changes[] = 'Jenis Perolehan';
        }


        if (
            (float) $oldProject['sst_value'] !==
            (float) $data['sst_value']
        ) {
            $changes[] = 'Nilai SST';
        }


        if (
            (float) $oldProject['meeting_approval_value'] !==
            (float) $data['meeting_approval_value']
        ) {
            $changes[] = 'Nilai Kelulusan Mesyuarat';
        }


        if (
            $oldProject['payment_method'] !==
            $data['payment_method']
        ) {
            $changes[] = 'Kaedah Pembayaran';
        }


        if (
            $oldProject['start_date'] !==
            $data['start_date']
        ) {
            $changes[] = 'Tarikh Mula';
        }


        if (
            $oldProject['end_date'] !==
            $data['end_date']
        ) {
            $changes[] = 'Tarikh Tamat';
        }


        if (
            $oldProject['project_status'] !==
            $data['project_status']
        ) {
            $changes[] = 'Status Projek';
        }


        $this->project->update(
            $id,
            $data
        );


        if (
            isset($_POST['schedule_date']) &&
            isset($_POST['schedule_amount'])
        ) {

            $this->project->deleteSchedules($id);

            $this->saveSchedules($id);
        }


        if (!empty($changes)) {

            $this->notification->create([
                'category' => 'project',

                'action' => 'updated',

                'title' =>
                    'Projek Dikemaskini',

                'message' =>
                    $data['project_id'] .
                    ' - ' .
                    $data['project_name'] .
                    ' dikemaskini: ' .
                    implode(', ', $changes) .
                    '.',

                'entity_type' =>
                    'project',

                'entity_id' =>
                    $id
            ]);
        }

        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=project_updated'
        );

        exit;
    }


    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $project = $this->project->getById($id);

        if (!$project) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $this->project->delete($id);

        $this->notification->create([
            'category' => 'project',

            'action' => 'deleted',

            'title' => 'Projek Dipadam',

            'message' =>
                $project['project_id'] .
                ' - ' .
                $project['project_name'] .
                ' telah dipadam.',

            'entity_type' => 'project',

            'entity_id' => $id
        ]);

        header('Location: ' . PUBLIC_ROOT . 'dashboard');
        exit;
    }


    private function getFormData()
    {
        return [
            'project_id' => isset($_POST['project_id'])
                ? trim($_POST['project_id'])
                : '',

            'department' => isset($_POST['department'])
                ? trim($_POST['department'])
                : '',

            'project_name' => isset($_POST['project_name'])
                ? trim($_POST['project_name'])
                : '',

            'description' => isset($_POST['description'])
                ? trim($_POST['description'])
                : '',

            'approval_status' => isset($_POST['approval_status'])
                ? $_POST['approval_status']
                : 'Tangguh',

            'meeting_count' => isset($_POST['meeting_count'])
                ? (int) $_POST['meeting_count']
                : 0,

            'procurement_type' => isset($_POST['procurement_type'])
                ? $_POST['procurement_type']
                : '',

            'sst_value' => isset($_POST['sst_value'])
                ? (float) $_POST['sst_value']
                : 0,

            'meeting_approval_value' =>
                isset($_POST['meeting_approval_value'])
                ? (float) $_POST['meeting_approval_value']
                : 0,

            'payment_method' => isset($_POST['payment_method'])
                ? $_POST['payment_method']
                : 'One-off',

            'start_date' => isset($_POST['start_date'])
                ? $_POST['start_date']
                : '',

            'end_date' => isset($_POST['end_date'])
                ? $_POST['end_date']
                : '',

            'project_status' => isset($_POST['project_status'])
                ? $_POST['project_status']
                : 'Belum Mula'
        ];
    }


    private function saveSchedules($projectId)
    {
        if (
            empty($_POST['schedule_date']) ||
            empty($_POST['schedule_amount'])
        ) {
            return;
        }

        foreach ($_POST['schedule_date'] as $index => $date) {

            $amount = isset($_POST['schedule_amount'][$index])
                ? $_POST['schedule_amount'][$index]
                : 0;

            if (isset($_POST['schedule_type'][$index])) {
                $type = $_POST['schedule_type'][$index];
            } elseif (isset($_POST['payment_method'])) {
                $type = $_POST['payment_method'];
            } else {
                $type = 'One-off';
            }

            if (
                empty($date) ||
                (float) $amount <= 0
            ) {
                continue;
            }

            $this->project->addSchedule(
                $projectId,
                $date,
                $amount,
                $type
            );
        }
    }
    public function recordPayment($scheduleId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $schedule =
            $this->project->getScheduleById($scheduleId);

        if (!$schedule) {
            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=schedule_not_found'
            );
            exit;
        }

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $paymentDate = isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';

        $description = isset($_POST['description'])
            ? trim($_POST['description'])
            : '';

        if ($amount <= 0 || empty($paymentDate)) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_payment'
            );

            exit;
        }

        $this->project->addExpense(
            $schedule['project_id'],
            $scheduleId,
            $amount,
            $paymentDate,
            $description
        );

        $this->project->markSchedulePaid(
            $scheduleId
        );

        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=payment_recorded'
        );

        exit;
    }
    public function cancelPayment($expenseId)
    {
        $expense = $this->project->getExpenseById($expenseId);

        if (!$expense) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=expense_not_found');
            exit;
        }

        $scheduleId = $expense['schedule_id'];

        $this->project->deleteExpense($expenseId);

        if (!empty($scheduleId)) {
            $this->project->markSchedulePending($scheduleId);
        }

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=payment_cancelled');
        exit;
    }

    public function addScheduleRecord($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=project_not_found');
            exit;
        }

        $date = isset($_POST['claim_date'])
            ? $_POST['claim_date']
            : '';

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $type = isset($_POST['type'])
            ? $_POST['type']
            : 'Ansuran';

        if (empty($date) || $amount <= 0) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_schedule');
            exit;
        }

        $this->project->addSchedule(
            $projectId,
            $date,
            $amount,
            $type
        );

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=schedule_added');
        exit;
    }
    public function addManualExpense($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=project_not_found');
            exit;
        }

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $paymentDate = isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';

        $description = isset($_POST['description'])
            ? trim($_POST['description'])
            : '';

        if ($amount <= 0 || empty($paymentDate)) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_expense');
            exit;
        }

        $this->project->addExpense(
            $projectId,
            null,
            $amount,
            $paymentDate,
            $description
        );

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=manual_expense_added');
        exit;
    }

    public function addVariationRecord($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=project_not_found');
            exit;
        }

        $description = isset($_POST['description'])
            ? trim($_POST['description'])
            : '';

        $type = isset($_POST['variation_type'])
            ? $_POST['variation_type']
            : 'Tambah';

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $date = isset($_POST['variation_date'])
            ? $_POST['variation_date']
            : '';

        if (
            empty($description) ||
            $amount <= 0 ||
            empty($date)
        ) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_variation');
            exit;
        }

        $this->project->addVariation(
            $projectId,
            $description,
            $type,
            $amount,
            $date
        );

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=variation_added');
        exit;
    }

    public function deleteScheduleRecord($scheduleId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $schedule =
            $this->project->getScheduleById($scheduleId);

        if (!$schedule) {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=schedule_not_found');
            exit;
        }

        if ($schedule['status'] === 'Dibayar') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=schedule_paid');
            exit;
        }

        $this->project->deleteSchedule($scheduleId);

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=schedule_deleted');
        exit;
    }
}