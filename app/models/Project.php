<?php

class Project extends Model
{
    private function access($alias = 'p')
    {
        return ProjectAccessPolicy::condition($alias);
    }

    private function bindAccess($database, array $access)
    {
        if ($access['department'] !== null) {
            $database->bindValue(':project_access_department', $access['department']);
        }
    }

    public function getAll()
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $database->prepare("
            SELECT p.*
            FROM projects p
            WHERE {$access['sql']}
            ORDER BY created_at DESC
        ");
        $this->bindAccess($database, $access);

        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function lockById($id)
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare("SELECT p.* FROM projects p WHERE p.id = :id AND {$access['sql']} FOR UPDATE");
        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function getById($id)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $database->prepare("
            SELECT p.*
            FROM projects p
            WHERE p.id = :id AND {$access['sql']}
            LIMIT 1
        ");

        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);
        $database->execute();

        return $database->fetchAssociative();
    }


    public function projectIdExists($projectId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare("SELECT p.id FROM projects p WHERE p.project_id = :project_id AND {$access['sql']} LIMIT 1");
        $database->bindValue(':project_id', $projectId);
        $this->bindAccess($database, $access);
        $database->execute();
        return (bool) $database->fetchAssociative();
    }

    public function getUndated()
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare("SELECT p.* FROM projects p WHERE p.start_date IS NULL AND {$access['sql']} ORDER BY p.created_at DESC");
        $this->bindAccess($database, $access);
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function create($data)
    {
        $database = Database::openConnection();

        $query = "
        INSERT INTO projects (
            project_id,
            department,
            project_name,
            description,
            approval_status,
            meeting_count,
            procurement_type,
            sst_value,
            meeting_approval_value,
            payment_method,
            start_date,
            end_date,
            project_status,
            created_by,
            updated_by
        )
        VALUES (
            :project_id,
            :department,
            :project_name,
            :description,
            :approval_status,
            :meeting_count,
            :procurement_type,
            :sst_value,
            :meeting_approval_value,
            :payment_method,
            :start_date,
            :end_date,
            :project_status,
            :created_by,
            :updated_by
        )
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $data['project_id']
        );

        $database->bindValue(
            ':department',
            $data['department']
        );

        $database->bindValue(
            ':project_name',
            $data['project_name']
        );

        $database->bindValue(
            ':description',
            $data['description']
        );

        $database->bindValue(
            ':approval_status',
            $data['approval_status']
        );

        $database->bindValue(
            ':meeting_count',
            $data['meeting_count']
        );

        $database->bindValue(
            ':procurement_type',
            $data['procurement_type']
        );

        $database->bindValue(
            ':sst_value',
            $data['sst_value']
        );

        $database->bindValue(
            ':meeting_approval_value',
            $data['meeting_approval_value']
        );

        $database->bindValue(
            ':payment_method',
            $data['payment_method']
        );

        $database->bindValue(
            ':start_date',
            $data['start_date'] ?: null
        );

        $database->bindValue(
            ':end_date',
            $data['end_date'] ?: null
        );

        $database->bindValue(
            ':project_status',
            $data['project_status']
        );
        $database->bindValue(':created_by', AuditLog::actorId());
        $database->bindValue(':updated_by', AuditLog::actorId());

        $database->execute();

        if ($database->countRows() !== 1) {
            return false;
        }

