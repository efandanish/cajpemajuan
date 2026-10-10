<?php

class ChargeController extends Controller
{
    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('DevelopmentCharge');
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

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }


        $charge =
            $this->developmentCharge->getById($id);


        if (!$charge) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=charge_not_found'
            );

            exit;
        }

        // Notification

        $this->notification->create([
            'category' => 'charge',

            'action' => 'deleted',

            'title' => 'Caj Pemajuan Dipadam',

            'message' =>
                $charge['osc_id'] .
                ' - ' .
                $charge['developer_name'] .
                ' telah dipadam.',

            'entity_type' =>
                'development_charge',

            'entity_id' =>
                $id
        ]);

        $this->developmentCharge->delete($id);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_deleted'
        );

        exit;
    }
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $oldCharge =
            $this->developmentCharge
                ->getById($id);


        if (!$oldCharge) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=charge_not_found'
            );

            exit;
        }

        $data =
            $this->getFormData();


        $existingPayments =
            $this->developmentCharge
                ->getPayments($id);


        $hasPaidPayments =
            $this->developmentCharge
                ->hasPaidPayments($id);


        if ($hasPaidPayments) {

            $data['payment_plan'] =
                $oldCharge['payment_plan'];
        }

        $paymentPlanChanged =
            $oldCharge['payment_plan']
            !==
            $data['payment_plan'];

        /*
        |--------------------------------------------------------------------------
        | UPDATE MAIN CHARGE
        |--------------------------------------------------------------------------
        */

        $changes = [];


        if (
            $oldCharge['developer_name']
            !==
            $data['developer_name']
        ) {
            $changes[] = 'Nama Pemaju';
        }


        if (
            $oldCharge['project_name']
            !==
            $data['project_name']
        ) {
            $changes[] = 'Nama Projek';
        }


        if (
            $oldCharge['osc_id']
            !==
            $data['osc_id']
        ) {
            $changes[] = 'ID OSC';
        }


        if (
            (float) $oldCharge['charge_amount']
            !==
            (float) $data['charge_amount']
        ) {
            $changes[] = 'Jumlah Caj';
        }


        if (
            $oldCharge['osc_decision']
            !==
            $data['osc_decision']
        ) {
            $changes[] = 'Keputusan OSC';
        }


        if (
            $oldCharge['payment_plan']
            !==
            $data['payment_plan']
        ) {
            $changes[] = 'Pelan Bayaran';
        }


        $this->developmentCharge
            ->update(
                $id,
                $data
            );

        if (
            $paymentPlanChanged &&
            !$hasPaidPayments
        ) {

            /*
            |--------------------------------------------------------------------------
            | REMOVE OLD PENDING SCHEDULE
            |--------------------------------------------------------------------------
            */

            $this->developmentCharge
                ->deletePendingPayments($id);


            /*
            |--------------------------------------------------------------------------
            | CREATE NEW SCHEDULE FROM EDIT FORM
            |--------------------------------------------------------------------------
            */

            $numbers =
                isset($_POST['new_installment_no'])
                ? $_POST['new_installment_no']
                : [];


            $amounts =
                isset($_POST['new_installment_amount'])
                ? $_POST['new_installment_amount']
                : [];


            $dates =
                isset($_POST['new_installment_due_date'])
                ? $_POST['new_installment_due_date']
                : [];


            foreach (
                $numbers
                as $index => $number
            ) {

                $amount =
                    isset($amounts[$index])
                    ? (float) $amounts[$index]
                    : 0;


                $dueDate =
                    isset($dates[$index])
                    ? $dates[$index]
                    : null;


                if ($amount <= 0) {
                    continue;
                }


                $this->developmentCharge
                    ->addPaymentSchedule(
                        $id,
                        (int) $number,
                        $amount,
                        $dueDate
                    );
            }

        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE PENDING INSTALLMENTS
        |--------------------------------------------------------------------------
        */

        $scheduleIds =
            isset($_POST['payment_schedule_id'])
            ? $_POST['payment_schedule_id']
            : [];


        $scheduleAmounts =
            isset($_POST['payment_schedule_amount'])
            ? $_POST['payment_schedule_amount']
            : [];


        $scheduleDates =
            isset($_POST['payment_schedule_due_date'])
            ? $_POST['payment_schedule_due_date']
            : [];


        foreach (
            $scheduleIds
            as $index => $scheduleId
        ) {

            $amount =
                isset($scheduleAmounts[$index])
                ? (float) $scheduleAmounts[$index]
                : 0;


            $dueDate =
                isset($scheduleDates[$index])
                ? $scheduleDates[$index]
                : null;


            if (
                $amount <= 0 ||
                empty($dueDate)
            ) {
                continue;
            }


            $this->developmentCharge
                ->updatePendingPaymentSchedule(
                    $scheduleId,
                    $id,
                    $amount,
                    $dueDate
                );
        }


        /*
        |--------------------------------------------------------------------------
        | NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (!empty($changes)) {

            $this->notification->create([
                'category' =>
                    'charge',

                'action' =>
                    'updated',

                'title' =>
                    'Caj Pemajuan Dikemaskini',

                'message' =>
                    $data['osc_id'] .
                    ' dikemaskini: ' .
                    implode(
                        ', ',
                        $changes
                    ) .
                    '.',

                'entity_type' =>
                    'development_charge',

                'entity_id' =>
                    $id
            ]);
        }


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_updated'
        );

        exit;
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard');
            exit;
        }

        $data = $this->getFormData();

        $chargeId =
            $this->developmentCharge->create(
                $data
            );

        if (!$chargeId) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=charge_create_failed'
            );

            exit;
        }

        if (
            $data['payment_plan'] === 'Bayaran Penuh'
        ) {

            $this->developmentCharge->addPaymentSchedule(
                $chargeId,
                1,
                $data['charge_amount'],
                null
            );
        }

        if (
            $data['payment_plan'] === '3 Ansuran' ||
            $data['payment_plan'] === '4 Ansuran'
        ) {

            $numbers =
                isset($_POST['installment_no'])
                ? $_POST['installment_no']
                : [];

            $amounts =
                isset($_POST['installment_amount'])
                ? $_POST['installment_amount']
                : [];

            $dates =
                isset($_POST['installment_due_date'])
                ? $_POST['installment_due_date']
                : [];


            foreach ($numbers as $index => $number) {

                $amount =
                    isset($amounts[$index])
                    ? (float) $amounts[$index]
                    : 0;

                $dueDate =
                    isset($dates[$index])
                    ? $dates[$index]
                    : null;

                if ($amount <= 0) {
                    continue;
                }

                $this->developmentCharge
                    ->addPaymentSchedule(
                        $chargeId,
                        (int) $number,
                        $amount,
                        $dueDate
                    );
            }
        }

        $this->notification->create([
            'category' => 'charge',

            'action' => 'created',

            'title' => 'Caj Pemajuan Baru',

            'message' =>
                $data['osc_id'] .
                ' - ' .
                $data['developer_name'] .
                ' telah didaftarkan.',

            'entity_type' =>
                'development_charge',

            'entity_id' =>
                null
        ]);

        header('Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_added');
        exit;
    }

    private function getFormData()
    {
        return [
            'developer_name' =>
                isset($_POST['developer_name'])
                ? trim($_POST['developer_name'])
                : '',

            'project_name' =>
                isset($_POST['project_name'])
                ? trim($_POST['project_name'])
                : '',

            'osc_id' =>
                isset($_POST['osc_id'])
                ? trim($_POST['osc_id'])
                : '',

            'lot_number' =>
                isset($_POST['lot_number'])
                ? trim($_POST['lot_number'])
                : '',

            'mukim' =>
                isset($_POST['mukim'])
                ? trim($_POST['mukim'])
                : '',

            'file_number' =>
                isset($_POST['file_number'])
                ? trim($_POST['file_number'])
                : '',

            'charge_amount' =>
                isset($_POST['charge_amount'])
                ? (float) $_POST['charge_amount']
                : 0,

            'osc_decision' =>
                isset($_POST['osc_decision'])
                ? $_POST['osc_decision']
                : 'Tangguh',

            'payment_plan' =>
                isset($_POST['payment_plan'])
                ? $_POST['payment_plan']
                : 'Bayaran Penuh'
        ];
    }

    public function payment($paymentId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $payment =
            $this->developmentCharge
                ->getPaymentById(
                    $paymentId
                );


        if (!$payment) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=payment_not_found'
            );

            exit;
        }


        $paymentDate =
            isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';


        if (empty($paymentDate)) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_payment'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | RECEIPT
        |--------------------------------------------------------------------------
        */

        $receiptPath = null;


        if (
            isset($_FILES['receipt']) &&
            $_FILES['receipt']['error'] === UPLOAD_ERR_OK
        ) {

            $file =
                $_FILES['receipt'];


            /*
            |--------------------------------------------------------------------------
            | MAX 5 MB
            |--------------------------------------------------------------------------
            */

            if ($file['size'] > 5 * 1024 * 1024) {

                header(
                    'Location: ' . PUBLIC_ROOT . 'dashboard?error=receipt_too_large'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | ALLOWED EXTENSIONS
            |--------------------------------------------------------------------------
            */

            $extension =
                strtolower(
                    pathinfo(
                        $file['name'],
                        PATHINFO_EXTENSION
                    )
                );


            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'pdf'
            ];


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                header(
                    'Location: ' . PUBLIC_ROOT . 'dashboard?error=invalid_receipt'
                );

                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD DIRECTORY
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                dirname(__DIR__, 2) .
                '/public/uploads/receipts/';


            if (!is_dir($uploadDirectory)) {

                mkdir(
                    $uploadDirectory,
                    0755,
                    true
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SAFE FILE NAME
            |--------------------------------------------------------------------------
            */

            $fileName =
                'receipt_' .
                $paymentId .
                '_' .
                time() .
                '.' .
                $extension;


            $destination =
                $uploadDirectory .
                $fileName;


            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )
            ) {

                header(
                    'Location: ' . PUBLIC_ROOT . 'dashboard?error=receipt_upload_failed'
                );

                exit;
            }


            $receiptPath =
                'uploads/receipts/' .
                $fileName;
        }

        $this->developmentCharge
            ->markPaymentPaid(
                $paymentId,
                $paymentDate,
                $receiptPath
            );


        /*
        |--------------------------------------------------------------------------
        | NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $this->notification->create([
            'category' => 'payment',
            'action' => 'created',
            'title' => 'Bayaran Caj Direkodkan',

            'message' =>
                'Bayaran ansuran bernilai RM ' .
                number_format(
                    $payment['amount'],
                    2
                ) .
                ' telah direkodkan.',

            'entity_type' =>
                'development_charge_payment',

            'entity_id' =>
                $paymentId
        ]);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_payment_recorded'
        );

        exit;
    }

    public function cancelPayment($paymentId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $payment =
            $this->developmentCharge
                ->getPaymentById(
                    $paymentId
                );


        if (!$payment) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=payment_not_found'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE RECEIPT FILE
        |--------------------------------------------------------------------------
        */

        if (!empty($payment['receipt_file'])) {

            $filePath =
                dirname(__DIR__, 2) .
                '/public/' .
                $payment['receipt_file'];


            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | RESET PAYMENT
        |--------------------------------------------------------------------------
        */

        $this->developmentCharge
            ->cancelPayment(
                $paymentId
            );


        /*
        |--------------------------------------------------------------------------
        | NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $this->notification->create([
            'category' => 'payment',
            'action' => 'cancelled',
            'title' => 'Bayaran Caj Dibatalkan',

            'message' =>
                'Bayaran ansuran bernilai RM ' .
                number_format(
                    $payment['amount'],
                    2
                ) .
                ' telah dibatalkan.',

            'entity_type' =>
                'development_charge_payment',

            'entity_id' =>
                $paymentId
        ]);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_payment_cancelled'
        );

        exit;
    }
    public function generatePaymentSchedule($chargeId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $charge =
            $this->developmentCharge
                ->getById(
                    $chargeId
                );


        if (!$charge) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=charge_not_found'
            );

            exit;
        }


        $existingPayments =
            $this->developmentCharge
                ->getPayments(
                    $chargeId
                );


        if (!empty($existingPayments)) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | BAYARAN PENUH
        |--------------------------------------------------------------------------
        */

        if (
            $charge['payment_plan'] === 'Bayaran Penuh'
        ) {

            $this->developmentCharge
                ->addPaymentSchedule(
                    $chargeId,
                    1,
                    $charge['charge_amount'],
                    null
                );
        }


        /*
        |--------------------------------------------------------------------------
        | 3 ANSURAN
        |--------------------------------------------------------------------------
        */

        if (
            $charge['payment_plan'] === '3 Ansuran'
        ) {

            $amount =
                $charge['charge_amount'] / 3;

            for ($i = 1; $i <= 3; $i++) {

                $this->developmentCharge
                    ->addPaymentSchedule(
                        $chargeId,
                        $i,
                        $amount,
                        null
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4 ANSURAN
        |--------------------------------------------------------------------------
        */

        if (
            $charge['payment_plan'] === '4 Ansuran'
        ) {

            $amount =
                $charge['charge_amount'] / 4;

            for ($i = 1; $i <= 4; $i++) {

                $this->developmentCharge
                    ->addPaymentSchedule(
                        $chargeId,
                        $i,
                        $amount,
                        null
                    );
            }
        }


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=charge_schedule_generated'
        );

        exit;
    }
}