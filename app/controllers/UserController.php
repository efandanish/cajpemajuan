<?php

class UserController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('User');
        $this->loadModel('Notification');


        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();
        $this->requireAdmin();
        if ($this->request->isPost()) {
            foreach (['name', 'email', 'password', 'role', 'department'] as $key) {
                if (isset($_POST[$key]) && !is_string($_POST[$key])) {
                    http_response_code(422);
                    echo 'Maklumat pengguna tidak sah. Sila semak borang.';
                    exit;
                }
            }
        }

    }


    public function index()
    {
        $this->renderModule('user/index.php', ['users' => $this->user->getAll()]);
    }

    public function edit($id)
    {
        $user = $this->user->getById($id);
        if (!$user) {
            header('Location: ' . ReportingYear::url('user?error=user_not_found'));
            exit;
        }
        $this->renderModule('user/edit.php', ['user' => $user]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderModule('user/create.php');
            return;
        }

        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            header(
                'Location: ' . ReportingYear::url('user')
            );

            exit;
        }


        $name =
            isset($_POST['name'])
            ? trim($_POST['name'])
            : '';


        $email =
            isset($_POST['email'])
            ? trim($_POST['email'])
            : '';


        $password =
            isset($_POST['password'])
            ? $_POST['password']
            : '';


        $role =
            isset($_POST['role'])
            ? $_POST['role']
            : 'user';


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($name) ||
            empty($email) ||
            empty($password)
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_required')
            );

            exit;
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_email_invalid')
            );

            exit;
        }


        if (
            strlen($password) < 6
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_password_short')
            );

            exit;
        }


        if (
            $role !== 'admin' &&
            $role !== 'user'
        ) {
            $role = 'user';
        }


        if (
            $this->user
                ->emailExists($email)
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_email_exists')
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE USER
        |--------------------------------------------------------------------------
        */

        $department = isset($_POST['department']) ? trim($_POST['department']) : '';
        if (($department !== '' && !Departments::valid($department)) || ($role === 'user' && $department === '')) {
            $this->departmentError();
            return;
        }

        $this->atomic(function () use ($department, $name, $email, $password, $role) {
            $created = $this->user->create([
                'department' => $department === '' ? null : $department,
                'name' => $name, 'email' => $email, 'password' => $password, 'role' => $role
            ]);
            if (!$created) { throw new RuntimeException('User insertion failed.'); }


            $this->audit('create', 'user', $created, ['after' => ['name' => $name, 'email' => $email, 'role' => $role, 'department' => $department === '' ? null : $department, 'password_set' => true]]);
        });


        header(
            'Location: ' . ReportingYear::url('user?success=user_created')
        );

        exit;
    }


    public function update($id)
    {
        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            header(
                'Location: ' . ReportingYear::url('user')
            );

            exit;
        }


        $existingUser =
            $this->user->getById($id);


        if (!$existingUser) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_not_found')
            );

            exit;
        }


        $name =
            isset($_POST['name'])
            ? trim($_POST['name'])
            : '';


        $email =
            isset($_POST['email'])
            ? trim($_POST['email'])
            : '';


        $password =
            isset($_POST['password'])
            ? $_POST['password']
            : '';


        $role =
            isset($_POST['role'])
            ? $_POST['role']
            : 'user';


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($name) ||
            empty($email)
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_required')
            );

            exit;
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_email_invalid')
            );

            exit;
        }


        if (
            !empty($password) &&
            strlen($password) < 6
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_password_short')
            );

            exit;
        }


        if (
            $this->user
                ->emailExistsExcept(
                    $email,
                    $id
                )
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_email_exists')
            );

            exit;
        }


        if (
            $role !== 'admin' &&
            $role !== 'user'
        ) {
            $role = 'user';
        }


        /*
        |--------------------------------------------------------------------------
        | PREVENT CURRENT ADMIN REMOVING OWN ADMIN ROLE
        |--------------------------------------------------------------------------
        */

        if (
            (int) $id ===
            (int) $_SESSION['user_id']
        ) {
            $role =
                $existingUser['role'];
        }


        $department = array_key_exists('department', $_POST) ? trim($_POST['department']) : $existingUser['department'];
        if ($department !== null && $department !== '' && !Departments::valid($department)) {
            $this->departmentError();
            return;
        }

        $this->atomic(function () use ($id, $department, $name, $email, $password, $role, $existingUser) {
            if (!$this->user->updateUser($id, [
                'department' => $department === '' ? null : $department,
                'name' =>
                    $name,

                'email' =>
                    $email,

                'password' =>
                    $password,

                'role' =>
                    $role
            ])) { throw new RuntimeException('User update failed.'); }
            $this->audit('update', 'user', $id, [
                'before' => ['name' => $existingUser['name'], 'email' => $existingUser['email'], 'role' => $existingUser['role'], 'department' => $existingUser['department']],
                'after' => ['name' => $name, 'email' => $email, 'role' => $role, 'department' => $department === '' ? null : $department, 'password_changed' => $password !== '']
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | UPDATE CURRENT SESSION DETAILS
        |--------------------------------------------------------------------------
        */

        if (
            (int) $id ===
            (int) $_SESSION['user_id']
        ) {

            $_SESSION['user_name'] =
                $name;

            $_SESSION['user_email'] =
                $email;
        }


        header(
            'Location: ' . ReportingYear::url('user?success=user_updated')
        );

        exit;
    }


    private function departmentError()
    {
        $this->response->setStatusCode(422);
        http_response_code(422);
        echo '<p>Jabatan tidak sah. Pilih Jabatan yang disenaraikan; Jabatan wajib untuk pendaftaran pengguna biasa.</p>';
    }

    public function delete($id)
    {
        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            header(
                'Location: ' . ReportingYear::url('user')
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CANNOT DELETE YOURSELF
        |--------------------------------------------------------------------------
        */

        if (
            (int) $id ===
            (int) $_SESSION['user_id']
        ) {

            header(
                'Location: ' . ReportingYear::url('user?error=cannot_delete_self')
            );

            exit;
        }


        $user =
            $this->user->getById($id);


        if (!$user) {

            header(
                'Location: ' . ReportingYear::url('user?error=user_not_found')
            );

            exit;
        }


        $deleted = $this->atomic(function () use ($id) {
            // Locked snapshot (no password columns). Read receipts would cascade, so delete and audit them explicitly.
            $user = $this->user->getById($id, true);
            if (!$user) { return false; }
            $reads = $this->notification->getReadsForUser($id, true);

            if ($this->notification->deleteReadsByIds(array_column($reads, 'id')) !== count($reads)) {
                throw new RuntimeException('User notification read delete count mismatch.');
            }
            if ($this->user->deleteUser($id) !== 1) { throw new RuntimeException('User delete failed.'); }

            foreach ($reads as $read) {
                $this->audit('delete', 'notification_read', $read['id'], ['before' => $read, 'reason' => 'user_deleted']);
            }
            $this->audit('delete', 'user', $id, ['before' => [
                'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'],
                'role' => $user['role'], 'department' => $user['department']
            ]]);
            return true;
        });

        if (!$deleted) {
            header('Location: ' . ReportingYear::url('user?error=user_not_found'));
            exit;
        }


        header(
            'Location: ' . ReportingYear::url('user?success=user_deleted')
        );

        exit;
    }
}
