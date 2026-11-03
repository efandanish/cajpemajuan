<?php

/**
 * Readable, sanitised presentation of audit_logs rows, shared by audit-driven
 * notifications and the administrator Audit Log page. Pure functions: no database access.
 */
class AuditPresenter
{
    const PROJECT_TYPES = ['project', 'project_payment_schedule', 'project_expense', 'project_variation'];
    // No confirmed Caj Pemajuan -> Projek/Jabatan relationship yet: department stays unassigned.
    const CHARGE_TYPES = ['development_charge', 'development_charge_payment'];
    const UNASSIGNED = 'Belum Ditentukan';

    public static function details($raw)
    {
        $details = is_string($raw) && $raw !== '' ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        return is_array($details) ? $details : [];
    }

    /** Snapshot describing the record: previous values for DELETE, new values otherwise. */
    public static function snapshot($action, array $details)
    {
        $key = strtolower($action) === 'delete' ? 'before' : 'after';
        $snapshot = isset($details[$key]) && is_array($details[$key]) ? $details[$key] : [];
        if (!$snapshot && $key === 'after' && isset($details['before']) && is_array($details['before'])) { $snapshot = $details['before']; }
        return $snapshot;
    }

    /** 'upload', 'replace', 'remove' or null, from the payment before/after snapshots. */
    public static function receiptEvent($entityType, array $details)
    {
        if ($entityType !== 'development_charge_payment') { return null; }
        $before = isset($details['before']) && is_array($details['before']) ? $details['before'] : [];
        $after = isset($details['after']) && is_array($details['after']) ? $details['after'] : [];
        $had = self::hasReceipt($before);
        $has = self::hasReceipt($after);
        if (!$had && $has) { return 'upload'; }
        if ($had && !$has) { return 'remove'; }
        if ($had && $has && (self::value($before, 'receipt_file') !== self::value($after, 'receipt_file')
            || self::value($before, 'receipt_number') !== self::value($after, 'receipt_number'))) {
            return 'replace';
        }
        return null;
    }

    public static function receiptLabel($event)
    {
        return ['upload' => 'Resit dimuat naik', 'replace' => 'Resit diganti', 'remove' => 'Resit dibuang'][$event] ?? '';
    }

    public static function typeLabel($entityType, $receiptEvent = null)
    {
        if ($receiptEvent !== null) { return 'Resit Bayaran'; }
        return [
            'project' => 'Projek Pembangunan',
            'project_payment_schedule' => 'Jadual Bayaran Projek',
            'project_expense' => 'Perbelanjaan Projek',
            'project_variation' => 'Variasi Projek',
            'development_charge' => 'Caj Pemajuan',
            'development_charge_payment' => 'Bayaran Caj',
            'user' => 'Pengguna',
            'notification' => 'Notifikasi',
            'notification_read' => 'Bacaan Notifikasi'
        ][$entityType] ?? $entityType;
    }

    public static function departmentLabel($entityType, $resolved, $previous = null)
    {
        if (in_array($entityType, self::CHARGE_TYPES, true)) { return self::UNASSIGNED; }
        $label = $resolved !== null && $resolved !== '' ? $resolved : '—';
        return $previous !== null && $previous !== '' && $previous !== $resolved ? $previous . ' → ' . $label : $label;
    }

    public static function recordLabel($entityType, $entityId, array $snapshot, array $details = [])
    {
        if ($entityType === 'project') {
            $name = $snapshot['project_name'] ?? ('ID ' . ($snapshot['project_id'] ?? $entityId));
            return 'projek ' . $name;
        }
        if ($entityType === 'development_charge') {
            $reference = $snapshot['osc_id'] ?? ($snapshot['project_name'] ?? ('ID ' . $entityId));
            return 'caj pemajuan ' . $reference;
        }
        if ($entityType === 'user') {
            return 'pengguna ' . ($snapshot['name'] ?? ('ID ' . $entityId));
        }
        if ($entityType === 'project_payment_schedule') {
            return 'jadual bayaran #' . $entityId . ' bagi projek #' . ($snapshot['project_id'] ?? '?');
        }
        if ($entityType === 'project_expense') {
            return 'perbelanjaan projek #' . ($snapshot['project_id'] ?? '?') . ' (#' . $entityId . ')';
        }
        if ($entityType === 'project_variation') {
            return 'variasi projek #' . ($snapshot['project_id'] ?? '?') . ' (#' . $entityId . ')';
        }
        if ($entityType === 'development_charge_payment') {
            $charge = !empty($details['charge_reference']) ? $details['charge_reference'] : '#' . ($snapshot['development_charge_id'] ?? '?');
            return 'bayaran caj #' . $entityId . ' bagi caj ' . $charge;
        }
        return 'rekod #' . $entityId;
    }

