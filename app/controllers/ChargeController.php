<?php

class ChargeController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('DevelopmentCharge');
        $this->loadModel('Notification');
        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();
        $action = strtolower($this->request->param('action'));
        // Authenticated users may create; every other mutation remains admin-only.
        $isCreate = $action === 'create' && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true);
        if (!$isCreate && ($_SERVER['REQUEST_METHOD'] !== 'GET' || !in_array($action, ['index', 'edit', 'payment'], true))) {
            $this->requireAdmin();
        }

    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . ReportingYear::url('charge'));
            exit;
        }


        $charge =
            $this->developmentCharge->getById($id);


        if (!$charge) {

            header(
                'Location: ' . ReportingYear::url('charge?error=charge_not_found')
            );

            exit;
        }

        $deleted = $this->atomic(function () use ($id) {
            // Fresh locked snapshots: payments are removed by ON DELETE CASCADE, so capture them first.
            $charge = $this->developmentCharge->lockById($id);
            if (!$charge) { return false; }
            $payments = $this->developmentCharge->getPayments($id, true);

            if ($this->developmentCharge->delete($id) !== 1) { throw new RuntimeException('Charge delete failed.'); }

            foreach ($payments as $payment) {
                $this->audit('delete', 'development_charge_payment', $payment['id'], ['before' => $payment, 'reason' => 'charge_deleted']);
            }
            $this->audit('delete', 'development_charge', $id, ['before' => $charge]);
            return true;
        });

        if (!$deleted) {
            header('Location: ' . ReportingYear::url('charge?error=charge_not_found'));
            exit;
        }

        header(
            'Location: ' . ReportingYear::url('charge?success=charge_deleted')
        );

        exit;
    }
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('charge'));
            exit;
        }
        $result = null;
        try {
            $this->atomic(function () use ($id, &$result) {
                $old = $this->developmentCharge->lockById($id);
                if (!$old) { return null; }
                $payments = $this->developmentCharge->getPayments($id, true);
                $result = ChargeValidation::validate($_POST, $old, $payments);
                if ($result['errors']) { throw new DomainException('Charge edit validation failed.'); }
                if (!$this->developmentCharge->update($id, $result['data'])) { throw new RuntimeException('Charge update failed.'); }
                $this->audit('update', 'development_charge', $id, ['before' => $old, 'after' => $result['data']]);
                if ($old['payment_plan'] !== $result['data']['payment_plan']) {
                    // Same filter as deletePendingPayments(); the charge lock keeps this set stable.
                    $pending = array_values(array_filter($this->developmentCharge->getPayments($id, true), function ($payment) {
                        return $payment['status'] === 'Menunggu';
                    }));
                    if ($this->developmentCharge->deletePendingPayments($id) !== count($pending)) {
                        throw new RuntimeException('Pending payment delete count mismatch.');
                    }
                    foreach ($pending as $oldPayment) {
                        $this->audit('delete', 'development_charge_payment', $oldPayment['id'], ['before' => $oldPayment, 'reason' => 'schedule_replaced']);
                    }
                    foreach ($result['new'] as $row) {
                        $paymentId = $this->developmentCharge->addPaymentSchedule($id, $row[0], $row[1], $row[2]);
                        $this->audit('create', 'development_charge_payment', $paymentId, ['after' => ['development_charge_id' => $id, 'installment_no' => $row[0], 'amount' => $row[1], 'due_date' => $row[2], 'status' => 'Menunggu']]);
                    }
                }
                foreach ($result['updates'] as $row) {
                    $oldPayment = $this->developmentCharge->getPaymentById($row[0]);
                    $this->developmentCharge->updatePendingPaymentSchedule($row[0], $id, $row[1], $row[2]);
                    $this->audit('update', 'development_charge_payment', $row[0], ['before' => $oldPayment, 'after' => array_merge($oldPayment ?: [], ['amount' => $row[1], 'due_date' => $row[2]]), 'schedule' => true]);
                }
                return $result;
            });
        } catch (DomainException $error) {
            // atomic() rolled back. Unexpected domain failures still propagate.
            if ($result === null || !$result['errors']) { throw $error; }
        }
        if ($result === null) {
            header('Location: ' . ReportingYear::url('charge?error=charge_not_found'));
            exit;
        }
        if ($result['errors']) {
            $this->showChargeErrors($result['errors'], $id);
            return;
        }
        header('Location: ' . ReportingYear::url('charge?success=charge_updated'));
        exit;
    }

    public function index()
    {
        $year = ReportingYear::selected();
        $charges = $this->developmentCharge->getAllByYear($year);
        foreach ($charges as &$charge) {
            $totalPaid = (float) $this->developmentCharge->getTotalPaid($charge['id']);
            $charge['total_paid'] = $totalPaid;
            $charge['payment_status'] = $totalPaid <= 0 ? 'Belum Dibayar' : ($totalPaid < (float) $charge['charge_amount'] ? 'Bayaran Sebahagian' : 'Selesai Dibayar');
        }
        unset($charge);
        $this->renderModule('charge/index.php', ['developmentCharges' => $charges, 'selectedYear' => $year, 'yearModule' => 'charge']);
    }

    public function edit($id)
    {
        $charge = $this->developmentCharge->getById($id);
        if (!$charge) {
            header('Location: ' . ReportingYear::url('charge?error=charge_not_found'));
            exit;
        }
        $payments = $this->developmentCharge->getPayments($id);
        $this->renderModule('charge/edit.php', [
            'charge' => $charge,
            'payments' => $payments,
            'hasPayments' => !empty($payments),
            'hasPaidPayments' => $this->developmentCharge->hasPaidPayments($id)
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderModule('charge/create.php');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('charge'));
            exit;
        }
        $result = ChargeValidation::validate($_POST);
        $result['data']['record_year'] = ReportingYear::selected();
        if ($result['errors']) {
            $this->showChargeErrors($result['errors']);
            return;
        }
        $this->atomic(function () use ($result) {
            $id = $this->developmentCharge->create($result['data']);
            if (!$id) { throw new RuntimeException('Charge insertion failed.'); }
            foreach ($result['new'] as $row) {
                $paymentId = $this->developmentCharge->addPaymentSchedule($id, $row[0], $row[1], $row[2]);
                $this->audit('create', 'development_charge_payment', $paymentId, ['after' => ['development_charge_id' => $id, 'installment_no' => $row[0], 'amount' => $row[1], 'due_date' => $row[2], 'status' => 'Menunggu']]);
            }
            $this->audit('create', 'development_charge', $id, ['after' => $result['data']]);
        });
        header('Location: ' . ReportingYear::url('charge?success=charge_added'));
        exit;
    }

    private function showChargeErrors(array $errors, $id = null)
    {
        $this->response->setStatusCode(422);
        http_response_code(422);
        $data = ['validationErrors' => $errors, 'oldInput' => $_POST];
        if ($id !== null) {
            $data['charge'] = $this->developmentCharge->getById($id);
            $data['payments'] = $this->developmentCharge->getPayments($id);
            foreach ($data['payments'] as &$payment) {
                if ($payment['status'] !== 'Menunggu' || !isset($_POST['payment_schedule_id']) || !is_array($_POST['payment_schedule_id'])) { continue; }
                foreach ($_POST['payment_schedule_id'] as $index => $postedId) {
                    if (!is_scalar($postedId) || (string) $postedId !== (string) $payment['id']) { continue; }
                    foreach (['amount' => 'payment_schedule_amount', 'due_date' => 'payment_schedule_due_date'] as $field => $key) {
                        if (isset($_POST[$key]) && is_array($_POST[$key]) && isset($_POST[$key][$index]) && is_string($_POST[$key][$index])) { $payment[$field] = $_POST[$key][$index]; }
                    }
                }
            }
            unset($payment);
            $data['hasPayments'] = !empty($data['payments']);
            $data['hasPaidPayments'] = $this->developmentCharge->hasPaidPayments($id);
            foreach (['developer_name', 'project_name', 'osc_id', 'lot_number', 'mukim', 'file_number', 'charge_amount', 'osc_decision', 'payment_plan'] as $key) {
                if (isset($_POST[$key]) && is_string($_POST[$key]) && !($key === 'payment_plan' && $data['hasPaidPayments'])) {
                    $data['charge'][$key] = $_POST[$key];
                }
            }
        }
        $this->renderModule($id === null ? 'charge/create.php' : 'charge/edit.php', $data);
    }

    private function paymentError(array $payment, $message)
    {
        $this->response->setStatusCode(422);
        http_response_code(422);
        $this->renderModule('charge/payment.php', [
            'charge' => $this->developmentCharge->getById($payment['development_charge_id']),
            'payments' => $this->developmentCharge->getPayments($payment['development_charge_id']),
            'validationErrors' => [$message], 'paymentFormId' => $payment['id'], 'oldPaymentInput' => $_POST
        ]);
    }

    private function receiptDirectory()
    {
        $configured = Config::get('RECEIPT_STORAGE_PATH');
        return $configured ? rtrim($configured, '/') . '/' : dirname(__DIR__, 2) . '/public/uploads/receipts/';
    }

    public function payment($paymentId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $charge = $this->developmentCharge->getById($paymentId);
            if (!$charge) {
                header('Location: ' . ReportingYear::url('charge?error=charge_not_found'));
                exit;
            }
            $this->renderModule('charge/payment.php', [
                'charge' => $charge, 'payments' => $this->developmentCharge->getPayments($paymentId)
            ]);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('charge'));
            exit;
        }


        $payment =
            $this->developmentCharge
                ->getPaymentById(
                    $paymentId
                );


        if (!$payment) {

            header(
                'Location: ' . ReportingYear::url('charge?error=payment_not_found')
            );

            exit;
        }


        $paymentDate =
            isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';


        $receiptNumber = ChargeValidation::text(isset($_POST['receipt_number']) ? $_POST['receipt_number'] : null);
        if ($receiptNumber === '' || mb_strlen($receiptNumber, 'UTF-8') > 100) {
            $this->paymentError($payment, 'No. Resit wajib diisi (maksimum 100 aksara).');
            return;
        }
        if (!ChargeValidation::date($paymentDate)) {
            $this->paymentError($payment, 'Tarikh Bayaran / Tarikh Resit mesti tarikh kalendar sah.');
            return;
        }
        if ($payment['status'] === 'Dibayar') {
            $this->paymentError($payment, 'Bayaran telah direkodkan. Muat semula halaman; bayaran yang sama tidak boleh direkodkan semula.');
            return;
        }
        if (!isset($_FILES['receipt']) || !is_array($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            $this->paymentError($payment, 'Fail resit wajib dimuat naik. JPG, PNG atau PDF, maksimum 5 MB.');
            return;
        }

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
                    'Location: ' . ReportingYear::url('charge?error=receipt_too_large')
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
                    'Location: ' . ReportingYear::url('charge?error=invalid_receipt')
                );

                exit;
            }


            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
            if (!isset($mimes[$extension]) || $mime !== $mimes[$extension]) {
                $this->paymentError($payment, 'Kandungan fail resit tidak sepadan dengan format JPG, PNG atau PDF.');
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | UPLOAD DIRECTORY
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                $this->receiptDirectory();


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
                bin2hex(random_bytes(12)) .
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
                    'Location: ' . ReportingYear::url('charge?error=receipt_upload_failed')
                );

                exit;
            }


            $receiptPath =
                'uploads/receipts/' .
                $fileName;
        }

        try {
            $this->atomic(function () use ($payment, $paymentId, $paymentDate, $receiptPath, $receiptNumber) {
                $this->developmentCharge->lockById($payment['development_charge_id']);
                $current = $this->developmentCharge->getPaymentById($paymentId);
                if (!$current || $current['status'] !== 'Menunggu') {
                    throw new DomainException('Bayaran telah direkodkan. Muat semula halaman.');
                }
        $this->developmentCharge
            ->markPaymentPaid(
                $paymentId,
                $paymentDate,
                $receiptPath,
                $receiptNumber
            );
        $this->audit('update', 'development_charge_payment', $paymentId, ['before' => $current, 'after' => array_merge($current, ['status' => 'Dibayar', 'payment_date' => $paymentDate, 'receipt_file' => $receiptPath, 'receipt_number' => $receiptNumber]), 'receipt' => true]);


            });
        } catch (Throwable $error) {
            // Only this request's newly uploaded file; never an existing receipt.
            if ($receiptPath) {
                $newFile = $this->receiptDirectory() . basename($receiptPath);
                if (is_file($newFile) && !is_link($newFile)) { unlink($newFile); }
            }
            if ($error instanceof DomainException) {
                $this->paymentError($payment, $error->getMessage());
                return;
            }
            throw $error;
        }

        header(
            'Location: ' . ReportingYear::url('charge/payment/' . $payment['development_charge_id'] . '?success=charge_payment_recorded')
        );

        exit;
    }

    public function cancelPayment($paymentId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . ReportingYear::url('charge')
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
                'Location: ' . ReportingYear::url('charge?error=payment_not_found')
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | RESET PAYMENT
        |--------------------------------------------------------------------------
        */

        $cancelled = $this->atomic(function () use ($paymentId, $payment) {
            if (!$this->developmentCharge->lockById($payment['development_charge_id'])) { return false; }
            $current = $this->developmentCharge->getPaymentById($paymentId, true);
            if (!$current || (int) $current['development_charge_id'] !== (int) $payment['development_charge_id']) { return false; }

            $reset = ['status' => 'Menunggu', 'payment_date' => null, 'receipt_file' => null, 'receipt_number' => null];
            $expected = $this->expectedChangedRows($current, $reset + ['updated_by' => AuditLog::actorId()]);
            if ($this->developmentCharge->cancelPayment($paymentId) !== $expected) {
                throw new RuntimeException('Charge payment cancel failed.');
            }
            $this->audit('update', 'development_charge_payment', $paymentId, ['before' => $current, 'after' => array_merge($current, $reset), 'receipt' => false]);
            return $current;
        });

        if (!$cancelled) {
            header('Location: ' . ReportingYear::url('charge?error=payment_not_found'));
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE RECEIPT FILE
        |--------------------------------------------------------------------------
        | Only after commit: a rolled-back cancel still references the receipt.
        */

        if (!empty($cancelled['receipt_file']) && preg_match('~\Auploads/receipts/receipt_[0-9]+_[a-zA-Z0-9]+\.(?:jpg|jpeg|png|pdf)\z~D', $cancelled['receipt_file'])) {

            $filePath =
                $this->receiptDirectory() . basename($cancelled['receipt_file']);


            if (is_file($filePath) && !is_link($filePath)) {
                unlink($filePath);
            }
        }

        header(
            'Location: ' . ReportingYear::url('charge/payment/' . $payment['development_charge_id'] . '?success=charge_payment_cancelled')
        );

        exit;
    }
    public function generatePaymentSchedule($chargeId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . ReportingYear::url('charge')
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
                'Location: ' . ReportingYear::url('charge?error=charge_not_found')
            );

            exit;
        }


        if ($charge['payment_plan'] === '3 Ansuran') {
            $this->response->setStatusCode(422);
            http_response_code(422);
            $this->renderModule('charge/payment.php', [
                'charge' => $charge, 'payments' => $this->developmentCharge->getPayments($chargeId),
                'validationErrors' => ['Jadual 3 Ansuran bagi rekod sedia ada tidak dijana semula secara automatik. Jadual lama dikekalkan.']
            ]);
            return;
        }

        $existingPayments =
            $this->developmentCharge
                ->getPayments(
                    $chargeId
                );


        if (!empty($existingPayments)) {

            header(
                'Location: ' . ReportingYear::url('charge')
            );

            exit;
        }


        $this->atomic(function () use ($chargeId, $charge) {
            $this->developmentCharge->lockById($chargeId);
            if ($this->developmentCharge->getPayments($chargeId)) { return; }
            /*
            |--------------------------------------------------------------------------
            | BAYARAN PENUH
            |--------------------------------------------------------------------------
            */

            if (
            $charge['payment_plan'] === 'Bayaran Penuh'
            ) {

            $paymentId = $this->developmentCharge
                ->addPaymentSchedule(
                    $chargeId,
                    1,
                    $charge['charge_amount'],
                    null
                );
            $this->audit('create', 'development_charge_payment', $paymentId, ['after' => ['development_charge_id' => $chargeId, 'installment_no' => 1, 'amount' => $charge['charge_amount'], 'due_date' => null, 'status' => 'Menunggu']]);
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

                $paymentId = $this->developmentCharge
                    ->addPaymentSchedule(
                        $chargeId,
                        $i,
                        $amount,
                        null
                    );
                $this->audit('create', 'development_charge_payment', $paymentId, ['after' => ['development_charge_id' => $chargeId, 'installment_no' => $i, 'amount' => $amount, 'due_date' => null, 'status' => 'Menunggu']]);
            }
            }


        });

        header(
            'Location: ' . ReportingYear::url('charge/payment/' . $charge['id'] . '?success=charge_schedule_generated')
        );

        exit;
    }
}
