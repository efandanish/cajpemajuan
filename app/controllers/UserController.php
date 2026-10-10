<?php

class UserController extends Controller
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('User');
        $this->loadModel('Notification');


        if (
            session_status() ===
            PHP_SESSION_NONE
        ) {
            session_start();
        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN REQUIRED
        |--------------------------------------------------------------------------
        */

        if (empty($_SESSION['user_id'])) {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN ONLY
        |--------------------------------------------------------------------------
        */

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
        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_required'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_email_invalid'
            );

            exit;
        }


        if (
            strlen($password) < 6
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_password_short'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_email_exists'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE USER
        |--------------------------------------------------------------------------
        */

        $this->user->create([
            'name' =>
                $name,

            'email' =>
                $email,

            'password' =>
                $password,

            'role' =>
                $role
        ]);


        /*
        |--------------------------------------------------------------------------
        | NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $this->notification->create([
            'category' =>
                'system',

            'action' =>
                'user_created',

            'title' =>
                'Pengguna Baru Didaftarkan',

            'message' =>
                $name .
                ' telah didaftarkan sebagai ' .
                (
                    $role === 'admin'
                    ? 'Pentadbir.'
                    : 'Pengguna Biasa.'
                ),

            'entity_type' =>
                'user',

            'entity_id' =>
                null
        ]);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=user_created'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }


        $existingUser =
            $this->user->getById($id);


        if (!$existingUser) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_not_found'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_required'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_email_invalid'
            );

            exit;
        }


        if (
            !empty($password) &&
            strlen($password) < 6
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_password_short'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_email_exists'
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


        $this->user->updateUser(
            $id,
            [
                'name' =>
                    $name,

                'email' =>
                    $email,

                'password' =>
                    $password,

                'role' =>
                    $role
            ]
        );


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


        $this->notification->create([
            'category' =>
                'system',

            'action' =>
                'user_updated',

            'title' =>
                'Maklumat Pengguna Dikemaskini',

            'message' =>
                $name .
                ' telah dikemaskini.',

            'entity_type' =>
                'user',

            'entity_id' =>
                $id
        ]);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=user_updated'
        );

        exit;
    }


    public function delete($id)
    {
        if (
            $_SERVER['REQUEST_METHOD']
            !==
            'POST'
        ) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
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
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=cannot_delete_self'
            );

            exit;
        }


        $user =
            $this->user->getById($id);


        if (!$user) {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard?error=user_not_found'
            );

            exit;
        }


        $this->notification->create([
            'category' =>
                'system',

            'action' =>
                'user_deleted',

            'title' =>
                'Pengguna Dipadam',

            'message' =>
                $user['name'] .
                ' telah dipadam daripada sistem.',

            'entity_type' =>
                'user',

            'entity_id' =>
                $id
        ]);


        $this->user
            ->deleteUser($id);


        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard?success=user_deleted'
        );

        exit;
    }
}