    /** Business reference for the Audit Log table. */
    public static function reference($entityType, $entityId, array $snapshot, array $details)
    {
        if ($entityType === 'project') {
            return trim(($snapshot['project_id'] ?? ('#' . $entityId)) . ' · ' . ($snapshot['project_name'] ?? ''), ' ·');
        }
        if ($entityType === 'development_charge') {
            return trim(($snapshot['osc_id'] ?? ('#' . $entityId)) . ' · ' . ($snapshot['project_name'] ?? ''), ' ·');
        }
        if ($entityType === 'development_charge_payment') {
            $charge = !empty($details['charge_reference']) ? $details['charge_reference'] : '#' . ($snapshot['development_charge_id'] ?? '?');
            $installment = isset($snapshot['installment_no']) ? ' · Ansuran ' . $snapshot['installment_no'] : '';
            return 'Bayaran #' . $entityId . $installment . ' · Caj ' . $charge;
        }
        if (in_array($entityType, ['project_payment_schedule', 'project_expense', 'project_variation'], true)) {
            return '#' . $entityId . ' · Projek #' . ($snapshot['project_id'] ?? '?');
        }
        if ($entityType === 'user') {
            return ($snapshot['email'] ?? ($snapshot['name'] ?? '#' . $entityId));
        }
        return '#' . $entityId;
    }

    public static function verb($action, $receiptEvent = null)
    {
        if ($receiptEvent !== null) {
            return ['upload' => 'memuat naik resit bagi', 'replace' => 'menggantikan resit bagi', 'remove' => 'membuang resit bagi'][$receiptEvent];
        }
        return ['create' => 'mencipta', 'update' => 'mengemaskini', 'delete' => 'memadam'][strtolower($action)] ?? strtolower($action);
    }

    public static function changeSummary($action, array $details)
    {
        if (strtolower($action) !== 'update') { return ''; }
        $changes = isset($details['changes']) && is_array($details['changes']) ? $details['changes'] : [];
        if (!$changes && !empty($details['before']) && !empty($details['after']) && is_array($details['before']) && is_array($details['after'])) {
            foreach ($details['after'] as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at', 'created_by', 'updated_by'], true) || self::isSensitiveKey($key)) { continue; }
                if (!array_key_exists($key, $details['before']) || (string) $details['before'][$key] !== (string) (is_scalar($value) ? $value : json_encode($value))) {
                    $changes[] = $key;
                }
            }
        }
        return $changes ? ' (perubahan: ' . implode(', ', array_slice($changes, 0, 5)) . ')' : '';
    }

    /** One-line description; receipt events never include file paths or receipt numbers. */
    public static function description(array $audit, array $details)
    {
        $receipt = self::receiptEvent($audit['entity_type'], $details);
        $snapshot = self::snapshot($audit['action'], $details);
        $actor = $audit['actor_name'] !== null && $audit['actor_name'] !== '' ? $audit['actor_name'] : 'Sistem';
        return $actor . ' telah ' . self::verb($audit['action'], $receipt) . ' '
            . self::recordLabel($audit['entity_type'], (int) $audit['entity_id'], $snapshot, $details)
            . ($receipt === null ? self::changeSummary($audit['action'], $details) : '')
            . (!empty($details['previous_department']) && !empty($details['department'])
                ? ' (dipindahkan dari ' . $details['previous_department'] . ' ke ' . $details['department'] . ')' : '');
    }

    /** Field rows for before/after display, with secrets removed and paths reduced to a file name. */
    public static function fieldRows(array $details)
    {
        $before = isset($details['before']) && is_array($details['before']) ? self::sanitize($details['before']) : null;
        $after = isset($details['after']) && is_array($details['after']) ? self::sanitize($details['after']) : null;
        $keys = array_unique(array_merge($before ? array_keys($before) : [], $after ? array_keys($after) : []));
        $rows = [];
        foreach ($keys as $key) {
            $old = $before !== null && array_key_exists($key, $before) ? $before[$key] : null;
            $new = $after !== null && array_key_exists($key, $after) ? $after[$key] : null;
            $rows[] = ['field' => $key, 'before' => self::display($old), 'after' => self::display($new),
                'changed' => $before !== null && $after !== null && self::display($old) !== self::display($new)];
        }
        return ['has_before' => $before !== null, 'has_after' => $after !== null, 'rows' => $rows];
    }

    public static function sanitize(array $snapshot)
    {
        $clean = [];
        foreach ($snapshot as $key => $value) {
            if (self::isSensitiveKey($key)) { continue; }
            if ($key === 'receipt_file' && is_string($value) && $value !== '') { $value = basename($value); }
            $clean[$key] = is_array($value) ? self::sanitize($value) : $value;
        }
        return $clean;
    }

    private static function isSensitiveKey($key)
    {
        // 'password_changed' is a boolean flag, never a secret value.
        return $key !== 'password_changed' && (bool) preg_match('/pass(word)?|token|secret|hash|credential/i', (string) $key);
    }

    private static function display($value)
    {
        if ($value === null) { return '—'; }
        if (is_bool($value)) { return $value ? 'Ya' : 'Tidak'; }
        if (is_array($value)) { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
        return (string) $value;
    }

    private static function hasReceipt(array $snapshot)
    {
        return self::value($snapshot, 'receipt_file') !== '' || self::value($snapshot, 'receipt_number') !== '';
    }

    private static function value(array $snapshot, $key)
    {
        return isset($snapshot[$key]) && $snapshot[$key] !== null ? (string) $snapshot[$key] : '';
    }
}