        return $database->lastInsertId();
    }


    public function update($id, $data)
    {
        $database = Database::openConnection();
        $access = $this->access('projects');

        $database->prepare("
            UPDATE projects
            SET
                project_id = :project_id,
                department = :department,
                project_name = :project_name,
                description = :description,
                approval_status = :approval_status,
                meeting_count = :meeting_count,
                procurement_type = :procurement_type,
                sst_value = :sst_value,
                meeting_approval_value = :meeting_approval_value,
                payment_method = :payment_method,
                start_date = :start_date,
                end_date = :end_date,
                project_status = :project_status,
                updated_by = :updated_by
            WHERE id = :id AND {$access['sql']}
        ");

        $database->bindValue(':project_id', $data['project_id']);
        $database->bindValue(':department', $data['department']);
        $database->bindValue(':project_name', $data['project_name']);
        $database->bindValue(':description', $data['description']);
        $database->bindValue(':approval_status', $data['approval_status']);
        $database->bindValue(':meeting_count', $data['meeting_count']);
        $database->bindValue(':procurement_type', $data['procurement_type']);
        $database->bindValue(':sst_value', $data['sst_value']);
        $database->bindValue(':meeting_approval_value', $data['meeting_approval_value']);
        $database->bindValue(':payment_method', $data['payment_method']);
        $database->bindValue(':start_date', $data['start_date'] ?: null);
        $database->bindValue(':end_date', $data['end_date'] ?: null);
        $database->bindValue(':project_status', $data['project_status']);
        $database->bindValue(':updated_by', AuditLog::actorId());
        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);

        return $database->execute();
    }


    public function delete($id)
    {
        $database = Database::openConnection();
        $access = $this->access('projects');

        $database->prepare("
            DELETE FROM projects
            WHERE id = :id AND {$access['sql']}
        ");

        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);

        if (!$database->execute()) {
            return 0;
        }

        return (int) $database->countRows();
    }


    public function addSchedule($projectId, $date, $amount, $type)
    {
        $database = Database::openConnection();

        $database->prepare("
            INSERT INTO project_payment_schedules (
                project_id,
                claim_date,
                amount,
                type,
                created_by,
                updated_by
            )
            VALUES (
                :project_id,
                :claim_date,
                :amount,
                :type,
                :created_by,
                :updated_by
            )
        ");

        $database->bindValue(':project_id', $projectId);
        $database->bindValue(':claim_date', $date);
        $database->bindValue(':amount', $amount);
        $database->bindValue(':type', $type);
        $database->bindValue(':created_by', AuditLog::actorId());
        $database->bindValue(':updated_by', AuditLog::actorId());

        $database->execute();
        return $database->lastInsertId();
    }


    // $lock = true reads the latest committed rows and holds them until the transaction ends.
    public function getSchedules($projectId, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $database->prepare("
            SELECT s.*
            FROM project_payment_schedules s
            INNER JOIN projects p ON p.id = s.project_id
            WHERE s.project_id = :project_id AND {$access['sql']}
            ORDER BY claim_date ASC
        " . ($lock ? ' FOR UPDATE' : ''));

        $database->bindValue(':project_id', $projectId);
        $this->bindAccess($database, $access);
        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function deleteSchedules($projectId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $database->prepare("
            DELETE s FROM project_payment_schedules s
            INNER JOIN projects p ON p.id = s.project_id
            WHERE s.project_id = :project_id AND {$access['sql']}
        ");

        $database->bindValue(':project_id', $projectId);
        $this->bindAccess($database, $access);

        if (!$database->execute()) {
            return 0;
        }

        return (int) $database->countRows();
    }

    public function scheduleHasExpenses($scheduleId, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare("SELECT COUNT(*) AS total
            FROM project_expenses e
            INNER JOIN projects p ON p.id = e.project_id
            WHERE e.schedule_id = :id AND {$access['sql']}" . ($lock ? ' FOR UPDATE' : ''));
        $database->bindValue(':id', $scheduleId);
        $this->bindAccess($database, $access);
        $database->execute();
        return (int) $database->fetchAssociative()['total'] > 0;
    }

    public function getScheduleById($id, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT s.*
        FROM project_payment_schedules s
        INNER JOIN projects p ON p.id = s.project_id
        WHERE s.id = :id AND {$access['sql']}
        LIMIT 1
    " . ($lock ? ' FOR UPDATE' : '');

        $database->prepare($query);

        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);

        $database->execute();

        return $database->fetchAssociative();
    }


    public function addExpense(
        $projectId,
        $scheduleId,
        $amount,
        $paymentDate,
        $description
    ) {
        $database = Database::openConnection();

        $query = "
        INSERT INTO project_expenses (
            project_id,
            schedule_id,
            amount,
            payment_date,
            description,
            created_by,
            updated_by
        )
        VALUES (
            :project_id,
            :schedule_id,
            :amount,
            :payment_date,
            :description,
            :created_by,
            :updated_by
        )
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );

        $database->bindValue(
            ':schedule_id',
            $scheduleId
        );

        $database->bindValue(
            ':amount',
            $amount
        );

        $database->bindValue(
            ':payment_date',
            $paymentDate
        );

        $database->bindValue(
            ':description',
            $description
        );
        $database->bindValue(':created_by', AuditLog::actorId());
        $database->bindValue(':updated_by', AuditLog::actorId());

        $database->execute();
        return $database->lastInsertId();
    }


    public function markSchedulePaid($scheduleId)
    {
        $database = Database::openConnection();

        $query = "
        UPDATE project_payment_schedules
        SET status = 'Dibayar', updated_by = :updated_by
        WHERE id = :id
    ";

        $database->prepare($query);

        $database->bindValue(
            ':id',
            $scheduleId
        );
        $database->bindValue(':updated_by', AuditLog::actorId());

        return $database->execute();
    }


    public function getExpenses($projectId, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT
            e.*,
            project_payment_schedules.claim_date,
            project_payment_schedules.type AS schedule_type
        FROM project_expenses e

        INNER JOIN projects p
            ON p.id = e.project_id

        LEFT JOIN project_payment_schedules
            ON e.schedule_id =
               project_payment_schedules.id

        WHERE e.project_id = :project_id AND {$access['sql']}

        ORDER BY e.payment_date DESC,
                 e.id DESC
    " . ($lock ? ' FOR UPDATE' : '');

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );
        $this->bindAccess($database, $access);

        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function getTotalExpenses($projectId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT
            COALESCE(SUM(amount), 0) AS total
        FROM project_expenses e
        INNER JOIN projects p ON p.id = e.project_id
        WHERE e.project_id = :project_id AND {$access['sql']}
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );
        $this->bindAccess($database, $access);

        $database->execute();

        $result = $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return $result['total'];
    }
    public function getExpenseById($id, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT e.*
        FROM project_expenses e
        INNER JOIN projects p ON p.id = e.project_id
        WHERE e.id = :id AND {$access['sql']}
        LIMIT 1
    " . ($lock ? ' FOR UPDATE' : '');

        $database->prepare($query);
        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);
        $database->execute();

        return $database->fetchAssociative();
    }


    public function deleteExpense($id)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        DELETE e FROM project_expenses e
        INNER JOIN projects p ON p.id = e.project_id
        WHERE e.id = :id AND {$access['sql']}
    ";

        $database->prepare($query);
        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);

        if (!$database->execute()) {
            return 0;
        }

        return (int) $database->countRows();
    }


    public function markSchedulePending($scheduleId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        UPDATE project_payment_schedules s
        INNER JOIN projects p ON p.id = s.project_id
        SET s.status = 'Menunggu', s.updated_by = :updated_by
        WHERE s.id = :id AND {$access['sql']}
    ";

        $database->prepare($query);
        $database->bindValue(':id', $scheduleId);
        $database->bindValue(':updated_by', AuditLog::actorId());
        $this->bindAccess($database, $access);

        if (!$database->execute()) {
            return 0;
        }

        // Changed rows: an UPDATE that rewrites identical values reports 0.
        return (int) $database->countRows();
    }

    /** Retained recorded expenditure, by actual payment date rather than project cohort. */
    public function getExpenditureByPaymentYear($year)
    {
        if (!ReportingYear::valid($year)) {
            throw new InvalidArgumentException('Tahun perbelanjaan tidak sah.');
        }
        $database = Database::openConnection();
        $access = $this->access('p');
        // No schedule/project join: each recorded expense contributes exactly once.
        // An unassignable date could belong to any year; do not present an incomplete total.
        $database->prepare("
            SELECT COALESCE(SUM(CASE WHEN in_period = 1 THEN amount ELSE 0.00 END), 0.00) AS total,
                COALESCE(SUM(CASE WHEN invalid_date = 1
                    OR (in_period = 1 AND (amount IS NULL OR amount < 0)) THEN 1 ELSE 0 END), 0) AS unverified_count
            FROM (
                SELECT amount, payment_date BETWEEN :start_date AND :end_date AS in_period,
                    CASE WHEN payment_date IS NULL OR YEAR(payment_date) < 1000
                            OR MONTH(payment_date) = 0 OR DAYOFMONTH(payment_date) = 0 THEN 1
                        WHEN LAST_DAY(payment_date) IS NULL
                            OR DAYOFMONTH(payment_date) > DAYOFMONTH(LAST_DAY(payment_date)) THEN 1
                        ELSE 0 END AS invalid_date
                FROM project_expenses e
                INNER JOIN projects p ON p.id = e.project_id
                WHERE {$access['sql']}
            ) recorded_expenses
        ");
        $database->bindValue(':start_date', sprintf('%04d-01-01', (int) $year));
        $database->bindValue(':end_date', sprintf('%04d-12-31', (int) $year));
        $this->bindAccess($database, $access);
        $database->execute();
        $result = $database->fetchAssociative();
        $unverified = (int) $result['unverified_count'];
        // MariaDB sums DECIMAL values. Keep the exact decimal string, including verified zero.
        return ['total' => $unverified ? null : $result['total'], 'unverified_count' => $unverified];
    }

    public function getDashboardSummaryByYear($year)
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        // Match getAllByYear's cohort and getTotalExpenses's recorded expenses.
        $database->prepare("
            SELECT COUNT(DISTINCT p.id) AS record_count,
                COALESCE(SUM(e.amount), 0) AS expenditure_amount
            FROM projects p
            LEFT JOIN project_expenses e ON e.project_id = p.id
            WHERE YEAR(p.start_date) = :year AND {$access['sql']}
        ");
        $database->bindValue(':year', (int) $year);
        $this->bindAccess($database, $access);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function getAllByYear($year)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT p.*
        FROM projects p
        WHERE YEAR(p.start_date) = :year AND {$access['sql']}
        ORDER BY created_at DESC
    ";

        $database->prepare($query);

        $database->bindValue(
            ':year',
            $year
        );
        $this->bindAccess($database, $access);

        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function addVariation(
        $projectId,
        $description,
        $type,
        $amount,
        $date
    ) {
        $database = Database::openConnection();

        $query = "
        INSERT INTO project_variations (
            project_id,
            description,
            variation_type,
            amount,
            variation_date,
            created_by,
            updated_by
        )
        VALUES (
            :project_id,
            :description,
            :variation_type,
            :amount,
            :variation_date,
            :created_by,
            :updated_by
        )
    ";

        $database->prepare($query);

        $database->bindValue(':project_id', $projectId);
        $database->bindValue(':description', $description);
        $database->bindValue(':variation_type', $type);
        $database->bindValue(':amount', $amount);
        $database->bindValue(':variation_date', $date);
        $database->bindValue(':created_by', AuditLog::actorId());
        $database->bindValue(':updated_by', AuditLog::actorId());

        $database->execute();
        return $database->lastInsertId();
    }


    public function getVariations($projectId, $lock = false)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT v.*
        FROM project_variations v
        INNER JOIN projects p ON p.id = v.project_id
        WHERE v.project_id = :project_id AND {$access['sql']}
        ORDER BY variation_date DESC, id DESC
    " . ($lock ? ' FOR UPDATE' : '');

        $database->prepare($query);
        $database->bindValue(':project_id', $projectId);
        $this->bindAccess($database, $access);
        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function getVariationById($id)
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare('SELECT v.* FROM project_variations v
            INNER JOIN projects p ON p.id = v.project_id
            WHERE v.id = :id AND ' . $access['sql'] . ' LIMIT 1');
        $database->bindValue(':id', $id);
        $this->bindAccess($database, $access);
        $database->execute();
        return $database->fetchAssociative();
    }


    public function getVariationTotal($projectId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN variation_type = 'Tambah'
                            THEN amount
                        WHEN variation_type = 'Tolak'
                            THEN -amount
                        ELSE 0
                    END
                ),
                0
            ) AS total
        FROM project_variations v
        INNER JOIN projects p ON p.id = v.project_id
        WHERE v.project_id = :project_id AND {$access['sql']}
    ";

        $database->prepare($query);
        $database->bindValue(':project_id', $projectId);
        $this->bindAccess($database, $access);
        $database->execute();

        $result = $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return $result['total'];
    }

    public function deleteSchedule($scheduleId)
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $query = "
        DELETE s FROM project_payment_schedules s
        INNER JOIN projects p ON p.id = s.project_id
        WHERE s.id = :id
        AND s.status = 'Menunggu'
        AND {$access['sql']}
    ";

        $database->prepare($query);
        $database->bindValue(':id', $scheduleId);
        $this->bindAccess($database, $access);

        if (!$database->execute()) {
            return 0;
        }

        return (int) $database->countRows();
    }

    public function getOverdueProjects()
    {
        $database = Database::openConnection();
        $access = $this->access('p');
        $database->prepare("SELECT p.id, p.project_name, p.department, p.end_date FROM projects p
            WHERE p.end_date IS NOT NULL AND p.end_date < :today
            AND {$access['sql']}
            AND p.project_status NOT IN ('Selesai', 'Dibatalkan')");
        $database->bindValue(':today', date('Y-m-d'));
        $this->bindAccess($database, $access);
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function getPendingSchedulesWithDueDate()
    {
        $database = Database::openConnection();
        $access = $this->access('p');

        $database->prepare("
        SELECT
            s.id,
            s.project_id,
            s.claim_date,
            s.amount,
            s.type,
            s.status,

            p.project_id AS project_code,
            p.project_name,
            p.department

        FROM project_payment_schedules s

        INNER JOIN projects p
            ON p.id = s.project_id

        WHERE
            s.status = 'Menunggu'
            AND s.claim_date IS NOT NULL
            AND p.project_status NOT IN ('Selesai', 'Dibatalkan')
            AND {$access['sql']}

        ORDER BY s.claim_date ASC
    ");

        $this->bindAccess($database, $access);
        $database->execute();

        return $database->fetchAllAssociative();
    }
}
