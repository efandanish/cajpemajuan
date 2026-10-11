<?php

class Notification extends Model
{
    public function getById($id)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM notifications WHERE id = :id LIMIT 1');
        $database->bindValue(':id', (int) $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function create($data)
    {
        $database = Database::openConnection();

        $query = "
            INSERT INTO notifications (
                category,
                action,
                title,
                message,
                entity_type,
                entity_id
            )
            VALUES (
                :category,
                :action,
                :title,
                :message,
                :entity_type,
                :entity_id
            )
        ";

        $database->prepare($query);

        $database->bindValue(
            ':category',
            $data['category']
        );

        $database->bindValue(
            ':action',
            $data['action']
        );

        $database->bindValue(
            ':title',
            $data['title']
        );

        $database->bindValue(
            ':message',
            $data['message']
        );

        $database->bindValue(
            ':entity_type',
            isset($data['entity_type'])
            ? $data['entity_type']
            : null
        );

        $database->bindValue(
            ':entity_id',
            isset($data['entity_id'])
            ? $data['entity_id']
            : null
        );

        return $database->execute();
    }

    public function getUnreadCount($userId)
    {
        $database = Database::openConnection();

        $query = "
            SELECT COUNT(*) AS total

            FROM notifications n

            LEFT JOIN notification_reads nr
                ON nr.notification_id = n.id
                AND nr.user_id = :user_id

            WHERE nr.id IS NULL
        ";

        $database->prepare($query);

        $database->bindValue(
            ':user_id',
            $userId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return (int) $result['total'];
    }


    public function markAsRead(
        $notificationId,
        $userId
    ) {
        $database = Database::openConnection();

        $query = "
            INSERT IGNORE INTO notification_reads (
                notification_id,
                user_id
            )
            VALUES (
                :notification_id,
                :user_id
            )
        ";

        $database->prepare($query);

        $database->bindValue(
            ':notification_id',
            $notificationId
        );

        $database->bindValue(
            ':user_id',
            $userId
        );

        return $database->execute();
    }


    public function markAllAsRead($userId)
    {
        $database = Database::openConnection();

        $query = "
            INSERT IGNORE INTO notification_reads (
                notification_id,
                user_id
            )

            SELECT
                id,
                :user_id

            FROM notifications
        ";

        $database->prepare($query);

        $database->bindValue(
            ':user_id',
            $userId
        );

        return $database->execute();
    }

    public function getAllForUser($userId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            n.*,

            CASE
                WHEN nr.id IS NULL
                THEN 0
                ELSE 1
            END AS is_read

        FROM notifications n

        LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id
            AND nr.user_id = :user_id

        ORDER BY n.created_at DESC
    ";

        $database->prepare($query);

        $database->bindValue(
            ':user_id',
            $userId
        );

        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function getRecentForUser($userId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            n.*,

            CASE
                WHEN nr.id IS NULL
                THEN 0
                ELSE 1
            END AS is_read

        FROM notifications n

        LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id
            AND nr.user_id = :user_id

        ORDER BY n.created_at DESC

        LIMIT 10
    ";

        $database->prepare($query);

        $database->bindValue(
            ':user_id',
            $userId
        );

        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function clearAll()
    {
        $database = Database::openConnection();

        $database->prepare("
        DELETE FROM notification_reads
    ");

        $database->execute();

        $database->prepare("
        DELETE FROM notifications
    ");

        return $database->execute();
    }
    public function existsForEntity(
        $action,
        $entityType,
        $entityId
    ) {
        $database = Database::openConnection();

        $database->prepare("
        SELECT id
        FROM notifications

        WHERE action = :action
        AND entity_type = :entity_type
        AND entity_id = :entity_id

        LIMIT 1
    ");

        $database->bindValue(
            ':action',
            $action
        );

        $database->bindValue(
            ':entity_type',
            $entityType
        );

        $database->bindValue(
            ':entity_id',
            $entityId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        return $result ? true : false;
    }
    public function generateChargePaymentReminders(
        $developmentChargeModel
    ) {
        $payments =
            $developmentChargeModel
                ->getPendingPaymentsWithDueDate();


        if (empty($payments)) {
            return;
        }


        $today =
            new DateTime(
                date('Y-m-d')
            );


        foreach ($payments as $payment) {

            if (empty($payment['due_date'])) {
                continue;
            }


            $dueDate =
                new DateTime(
                    $payment['due_date']
                );


            $difference =
                (int) $today
                    ->diff($dueDate)
                    ->format('%r%a');


            /*
            |--------------------------------------------------------------------------
            | OVERDUE
            |--------------------------------------------------------------------------
            */

            if ($difference < 0) {

                $action =
                    'charge_payment_overdue';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Tertunggak',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' telah melepasi tarikh akhir ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DUE TODAY
            |--------------------------------------------------------------------------
            */

            if ($difference === 0) {

                $action =
                    'charge_payment_due_today';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Perlu Dibayar Hari Ini',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar hari ini.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | WITHIN 7 DAYS
            |--------------------------------------------------------------------------
            */

            if (
                $difference > 0 &&
                $difference <= 7
            ) {

                $action =
                    'charge_payment_due_soon';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Akan Tiba',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar pada ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

            }

        }
    }

    public function generateProjectPaymentReminders(
        $projectModel
    ) {
        $schedules =
            $projectModel
                ->getPendingSchedulesWithDueDate();


        if (empty($schedules)) {
            return;
        }


        $today =
            new DateTime(
                date('Y-m-d')
            );


        foreach ($schedules as $schedule) {

            if (empty($schedule['claim_date'])) {
                continue;
            }


            $dueDate =
                new DateTime(
                    $schedule['claim_date']
                );


            $difference =
                (int) $today
                    ->diff($dueDate)
                    ->format('%r%a');


            /*
            |--------------------------------------------------------------------------
            | OVERDUE
            |--------------------------------------------------------------------------
            */

            if ($difference < 0) {

                $action =
                    'project_payment_overdue';


                if (
                    !$this->existsForEntity(
                        $action,
                        'project_payment_schedule',
                        $schedule['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Tuntutan Projek Tertunggak',

                        'message' =>
                            $schedule['project_code'] .
                            ' - Tuntutan bernilai RM ' .
                            number_format(
                                $schedule['amount'],
                                2
                            ) .
                            ' telah melepasi tarikh ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $schedule['claim_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'project_payment_schedule',

                        'entity_id' =>
                            $schedule['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DUE TODAY
            |--------------------------------------------------------------------------
            */

            if ($difference === 0) {

                $action =
                    'project_payment_due_today';


                if (
                    !$this->existsForEntity(
                        $action,
                        'project_payment_schedule',
                        $schedule['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Tuntutan Projek Perlu Dibayar Hari Ini',

                        'message' =>
                            $schedule['project_code'] .
                            ' - Tuntutan bernilai RM ' .
                            number_format(
                                $schedule['amount'],
                                2
                            ) .
                            ' perlu dibayar hari ini.',

                        'entity_type' =>
                            'project_payment_schedule',

                        'entity_id' =>
                            $schedule['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | WITHIN 7 DAYS
            |--------------------------------------------------------------------------
            */

            if (
                $difference > 0 &&
                $difference <= 7
            ) {

                $action =
                    'project_payment_due_soon';


                if (
                    !$this->existsForEntity(
                        $action,
                        'project_payment_schedule',
                        $schedule['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Tuntutan Projek Akan Tiba',

                        'message' =>
                            $schedule['project_code'] .
                            ' - Tuntutan bernilai RM ' .
                            number_format(
                                $schedule['amount'],
                                2
                            ) .
                            ' dijadualkan pada ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $schedule['claim_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'project_payment_schedule',

                        'entity_id' =>
                            $schedule['id']
                    ]);

                }

            }

        }
    }
}
