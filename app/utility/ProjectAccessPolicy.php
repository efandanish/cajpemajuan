<?php

/**
 * Database-backed visibility policy for development projects.
 *
 * The authenticated user's current database row is deliberately read for
 * every policy evaluation. Session role/department values are not trusted.
 */
class ProjectAccessPolicy
{
    public static function condition($alias = 'p')
    {
        $userId = isset($_SESSION['user_id']) && is_scalar($_SESSION['user_id'])
            ? (int) $_SESSION['user_id'] : 0;
        $user = $userId > 0 ? (new User())->getById($userId) : false;

        if (!$user) {
            return ['sql' => '1 = 0', 'department' => null];
        }

        if (!in_array($user['role'], ['admin', 'user'], true)) {
            return ['sql' => '1 = 0', 'department' => null];
        }

        $department = isset($user['department']) ? trim((string) $user['department']) : '';
        if (self::isGlobalAdmin($user)) {
            return ['sql' => '1 = 1', 'department' => null];
        }

        if ($department === '') {
            return ['sql' => '1 = 0', 'department' => null];
        }

        return [
            'sql' => $alias . '.department = :project_access_department',
            'department' => $department
        ];
    }

    /**
     * Admin with no Jabatan: global visibility over projects, the audit log and notifications.
     * Pass a user row read from the database, never session or request values.
     */
    public static function isGlobalAdmin($user)
    {
        return is_array($user) && isset($user['role']) && $user['role'] === 'admin'
            && trim((string) (isset($user['department']) ? $user['department'] : '')) === '';
    }
}
